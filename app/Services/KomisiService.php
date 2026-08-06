<?php

namespace App\Services;

use App\Models\Komisi;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Titik pusat perhitungan & pencatatan komisi platform (Brief Sprint 8
 * Bagian 3 §2.2). Dipanggil oleh OrderCompletionService setiap order
 * berpindah ke status 'selesai'.
 *
 * Persentase komisi diambil dari config('jastip.komisi.percentage') —
 * JANGAN di-hardcode ulang di sini.
 */
class KomisiService
{
    public function __construct(private WalletService $walletService)
    {
    }

    /**
     * Potong komisi dari agreed_fare order, simpan row Komisi, lalu kredit
     * net_amount ke wallet jastiper yang mengerjakan order tsb.
     *
     * Idempotent: kalau order ini SUDAH punya Komisi (misal completion
     * ter-trigger dua kali dari jalur berbeda), row lama langsung
     * dikembalikan tanpa memotong komisi/kredit wallet lagi kedua kalinya.
     */
    public function potongKomisi(Order $order): Komisi
    {
        return DB::transaction(function () use ($order) {
            // Guard idempotency — kunci row order dulu supaya dua request yang
            // hampir bersamaan (jalur jastiper vs jalur customer) tidak lolos
            // pengecekan existing() ini berbarengan.
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            $existing = Komisi::where('order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }

            $grossAmount = (float) $order->agreed_fare;
            $percentage = (float) config('jastip.komisi.percentage');
            $commissionAmount = round($grossAmount * $percentage / 100, 2);
            $netAmount = round($grossAmount - $commissionAmount, 2);

            $komisi = Komisi::create([
                'order_id' => $order->id,
                'gross_amount' => $grossAmount,
                'commission_percentage' => $percentage,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
            ]);

            $jastiper = $order->jastiper;
            if ($jastiper) {
                // Catatan: provisioning wallet baru untuk jastiper belum ditangani
                // di titik manapun di codebase ini (customer juga sama). Supaya
                // proses komisi tidak gagal gara-gara wallet belum ada, kita
                // firstOrCreate di sini — aman & idempotent (morphOne unique
                // constraint di migration wallets menjamin tidak dobel).
                $wallet = $jastiper->wallet()->firstOrCreate([], ['balance' => 0]);

                $this->walletService->credit(
                    $wallet,
                    $netAmount,
                    'order_completion',
                    $order->id,
                    "Pendapatan bersih order #{$order->id}"
                );
            }

            return $komisi;
        });
    }
}