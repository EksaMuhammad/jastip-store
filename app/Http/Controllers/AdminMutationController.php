<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminMutationController extends Controller
{
    /**
     * Master Data Mutasi Keseluruhan (Ledger Saldo & Arus Kas Platform).
     */
    public function index(Request $request)
    {
        $data = $this->buildMutationQuery($request);

        return view('admin.mutations.index', $data);
    }

    /**
     * Helper untuk memproses query mutasi dan menghitung KPI.
     */
    public function buildMutationQuery(Request $request, string $pageName = 'page'): array
    {
        // 1. Filter Hari / Waktu Mutasi
        $days = $request->query('days', '7'); // default 7 hari terakhir
        $startDate = null;
        $endDate = now()->endOfDay();
        $daysLabel = '7 Hari Terakhir';

        if ($days === '1' || $days === 'today') {
            $startDate = now()->startOfDay();
            $daysLabel = 'Hari Ini (1 Hari)';
            $days = '1';
        } elseif ($days === '3') {
            $startDate = now()->subDays(3)->startOfDay();
            $daysLabel = '3 Hari Terakhir';
        } elseif ($days === '7') {
            $startDate = now()->subDays(7)->startOfDay();
            $daysLabel = '7 Hari Terakhir';
        } elseif ($days === '14') {
            $startDate = now()->subDays(14)->startOfDay();
            $daysLabel = '14 Hari Terakhir';
        } elseif ($days === '30') {
            $startDate = now()->subDays(30)->startOfDay();
            $daysLabel = '30 Hari Terakhir';
        } elseif ($days === '60') {
            $startDate = now()->subDays(60)->startOfDay();
            $daysLabel = '60 Hari Terakhir';
        } elseif ($days === '90') {
            $startDate = now()->subDays(90)->startOfDay();
            $daysLabel = '90 Hari Terakhir';
        } elseif ($days === 'all') {
            $startDate = null;
            $daysLabel = 'Semua Riwayat (All-Time)';
        } elseif (is_numeric($days) && (int)$days > 0) {
            $intDays = (int)$days;
            $startDate = now()->subDays($intDays)->startOfDay();
            $daysLabel = "{$intDays} Hari Terakhir";
            $days = (string)$intDays;
        } else {
            $days = '7';
            $startDate = now()->subDays(7)->startOfDay();
            $daysLabel = '7 Hari Terakhir';
        }

        // Custom direct date range support if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            try {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $daysLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
                $days = 'custom';
            } catch (\Throwable $e) {
                // Ignore parse errors, fallback to $days
            }
        }

        // 2. Base Query
        $baseQuery = WalletTransaction::with(['wallet.owner', 'order']);

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // 3. Filter Arus / Type (kredit / debit)
        $type = $request->query('type', 'all');
        if (in_array($type, ['kredit', 'debit'], true)) {
            $baseQuery->where('type', $type);
        }

        // 4. Filter Role Pemilik Akun (customer / jastiper)
        $role = $request->query('role', 'all');
        if (in_array($role, ['customer', 'jastiper', 'admin'], true)) {
            $baseQuery->whereHas('wallet', function ($q) use ($role) {
                $q->where('owner_role', $role);
            });
        }

        // 5. Filter Sumber / Source
        $source = $request->query('source', 'all');
        if (!empty($source) && $source !== 'all') {
            $baseQuery->where('source', $source);
        }

        // 6. Pencarian Teks
        $search = trim($request->query('search', ''));
        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('reference_order_id', 'like', "%{$search}%")
                  ->orWhereHas('wallet', function ($wq) use ($search) {
                      $wq->whereHasMorph('owner', ['customer' => \App\Models\Customer::class, 'jastiper' => \App\Models\Jastiper::class], function ($oq) use ($search) {
                          $oq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                      });
                  });
            });
        }

        // 7. Hitung KPI Summary Periode
        $kpiQuery = clone $baseQuery;
        $totalKredit = (float) (clone $kpiQuery)->where('type', 'kredit')->sum('amount');
        $totalDebit = (float) (clone $kpiQuery)->where('type', 'debit')->sum('amount');
        $netMutasi = $totalKredit - $totalDebit;
        $totalCount = (clone $kpiQuery)->count();

        // 8. Paginasi Hasil
        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $mutations = (clone $baseQuery)->latest('created_at')->paginate($perPage, ['*'], $pageName)->withQueryString();

        // Daftar distinct sources untuk filter dropdown
        $availableSources = [
            'topup' => 'Top-Up Saldo',
            'order_completion' => 'Pendapatan Selesai Jastip',
            'item_reimbursement' => 'Pencairan Talangan Belanja (DP Barang)',
            'pembayaran_order' => 'Pembayaran Order (Wallet)',
            'withdraw' => 'Penarikan Dana (Withdraw)',
            'refund' => 'Pengembalian Dana (Refund)',
            'komisi' => 'Potongan Komisi Platform',
        ];

        return [
            'mutations' => $mutations,
            'days' => $days,
            'daysLabel' => $daysLabel,
            'type' => $type,
            'role' => $role,
            'source' => $source,
            'search' => $search,
            'perPage' => $perPage,
            'totalKredit' => $totalKredit,
            'totalDebit' => $totalDebit,
            'netMutasi' => $netMutasi,
            'totalCount' => $totalCount,
            'availableSources' => $availableSources,
            'startDate' => $startDate ? $startDate->format('Y-m-d') : null,
            'endDate' => $endDate ? $endDate->format('Y-m-d') : null,
        ];
    }
}
