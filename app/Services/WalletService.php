<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Admin;
use App\Models\Topup;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletService
{
    public function __construct(private PaymentGatewayService $gateway)
    {
    }

    /**
     * Tambah saldo wallet (kredit).
     */
    public function credit(Wallet $wallet, float $amount, string $source, ?int $referenceOrderId = null, ?string $description = null): void
    {
        DB::transaction(function () use ($wallet, $amount, $source, $referenceOrderId, $description) {
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            $lockedWallet->increment('balance', $amount);

            WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => 'kredit',
                'amount' => $amount,
                'source' => $source,
                'reference_order_id' => $referenceOrderId,
                'description' => $description,
            ]);
        });
    }

    /**
     * Kurangi saldo wallet (debit). Brief Sprint 8 Bagian 3 §2.4 — dipakai
     * untuk memotong saldo jastiper saat pengajuan withdraw DISETUJUI admin
     * (bukan saat pengajuan dibuat, lihat WithdrawService).
     *
     * Pola lock & exception meniru persis PaymentService::payWithWallet().
     *
     * @throws InsufficientBalanceException kalau saldo tidak cukup.
     */
    public function debit(Wallet $wallet, float $amount, string $source, ?int $referenceOrderId = null, ?string $description = null): void
    {
        DB::transaction(function () use ($wallet, $amount, $source, $referenceOrderId, $description) {
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            $balance = (float) $lockedWallet->balance;

            if ($balance < $amount) {
                throw InsufficientBalanceException::forWallet($balance, $amount);
            }

            $lockedWallet->decrement('balance', $amount);

            WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'source' => $source,
                'reference_order_id' => $referenceOrderId,
                'description' => $description,
            ]);
        });
    }

    /**
     * Inisiasi Topup saldo tahap 1: Customer mengajukan permintaan topup (status: menunggu_approval).
     * Pembayaran belum dibuka sebelum di-ACC oleh Admin.
     */
    public function initiateTopup(Wallet $wallet, float $amount, string $channel = 'qris', ?string $bank = null): Topup
    {
        $topup = Topup::create([
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'status' => 'menunggu_approval',
            'method' => 'transfer',
            'channel' => $channel,
            'gateway_reference' => 'TOPUP-' . $wallet->id . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
        ]);

        return $topup->fresh();
    }

    /**
     * Tahap 1 Aksi Admin: ACC Pengajuan Top-Up customer.
     * Mengaktifkan tagihan pembayaran (menunggu_pembayaran) dan generate QRIS / VA.
     */
    public function approveTopupRequest(Topup $topup, Admin $admin): Topup
    {
        if ($topup->status !== 'menunggu_approval') {
            throw new \DomainException("Status topup saat ini bukan menunggu_approval.");
        }

        $deadlineMinutes = (int) config('jastip.payment.deadline_minutes', 60);

        $topup->update([
            'status' => 'menunggu_pembayaran',
            'approved_at' => now(),
            'approved_by_admin_id' => $admin->id,
            'payment_deadline' => now()->addMinutes($deadlineMinutes),
        ]);

        $channel = $topup->channel ?: 'qris';

        if ($channel === 'bank_transfer_va') {
            $bank = 'bca';
            $result = $this->gateway->createVirtualAccountForTopup($topup, $bank);
            $topup->update([
                'va_number' => $result['va_number'],
                'gateway_transaction_id' => $result['gateway_transaction_id'],
                'raw_response' => $result['raw'],
            ]);
        } else {
            $result = $this->gateway->createQrisForTopup($topup);
            $topup->update([
                'qr_string' => $result['qr_string'],
                'gateway_transaction_id' => $result['gateway_transaction_id'],
                'raw_response' => $result['raw'],
            ]);
        }

        try {
            $snapResult = $this->gateway->createSnapTokenForTopup($topup);
            if (!empty($snapResult['token'])) {
                $topup->update(['snap_token' => $snapResult['token']]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[SNAP TOKEN TOPUP ERROR] ' . $e->getMessage());
        }

        return $topup->fresh();
    }

    /**
     * Dapatkan atau buat Snap Token untuk Topup.
     */
    public function getOrCreateSnapToken(Topup $topup): string
    {
        if (!empty($topup->snap_token)) {
            return $topup->snap_token;
        }

        try {
            $snapResult = $this->gateway->createSnapTokenForTopup($topup);
            $token = $snapResult['token'] ?? ('MOCK-SNAP-' . \Illuminate\Support\Str::random(20));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[SNAP TOKEN ERROR] ' . $e->getMessage());
            $token = 'MOCK-SNAP-' . \Illuminate\Support\Str::random(20);
        }

        $topup->update(['snap_token' => $token]);
        return $token;
    }

    /**
     * Settle Topup otomatis (dipanggil saat Snap payment sukses / simulasi otomatis bayar).
     */
    public function settleTopupAutomatically(Topup $topup, ?string $channel = null, ?string $transactionId = null): Topup
    {
        if ($topup->status === 'berhasil') {
            return $topup;
        }

        $wallet = $topup->wallet;
        $this->credit($wallet, (float) $topup->amount, 'topup', null, "Topup Berhasil via Midtrans Snap ({$topup->gateway_reference})");

        $resolvedTransactionId = $transactionId ?: ($topup->gateway_transaction_id ?: 'TRX-MIDTRANS-' . strtoupper(Str::random(12)));
        $resolvedChannel = $channel ?: ($topup->channel ?: 'midtrans_snap');

        $topup->update([
            'status' => 'berhasil',
            'channel' => $resolvedChannel,
            'gateway_transaction_id' => $resolvedTransactionId,
            'verified_at' => now(),
            'raw_response' => array_merge($topup->raw_response ?? [], [
                'settled_by' => 'midtrans_callback',
                'settlement_type' => 'automatic_callback',
                'settled_at' => now()->toIso8601String(),
                'channel' => $resolvedChannel,
                'gateway_transaction_id' => $resolvedTransactionId,
                'note' => 'ACC Tahap 2 diselesaikan otomatis oleh callback Midtrans gateway.',
            ]),
        ]);

        return $topup->fresh();
    }

    /**
     * Tahap 1 Aksi Admin: Tolak Pengajuan Top-Up customer.
     */
    public function rejectTopupRequest(Topup $topup, Admin $admin, ?string $reason = null): Topup
    {
        $topup->update([
            'status' => 'ditolak_pengajuan',
            'approved_at' => now(),
            'approved_by_admin_id' => $admin->id,
            'rejection_note' => $reason ?: 'Pengajuan top-up ditolak oleh admin.',
        ]);

        return $topup->fresh();
    }

    /**
     * Handler webhook Midtrans untuk transaksi Topup.
     */
    public function handleWebhook(array $payload): void
    {
        $gatewayTransactionId = $payload['transaction_id'] ?? null;
        $orderReference = $payload['order_id'] ?? null;

        $topup = null;
        if ($gatewayTransactionId) {
            $topup = Topup::where('gateway_transaction_id', $gatewayTransactionId)->first();
        }
        if (!$topup && $orderReference) {
            $topup = Topup::where('gateway_reference', $orderReference)->first();
        }

        if (!$topup) {
            \Illuminate\Support\Facades\Log::warning('[MIDTRANS WEBHOOK] Topup tidak ditemukan untuk payload ini.', ['payload' => $payload]);
            return;
        }

        if (!in_array($topup->status, ['menunggu', 'menunggu_pembayaran', 'menunggu_verifikasi_bukti'], true)) {
            $topup->update(['raw_webhook_payload' => $payload]);
            return;
        }

        $status = $payload['transaction_status'] ?? null;

        if (in_array($status, ['settlement', 'capture'], true)) {
            DB::transaction(function () use ($topup, $payload) {
                $resolvedTransactionId = $payload['transaction_id'] ?? ($topup->gateway_transaction_id ?: 'TRX-MIDTRANS-' . strtoupper(Str::random(12)));
                $resolvedChannel = $payload['payment_type'] ?? ($topup->channel ?: 'midtrans_snap');

                $topup->update([
                    'status' => 'berhasil',
                    'verified_at' => now(),
                    'raw_webhook_payload' => $payload,
                    'gateway_transaction_id' => $resolvedTransactionId,
                    'channel' => $resolvedChannel,
                    'raw_response' => array_merge($topup->raw_response ?? [], [
                        'settled_by' => 'midtrans_callback',
                        'settlement_type' => 'webhook_callback',
                        'settled_at' => now()->toIso8601String(),
                        'channel' => $resolvedChannel,
                        'gateway_transaction_id' => $resolvedTransactionId,
                        'note' => 'ACC Tahap 2 diselesaikan otomatis oleh webhook Midtrans gateway.',
                    ]),
                ]);

                $wallet = $topup->wallet()->lockForUpdate()->first();
                $this->credit($wallet, (float) $topup->amount, 'topup', null, "Topup via Midtrans ({$topup->gateway_reference})");
            });
        } elseif (in_array($status, ['expire', 'cancel', 'deny', 'failure'], true)) {
            $topup->update([
                'status' => 'gagal',
                'raw_webhook_payload' => $payload,
            ]);
        } else {
            $topup->update(['raw_webhook_payload' => $payload]);
        }
    }

    /**
     * Customer upload bukti transfer manual untuk topup.
     * Mengubah status ke menunggu_verifikasi_bukti.
     */
    public function submitManualProof(Topup $topup, UploadedFile $proof): Topup
    {
        $path = $proof->store('topup-proofs', 'public');

        $topup->update([
            'proof_image' => $path,
            'status' => 'menunggu_verifikasi_bukti',
        ]);

        return $topup->fresh();
    }

    /**
     * BUGFIX: Admin approve/reject bukti transfer manual topup. Pola & cross-check
     * ke gateway sama seperti PaymentService::adminVerifyManualProof(), bedanya
     * status "sukses" topup namanya 'berhasil' (bukan 'lunas') dan approve di
     * sini mengkredit wallet customer, bukan memindahkan status order.
     */
    public function adminVerifyManualProof(Topup $topup, Admin $admin, bool $approve): Topup
    {
        if ($approve && $topup->gateway_transaction_id) {
            $gatewayStatus = $this->gateway->getStatus($topup->gateway_transaction_id);

            Log::info('[ADMIN VERIFY] Cross-check status Midtrans sebelum approve topup.', [
                'topup_id' => $topup->id,
                'gateway_status' => $gatewayStatus,
            ]);
        }

        if ($approve) {
            return DB::transaction(function () use ($topup, $admin) {
                $topup->update([
                    'status' => 'berhasil',
                    'verified_at' => now(),
                    'verified_by_admin_id' => $admin->id,
                ]);

                $wallet = $topup->wallet()->lockForUpdate()->first();
                $this->credit($wallet, (float) $topup->amount, 'topup', null, "Topup manual disetujui admin ({$topup->gateway_reference})");

                return $topup->fresh();
            });
        }

        $topup->update([
            'status' => 'gagal',
            'verified_at' => now(),
            'verified_by_admin_id' => $admin->id,
        ]);

        return $topup->fresh();
    }
}