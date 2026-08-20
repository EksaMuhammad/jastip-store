<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Wilayah;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Validator;

class OrderApiController extends Controller
{
    /**
     * Get a list of orders.
     */
    public function index(Request $request)
    {
        $customerId = $request->query('customer_id', 1);
        
        $orders = Order::with(['customer', 'jastiper'])
            ->where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->get();

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
}
