<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\JastiperVerification;
use App\Models\Order;
use App\Models\Jastiper;
use App\Services\WhatsAppService;
use App\Services\OrderDealService;
use App\Services\OrderCompletionService;
use App\Services\ChatService;
use App\Models\Komisi;
use App\Models\Promo;

class DashboardController extends Controller
{
    /**
     * Dashboard untuk Customer.
     */
    public function customerDashboard()
    {
        $customer = Auth::guard('customer')->user();

        // Catatan: daftar "Pesanan Aktif" TIDAK lagi di-query di sini. Data diambil
        // sepenuhnya oleh JavaScript lewat endpoint customerActiveOrdersFeed()
        // (GET /customer/orders/active-feed), baik untuk render pertama kali
        // maupun untuk polling real-time berikutnya (tawaran masuk, status deal, dst).
        // Ambil order yang sudah selesai tapi belum diberi rating oleh customer
        $unratedCompletedOrders = \App\Models\Order::where('customer_id', $customer->id)
            ->where('status', 'selesai')
            ->whereDoesntHave('rating')
            ->latest()
            ->get();

        $promos = Promo::where('is_active', true)->latest()->get();

        // Ambil jastiper yang sedang aktif check-in di lokasi belanja
        $checkinJastipers = Jastiper::whereNotNull('checkin_location')
            ->where('is_available', true)
            ->where('verification_status', 'approved')
            ->with('badge')
            ->latest('checked_in_at')
            ->get();

        $balance = $customer->wallet ? $customer->wallet->balance : 0;

        $pendingPriceAdjustments = \App\Models\OrderPriceAdjustment::whereHas('order', fn($q) => $q->where('customer_id', $customer->id))
            ->where('status', 'pending')
            ->with(['order', 'orderItem'])
            ->latest()
            ->get();

        return view('dashboard.customer', compact('customer', 'balance', 'unratedCompletedOrders', 'promos', 'checkinJastipers', 'pendingPriceAdjustments'));
    }

    /**
     * Halaman Aktivitas Customer (Proses & Riwayat).
     */
    public function customerActivity()
    {
        $customer = Auth::guard('customer')->user();
        
        $activeOrders = \App\Models\Order::where('customer_id', $customer->id)
            ->whereNotIn('status', ['selesai', 'dibatalkan', 'bermasalah'])
            ->with(['jastiper', 'addons', 'merchant', 'items.product.merchant'])
            ->latest()
            ->get();
            
        $pastOrders = \App\Models\Order::where('customer_id', $customer->id)
            ->whereIn('status', ['selesai', 'dibatalkan', 'bermasalah'])
            ->with(['jastiper', 'merchant', 'items.product.merchant'])
            ->latest()
            ->get();

        return view('dashboard.customer.activity', compact('customer', 'activeOrders', 'pastOrders'));
    }

    /**
     * Halaman Rincian Pesanan Customer (ShopeeFood / Mobile Reference Style).
     */
    public function customerOrderDetail($id)
    {
        $customer = Auth::guard('customer')->user();

        $order = \App\Models\Order::where('id', $id)
            ->where('customer_id', $customer->id)
            ->with([
                'jastiper.badge',
                'orderItems.product.merchant',
                'orderItems.priceAdjustment',
                'priceAdjustments',
                'payments' => fn($q) => $q->latest('id'),
                'addons',
                'wilayah',
                'merchant'
            ])
            ->firstOrFail();

        $latestPayment = $order->payments->first();
        $timelineLogs = \App\Models\ActivityLog::where('order_id', $order->id)->orderBy('created_at', 'asc')->get();

        return view('customer.orders.show', compact('customer', 'order', 'latestPayment', 'timelineLogs'));
    }

    /**
     * Cetak & Unduh Faktur / Nota Resmi Transaksi Pesanan (Untuk Reimburse Kantor & Arsip).
     */
    public function customerOrderInvoice($id)
    {
        $customer = Auth::guard('customer')->user();

        $order = \App\Models\Order::where('id', $id)
            ->where('customer_id', $customer->id)
            ->with([
                'customer.wallet',
                'jastiper.badge',
                'orderItems.product.merchant',
                'orderItems.priceAdjustment',
                'priceAdjustments',
                'payments' => fn($q) => $q->latest('id'),
                'addons',
                'wilayah',
                'merchant'
            ])
            ->firstOrFail();

        $latestPayment = $order->payments->first();

        return view('customer.orders.invoice', compact('customer', 'order', 'latestPayment'));
    }

    /**
     * Halaman Nota Transaksi Resmi (Public / Read-Only).
     * Dapat diakses penerima tanpa login melalui tautan bertoken aman, atau oleh pemilik pesanan/jastiper/admin.
     */
    public function publicOrderReceipt(Request $request, $id)
    {
        $order = \App\Models\Order::where('id', $id)
            ->with([
                'customer.wallet',
                'jastiper.badge',
                'orderItems.product.merchant',
                'orderItems.priceAdjustment',
                'priceAdjustments',
                'payments' => fn($q) => $q->latest('id'),
                'addons',
                'wilayah',
                'merchant'
            ])
            ->firstOrFail();

        $token = (string) $request->query('token');
        $expectedToken = $order->receipt_share_token;
        $isOwner = Auth::guard('customer')->check() && Auth::guard('customer')->id() === $order->customer_id;
        $isJastiper = Auth::guard('jastiper')->check() && Auth::guard('jastiper')->id() === $order->jastiper_id;
        $isAdmin = Auth::guard('admin')->check();

        if (! hash_equals($expectedToken, $token) && ! $isOwner && ! $isJastiper && ! $isAdmin) {
            abort(403, 'Akses nota tidak valid atau tautan kedaluwarsa.');
        }

        $customer = $order->customer;
        $latestPayment = $order->payments->first();
        $isPublicView = true;

        return view('customer.orders.invoice', compact('customer', 'order', 'latestPayment', 'isPublicView'));
    }

    /**
     * Cetak & Unduh Bukti Top-Up Saldo E-Wallet (Untuk Reimburse Kantor & Keuangan).
     */
    public function customerTopupInvoice($id)
    {
        $customer = Auth::guard('customer')->user();

        $topup = \App\Models\Topup::with(['wallet.owner', 'verifiedByAdmin', 'approvedByAdmin'])
            ->where('id', $id)
            ->firstOrFail();

        if ($topup->wallet->owner_role !== 'customer' || (int)$topup->wallet->owner_id !== (int)$customer->id) {
            abort(403, 'Akses tidak diizinkan. Bukti top-up ini bukan milik Anda.');
        }

        return view('customer.wallet.topup-invoice', compact('customer', 'topup'));
    }

    /**
     * Halaman Riwayat Chat Customer.
     */
    public function customerChats()
    {
        $customer = Auth::guard('customer')->user();

        // Otomatis tandai pesan sistem pada pesanan lampau yang sudah selesai/dibatalkan sebagai dibaca
        \App\Models\Chat::whereHas('order', fn($q) => $q->where('customer_id', $customer->id)->whereIn('status', ['selesai', 'dibatalkan']))
            ->where('sender_role', 'system')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $orders = \App\Models\Order::where('customer_id', $customer->id)
            ->whereNotNull('jastiper_id')
            ->with(['jastiper', 'chats' => function ($query) {
                $query->latest();
            }])
            ->get();

        $conversations = $orders->map(function ($order) {
            $latestChat = $order->chats->first();
            $unreadCount = $order->chats()->where('sender_role', '!=', 'customer')->where('is_read', false)->count();

            return [
                'order_id' => $order->id,
                'order_description' => $order->description,
                'jastiper_name' => $order->jastiper->name ?? 'Jastiper',
                'jastiper_id' => $order->jastiper_id,
                'latest_message' => $latestChat ? ($latestChat->message_type === 'image' ? '📷 [Foto]' : $latestChat->message) : 'Belum ada pesan. Mulai percakapan...',
                'latest_message_time' => $latestChat ? $latestChat->created_at->format('d/m/y') : $order->created_at->format('d/m/y'),
                'latest_message_timestamp' => $latestChat ? $latestChat->created_at : $order->created_at,
                'unread_count' => $unreadCount,
            ];
        })->sortByDesc('latest_message_timestamp')->values();

        return view('dashboard.customer.chats', compact('customer', 'conversations'));
    }

