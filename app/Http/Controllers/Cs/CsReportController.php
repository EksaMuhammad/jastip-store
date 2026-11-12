<?php

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\CustomerService;
use App\Models\Order;
use App\Models\Report;
use App\Services\ActivityLogger;
use App\Services\ChatService;
use App\Services\CustomerSupportRoutingService;
use App\Services\InsuranceService;
use App\Services\OrderCompletionService;
use App\Services\WalletService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CsReportController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private OrderCompletionService $completionService,
        private ChatService $chatService,
        private CustomerSupportRoutingService $routingService,
        private InsuranceService $insuranceService
    ) {}

    /**
     * Daftar Pengaduan & Sengketa untuk Petugas CS.
     */
    public function index(Request $request)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->updateHeartbeat($cs);

        $tab = $request->query('tab', 'my'); // 'my', 'queue', 'resolved', 'all'
        $category = $request->query('category');
        $search = $request->query('search');

        $query = Report::with(['order.customer', 'order.jastiper', 'assignedCs'])->latest();

        if ($tab === 'my') {
            $query->where('assigned_cs_id', $cs->id)->where('status', 'menunggu');
        } elseif ($tab === 'queue') {
            $query->where('cs_claim_status', 'unassigned')->where('status', 'menunggu');
        } elseif ($tab === 'resolved') {
            $query->where('assigned_cs_id', $cs->id)->where('status', 'selesai');
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
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        $stats = [
            'my_pending' => Report::where('assigned_cs_id', $cs->id)->where('status', 'menunggu')->count(),
            'unassigned_queue' => Report::where('cs_claim_status', 'unassigned')->where('status', 'menunggu')->count(),
            'my_resolved' => Report::where('assigned_cs_id', $cs->id)->where('status', 'selesai')->count(),
            'all_total' => Report::count(),
        ];

        return view('cs.reports.index', compact('cs', 'reports', 'stats', 'tab', 'category', 'search'));
    }

    /**
     * Tampilkan detail komplain pesanan untuk diinvestigasi oleh CS.
     */
    public function show($id)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->updateHeartbeat($cs);

        $report = Report::with([
            'order.customer.wallet',
            'order.jastiper.wallet',
            'order.items.product',
            'order.payments',
            'order.chats',
            'order.priceAdjustments',
            'assignedCs',
        ])->findOrFail($id);

        $order = $report->order;
        $customer = $order->customer;
        $jastiper = $order->jastiper;

        $totalPaid = (float) ($order->payments()->where('status', 'lunas')->sum('amount') ?: $order->agreed_fare);
        $totalPaid += $order->approvedPriceAdjustmentsTotal();

        return view('cs.reports.show', compact('cs', 'report', 'order', 'customer', 'jastiper', 'totalPaid'));
    }

    /**
     * Endpoint polling status tiket untuk refresh otomatis
     */
    public function status(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        return response()->json([
            'id' => $report->id,
            'status' => $report->status,
            'is_resolved' => $report->status === 'selesai',
        ]);
    }

    /**
     * Petugas CS mengklaim tiket komplain dari antrean bebas.
     */
    public function claim($id)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $report = Report::findOrFail($id);

        if ($report->status === 'selesai') {
            return back()->with('error', 'Tiket ini telah diselesaikan sebelumnya.');
        }

        $this->routingService->claimReport($report, $cs);

        ActivityLogger::log([
            'user_type' => 'customer_service',
            'user_id' => $cs->id,
            'user_name' => $cs->name,
            'order_id' => $report->order_id,
            'category' => 'cs_report',
            'action' => 'claim_report',
            'title' => "Klaim Tiket Komplain",
            'description' => "CS {$cs->name} mengklaim penanganan tiket komplain #REP-{$report->id} (Order #{$report->order_id})",
            'properties' => ['report_id' => $report->id],
        ]);

        return redirect()->route('cs.reports.show', $report->id)
            ->with('success', "Tiket komplain #REP-{$report->id} berhasil Anda klaim. Silakan lakukan investigasi.");
    }

    /**
     * CS mengambil keputusan resolusi atas komplain pesanan.
     */
    public function resolve(Request $request, $id)
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();

        $report = Report::with(['order.customer.wallet', 'order.jastiper.wallet'])->findOrFail($id);
        $order = $report->order;

        if ($report->status === 'selesai') {
            return redirect()->route('cs.reports.show', $report->id)
                ->with('error', 'Pengaduan ini sudah diselesaikan sebelumnya.');
        }

        if ($request->has('refund_amount')) {
            $cleaned = preg_replace('/[^\d]/', '', (string) $request->refund_amount);
            $request->merge(['refund_amount' => $cleaned !== '' ? $cleaned : null]);
        }

        $request->validate([
            'action' => 'required|in:refund_full,refund_partial,reject_complaint',
            'refund_amount' => 'required_if:action,refund_partial|nullable|numeric|min:1',
            'admin_decision' => 'required|string|min:5|max:1000',
            'investigation_notes' => 'nullable|string|max:2000',
        ], [
            'action.required' => 'Pilih keputusan resolusi pengaduan.',
            'admin_decision.required' => 'Tuliskan catatan putusan resmi komplain.',
            'refund_amount.required_if' => 'Nominal refund sebagian wajib diisi.',
            'refund_amount.min' => 'Nominal refund minimal Rp 1.',
        ]);

        DB::transaction(function () use ($request, $report, $order, $cs) {
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->first();
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            $totalPaid = (float) ($lockedOrder->payments()->where('status', 'lunas')->sum('amount') ?: $lockedOrder->agreed_fare);
            $totalPaid += $lockedOrder->approvedPriceAdjustmentsTotal();

            $action = $request->action;
            $refundAmount = 0;
            $isInsuranceClaim = $request->boolean('use_insurance') && in_array($action, ['refund_full', 'refund_partial']);

            if ($action === 'refund_full') {
                $refundAmount = $totalPaid;

                if ($isInsuranceClaim && $refundAmount > 0) {
                    $this->insuranceService->claimInsurance(
                        $lockedReport,
                        $refundAmount,
                        $refundAmount,
                        $request->admin_decision
                    );
                } else {
                    $customerWallet = $lockedOrder->customer->wallet;
                    if ($customerWallet && $refundAmount > 0) {
                        $this->walletService->credit(
                            $customerWallet,
                            $refundAmount,
                            'refund_dispute',
                            $lockedOrder->id,
                            "Pengembalian dana 100% komplain #REP-{$lockedReport->id} disetujui CS {$cs->name}"
                        );
                    }
                }

                $lockedOrder->update([
                    'status' => 'dibatalkan',
                    'cancelled_by_role' => 'admin',
                    'cancellation_reason' => "Pengaduan disetujui CS {$cs->name}: " . $request->admin_decision,
                ]);

                $claimTag = $isInsuranceClaim ? " (Klaim Proteksi Asuransi Ditalangi Platform)" : "";
                $this->chatService->sendSystemMessage(
                    $lockedOrder,
                    "⚖️ PUTUSAN CS ({$cs->name}): Pengaduan #REP-{$lockedReport->id} DITERIMA{$claimTag}. Dana sebesar Rp " . number_format($refundAmount, 0, ',', '.') . " telah dikembalikan 100% ke Saldo Dompet Customer. Pesanan ditutup."
                );

                if ($lockedOrder->customer?->phone_number) {
                    WhatsAppService::sendMessage(
                        $lockedOrder->customer->phone_number,
                        "Pengaduan Anda #REP-{$lockedReport->id} disetujui CS {$cs->name}. Dana Rp " . number_format($refundAmount, 0, ',', '.') . " telah dikembalikan ke dompet Anda."
                    );
                }

                if ($lockedOrder->jastiper?->phone_number) {
                    WhatsAppService::sendMessage(
                        $lockedOrder->jastiper->phone_number,
                        "⚖️ Putusan CS: Pengaduan pesanan #{$lockedOrder->id} telah diselesaikan oleh CS {$cs->name} (Keputusan: Refund 100% - {$request->admin_decision})."
                    );
                }

            } elseif ($action === 'refund_partial') {
                $refundAmount = min((float) $request->refund_amount, $totalPaid);

                if ($isInsuranceClaim && $refundAmount > 0) {
                    $this->insuranceService->claimInsurance(
                        $lockedReport,
                        $refundAmount,
                        $refundAmount,
                        $request->admin_decision
                    );
                } else {
                    $customerWallet = $lockedOrder->customer->wallet;
                    if ($customerWallet && $refundAmount > 0) {
                        $this->walletService->credit(
                            $customerWallet,
                            $refundAmount,
                            'refund_dispute',
                            $lockedOrder->id,
                            "Refund sebagian komplain #REP-{$lockedReport->id} oleh CS {$cs->name}"
                        );
                    }
                }

                $this->completionService->completeOrder($lockedOrder, 'admin');

                $claimTag = $isInsuranceClaim ? " (Talangan Kas Asuransi Platform)" : "";
                $this->chatService->sendSystemMessage(
                    $lockedOrder,
                    "⚖️ PUTUSAN CS ({$cs->name}): Pengaduan diselesaikan dengan Refund Sebagian{$claimTag} sebesar Rp " . number_format($refundAmount, 0, ',', '.') . " ke Dompet Customer. Sisa hak bayar diteruskan kepada Jastiper."
                );

            } elseif ($action === 'reject_complaint') {
                $refundAmount = 0;
                $this->completionService->completeOrder($lockedOrder, 'admin');

                $this->chatService->sendSystemMessage(
                    $lockedOrder,
                    "⚖️ PUTUSAN CS ({$cs->name}): Setelah investigasi, pengaduan #REP-{$lockedReport->id} DITOLAK. Barang telah diverifikasi sesuai. Pembayaran diteruskan kepada Jastiper."
                );
            }

            // Update status tiket Report
            $lockedReport->update([
                'status' => 'selesai',
                'cs_claim_status' => 'resolved',
                'assigned_cs_id' => $cs->id,
                'resolution_type' => $action,
                'admin_decision' => $request->admin_decision,
                'investigation_notes' => $request->investigation_notes,
                'refund_amount' => $refundAmount,
                'is_insurance_claim' => $isInsuranceClaim,
                'insurance_payout' => $isInsuranceClaim ? $refundAmount : 0,
                'jastiper_charged_amount' => $isInsuranceClaim ? $refundAmount : 0,
                'decided_at' => now(),
            ]);

            $this->routingService->updateHeartbeat($cs);

            ActivityLogger::log([
                'user_type' => 'customer_service',
                'user_id' => $cs->id,
                'user_name' => $cs->name,
                'order_id' => $lockedOrder->id,
                'category' => 'cs_report',
                'action' => 'resolve_report',
                'title' => "Putusan Resolusi Komplain",
                'description' => "CS {$cs->name} memutuskan resolusi {$action} untuk komplain #REP-{$lockedReport->id} (Refund: Rp " . number_format($refundAmount, 0, ',', '.') . ")",
                'properties' => [
                    'report_id' => $lockedReport->id,
                    'action' => $action,
                    'refund_amount' => $refundAmount,
                ],
            ]);
        });

        return redirect()->route('cs.reports.show', $report->id)
            ->with('success', "Resolusi komplain #REP-{$report->id} berhasil dieksekusi dan disimpan.");
    }
}

