<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Admin;
use App\Models\WithdrawRequest;
use App\Services\WithdrawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Brief Sprint 8 Bagian 3 §2.7. Mengikuti pola PaymentController::adminPage()
 * / adminVerify() yang sudah ada untuk verifikasi pembayaran.
 */
class AdminWithdrawController extends Controller
{
    public function __construct(private WithdrawService $withdrawService)
    {
    }

    /**
     * GET /admin/withdraws — daftar pengajuan, default filter status 'menunggu'.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'menunggu');

        $query = WithdrawRequest::with(['jastiper', 'processedByAdmin'])->latest();

        if (in_array($status, ['menunggu', 'disetujui', 'ditolak'], true)) {
            $query->where('status', $status);
        }

        $withdraws = $query->paginate(15)->withQueryString();

        return view('admin.withdraws', compact('withdraws', 'status'));
    }

    /**
     * POST /admin/withdraws/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        $withdrawRequest = WithdrawRequest::findOrFail($id);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $this->withdrawService->approve($withdrawRequest, $admin);
        } catch (InsufficientBalanceException|\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Pengajuan withdraw disetujui, saldo jastiper sudah dipotong.');
    }

    /**
     * POST /admin/withdraws/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'required|string|max:500',
        ]);

        $withdrawRequest = WithdrawRequest::findOrFail($id);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $this->withdrawService->reject($withdrawRequest, $admin, $request->input('admin_note'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Pengajuan withdraw ditolak.');
    }
}