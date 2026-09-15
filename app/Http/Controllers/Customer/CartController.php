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
        $deliveryFee = 15000; // Hardcoded fallback, should be from form input.
        
        if ($request->has('delivery_fee')) {
            $deliveryFee = $request->input('delivery_fee');
        }

        $dpAmount = ($estimatedItemsPrice * 0.5) + $deliveryFee;

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
            'origin_lat' => -7.9839, // Default Malang coordinates
            'origin_lng' => 112.6214,
            'destination_address' => $request->input('delivery_location', 'Alamat Customer'),
            'destination_lat' => -7.9839,
            'destination_lng' => 112.6214,
            'recipient_name' => Auth::guard('customer')->user()->name,
            'recipient_phone' => Auth::guard('customer')->user()->phone_number,
            'estimated_fare' => $deliveryFee, // Only the delivery fee for Jastiper to see
            'downpayment_amount' => $dpAmount, // Full DP (Items * 0.5 + delivery fee)
            'cash_amount' => 0,
            'status' => 'menunggu_tawaran', // Allow jastiper to see and bid on this order
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

        return redirect()->route('customer.dashboard')->with('success', 'Pesanan Katalog berhasil dibuat! Menunggu tawaran dari Jastiper.');
    }
}
