<?php

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\CustomerService;
use App\Models\Report;
use App\Models\SupportConversation;
use App\Services\CustomerSupportRoutingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CsDashboardController extends Controller
{
    public function __construct(
        private CustomerSupportRoutingService $routingService
    ) {}

    /**
     * Dashboard Portal CS: Tinjauan antrean bebas dan tiket yang sedang ditangani.
     */
    public function index()
    {
        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();

        // Heartbeat saat buka dashboard
        $this->routingService->updateHeartbeat($cs);

        // 1. Antrean Bebas (Menunggu penanganan - Zero Neglect)
        $unassignedChats = SupportConversation::with(['customer', 'customer.supportMessages' => function ($q) {
            $q->latest()->limit(1);
        }])
        ->where('status', 'menunggu')
        ->orderByDesc('last_message_at')
        ->get();

        $unassignedReports = Report::with(['order.customer', 'order.jastiper'])
            ->where('cs_claim_status', 'unassigned')
            ->where('status', 'menunggu')
            ->latest()
            ->get();

        // 2. Pekerjaan Aktif Saya
        $myActiveChats = SupportConversation::with(['customer', 'customer.supportMessages' => function ($q) {
            $q->latest()->limit(1);
        }])
        ->where('assigned_cs_id', $cs->id)
        ->where('status', 'aktif')
        ->orderByDesc('last_message_at')
        ->get();

        $myActiveReports = Report::with(['order.customer', 'order.jastiper'])
            ->where('assigned_cs_id', $cs->id)
            ->where('status', 'menunggu')
            ->latest()
            ->get();

        // 3. Statistik Kinerja Hari Ini
        $myResolvedReportsCount = Report::where('assigned_cs_id', $cs->id)
            ->where('status', 'selesai')
            ->count();

        $myClosedChatsCount = SupportConversation::where('assigned_cs_id', $cs->id)
            ->where('status', 'selesai')
            ->count();

        $stats = [
            'unassigned_chats' => $unassignedChats->count(),
            'unassigned_reports' => $unassignedReports->count(),
            'my_active_chats' => $myActiveChats->count(),
            'my_active_reports' => $myActiveReports->count(),
            'my_resolved_reports' => $myResolvedReportsCount,
            'my_closed_chats' => $myClosedChatsCount,
        ];

        return view('cs.dashboard', compact(
            'cs',
            'stats',
            'unassignedChats',
            'unassignedReports',
            'myActiveChats',
            'myActiveReports'
        ));
    }

    /**
     * Ubah status ketersediaan kerja CS (tersedia, sibuk, istirahat).
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'work_status' => 'required|in:tersedia,sibuk,istirahat',
        ]);

        /** @var CustomerService $cs */
        $cs = Auth::guard('cs')->user();
        $this->routingService->updateHeartbeat($cs, $request->work_status);

        $statusLabels = [
            'tersedia' => 'Siap Menerima Tiket (Tersedia)',
            'sibuk' => 'Sedang Menangani (Sibuk)',
            'istirahat' => 'Sedang Rehat / Istirahat',
        ];

        return back()->with('success', "Status kerja berhasil diubah menjadi: {$statusLabels[$request->work_status]}.");
    }

    /**
     * Endpoint Heartbeat berkala (AJAX) untuk menjaga status online CS & mengecek antrean baru.
     */
    public function heartbeat()
    {
        /** @var CustomerService|null $cs */
        $cs = Auth::guard('cs')->user();
        if (!$cs) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $this->routingService->updateHeartbeat($cs);

        $unassignedChatsCount = SupportConversation::where('status', 'menunggu')->count();
        $unassignedReportsCount = Report::where('cs_claim_status', 'unassigned')->where('status', 'menunggu')->count();

        return response()->json([
            'status' => 'ok',
            'is_online' => true,
            'work_status' => $cs->work_status,
            'last_active_at' => $cs->last_active_at->toIso8601String(),
            'unassigned_chats' => $unassignedChatsCount,
            'unassigned_reports' => $unassignedReportsCount,
            'has_urgent_queue' => ($unassignedChatsCount > 0 || $unassignedReportsCount > 0),
        ]);
    }
}

