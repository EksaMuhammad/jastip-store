<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\JastiperVerification;
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

        // Ambil order umum (jastiper_id = null) di wilayah kerja Jastiper
        $orders = \App\Models\Order::where('status', 'menunggu_tawaran')
            ->whereNull('jastiper_id')
            ->where('wilayah_id', $jastiper->wilayah_id)
            ->latest()
            ->get();

        return view('dashboard.jastiper', compact('jastiper', 'orders', 'directOrders'));
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
}
