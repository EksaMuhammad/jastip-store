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
        return view('dashboard.jastiper', compact('jastiper'));
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
}
