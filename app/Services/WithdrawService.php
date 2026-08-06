<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Admin;
use App\Models\Jastiper;
use App\Models\WithdrawRequest;
use Illuminate\Support\Facades\DB;

/**
 * Brief Sprint 8 Bagian 3 §2.5.
 *
 * Keputusan desain (lihat §3 brief — belum dikonfirmasi PM, asumsi dipakai
 * dulu sampai ada keputusan lain):
 * - Tidak ada minimum nominal withdraw.
 * - Tidak ada fee tambahan saat withdraw (komisi 10% sudah dipotong di awal).
 * - 1 jastiper tidak boleh punya lebih dari 1 pengajuan berstatus 'menunggu'
 *   sekaligus.
 */
class WithdrawService
{
    public function __construct(private WalletService $walletService)
    {
    }

    /**
     * Ajukan withdraw baru. Saldo TIDAK dipotong di titik ini — hanya
     * divalidasi cukup atau tidak. Baru benar-benar dipotong saat approve().
     */
    public function requestWithdraw(Jastiper $jastiper, float $amount, string $bankName, string $accountNumber, string $accountHolder): WithdrawRequest
    {
        return DB::transaction(function () use ($jastiper, $amount, $bankName, $accountNumber, $accountHolder) {
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Jumlah withdraw harus lebih besar dari 0.');
            }

            $wallet = $jastiper->wallet()->lockForUpdate()->first();
            $balance = $wallet ? (float) $wallet->balance : 0.0;

            if ($amount > $balance) {
                throw InsufficientBalanceException::forWallet($balance, $amount);
            }

            $sudahAdaMenunggu = WithdrawRequest::where('jastiper_id', $jastiper->id)
                ->where('status', 'menunggu')
                ->exists();

            if ($sudahAdaMenunggu) {
                throw new \RuntimeException('Anda masih punya pengajuan withdraw yang sedang menunggu diproses. Tunggu sampai selesai diproses sebelum mengajukan lagi.');
            }

            return WithdrawRequest::create([
                'jastiper_id' => $jastiper->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'bank_name' => $bankName,
                'bank_account_number' => $accountNumber,
                'bank_account_holder' => $accountHolder,
                'status' => 'menunggu',
            ]);
        });
    }

    /**
     * Admin menyetujui pengajuan — di titik inilah saldo wallet benar-benar
     * dipotong. Kalau ternyata saldo sudah tidak cukup (misal terpakai di
     * tempat lain sejak pengajuan dibuat), InsufficientBalanceException akan
     * otomatis dilempar oleh WalletService::debit().
     */
    public function approve(WithdrawRequest $request, Admin $admin): WithdrawRequest
    {
        return DB::transaction(function () use ($request, $admin) {
            $request = WithdrawRequest::where('id', $request->id)->lockForUpdate()->first();

            if ($request->status !== 'menunggu') {
                throw new \RuntimeException('Pengajuan ini sudah diproses sebelumnya.');
            }

            $this->walletService->debit(
                $request->wallet,
                (float) $request->amount,
                'withdraw',
                null,
                "Withdraw disetujui — permintaan #{$request->id}"
            );

            $request->update([
                'status' => 'disetujui',
                'processed_by_admin_id' => $admin->id,
                'processed_at' => now(),
            ]);

            $jastiper = $request->jastiper;
            if ($jastiper) {
                \App\Services\WhatsAppService::sendMessage(
                    $jastiper->phone_number,
                    "Pengajuan withdraw Rp" . number_format((float) $request->amount, 0, ',', '.') . " telah disetujui dan sedang diproses."
                );
            }

            return $request->fresh();
        });
    }

    /**
     * Admin menolak pengajuan — TIDAK ada debit karena saldo belum pernah
     * dipotong di titik pengajuan.
     */
    public function reject(WithdrawRequest $request, Admin $admin, string $note): WithdrawRequest
    {
        return DB::transaction(function () use ($request, $admin, $note) {
            $request = WithdrawRequest::where('id', $request->id)->lockForUpdate()->first();

            if ($request->status !== 'menunggu') {
                throw new \RuntimeException('Pengajuan ini sudah diproses sebelumnya.');
            }

            $request->update([
                'status' => 'ditolak',
                'admin_note' => $note,
                'processed_by_admin_id' => $admin->id,
                'processed_at' => now(),
            ]);

            $jastiper = $request->jastiper;
            if ($jastiper) {
                \App\Services\WhatsAppService::sendMessage(
                    $jastiper->phone_number,
                    "Pengajuan withdraw Rp" . number_format((float) $request->amount, 0, ',', '.') . " ditolak. Alasan: {$note}"
                );
            }

            return $request->fresh();
        });
    }
}