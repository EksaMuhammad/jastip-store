<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WalletTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Titik pusat orkestrasi bisnis untuk fitur pembayaran wajib (virtual escrow).
 * Pola sama seperti App\Services\OrderDealService: controller HANYA boleh
 * memanggil method di sini, tidak menyentuh state machine Payment/Order
 * secara langsung. Lihat brief §2.4.
 */
class PaymentService
{
    public function __construct(private PaymentGatewayService $gateway)
    {
    }

    /**
     * Dipanggil TEPAT SETELAH OrderDealService::formDeal(). Membuat row Payment
     * baru (status 'menunggu') dan memindahkan order ke status
     * 'menunggu_pembayaran'. Idempotent: kalau order sudah punya Payment yang
     * masih 'menunggu' & belum lewat deadline, row itu yang dikembalikan lagi
     * (bukan bikin baru) — supaya reload halaman pembayaran tidak numpuk row.
     *
     * Method belum dipilih customer di titik ini (baru dipilih di halaman
     * pembayaran / payWithGatewayChannel() / payWithWallet()), jadi kolom
     * `method` diisi placeholder 'transfer' dulu karena kolomnya NOT NULL
     * tanpa default di skema lama — nilai ini akan ditimpa begitu customer
     * benar-benar memilih metode.
     */
    public function initiate(Order $order): Payment
    {
        $existing = Payment::where('order_id', $order->id)
            ->where('status', 'menunggu')
            ->where(function ($q) {
                $q->whereNull('payment_deadline')->orWhere('payment_deadline', '>', now());
            })
            ->latest('id')
            ->first();

        if ($existing) {
            if ($order->status !== 'menunggu_pembayaran') {
                $order->update(['status' => 'menunggu_pembayaran']);
            }

            return $existing;
        }

        $deadlineMinutes = (int) config('jastip.payment.deadline_minutes', 15);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'transfer',
            'amount' => $order->agreed_fare ?? $order->estimated_fare,
            'status' => 'menunggu',
            'payment_deadline' => now()->addMinutes($deadlineMinutes),
            'gateway_reference' => $this->generateGatewayReference($order),
        ]);

        $order->update(['status' => 'menunggu_pembayaran']);

        return $payment;
    }

    /**
     * Customer memilih bayar via transfer (VA) atau QRIS. Memanggil gateway
     * sesuai channel, lalu simpan hasilnya (va_number/qr_string/gateway_transaction_id
     * /raw_response) ke row Payment yang sama. Belum mengubah status Payment/Order —
     * itu baru terjadi saat webhook settlement masuk (handleWebhook()).
     *
     * @param Payment $payment
     * @param string $channel 'bank_transfer_va' | 'qris'
     * @param string|null $bank Wajib diisi kalau $channel = 'bank_transfer_va'.
     */
    public function payWithGatewayChannel(Payment $payment, string $channel, ?string $bank = null): Payment
    {
        if (!in_array($channel, ['bank_transfer_va', 'qris'], true)) {
            throw new \InvalidArgumentException("Channel pembayaran tidak dikenal: {$channel}");
        }

        if ($channel === 'bank_transfer_va') {
            if (!$bank) {
                throw new \InvalidArgumentException('Bank wajib diisi untuk channel bank_transfer_va.');
            }

            $result = $this->gateway->createVirtualAccount($payment, $bank);

            $payment->update([
                'method' => 'transfer',
                'channel' => 'bank_transfer_va',
                'va_number' => $result['va_number'],
                'qr_string' => null,
                'gateway_transaction_id' => $result['gateway_transaction_id'],
                'raw_response' => $result['raw'],
            ]);
        } else {
            $result = $this->gateway->createQris($payment);

            $payment->update([
                'method' => 'transfer',
                'channel' => 'qris',
                'qr_string' => $result['qr_string'],
                'va_number' => null,
                'gateway_transaction_id' => $result['gateway_transaction_id'],
                'raw_response' => $result['raw'],
            ]);
        }

        return $payment->fresh();
    }

    /**
     * Debit langsung dari saldo wallet customer. Instan — tidak lewat gateway
     * sama sekali (brief §2.3). Kalau saldo cukup: debit wallet, catat
     * WalletTransaction, tandai Payment lunas, dan trigger
     * OrderDealService::startProcessing() supaya order lanjut ke 'diproses'.
     *
     * @throws InsufficientBalanceException kalau saldo wallet tidak cukup.
     */
    public function payWithWallet(Payment $payment, Customer $customer): Payment
    {
        return DB::transaction(function () use ($payment, $customer) {
            $wallet = $customer->wallet()->lockForUpdate()->first();
            $balance = $wallet ? (float) $wallet->balance : 0.0;
            $required = (float) $payment->amount;

            if ($balance < $required) {
                throw InsufficientBalanceException::forWallet($balance, $required);
            }

            $wallet->decrement('balance', $required);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $required,
                'source' => 'pembayaran_order',
                'reference_order_id' => $payment->order_id,
                'description' => "Pembayaran order #{$payment->order_id} via saldo wallet",
            ]);

            $payment->update([
                'method' => 'wallet',
                'channel' => null,
                'status' => 'lunas',
                'verified_at' => now(),
            ]);

            $order = $payment->order()->lockForUpdate()->first();
            app(OrderDealService::class)->startProcessing($order);

            return $payment->fresh();
        });
    }

    /**
     * Handler webhook Midtrans (POST /webhooks/midtrans). Validasi signature,
     * cari Payment terkait, lalu update status. Idempotent terhadap webhook
     * duplikat: kalau Payment sudah tidak berstatus 'menunggu' lagi, payload
     * tetap disimpan untuk audit tapi tidak memicu startProcessing() dua kali.
     */
    public function handleWebhook(array $payload): void
    {
        if (!$this->gateway->verifyWebhookSignature($payload)) {
            Log::warning('[MIDTRANS WEBHOOK] Signature tidak valid, payload ditolak.', ['payload' => $payload]);
            throw new \RuntimeException('Signature webhook tidak valid.');
        }

        $gatewayTransactionId = $payload['transaction_id'] ?? null;
        $orderReference = $payload['order_id'] ?? null;

        $payment = null;
        if ($gatewayTransactionId) {
            $payment = Payment::where('gateway_transaction_id', $gatewayTransactionId)->first();
        }
        if (!$payment && $orderReference) {
            $payment = Payment::where('gateway_reference', $orderReference)->first();
        }

        if (!$payment) {
            Log::warning('[MIDTRANS WEBHOOK] Payment tidak ditemukan untuk payload ini.', ['payload' => $payload]);
            return;
        }

        // Idempotency: webhook duplikat tidak boleh memicu startProcessing() lagi.
        if ($payment->status !== 'menunggu') {
            $payment->update(['raw_webhook_payload' => $payload]);
            return;
        }

        $status = $payload['transaction_status'] ?? null;

        if (in_array($status, ['settlement', 'capture'], true)) {
            DB::transaction(function () use ($payment, $payload) {
                $payment->update([
                    'status' => 'lunas',
                    'verified_at' => now(),
                    'raw_webhook_payload' => $payload,
                ]);

                $order = $payment->order()->lockForUpdate()->first();
                app(OrderDealService::class)->startProcessing($order);
            });
        } elseif (in_array($status, ['expire', 'cancel', 'deny', 'failure'], true)) {
            $payment->update([
                'status' => 'gagal',
                'raw_webhook_payload' => $payload,
            ]);
        } else {
            // pending atau status lain yang belum final — simpan payload saja untuk audit.
            $payment->update(['raw_webhook_payload' => $payload]);
        }
    }

    /**
     * Customer upload bukti transfer manual (fallback kalau VA/QRIS bermasalah).
     * Tidak langsung mengubah status Payment — menunggu admin approve.
     */
    public function submitManualProof(Payment $payment, UploadedFile $proof): Payment
    {
        $path = $proof->store('payment-proofs', 'public');

        $payment->update(['proof_image' => $path]);

        return $payment->fresh();
    }

    /**
     * Admin approve/reject bukti transfer manual. SEBELUM approve, cross-check
     * dulu status transaksi ke Midtrans (brief §0 keputusan #3) — supaya admin
     * tidak asal klik "lunas" untuk bukti transfer palsu. Cross-check hanya
     * dijalankan kalau Payment punya gateway_transaction_id (kemungkinan customer
     * pernah mencoba jalur VA/QRIS sebelum upload bukti manual); kalau tidak ada,
     * verifikasi murni mengandalkan penilaian admin atas bukti_image.
     */
    public function adminVerifyManualProof(Payment $payment, Admin $admin, bool $approve): Payment
    {
        if ($approve && $payment->gateway_transaction_id) {
            $gatewayStatus = $this->gateway->getStatus($payment->gateway_transaction_id);

            Log::info('[ADMIN VERIFY] Cross-check status Midtrans sebelum approve.', [
                'payment_id' => $payment->id,
                'gateway_status' => $gatewayStatus,
            ]);
        }

        if ($approve) {
            return DB::transaction(function () use ($payment, $admin) {
                $payment->update([
                    'status' => 'lunas',
                    'verified_at' => now(),
                    'verified_by_admin_id' => $admin->id,
                ]);

                $order = $payment->order()->lockForUpdate()->first();
                app(OrderDealService::class)->startProcessing($order);

                return $payment->fresh();
            });
        }

        $payment->update([
            'status' => 'gagal',
            'verified_at' => now(),
            'verified_by_admin_id' => $admin->id,
        ]);

        return $payment->fresh();
    }

    /**
     * Dipanggil scheduler tiap menit (Tahap 4). Cari semua Payment 'menunggu'
     * yang sudah lewat payment_deadline, tandai 'kedaluwarsa', batalkan order
     * terkait, dan lepas jastiper_id kembali ke null supaya jastiper itu
     * available lagi untuk order lain.
     *
     * @return int Jumlah order yang di-cancel.
     */
    public function expireOverdue(): int
    {
        $overduePayments = Payment::where('status', 'menunggu')
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', now())
            ->get();

        $count = 0;

        foreach ($overduePayments as $payment) {
            DB::transaction(function () use ($payment, &$count) {
                $order = $payment->order()->lockForUpdate()->first();

                if (!$order || !in_array($order->status, ['menunggu_pembayaran'], true)) {
                    // Order sudah berubah status di luar dugaan (race condition) — skip,
                    // biar tidak salah cancel order yang sebenarnya sudah lunas/diproses.
                    return;
                }

                $payment->update(['status' => 'kedaluwarsa']);

                $order->update([
                    'status' => 'dibatalkan',
                    'jastiper_id' => null,
                    'cancelled_by_role' => 'system',
                    'cancellation_reason' => 'Pembayaran tidak diselesaikan sebelum batas waktu.',
                ]);

                $count++;
            });
        }

        return $count;
    }

    private function generateGatewayReference(Order $order): string
    {
        return 'ORDER-' . $order->id . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }
}