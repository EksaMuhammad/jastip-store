<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Topup;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
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
     * Inisiasi Topup saldo via gateway (Midtrans).
     */
    public function initiateTopup(Wallet $wallet, float $amount, string $channel, ?string $bank = null): Topup
    {
        if (!in_array($channel, ['bank_transfer_va', 'qris'], true)) {
            throw new \InvalidArgumentException("Channel topup tidak dikenal: {$channel}");
        }

        $deadlineMinutes = (int) config('jastip.payment.deadline_minutes', 15);

        $topup = Topup::create([
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'status' => 'menunggu',
            'method' => 'transfer',
            'channel' => $channel,
            'payment_deadline' => now()->addMinutes($deadlineMinutes),
            'gateway_reference' => 'TOPUP-' . $wallet->id . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
        ]);

        if ($channel === 'bank_transfer_va') {
            if (!$bank) {
                throw new \InvalidArgumentException('Bank wajib diisi untuk channel bank_transfer_va.');
            }

            // Memanggil gateway, catatan: gateway saat ini butuh Payment, kita harus ubah interface
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

        if ($topup->status !== 'menunggu') {
            $topup->update(['raw_webhook_payload' => $payload]);
            return;
        }

        $status = $payload['transaction_status'] ?? null;

        if (in_array($status, ['settlement', 'capture'], true)) {
            DB::transaction(function () use ($topup, $payload) {
                $topup->update([
                    'status' => 'berhasil',
                    'verified_at' => now(),
                    'raw_webhook_payload' => $payload,
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
}