    /**
     * Tandai semua chat sebagai terbaca untuk customer aktif.
     */
    public function customerMarkAllChatsRead(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        // Tandai seluruh chat pesanan customer sebagai terbaca
        \App\Models\Chat::whereHas('order', fn($q) => $q->where('customer_id', $customer->id))
            ->where('sender_role', '!=', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Tandai seluruh support message customer sebagai terbaca
        \App\Models\SupportMessage::where('customer_id', $customer->id)
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua obrolan telah ditandai sebagai dibaca.'
            ]);
        }

        return redirect()->back()->with('success', 'Semua obrolan telah ditandai sebagai dibaca.');
    }

    /**
     * Halaman Profil Customer.
     */
    public function customerProfile()
    {
        $customer = Auth::guard('customer')->user();
        $balance = $customer->wallet ? $customer->wallet->balance : 0;
        
        $completedOrdersCount = Order::where('customer_id', $customer->id)
            ->where('status', 'selesai')
            ->count();
            
        $totalOrdersCount = Order::where('customer_id', $customer->id)->count();
        $favoriteCount = $customer->favorites()->count();

        return view('customer.profile', compact(
            'customer',
            'balance',
            'completedOrdersCount',
            'totalOrdersCount',
            'favoriteCount'
        ));
    }

    /**
     * Update Profil Customer
     */
    public function updateCustomerProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $customer->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Helper to get customer saved addresses (from persistent cache or session)
     */
    public static function getCustomerSavedAddresses(?\App\Models\Customer $customer = null): array
    {
        if (!$customer) {
            $customer = Auth::guard('customer')->user();
        }

        if ($customer) {
            $cached = Cache::get("customer_saved_addresses_{$customer->id}");
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }

        $sessionAddresses = session()->get('customer_saved_addresses', []);
        if (!empty($sessionAddresses) && $customer) {
            Cache::forever("customer_saved_addresses_{$customer->id}", $sessionAddresses);
        }

        return $sessionAddresses;
    }

    /**
     * Helper to persist customer saved addresses (to cache & session)
     */
    public static function persistCustomerAddresses(?\App\Models\Customer $customer, array $addresses): void
    {
        if ($customer) {
            Cache::forever("customer_saved_addresses_{$customer->id}", $addresses);
        }
        session()->put('customer_saved_addresses', $addresses);
        session()->save();
    }

    /**
     * Halaman Kelola & Tambah Alamat Tersimpan Customer
     */
    public function customerAddress()
    {
        $customer = Auth::guard('customer')->user();
        $savedAddresses = self::getCustomerSavedAddresses($customer);
        return view('customer.address', compact('customer', 'savedAddresses'));
    }

    /**
     * Simpan / Tambah Alamat Customer
     */
    public function saveCustomerAddress(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'label' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
        ]);

        $customer = Auth::guard('customer')->user();
        $savedAddresses = self::getCustomerSavedAddresses($customer);

        $newAddress = [
            'id' => (string) (time() . '_' . rand(100, 999)),
            'label' => trim($request->label ?: 'Alamat Saya'),
            'address' => trim($request->address),
            'latitude' => $request->latitude ? (float) $request->latitude : null,
            'longitude' => $request->longitude ? (float) $request->longitude : null,
            'notes' => $request->notes ? trim($request->notes) : null,
            'created_at' => now()->translatedFormat('d M Y H:i') ?: now()->format('d M Y H:i'),
        ];

        array_unshift($savedAddresses, $newAddress);
        $savedAddresses = array_slice($savedAddresses, 0, 20);

        self::persistCustomerAddresses($customer, $savedAddresses);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'address' => $newAddress, 'savedAddresses' => $savedAddresses]);
        }

        return redirect()->route('customer.address')->with('success', 'Alamat berhasil disimpan!');
    }

    /**
     * Hapus Alamat Tersimpan Customer
     */
    public function deleteCustomerAddress(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();
        $savedAddresses = self::getCustomerSavedAddresses($customer);

        $savedAddresses = array_values(array_filter($savedAddresses, function ($addr) use ($id) {
            return (string) ($addr['id'] ?? '') !== (string) $id;
        }));

        self::persistCustomerAddresses($customer, $savedAddresses);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'savedAddresses' => $savedAddresses]);
        }

        return redirect()->route('customer.address')->with('success', 'Alamat berhasil dihapus!');
    }

    /**
     * Halaman Keamanan Akun & Password Customer
     */
    public function customerSecurity()
    {
        $customer = Auth::guard('customer')->user();
        $hasPassword = !empty($customer->password);
        return view('customer.security', compact('customer', 'hasPassword'));
    }

    /**
     * Update Password Customer
     */
    public function updateCustomerPassword(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $hasPassword = !empty($customer->password);

        if ($hasPassword) {
            $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
            ], [
                'current_password.required' => 'Kata sandi saat ini wajib diisi.',
                'password.required' => 'Kata sandi baru wajib diisi.',
                'password.min' => 'Kata sandi baru minimal 6 karakter.',
                'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            ]);

            if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $customer->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->withInput();
            }
        } else {
            $request->validate([
                'password' => 'required|string|min:6|confirmed',
            ], [
                'password.required' => 'Kata sandi baru wajib diisi.',
                'password.min' => 'Kata sandi baru minimal 6 karakter.',
                'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            ]);
        }

        $customer->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return redirect()->route('customer.security')->with('success', 'Kata sandi akun berhasil diperbarui! Anda dapat menggunakannya untuk login.');
    }

    /**
     * Dashboard untuk Jastiper.
     */
    public function jastiperDashboard()
    {
        $jastiper = Auth::guard('jastiper')->user();
        // Otomatis sinkronkan wilayah jika koordinat jastiper berada di wilayah terdekat yang berbeda
        $jastiper->syncWilayahFromCoordinates();
        // Load relation wilayah to display location
        $jastiper->load(['wilayah', 'latestVerification']);

        // Ambil direct orders khusus untuk Jastiper ini yang belum direspons
        $directOrders = \App\Models\Order::where('status', 'menunggu_tawaran')
            ->where('jastiper_id', $jastiper->id)
            ->with(['customer', 'merchant', 'items.product.merchant'])
            ->latest()
            ->get();

        $activeOrders = \App\Models\Order::where('jastiper_id', $jastiper->id)
            ->whereIn('status', ['deal', 'diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan'])
            ->with(['customer', 'merchant', 'items.product.merchant', 'addons'])
            ->latest()
            ->get();

        // Pesanan yang sedang dalam proses investigasi pengaduan/sengketa
        $disputedOrders = \App\Models\Order::where('jastiper_id', $jastiper->id)
            ->where('status', 'bermasalah')
            ->with(['customer', 'merchant', 'items.product.merchant', 'reports'])
            ->latest()
            ->get();

        // Riwayat pesanan terdahulu (Selesai / Dibatalkan)
        $pastOrders = \App\Models\Order::where('jastiper_id', $jastiper->id)
            ->whereIn('status', ['selesai', 'dibatalkan'])
            ->with(['customer', 'merchant', 'items.product.merchant', 'reports'])
            ->latest()
            ->take(10)
            ->get();

        // Catatan: daftar order umum (feed radius+kategori) SENGAJA tidak lagi diambil
        // di sini. Feed sepenuhnya diambil oleh JavaScript di view lewat endpoint
        // jastiperOrderFeed() (GET /jastiper/orders/feed), baik untuk render pertama
        // kali halaman dibuka maupun untuk polling real-time berikutnya. Ini supaya
        // hanya ada SATU logika query (radius+kategori) yang dipakai, dan tidak ada
        // lagi perbedaan data antara render awal vs hasil polling.
        // ===== Rekap Pendapatan ringkas untuk kartu di atas dashboard (Bagian 3
        // Sprint 8 §2.10) — angka detail lengkap ada di halaman
        // jastiper.earnings, di sini cukup ringkasan hari ini/bulan ini/rating/
        // penyelesaian. Dihitung langsung di controller (bukan fetch JS)
        // karena halaman ini render dashboard utuh sekali muat, bukan feed
        // yang di-polling seperti bagian order.
        $komisiBaseQuery = Komisi::whereHas('order', function ($q) use ($jastiper) {
            $q->where('jastiper_id', $jastiper->id);
        });

        $todayEarnings = (clone $komisiBaseQuery)->whereDate('created_at', now()->toDateString())->sum('net_amount');
        $monthEarnings = (clone $komisiBaseQuery)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('net_amount');

        $totalOrdersCount = Order::where('jastiper_id', $jastiper->id)
            ->whereNotIn('status', ['menunggu_tawaran', 'menunggu_pembayaran'])
            ->count();
        $completedOrdersCount = Order::where('jastiper_id', $jastiper->id)->where('status', 'selesai')->count();
        // Belum ada order relevan sama sekali -> tampilkan 100% (ramah untuk
        // jastiper baru), bukan 0% yang terkesan buruk.
        $completionRate = $totalOrdersCount > 0 ? round(($completedOrdersCount / $totalOrdersCount) * 100) : 100;

        // Rating: pakai Badge (Bagian 2) kalau sudah ada datanya, fallback ke
        // rata-rata langsung dari tabel ratings kalau Bagian 2 belum jalan/
        // belum sempat recalculate.
        $ratingAvg = $jastiper->badge?->avg_rating;
        if (!$ratingAvg) {
            $ratingAvg = $jastiper->ratings()->avg('rating');
        }
        $ratingAvg = $ratingAvg ? round((float) $ratingAvg, 1) : null;

        return view('dashboard.jastiper', compact(
            'jastiper',
            'directOrders',
            'activeOrders',
            'disputedOrders',
            'pastOrders',
            'todayEarnings',
            'monthEarnings',
            'completionRate',
            'ratingAvg'
        ));
    }

    /**
     * Halaman Riwayat Pesanan Jastiper.
     */
    public function jastiperHistory(Request $request)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $tab = $request->query('tab', 'semua'); // 'semua', 'selesai', 'batal'

        $query = \App\Models\Order::where('jastiper_id', $jastiper->id)
            ->whereIn('status', ['selesai', 'dibatalkan', 'bermasalah'])
            ->with(['customer', 'merchant', 'items.product.merchant', 'reports'])
            ->latest('updated_at');

        if ($tab === 'selesai') {
            $query->where('status', 'selesai');
        } elseif ($tab === 'batal') {
            $query->whereIn('status', ['dibatalkan', 'bermasalah']);
        }

        $orders = $query->paginate(15)->withQueryString();

        // Statistik Ringkasan
        $totalCompleted = \App\Models\Order::where('jastiper_id', $jastiper->id)->where('status', 'selesai')->count();
        $totalCancelled = \App\Models\Order::where('jastiper_id', $jastiper->id)->whereIn('status', ['dibatalkan', 'bermasalah'])->count();
        $totalEarned = \App\Models\Order::where('jastiper_id', $jastiper->id)->where('status', 'selesai')->sum('agreed_fare');

        return view('jastiper.history', compact(
            'jastiper',
            'orders',
            'tab',
            'totalCompleted',
            'totalCancelled',
            'totalEarned'
        ));
    }

    /**
     * Halaman Profil Jastiper.
     */
    public function jastiperProfile()
    {
        $jastiper = Auth::guard('jastiper')->user();
        $jastiper->load(['wilayah', 'latestVerification']);

        $completedOrdersCount = Order::where('jastiper_id', $jastiper->id)->where('status', 'selesai')->count();
        $totalOrdersCount = Order::where('jastiper_id', $jastiper->id)
            ->whereNotIn('status', ['menunggu_tawaran', 'menunggu_pembayaran'])
            ->count();
        $completionRate = $totalOrdersCount > 0 ? round(($completedOrdersCount / $totalOrdersCount) * 100) : 100;

        $ratingAvg = $jastiper->badge?->avg_rating;
        if (!$ratingAvg) {
            $ratingAvg = $jastiper->ratings()->avg('rating');
        }
        $ratingAvg = $ratingAvg ? round((float) $ratingAvg, 1) : null;

        return view('jastiper.profile', compact(
            'jastiper',
            'completedOrdersCount',
            'completionRate',
            'ratingAvg'
        ));
    }

    /**
     * Halaman Buat Request Baru oleh Customer.
     */
    public function customerCreateOrder()
    {
        $customer = Auth::guard('customer')->user();
        return view('dashboard.customer.create_order', compact('customer'));
    }

    /**
     * Halaman Verifikasi Akun untuk Jastiper.
     */
    public function jastiperVerification()
    {
        $jastiper = Auth::guard('jastiper')->user();
        $jastiper->load('latestVerification');
        return view('jastiper.verification', compact('jastiper'));
    }

    /**
     * Action Admin untuk memproses status verifikasi (Approve/Reject)
     */
    public function adminVerificationUpdate(Request $request, $id)
    {
        $verification = JastiperVerification::findOrFail($id);

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string',
        ]);

        $verification->status = $request->status;
        $verification->rejection_reason = $request->status === 'rejected' ? $request->rejection_reason : null;
        $verification->reviewed_by = \App\Models\Admin::first()?->id ?? 1;
        $verification->reviewed_at = now();
        $verification->save();

        // Update Jastiper verification status
        $jastiper = $verification->jastiper;
        $jastiper->verification_status = $request->status;
        $jastiper->save();

        // Send notification via WhatsApp
        if ($request->status === 'approved') {
            $msg = "Halo *{$jastiper->name}*!\n\nPengajuan verifikasi akun Jastiper Anda di *JastipKuy* telah *DISETUJUI* oleh Admin. Akun Anda kini aktif dan Anda siap untuk menerima tawaran titipan belanjaan! 🚀";
        } else {
            $msg = "Halo *{$jastiper->name}*!\n\nPengajuan verifikasi akun Jastiper Anda di *JastipKuy* ditolak oleh Admin dengan alasan:\n\n_\"{$request->rejection_reason}\"_\n\nSilakan masuk kembali ke Dashboard Jastiper dan ajukan ulang dengan dokumen/foto yang lebih jelas. Terima kasih.";
        }

        WhatsAppService::sendMessage($jastiper->phone_number, $msg);

        return redirect()->back()->with('success', 'Status verifikasi berhasil diperbarui dan notifikasi WhatsApp telah dikirim!');
    }

    /**
     * Halaman pilih wilayah & radius operasional untuk Jastiper.
     */
    public function jastiperArea()
    {
        $jastiper = Auth::guard('jastiper')->user();
        return view('jastiper.area', compact('jastiper'));
    }

    /**
     * Halaman Utama Dashboard Admin.
     */
    /**
     * Halaman Utama Dashboard Analytics Admin.
     */
    public function adminDashboard(Request $request)
    {
        // 1. Date filter (default: today)
        $dateFilter = $request->query('date_range', 'today'); // 'today', 'yesterday', 'this_week', 'this_month', 'all'
        $startDate = null;
        $endDate = null;

        $now = now();
        if ($dateFilter === 'yesterday') {
            $startDate = now()->yesterday()->startOfDay();
            $endDate = now()->yesterday()->endOfDay();
        } elseif ($dateFilter === 'this_week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } elseif ($dateFilter === 'this_month') {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        } elseif ($dateFilter === 'all') {
            $startDate = null;
            $endDate = null;
        } else {
            // Default 'today'
            $dateFilter = 'today';
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        }

        // Base order query with date filter
        $orderQuery = \App\Models\Order::query();
        if ($startDate && $endDate) {
            $orderQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Summary Counts
        $totalOrdersFiltered = (clone $orderQuery)->count();
        $activeOrdersCount = \App\Models\Order::whereNotIn('status', ['selesai', 'dibatalkan', 'bermasalah'])->count();
        $completedOrdersCount = (clone $orderQuery)->where('status', 'selesai')->count();
        $cancelledOrdersCount = (clone $orderQuery)->whereIn('status', ['dibatalkan', 'bermasalah'])->count();

        // All time stats for comparison
        $totalOrdersAllTime = \App\Models\Order::count();
        $totalCompletedAllTime = \App\Models\Order::where('status', 'selesai')->count();
        $totalNetRevenueAllTime = \App\Models\Komisi::sum('commission_amount');

        // 2. Active Driver / Jastiper Analytics
        $activeDriversQuery = \App\Models\Jastiper::where('verification_status', 'approved')
            ->where(function ($q) {
                $q->where('is_available', true)
                  ->orWhere('work_status', '!=', 'offline')
                  ->orWhereNotNull('checkin_location');
            })
            ->with(['wilayah']);

        $activeDriversList = (clone $activeDriversQuery)
            ->withCount(['orders as current_orders_count' => function ($q) {
                $q->whereNotIn('status', ['selesai', 'dibatalkan', 'bermasalah']);
            }])
            ->latest('updated_at')
            ->get();

        $totalActiveDriversCount = $activeDriversList->count();
        $totalOnlineTersedia = $activeDriversList->where('work_status', 'tersedia')->count();
        $totalOnlineStandby = $activeDriversList->where('work_status', 'standby')->count();

        // 3. Service / Category breakdown
        $serviceBreakdownRaw = (clone $orderQuery)
            ->select('category', DB::raw('count(*) as count'), DB::raw('SUM(COALESCE(agreed_fare, estimated_fare)) as total_val'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        $allCategories = [
            'beli-antar' => 'Beli & Antar (Belanja)',
            'ambil-antar' => 'Ambil & Antar (Titip)',
            'toko-kirim' => 'Toko / Katalog Partner',
            'dokumen' => 'Dokumen Penting',
            'multi-stop' => 'Multi-Stop (Beberapa Titik)',
            'kirim-pihak-ketiga' => 'Ekspedisi Pihak Ketiga',
        ];

        $serviceBreakdown = [];
        foreach ($allCategories as $catKey => $catLabel) {
            $found = $serviceBreakdownRaw->firstWhere('category', $catKey);
            $serviceBreakdown[] = [
                'key' => $catKey,
                'label' => $catLabel,
                'count' => $found ? $found->count : 0,
                'total_val' => $found ? (float)$found->total_val : 0,
            ];
        }

        // Sort by count desc
        usort($serviceBreakdown, fn($a, $b) => $b['count'] <=> $a['count']);
        $topService = $serviceBreakdown[0] ?? null;

        // 4. Financial Analytics (Kas Masuk, Kas Keluar/Pengeluaran termasuk Refund, Keuntungan Bersih)
        $paymentInQuery = \App\Models\Payment::where('status', 'lunas');
        $topupInQuery = \App\Models\Topup::where('status', 'berhasil');
        $withdrawOutQuery = \App\Models\WithdrawRequest::whereIn('status', ['disetujui', 'approved']);
        $refundQuery = \App\Models\WalletTransaction::where('source', 'like', 'refund%');
        $komisiQuery = \App\Models\Komisi::query();

        if ($startDate && $endDate) {
            $paymentInQuery->whereBetween('created_at', [$startDate, $endDate]);
            $topupInQuery->whereBetween('created_at', [$startDate, $endDate]);
            $withdrawOutQuery->whereBetween('processed_at', [$startDate, $endDate]);
            $refundQuery->whereBetween('created_at', [$startDate, $endDate]);
            $komisiQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // 4a. Kas Masuk (Order Payment + Topup)
        $cashInPayments = (float) $paymentInQuery->sum('amount');
        $cashInTopups = (float) $topupInQuery->sum('amount');
        $totalCashIn = $cashInPayments + $cashInTopups;

        // 4b. Kas Keluar (Pengeluaran: Withdraw Jastiper + Refund Customer)
        $cashOutWithdraws = (float) $withdrawOutQuery->sum('amount');
        $withdrawCount = (int) $withdrawOutQuery->count();
        $totalRefund = (float) $refundQuery->sum('amount');
        $refundCount = (int) $refundQuery->count();
        $totalCashOut = $cashOutWithdraws + $totalRefund;

        // 4c. Keuntungan Bersih Platform (Laba Komisi Aplikasi + Margin Markup Toko)
        $platformCommissionEarned = (float) $komisiQuery->sum('commission_amount');
        $grossOrderFare = (float) $komisiQuery->sum('gross_amount');
        $itemMarkupProfit = (float) (clone $orderQuery)->where('status', 'selesai')->sum('merchant_markup_profit');
        $totalPlatformProfit = $platformCommissionEarned + $itemMarkupProfit;
        $netCashFlow = $totalCashIn - $totalCashOut;

        // 4d. Dana Mengendap (Escrow Titipan Saldo User)
        $customerEscrowBalance = (float) \App\Models\Wallet::where('owner_role', 'customer')->sum('balance');
        $jastiperPendingBalance = (float) \App\Models\Wallet::where('owner_role', 'jastiper')->sum('balance');
        $totalEscrowBalance = $customerEscrowBalance + $jastiperPendingBalance;

        // 5. Hourly Orders Distribution (for Today chart)
        $hourlyDistribution = array_fill(0, 24, 0);
        $hourlyQuery = (clone $orderQuery)->get();
        foreach ($hourlyQuery as $ord) {
            $hour = (int)$ord->created_at->format('G');
            if (isset($hourlyDistribution[$hour])) {
                $hourlyDistribution[$hour]++;
            }
        }

        // 6. Interactive Orders Table with Search & Status Filter
        $tableQuery = \App\Models\Order::with(['customer', 'jastiper', 'wilayah', 'reports'])
            ->latest();

        if ($request->filled('status_filter') && $request->status_filter !== 'all') {
            if ($request->status_filter === 'aktif') {
                $tableQuery->whereNotIn('status', ['selesai', 'dibatalkan', 'bermasalah']);
            } else {
                $tableQuery->where('status', $request->status_filter);
            }
        }

        if ($request->filled('category_filter') && $request->category_filter !== 'all') {
            $tableQuery->where('category', $request->category_filter);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $tableQuery->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('jastiper', function ($jq) use ($search) {
                      $jq->where('name', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        $latestOrders = $tableQuery->paginate(15)->appends($request->query());

        // 7. Master Data Mutasi Keseluruhan (Ringkasan Eksekutif Cepat)
        $mutationTotalCount = \App\Models\WalletTransaction::count();
        $mutationTotalKredit = (float) \App\Models\WalletTransaction::where('type', 'kredit')->sum('amount');
        $mutationTotalDebit = (float) \App\Models\WalletTransaction::where('type', 'debit')->sum('amount');
        $mutationNet = $mutationTotalKredit - $mutationTotalDebit;

        // 8. Ringkasan Kas & Pool Proteksi Asuransi Platform
        $insurancePool = app(\App\Services\InsuranceService::class)->getPoolSummary($startDate, $endDate);

        return view('admin.dashboard', compact(
            'dateFilter',
            'totalOrdersFiltered',
            'activeOrdersCount',
            'completedOrdersCount',
            'cancelledOrdersCount',
            'totalOrdersAllTime',
            'totalCompletedAllTime',
            'totalNetRevenueAllTime',
            'activeDriversList',
            'totalActiveDriversCount',
            'totalOnlineTersedia',
            'totalOnlineStandby',
            'serviceBreakdown',
            'topService',
            'totalCashIn',
            'cashInPayments',
            'cashInTopups',
            'totalCashOut',
            'cashOutWithdraws',
            'withdrawCount',
            'totalRefund',
            'refundCount',
            'platformCommissionEarned',
            'itemMarkupProfit',
            'totalPlatformProfit',
            'grossOrderFare',
            'netCashFlow',
            'customerEscrowBalance',
            'jastiperPendingBalance',
            'totalEscrowBalance',
            'hourlyDistribution',
            'latestOrders',
            'mutationTotalCount',
            'mutationTotalKredit',
            'mutationTotalDebit',
            'mutationNet',
            'insurancePool'
        ));
    }

    /**
     * Halaman dashboard antrian verifikasi Admin.
     */
    public function adminVerification()
    {
        return view('admin.verification');
    }

    /**
     * Halaman Booking Favorit & Lihat Checkin List untuk Customer.
     */
    public function customerBookingView()
    {
        $customer = Auth::guard('customer')->user();

        // Ambil jastiper yang sedang check-in aktif dan online (is_available = true)
        $checkinJastipers = \App\Models\Jastiper::whereNotNull('checkin_location')
            ->where('is_available', true)
            ->where('verification_status', 'approved')
            ->with('badge')
            ->get();

        // Ambil jastiper favorit milik customer
        $favoriteJastipers = $customer->favorites()->with('badge')->get();
        $favorites = $customer->favorites()->pluck('jastiper_id');

        // Ambil jastiper dari riwayat pesanan selesai
        $historyJastiperIds = \App\Models\Order::where('customer_id', $customer->id)
            ->where('status', 'selesai')
            ->whereNotNull('jastiper_id')
            ->distinct()
            ->pluck('jastiper_id');

        $historyJastipers = \App\Models\Jastiper::whereIn('id', $historyJastiperIds)
            ->whereNotIn('id', $favorites) // Hindari duplikasi jika sudah di favorit
            ->with('badge')
            ->get();

        return view('dashboard.customer.booking', compact('customer', 'checkinJastipers', 'favoriteJastipers', 'historyJastipers', 'favorites'));
    }

    /**
     * Toggle status favorit jastiper oleh customer.
     */
    public function customerToggleFavorite($id)
    {
        $customer = Auth::guard('customer')->user();
        
        // Cek apakah sudah difavoritkan
        $exists = \App\Models\CustomerFavorite::where('customer_id', $customer->id)
            ->where('jastiper_id', $id)
            ->first();

        if ($exists) {
            $exists->delete();
            $msg = 'Jastiper berhasil dihapus dari daftar favorit Anda.';
        } else {
            \App\Models\CustomerFavorite::create([
                'customer_id' => $customer->id,
                'jastiper_id' => $id,
            ]);
            $msg = 'Jastiper berhasil ditambahkan ke daftar favorit Anda! ❤️';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Endpoint API cek ketersediaan real-time jastiper favorit.
     */
    public function customerJastiperAvailability($id)
    {
        $jastiper = \App\Models\Jastiper::findOrFail($id);

        return response()->json([
            'id' => $jastiper->id,
            'name' => $jastiper->name,
            'is_available' => (bool)$jastiper->is_available,
            'checkin_location' => $jastiper->checkin_location,
            'checked_in_at' => $jastiper->checked_in_at ? $jastiper->checked_in_at->diffForHumans() : null,
        ]);
    }

    /**
     * Action Jastiper untuk melakukan check-in / check-out.
     */
    public function jastiperCheckin(\Illuminate\Http\Request $request)
    {
        $jastiper = Auth::guard('jastiper')->user();

        $request->validate([
            'action' => 'required|in:checkin,checkout',
            'location_name_select' => 'required_if:action,checkin|nullable|string|max:255',
            'location_name_custom' => 'required_if:location_name_select,custom|nullable|string|max:255',
        ]);

        if ($request->action === 'checkout') {
            $jastiper->update([
                'checkin_location' => null,
                'checked_in_at' => null,
                'is_available' => true,
            ]);
            $msg = 'Anda berhasil check-out. Status ketersediaan Anda diatur ke Siap Menerima Order.';
        } else {
            $locationName = $request->location_name_select === 'custom'
                ? $request->location_name_custom
                : $request->location_name_select;

            $updateData = [
                'checkin_location' => $locationName,
                'checked_in_at' => now(),
                'is_available' => true,
            ];

            // Jika koordinat dikirim via input lat/lng atau GPS
            if ($request->filled('lat') && $request->filled('lng')) {
                $updateData['current_lat'] = (float) $request->input('lat');
                $updateData['current_lng'] = (float) $request->input('lng');
            } else {
                // Resolusi koordinat cerdas agar peta Jastiper & radar Customer langsung berpindah
                $locLower = strtolower($locationName);
                $knownCoords = [
                    'mie gacoan lowokwaru' => [-7.9445, 112.6180],
                    'starbucks ijen' => [-7.9735, 112.6230],
                    'matos' => [-7.9570, 112.6185],
                    'malang town square' => [-7.9570, 112.6185],
                    'pasar besar malang' => [-7.9865, 112.6325],
                    'bakso bakar pak man' => [-7.9620, 112.6280],
                    'lippo plaza' => [-7.4478, 112.7183],
                    'alun-alun sidoarjo' => [-7.4528, 112.7175],
                    'tunjungan plaza' => [-7.2625, 112.7380],
                ];

                $matched = false;
                foreach ($knownCoords as $k => $c) {
                    if (str_contains($locLower, $k)) {
                        $updateData['current_lat'] = $c[0];
                        $updateData['current_lng'] = $c[1];
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    if (str_contains($locLower, 'wiyung')) {
                        $updateData['current_lat'] = -7.3110;
                        $updateData['current_lng'] = 112.6980;
                    } elseif (str_contains($locLower, 'wonoayu')) {
                        $updateData['current_lat'] = -7.4345;
                        $updateData['current_lng'] = 112.6178;
                    } elseif (str_contains($locLower, 'sedengan') || str_contains($locLower, 'krian')) {
                        $updateData['current_lat'] = -7.4120;
                        $updateData['current_lng'] = 112.5830;
                    } elseif (str_contains($locLower, 'sidoarjo')) {
                        $updateData['current_lat'] = -7.4478;
                        $updateData['current_lng'] = 112.7183;
                    } elseif (str_contains($locLower, 'rungkut')) {
                        $updateData['current_lat'] = -7.3180;
                        $updateData['current_lng'] = 112.7820;
                    } elseif (str_contains($locLower, 'surabaya')) {
                        $updateData['current_lat'] = -7.2575;
                        $updateData['current_lng'] = 112.7521;
                    }
                }
            }

            if (!empty($updateData['current_lat']) && !empty($updateData['current_lng'])) {
                $nearestWilayah = \App\Models\Wilayah::findNearest((float) $updateData['current_lat'], (float) $updateData['current_lng']);
                if ($nearestWilayah) {
                    $updateData['wilayah_id'] = $nearestWilayah->id;
                }
            }

            $jastiper->update($updateData);
            $msg = "Anda berhasil check-in di {$locationName}! Posisi Anda di peta telah diperbarui dan Customer kini dapat membooking Anda secara langsung.";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Aksi terima order booking langsung dari sisi jastiper.
     */
    public function jastiperDirectAccept($id)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = \App\Models\Order::findOrFail($id);

        if ($order->jastiper_id !== $jastiper->id) {
            return redirect()->back()->with('error', 'Pesanan ini bukan ditujukan langsung untuk Anda.');
        }

        if ($order->status !== 'menunggu_tawaran') {
            return redirect()->back()->with('error', 'Status pesanan ini sudah berubah.');
        }

        // Jalur Booking Langsung: deal terbentuk (jastiper_id + agreed_fare terkunci).
        // SEJAK FITUR PEMBAYARAN WAJIB (lihat BRIEF-fitur-pembayaran.md §1.1), order
        // TIDAK LAGI langsung lanjut ke "diproses" di sini — order berhenti dulu di
        // "menunggu_pembayaran" sampai customer menyelesaikan pembayaran (transfer VA/QRIS
        // atau saldo wallet). Transisi ke "diproses" baru terjadi lewat
        // PaymentService::payWithWallet() / handleWebhook() / adminVerifyManualProof().
        $dealService = app(OrderDealService::class);
        $order = $dealService->formDeal($order, $jastiper, (float) $order->estimated_fare, 'direct');
        app(\App\Services\PaymentService::class)->initiate($order);

        // Notifikasi WA lama dihapus di sini — sejak Tahap 3, formDeal() di atas
        // sudah otomatis mengirim pesan pembuka chat via
        // ChatService::createDealRoomOpeningMessage(). Customer & jastiper akan
        // lihat pesan itu di chat room order ini begitu deal terbentuk.

        return redirect()->route('jastiper.dashboard')->with('success', 'Booking diterima! Menunggu customer menyelesaikan pembayaran sebelum Anda bisa mulai memproses.');
    }

    /**
     * Aksi tolak order booking langsung dari sisi jastiper (dikembalikan ke lelang umum).
     */
    public function jastiperDirectReject($id)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = \App\Models\Order::findOrFail($id);

        if ($order->jastiper_id !== $jastiper->id) {
            return redirect()->back()->with('error', 'Pesanan ini bukan ditujukan langsung untuk Anda.');
        }

        if ($order->status !== 'menunggu_tawaran') {
            return redirect()->back()->with('error', 'Status pesanan ini sudah berubah.');
        }

        // Kembalikan ke lelang umum dengan menghapus jastiper_id
        $order->update([
            'jastiper_id' => null,
            'status' => 'menunggu_tawaran',
        ]);

        // Simulasikan pesan WhatsApp ke Customer bahwa jastiper menolak tapi order dilempar ke umum
        $customer = $order->customer;
        $msg = "Halo *{$customer->name}*!\n\nJastiper favorit Anda *{$jastiper->name}* saat ini sedang sibuk dan terpaksa melewatkan booking Anda. Jangan khawatir, request Anda kini dialihkan ke *Tawaran Terbuka* agar bisa diambil oleh Jastiper aktif lainnya! 🚀";
        WhatsAppService::sendMessage($customer->phone_number, $msg);

        return redirect()->route('jastiper.dashboard')->with('success', 'Booking langsung ditolak, pesanan dialihkan ke tawaran terbuka.');
    }

    /**
     * Mengubah status kerja Jastiper (Online / Offline).
     */
    public function jastiperToggleStatus()
    {
        $jastiper = Auth::guard('jastiper')->user();

        $newStatus = !$jastiper->is_available;

        $updateData = [
            'is_available' => $newStatus,
        ];

        // Jika mengubah status ke Offline, otomatis check-out dari lokasi check-in
        if (!$newStatus) {
            $updateData['checkin_location'] = null;
            $updateData['checked_in_at'] = null;
        }

        $jastiper->update($updateData);

        $msg = $newStatus 
            ? 'Status Anda sekarang ONLINE. Siap menerima permintaan order belanjaan!' 
            : 'Status Anda sekarang OFFLINE. Anda tidak akan menerima permintaan order belanjaan.';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * ================================================================
     * ENDPOINT BARU — Bagian 3: Halaman Feed Request (sisi Jastiper)
     * ================================================================
     */

    /**
     * Mengubah status kerja Jastiper ke salah satu dari 3 state eksplisit:
     * tersedia | standby | offline.
     *
     * Dipanggil lewat fetch() dari UI toggle 3-state (bukan cuma switch on/off biasa).
     * Mendukung dua mode respons: JSON (kalau dipanggil via fetch/AJAX) atau redirect
     * back (kalau suatu saat dipakai lewat form submit biasa/no-JS fallback).
     */
    public function jastiperUpdateWorkStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:tersedia,standby,offline',
        ], [
            'status.required' => 'Status kerja wajib dipilih.',
            'status.in' => 'Status kerja tidak valid.',
        ]);

        $jastiper = Auth::guard('jastiper')->user();

        if ($jastiper->verification_status !== 'approved' && $request->status !== 'offline') {
            $errorMsg = 'Akun Anda belum terverifikasi oleh Admin, tidak bisa mengubah status ke online.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 403);
            }

            return redirect()->back()->with('error', $errorMsg);
        }

        // Method setWorkStatus() di model otomatis sinkron kolom is_available lama
        // dan otomatis check-out lokasi kalau statusnya diubah ke offline.
        $jastiper->setWorkStatus($request->status);

        $labels = [
            'tersedia' => 'TERSEDIA — Anda siap menerima order belanjaan baru!',
            'standby' => 'STANDBY — Anda online tapi sementara tidak menerima order baru.',
            'offline' => 'OFFLINE — Anda tidak akan menerima order apapun.',
        ];
        $msg = $labels[$request->status];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'work_status' => $jastiper->work_status,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Endpoint JSON: ambil daftar request/order aktif dalam radius jangkauan Jastiper,
     * dengan filter kategori opsional. Dipakai untuk render awal feed & untuk polling
     * real-time (dipanggil ulang tiap beberapa detik lewat JS di Bagian 4).
     *
     * Query params:
     *  - category (opsional): salah satu dari enum kategori order, atau 'semua'/kosong = semua kategori
     *
     * GET /jastiper/orders/feed?category=beli-antar
     */
    public function jastiperOrderFeed(Request $request)
    {
        $jastiper = Auth::guard('jastiper')->user();
        \Illuminate\Support\Facades\Log::info('FEED REQUESTED BY CLIENT', [
            'jastiper_id' => $jastiper ? $jastiper->id : 'null',
            'online' => $jastiper ? $jastiper->work_status : 'null',
            'current_lat' => $jastiper ? $jastiper->current_lat : 'null',
            'current_lng' => $jastiper ? $jastiper->current_lng : 'null',
            'radius' => $jastiper ? $jastiper->radius_km : 'null',
        ]);

        if ($jastiper->verification_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum terverifikasi oleh Admin.',
                'orders' => [],
                'count' => 0,
            ], 403);
        }

        // Kalau statusnya bukan "tersedia" (standby/offline), tidak menerima order baru sama sekali
        if (!$jastiper->isReceivingNewOrders()) {
            return response()->json([
                'success' => true,
                'message' => $jastiper->work_status === 'standby'
                    ? 'Anda sedang standby. Ubah status ke Tersedia untuk melihat order baru.'
                    : 'Anda sedang offline. Ubah status ke Tersedia untuk melihat order baru.',
                'orders' => [],
                'count' => 0,
            ]);
        }

        if (is_null($jastiper->current_lat) || is_null($jastiper->current_lng)) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi Anda belum ditentukan. Silakan atur titik lokasi di halaman "Atur Area" terlebih dahulu.',
                'orders' => [],
                'count' => 0,
            ], 422);
        }

        $category = $request->query('category');

        $orders = Order::whereIn('status', ['menunggu_tawaran', 'ada_tawaran'])
            ->whereNull('jastiper_id')
            ->nearby((float) $jastiper->current_lat, (float) $jastiper->current_lng, (float) $jastiper->radius_km)
            ->categoryIs($category)
            ->with(['customer:id,name,phone_number', 'merchant', 'items.product.merchant'])
            ->with(['offers' => function ($q) use ($jastiper) {
                $q->where('jastiper_id', $jastiper->id)->where('status', 'pending');
            }])
            ->limit(50)
            ->get();

        $categoryNames = [
            'beli-antar' => 'Titip Kuliner',
            'ambil-antar' => 'Titip Ambil',
            'toko-kirim' => 'Titip Toko',
            'dokumen' => 'Dokumen Kecil',
            'multi-stop' => 'Multi-Stop',
            'kirim-pihak-ketiga' => 'Titip Ekspedisi',
        ];

        $formatted = $orders->map(function ($order) use ($categoryNames) {
            $myOffer = $order->offers->first();

            $itemsData = $order->items->map(function ($it) use ($order) {
                $price = (float) ($it->actual_price ?? $it->estimated_price ?? 0);
                return [
                    'id' => $it->id,
                    'name' => $it->name,
                    'name_clean' => $it->clean_name,
                    'quantity' => (int) ($it->quantity ?: 1),
                    'price' => $price,
                    'price_formatted' => $price > 0 ? 'Rp ' . number_format($price, 0, ',', '.') : null,
                    'notes' => $it->notes,
                    'merchant_name' => $it->product?->merchant?->name ?? null,
                    'image_url' => $it->image_url ?: $order->reference_photo_url,
                ];
            })->values();

            $merchantName = $order->merchant?->name
                ?? ($order->items->first(fn($it) => $it->product && $it->product->merchant)?->product?->merchant?->name ?? null);

            return [
                'id' => $order->id,
                'category' => $order->category,
                'category_label' => $categoryNames[$order->category] ?? 'Jastip',
                'description' => $order->description,
                'merchant_name' => $merchantName,
                'reference_photo_url' => $order->reference_photo_url,
                'items' => $itemsData,
                'items_count' => $itemsData->count(),
                'weight_category' => $order->weight_category,
                'origin_address' => $order->origin_address,
                'destination_address' => $order->destination_address,
                'recipient_name' => $order->recipient_name,
                'recipient_phone' => $order->recipient_phone,
                'estimated_fare' => (float) $order->estimated_fare,
                'estimated_fare_formatted' => 'Rp ' . number_format((float) $order->estimated_fare, 0, ',', '.'),
                'distance_km' => round((float) $order->distance_km, 2),
                'customer_name' => $order->customer->name ?? '-',
                'created_at' => $order->created_at->diffForHumans(),
                'multi_stops' => $order->multi_stops,
                'courier_agency' => $order->courier_agency,
                'my_offer_price' => $myOffer ? (float) $myOffer->offered_price : null,
                'my_offer_price_formatted' => $myOffer ? 'Rp ' . number_format((float) $myOffer->offered_price, 0, ',', '.') : null,
            ];
        });

        $recentOffers = \App\Models\Offer::where('jastiper_id', $jastiper->id)
            ->whereIn('status', ['accepted', 'rejected'])
            ->where('updated_at', '>=', now()->subMinutes(10))
            ->with('order')
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'order_id' => $offer->order_id,
                    'order_description' => $offer->order->description ?? 'Jastip',
                    'status' => $offer->status,
                    'updated_at' => $offer->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => null,
            'orders' => $formatted,
            'count' => $formatted->count(),
            'radius_km' => (float) $jastiper->radius_km,
            'recent_offers' => $recentOffers,
        ]);
    }

    /**
     * ================================================================
     * ENDPOINT BARU — Halaman Tawaran & Deal (Bidding)
     * ================================================================
     */

    /**
     * Ongkir minimum tawaran yang diperbolehkan (Rp).
     */
    private const MIN_OFFER_PRICE = 5000;

    /**
     * Aksi Jastiper mengajukan/mengubah SATU tawaran harga untuk satu order
     * di feed umum (order tanpa jastiper_id, alias bukan direct booking).
     *
     * Body: { "offered_price": 12000 }
     * POST /jastiper/orders/{id}/offer
     */
    public function jastiperSubmitOffer(Request $request, $id)
    {
        $jastiper = Auth::guard('jastiper')->user();

        if ($jastiper->verification_status !== 'approved') {
            $msg = 'Akun Anda belum terverifikasi oleh Admin.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 403)
                : redirect()->back()->with('error', $msg);
        }

        $request->validate([
            'offered_price' => 'required|numeric|min:' . self::MIN_OFFER_PRICE,
        ], [
            'offered_price.required' => 'Harga tawaran wajib diisi.',
            'offered_price.numeric' => 'Harga tawaran harus berupa angka.',
            'offered_price.min' => 'Harga tawaran minimal Rp ' . number_format(self::MIN_OFFER_PRICE, 0, ',', '.') . '.',
        ]);

        $result = DB::transaction(function () use ($request, $id, $jastiper) {
            $order = Order::where('id', $id)->lockForUpdate()->first();

            if (!$order) {
                return ['success' => false, 'message' => 'Order tidak ditemukan.'];
            }

            // Order ini harus order feed umum (belum dipasangkan jastiper) dan masih
            // membuka kesempatan tawaran (menunggu_tawaran = belum ada tawaran sama sekali,
            // ada_tawaran = sudah ada tawaran dari jastiper lain tapi belum dipilih customer).
            if (!in_array($order->status, ['menunggu_tawaran', 'ada_tawaran']) || !is_null($order->jastiper_id)) {
                return ['success' => false, 'message' => 'Orderan ini sudah tidak menerima tawaran baru (sudah deal/dibatalkan atau merupakan booking langsung).'];
            }

            $offer = \App\Models\Offer::updateOrCreate(
                ['order_id' => $order->id, 'jastiper_id' => $jastiper->id],
                ['offered_price' => $request->offered_price, 'status' => 'pending']
            );

            if ($order->status !== 'ada_tawaran') {
                $order->update(['status' => 'ada_tawaran']);
            }

            return ['success' => true, 'order' => $order, 'offer' => $offer];
        });

        if (!$result['success']) {
            return $request->wantsJson()
                ? response()->json($result, 409)
                : redirect()->back()->with('error', $result['message']);
        }

        // Notifikasi WhatsApp ke Customer (di luar transaction, tidak perlu ikut rollback)
        $order = $result['order'];
        $customer = $order->customer;
        if ($customer) {
            $hargaFormatted = 'Rp ' . number_format((float) $request->offered_price, 0, ',', '.');
            $msg = "Halo *{$customer->name}*, ada tawaran baru dari Jastiper *{$jastiper->name}* sebesar *{$hargaFormatted}* untuk belanjaan \"{$order->description}\". Cek dashboard Anda! 📲";
            WhatsAppService::sendMessage($customer->phone_number, $msg);
        }

        $msg = 'Tawaran berhasil dikirim! Menunggu keputusan customer.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'offer_id' => $result['offer']->id])
            : redirect()->back()->with('success', $msg);
    }

    /**
     * Aksi Jastiper mengajukan tawaran BANYAK order sekaligus dalam satu klik,
     * masing-masing di harga default (estimated_fare milik order tersebut).
     * Tiap order tetap 1 pembayar sendiri-sendiri (tidak digabung/split bill) — ini murni
     * efisiensi supaya jastiper tidak perlu isi harga satu-satu untuk order yang searah.
     *
     * Body JSON: { "order_ids": [12, 15, 20] }
     * POST /jastiper/orders/multi-offer
     */
    public function jastiperMultiSubmitOffer(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|distinct|exists:orders,id',
        ], [
            'order_ids.required' => 'Pilih minimal 1 order untuk ditawar.',
            'order_ids.*.exists' => 'Salah satu order yang dipilih tidak ditemukan.',
        ]);

        $jastiper = Auth::guard('jastiper')->user();

        if ($jastiper->verification_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum terverifikasi oleh Admin.',
            ], 403);
        }

        $submitted = [];
        $failed = [];

        foreach ($request->order_ids as $orderId) {
            // Kunci baris order ini supaya aman dari race condition (2 jastiper klik bersamaan)
            $result = DB::transaction(function () use ($orderId, $jastiper) {
                $order = Order::where('id', $orderId)->lockForUpdate()->first();

                if (!$order) {
                    return ['status' => 'failed', 'id' => $orderId, 'reason' => 'Order tidak ditemukan.'];
                }

                if (!in_array($order->status, ['menunggu_tawaran', 'ada_tawaran']) || !is_null($order->jastiper_id)) {
                    return ['status' => 'failed', 'id' => $orderId, 'reason' => 'Sudah tidak menerima tawaran (deal/dibatalkan/booking langsung).'];
                }

                $offer = \App\Models\Offer::updateOrCreate(
                    ['order_id' => $order->id, 'jastiper_id' => $jastiper->id],
                    ['offered_price' => $order->estimated_fare, 'status' => 'pending']
                );

                if ($order->status !== 'ada_tawaran') {
                    $order->update(['status' => 'ada_tawaran']);
                }

                return ['status' => 'submitted', 'id' => $orderId, 'order' => $order, 'offer' => $offer];
            });

            if ($result['status'] === 'submitted') {
                $submitted[] = $result['id'];

                // Notifikasi WhatsApp ke customer per order yang berhasil ditawar
                $order = $result['order'];
                $customer = $order->customer;
                if ($customer) {
                    $hargaFormatted = 'Rp ' . number_format((float) $result['offer']->offered_price, 0, ',', '.');
                    $msg = "Halo *{$customer->name}*, ada tawaran baru dari Jastiper *{$jastiper->name}* sebesar *{$hargaFormatted}* untuk belanjaan \"{$order->description}\" (dikirim sekaligus bersama beberapa order lain dalam satu rute). Cek dashboard Anda! 📲";
                    WhatsAppService::sendMessage($customer->phone_number, $msg);
                }
            } else {
                $failed[] = ['id' => $result['id'], 'reason' => $result['reason']];
            }
        }

        return response()->json([
            'success' => count($submitted) > 0,
            'submitted_count' => count($submitted),
            'submitted_ids' => $submitted,
            'failed' => $failed,
            'message' => count($failed) > 0
                ? count($submitted) . ' tawaran berhasil dikirim, ' . count($failed) . ' order gagal ditawar (sudah tidak tersedia).'
                : count($submitted) . ' tawaran berhasil dikirim sekaligus!',
        ]);
    }

    /**
     * Aksi Customer memilih SATU tawaran untuk dijadikan deal.
     * $id di sini adalah ID Offer (bukan ID Order).
     *
     * POST /customer/offers/{id}/accept
     */
    public function customerAcceptOffer(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();

        $result = DB::transaction(function () use ($id, $customer) {
            $offer = \App\Models\Offer::with('jastiper')->where('id', $id)->lockForUpdate()->first();

            if (!$offer) {
                return ['success' => false, 'message' => 'Tawaran tidak ditemukan.'];
            }

            $order = Order::where('id', $offer->order_id)->lockForUpdate()->first();

            if (!$order || $order->customer_id !== $customer->id) {
                return ['success' => false, 'message' => 'Order tidak ditemukan atau bukan milik Anda.'];
            }

            if (!in_array($order->status, ['menunggu_tawaran', 'ada_tawaran'])) {
                return ['success' => false, 'message' => 'Order ini sudah tidak bisa memilih tawaran (sudah deal/dibatalkan).'];
            }

            if ($offer->status !== 'pending') {
                return ['success' => false, 'message' => 'Tawaran ini sudah tidak berlaku.'];
            }

            // Ambil seluruh tawaran lain (pending) untuk order ini SEBELUM diubah,
            // supaya bisa dipakai kirim notifikasi penolakan setelah transaction commit.
            $otherOffers = \App\Models\Offer::with('jastiper')
                ->where('order_id', $order->id)
                ->where('id', '!=', $offer->id)
                ->where('status', 'pending')
                ->get();

            $offer->update(['status' => 'accepted']);

            \App\Models\Offer::where('order_id', $order->id)
                ->where('id', '!=', $offer->id)
                ->update(['status' => 'rejected']);

            // Jalur Bidding: deal terbentuk (jastiper_id + agreed_fare terkunci).
            $dealService = app(OrderDealService::class);

            if ($order->is_catalog_order) {
                // Jika pesanan dari katalog, DP harus disesuaikan dengan ongkir baru (agreed_fare)
                // DP = Harga Barang * 50% + agreed_fare
                // Karena $order->downpayment_amount yang lama adalah = Harga Barang * 50% + estimated_fare
                // Kita cukup tambahkan selisihnya
                $fareDifference = $offer->offered_price - $order->estimated_fare;
                $newDpAmount = $order->downpayment_amount + $fareDifference;
                $order->update(['downpayment_amount' => $newDpAmount]);
            }

            $order = $dealService->formDeal($order, $offer->jastiper, (float) $offer->offered_price, 'bidding');

            // SEJAK FITUR PEMBAYARAN WAJIB (lihat BRIEF-fitur-pembayaran.md §1.1), order
            // tidak lagi berhenti di status 'deal' menunggu jastiper tap "Mulai Proses" —
            // order langsung lanjut ke 'menunggu_pembayaran' di sini lewat
            // PaymentService::initiate(), sama seperti jalur Booking Langsung. Jastiper baru
            // bisa mulai memproses setelah pembayaran customer terkonfirmasi lunas.
            app(\App\Services\PaymentService::class)->initiate($order);

            return [
                'success' => true,
                'order' => $order,
                'offer' => $offer,
                'other_offers' => $otherOffers,
            ];
        });

        if (!$result['success']) {
            return $request->wantsJson()
                ? response()->json($result, 409)
                : redirect()->back()->with('error', $result['message']);
        }

        $order = $result['order'];
        $offer = $result['offer'];
        $jastiperTerpilih = $offer->jastiper;
        $customer = $order->customer;

        // Notifikasi ke jastiper yang terpilih: WA lama dihapus — sejak Tahap 3,
        // formDeal() di dalam DB::transaction() di atas sudah otomatis mengirim
        // pesan pembuka chat via ChatService::createDealRoomOpeningMessage().

        // Notifikasi ke jastiper lain yang tawarannya ditolak (TETAP WhatsApp —
        // jastiper ini tidak jadi bagian dari deal, tidak valid punya chat room
        // untuk order ini)
        foreach ($result['other_offers'] as $rejected) {
            if ($rejected->jastiper) {
                $msg = "Terima kasih telah berpartisipasi. Sayangnya tawaran Anda untuk belanjaan \"{$order->description}\" belum terpilih kali ini. Tetap semangat pantau orderan lainnya! 💪";
                WhatsAppService::sendMessage($rejected->jastiper->phone_number, $msg);
            }
        }

        $msg = "Deal! Jastiper {$jastiperTerpilih?->name} akan segera memproses belanjaan Anda.";

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->back()->with('success', $msg);
    }

    /**
     * Aksi Customer memperluas radius pencarian jastiper (saat timeout tidak ada tawaran cocok).
     * Implementasi ringan: cukup update timestamp order supaya "naik" kembali ke atas feed
     * jastiper (karena feed di-order oleh jarak, bukan waktu — tapi update ini menjaga
     * order tetap dianggap "baru" dan memicu jastiper untuk memeriksa ulang feed via polling).
     *
     * POST /customer/orders/{id}/expand-radius
     */
    public function customerExpandOrderRadius(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();
        $order = Order::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$order) {
            $msg = 'Order tidak ditemukan atau bukan milik Anda.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : redirect()->back()->with('error', $msg);
        }

        if (!in_array($order->status, ['menunggu_tawaran', 'ada_tawaran'])) {
            $msg = 'Order ini sudah tidak bisa diperluas jangkauannya.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 409)
                : redirect()->back()->with('error', $msg);
        }

        $order->touch();

        WhatsAppService::sendMessage(
            $customer->phone_number,
            "Pencarian jastiper telah *diperluas* ke radius yang lebih jauh. Mohon tunggu sejenak, tawaran baru akan segera masuk! 🔎"
        );

        $msg = 'Pencarian jastiper berhasil diperluas.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->back()->with('success', $msg);
    }

    /**
     * Aksi Customer membatalkan order (biasanya dipakai saat timeout, belum ada tawaran cocok).
     *
     * POST /customer/orders/{id}/cancel
     */
    public function customerCancelOrder(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();
        $order = Order::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$order) {
            $msg = 'Order tidak ditemukan atau bukan milik Anda.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : redirect()->back()->with('error', $msg);
        }

        if (!in_array($order->status, ['menunggu_tawaran', 'ada_tawaran'])) {
            $msg = 'Order ini sudah tidak bisa dibatalkan (sudah deal/selesai/dibatalkan).';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 409)
                : redirect()->back()->with('error', $msg);
        }

        $order->update([
            'status' => 'dibatalkan',
            'cancelled_by_role' => 'customer',
            'cancelled_by_id' => $customer->id,
        ]);

        // Bersihkan antrean tawaran pending milik jastiper untuk order ini
        \App\Models\Offer::where('order_id', $order->id)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        $msg = 'Pesanan berhasil dibatalkan.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->back()->with('success', $msg);
    }

    /**
     * Aksi Jastiper menandai order terpilih mulai dibelanjakan (status -> diproses).
     *
     * POST /jastiper/orders/{id}/start-process
     */
    public function jastiperStartProcessOrder(Request $request, $id)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = Order::where('id', $id)->where('jastiper_id', $jastiper->id)->first();

        if (!$order) {
            $msg = 'Order tidak ditemukan atau bukan milik Anda.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : redirect()->back()->with('error', $msg);
        }

        // SEJAK FITUR PEMBAYARAN WAJIB (lihat BRIEF-fitur-pembayaran.md §1.1), endpoint
        // ini TIDAK LAGI boleh memicu transisi 'deal' -> 'diproses' secara manual.
        // Sejak refactor Tahap 2, formDeal() di customerAcceptOffer() langsung disusul
        // PaymentService::initiate(), jadi order sudah lompat ke 'menunggu_pembayaran'
        // sebelum sempat mampir ke tombol ini — guard di bawah pada dasarnya defensif
        // untuk kasus tak terduga. Transisi ke 'diproses' sekarang HANYA boleh terjadi
        // lewat pembayaran terkonfirmasi: PaymentService::payWithWallet(),
        // PaymentService::handleWebhook() (settlement Midtrans), atau
        // PaymentService::adminVerifyManualProof() (approve bukti transfer manual).
        $msg = 'Pesanan baru bisa mulai diproses setelah pembayaran customer dikonfirmasi lunas. Endpoint ini tidak lagi memicu perubahan status secara manual.';

        return $request->wantsJson()
            ? response()->json(['success' => false, 'message' => $msg], 409)
            : redirect()->back()->with('error', $msg);
    }

    /**
     * Aksi Jastiper menyelesaikan belanjaan (status -> selesai).
     *
     * POST /jastiper/orders/{id}/complete
     */
    public function jastiperCompleteOrder(Request $request, $id)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = Order::where('id', $id)->where('jastiper_id', $jastiper->id)->first();

        if (!$order) {
            $msg = 'Order tidak ditemukan atau bukan milik Anda.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : redirect()->back()->with('error', $msg);
        }

        if (!in_array($order->status, ['deal', 'diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan', 'diterima'])) {
            $msg = 'Order ini tidak bisa diselesaikan.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 409)
                : redirect()->back()->with('error', $msg);
        }

        // Titik completion disentralisasi ke OrderCompletionService (Sprint 8
        // Bagian 3 §2.3) — method ini yang bertanggung jawab set status
        // 'selesai', potong komisi, kredit wallet jastiper, trigger badge
        // recalc (Bagian 2), dan kirim notifikasi. Jangan lagi
        // $order->update(['status' => 'selesai']) manual di sini.
        app(OrderCompletionService::class)->completeOrder($order, 'jastiper');

        $msg = 'Pesanan berhasil diselesaikan!';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->back()->with('success', $msg);
    }

    /**
     * Endpoint JSON: ambil daftar order aktif milik Customer beserta seluruh tawaran
     * masuk (lengkap dengan reputasi jastiper: rating & jumlah order selesai, serta
     * badge kecepatan respons). Dipakai untuk render awal & polling real-time di
     * dashboard customer (setiap ~6 detik).
     *
     * GET /customer/orders/active-feed
     */
    public function customerActiveOrdersFeed(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::with([
                'jastiper',
                'merchant',
                'items.product.merchant',
                'addons',
                'priceAdjustments' => fn($q) => $q->where('status', 'pending'),
                'payments' => function ($q) {
                    $q->where('status', 'menunggu')->latest('id');
                },
                'offers' => function ($q) {
                    $q->where('status', 'pending')->orderBy('offered_price', 'asc');
                },
                'offers.jastiper' => function ($q) {
                    $q->with('badge')
                      ->withAvg('ratings', 'rating')
                      ->withCount(['orders as completed_orders_count' => function ($q) {
                          $q->where('status', 'selesai');
                      }]);
                },
            ])
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['selesai', 'dibatalkan', 'bermasalah'])
            ->latest()
            ->limit(20)
            ->get();

        $formatted = $orders->map(function ($order) {
            $secondsSinceCreated = $order->created_at->diffInSeconds(now());

            $itemsData = $order->items->map(function ($it) {
                $price = (float) ($it->actual_price ?? $it->estimated_price ?? 0);
                return [
                    'id' => $it->id,
                    'name' => $it->name,
                    'name_clean' => $it->clean_name,
                    'quantity' => (int) ($it->quantity ?: 1),
                    'price' => $price,
                    'price_formatted' => $price > 0 ? 'Rp ' . number_format($price, 0, ',', '.') : null,
                    'notes' => $it->notes,
                    'merchant_name' => $it->product?->merchant?->name ?? null,
                ];
            })->values();

            $merchantName = $order->merchant?->name
                ?? ($order->items->first(fn($it) => $it->product && $it->product->merchant)?->product?->merchant?->name ?? null);

            $pendingAdj = $order->priceAdjustments ? $order->priceAdjustments->first() : null;

            return [
                'id' => $order->id,
                'description' => $order->description,
                'merchant_name' => $merchantName,
                'items' => $itemsData,
                'items_count' => $itemsData->count(),
                'has_pending_adjustment' => $pendingAdj !== null,
                'pending_adjustment' => $pendingAdj ? [
                    'id' => $pendingAdj->id,
                    'item_name' => $pendingAdj->item_name,
                    'proposed_price' => (float) $pendingAdj->proposed_price,
                    'difference' => (float) $pendingAdj->difference,
                    'difference_formatted' => '+Rp ' . number_format($pendingAdj->difference, 0, ',', '.'),
                    'reason' => $pendingAdj->reason,
                ] : null,
                'category' => $order->category,
                'status' => $order->status,
                'estimated_fare' => (float) $order->estimated_fare,
                'estimated_fare_formatted' => 'Rp ' . number_format((float) $order->estimated_fare, 0, ',', '.'),
                'has_uploaded_proof' => $order->payments->first() && $order->payments->first()->proof_image !== null,
                'agreed_fare_formatted' => $order->agreed_fare ? 'Rp ' . number_format((float) $order->agreed_fare, 0, ',', '.') : null,
                'created_at' => $order->created_at->toIso8601String(),
                'seconds_since_created' => $secondsSinceCreated,
                'jastiper' => $order->jastiper ? [
                    'id' => $order->jastiper->id,
                    'name' => $order->jastiper->name,
                    'phone_number' => $order->jastiper->phone_number,
                ] : null,
                'addons' => $order->addons->map(function ($addon) {
                    return [
                        'id' => $addon->id,
                        'description' => $addon->description,
                        'payment_status' => $addon->payment_status,
                        'additional_fare' => (float) $addon->additional_fare,
                        'additional_fare_formatted' => 'Rp ' . number_format((float) $addon->additional_fare, 0, ',', '.'),
                    ];
                })->values(),
                'offers' => $order->offers->map(function ($offer) use ($order) {
                    $responseSeconds = $order->created_at->diffInSeconds($offer->created_at);
                    if ($responseSeconds < 120) {
                        $speedLabel = 'Sangat Cepat';
                        $speedTier = 'fast';
                    } elseif ($responseSeconds <= 300) {
                        $speedLabel = 'Cepat';
                        $speedTier = 'medium';
                    } else {
                        $speedLabel = 'Standar';
                        $speedTier = 'normal';
                    }

                    $ratingAvg = $offer->jastiper?->badge?->avg_rating ?? $offer->jastiper?->ratings_avg_rating;
                    $badgeLevel = $offer->jastiper?->badge?->badge_level ?? 'bronze';

                    return [
                        'offer_id' => $offer->id,
                        'jastiper_id' => $offer->jastiper_id,
                        'jastiper_name' => $offer->jastiper->name ?? '-',
                        'offered_price' => (float) $offer->offered_price,
                        'offered_price_formatted' => 'Rp ' . number_format((float) $offer->offered_price, 0, ',', '.'),
                        'rating_avg' => $ratingAvg ? round((float) $ratingAvg, 1) : null,
                        'completed_orders_count' => $offer->jastiper->completed_orders_count ?? 0,
                        'badge_level' => $badgeLevel,
                        'response_speed_label' => $speedLabel,
                        'response_speed_tier' => $speedTier,
                    ];
                })->values(),
                'multi_stops' => $order->multi_stops ? collect($order->multi_stops)->map(function($s) {
                    return [
                        'stop_number' => $s['stop_number'] ?? 1,
                        'name' => $s['name'] ?? '',
                        'address' => $s['address'] ?? '',
                        'status' => $s['status'] ?? 'pending',
                        'photo' => !empty($s['photo']) ? \Illuminate\Support\Facades\Storage::url($s['photo']) : null,
                        'completed_at' => $s['completed_at'] ?? null,
                    ];
                })->values() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $formatted,
            'count' => $formatted->count(),
        ]);
    }

    /**
     * Aksi Jastiper untuk update status pesanan (progress).
     */
    public function jastiperUpdateOrderStatus(Request $request, $id)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = Order::where('id', $id)->where('jastiper_id', $jastiper->id)->first();

        if (!$order) {
            $errMsg = 'Order tidak ditemukan atau bukan milik Anda.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 404);
            }
            return redirect()->back()->with('error', $errMsg);
        }

        $request->validate([
            'status' => 'required|in:barang_diambil,sedang_diantar,tiba_tujuan',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif,jfif|max:25600',
        ], [
            'photo.file' => 'Berkas foto tidak valid atau gagal terunggah.',
            'photo.mimes' => 'Format foto harus berupa JPG, PNG, WEBP, atau HEIC.',
            'photo.max' => 'Ukuran foto maksimal 25MB.',
        ]);

        $updateData = ['status' => $request->status];
        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = \App\Services\ImageUploadService::store($request->file('photo'), 'order_proofs', 'public');
            if ($request->status === 'barang_diambil') {
                $updateData['pickup_photo'] = $photoPath;
                $updateData['pickup_time'] = now();
            } elseif ($request->status === 'tiba_tujuan') {
                $updateData['delivery_photo'] = $photoPath;
                $updateData['delivery_time'] = now();
            }
        }

        $order->update($updateData);

        $statusLabels = [
            'barang_diambil' => 'Barang Diambil di Toko',
            'sedang_diantar' => 'Mulai Pengantaran ke Tujuan',
            'tiba_tujuan' => 'Tiba di Lokasi Tujuan'
        ];
        $label = $statusLabels[$request->status] ?? $request->status;
        
        $properties = ['status' => $request->status];
        if (!empty($photoPath)) {
            $properties['photo'] = $photoPath;
            $properties['photo_type'] = $request->status === 'barang_diambil' ? 'pickup_photo' : 'delivery_photo';
        }

        \App\Services\ActivityLogger::logOrder(
            $order,
            "status_{$request->status}",
            "Jastiper: {$label}" . (!empty($photoPath) ? " (+Foto Bukti)" : ""),
            "Mitra Jastiper {$jastiper->name} memperbarui status pesanan #{$order->id} menjadi \"{$label}\"" . (!empty($photoPath) ? " dengan melampirkan foto bukti." : ""),
            $jastiper,
            'pengantaran',
            $properties
        );

        $statusMessages = [
            'barang_diambil' => "Jastiper telah mengambil barang pesanan \"{$order->description}\"" . (!empty($photoPath) ? " dan melampirkan foto bukti barang." : "."),
            'sedang_diantar' => "Jastiper sedang mengantar pesanan \"{$order->description}\" ke tujuan.",
            'tiba_tujuan' => "Pesanan \"{$order->description}\" telah tiba di tujuan" . (!empty($photoPath) ? " dengan foto bukti serah terima!" : "!") . " Mohon konfirmasi penerimaan di aplikasi."
        ];
        
        if (isset($statusMessages[$request->status])) {
            \App\Services\WhatsAppService::sendMessage($order->customer->phone_number, $statusMessages[$request->status]);
            app(\App\Services\ChatService::class)->sendSystemMessage($order, $statusMessages[$request->status]);
        }

        $successMsg = "Status pesanan berhasil diperbarui menjadi \"{$label}\"" . (!empty($photoPath) ? " beserta foto bukti." : ".");
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'redirect_url' => route('jastiper.dashboard')
            ]);
        }

        return redirect()->back()->with('success', $successMsg);
    }

    /**
     * Selesaikan belanja satu per satu untuk order multi-stop beserta upload bukti foto per titik.
     */
    public function jastiperCompleteStop(Request $request, $id, $stop_number)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = Order::where('id', $id)->where('jastiper_id', $jastiper->id)->first();

        if (!$order) {
            $errMsg = 'Order tidak ditemukan atau bukan milik Anda.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 404);
            }
            return redirect()->back()->with('error', $errMsg);
        }

        $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,webp,heic,heif,jfif|max:25600',
            'notes' => 'nullable|string|max:500',
        ], [
            'photo.required' => 'Foto bukti pembelian di toko ini wajib diunggah.',
            'photo.file' => 'Berkas foto tidak valid atau gagal terunggah.',
            'photo.mimes' => 'Format foto harus berupa JPG, PNG, WEBP, atau HEIC.',
            'photo.max' => 'Ukuran foto maksimal 25MB.',
        ]);

        $photoPath = \App\Services\ImageUploadService::store($request->file('photo'), 'order_stops', 'public');

        $stops = $order->multi_stops ?: [];
        $stopNumberInt = (int) $stop_number;
        $found = false;
        $completedStopName = "Stop #{$stopNumberInt}";

        foreach ($stops as $idx => $st) {
            $currentStopNum = isset($st['stop_number']) ? (int)$st['stop_number'] : ($idx + 1);
            if ($currentStopNum === $stopNumberInt) {
                $stops[$idx]['status'] = 'completed';
                $stops[$idx]['photo'] = $photoPath;
                $stops[$idx]['completed_at'] = now()->toIso8601String();
                $stops[$idx]['notes_update'] = $request->notes;
                $completedStopName = $st['name'] ?: "Stop #{$stopNumberInt}";
                $found = true;
                break;
            }
        }

        if (!$found) {
            $errMsg = "Titik singgah Stop #{$stopNumberInt} tidak ditemukan pada pesanan ini.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 404);
            }
            return redirect()->back()->with('error', $errMsg);
        }

        $order->multi_stops = $stops;

        // Cek apakah seluruh stop sudah selesai
        $allCompleted = collect($stops)->every(fn($s) => ($s['status'] ?? '') === 'completed');
        $nextPendingStop = collect($stops)->first(fn($s) => ($s['status'] ?? '') !== 'completed');

        if ($allCompleted) {
            $order->status = 'barang_diambil';
            $order->pickup_photo = $photoPath;
            $order->pickup_time = now();
            $order->save();

            \App\Services\ActivityLogger::logOrder(
                $order,
                'status_barang_diambil',
                "Semua Stop Selesai Diambil (+Foto Bukti)",
                "Mitra Jastiper {$jastiper->name} telah menyelesaikan belanja seluruh titik ({$completedStopName}) dan siap mengantar ke alamat customer.",
                $jastiper,
                'pengantaran',
                ['photo' => $photoPath, 'multi_stops_completed' => true]
            );

            $notifMsg = "✅ Seluruh barang pesanan multi-toko #{$order->id} telah selesai diambil jastiper ({$jastiper->name})! Jastiper bersiap mengantar ke lokasi Anda.";
            \App\Services\WhatsAppService::sendMessage($order->customer->phone_number, $notifMsg);
            app(\App\Services\ChatService::class)->sendSystemMessage($order, $notifMsg);

            $successMsg = "Hebat! Semua toko telah selesai diambil. Silakan mulai antar pesanan ke alamat tujuan!";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect_url' => route('jastiper.dashboard')
                ]);
            }

            return redirect()->back()->with('success', $successMsg);
        } else {
            $order->save();
            $nextStopName = $nextPendingStop['name'] ?? ('Stop #' . ($nextPendingStop['stop_number'] ?? ''));

            \App\Services\ActivityLogger::logOrder(
                $order,
                "multi_stop_completed_{$stopNumberInt}",
                "Stop #{$stopNumberInt} Selesai: {$completedStopName}",
                "Mitra Jastiper {$jastiper->name} telah selesai belanja di {$completedStopName} (Stop #{$stopNumberInt}) dan sekarang menuju ke {$nextStopName}.",
                $jastiper,
                'pengantaran',
                ['photo' => $photoPath, 'stop_number' => $stopNumberInt, 'completed_stop' => $completedStopName, 'next_stop' => $nextStopName]
            );

            $notifMsg = "🛍️ Update Jastiper: Barang di {$completedStopName} (Stop #{$stopNumberInt}) selesai diambil! Sekarang jastiper sedang menuju ke {$nextStopName}.";
            \App\Services\WhatsAppService::sendMessage($order->customer->phone_number, $notifMsg);
            app(\App\Services\ChatService::class)->sendSystemMessage($order, $notifMsg);

            $successMsg = "Stop #{$stopNumberInt} ({$completedStopName}) berhasil diselesaikan! Lanjut menuju {$nextStopName}.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect_url' => route('jastiper.dashboard')
                ]);
            }

            return redirect()->back()->with('success', $successMsg);
        }
    }

    /**
     * Aksi Customer untuk mengkonfirmasi barang diterima.
     */
    public function customerConfirmDelivery(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();
        $order = Order::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$order) {
            $msg = 'Order tidak ditemukan atau bukan milik Anda.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : redirect()->back()->with('error', $msg);
        }

        if ($order->status !== 'tiba_tujuan') {
            $msg = 'Order belum bisa dikonfirmasi.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 400)
                : redirect()->back()->with('error', $msg);
        }

        // Titik completion disentralisasi ke OrderCompletionService (Sprint 8
        // Bagian 3 §2.3) — sama seperti jastiperCompleteOrder() di atas,
        // supaya potong komisi & kredit wallet jastiper juga terjadi lewat
        // jalur konfirmasi customer ini.
        app(OrderCompletionService::class)->completeOrder($order, 'customer');

        $msg = 'Terima kasih telah mengkonfirmasi penerimaan barang!';
        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->route('customer.dashboard')->with('success', $msg);
    }
}