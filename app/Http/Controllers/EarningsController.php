<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Komisi;
use App\Models\WithdrawRequest;
use App\Services\WithdrawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Brief Sprint 8 Bagian 3 §2.6. Halaman jastiper — mengikuti tech split
 * project ini: Blade shell tipis + Alpine.js + fetch() ke endpoint JSON,
 * BUKAN Livewire (lihat catatan tech split di brief).
 */
class EarningsController extends Controller
{
    public function __construct(private WithdrawService $withdrawService)
    {
    }

    /**
     * GET /jastiper/earnings — render shell HTML saja, data diambil via fetch JS.
     */
    public function recap(Request $request)
    {
        $jastiper = Auth::guard('jastiper')->user();

        return view('jastiper.earnings', compact('jastiper'));
    }

    /**
     * GET /jastiper/earnings/data — breakdown pendapatan (harian/mingguan)
     * dari tabel komisi, plus saldo wallet & status pengajuan withdraw
     * terbaru milik jastiper yang login.
     */
    public function recapData(Request $request)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $period = $request->query('period', 'harian');

        $baseQuery = DB::table('komisi')
            ->join('orders', 'komisi.order_id', '=', 'orders.id')
            ->where('orders.jastiper_id', $jastiper->id)
            ->whereNull('komisi.deleted_at');

        if ($period === 'mingguan') {
            // 8 minggu terakhir, dikelompokkan per tahun+nomor minggu ISO.
            $rows = (clone $baseQuery)
                ->selectRaw("YEARWEEK(komisi.created_at, 3) as period_key, MIN(DATE(komisi.created_at)) as period_start, SUM(komisi.gross_amount) as gross_amount, SUM(komisi.commission_amount) as commission_amount, SUM(komisi.net_amount) as net_amount, COUNT(*) as orders_count")
                ->groupBy('period_key')
                ->orderByDesc('period_key')
                ->limit(8)
                ->get();

            $breakdown = $rows->map(function ($row) {
                return [
                    'label' => 'Minggu ' . \Carbon\Carbon::parse($row->period_start)->format('d/m'),
                    'gross_amount' => (float) $row->gross_amount,
                    'commission_amount' => (float) $row->commission_amount,
                    'net_amount' => (float) $row->net_amount,
                    'orders_count' => (int) $row->orders_count,
                ];
            })->values();
        } else {
            // Default 'harian' — 14 hari terakhir.
            $rows = (clone $baseQuery)
                ->selectRaw('DATE(komisi.created_at) as period_key, SUM(komisi.gross_amount) as gross_amount, SUM(komisi.commission_amount) as commission_amount, SUM(komisi.net_amount) as net_amount, COUNT(*) as orders_count')
                ->groupBy('period_key')
                ->orderByDesc('period_key')
                ->limit(14)
                ->get();

            $breakdown = $rows->map(function ($row) {
                return [
                    'label' => \Carbon\Carbon::parse($row->period_key)->format('d/m/Y'),
                    'gross_amount' => (float) $row->gross_amount,
                    'commission_amount' => (float) $row->commission_amount,
                    'net_amount' => (float) $row->net_amount,
                    'orders_count' => (int) $row->orders_count,
                ];
            })->values();
        }

        $wallet = $jastiper->wallet;
        $saldo = $wallet ? (float) $wallet->balance : 0.0;

        $pendingWithdraw = WithdrawRequest::where('jastiper_id', $jastiper->id)
            ->where('status', 'menunggu')
            ->latest()
            ->first();

        $recentWithdraws = WithdrawRequest::where('jastiper_id', $jastiper->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'amount', 'status', 'admin_note', 'created_at', 'processed_at']);

        return response()->json([
            'success' => true,
            'period' => $period,
            'saldo' => $saldo,
            'breakdown' => $breakdown,
            'total_net' => $breakdown->sum('net_amount'),
            'pending_withdraw' => $pendingWithdraw,
            'recent_withdraws' => $recentWithdraws,
        ]);
    }

    /**
     * POST /jastiper/earnings/withdraw — ajukan withdraw baru.
     */
    public function requestWithdraw(Request $request)
    {
        $jastiper = Auth::guard('jastiper')->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:150',
        ]);

        try {
            $withdrawRequest = $this->withdrawService->requestWithdraw(
                $jastiper,
                (float) $validated['amount'],
                $validated['bank_name'],
                $validated['bank_account_number'],
                $validated['bank_account_holder']
            );
        } catch (InsufficientBalanceException|\RuntimeException|\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : redirect()->back()->with('error', $msg);
        }

        $msg = 'Pengajuan withdraw berhasil dikirim, tunggu persetujuan admin.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'withdraw_request' => $withdrawRequest])
            : redirect()->back()->with('success', $msg);
    }
}