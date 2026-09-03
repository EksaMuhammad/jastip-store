<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::with(['cartItems.product.merchant'])->firstOrCreate(
            ['customer_id' => Auth::guard('customer')->id()]
        );
        
        return view('customer.cart.index', compact('cart'));
    }

    public function add(Request $request, Product $product)
    {
        $cart = Cart::firstOrCreate(
            ['customer_id' => Auth::guard('customer')->id()]
        );

        // Optional: Ensure only 1 merchant per cart (if you want to restrict)
        if ($cart->merchant_id && $cart->merchant_id !== $product->merchant_id) {
            // Either clear cart or return error. Let's update merchant_id for now if it's empty
            if ($cart->cartItems()->count() == 0) {
                $cart->update(['merchant_id' => $product->merchant_id]);
            } else {
                return response()->json(['error' => 'Hanya bisa pesan dari 1 toko dalam 1 keranjang.'], 400);
            }
        } else if (!$cart->merchant_id) {
            $cart->update(['merchant_id' => $product->merchant_id]);
        }

        $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->increment('quantity');
        } else {
            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => 1
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Ditambahkan ke keranjang']);
    }

    public function checkout(Request $request)
    {
        $cart = Cart::with(['cartItems.product.merchant'])->where('customer_id', Auth::guard('customer')->id())->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Keranjang kosong.');
        }

        $merchant = $cart->merchant_id ? Merchant::find($cart->merchant_id) : null;
        $merchantName = $merchant ? $merchant->name : 'Pesanan Katalog';
        
        // Calculate totals
        $estimatedItemsPrice = 0;
        foreach ($cart->cartItems as $item) {
            $estimatedItemsPrice += ($item->product->estimated_price * $item->quantity);
        }

        // DP Logic: 50% of items + full ongkir + app fee
        $appFee = env('APP_FEE_CUSTOMER', 2000);
        
        // We will need the customer to input location in checkout, but for now let's assume a flat delivery fee or calculate it.
        // In real app, delivery fee is calculated by maps. Let's assume a default for now, or require a form submission.
        $deliveryFee = 15000; // Hardcoded fallback, should be from form input.
        
        if ($request->has('delivery_fee')) {
            $deliveryFee = $request->input('delivery_fee');
        }

        $dpAmount = ($estimatedItemsPrice * 0.5) + $deliveryFee + $appFee;

        // Create Order
        $order = \App\Models\Order::create([
            'customer_id' => Auth::guard('customer')->id(),
            'merchant_id' => $cart->merchant_id,
            'is_catalog_order' => true,
            'wilayah_id' => 11, // Malang Kota
            'category' => $merchant && $merchant->type == 'food' ? 'beli-antar' : 'toko-kirim',
            'weight_category' => 'ringan',
            'description' => "Pesanan dari Katalog: " . $merchantName,
            'origin_address' => $merchant ? $merchant->address : 'Sesuai Toko',
            'origin_lat' => 0,
            'origin_lng' => 0,
            'destination_address' => $request->input('delivery_location', 'Alamat Customer'),
            'destination_lat' => 0,
            'destination_lng' => 0,
            'recipient_name' => Auth::guard('customer')->user()->name,
            'recipient_phone' => Auth::guard('customer')->user()->phone_number,
            'estimated_fare' => $dpAmount, // We use this for the initial payment calculation
            'downpayment_amount' => $dpAmount,
            'cash_amount' => 0,
            'status' => 'menunggu_pembayaran',
        ]);

        // Create Order Items
        foreach ($cart->cartItems as $item) {
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'estimated_price' => $item->product->estimated_price,
            ]);
        }

        // Clear Cart
        $cart->cartItems()->delete();
        $cart->update(['merchant_id' => null]);

        // Redirect to JKPay payment page for the order
        app(\App\Services\PaymentService::class)->initiate($order);
        
        return redirect()->route('customer.orders.payment.page', ['id' => $order->id]);
    }
}
