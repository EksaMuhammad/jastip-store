<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\DistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Tampilkan halaman keranjang belanja beserta rute multi-toko dan estimasi ongkir.
     */
    public function index(Request $request): View
    {
        $cart = Cart::with(['cartItems.product.merchant'])->firstOrCreate(
            ['customer_id' => Auth::guard('customer')->id()]
        );

        // Kelompokkan item berdasarkan merchant/toko
        $groupedItems = $cart->cartItems->groupBy(function ($item) {
            return $item->product->merchant_id ?? 0;
        });

        // Kumpulkan merchant unik yang ada di keranjang sesuai urutan
        $merchantIds = $cart->cartItems->pluck('product.merchant_id')->unique()->values();
        $merchants = Merchant::whereIn('id', $merchantIds)->get()->keyBy('id');

        $merchantLocations = [];
        foreach ($merchantIds as $mId) {
            if ($merchants->has($mId)) {
                $m = $merchants->get($mId);
                $coords = $m->getCoordinates();
                $merchantLocations[] = [
                    'id' => $m->id,
                    'name' => $m->name,
                    'address' => $m->address,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ];
            }
        }

        // Koordinat default tujuan pengiriman (Malang Kota)
        $destLat = $request->filled('lat') ? (float) $request->input('lat') : -7.9839;
        $destLng = $request->filled('lng') ? (float) $request->input('lng') : 112.6214;

        // Hitung rute berantai Toko 1 -> Toko 2 -> ... -> Tujuan
        $routeCalculation = DistanceService::calculateMultiStoreRoute($merchantLocations, $destLat, $destLng);

        return view('customer.cart.index', compact(
            'cart',
            'groupedItems',
            'merchants',
            'merchantIds',
            'merchantLocations',
            'routeCalculation'
        ));
    }

    /**
     * Tambahkan produk ke keranjang belanja (Mendukung Multi-Merchant / Toko Berbeda).
     */
    public function add(Request $request, Product $product): JsonResponse
    {
        $cart = Cart::firstOrCreate(
            ['customer_id' => Auth::guard('customer')->id()]
        );

        // Jika cart belum memiliki merchant_id default, pasang toko pertama
        if (!$cart->merchant_id) {
            $cart->update(['merchant_id' => $product->merchant_id]);
        }

        $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->increment('quantity');
        } else {
            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "{$product->name} berhasil ditambahkan ke keranjang belanja.",
            'cart_count' => $cart->cartItems()->sum('quantity'),
        ]);
    }

    /**
     * Hitung ulang rute multi-stop & ongkir via AJAX saat customer mengubah titik pengiriman.
     */
    public function calculateRoute(Request $request): JsonResponse
    {
        $destLat = (float) $request->input('destination_lat', -7.9839);
        $destLng = (float) $request->input('destination_lng', 112.6214);

        $cart = Cart::with(['cartItems.product.merchant'])
            ->where('customer_id', Auth::guard('customer')->id())
            ->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['error' => 'Keranjang kosong'], 400);
        }

        $merchantIds = $cart->cartItems->pluck('product.merchant_id')->unique()->values();
        $merchants = Merchant::whereIn('id', $merchantIds)->get()->keyBy('id');

        $merchantLocations = [];
        foreach ($merchantIds as $mId) {
            if ($merchants->has($mId)) {
                $m = $merchants->get($mId);
                $coords = $m->getCoordinates();
                $merchantLocations[] = [
                    'id' => $m->id,
                    'name' => $m->name,
                    'address' => $m->address,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ];
            }
        }

        $calculation = DistanceService::calculateMultiStoreRoute($merchantLocations, $destLat, $destLng);

        return response()->json($calculation);
    }

    /**
     * Update jumlah barang di keranjang.
     */
    public function updateQuantity(Request $request, CartItem $cartItem): JsonResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        
        $cart = $cartItem->cart;
        if ($cart->customer_id !== Auth::guard('customer')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $cartItem->update(['quantity' => (int) $request->input('quantity')]);

        return response()->json(['success' => true]);
    }

    /**
     * Hapus barang dari keranjang belanja.
     */
    public function remove(CartItem $cartItem): RedirectResponse
    {
        $cart = $cartItem->cart;
        if ($cart->customer_id === Auth::guard('customer')->id()) {
            $cartItem->delete();
        }

        return redirect()->route('customer.cart.index')->with('success', 'Barang dihapus dari keranjang.');
    }

    /**
     * Checkout pesanan keranjang (Mendukung Rute Multi-Toko Berantai).
     */
    public function checkout(Request $request): RedirectResponse
    {
        $cart = Cart::with(['cartItems.product.merchant'])
            ->where('customer_id', Auth::guard('customer')->id())
            ->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Keranjang masih kosong.');
        }

        $destLat = $request->filled('destination_lat') ? (float) $request->input('destination_lat') : -7.9839;
        $destLng = $request->filled('destination_lng') ? (float) $request->input('destination_lng') : 112.6214;
        $destAddress = $request->input('delivery_location', 'Alamat Customer');

        // Kelompokkan item berdasarkan merchant/toko
        $groupedItems = $cart->cartItems->groupBy(function ($item) {
            return $item->product->merchant_id ?? 0;
        });

        $merchantIds = $cart->cartItems->pluck('product.merchant_id')->unique()->values();
        $merchants = Merchant::whereIn('id', $merchantIds)->get()->keyBy('id');

        $merchantLocations = [];
        $multiStops = [];
        $merchantNames = [];

        foreach ($merchantIds as $idx => $mId) {
            if ($merchants->has($mId)) {
                $m = $merchants->get($mId);
                $coords = $m->getCoordinates();
                $itemsForMerchant = $groupedItems->get($mId, collect());

                $itemDescriptions = $itemsForMerchant->map(function ($it) {
                    return "{$it->product->name} ({$it->quantity}x)";
                })->implode(', ');

                $merchantLocations[] = [
                    'id' => $m->id,
                    'name' => $m->name,
                    'address' => $m->address,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ];

                $multiStops[] = [
                    'type' => 'merchant',
                    'merchant_id' => $m->id,
                    'name' => $m->name,
                    'address' => "Toko " . ($idx + 1) . ": {$m->name} ({$m->address})",
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'notes' => $itemDescriptions,
                ];

                $merchantNames[] = $m->name;
            }
        }

        // Hitung rute multi-toko berantai (Toko 1 ➔ Toko 2 ➔ ... ➔ Alamat Tujuan)
        $routeCalculation = DistanceService::calculateMultiStoreRoute($merchantLocations, $destLat, $destLng);
        $totalDistance = $routeCalculation['total_distance_km'];
        $deliveryFee = $routeCalculation['estimated_fare'];

        // Jika customer/form mengirimkan delivery_fee terverifikasi
        if ($request->has('delivery_fee') && (int) $request->input('delivery_fee') > 0) {
            $deliveryFee = (int) $request->input('delivery_fee');
        }

        // Hitung total harga barang
        $estimatedItemsPrice = 0;
        foreach ($cart->cartItems as $item) {
            $estimatedItemsPrice += ($item->product->final_price * $item->quantity);
        }

        // Skema DP: 50% barang + full ongkir
        $dpAmount = ($estimatedItemsPrice * 0.5) + $deliveryFee;

        // Informasi asal / rute
        $firstMerchant = $merchantLocations[0] ?? null;
        $originAddress = count($merchantNames) > 1
            ? implode(' ➔ ', array_map(function ($nm, $i) {
                return "Toko " . ($i + 1) . ": {$nm}";
            }, $merchantNames, array_keys($merchantNames)))
            : ($firstMerchant['address'] ?? 'Sesuai Toko');

        $isMultiStore = count($merchantNames) > 1;
        $category = $isMultiStore ? 'multi-stop' : ($firstMerchant && ($merchants->get($firstMerchant['id'])->type ?? '') === 'food' ? 'beli-antar' : 'toko-kirim');
        
        $descTitle = $isMultiStore
            ? "Pesanan Multi-Toko (" . count($merchantNames) . " Toko: " . implode(', ', $merchantNames) . ") - Rute berantai {$totalDistance} KM"
            : "Pesanan dari Katalog: " . ($merchantNames[0] ?? 'Toko');

        // Buat Order baru
        $order = Order::create([
            'customer_id' => Auth::guard('customer')->id(),
            'merchant_id' => !$isMultiStore ? ($firstMerchant['id'] ?? null) : null,
            'is_catalog_order' => true,
            'wilayah_id' => 11, // Malang Kota
            'category' => $category,
            'weight_category' => 'ringan',
            'description' => $descTitle,
            'origin_address' => $originAddress,
            'origin_lat' => $firstMerchant['lat'] ?? -7.9839,
            'origin_lng' => $firstMerchant['lng'] ?? 112.6214,
            'destination_address' => $destAddress,
            'destination_lat' => $destLat,
            'destination_lng' => $destLng,
            'recipient_name' => Auth::guard('customer')->user()->name,
            'recipient_phone' => Auth::guard('customer')->user()->phone_number,
            'estimated_fare' => $deliveryFee,
            'downpayment_amount' => $dpAmount,
            'cash_amount' => 0,
            'multi_stops' => $isMultiStore ? $multiStops : null,
            'status' => 'menunggu_tawaran',
        ]);

        // Buat Order Items
        foreach ($cart->cartItems as $item) {
            $merchantLabel = $item->product->merchant->name ?? 'Toko';
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'name' => "{$item->product->name} [{$merchantLabel}]",
                'quantity' => $item->quantity,
                'estimated_price' => $item->product->final_price,
            ]);
        }

        // Kosongkan keranjang
        $cart->cartItems()->delete();
        $cart->update(['merchant_id' => null]);

        $successMsg = $isMultiStore
            ? "Pesanan Multi-Toko (" . count($merchantNames) . " Toko) berhasil dibuat dengan rute berantai {$totalDistance} KM! Menunggu tawaran dari Jastiper."
            : "Pesanan Katalog berhasil dibuat! Menunggu tawaran dari Jastiper.";

        return redirect()->route('customer.dashboard')->with('success', $successMsg);
    }
}
