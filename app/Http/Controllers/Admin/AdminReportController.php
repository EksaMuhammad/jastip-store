<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Report;
use App\Services\ChatService;
use App\Services\OrderCompletionService;
use App\Services\WalletService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private OrderCompletionService $completionService,
        private ChatService $chatService
    ) {}

    /**
     * Tampilkan daftar pengaduan / sengketa pesanan.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'menunggu');
        $category = $request->query('category');
        $search = $request->query('search');

        $query = Report::with(['order.customer', 'order.jastiper', 'decider'])->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('order.customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('order.jastiper', function ($jq) use ($search) {
                      $jq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 'menunggu')->count(),
            'resolved' => Report::where('status', 'selesai')->count(),
            'total_refund' => (float) Report::where('status', 'selesai')->sum('refund_amount'),
        ];

        return view('admin.reports.index', compact('reports', 'stats', 'status', 'category', 'search'));
    }

    /**
     * Tampilkan detail pengaduan, pesanan terkait, bukti gambar, dan aksi resolusi.
     */
    public function show($id)
    {
        $report = Report::with([
            'order.customer.wallet',
            'order.jastiper.wallet',
            'order.items.product',
            'order.payments',
            'order.chats',
            'order.priceAdjustments',
            'decider',
        ])->findOrFail($id);

        $order = $report->order;
        $customer = $order->customer;
        $jastiper = $order->jastiper;

        // Hitung total dana yang telah dibayarkan oleh customer
        $totalPaid = (float) ($order->payments()->where('status', 'lunas')->sum('amount') ?: $order->agreed_fare);
        $totalPaid += $order->approvedPriceAdjustmentsTotal();

        return view('admin.reports.show', compact('report', 'order', 'customer', 'jastiper', 'totalPaid'));
    }

    /**
     * Eksekusi keputusan resolusi komplain / sengketa oleh Admin.
     */
    public function resolve(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return redirect()->route('admin.login')->with('error', 'Silakan login sebagai admin.');
        }

        $report = Report::with(['order.customer.wallet', 'order.jastiper.wallet'])->findOrFail($id);
        $order = $report->order;

        if ($report->status === 'selesai') {
            return redirect()->route('admin.reports.show', $report->id)
                ->with('error', 'Pengaduan ini sudah diselesaikan sebelumnya.');
        }

        $request->validate([
            'action' => 'required|in:refund_full,refund_partial,reject_complaint',
            'refund_amount' => 'required_if:action,refund_partial|nullable|numeric|min:1',
            'admin_decision' => 'required|string|min:5|max:1000',
            'investigation_notes' => 'nullable|string|max:2000',
        ], [
            'action.required' => 'Pilih keputusan resolusi pengaduan.',
            'admin_decision.required' => 'Tuliskan alasan/ringkasan keputusan resmi admin.',
            'refund_amount.required_if' => 'Nominal pengembalian dana sebagian wajib diisi.',
            'refund_amount.min' => 'Nominal refund minimal Rp 1.',
        ]);

        $result = DB::transaction(function () use ($request, $report, $order, $admin) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first();
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            $totalPaid = (float) ($lockedOrder->payments()->where('status', 'lunas')->sum('amount') ?: $lockedOrder->agreed_fare);
            $totalPaid += $lockedOrder->approvedPriceAdjustmentsTotal();

            $action = $request->action;
            $refundAmount = 0;

            if ($action === 'refund_full') {
                $refundAmount = $totalPaid;

                // Kreditkan dana penuh ke dompet customer
                $customerWallet = $lockedOrder->customer->wallet;
                if ($customerWallet && $refundAmount > 0) {
                    $this->walletService->credit(
                        $customerWallet,
                        $refundAmount,
                        'refund_dispute',
                        $lockedOrder->id,
                        "Pengembalian dana 100% komplain #REP-{$lockedReport->id} (Pesanan #{$lockedOrder->id})"
                    );
                }

                // Tandai order dibatalkan karena komplain disetujui penuh
                $lockedOrder->update([
                    'status' => 'dibatalkan',
                    'cancelled_by_role' => 'admin',
                    'cancelled_by_id' => $admin->id,
                    'cancellation_reason' => 'Pengaduan customer disetujui Admin: ' . $request->admin_decision,
                ]);

                // Notifikasi sistem
                $this->chatService->sendSystemMessage(
                    $lockedOrder,
                    "⚖️ PUTUSAN ADMIN: Pengaduan #REP-{$lockedReport->id} DITERIMA. Dana sebesar Rp " . number_format($refundAmount, 0, ',', '.') . " telah dikembalikan 100% ke Saldo Dompet Customer. Pesanan ditutup."
                );

                if ($lockedOrder->customer?->phone_number) {
                    WhatsAppService::sendMessage(
                        $lockedOrder->customer->phone_number,
                        "Pengaduan Anda #REP-{$lockedReport->id} pada pesanan #{$lockedOrder->id} disetujui Admin. Saldo Rp " . number_format($refundAmount, 0, ',', '.') . " telah masuk ke dompet Anda."
                    );
                }

            } elseif ($action === 'refund_partial') {
                $refundAmount = min((float) $request->refund_amount, $totalPaid);

                // Kreditkan refund sebagian ke dompet customer
                $customerWallet = $lockedOrder->customer->wallet;
                if ($customerWallet && $refundAmount > 0) {
                    $this->walletService->credit(
                        $customerWallet,
                        $refundAmount,
                        'refund_dispute',
                        $lockedOrder->id,
                        "Pengembalian dana sebagian komplain #REP-{$lockedReport->id} (Pesanan #{$lockedOrder->id})"
                    );
                }

                // Selesaikan pesanan & teruskan hak jastiper
                $this->completionService->completeOrder($lockedOrder, 'admin');

                $this->chatService->sendSystemMessage(
                    $lockedOrder,
                    "⚖️ PUTUSAN ADMIN: Pengaduan diselesaikan dengan Refund Sebagian sebesar Rp " . number_format($refundAmount, 0, ',', '.') . " ke Dompet Customer. Sisa hak bayar diteruskan kepada Jastiper dan pesanan diselesaikan."
                );

            } elseif ($action === 'reject_complaint') {
                $refundAmount = 0;

                // Tolak komplain, verifikasi barang telah sesuai, selesaikan pesanan & bayar jastiper
                $this->completionService->completeOrder($lockedOrder, 'admin');

                $this->chatService->sendSystemMessage(
                    $lockedOrder,
                    "⚖️ PUTUSAN ADMIN: Pengaduan #REP-{$lockedReport->id} DITOLAK. Setelah investigasi, pengiriman dan belanjaan dinyatakan sah & selesai. Dana pembayaran telah diteruskan ke Jastiper."
                );
            }

            // Update Report Record
            $lockedReport->update([
                'status' => 'selesai',
                'resolution_type' => $action,
                'refund_amount' => $refundAmount,
                'admin_decision' => $request->admin_decision,
                'investigation_notes' => $request->investigation_notes,
                'decided_by' => $admin->id,
                'decided_at' => now(),
            ]);

            \App\Services\ActivityLogger::log([
                'user_type' => 'admin',
                'user_id' => $admin->id,
                'user_name' => $admin->name ?? 'Admin',
                'order_id' => $lockedOrder->id,
                'category' => 'komplain_tiket',
                'action' => 'report_resolved',
                'title' => "Tiket Komplain #REP-{$lockedReport->id} Diselesaikan",
                'description' => "Admin " . ($admin->name ?? 'Admin') . " menyelesaikan komplain pesanan #{$lockedOrder->id} dengan keputusan \"{$action}\". Catatan: \"{$request->admin_decision}\"",
                'properties' => [
                    'report_id' => $lockedReport->id,
                    'action' => $action,
                    'refund_amount' => $refundAmount,
                ],
            ]);

            return ['success' => true, 'report' => $lockedReport];
        });

        return redirect()->route('admin.reports.show', $report->id)
            ->with('success', 'Keputusan resolusi pengaduan berhasil diproses dan dicatat dalam sistem.');
    }
}
