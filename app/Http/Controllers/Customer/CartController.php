<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Wilayah;
use App\Services\DistanceService;
use App\Services\InsuranceService;
use App\Services\TariffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Tampilkan halaman keranjang belanja terpadu (Katalog, Titip Beli Kustom, & Jasa Kurir/Antar)
     * beserta rute multi-stop berantai dan estimasi ongkir.
     */
    public function index(Request $request): View
    {
        $cart = Cart::with(['cartItems.product.merchant'])->firstOrCreate(
            ['customer_id' => Auth::guard('customer')->id()]
        );

        $catalogItems = $cart->catalogItems;
        $customBuyItems = $cart->customBuyItems;
        $deliveryServiceItems = $cart->deliveryServiceItems;

        // Kelompokkan item katalog berdasarkan merchant/toko
        $groupedCatalog = $catalogItems->groupBy(function ($item) {
            return $item->product->merchant_id ?? 0;
        });

        $merchantIds = $catalogItems->pluck('product.merchant_id')->filter()->unique()->values();
        $merchants = Merchant::whereIn('id', $merchantIds)->get()->keyBy('id');

        // Kumpulkan semua titik penjemputan/belanja dari semua layanan
        $allStops = $this->collectStops($cart, $merchants);

        // Tentukan default titik pengantaran / tujuan akhir customer
        // Prioritas:
        // 1. Parameter eksplisit request (lat, lng, address)
        // 2. Alamat pesanan terakhir customer jika memesan barang/makanan (lokasi rumah customer)
        // 3. Titik tujuan dari item jasa di keranjang (jika hanya pesan kurir)
        // 4. Area sekitar toko pertama
        $lastOrder = Order::where('customer_id', Auth::guard('customer')->id())
            ->whereNotNull('destination_lat')
            ->latest()
            ->first();

        $firstStop = $allStops[0] ?? null;
        $fallbackLat = $firstStop ? ($firstStop['lat'] + 0.012) : -7.4435;
        $fallbackLng = $firstStop ? ($firstStop['lng'] + 0.012) : 112.7085;
        $fallbackAddr = $firstStop ? "Area sekitar {$firstStop['name']}" : 'Alamat Pengantaran Customer';

        $firstItemWithDest = $cart->cartItems->filter(fn($it) => !empty($it->destination_lat))->first();

        if ($request->filled('lat') && $request->filled('lng')) {
            $destLat = (float) $request->input('lat');
            $destLng = (float) $request->input('lng');
            $destAddress = $request->input('address') ?: ($lastOrder?->destination_address ?? $fallbackAddr);
        } elseif ($lastOrder && $catalogItems->isNotEmpty()) {
            $destLat = (float) $lastOrder->destination_lat;
            $destLng = (float) $lastOrder->destination_lng;
            $destAddress = $lastOrder->destination_address ?: $fallbackAddr;
        } elseif ($firstItemWithDest) {
            $destLat = (float) $firstItemWithDest->destination_lat;
            $destLng = (float) $firstItemWithDest->destination_lng;
            $destAddress = $firstItemWithDest->destination_address ?: $fallbackAddr;
        } elseif ($lastOrder) {
            $destLat = (float) $lastOrder->destination_lat;
            $destLng = (float) $lastOrder->destination_lng;
            $destAddress = $lastOrder->destination_address ?: $fallbackAddr;
        } else {
            $destLat = $fallbackLat;
            $destLng = $fallbackLng;
            $destAddress = $fallbackAddr;
        }

        // Tentukan estimasi kategori beban muatan secara otomatis dari isi keranjang
        $weightCategory = $this->determineWeightCategory($cart);

        // Hitung rute berantai seluruh titik: Stop 1 -> Stop 2 -> ... -> Tujuan
        $routeCalculation = DistanceService::calculateMultiStoreRoute($allStops, $destLat, $destLng, [], $weightCategory);

        // Konfigurasi Proteksi Asuransi Barang
        $tariffService = app(TariffService::class);
        $insuranceEnabled = (bool) $tariffService->get('insurance_enabled', 1);
        $insuranceThreshold = (float) $tariffService->get('insurance_min_order_threshold', 200000);
        $insuranceFee = (float) $tariffService->get('insurance_fee', 2000);

        // Kalkulasi Total Harga Barang
        $totalCatalogPrice = $catalogItems->sum(fn($it) => ($it->product->final_price ?? 0) * $it->quantity);
        $totalCustomPrice = $customBuyItems->sum(fn($it) => ($it->estimated_price ?? 0) * $it->quantity);
        $totalItemsPrice = $totalCatalogPrice + $totalCustomPrice;
        $isInsuranceEligible = $insuranceEnabled && ($totalItemsPrice >= $insuranceThreshold);

        $estimatedFare = $routeCalculation['estimated_fare'];
        $initialInsuranceFee = $isInsuranceEligible ? $insuranceFee : 0;
        $dpAmount = ($totalItemsPrice * 0.5) + $estimatedFare + $initialInsuranceFee;
        $grandTotal = $totalItemsPrice + $estimatedFare + $initialInsuranceFee;

        $customer = Auth::guard('customer')->user();
        $savedAddresses = \App\Http\Controllers\DashboardController::getCustomerSavedAddresses($customer);

        return view('customer.cart.index', compact(
            'cart',
            'catalogItems',
            'customBuyItems',
            'deliveryServiceItems',
            'groupedCatalog',
            'merchants',
            'allStops',
            'destLat',
            'destLng',
            'destAddress',
            'routeCalculation',
            'totalCatalogPrice',
            'totalCustomPrice',
            'totalItemsPrice',
            'estimatedFare',
            'dpAmount',
            'grandTotal',
            'insuranceEnabled',
            'insuranceThreshold',
            'insuranceFee',
            'isInsuranceEligible',
            'weightCategory',
            'savedAddresses'
        ));
    }

    /**
     * Tambahkan produk katalog ke keranjang belanja (Mendukung Multi-Merchant / Toko Berbeda).
     */
    public function add(Request $request, Product $product): JsonResponse|RedirectResponse
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
                'item_type' => 'catalog',
                'category' => $product->category ?? 'beli-antar',
                'name' => $product->name,
                'quantity' => 1,
                'estimated_price' => $product->final_price,
            ]);
        }

        $totalCount = $cart->cartItems()->sum('quantity');

        // Hanya kembalikan JSON jika dipanggil via AJAX/Fetch dan tidak mengharapkan halaman HTML penuh
        if (($request->ajax() || $request->isJson() || $request->wantsJson()) && !$request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => "{$product->name} berhasil ditambahkan ke keranjang belanja.",
                'cart_count' => $totalCount,
            ]);
        }

        return redirect()->back()->with('success', "{$product->name} berhasil ditambahkan ke keranjang belanja.");
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

        $catalogItems = $cart->catalogItems;
        $merchantIds = $catalogItems->pluck('product.merchant_id')->filter()->unique()->values();
        $merchants = Merchant::whereIn('id', $merchantIds)->get()->keyBy('id');

        $allStops = $this->collectStops($cart, $merchants);
        $destAddr = $request->input('destination_address', 'Alamat Pengantaran Utama');
        $weightCategory = $this->determineWeightCategory($cart);
        $calculation = DistanceService::calculateMultiStoreRoute($allStops, $destLat, $destLng, [
            'name' => 'Alamat Pengantaran Utama',
            'address' => $destAddr,
        ], $weightCategory);

        $totalCatalogPrice = $catalogItems->sum(fn($it) => ($it->product->final_price ?? 0) * $it->quantity);
        $totalCustomPrice = $cart->customBuyItems->sum(fn($it) => ($it->estimated_price ?? 0) * $it->quantity);
        $totalItemsPrice = $totalCatalogPrice + $totalCustomPrice;
        $estimatedFare = $calculation['estimated_fare'];
        $dpAmount = ($totalItemsPrice * 0.5) + $estimatedFare;
        $grandTotal = $totalItemsPrice + $estimatedFare;

        return response()->json(array_merge($calculation, [
            'total_items_price' => $totalItemsPrice,
            'dp_amount' => $dpAmount,
            'grand_total' => $grandTotal,
            'weight_category' => $weightCategory,
            'weight_fare' => $calculation['weight_fare'] ?? 0,
            'weight_fare_formatted' => number_format($calculation['weight_fare'] ?? 0, 0, ',', '.'),
            'total_items_price_formatted' => number_format($totalItemsPrice, 0, ',', '.'),
            'estimated_fare_formatted' => number_format($estimatedFare, 0, ',', '.'),
            'dp_amount_formatted' => number_format($dpAmount, 0, ',', '.'),
            'grand_total_formatted' => number_format($grandTotal, 0, ',', '.'),
        ]));
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
        $cart->load(['cartItems.product.merchant', 'cartItems.merchant']);

        $unitPrice = ($cartItem->item_type === 'catalog' && $cartItem->product)
            ? (float) ($cartItem->product->final_price ?? 0)
            : (float) ($cartItem->estimated_price ?? 0);
        $itemSubtotal = $unitPrice * $cartItem->quantity;

        $catalogItems = $cart->catalogItems;
        $totalCatalogPrice = $catalogItems->sum(fn($it) => ($it->product->final_price ?? 0) * $it->quantity);
        $totalCustomPrice = $cart->customBuyItems->sum(fn($it) => ($it->estimated_price ?? 0) * $it->quantity);
        $totalItemsPrice = $totalCatalogPrice + $totalCustomPrice;

        // Recalculate weight category & fare
        $weightCategory = $this->determineWeightCategory($cart);

        $merchantIds = $catalogItems->pluck('product.merchant_id')->filter()->unique()->values();
        $merchants = Merchant::whereIn('id', $merchantIds)->get()->keyBy('id');
        $allStops = $this->collectStops($cart, $merchants);

        $firstItemWithDest = $cart->cartItems->filter(fn($it) => !empty($it->destination_lat))->first();
        $destLat = (float) $request->input('destination_lat', $firstItemWithDest?->destination_lat ?? -7.9839);
        $destLng = (float) $request->input('destination_lng', $firstItemWithDest?->destination_lng ?? 112.6214);
        $destAddr = $request->input('destination_address', 'Alamat Pengantaran Utama');

        $calculation = DistanceService::calculateMultiStoreRoute($allStops, $destLat, $destLng, [
            'name' => 'Alamat Pengantaran Utama',
            'address' => $destAddr,
        ], $weightCategory);

        $estimatedFare = $calculation['estimated_fare'];
        $grandTotal = $totalItemsPrice + $estimatedFare;
        $dpAmount = ($totalItemsPrice * 0.5) + $estimatedFare;

        return response()->json([
            'success' => true,
            'cart_item_id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'unit_price' => $unitPrice,
            'item_subtotal' => $itemSubtotal,
            'item_subtotal_formatted' => number_format($itemSubtotal, 0, ',', '.'),
            'total_items_price' => $totalItemsPrice,
            'total_items_price_formatted' => number_format($totalItemsPrice, 0, ',', '.'),
            'total_cart_count' => $cart->cartItems()->sum('quantity'),
            'weight_category' => $weightCategory,
            'weight_fare' => $calculation['weight_fare'] ?? 0,
            'weight_fare_formatted' => number_format($calculation['weight_fare'] ?? 0, 0, ',', '.'),
            'estimated_fare' => $estimatedFare,
            'estimated_fare_formatted' => number_format($estimatedFare, 0, ',', '.'),
            'grand_total' => $grandTotal,
            'grand_total_formatted' => number_format($grandTotal, 0, ',', '.'),
            'dp_amount' => $dpAmount,
            'dp_amount_formatted' => number_format($dpAmount, 0, ',', '.'),
            'route_calculation' => $calculation,
        ]);
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

        return redirect()->route('customer.cart.index')->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /**
     * Kosongkan seluruh isi keranjang belanja.
     */
    public function clear(): RedirectResponse
    {
        $cart = Cart::where('customer_id', Auth::guard('customer')->id())->first();
        if ($cart) {
            $cart->cartItems()->delete();
            $cart->update(['merchant_id' => null]);
        }

        return redirect()->route('customer.cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    /**
     * Checkout pesanan keranjang terpadu (Katalog, Titip Beli Kustom, & Jasa Kurir/Antar).
     */
    public function checkout(Request $request): RedirectResponse
    {
        $cart = Cart::with(['cartItems.product.merchant', 'cartItems.merchant'])
            ->where('customer_id', Auth::guard('customer')->id())
            ->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Keranjang masih kosong.');
        }

        $firstItemWithDest = $cart->cartItems->filter(fn($it) => !empty($it->destination_lat))->first();
        $destLat = $request->filled('destination_lat') ? (float) $request->input('destination_lat') : ($firstItemWithDest?->destination_lat ?? -7.9839);
        $destLng = $request->filled('destination_lng') ? (float) $request->input('destination_lng') : ($firstItemWithDest?->destination_lng ?? 112.6214);
        $destAddress = $request->input('destination_address') ?? $request->input('delivery_location') ?? ($firstItemWithDest?->destination_address ?? 'Alamat Pengantaran Customer');

        $catalogItems = $cart->catalogItems;
        $customBuyItems = $cart->customBuyItems;
        $deliveryServiceItems = $cart->deliveryServiceItems;

        $merchantIds = $catalogItems->pluck('product.merchant_id')->filter()->unique()->values();
        $merchants = Merchant::whereIn('id', $merchantIds)->get()->keyBy('id');

        $allStops = $this->collectStops($cart, $merchants);

        $recipientName = $request->input('recipient_name') ?: Auth::guard('customer')->user()->name;
        $recipientPhone = $request->input('recipient_phone') ?: Auth::guard('customer')->user()->phone_number;

        // Tentukan estimasi kategori beban muatan secara otomatis dari kuantitas & jenis item keranjang
        $weightCategory = $this->determineWeightCategory($cart);

        // Hitung rute multi-stop berantai (Stop 1 ➔ Stop 2 ➔ ... ➔ Alamat Tujuan)
        $routeCalculation = DistanceService::calculateMultiStoreRoute($allStops, $destLat, $destLng, [
            'name' => 'Alamat Pengantaran Utama',
            'address' => $destAddress,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
        ], $weightCategory);
        $totalDistance = $routeCalculation['total_distance_km'];
        $deliveryFee = $routeCalculation['estimated_fare'];
        $orderedStops = $routeCalculation['ordered_stops'];

        // Jika terverifikasi ada delivery fee dari form
        if ($request->filled('delivery_fee') && (int) $request->input('delivery_fee') > 0) {
            $deliveryFee = (int) $request->input('delivery_fee');
        }

        // Hitung total harga barang
        $totalCatalogPrice = $catalogItems->sum(fn($it) => ($it->product->final_price ?? 0) * $it->quantity);
        $totalCustomPrice = $customBuyItems->sum(fn($it) => ($it->estimated_price ?? 0) * $it->quantity);
        $estimatedItemsPrice = $totalCatalogPrice + $totalCustomPrice;

        // Konfigurasi & verifikasi proteksi asuransi pesanan
        $tariffService = app(TariffService::class);
        $insuranceEnabled = (bool) $tariffService->get('insurance_enabled', 1);
        $insuranceThreshold = (float) $tariffService->get('insurance_min_order_threshold', 200000);
        $insuranceFee = (float) $tariffService->get('insurance_fee', 2000);

        $hasInsurance = $request->boolean('has_insurance') && $insuranceEnabled && ($estimatedItemsPrice >= $insuranceThreshold);
        $appliedInsuranceFee = $hasInsurance ? $insuranceFee : 0;

        // Skema DP: 50% barang + full ongkir + premi asuransi (jika dipilih)
        $dpAmount = ($estimatedItemsPrice * 0.5) + $deliveryFee + $appliedInsuranceFee;

        // Tentukan kategori order & primary merchant
        $hasMultiDropoff = $routeCalculation['has_multi_dropoff'] ?? false;
        
        // Filter titik singgah perantara (tanpa menduplikat tujuan akhir yang sudah tercatat di destination_address)
        $intermediateStops = array_values(array_filter($orderedStops, function($st) {
            return ($st['type'] ?? '') !== 'destination' && ($st['id'] ?? '') !== 'final_destination';
        }));

        $isMultiStop = count($intermediateStops) > 1 || $hasMultiDropoff || count($allStops) > 1;

        // Susun struktur multi_stops untuk Order jika ada lebih dari 1 titik singgah / multi-dropoff
        $multiStops = null;
        if ($isMultiStop) {
            $multiStops = [];
            foreach ($intermediateStops as $idx => $st) {
                $multiStops[] = [
                    'stop_number' => $idx + 1,
                    'action' => $st['action'] ?? 'pickup',
                    'type' => $st['type'] ?? 'stop',
                    'merchant_id' => $st['merchant_id'] ?? null,
                    'name' => $st['name'] ?? '',
                    'badge' => $st['badge'] ?? '',
                    'address' => $st['address'] ?? '',
                    'lat' => $st['lat'],
                    'lng' => $st['lng'],
                    'recipient_name' => $st['recipient_name'] ?? null,
                    'recipient_phone' => $st['recipient_phone'] ?? null,
                    'is_perishable' => $st['is_perishable'] ?? false,
                    'notes' => $st['notes'] ?? null,
                ];
            }
        }

        if ($isMultiStop) {
            $category = 'multi-stop';
        } elseif ($catalogItems->isNotEmpty()) {
            $firstMerchant = $merchants->first();
            $category = ($firstMerchant && $firstMerchant->type === 'food') ? 'beli-antar' : 'toko-kirim';
        } elseif ($customBuyItems->isNotEmpty()) {
            $category = 'beli-antar';
        } elseif ($deliveryServiceItems->isNotEmpty()) {
            $firstDelivery = $deliveryServiceItems->first();
            $category = $firstDelivery->category ?: 'ambil-antar';
        } else {
            $category = 'beli-antar';
        }

        $primaryMerchantId = (!$isMultiStop && $catalogItems->isNotEmpty() && $merchants->count() === 1)
            ? $merchants->first()->id
            : null;

        // Jika bukan multi-stop dan tidak ada toko katalog, gunakan merchant_id dari item titip beli kustom
        if (!$primaryMerchantId && !$isMultiStop && $customBuyItems->isNotEmpty()) {
            $primaryMerchantId = $customBuyItems->first()->merchant_id;
        }

        // Tentukan origin address ringkas
        $firstStop = $orderedStops[0] ?? ($allStops[0] ?? null);
        $originAddress = $isMultiStop
            ? implode(' ➔ ', array_map(function($st, $i) {
                $actLabel = (($st['action'] ?? '') === 'dropoff') ? 'Antar' : 'Jemput/Beli';
                return "Stop " . ($i + 1) . " [{$actLabel}]: {$st['name']}";
            }, $multiStops, array_keys($multiStops)))
            : ($firstStop['address'] ?? 'Sesuai Rute');
        $originLat = $firstStop['lat'] ?? -7.9839;
        $originLng = $firstStop['lng'] ?? 112.6214;

        // Ambil reference photo dari item titip beli / jasa jika ada
        $refPhoto = $customBuyItems->first(fn($it) => !empty($it->reference_photo))?->reference_photo
            ?? $deliveryServiceItems->first(fn($it) => !empty($it->reference_photo))?->reference_photo;

        // Bangun ringkasan deskripsi pesanan yang jelas dan informatif (makanan/barang apa, dari toko/resto mana)
        $itemSummaries = [];
        $merchantNames = collect();

        foreach ($catalogItems as $item) {
            $mName = $item->product->merchant->name ?? null;
            if ($mName) $merchantNames->push($mName);
            $itemSummaries[] = "{$item->quantity}x {$item->product->name}";
        }

        foreach ($customBuyItems as $item) {
            if ($item->merchant) $merchantNames->push($item->merchant->name);
            $qty = $item->quantity ?: 1;
            $itemSummaries[] = "{$qty}x {$item->name}";
        }

        foreach ($deliveryServiceItems as $item) {
            $itemSummaries[] = "Antar: {$item->name}";
        }

        $uniqueMerchants = $merchantNames->unique()->values();
        $merchantPrefix = '';
        if ($uniqueMerchants->isNotEmpty()) {
            if ($uniqueMerchants->count() === 1) {
                $merchantPrefix = $uniqueMerchants->first() . ' – ';
            } else {
                $merchantPrefix = $uniqueMerchants->take(2)->implode(' & ') . ($uniqueMerchants->count() > 2 ? ' +' . ($uniqueMerchants->count() - 2) . ' toko' : '') . ' – ';
            }
        }

        $itemsText = '';
        if (!empty($itemSummaries)) {
            if (count($itemSummaries) <= 2) {
                $itemsText = implode(', ', $itemSummaries);
            } else {
                $itemsText = $itemSummaries[0] . ', ' . $itemSummaries[1] . ' (+' . (count($itemSummaries) - 2) . ' item lain)';
            }
        } else {
            $summaryParts = [];
            if ($catalogItems->isNotEmpty()) $summaryParts[] = $catalogItems->count() . ' Produk Toko';
            if ($customBuyItems->isNotEmpty()) $summaryParts[] = $customBuyItems->count() . ' Titip Beli';
            if ($deliveryServiceItems->isNotEmpty()) $summaryParts[] = $deliveryServiceItems->count() . ' Pengantaran';
            $itemsText = 'Pesanan (' . implode(', ', $summaryParts) . ')';
        }

        $freshnessTag = ($routeCalculation['has_freshness_optimization'] ?? false) ? ' [Freshness]' : '';
        $dropoffTag = ($routeCalculation['has_multi_dropoff'] ?? false) ? ' [Multi-Dropoff]' : '';

        // Jelas & transparan: Nama Resto/Toko + Nama Makanan/Barang + Jarak KM
        $descTitle = $merchantPrefix . $itemsText . " ({$totalDistance} KM){$freshnessTag}{$dropoffTag}";

        // Tentukan wilayah_id secara dinamis (Surabaya, Sidoarjo, Malang, dll)
        $destStr = strtolower(($destAddress ?? '') . ' ' . ($originAddress ?? ''));
        $targetWilayah = null;
        if (str_contains($destStr, 'surabaya')) {
            $targetWilayah = Wilayah::where('name', 'like', '%Surabaya%')->first();
        } elseif (str_contains($destStr, 'sidoarjo')) {
            $targetWilayah = Wilayah::where('name', 'like', '%Sidoarjo%')->first();
        } elseif (str_contains($destStr, 'gresik')) {
            $targetWilayah = Wilayah::where('name', 'like', '%Gresik%')->first();
        } elseif (str_contains($destStr, 'malang') || str_contains($destStr, 'batu')) {
            $targetWilayah = Wilayah::where('name', 'like', '%Malang%')->first();
        } else {
            $targetWilayah = Wilayah::where('name', 'like', '%Jawa Timur%')->first()
                ?? Wilayah::where('name', 'like', '%Malang%')->first()
                ?? Wilayah::where('is_active', true)->first();
        }
        $wilayahId = $targetWilayah?->id ?? 11;

        // Buat Order baru
        $order = Order::create([
            'customer_id' => Auth::guard('customer')->id(),
            'merchant_id' => $primaryMerchantId,
            'is_catalog_order' => $catalogItems->isNotEmpty(),
            'wilayah_id' => $wilayahId,
            'category' => $category,
            'weight_category' => $weightCategory,
            'description' => $descTitle,
            'reference_photo' => $refPhoto,
            'origin_address' => $originAddress,
            'origin_lat' => $originLat,
            'origin_lng' => $originLng,
            'destination_address' => $destAddress,
            'destination_lat' => $destLat,
            'destination_lng' => $destLng,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'estimated_fare' => $deliveryFee,
            'downpayment_amount' => $dpAmount,
            'cash_amount' => 0,
            'has_insurance' => $hasInsurance,
            'insurance_fee' => $appliedInsuranceFee,
            'multi_stops' => $multiStops,
            'status' => 'menunggu_tawaran',
        ]);

        // Catat premi asuransi ke buku kas pool Dana Asuransi jika proteksi aktif
        if ($hasInsurance && $appliedInsuranceFee > 0) {
            app(InsuranceService::class)->recordPremium($order, $appliedInsuranceFee);
        }

        // Simpan Order Items
        // 1. Produk Katalog (Simpan HPP Toko & Margin Markup Platform)
        $totalMarkupProfit = 0.0;
        foreach ($catalogItems as $item) {
            $merchantLabel = $item->product->merchant->name ?? 'Toko';
            $finalPrice = (float) $item->product->final_price;
            $hppPrice = (float) $item->product->effective_hpp_price;
            $markupAmount = max(0, round(($finalPrice - $hppPrice) * $item->quantity, 2));
            $totalMarkupProfit += $markupAmount;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'name' => "{$item->product->name} [{$merchantLabel}]",
                'quantity' => $item->quantity,
                'hpp_price' => $hppPrice,
                'markup_amount' => $markupAmount,
                'estimated_price' => $finalPrice,
                'actual_price' => $finalPrice,
                'notes' => null,
            ]);
        }

        if ($totalMarkupProfit > 0) {
            $order->update(['merchant_markup_profit' => $totalMarkupProfit]);
        }

        // 2. Titip Beli Kustom (Non-Katalog)
        foreach ($customBuyItems as $item) {
            $storeTag = $item->merchant ? " [{$item->merchant->name}]" : "";
            $productId = $item->product_id;

            // Jika belum terhubung ke produk tapi memiliki toko, daftarkan/hubungkan otomatis
            if (!$productId && $item->merchant_id) {
                $pPrice = (float) ($item->estimated_price ?: 0);
                $pName = trim(strtok($item->description ?: $item->name, "\n"));
                if (empty($pName)) {
                    $pName = 'Menu Titip ' . ($item->merchant->name ?? 'Toko');
                } elseif (strlen($pName) > 90) {
                    $pName = \Illuminate\Support\Str::limit($pName, 85);
                }

                $prod = \App\Models\Product::firstOrCreate(
                    ['merchant_id' => $item->merchant_id, 'name' => $pName],
                    [
                        'description' => $item->description,
                        'hpp_price' => $pPrice,
                        'estimated_price' => $pPrice,
                        'image' => $item->reference_photo,
                        'is_available' => true,
                        'is_auto_markup' => false,
                    ]
                );
                if (!empty($item->reference_photo) && empty($prod->image)) {
                    $prod->update(['image' => $item->reference_photo]);
                }
                $productId = $prod->id;
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $productId,
                'name' => "[Titip Beli] {$item->name}{$storeTag}",
                'quantity' => $item->quantity ?: 1,
                'estimated_price' => $item->estimated_price ?: 0,
                'actual_price' => null,
                'notes' => $item->description ?: ($item->origin_address ? "Beli di: {$item->origin_address}" : null),
            ]);
        }

        // 3. Jasa Pengantaran / Kurir
        foreach ($deliveryServiceItems as $item) {
            $serviceLabel = strtoupper(str_replace('-', ' ', $item->category ?: 'jasa'));
            $dropoffInfo = $item->recipient_name ? " ➔ Penerima: {$item->recipient_name} ({$item->recipient_phone})" : "";
            $destInfo = $item->destination_address ? " ➔ Drop-off: {$item->destination_address}" : "";
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => null,
                'name' => "[{$serviceLabel}] {$item->name}",
                'quantity' => 1,
                'estimated_price' => 0,
                'actual_price' => 0,
                'notes' => "Jemput di: {$item->origin_address}{$destInfo}{$dropoffInfo}" . ($item->description ? " | {$item->description}" : ""),
            ]);
        }

        // Log Aktivitas Pembuatan Pesanan
        \App\Services\ActivityLogger::logOrder(
            $order,
            'order_created',
            "Pesanan #{$order->id} Dibuat",
            "Customer " . Auth::guard('customer')->user()->name . " membuat pesanan: \"{$order->description}\" (Estimasi Ongkir: Rp " . number_format($order->estimated_fare, 0, ',', '.') . ")",
            Auth::guard('customer')->user(),
            'pesanan'
        );

        // Kosongkan keranjang
        $cart->cartItems()->delete();
        $cart->update(['merchant_id' => null]);

        $successMsg = $isMultiStop
            ? "Pesanan Terpadu Multi-Stop (" . count($multiStops) . " Titik Rute) berhasil dibuat dengan rute berantai {$totalDistance} KM! Menunggu tawaran dari Jastiper."
            : "Pesanan Terpadu berhasil dibuat! Menunggu tawaran dari Jastiper.";

        return redirect()->route('customer.dashboard')->with('success', $successMsg);
    }

    /**
     * Kumpulkan seluruh titik penjemputan/belanja dari item keranjang (Multi-Layanan & Multi-Merchant).
     */
    protected function collectStops(Cart $cart, $merchants): array
    {
        $stops = [];

        // Deteksi apakah item berupa makanan/minuman yang membutuhkan penanganan kesegaran khusus
        $isPerishableItem = function ($text, $merchant = null) {
            if ($merchant && ($merchant->type === 'food' || $merchant->category === 'food')) {
                return true;
            }
            $keywords = ['kopi', 'coffee', 'es ', 'ice', 'minuman', 'drink', 'boba', 'jus', 'juice', 'makanan', 'food', 'mie', 'nasi', 'ayam', 'bakso', 'soto', 'burger', 'pizza', 'roti', 'cake'];
            $lower = strtolower($text ?? '');
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    return true;
                }
            }
            return false;
        };

        // 1. Toko Katalog (dikelompokkan per merchant unik)
        $catalogItems = $cart->catalogItems;
        $groupedCatalog = $catalogItems->groupBy(fn($it) => $it->product->merchant_id ?? 0);
        $merchantIds = $catalogItems->pluck('product.merchant_id')->filter()->unique()->values();

        foreach ($merchantIds as $mId) {
            if ($merchants->has($mId)) {
                $m = $merchants->get($mId);
                $coords = $m->getCoordinates();
                $itemsForMerchant = $groupedCatalog->get($mId, collect());
                $itemDescriptions = $itemsForMerchant->map(fn($it) => "{$it->product->name} ({$it->quantity}x)")->implode(', ');
                $isFood = ($m->type === 'food') || $itemsForMerchant->contains(fn($it) => $isPerishableItem($it->product->name, $m));

                $stops[] = [
                    'id' => 'merchant_' . $m->id,
                    'type' => 'merchant',
                    'action' => 'pickup',
                    'merchant_id' => $m->id,
                    'name' => $m->name,
                    'badge' => $isFood ? '☕ Katalog Makanan/Minuman' : '🏪 Katalog Toko',
                    'address' => $m->address,
                    'lat' => (float) $coords['lat'],
                    'lng' => (float) $coords['lng'],
                    'notes' => $itemDescriptions,
                    'item_count' => $itemsForMerchant->count(),
                    'is_perishable' => $isFood,
                ];
            }
        }

        // 2. Titip Beli Kustom (Non-Katalog)
        foreach ($cart->customBuyItems as $cItem) {
            $merchantObj = $cItem->merchant;
            $displayName = $merchantObj ? "Beli: {$cItem->name} [{$merchantObj->name}]" : "Beli: {$cItem->name}";
            $badgeTitle = $merchantObj ? "🏪 {$merchantObj->name} (Toko Baru)" : '📝 Titip Beli Kustom';
            $addr = $cItem->origin_address ?: ($merchantObj ? $merchantObj->address : 'Titik Pembelian Kustom');
            $isFood = ($merchantObj && $merchantObj->type === 'food') || $isPerishableItem($cItem->name . ' ' . $cItem->description, $merchantObj);

            $stops[] = [
                'id' => 'custom_' . $cItem->id,
                'type' => 'custom_buy',
                'action' => 'pickup',
                'merchant_id' => $cItem->merchant_id,
                'cart_item_id' => $cItem->id,
                'name' => $displayName,
                'badge' => $isFood ? '☕ Titip Beli Kuliner' : $badgeTitle,
                'address' => $addr,
                'lat' => (float) ($cItem->origin_lat ?? ($merchantObj?->latitude ?? -7.9839)),
                'lng' => (float) ($cItem->origin_lng ?? ($merchantObj?->longitude ?? 112.6214)),
                'notes' => $cItem->description ?: "Est. Rp " . number_format($cItem->estimated_price ?? 0, 0, ',', '.'),
                'item_count' => $cItem->quantity ?: 1,
                'is_perishable' => $isFood,
            ];
        }

        // 3. Jasa Pengantaran / Kurir / Multi-stop
        foreach ($cart->deliveryServiceItems as $dItem) {
            $serviceName = ucwords(str_replace('-', ' ', $dItem->category ?: 'jasa'));

            // Periksa jika ada titik pemberhentian berantai (multi-stop data)
            if (!empty($dItem->stops_data) && is_array($dItem->stops_data)) {
                foreach ($dItem->stops_data as $sIdx => $stopInfo) {
                    $stopAddr = $stopInfo['address'] ?? "Titik Singgah " . ($sIdx + 1);
                    $stopLat = !empty($stopInfo['lat']) ? (float)$stopInfo['lat'] : (float)($dItem->origin_lat ?? -7.9839);
                    $stopLng = !empty($stopInfo['lng']) ? (float)$stopInfo['lng'] : (float)($dItem->origin_lng ?? 112.6214);
                    $action = $stopInfo['action'] ?? 'pickup';
                    $isDropoff = ($action === 'dropoff');

                    $badge = $isDropoff
                        ? "🔵 Antar Singgah (" . ($sIdx + 1) . ")"
                        : "🟢 Jemput Singgah (" . ($sIdx + 1) . ")";

                    $title = $isDropoff
                        ? "Drop-off " . ($sIdx + 1) . (!empty($stopInfo['recipient_name']) ? ": " . $stopInfo['recipient_name'] : ": " . \Illuminate\Support\Str::limit($stopAddr, 25))
                        : "Stop " . ($sIdx + 1) . ": " . \Illuminate\Support\Str::limit($stopAddr, 25);

                    $stops[] = [
                        'id' => 'delivery_' . $dItem->id . '_stop_' . $sIdx,
                        'type' => $isDropoff ? 'dropoff' : 'delivery_service',
                        'action' => $action,
                        'cart_item_id' => $dItem->id,
                        'name' => $title,
                        'badge' => $badge,
                        'address' => $stopAddr,
                        'lat' => $stopLat,
                        'lng' => $stopLng,
                        'recipient_name' => $stopInfo['recipient_name'] ?? null,
                        'recipient_phone' => $stopInfo['recipient_phone'] ?? null,
                        'notes' => $stopInfo['notes'] ?? ($dItem->description ?? ''),
                        'item_count' => 1,
                        'is_perishable' => false,
                    ];
                }
            } else {
                // Item jasa biasa (ambil-antar, kirim dokumen, titip pihak ketiga, dll)
                $stops[] = [
                    'id' => 'delivery_' . $dItem->id . '_pickup',
                    'type' => 'delivery_service',
                    'action' => 'pickup',
                    'cart_item_id' => $dItem->id,
                    'name' => 'Jemput: ' . $dItem->name,
                    'badge' => "📦 {$serviceName} (Jemput)",
                    'address' => $dItem->origin_address ?: 'Titik Penjemputan',
                    'lat' => (float) ($dItem->origin_lat ?? -7.9839),
                    'lng' => (float) ($dItem->origin_lng ?? 112.6214),
                    'notes' => $dItem->description ?: 'Pengambilan barang/dokumen',
                    'item_count' => 1,
                    'is_perishable' => false,
                ];

                // Jika item memiliki alamat drop-off spesifik penerima
                if (!empty($dItem->destination_address)) {
                    $stops[] = [
                        'id' => 'delivery_' . $dItem->id . '_dropoff',
                        'type' => 'delivery_service_dropoff',
                        'action' => 'dropoff',
                        'cart_item_id' => $dItem->id,
                        'name' => 'Antar: ' . ($dItem->recipient_name ?: $dItem->name),
                        'badge' => "🔵 Antar Penerima",
                        'address' => $dItem->destination_address,
                        'lat' => (float) ($dItem->destination_lat ?? -7.9839),
                        'lng' => (float) ($dItem->destination_lng ?? 112.6214),
                        'recipient_name' => $dItem->recipient_name,
                        'recipient_phone' => $dItem->recipient_phone,
                        'notes' => "Untuk: " . ($dItem->recipient_name ? "{$dItem->recipient_name} ({$dItem->recipient_phone})" : "Penerima") . ($dItem->description ? " - {$dItem->description}" : ""),
                        'item_count' => 1,
                        'is_perishable' => false,
                    ];
                }
            }
        }

        return $stops;
    }

    /**
     * Tentukan kategori beban muatan secara cerdas & otomatis berdasarkan isi keranjang.
     * Aturan:
     * - 'berat': Jika ada item yang dipilih 'berat' ATAU total kuantitas barang >= 12 item/cup (est. >= 7-10 kg)
     * - 'sedang': Jika ada item 'sedang' ATAU total kuantitas barang 5..11 item/cup (est. 3-6 kg)
     * - 'ringan': 1..4 item/cup (est. < 3 kg)
     */
    public function determineWeightCategory(Cart $cart): string
    {
        $cartItems = $cart->cartItems ?? collect();
        if ($cartItems->isEmpty()) {
            return 'ringan';
        }

        // 1. Cek jika ada item kustom/jasa yang secara spesifik diset 'berat'
        if ($cartItems->contains(fn($it) => $it->weight_category === 'berat')) {
            return 'berat';
        }

        // 2. Cek jika ada item kustom/jasa yang diset 'sedang'
        $hasExplicitSedang = $cartItems->contains(fn($it) => $it->weight_category === 'sedang');

        // 3. Hitung akumulasi total kuantitas barang fisik (produk katalog + kustom)
        $totalQuantity = (int) $cartItems->sum('quantity');

        if ($totalQuantity >= 12) {
            return 'berat';
        }

        if ($totalQuantity >= 5 || $hasExplicitSedang) {
            return 'sedang';
        }

        return 'ringan';
    }
}
