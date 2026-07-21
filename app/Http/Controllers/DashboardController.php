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

        // Ambil order nyata yang berstatus menunggu tawaran di wilayah kerja Jastiper
        $orders = \App\Models\Order::where('status', 'menunggu_tawaran')
            ->where('wilayah_id', $jastiper->wilayah_id)
            ->latest()
            ->get();

        return view('dashboard.jastiper', compact('jastiper', 'orders'));
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
}
