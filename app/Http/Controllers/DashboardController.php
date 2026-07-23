<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\JastiperVerification;
use App\Models\Order;
use App\Models\Jastiper;
use App\Services\WhatsAppService;
use App\Services\OrderDealService;

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
        return view('dashboard.customer', compact('customer'));
    }

    /**
     * Dashboard untuk Jastiper.
     */
    public function jastiperDashboard()
    {
        $jastiper = Auth::guard('jastiper')->user();
        // Load relation wilayah to display location
        $jastiper->load(['wilayah', 'latestVerification']);

        // Ambil direct orders khusus untuk Jastiper ini yang belum direspons
        $directOrders = \App\Models\Order::where('status', 'menunggu_tawaran')
            ->where('jastiper_id', $jastiper->id)
            ->latest()
            ->get();

        // Ambil order aktif yang dipegang Jastiper ini (deal, diproses, dst)
        $activeOrders = \App\Models\Order::where('jastiper_id', $jastiper->id)
            ->whereNotIn('status', ['selesai', 'dibatalkan', 'bermasalah'])
            ->with('customer')
            ->latest()
            ->get();

        // Catatan: daftar order umum (feed radius+kategori) SENGAJA tidak lagi diambil
        // di sini. Feed sepenuhnya diambil oleh JavaScript di view lewat endpoint
        // jastiperOrderFeed() (GET /jastiper/orders/feed), baik untuk render pertama
        // kali halaman dibuka maupun untuk polling real-time berikutnya. Ini supaya
        // hanya ada SATU logika query (radius+kategori) yang dipakai, dan tidak ada
        // lagi perbedaan data antara render awal vs hasil polling.
        return view('dashboard.jastiper', compact('jastiper', 'directOrders', 'activeOrders'));
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

            $jastiper->update([
                'checkin_location' => $locationName,
                'checked_in_at' => now(),
                'is_available' => true,
            ]);
            $msg = "Anda berhasil check-in di {$locationName}! Customer kini dapat membooking Anda secara langsung.";
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

        // Jalur Booking Langsung: deal terbentuk (jastiper_id + agreed_fare terkunci),
        // lalu langsung lanjut ke status "diproses" tanpa perlu tap tombol terpisah —
        // mempertahankan perilaku lama (jastiper yang terima booking langsung dianggap
        // otomatis langsung mulai memproses). Lihat app/Services/OrderDealService.php
        // untuk penjelasan kenapa dipecah jadi 2 pemanggilan method.
        $dealService = app(OrderDealService::class);
        $order = $dealService->formDeal($order, $jastiper, (float) $order->estimated_fare, 'direct');
        $order = $dealService->startProcessing($order);

        // Simulasikan pesan WhatsApp ke Customer
        $customer = $order->customer;
        $msg = "Halo *{$customer->name}*!\n\nBooking langsung Anda untuk Jastiper *{$jastiper->name}* (*{$order->description}*) telah *DITERIMA*! Hubungi jastiper di nomor: {$jastiper->phone_number} untuk koordinasi belanjaan. Terima kasih. 🙏";
        WhatsAppService::sendMessage($customer->phone_number, $msg);

        return redirect()->route('jastiper.dashboard')->with('success', 'Booking langsung berhasil diterima! Silakan proses belanjaan.');
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
            ->with('customer:id,name,phone_number')
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

            return [
                'id' => $order->id,
                'category' => $order->category,
                'category_label' => $categoryNames[$order->category] ?? 'Jastip',
                'description' => $order->description,
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
                'my_offer_price' => $myOffer ? (float) $myOffer->offered_price : null,
                'my_offer_price_formatted' => $myOffer ? 'Rp ' . number_format((float) $myOffer->offered_price, 0, ',', '.') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => null,
            'orders' => $formatted,
            'count' => $formatted->count(),
            'radius_km' => (float) $jastiper->radius_km,
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

            // Jalur Bidding: deal terbentuk (jastiper_id + agreed_fare terkunci), berhenti
            // di status 'deal' — jastiper masih perlu tap "Mulai Proses" secara eksplisit
            // (lihat jastiperStartProcessOrder). Ini beda dengan jalur Booking Langsung yang
            // otomatis lanjut ke 'diproses'. Lihat app/Services/OrderDealService.php.
            $order = app(OrderDealService::class)->formDeal($order, $offer->jastiper, (float) $offer->offered_price, 'bidding');

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

        // Notifikasi ke jastiper yang terpilih
        if ($jastiperTerpilih) {
            $msg = "Selamat! Tawaran Anda untuk belanjaan \"{$order->description}\" *DISETUJUI* oleh customer. Silakan koordinasi lebih lanjut di nomor {$customer->phone_number} ({$customer->name}). 🎉";
            WhatsAppService::sendMessage($jastiperTerpilih->phone_number, $msg);
        }

        // Notifikasi ke jastiper lain yang tawarannya ditolak
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

        if ($order->status !== 'deal') {
            $msg = 'Order ini tidak dalam status deal.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 409)
                : redirect()->back()->with('error', $msg);
        }

        $order = app(OrderDealService::class)->startProcessing($order);

        $customer = $order->customer;
        if ($customer) {
            $msg = "Halo *{$customer->name}*, belanjaan Anda \"{$order->description}\" *MULAI DIPROSES* oleh Jastiper *{$jastiper->name}*! Kurir sedang membelanjakan barang Anda. 🚀";
            WhatsAppService::sendMessage($customer->phone_number, $msg);
        }

        $msg = 'Status pesanan berhasil diubah menjadi Sedang Diproses.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->back()->with('success', $msg);
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

        $order->update(['status' => 'selesai']);

        $customer = $order->customer;
        if ($customer) {
            $msg = "Halo *{$customer->name}*, belanjaan Anda \"{$order->description}\" telah *SELESAI* dibelanjakan dan diantarkan oleh Jastiper *{$jastiper->name}*! Terima kasih telah menggunakan layanan JastipKuy. 🙏";
            WhatsAppService::sendMessage($customer->phone_number, $msg);
        }

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
                'offers' => function ($q) {
                    $q->where('status', 'pending')->orderBy('offered_price', 'asc');
                },
                'offers.jastiper' => function ($q) {
                    $q->withAvg('ratings', 'rating')
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

            return [
                'id' => $order->id,
                'description' => $order->description,
                'category' => $order->category,
                'status' => $order->status,
                'estimated_fare' => (float) $order->estimated_fare,
                'estimated_fare_formatted' => 'Rp ' . number_format((float) $order->estimated_fare, 0, ',', '.'),
                'agreed_fare_formatted' => $order->agreed_fare ? 'Rp ' . number_format((float) $order->agreed_fare, 0, ',', '.') : null,
                'created_at' => $order->created_at->toIso8601String(),
                'seconds_since_created' => $secondsSinceCreated,
                'jastiper' => $order->jastiper ? [
                    'id' => $order->jastiper->id,
                    'name' => $order->jastiper->name,
                    'phone_number' => $order->jastiper->phone_number,
                ] : null,
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

                    $ratingAvg = $offer->jastiper?->ratings_avg_rating;

                    return [
                        'offer_id' => $offer->id,
                        'jastiper_id' => $offer->jastiper_id,
                        'jastiper_name' => $offer->jastiper->name ?? '-',
                        'offered_price' => (float) $offer->offered_price,
                        'offered_price_formatted' => 'Rp ' . number_format((float) $offer->offered_price, 0, ',', '.'),
                        'rating_avg' => $ratingAvg ? round((float) $ratingAvg, 1) : null,
                        'completed_orders_count' => $offer->jastiper->completed_orders_count ?? 0,
                        'response_speed_label' => $speedLabel,
                        'response_speed_tier' => $speedTier,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $formatted,
            'count' => $formatted->count(),
        ]);
    }
}