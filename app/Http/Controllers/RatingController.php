<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    /**
     * Halaman form rating & review untuk sebuah order yang sudah selesai.
     *
     * GET /customer/orders/{id}/rating
     */
    public function showForm(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::where('id', $id)
            ->where('customer_id', $customer->id)
            ->with(['jastiper', 'rating'])
            ->first();

        if (!$order) {
            $msg = 'Order tidak ditemukan atau bukan milik Anda.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : redirect()->route('customer.dashboard')->with('error', $msg);
        }

        if ($order->status !== 'selesai') {
            $msg = 'Order ini belum selesai, belum bisa diberi rating.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 409)
                : redirect()->route('customer.dashboard')->with('error', $msg);
        }

        // Kalau sudah pernah dirating, tampilkan halaman yang sama dalam mode
        // "read-only" (lihat kondisi @if($order->rating) di view) daripada
        // redirect — supaya customer tetap bisa melihat rating yang sudah
        // mereka berikan sebelumnya.
        return view('customer.rating', compact('order'));
    }

    /**
     * Simpan rating & review.
     *
     * POST /customer/orders/{id}/rating
     */
    public function store(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$order) {
            $msg = 'Order tidak ditemukan atau bukan milik Anda.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 404)
                : redirect()->route('customer.dashboard')->with('error', $msg);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ]);

        try {
            app(RatingService::class)->submitRating(
                $order,
                $customer,
                (int) $validated['rating'],
                $validated['review_text'] ?? null
            );
        } catch (\RuntimeException $e) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 409)
                : redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $msg = 'Terima kasih! Rating & review Anda berhasil disimpan.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->route('customer.dashboard')->with('success', $msg);
    }
}