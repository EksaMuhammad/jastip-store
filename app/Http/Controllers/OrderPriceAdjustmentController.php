<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPriceAdjustment;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderPriceAdjustmentController extends Controller
{
    /**
     * Ambil daftar item yang dapat disesuaikan harganya oleh Jastiper.
     */
    public function getItemsForAdjustment(Request $request, $orderId): JsonResponse
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = Order::with('items.priceAdjustment')
            ->where('id', $orderId)
            ->where('jastiper_id', $jastiper->id)
            ->firstOrFail();

        $items = $order->items->map(function (OrderItem $item) {
            $hasPending = $item->priceAdjustment && $item->priceAdjustment->status === 'pending';
            return [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'estimated_price' => (float) ($item->estimated_price ?? 0),
                'actual_price' => $item->actual_price !== null ? (float) $item->actual_price : null,
                'current_price' => (float) ($item->actual_price ?? $item->estimated_price ?? 0),
                'notes' => $item->notes,
                'has_pending' => (bool) $hasPending,
            ];
        });

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    /**
     * Jastiper mengajukan penyesuaian harga barang asli toko via Chat.
     */
    public function store(Request $request, $orderId): JsonResponse
    {
        $jastiper = Auth::guard('jastiper')->user();
        $order = Order::where('id', $orderId)
            ->where('jastiper_id', $jastiper->id)
            ->firstOrFail();

        if (!in_array($order->status, ['diproses', 'barang_diambil', 'deal'])) {
            return response()->json([
                'success' => false,
                'message' => 'Penyesuaian harga hanya dapat diajukan saat pesanan sedang diproses.',
            ], 422);
        }

        $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'proposed_price' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:1000',
            'proof_image' => 'nullable|image|max:3072', // Max 3MB
        ]);

        $orderItem = OrderItem::where('id', $request->order_item_id)
            ->where('order_id', $order->id)
            ->firstOrFail();

        // Cek jika sudah ada pengajuan pending untuk item ini
        $existingPending = OrderPriceAdjustment::where('order_item_id', $orderItem->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'Item ini sudah memiliki pengajuan harga yang sedang menunggu persetujuan customer.',
            ], 422);
        }

        $originalPrice = (float) ($orderItem->actual_price ?? $orderItem->estimated_price ?? 0);
        $proposedPrice = (float) $request->proposed_price;
        $difference = $proposedPrice - $originalPrice;

        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('price_adjustments', 'public');
        }

        $adjustment = OrderPriceAdjustment::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'jastiper_id' => $jastiper->id,
            'item_name' => $orderItem->name,
            'original_price' => $originalPrice,
            'proposed_price' => $proposedPrice,
            'difference' => $difference,
            'reason' => $request->reason,
            'proof_image' => $proofPath,
            'status' => 'pending',
        ]);

        // Buat pesan interaktif ke dalam Chat Room
        $diffSign = $difference >= 0 ? '+' : '-';
        $diffFormatted = $diffSign . 'Rp ' . number_format(abs($difference), 0, ',', '.');
        $chatText = "🏷️ KONFIRMASI HARGA ASLI TOKO\n"
            . "Barang: {$orderItem->name}\n"
            . "Estimasi Awal: Rp " . number_format($originalPrice, 0, ',', '.') . "\n"
            . "Harga Asli Toko: Rp " . number_format($proposedPrice, 0, ',', '.') . "\n"
            . "Selisih: {$diffFormatted}\n"
            . ($request->reason ? "Catatan Jastiper: \"{$request->reason}\"\n" : "")
            . "Harap konfirmasi untuk melanjutkan pembelian barang ini.";

        $chat = Chat::create([
            'order_id' => $order->id,
            'sender_type' => 'system',
            'sender_id' => 0,
            'sender_role' => 'system',
            'message_type' => 'action_button',
            'message' => $chatText,
            'attachment_path' => $proofPath,
            'action_type' => 'price_adjustment:' . $adjustment->id,
            'is_read' => false,
        ]);

        // Log ke Audit Aktivitas
        \App\Services\ActivityLogger::log([
            'user_type' => 'jastiper',
            'user_id' => $jastiper->id,
            'user_name' => $jastiper->name,
            'order_id' => $order->id,
            'category' => 'harga_toko',
            'action' => 'price_adjustment_proposed',
            'title' => "Pengajuan Penyesuaian Harga Toko",
            'description' => "Jastiper {$jastiper->name} mengajukan perubahan harga untuk \"{$orderItem->name}\" ({$diffFormatted}) pada pesanan #{$order->id}." . ($request->reason ? " Alasan: \"{$request->reason}\"" : ""),
            'properties' => [
                'adjustment_id' => $adjustment->id,
                'original_price' => $originalPrice,
                'proposed_price' => $proposedPrice,
                'difference' => $difference,
            ]
        ]);

        // Notifikasi WhatsApp ke Customer jika nomor tersedia
        try {
            if ($order->customer && $order->customer->phone_number) {
                WhatsAppService::sendMessage(
                    $order->customer->phone_number,
                    "Halo {$order->customer->name}! Jastiper mengajukan penyesuaian harga di toko untuk pesanan #{$order->id} ({$orderItem->name}: {$diffFormatted}). Silakan buka chat pesanan di aplikasi JastipKuy untuk menyetujui."
                );
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan penyesuaian harga berhasil dikirim ke Chat.',
            'adjustment' => $adjustment,
            'chat_message' => [
                'id' => $chat->id,
                'sender_role' => 'system',
                'sender_name' => 'Sistem',
                'is_mine' => false,
                'message_type' => 'action_button',
                'message' => $chat->message,
                'attachment_url' => $proofPath ? Storage::url($proofPath) : null,
                'action_type' => $chat->action_type,
                'created_at' => $chat->created_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Ambil data detail adjustment untuk modal tinjauan.
     */
    public function show($adjustmentId): JsonResponse
    {
        $adjustment = OrderPriceAdjustment::with(['orderItem', 'order', 'jastiper'])->findOrFail($adjustmentId);

        return response()->json([
            'success' => true,
            'adjustment' => [
                'id' => $adjustment->id,
                'order_id' => $adjustment->order_id,
                'item_name' => $adjustment->item_name,
                'original_price' => (float) $adjustment->original_price,
                'proposed_price' => (float) $adjustment->proposed_price,
                'difference' => (float) $adjustment->difference,
                'reason' => $adjustment->reason,
                'proof_image_url' => $adjustment->proof_image ? Storage::url($adjustment->proof_image) : null,
                'status' => $adjustment->status,
                'created_at' => $adjustment->created_at->format('d M Y H:i'),
                'jastiper_name' => $adjustment->jastiper->name ?? 'Jastiper',
            ]
        ]);
    }

    /**
     * Customer menanggapi penyesuaian harga (Approve / Reject / Cancel Order).
     */
    public function respond(Request $request, $adjustmentId): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $adjustment = OrderPriceAdjustment::with(['order', 'orderItem'])->findOrFail($adjustmentId);
        $order = $adjustment->order;

        if ($order->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        if ($adjustment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Pengajuan ini sudah ditindaklanjuti sebelumnya (Status: {$adjustment->status}).",
            ], 422);
        }

        $request->validate([
            'action' => 'required|in:approve,reject,cancel_order',
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        $result = DB::transaction(function () use ($request, $adjustment, $order, $customer) {
            if ($request->action === 'approve') {
                $adjustment->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                // Update harga riil pada order_items
                if ($adjustment->orderItem) {
                    $notePrefix = $adjustment->orderItem->notes ? $adjustment->orderItem->notes . ' | ' : '';
                    $adjustment->orderItem->update([
                        'actual_price' => $adjustment->proposed_price,
                        'notes' => $notePrefix . "Penyesuaian toko disetujui: Rp " . number_format($adjustment->proposed_price, 0, ',', '.'),
                    ]);
                }

                // Tambahkan selisih ke tagihan tunai / sisa bayar pesanan
                if ($adjustment->difference > 0) {
                    $order->increment('cash_amount', $adjustment->difference);
                }

                // Buat pesan konfirmasi di Chat
                $diffText = '+Rp ' . number_format($adjustment->difference, 0, ',', '.');
                Chat::create([
                    'order_id' => $order->id,
                    'sender_type' => 'system',
                    'sender_id' => 0,
                    'sender_role' => 'system',
                    'message_type' => 'text',
                    'message' => "✓ Customer telah MENYETUJUI penyesuaian harga untuk \"{$adjustment->item_name}\" ({$diffText}). Perubahan harga telah tercatat di struk pesanan dan selisih akan diselesaikan saat serah terima barang.",
                    'is_read' => false,
                ]);

                \App\Services\ActivityLogger::log([
                    'user_type' => 'customer',
                    'user_id' => $customer->id,
                    'user_name' => $customer->name,
                    'order_id' => $order->id,
                    'category' => 'harga_toko',
                    'action' => 'price_adjustment_approved',
                    'title' => "Kenaikan Harga Toko Disetujui",
                    'description' => "Customer {$customer->name} menyetujui penyesuaian harga toko \"{$adjustment->item_name}\" ({$diffText}) pada pesanan #{$order->id}.",
                    'properties' => ['difference' => $adjustment->difference],
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'approved',
                    'message' => 'Penyesuaian harga berhasil disetujui dan dicatat di struk pesanan.',
                ]);
            }

            if ($request->action === 'reject' || $request->action === 'cancel_order') {
                $statusToSet = ($request->action === 'reject') ? 'rejected' : 'cancelled';
                $adjustment->update([
                    'status' => $statusToSet,
                    'rejected_at' => now(),
                ]);

                // Jalankan pembatalan pesanan resmi sesuai ketentuan pembatalan di lokasi
                $cancelController = app(OrderCancellationController::class);
                $cancelReq = new Request([
                    'reason' => $request->cancel_reason ?: "Kenaikan harga barang di toko tidak disetujui ({$adjustment->item_name})",
                ]);
                $cancelController->cancelOrder($cancelReq, $order->id);

                $cancelLog = \App\Models\OrderCancellationLog::where('order_id', $order->id)->latest()->first();
                $refundAmount = $cancelLog ? (float) $cancelLog->total_refund : 0;
                $jastiperCompensation = $cancelLog ? (float) $cancelLog->jastiper_compensation : 0;
                $refundFormatted = 'Rp ' . number_format($refundAmount, 0, ',', '.');
                $compFormatted = 'Rp ' . number_format($jastiperCompensation, 0, ',', '.');

                $itemLabel = $adjustment->item_name ?: 'barang';
                $diffText = $adjustment->difference > 0 ? " (+Rp " . number_format($adjustment->difference, 0, ',', '.') . ")" : "";

                $chatNotice = "⚠️ PESANAN DIBATALKAN KARENA KENAIKAN HARGA DITOLAK\n"
                    . "Customer menolak penyesuaian harga toko untuk \"{$itemLabel}\"{$diffText}.\n"
                    . "Karena Jastiper telah berada di lokasi toko:\n"
                    . "• Ongkir ({$compFormatted}) diteruskan ke Dompet Jastiper sebagai kompensasi perjalanan.\n"
                    . "• Uang belanja barang ({$refundFormatted}) telah dikembalikan ke Saldo Dompet Customer.";

                Chat::create([
                    'order_id' => $order->id,
                    'sender_type' => 'system',
                    'sender_id' => 0,
                    'sender_role' => 'system',
                    'message_type' => 'text',
                    'message' => $chatNotice,
                    'is_read' => false,
                ]);

                return response()->json([
                    'success' => true,
                    'status' => $statusToSet,
                    'title' => 'Pesanan Berhasil Dibatalkan',
                    'message' => "Pesanan dibatalkan karena kenaikan harga toko tidak disetujui. Ongkir ({$compFormatted}) diteruskan ke Jastiper dan uang belanja ({$refundFormatted}) telah dikembalikan ke Saldo Dompet Anda.",
                    'refund_amount' => $refundAmount,
                    'jastiper_compensation' => $jastiperCompensation,
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
        });

        if (!$request->expectsJson() && !$request->isJson()) {
            if ($result instanceof JsonResponse && $result->getStatusCode() === 200) {
                $content = json_decode($result->getContent(), true);
                return redirect()->back()->with('success', $content['message'] ?? 'Berhasil menanggapi penyesuaian harga.');
            }
            if ($result instanceof JsonResponse) {
                $content = json_decode($result->getContent(), true);
                return redirect()->back()->with('error', $content['message'] ?? 'Gagal memproses penyesuaian harga.');
            }
        }

        return $result;
    }
}
