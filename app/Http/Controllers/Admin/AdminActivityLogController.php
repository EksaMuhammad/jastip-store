<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    /**
     * Tampilkan halaman utama Log & Monitoring Aktivitas Platform.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $userType = $request->query('user_type');
        $category = $request->query('category');
        $orderId = $request->query('order_id');
        $dateFilter = $request->query('date', 'all');

        $query = ActivityLog::with('order')->latest();

        // 1. Filter Pencarian Teks
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // 2. Filter Peran User
        if ($userType && $userType !== 'all') {
            $query->where('user_type', $userType);
        }

        // 3. Filter Kategori Aktivitas
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        // 4. Filter Nomor Order Tertentu
        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        // 5. Filter Rentang Waktu
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($dateFilter === 'yesterday') {
            $query->whereDate('created_at', today()->subDay());
        } elseif ($dateFilter === 'this_week') {
            $query->where('created_at', '>=', now()->startOfWeek());
        } elseif ($dateFilter === 'this_month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        }

        // Statistik Ringkasan
        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'orders' => ActivityLog::whereIn('category', ['pesanan', 'pengantaran'])->count(),
            'tickets' => ActivityLog::whereIn('category', ['komplain_tiket', 'chat_cs'])->count(),
            'adjustments' => ActivityLog::where('category', 'harga_toko')->count(),
        ];

        $activities = $query->paginate(25)->withQueryString();

        return view('admin.activities.index', compact('activities', 'stats', 'search', 'userType', 'category', 'orderId', 'dateFilter'));
    }

    /**
     * Dapatkan riwayat timeline lengkap untuk pesanan tertentu (JSON untuk modal timeline investigasi CS).
     */
    public function orderTimeline($orderId)
    {
        $order = Order::with(['customer', 'jastiper', 'priceAdjustments', 'cancellationLog', 'reports'])->find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        $logs = ActivityLog::where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'category' => $log->category,
                    'category_meta' => $log->category_meta,
                    'title' => $log->title,
                    'description' => $log->description,
                    'user_type' => $log->user_type,
                    'user_name' => $log->user_name,
                    'role_badge' => $log->role_badge,
                    'properties' => $log->properties,
                    'time' => $log->created_at->format('H:i'),
                    'date' => $log->created_at->translatedFormat('d M Y'),
                    'full_time' => $log->created_at->translatedFormat('d F Y • H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'description' => $order->description,
                'status' => $order->status,
                'customer_name' => $order->customer?->name ?? 'Customer #' . $order->customer_id,
                'customer_phone' => $order->customer?->phone_number,
                'jastiper_name' => $order->jastiper?->name ?? 'Belum ada jastiper',
                'jastiper_phone' => $order->jastiper?->phone_number,
                'fare' => $order->agreed_fare ?: $order->estimated_fare,
                'created_at' => $order->created_at->translatedFormat('d F Y • H:i'),
            ],
            'timeline' => $logs,
        ]);
    }
}
