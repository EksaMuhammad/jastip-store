<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\JastiperVerification;
use App\Models\Order;
use App\Models\Jastiper;
use App\Services\WhatsAppService;

class DashboardController extends Controller
{
    /**
     * Dashboard untuk Customer.
     */
    public function customerDashboard()
    {
        $customer = Auth::guard('customer')->user();
        
        // Ambil order nyata milik customer yang masih aktif (belum selesai/batal)
        $orders = \App\Models\Order::with('jastiper')
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['menunggu_tawaran', 'diproses'])
            ->latest()
            ->get();

        return view('dashboard.customer', compact('customer', 'orders'));
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

        // Catatan: daftar order umum (feed radius+kategori) SENGAJA tidak lagi diambil
        // di sini. Feed sepenuhnya diambil oleh JavaScript di view lewat endpoint
        // jastiperOrderFeed() (GET /jastiper/orders/feed), baik untuk render pertama
        // kali halaman dibuka maupun untuk polling real-time berikutnya. Ini supaya
        // hanya ada SATU logika query (radius+kategori) yang dipakai, dan tidak ada
        // lagi perbedaan data antara render awal vs hasil polling.
        return view('dashboard.jastiper', compact('jastiper', 'directOrders'));
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
     * Aksi terima order oleh Jastiper.
     */
    public function jastiperAcceptOrder($id)
    {
        $jastiper = Auth::guard('jastiper')->user();

        if ($jastiper->verification_status !== 'approved') {
            return redirect()->back()->with('error', 'Akun Anda belum terverifikasi oleh Admin.');
        }

        $order = \App\Models\Order::findOrFail($id);

        if ($order->status !== 'menunggu_tawaran') {
            return redirect()->back()->with('error', 'Orderan ini sudah diambil oleh Jastiper lain.');
        }

        // Update order status dan relasi jastiper
        $order->update([
            'jastiper_id' => $jastiper->id,
            'status' => 'diproses',
            'agreed_fare' => $order->estimated_fare,
        ]);

        // Simulasikan pesan WhatsApp ke Customer
        $customer = $order->customer;
        $msg = "Halo *{$customer->name}*!\n\nPesanan jastip Anda (*{$order->description}*) telah *DITERIMA* oleh Jastiper *{$jastiper->name}*! Hubungi jastiper di nomor: {$jastiper->phone_number} untuk koordinasi belanjaan. Terima kasih. 🙏";
        WhatsAppService::sendMessage($customer->phone_number, $msg);

        return redirect()->route('jastiper.dashboard')->with('success', 'Orderan berhasil diambil! Silakan hubungi customer.');
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

        $order->update([
            'status' => 'diproses',
            'agreed_fare' => $order->estimated_fare,
        ]);

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

        $orders = Order::where('status', 'menunggu_tawaran')
            ->whereNull('jastiper_id')
            ->nearby((float) $jastiper->current_lat, (float) $jastiper->current_lng, (float) $jastiper->radius_km)
            ->categoryIs($category)
            ->with('customer:id,name,phone_number')
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
     * Endpoint terima BANYAK order sekaligus (multi-order) dalam satu aksi.
     * Tiap order tetap 1 pembayar sendiri-sendiri (tidak digabung/split bill) — ini murni
     * efisiensi rute, jastiper cuma "checkout" beberapa order yang searah dalam satu klik.
     *
     * Body JSON: { "order_ids": [12, 15, 20] }
     * POST /jastiper/orders/multi-accept
     */
    public function jastiperMultiAcceptOrders(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|distinct|exists:orders,id',
        ], [
            'order_ids.required' => 'Pilih minimal 1 order untuk diterima.',
            'order_ids.*.exists' => 'Salah satu order yang dipilih tidak ditemukan.',
        ]);

        $jastiper = Auth::guard('jastiper')->user();

        if ($jastiper->verification_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum terverifikasi oleh Admin.',
            ], 403);
        }

        $accepted = [];
        $failed = [];

        foreach ($request->order_ids as $orderId) {
            // Kunci baris order ini supaya aman dari race condition (2 jastiper klik bersamaan)
            $result = DB::transaction(function () use ($orderId, $jastiper) {
                $order = Order::where('id', $orderId)->lockForUpdate()->first();

                if (!$order) {
                    return ['status' => 'failed', 'id' => $orderId, 'reason' => 'Order tidak ditemukan.'];
                }

                if ($order->status !== 'menunggu_tawaran' || !is_null($order->jastiper_id)) {
                    return ['status' => 'failed', 'id' => $orderId, 'reason' => 'Sudah diambil Jastiper lain.'];
                }

                $order->update([
                    'jastiper_id' => $jastiper->id,
                    'status' => 'diproses',
                    'agreed_fare' => $order->estimated_fare,
                ]);

                return ['status' => 'accepted', 'id' => $orderId, 'order' => $order];
            });

            if ($result['status'] === 'accepted') {
                $accepted[] = $result['id'];

                // Notifikasi WhatsApp ke customer per order yang berhasil diambil
                $order = $result['order'];
                $customer = $order->customer;
                if ($customer) {
                    $msg = "Halo *{$customer->name}*!\n\nPesanan jastip Anda (*{$order->description}*) telah *DITERIMA* oleh Jastiper *{$jastiper->name}* (sekaligus bersama beberapa order lain dalam satu rute). Hubungi jastiper di nomor: {$jastiper->phone_number} untuk koordinasi belanjaan. Terima kasih. 🙏";
                    WhatsAppService::sendMessage($customer->phone_number, $msg);
                }
            } else {
                $failed[] = ['id' => $result['id'], 'reason' => $result['reason']];
            }
        }

        return response()->json([
            'success' => count($accepted) > 0,
            'accepted_count' => count($accepted),
            'accepted_ids' => $accepted,
            'failed' => $failed,
            'message' => count($failed) > 0
                ? count($accepted) . ' order berhasil diambil, ' . count($failed) . ' order gagal (sudah diambil orang lain).'
                : count($accepted) . ' order berhasil diambil sekaligus!',
        ]);
    }
}