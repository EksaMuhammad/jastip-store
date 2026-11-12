<?php

namespace App\Services;

use App\Models\InsuranceTransaction;
use App\Models\Order;
use App\Models\Report;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InsuranceService
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Catat pemasukan premi asuransi saat pesanan dibuat & dibayar.
     */
    public function recordPremium(Order $order, float $fee): InsuranceTransaction
    {
        return DB::transaction(function () use ($order, $fee) {
            $order->update([
                'has_insurance' => true,
                'insurance_fee' => $fee,
            ]);

            return InsuranceTransaction::create([
                'type' => 'premi_masuk',
                'amount' => $fee,
                'order_id' => $order->id,
                'description' => "Penerimaan premi proteksi asuransi pesanan #{$order->id}",
            ]);
        });
    }

    /**
     * Eksekusi klaim ganti rugi asuransi (Platform menalangi customer & membebankan ke jastiper).
     */
    public function claimInsurance(Report $report, float $payoutAmount, ?float $jastiperCharge = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($report, $payoutAmount, $jastiperCharge, $notes) {
            $order = $report->order;
            $charge = $jastiperCharge ?? $payoutAmount;

            // 1. Catat pengeluaran dana asuransi platform (Talangan Platform)
            InsuranceTransaction::create([
                'type' => 'klaim_keluar',
                'amount' => $payoutAmount,
                'order_id' => $order->id,
                'report_id' => $report->id,
                'description' => "Pencairan talangan klaim asuransi komplain #REP-{$report->id} (Order #{$order->id}): " . ($notes ?? 'Kerusakan/kehilangan barang oleh jastiper'),
            ]);

            // 2. Ganti dana customer 100% langsung ke dompet customer
            $customerWallet = $order->customer?->wallet;
            if ($customerWallet && $payoutAmount > 0) {
                $this->walletService->credit(
                    $customerWallet,
                    $payoutAmount,
                    'asuransi_klaim',
                    $order->id,
                    "Pencairan ganti rugi 100% Proteksi Asuransi #REP-{$report->id} (Pesanan #{$order->id})"
                );
            }

            // 3. Bebankan pertanggungjawaban ganti rugi ke dompet Jastiper (saldo boleh minus)
            $jastiperWallet = $order->jastiper?->wallet;
            if ($jastiperWallet && $charge > 0) {
                $this->walletService->forceDebit(
                    $jastiperWallet,
                    $charge,
                    'beban_ganti_rugi',
                    $order->id,
                    "Beban ganti rugi kerusakan pesanan #{$order->id} (Ditalangi Kas Asuransi Platform)",
                    true // Izinkan minus
                );
            }

            // 4. Update data order & report
            $order->update([
                'insurance_claimed' => true,
                'insurance_claim_amount' => $payoutAmount,
                'jastiper_debt_amount' => $charge,
            ]);

            $report->update([
                'is_insurance_claim' => true,
                'insurance_payout' => $payoutAmount,
                'jastiper_charged_amount' => $charge,
            ]);
        });
    }

    /**
     * Hitung ringkasan posisi kas Dana Asuransi & statistik talangan platform.
     */
    public function getPoolSummary(?Carbon $start = null, ?Carbon $end = null): array
    {
        $query = InsuranceTransaction::query();
        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }

        $allTimeQuery = InsuranceTransaction::query();

        // 1. Pemasukan Premi Masuk
        $totalPremiPeriode = (float) (clone $query)->where('type', 'premi_masuk')->sum('amount');
        $totalPremiAllTime = (float) (clone $allTimeQuery)->where('type', 'premi_masuk')->sum('amount');

        // 2. Pengeluaran Klaim Talangan
        $totalKlaimPeriode = (float) (clone $query)->where('type', 'klaim_keluar')->sum('amount');
        $totalKlaimAllTime = (float) (clone $allTimeQuery)->where('type', 'klaim_keluar')->sum('amount');

        // 3. Saldo Kas Bersih Pool Asuransi (Bisa MINUS jika platform nomboki lebih besar daripada premi masuk)
        $netPoolPeriode = $totalPremiPeriode - $totalKlaimPeriode;
        $netPoolAllTime = $totalPremiAllTime - $totalKlaimAllTime;

        // 4. Total Piutang / Saldo Minus Jastiper yang Ditalangi Platform
        $negativeJastiperWallets = Wallet::where('owner_role', 'jastiper')
            ->where('balance', '<', 0)
            ->get();
        $totalJastiperDebt = abs((float) $negativeJastiperWallets->sum('balance'));

        // 5. Metrik Pesanan Berasuransi
        $insuredOrdersCount = Order::where('has_insurance', true)->count();
        $claimedOrdersCount = Order::where('insurance_claimed', true)->count();

        // 6. Transaksi Terbaru
        $recentTransactions = (clone $query)->latest()->limit(10)->get();

        return [
            'total_premi_periode' => $totalPremiPeriode,
            'total_premi_all_time' => $totalPremiAllTime,
            'total_klaim_periode' => $totalKlaimPeriode,
            'total_klaim_all_time' => $totalKlaimAllTime,
            'net_pool_periode' => $netPoolPeriode,
            'net_pool_all_time' => $netPoolAllTime,
            'is_deficit' => $netPoolAllTime < 0,
            'total_jastiper_debt' => $totalJastiperDebt,
            'debtor_count' => $negativeJastiperWallets->count(),
            'insured_orders_count' => $insuredOrdersCount,
            'claimed_orders_count' => $claimedOrdersCount,
            'recent_transactions' => $recentTransactions,
        ];
    }
}

