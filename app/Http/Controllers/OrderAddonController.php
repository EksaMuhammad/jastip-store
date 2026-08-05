<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderAddon;
use Illuminate\Support\Facades\Auth;
use App\Services\WhatsAppService;
use App\Models\Chat;

class OrderAddonController extends Controller
{
    /**
     * Customer requests an addon (also sends a chat message)
     */
    public function requestAddon(Request $request, $order_id)
    {
        $customer = Auth::guard('customer')->user();
        $order = Order::where('id', $order_id)->where('customer_id', $customer->id)->firstOrFail();

        $request->validate([
            'description' => 'required|string|max:500'
        ]);

        if (!in_array($order->status, ['diproses', 'barang_diambil'])) {
            return response()->json(['success' => false, 'message' => 'Permintaan tambahan tidak dapat dilakukan pada tahap ini.'], 400);
        }

        $addon = OrderAddon::create([
            'order_id' => $order->id,
            'description' => $request->description,
            'additional_fare' => 0,
            'payment_status' => 'pending_jastiper',
        ]);

        // Send a system message in chat with action_button
        Chat::create([
            'order_id' => $order->id,
            'sender_type' => 'system',
            'sender_id' => 0,
            'message_type' => 'action_button',
            'message' => "Customer meminta tambahan pesanan:\n" . $request->description,
            'action_type' => 'addon_request:' . $addon->id,
        ]);

        return response()->json(['success' => true, 'addon' => $addon]);
    }

    /**
     * Jastiper responds to addon request (accepts/rejects)
     */
    public function respondAddon(Request $request, $addon_id)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $addon = OrderAddon::where('id', $addon_id)->with('order')->firstOrFail();
        
        if ($addon->order->jastiper_id !== $jastiper->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'action' => 'required|in:accept,reject',
            'additional_fare' => 'required_if:action,accept|numeric|min:0'
        ]);

        if ($request->action === 'reject') {
            $addon->update(['payment_status' => 'rejected']);
            
            Chat::create([
                'order_id' => $addon->order_id,
                'sender_type' => 'system',
                'sender_id' => 0,
                'message' => "Jastiper menolak permintaan tambahan: {$addon->description}",
            ]);
            
            return back()->with('success', 'Tambahan ditolak.');
        }

        // Accept
        $addon->update([
            'payment_status' => 'pending_payment',
            'additional_fare' => $request->additional_fare,
        ]);

        // Hitung ulang tagihan
        $order = $addon->order;
        $order->increment('agreed_fare', $request->additional_fare);
        
        Chat::create([
            'order_id' => $order->id,
            'sender_type' => 'system',
            'sender_id' => 0,
            'message' => "Jastiper menerima permintaan tambahan: {$addon->description}. Biaya tambahan: Rp " . number_format($request->additional_fare, 0, ',', '.') . ". Tagihan telah diupdate.",
        ]);

        WhatsAppService::sendMessage($order->customer->phone_number, "Jastiper menyetujui tambahan pesanan Anda \"{$addon->description}\" dengan biaya Rp " . number_format($request->additional_fare, 0, ',', '.') . ". Silakan bayar sisa tagihan dari dashboard Anda.");

        return back()->with('success', 'Tambahan disetujui, tagihan diupdate.');
    }

    /**
     * Customer chooses payment method for the addon
     */
    public function payAddon(Request $request, $order_id, $addon_id)
    {
        $customer = Auth::guard('customer')->user();
        $order = Order::where('id', $order_id)->where('customer_id', $customer->id)->firstOrFail();
        
        $addon = OrderAddon::where('id', $addon_id)
            ->where('order_id', $order->id)
            ->where('payment_status', 'pending_payment')
            ->firstOrFail();

        $request->validate([
            'payment_method' => 'required|in:transfer,cod'
        ]);

        if ($request->payment_method === 'cod') {
            $addon->update(['payment_status' => 'paid_cod']);
            
            Chat::create([
                'order_id' => $order->id,
                'sender_type' => 'system',
                'sender_id' => null,
                'message' => "Customer memilih pembayaran COD untuk tambahan pesanan \"{$addon->description}\". Harap tagih Rp " . number_format($addon->additional_fare, 0, ',', '.') . " secara tunai saat pengantaran.",
            ]);

            return response()->json(['success' => true, 'message' => 'Pilihan COD tersimpan.']);
        } else {
            $addon->update(['payment_status' => 'paid_transfer']);

            Chat::create([
                'order_id' => $order->id,
                'sender_type' => 'system',
                'sender_id' => null,
                'message' => "Customer telah membayar tagihan tambahan pesanan \"{$addon->description}\" via Transfer.",
            ]);

            return response()->json(['success' => true, 'message' => 'Pembayaran Transfer berhasil (Simulasi).']);
        }
    }
}
