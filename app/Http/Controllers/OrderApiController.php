<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Chat;
use App\Models\Wilayah;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderApiController extends Controller
{
    /**
     * Get a list of orders.
     */
    public function index(Request $request)
    {
        $customerId = $request->query('customer_id');
        $jastiperId = $request->query('jastiper_id');
        $status = $request->query('status');
        
        $query = Order::with(['customer', 'jastiper', 'offers.jastiper']);
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($jastiperId) {
            $query->where('jastiper_id', $jastiperId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        
        $orders = $query->orderBy('id', 'desc')->get();
 
        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Store a new order.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|in:beli-antar,ambil-antar,toko-kirim,dokumen,multi-stop,kirim-pihak-ketiga',
            'description' => 'required|string',
            'origin_address' => 'nullable|string',
            'destination_address' => 'required|string',
            'recipient_name' => 'required|string',
            'recipient_phone' => 'required|string',
            'estimated_fare' => 'required|numeric',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $customerId = $request->input('customer_id', 1);
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan.'
            ], 404);
        }

        $wilayah = Wilayah::first();
        if (!$wilayah) {
            return response()->json([
                'success' => false,
                'message' => 'Wilayah operasional tidak aktif.'
            ], 400);
        }

        $order = Order::create([
            'customer_id' => $customer->id,
            'wilayah_id' => $wilayah->id,
            'category' => $request->input('category'),
            'weight_category' => 'ringan', // Default
            'description' => $request->input('description'),
            'origin_address' => $request->input('origin_address') ?: 'Lokasi Pin Peta Asal',
            'origin_lat' => -7.9839, // Default Malang coordinates
            'origin_lng' => 112.6214,
            'destination_address' => $request->input('destination_address'),
            'destination_lat' => -7.9839,
            'destination_lng' => 112.6214,
            'recipient_name' => $request->input('recipient_name'),
            'recipient_phone' => $request->input('recipient_phone'),
            'estimated_fare' => $request->input('estimated_fare'),
            'status' => 'menunggu_tawaran',
        ]);

        // Send WhatsApp simulation notification
        $categoryNames = [
            'beli-antar' => 'Jastip Kuliner (Beli-Antar)',
            'ambil-antar' => 'Jastip Ambil Barang (Ambil-Antar)',
            'toko-kirim' => 'Jastip Toko',
            'dokumen' => 'Jastip Dokumen Kecil',
            'multi-stop' => 'Jastip Multi-Stop',
            'kirim-pihak-ketiga' => 'Jastip Pihak Ketiga',
        ];
        $catLabel = $categoryNames[$order->category] ?? 'Jastip';

        $msg = "Halo *{$customer->name}*!\n\nPermintaan Jastip baru Anda dari Aplikasi Mobile telah dikirim:\n\n📦 *Layanan*: {$catLabel}\n📝 *Deskripsi*: {$order->description}\n💰 *Estimasi Ongkir*: Rp " . number_format($order->estimated_fare, 0, ',', '.') . "\n\nSistem sedang mencarikan Jastiper terdekat di area Malang. Mohon tunggu tawaran masuk! 🚀";
        
        try {
            WhatsAppService::sendMessage($customer->phone_number, $msg);
        } catch (\Exception $e) {
            // Ignore notification errors
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat.',
            'order' => $order
        ], 201);
    }

    /**
     * Get active orders for Jastiper feed.
     */
    public function jastiperFeed(Request $request)
    {
        $orders = Order::with(['customer'])
            ->where('status', 'menunggu_tawaran')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Submit a bid/offer for an order.
     */
    public function jastiperBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'jastiper_id' => 'nullable|exists:jastiper,id',
            'offered_price' => 'required|numeric|min:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($request->input('order_id'));
        if ($order->status !== 'menunggu_tawaran' && $order->status !== 'ada_tawaran') {
            return response()->json([
                'success' => false,
                'message' => 'Order ini sudah tidak menerima tawaran.'
            ], 400);
        }

        $jastiperId = $request->input('jastiper_id', 1);

        // Create new offer
        $offer = \App\Models\Offer::create([
            'order_id' => $order->id,
            'jastiper_id' => $jastiperId,
            'offered_price' => $request->input('offered_price'),
            'status' => 'pending'
        ]);

        // Update order status
        $order->status = 'ada_tawaran';
        $order->save();

        // Send simulation WhatsApp to customer
        try {
            $msg = "Halo *{$order->customer->name}*!\n\nJastiper baru saja mengirimkan penawaran ongkir untuk pesanan Anda (*{$order->description}*):\n\n💰 *Penawaran Ongkir*: Rp " . number_format($offer->offered_price, 0, ',', '.') . "\n\nSilakan cek dashboard website/aplikasi Anda untuk menyetujui penawaran ini! 🚀";
            WhatsAppService::sendMessage($order->customer->phone_number, $msg);
        } catch (\Exception $e) {
            // Ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Penawaran berhasil dikirim.',
            'offer' => $offer
        ]);
    }

    /**
     * Get list of customers.
     */
    public function getCustomers()
    {
        return response()->json([
            'success' => true,
            'customers' => \App\Models\Customer::orderBy('name', 'asc')->get()
        ]);
    }

    /**
     * Get list of jastipers.
     */
    public function getJastipers()
    {
        return response()->json([
            'success' => true,
            'jastipers' => \App\Models\Jastiper::orderBy('name', 'asc')->get()
        ]);
    }

    /**
     * Get list of active wilayah.
     */
    public function getWilayahList()
    {
        return response()->json([
            'success' => true,
            'wilayah' => \App\Models\Wilayah::where('is_active', true)->orderBy('name', 'asc')->get()
        ]);
    }

    /**
     * Get list of checked in jastipers.
     */
    public function getCheckinJastipers()
    {
        return response()->json([
            'success' => true,
            'jastipers' => \App\Models\Jastiper::whereNotNull('checkin_location')
                ->where('is_available', true)
                ->orderBy('checked_in_at', 'desc')
                ->get()
        ]);
    }

    public function acceptOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id' => 'required|exists:offers,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $id = $request->input('offer_id');
        $customerId = $request->input('customer_id');
        $customer = \App\Models\Customer::find($customerId);

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

            $otherOffers = \App\Models\Offer::with('jastiper')
                ->where('order_id', $order->id)
                ->where('id', '!=', $offer->id)
                ->where('status', 'pending')
                ->get();

            $offer->update(['status' => 'accepted']);

            \App\Models\Offer::where('order_id', $order->id)
                ->where('id', '!=', $offer->id)
                ->update(['status' => 'rejected']);

            $order = app(\App\Services\OrderDealService::class)->formDeal($order, $offer->jastiper, (float) $offer->offered_price, 'bidding');
            app(\App\Services\PaymentService::class)->initiate($order);

            return [
                'success' => true,
                'order' => $order,
                'offer' => $offer,
                'other_offers' => $otherOffers,
            ];
        });

        return response()->json($result);
    }

    public function payOrderWithWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $orderId = $request->input('order_id');
        $customerId = $request->input('customer_id');

        $order = Order::find($orderId);
        if ($order->customer_id !== (int)$customerId) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan atau bukan milik Anda.'], 403);
        }

        $payment = \App\Models\Payment::where('order_id', $order->id)
            ->where('status', 'menunggu')
            ->latest('id')
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Tagihan pembayaran tidak ditemukan.'], 404);
        }

        $customer = \App\Models\Customer::find($customerId);

        try {
            $payment = app(\App\Services\PaymentService::class)->payWithWallet($payment, $customer);
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil.',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membayar: ' . $e->getMessage()
            ], 400);
        }
    }

    public function confirmDelivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $orderId = $request->input('order_id');
        $customerId = $request->input('customer_id');

        $order = Order::where('id', $orderId)->where('customer_id', $customerId)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan atau bukan milik Anda.'], 404);
        }

        if ($order->status !== 'tiba_tujuan') {
            return response()->json(['success' => false, 'message' => 'Order belum bisa dikonfirmasi.'], 400);
        }

        app(\App\Services\OrderCompletionService::class)->completeOrder($order, 'customer');

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih telah mengkonfirmasi penerimaan barang!'
        ]);
    }

    public function updateOrderStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'jastiper_id' => 'required|exists:jastiper,id',
            'status' => 'required|in:barang_diambil,sedang_diantar,tiba_tujuan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $orderId = $request->input('order_id');
        $jastiperId = $request->input('jastiper_id');
        $status = $request->input('status');

        $order = Order::where('id', $orderId)->where('jastiper_id', $jastiperId)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan atau bukan milik Anda.'], 404);
        }

        $order->update(['status' => $status]);

        $statusMessages = [
            'barang_diambil' => "Jastiper telah mengambil barang pesanan \"{$order->description}\".",
            'sedang_diantar' => "Jastiper sedang mengantar pesanan \"{$order->description}\" ke tujuan.",
            'tiba_tujuan' => "Pesanan \"{$order->description}\" telah tiba di tujuan! Mohon konfirmasi penerimaan di aplikasi."
        ];
        
        if (isset($statusMessages[$status])) {
            try {
                \App\Services\WhatsAppService::sendMessage($order->customer->phone_number, $statusMessages[$status]);
            } catch (\Exception $e) {}
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui.',
            'order' => $order
        ]);
    }

    public function acceptDirectBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'jastiper_id' => 'required|exists:jastiper,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $id = $request->input('order_id');
        $jastiperId = $request->input('jastiper_id');
        $jastiper = \App\Models\Jastiper::find($jastiperId);
        $order = Order::findOrFail($id);

        if ($order->jastiper_id !== $jastiper->id) {
            return response()->json(['success' => false, 'message' => 'Pesanan ini bukan ditujukan langsung untuk Anda.'], 403);
        }

        if ($order->status !== 'menunggu_tawaran') {
            return response()->json(['success' => false, 'message' => 'Status pesanan ini sudah berubah.'], 400);
        }

        $dealService = app(\App\Services\OrderDealService::class);
        $order = $dealService->formDeal($order, $jastiper, (float) $order->estimated_fare, 'direct');
        app(\App\Services\PaymentService::class)->initiate($order);

        return response()->json([
            'success' => true,
            'message' => 'Booking diterima! Menunggu customer menyelesaikan pembayaran.',
            'order' => $order
        ]);
    }

    public function rejectDirectBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'jastiper_id' => 'required|exists:jastiper,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $id = $request->input('order_id');
        $jastiperId = $request->input('jastiper_id');
        $jastiper = \App\Models\Jastiper::find($jastiperId);
        $order = Order::findOrFail($id);

        if ($order->jastiper_id !== $jastiper->id) {
            return response()->json(['success' => false, 'message' => 'Pesanan ini bukan ditujukan langsung untuk Anda.'], 403);
        }

        if ($order->status !== 'menunggu_tawaran') {
            return response()->json(['success' => false, 'message' => 'Status pesanan ini sudah berubah.'], 400);
        }

        $order->update([
            'jastiper_id' => null,
            'status' => 'menunggu_tawaran',
        ]);

        $customer = $order->customer;
        $msg = "Halo *{$customer->name}*!\n\nJastiper favorit Anda *{$jastiper->name}* saat ini sedang sibuk dan terpaksa melewatkan booking Anda. Jangan khawatir, request Anda kini dialihkan ke *Tawaran Terbira* agar bisa diambil oleh Jastiper aktif lainnya! 🚀";
        try {
            \App\Services\WhatsAppService::sendMessage($customer->phone_number, $msg);
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Booking langsung ditolak, pesanan dialihkan ke tawaran terbuka.',
            'order' => $order
        ]);
    }

    public function getChatHistory(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'sender_role' => 'required|in:customer,jastiper',
            'sender_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan.'], 404);
        }

        $role = $request->input('sender_role');
        $senderId = (int)$request->input('sender_id');

        // Check ownership
        $isOwner = $role === 'customer'
            ? $order->customer_id === $senderId
            : $order->jastiper_id === $senderId;

        if (!$isOwner) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke chat order ini.'], 403);
        }

        // Mark opponent messages as read
        Chat::forOrder($order->id)->unreadFor($role)->update(['is_read' => true]);

        $chats = Chat::forOrder($order->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $messages = $chats->map(function (Chat $chat) use ($order, $role) {
            return [
                'id' => $chat->id,
                'sender_role' => $chat->sender_role,
                'sender_name' => $chat->sender_role === 'system' ? 'Sistem' : ($chat->sender_role === 'customer' ? ($order->customer->name ?? 'Customer') : ($order->jastiper->name ?? 'Jastiper')),
                'is_mine' => $chat->sender_role === $role,
                'message_type' => $chat->message_type,
                'message' => $chat->message,
                'attachment_url' => $chat->attachment_path ? \Illuminate\Support\Facades\Storage::url($chat->attachment_path) : null,
                'action_type' => $chat->action_type,
                'is_read' => (bool)$chat->is_read,
                'created_at' => $chat->created_at->toIso8601String(),
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function sendChatMessage(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'sender_role' => 'required|in:customer,jastiper',
            'sender_id' => 'required',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan.'], 404);
        }

        $role = $request->input('sender_role');
        $senderId = (int)$request->input('sender_id');
        $message = $request->input('message');

        // Check ownership
        $isOwner = $role === 'customer'
            ? $order->customer_id === $senderId
            : $order->jastiper_id === $senderId;

        if (!$isOwner) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke chat order ini.'], 403);
        }

        $chat = app(\App\Services\ChatService::class)->sendMessage(
            $order,
            $role,
            $senderId,
            $message,
            null
        );

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $chat->id,
                'sender_role' => $chat->sender_role,
                'sender_name' => $chat->sender_role === 'system' ? 'Sistem' : ($chat->sender_role === 'customer' ? ($order->customer->name ?? 'Customer') : ($order->jastiper->name ?? 'Jastiper')),
                'is_mine' => true,
                'message_type' => $chat->message_type,
                'message' => $chat->message,
                'attachment_url' => null,
                'action_type' => null,
                'is_read' => false,
                'created_at' => $chat->created_at->toIso8601String(),
            ]
        ]);
    }
}
