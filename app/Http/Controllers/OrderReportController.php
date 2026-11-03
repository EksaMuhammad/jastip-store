<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Report;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderReportController extends Controller
{
    /**
     * Tampilkan formulir pengajuan keluhan / komplain untuk order tertentu.
     */
    public function create(Request $request, $orderId)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $order = Order::with(['items.product', 'jastiper', 'reports'])->findOrFail($orderId);

        if ($order->customer_id !== $customer->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Cek jika sudah ada laporan yang masih menunggu investigasi
        $pendingReport = $order->reports()->where('status', 'menunggu')->latest()->first();

        return view('customer.orders.report', compact('order', 'pendingReport'));
    }

    /**
     * Simpan keluhan / komplain resmi dari customer.
     */
    public function store(Request $request, $orderId)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $order = Order::with(['items', 'jastiper'])->findOrFail($orderId);

        if ($order->customer_id !== $customer->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Cek jika sudah ada komplain yang aktif
        $existingPending = $order->reports()->where('status', 'menunggu')->first();
        if ($existingPending) {
            return redirect()->route('customer.orders.report', $order->id)
                ->with('error', 'Pesanan ini sudah memiliki pengaduan aktif yang sedang ditinjau oleh Admin.');
        }

        $request->validate([
            'category' => 'required|string|in:barang_tidak_sesuai,barang_rusak,barang_kurang,selisih_harga_ongkir,lainnya',
            'description' => 'required|string|min:10|max:2000',
            'evidence_image' => 'nullable|file|extensions:jpeg,png,jpg,webp,heic,heif|max:20480',
        ], [
            'category.required' => 'Pilih kategori masalah pengaduan Anda.',
            'description.required' => 'Mohon jelaskan kendala atau ketidaksesuaian barang secara rinci.',
            'description.min' => 'Penjelasan kendala minimal 10 karakter.',
            'evidence_image.file' => 'Bukti harus berupa file gambar/foto.',
            'evidence_image.extensions' => 'Format foto bukti harus berupa JPG, PNG, WEBP, atau HEIC/HEIF (iPhone).',
            'evidence_image.max' => 'Ukuran foto maksimal 20MB.',
        ]);

        $evidencePath = null;
        if ($request->hasFile('evidence_image')) {
            $evidencePath = \App\Services\ImageUploadService::store($request->file('evidence_image'), 'reports', 'public');
        }

        $report = Report::create([
            'order_id' => $order->id,
            'reporter_role' => 'customer',
            'reporter_id' => $customer->id,
            'category' => $request->category,
            'description' => $request->description,
            'evidence_image' => $evidencePath,
            'status' => 'menunggu',
        ]);

        // Ubah status order menjadi 'bermasalah' agar pelepasan dana ditahan
        $order->update(['status' => 'bermasalah']);

        // Label kategori ramah pengguna
        $categoryLabels = [
            'barang_tidak_sesuai' => 'Barang Tidak Sesuai Pesanan',
            'barang_rusak' => 'Barang Rusak / Cacat Fisik',
            'barang_kurang' => 'Jumlah Barang Kurang',
            'selisih_harga_ongkir' => 'Selisih Harga / Biaya',
            'lainnya' => 'Kendala Lainnya',
        ];
        $catName = $categoryLabels[$request->category] ?? $request->category;

        // Kirim pesan sistem resmi di riwayat chat order
        app(ChatService::class)->sendSystemMessage(
            $order,
            "⚠️ Customer telah mengajukan pengaduan resmi (Kategori: {$catName}). Status pesanan kini dialihkan ke peninjauan Tim Admin JastipKuy (#REP-{$report->id}). Pencairan dana ditahan sementara hingga diputuskan oleh Admin."
        );

        \App\Services\ActivityLogger::log([
            'user_type' => 'customer',
            'user_id' => $customer->id,
            'user_name' => $customer->name,
            'order_id' => $order->id,
            'category' => 'komplain_tiket',
            'action' => 'report_filed',
            'title' => "Tiket Komplain Pengaduan #REP-{$report->id}",
            'description' => "Customer {$customer->name} mengajukan komplain resmi untuk pesanan #{$order->id} (Kategori: {$catName}). Keterangan: \"{$request->description}\"",
            'properties' => [
                'report_id' => $report->id,
                'category' => $request->category,
                'status' => 'menunggu',
                'evidence_image' => $evidencePath,
            ],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaduan Anda berhasil dikirim ke Admin JastipKuy untuk ditinjau.',
                'report' => $report,
            ]);
        }

        return redirect()->route('customer.orders.report', $order->id)
            ->with('success', 'Pengaduan Anda berhasil dilaporkan. Tim Admin JastipKuy akan segera menginvestigasi bukti dan keterangan Anda.');
    }
}
