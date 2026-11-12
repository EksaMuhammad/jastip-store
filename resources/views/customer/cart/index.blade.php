@extends('layouts.support')

@section('title', 'Keranjangku - JastipKuy')

@section('styles')
<!-- Leaflet CSS & JS for Multi-Stop Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
    .custom-stop-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        background: #E11D48;
        color: white;
        font-weight: 800;
        font-size: 11px;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(225, 29, 72, 0.4);
    }
    .custom-dest-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background: #2563EB;
        color: white;
        font-weight: 800;
        font-size: 12px;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.4);
    }
</style>
@endsection

@section('content')
@php
    $resolveImageUrl = function($path) {
        if (empty($path)) return null;
        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }
        if (\Illuminate\Support\Str::startsWith($path, '/storage/')) {
            return asset(ltrim($path, '/'));
        }
        if (\Illuminate\Support\Str::startsWith($path, 'storage/')) {
            return asset($path);
        }
        return asset('storage/' . ltrim($path, '/'));
    };
@endphp
<div class="min-h-screen bg-[#F6F7F9] pb-32">
    
    <!-- Top Header (Inspired by Image 3: ← Keranjangku) -->
    <div class="bg-white sticky top-0 z-40 px-4 h-14 border-b border-slate-100 shadow-xs flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.dashboard') }}" class="text-rose-600 p-1 -ml-1 rounded-full hover:bg-rose-50 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="font-extrabold text-base text-slate-900 tracking-tight">Keranjangku</h1>
        </div>

        @if($cart && !$cart->cartItems->isEmpty())
            <form action="{{ route('customer.cart.clear') }}" method="POST" onsubmit="return confirm('Kosongkan seluruh keranjang?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-bold text-slate-400 hover:text-rose-600 transition">
                    Hapus Semua
                </button>
            </form>
        @endif
    </div>

    <!-- Alert Flash Notifications with Guaranteed Auto-Dismiss & Close Button -->
    @if(session('success'))
    <div id="flash-success-alert" class="max-w-md mx-auto px-4 pt-3 transition-all duration-300">
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span class="truncate leading-snug">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="dismissFlashNotice('flash-success-alert')" class="text-emerald-500 hover:text-emerald-800 p-1 rounded-lg transition shrink-0" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div id="flash-error-alert" class="max-w-md mx-auto px-4 pt-3 transition-all duration-300">
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-semibold flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span class="truncate leading-snug">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="dismissFlashNotice('flash-error-alert')" class="text-rose-400 hover:text-rose-700 p-1 rounded-lg transition shrink-0" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>
    @endif

    @if(!$cart || $cart->cartItems->isEmpty())
    <!-- Empty Cart State -->
    <div class="flex flex-col items-center justify-center py-20 px-4 max-w-sm mx-auto text-center">
        <div class="w-20 h-20 bg-rose-50 border border-rose-100 rounded-3xl flex items-center justify-center text-rose-500 mb-4 shadow-inner">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
        </div>
        <h3 class="font-extrabold text-slate-900 text-base mb-1">Keranjangmu masih kosong</h3>
        <p class="text-xs text-slate-400 mb-6 max-w-xs leading-relaxed">
            Yuk jelajahi kuliner favorit atau pesan jasa titip belanja praktis sekarang!
        </p>
        <div class="flex flex-col w-full gap-2.5">
            <a href="{{ route('customer.food.index') }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl transition shadow-md shadow-rose-600/20">
                Eksplor Kuliner Sekitarmu
            </a>
            <a href="{{ route('customer.orders.create') }}" class="w-full bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs py-3 rounded-xl border border-slate-200 transition">
                Pesan Titip Beli / Antar Barang
            </a>
        </div>
    </div>

    @else

    <div class="max-w-md mx-auto px-3.5 sm:px-4 pt-3.5 space-y-3.5">

        <!-- Form Checkout Utama -->
        <form action="{{ route('customer.cart.checkout') }}" method="POST" id="checkout-form" class="space-y-3.5">
            @csrf

            <!-- 1. MERCHANTS / STORE GROUPS (Exact Reference Style: 🏪 Burger Bangor - Tlogomas >) -->
            @if($groupedCatalog->isNotEmpty())
                <div class="hidden">Produk Katalog Toko</div>
                @foreach($groupedCatalog as $merchantId => $items)
                    @php
                        $merchant = $merchants->get($merchantId);
                        $merchantName = $merchant ? $merchant->name : 'Toko Rekanan Jastip';
                        $mCoords = $merchant ? $merchant->getCoordinates() : null;
                        $mLat = (float) ($mCoords['lat'] ?? 0);
                        $mLng = (float) ($mCoords['lng'] ?? 0);
                        $merchantDistKm = ($mLat != 0 && $mLng != 0 && $destLat != 0 && $destLng != 0) 
                            ? round(\App\Services\DistanceService::haversine($mLat, $mLng, $destLat, $destLng), 1) 
                            : null;
                        $merchantLogo = $merchant ? $resolveImageUrl($merchant->image) : null;
                    @endphp
                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3">
                        <!-- Merchant Header -->
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <a href="{{ $merchant ? route('customer.merchant.show', $merchant->id) : '#' }}" class="flex items-center gap-2.5 group min-w-0">
                                <div class="w-7 h-7 rounded-lg overflow-hidden bg-slate-100 border border-slate-200/80 shrink-0 flex items-center justify-center">
                                    @if($merchantLogo)
                                        <img src="{{ $merchantLogo }}" alt="{{ $merchantName }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-sm">🏪</span>
                                    @endif
                                </div>
                                <h3 class="font-bold text-xs sm:text-sm text-slate-800 group-hover:text-rose-600 transition truncate">
                                    {{ $merchantName }}
                                </h3>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-rose-600 group-hover:translate-x-0.5 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($merchantDistKm !== null)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                                        <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                        {{ $merchantDistKm }} KM
                                    </span>
                                @endif
                                <span class="text-[10px] font-bold text-slate-400 hover:text-slate-600 cursor-pointer">
                                    Ubah
                                </span>
                            </div>
                        </div>

                        <!-- Product Items in Merchant -->
                        <div class="space-y-3 divide-y divide-slate-50">
                            @foreach($items as $item)
                                @php
                                    $price = $item->product->final_price ?? $item->estimated_price;
                                    $productImg = $resolveImageUrl($item->product->image ?? ($merchant->image ?? null));
                                @endphp
                                <div class="pt-2.5 first:pt-0 flex items-start gap-3">
                                    <!-- Product Image -->
                                    <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($productImg)
                                            <img src="{{ $productImg }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-rose-50 flex items-center justify-center text-rose-500 font-black text-xs\'>JK</div>';">
                                        @else
                                            <div class="w-full h-full bg-rose-50 flex items-center justify-center text-rose-500 font-bold text-xs">
                                                JK
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Product Details -->
                                    <div class="flex-1 min-w-0 flex flex-col justify-between self-stretch">
                                        <div class="flex items-start justify-between gap-1">
                                            <div>
                                                <h4 class="font-bold text-xs text-slate-800 leading-snug line-clamp-2">
                                                    {{ $item->product->name }}
                                                </h4>
                                                @if($merchantDistKm !== null)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 mt-0.5">
                                                        <svg class="w-3 h-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                        <span>{{ $merchantDistKm }} KM dari toko</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <button type="button" onclick="removeCartItem({{ $item->id }})" class="text-slate-300 hover:text-rose-500 p-0.5 shrink-0 transition" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between mt-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-500 font-semibold" id="item-multiplier-{{ $item->id }}">{{ $item->quantity }} x</span>
                                                <span class="text-rose-600 font-extrabold text-xs sm:text-sm" id="item-subtotal-{{ $item->id }}">
                                                    Rp{{ number_format($price * $item->quantity, 0, ',', '.') }}
                                                </span>
                                            </div>

                                            <!-- Qty Stepper -->
                                            <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50/50">
                                                <button type="button" onclick="changeCartItemQty({{ $item->id }}, -1)" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:bg-slate-200 text-xs font-bold transition">
                                                    -
                                                </button>
                                                <span class="w-7 text-center text-xs font-bold text-slate-800" id="item-qty-{{ $item->id }}">{{ $item->quantity }}</span>
                                                <button type="button" onclick="changeCartItemQty({{ $item->id }}, 1)" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:bg-slate-200 text-xs font-bold transition">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Quick Add More Menu from this Merchant -->
                        <div class="pt-2 border-t border-slate-50 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-medium">Mau menu lain dari toko ini?</span>
                            <a href="{{ $merchant ? route('customer.merchant.show', $merchant->id) : route('customer.food') }}" class="text-[10px] font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Tambah Menu</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- 2. TITIP BELI KUSTOM (NON-KATALOG) -->
            @if($customBuyItems->isNotEmpty())
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base shrink-0">🛍️</span>
                            <h3 class="font-bold text-xs sm:text-sm text-slate-800 truncate">Titip Beli Kustom (Non-Katalog)</h3>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400">Toko Bebas</span>
                    </div>

                    <div class="space-y-3 divide-y divide-slate-50">
                        @foreach($customBuyItems as $item)
                            @php
                                $cLat = (float) ($item->origin_lat ?? ($item->merchant->latitude ?? 0));
                                $cLng = (float) ($item->origin_lng ?? ($item->merchant->longitude ?? 0));
                                $cDistKm = ($cLat != 0 && $cLng != 0 && $destLat != 0 && $destLng != 0) 
                                    ? round(\App\Services\DistanceService::haversine($cLat, $cLng, $destLat, $destLng), 1) 
                                    : null;
                                $customImg = $resolveImageUrl($item->reference_photo ?? ($item->merchant->image ?? ($item->product->image ?? null)));
                            @endphp
                            <div class="pt-2.5 first:pt-0 flex items-start gap-3">
                                <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden shrink-0 flex items-center justify-center">
                                    @if($customImg)
                                        <img src="{{ $customImg }}" alt="{{ $item->name }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-amber-50 flex items-center justify-center text-amber-500 font-black text-xs\'>TB</div>';">
                                    @else
                                        <div class="w-full h-full bg-amber-50 flex items-center justify-center text-amber-500 font-bold text-xs">
                                            TB
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 flex flex-col justify-between self-stretch">
                                    <div class="flex items-start justify-between gap-1">
                                        <div>
                                            <h4 class="font-bold text-xs text-slate-800 leading-snug">{{ $item->name }}</h4>
                                            <p class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">Beli di: {{ $item->origin_address }}</p>
                                            @if($cDistKm !== null)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 mt-0.5">
                                                    <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                    <span>{{ $cDistKm }} KM ke titik antar</span>
                                                </span>
                                            @endif
                                        </div>
                                        <button type="button" onclick="removeCartItem({{ $item->id }})" class="text-slate-300 hover:text-rose-500 p-0.5 shrink-0 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-slate-500 font-semibold" id="item-multiplier-{{ $item->id }}">{{ $item->quantity }} x</span>
                                            <span class="text-rose-600 font-extrabold text-xs sm:text-sm" id="item-subtotal-{{ $item->id }}">
                                                Rp{{ number_format($item->estimated_price * $item->quantity, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50/50">
                                            <button type="button" onclick="changeCartItemQty({{ $item->id }}, -1)" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:bg-slate-200 text-xs font-bold transition">-</button>
                                            <span class="w-7 text-center text-xs font-bold text-slate-800" id="item-qty-{{ $item->id }}">{{ $item->quantity }}</span>
                                            <button type="button" onclick="changeCartItemQty({{ $item->id }}, 1)" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:bg-slate-200 text-xs font-bold transition">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 3. JASA PENGANTARAN / KURIR JASTIP -->
            @if($deliveryServiceItems->isNotEmpty())
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base shrink-0">📦</span>
                            <h3 class="font-bold text-xs sm:text-sm text-slate-800 truncate">Jasa Pengantaran / Kurir Jastip</h3>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400">Ambil & Antar</span>
                    </div>

                    <div class="space-y-3 divide-y divide-slate-50">
                        @foreach($deliveryServiceItems as $item)
                            @php
                                $dLat = (float) ($item->origin_lat ?? 0);
                                $dLng = (float) ($item->origin_lng ?? 0);
                                $targetLat = (float) ($item->destination_lat ?: $destLat);
                                $targetLng = (float) ($item->destination_lng ?: $destLng);
                                $dDistKm = ($dLat != 0 && $dLng != 0 && $targetLat != 0 && $targetLng != 0) 
                                    ? round(\App\Services\DistanceService::haversine($dLat, $dLng, $targetLat, $targetLng), 1) 
                                    : null;
                                $deliveryImg = $resolveImageUrl($item->reference_photo);
                            @endphp
                            <div class="pt-2.5 first:pt-0 flex items-start gap-3">
                                <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden shrink-0 flex items-center justify-center">
                                    @if($deliveryImg)
                                        <img src="{{ $deliveryImg }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full bg-blue-50 flex items-center justify-center text-blue-500 font-black text-xs\'>JP</div>';">
                                    @else
                                        <div class="w-full h-full bg-blue-50 flex items-center justify-center text-blue-500 font-bold text-xs">
                                            JP
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 flex flex-col justify-between self-stretch">
                                    <div class="flex items-start justify-between gap-1">
                                        <div>
                                            <h4 class="font-bold text-xs text-slate-800 leading-snug">{{ $item->name }}</h4>
                                            <p class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">Jemput: {{ $item->origin_address }}</p>
                                            @if($item->destination_address)
                                                <p class="text-[10px] text-slate-500 line-clamp-1 mt-0.5 font-medium">Antar: {{ $item->destination_address }} ({{ $item->recipient_name }})</p>
                                            @endif
                                            @if($dDistKm !== null)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 mt-0.5">
                                                    <svg class="w-3 h-3 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                    <span>{{ $dDistKm }} KM jarak antar</span>
                                                </span>
                                            @endif
                                        </div>
                                        <button type="button" onclick="removeCartItem({{ $item->id }})" class="text-slate-300 hover:text-rose-500 p-0.5 shrink-0 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">
                                            Tarif Mengikuti Jarak Rute
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Pintasan Tambah Pesanan / Toko / Layanan Lain (Multi-Stop) -->
            <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5">
                        <span>➕</span> Tambah Pesanan / Layanan Lain
                    </span>
                    <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                        Multi-Stop Hemat
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('customer.food.nearby') }}" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50/70 hover:bg-rose-50/60 hover:border-rose-200 border border-slate-200/60 transition group">
                        <span class="text-xl shrink-0">🏪</span>
                        <div class="min-w-0">
                            <p class="font-extrabold text-[11px] text-slate-800 group-hover:text-rose-600 leading-tight truncate">Resto Sekitarmu</p>
                            <p class="text-[9px] text-slate-400 leading-tight">Tambah resto lain</p>
                        </div>
                    </a>
                    <a href="{{ route('customer.orders.create') }}?cat=ambil-antar" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50/70 hover:bg-sky-50/60 hover:border-sky-200 border border-slate-200/60 transition group">
                        <span class="text-xl shrink-0">📦</span>
                        <div class="min-w-0">
                            <p class="font-extrabold text-[11px] text-slate-800 group-hover:text-sky-600 leading-tight truncate">Kirim Dokumen/Barang</p>
                            <p class="text-[9px] text-slate-400 leading-tight">Jasa antar kurir</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- 4. RUTE PENGANTARAN BERANTAI & SMART FRESHNESS PRIORITY (Clean Compact Card) -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0 animate-pulse"></span>
                        <h3 class="font-extrabold text-xs text-slate-900 tracking-tight" id="route-header-title">
                            {{ ($routeCalculation['is_multi_store'] ?? (count($allStops) > 1)) ? 'Rute Pengantaran Berantai' : 'Rute Pengantaran' }}
                        </h3>
                    </div>
                    <span id="route-total-distance-badge" class="text-[10px] font-bold bg-rose-50 text-rose-600 px-2 py-0.5 rounded-full border border-rose-100">
                        {{ $routeCalculation['total_distance_km'] ?? '0' }} KM Total
                    </span>
                </div>

                <div class="text-[11px] font-semibold text-slate-500">
                    {{ count($allStops) }} Titik Penjemputan / Belanja
                </div>

                <!-- Sleek Minimalist Freshness Priority Pill -->
                @if($routeCalculation['has_freshness_optimization'] ?? false)
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2.5 flex items-center gap-2 text-emerald-800 text-[11px] font-bold">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>Smart Freshness Priority: Makanan & Minuman diantar segar</span>
                </div>
                @endif

                <!-- Collapsible Map Toggle -->
                <div x-data="{ openMap: false }" class="pt-1">
                    <button type="button" @click="openMap = !openMap; $nextTick(() => { if (cartMap) cartMap.invalidateSize(); })" class="w-full py-2 bg-slate-50 hover:bg-slate-100 rounded-xl text-slate-600 text-xs font-bold transition flex items-center justify-center gap-1.5 border border-slate-100">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                        <span x-text="openMap ? 'Sembunyikan Peta Rute' : 'Tampilkan Peta Rute Multi-Stop'"></span>
                    </button>
                    <div x-show="openMap" x-cloak class="mt-2.5 rounded-xl overflow-hidden border border-slate-200 h-48 relative">
                        <div id="cart-route-map" class="w-full h-full"></div>
                    </div>
                </div>
            </div>

            <!-- 5. ALAMAT PENGANTARAN UTAMA -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-rose-600">📍</span>
                        <h3 class="font-extrabold text-xs sm:text-sm text-slate-900">Alamat Pengantaran Utama</h3>
                    </div>
                    <span id="manual-status-badge" class="hidden text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                        ✓ Terverifikasi
                    </span>
                </div>

                <!-- Tabs: GPS vs Alamat Tersimpan vs Manual -->
                @php
                    $savedAddresses = $savedAddresses ?? \App\Http\Controllers\DashboardController::getCustomerSavedAddresses(Auth::guard('customer')->user());
                @endphp
                <div class="grid grid-cols-3 gap-1 p-1 bg-slate-100 rounded-xl text-xs font-bold">
                    <button type="button" id="tab-btn-gps" onclick="switchLocationMode('gps')" class="py-1.5 px-1.5 rounded-lg transition flex items-center justify-center gap-1 bg-white text-rose-600 shadow-2xs">
                        <span>📍 Lokasi GPS</span>
                    </button>
                    <button type="button" id="tab-btn-saved" onclick="switchLocationMode('saved')" class="py-1.5 px-1.5 rounded-lg transition flex items-center justify-center gap-1 text-slate-500 hover:text-slate-800">
                        <span>📋 Tersimpan</span>
                        @if(!empty($savedAddresses) && count($savedAddresses) > 0)
                            <span class="bg-rose-500 text-white text-[9px] font-extrabold px-1.5 py-0.2 rounded-full">{{ count($savedAddresses) }}</span>
                        @endif
                    </button>
                    <button type="button" id="tab-btn-manual" onclick="switchLocationMode('manual')" class="py-1.5 px-1.5 rounded-lg transition flex items-center justify-center gap-1 text-slate-500 hover:text-slate-800">
                        <span>🔍 Tempat Lain</span>
                    </button>
                </div>

                <!-- GPS Panel -->
                <div id="panel-location-gps" class="space-y-2">
                    <div class="relative">
                        <textarea id="delivery_location_gps" rows="2" class="w-full border border-slate-200 rounded-xl text-xs p-2.5 bg-slate-50/50 focus:ring-2 focus:ring-rose-500 focus:outline-none" placeholder="Alamat otomatis via GPS...">{{ $destAddress }}</textarea>
                    </div>
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <button type="button" onclick="detectDeviceLocation()" class="text-[10px] font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span id="btn-gps-refresh-label">Perbarui Lokasi GPS</span>
                        </button>
                    </div>

                    @if(!empty($savedAddresses) && count($savedAddresses) > 0)
                        <!-- Quick Chips Alamat Tersimpan di GPS Panel -->
                        <div class="flex items-center gap-1.5 flex-wrap pt-1 border-t border-slate-100">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Pilih Cepat:</span>
                            @foreach(array_slice($savedAddresses, 0, 3) as $sAddr)
                                <button type="button" 
                                        onclick="selectSavedCartAddress('{{ addslashes($sAddr['id'] ?? '') }}', '{{ addslashes($sAddr['address'] ?? '') }}', {{ (float)($sAddr['latitude'] ?? 0) }}, {{ (float)($sAddr['longitude'] ?? 0) }})"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-lg border border-slate-200 bg-white hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 transition cursor-pointer text-slate-700 shadow-2xs">
                                    <span>{{ stripos($sAddr['label'] ?? '', 'kantor') !== false ? '🏢' : '🏠' }}</span>
                                    <span>{{ $sAddr['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Panel Alamat Tersimpan -->
                <div id="panel-location-saved" class="space-y-2 hidden">
                    @if(!empty($savedAddresses) && count($savedAddresses) > 0)
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-0.5">
                            @foreach($savedAddresses as $sAddr)
                                <div onclick="selectSavedCartAddress('{{ addslashes($sAddr['id'] ?? '') }}', '{{ addslashes($sAddr['address'] ?? '') }}', {{ (float)($sAddr['latitude'] ?? 0) }}, {{ (float)($sAddr['longitude'] ?? 0) }})"
                                     data-address-id="{{ $sAddr['id'] ?? '' }}"
                                     class="saved-cart-address-card p-3 rounded-xl border border-slate-200 bg-white hover:border-rose-300 hover:bg-rose-50/40 transition cursor-pointer flex items-start justify-between gap-2.5">
                                    <div class="flex items-start gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 text-sm font-bold mt-0.5">
                                            @if(stripos($sAddr['label'] ?? '', 'kantor') !== false) 🏢
                                            @elseif(stripos($sAddr['label'] ?? '', 'kos') !== false) 🏠
                                            @elseif(stripos($sAddr['label'] ?? '', 'toko') !== false) 🏪
                                            @else 📍 @endif
                                        </div>
                                        <div class="min-w-0 space-y-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <h4 class="font-extrabold text-xs text-slate-800">{{ $sAddr['label'] }}</h4>
                                                <span class="saved-check-badge hidden text-[9px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 px-1 rounded">✓ Terpilih</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed">{{ $sAddr['address'] }}</p>
                                            @if(!empty($sAddr['notes']))
                                                <p class="text-[10px] text-slate-400 italic">Patokan: {{ $sAddr['notes'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" class="shrink-0 text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg transition mt-0.5">
                                        Pilih
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <div class="pt-1 text-center">
                            <a href="{{ route('customer.address') }}" class="text-[11px] font-bold text-rose-600 hover:underline">
                                + Kelola / Tambah Alamat Baru
                            </a>
                        </div>
                    @else
                        <div class="p-4 border border-dashed border-slate-200 rounded-xl text-center space-y-2">
                            <p class="text-xs text-slate-500 font-medium">Belum ada alamat tersimpan di akun Anda.</p>
                            <a href="{{ route('customer.address') }}" class="inline-block bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition shadow-xs">
                                + Tambah Alamat Sekarang
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Manual Panel -->
                <div id="panel-location-manual" class="space-y-2 hidden">
                    <div class="flex gap-1.5">
                        <input type="text" id="manual_search_input" class="w-full border border-slate-200 rounded-xl text-xs px-3 py-2 focus:ring-2 focus:ring-rose-500 focus:outline-none" placeholder="Cari jalan, gedung, atau daerah...">
                        <button type="button" id="btn-search-manual" onclick="searchManualAddress()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-3 py-2 rounded-xl shrink-0 transition">
                            Cari
                        </button>
                    </div>
                    <span id="search-feedback-text" class="text-[10px] text-slate-400 block"></span>
                    <textarea id="delivery_location_manual" rows="2" class="w-full border border-slate-200 rounded-xl text-xs p-2.5 bg-slate-50/50 focus:ring-2 focus:ring-rose-500 focus:outline-none" placeholder="Detail alamat atau patokan..."></textarea>
                </div>

                <!-- Recipient Info -->
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Nama Penerima</label>
                        <input type="text" name="recipient_name" id="recipient_name" value="{{ Auth::guard('customer')->user()->name }}" class="w-full border border-slate-200 rounded-xl text-xs px-2.5 py-2 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1">No. Handphone / WA</label>
                        <input type="tel" name="recipient_phone" id="recipient_phone" value="{{ Auth::guard('customer')->user()->phone_number }}" class="w-full border border-slate-200 rounded-xl text-xs px-2.5 py-2 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>
                </div>

                <div class="hidden">
                    <span id="route-dest-display">{{ $destAddress }}</span>
                    <span id="route-fare-display">Rp{{ number_format($estimatedFare, 0, ',', '.') }}</span>
                    <span id="billing-ongkir-display">Rp{{ number_format($estimatedFare, 0, ',', '.') }}</span>
                    <span id="dp-ongkir-display">Rp{{ number_format($estimatedFare, 0, ',', '.') }}</span>
                    <span id="billing-total-dp-display">Rp{{ number_format($dpAmount, 0, ',', '.') }}</span>
                </div>

                <!-- Hidden inputs for form submission -->
                <input type="hidden" name="delivery_location" id="delivery_location" value="{{ $destAddress }}">
                <input type="hidden" name="destination_address" id="destination_address" value="{{ $destAddress }}">
                <input type="hidden" name="destination_lat" id="destination_lat" value="{{ $destLat }}">
                <input type="hidden" name="destination_lng" id="destination_lng" value="{{ $destLng }}">
                <input type="hidden" name="delivery_fee" id="delivery_fee_input" value="{{ $estimatedFare }}">
            </div>

            <!-- 5.5. PROTEKSI ASURANSI BARANG / TALANGAN PLATFORM -->
            @if(!empty($insuranceEnabled) && !empty($isInsuranceEligible))
            <div class="bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-indigo-500/10 rounded-2xl p-4 border border-rose-200/80 shadow-xs">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        <input type="checkbox" name="has_insurance" id="has_insurance" value="1" checked
                               onchange="toggleInsuranceProtection(this.checked)"
                               class="w-5 h-5 text-rose-600 bg-white border-slate-300 rounded focus:ring-rose-500 cursor-pointer">
                    </div>
                    <label for="has_insurance" class="cursor-pointer select-none flex-1">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-black text-xs sm:text-sm text-slate-900 flex items-center gap-1">
                                    🛡️ Proteksi Asuransi Barang
                                </span>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-rose-600 text-white shadow-2xs">
                                    +Rp {{ number_format($insuranceFee, 0, ',', '.') }}
                                </span>
                            </div>
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-md">
                                Order &ge; Rp {{ number_format($insuranceThreshold, 0, ',', '.') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 leading-snug">
                            Jaminan ganti rugi 100% jika makanan/barang rusak, tumpah, atau hilang saat diantar jastiper. 
                            <strong>Dana langsung ditalangi kas asuransi platform</strong> tanpa menunggu ganti rugi jastiper.
                        </p>
                    </label>
                </div>
            </div>
            @endif

            <!-- 6. RINCIAN PERHITUNGAN BIAYA & TARIF ONGKIR BERANTAI -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3" x-data="{ openBreakdown: true }">
                <div class="flex items-center justify-between cursor-pointer" @click="openBreakdown = !openBreakdown">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🧾</span>
                        <div>
                            <h3 class="font-extrabold text-xs sm:text-sm text-slate-900">Rincian Perhitungan Biaya</h3>
                            <p class="text-[10px] text-slate-400 font-medium">Klik untuk buka/tutup rincian tarif</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-rose-600 font-bold text-xs">
                        <span id="breakdown-preview-total" class="font-extrabold">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
                        <svg class="w-4 h-4 transform transition-transform" :class="{ 'rotate-180': openBreakdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Breakdown Items Container -->
                <div class="space-y-2.5 pt-2 border-t border-slate-100 text-xs" x-show="openBreakdown" x-transition>
                    <!-- Subtotal Belanja -->
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Subtotal Harga Barang / Pesanan</span>
                        <span class="font-extrabold text-slate-800" id="breakdown-items-subtotal">Rp{{ number_format($totalItemsPrice, 0, ',', '.') }}</span>
                    </div>

                    <!-- Total Ongkir Header -->
                    <div class="pt-2 border-t border-dashed border-slate-100 flex items-center justify-between font-bold text-slate-700">
                        <span class="flex items-center gap-1">
                            <span id="ongkir-label-title">{{ ($routeCalculation['is_multi_store'] ?? false) ? '🛵 Ongkir Rute Berantai' : '🛵 Ongkir Pengantaran' }}</span>
                            <span class="text-[10px] font-extrabold bg-rose-50 text-rose-600 px-1.5 py-0.2 rounded-md border border-rose-100">
                                {{ $routeCalculation['total_distance_km'] ?? '0' }} KM
                            </span>
                        </span>
                        <span class="text-rose-600 font-extrabold" id="breakdown-fare-total">Rp{{ number_format($estimatedFare, 0, ',', '.') }}</span>
                    </div>

                    <!-- Detail Komponen Ongkir -->
                    <div class="bg-slate-50/80 rounded-xl p-3 space-y-2 text-[11px] text-slate-600 border border-slate-100">
                        <!-- Tarif Dasar -->
                        <div class="flex items-center justify-between">
                            <span>• Tarif Dasar (s.d {{ $routeCalculation['base_km'] ?? 2.0 }} KM pertama)</span>
                            <span class="font-bold text-slate-800">Rp{{ number_format($routeCalculation['base_fare'] ?? 10000, 0, ',', '.') }}</span>
                        </div>

                        <!-- Jarak Ekstra -->
                        @if(($routeCalculation['extra_km_distance'] ?? 0) > 0)
                        <div class="flex items-center justify-between">
                            <span>• Jarak Ekstra (<span id="bd-extra-km">{{ number_format($routeCalculation['extra_km_distance'] ?? 0, 2) }}</span> KM × Rp<span id="bd-extra-km-rate">{{ number_format($routeCalculation['extra_km_rate'] ?? 2000, 0, ',', '.') }}</span>/KM)</span>
                            <span class="font-bold text-slate-800" id="bd-km-fare">Rp{{ number_format($routeCalculation['km_fare'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <!-- Tambahan Toko Belanja (Multi-Stop Pickups) -->
                        @if(($routeCalculation['extra_stops_count'] ?? 0) > 0)
                        <div class="flex items-center justify-between">
                            <span>• Ekstra Titik Jemput / Toko (<span id="bd-extra-stops">{{ $routeCalculation['extra_stops_count'] }}</span> toko × Rp{{ number_format($routeCalculation['extra_store_rate'] ?? 5000, 0, ',', '.') }})</span>
                            <span class="font-bold text-slate-800" id="bd-stops-fare">Rp{{ number_format($routeCalculation['extra_stops_fare'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <!-- Tambahan Titik Antar / Dropoff (Multi-Dropoff) -->
                        @if(($routeCalculation['extra_dropoffs_count'] ?? 0) > 0)
                        <div class="flex items-center justify-between">
                            <span>• Ekstra Titik Antar Dokumen/Barang (<span id="bd-extra-dropoffs">{{ $routeCalculation['extra_dropoffs_count'] }}</span> titik × Rp{{ number_format($routeCalculation['extra_dropoff_rate'] ?? 6000, 0, ',', '.') }})</span>
                            <span class="font-bold text-slate-800" id="bd-dropoffs-fare">Rp{{ number_format($routeCalculation['extra_dropoffs_fare'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @endif

                        <!-- Tambahan Beban Muatan (Weight Category) -->
                        <div id="bd-weight-fare-row" class="flex items-center justify-between {{ ($routeCalculation['weight_fare'] ?? 0) > 0 ? '' : 'hidden' }}">
                            <span>• Beban Muatan (<span id="bd-weight-label" class="capitalize font-bold">{{ ucfirst($routeCalculation['weight_category'] ?? $weightCategory ?? 'ringan') }}</span>)</span>
                            <span class="font-bold text-slate-800" id="bd-weight-fare">Rp{{ number_format($routeCalculation['weight_fare'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Proteksi Asuransi Row -->
                    @if(!empty($insuranceEnabled) && !empty($isInsuranceEligible))
                    <div id="breakdown-insurance-row" class="flex items-center justify-between text-slate-600 pt-2 border-t border-dashed border-slate-100">
                        <span class="flex items-center gap-1 font-semibold text-slate-700">
                            <span>🛡️ Proteksi Asuransi Barang</span>
                            <span class="text-[9px] font-bold bg-amber-100 text-amber-800 px-1.5 py-0.2 rounded">Aktif</span>
                        </span>
                        <span class="font-extrabold text-slate-800" id="breakdown-insurance-fee">Rp{{ number_format($insuranceFee, 0, ',', '.') }}</span>
                    </div>
                    @endif

                    <!-- Total Akhir -->
                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between font-black text-sm text-slate-900">
                        <span>Total Pembayaran</span>
                        <span class="text-rose-600 text-base" id="breakdown-grand-total">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Sticky Bottom Checkout Bar (Inspired by Image 3) -->
            <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-slate-100 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] px-4 py-3">
                <div class="max-w-md mx-auto flex items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-slate-500 font-medium">Total: </span>
                            <span id="sticky-total-dp" class="text-rose-600 font-extrabold text-base tracking-tight">
                                Rp{{ number_format($grandTotal, 0, ',', '.') }}
                            </span>
                        </div>
                        <span id="sticky-distance-summary" class="text-[10px] text-slate-400 block font-medium">
                            Termasuk ongkir ({{ $routeCalculation['total_distance_km'] ?? '0' }} KM)
                        </span>
                    </div>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 active:scale-98 text-white font-bold text-sm px-7 py-3 rounded-xl transition shadow-md shadow-rose-600/25 shrink-0">
                        Checkout
                    </button>
                </div>
            </div>

        </form>

    </div>
    @endif
</div>

<!-- Hidden Form for Item Removal -->
<form id="remove-item-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
let TOTAL_ITEMS_PRICE = {{ $totalItemsPrice ?? 0 }};
let CURRENT_ORDERED_STOPS = @json($routeCalculation['ordered_stops'] ?? []);
let currentDestLat = {{ $destLat ?? -7.9839 }};
let currentDestLng = {{ $destLng ?? 112.6214 }};
let currentDestAddr = @json($destAddress ?? '');

const INSURANCE_ENABLED = {{ (!empty($insuranceEnabled) && !empty($isInsuranceEligible)) ? 'true' : 'false' }};
const INSURANCE_FEE = {{ (!empty($insuranceEnabled) && !empty($isInsuranceEligible)) ? (int)$insuranceFee : 0 }};
let isInsuranceActive = {{ (!empty($insuranceEnabled) && !empty($isInsuranceEligible)) ? 'true' : 'false' }};
let currentDeliveryFare = {{ (int)($estimatedFare ?? 0) }};

let cartMap = null;
let cartMarkersGroup = null;
let cartRoutePolyline = null;
let currentMode = 'gps';

function toggleInsuranceProtection(checked) {
    isInsuranceActive = checked;
    const insRow = document.getElementById('breakdown-insurance-row');
    if (insRow) {
        insRow.style.display = checked ? 'flex' : 'none';
    }
    refreshTotalDisplays();
}

function refreshTotalDisplays() {
    const insuranceAddon = (INSURANCE_ENABLED && isInsuranceActive) ? INSURANCE_FEE : 0;
    const grandTotal = TOTAL_ITEMS_PRICE + currentDeliveryFare + insuranceAddon;
    const dpTotal = (TOTAL_ITEMS_PRICE * 0.5) + currentDeliveryFare + insuranceAddon;

    const stickyTotal = document.getElementById('sticky-total-dp');
    if (stickyTotal) stickyTotal.innerText = formatRupiah(grandTotal);

    const bdPreview = document.getElementById('breakdown-preview-total');
    if (bdPreview) bdPreview.innerText = formatRupiah(grandTotal);

    const bdGrand = document.getElementById('breakdown-grand-total');
    if (bdGrand) bdGrand.innerText = formatRupiah(grandTotal);

    const billingDp = document.getElementById('billing-total-dp-display');
    if (billingDp) billingDp.innerText = formatRupiah(dpTotal);
}

function dismissFlashNotice(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        setTimeout(() => el.remove(), 350);
    }
}
setTimeout(() => {
    dismissFlashNotice('flash-success-alert');
    dismissFlashNotice('flash-error-alert');
}, 3800);


let qtyUpdateTimers = {};

function changeCartItemQty(cartItemId, delta) {
    const qtyEl = document.getElementById(`item-qty-${cartItemId}`);
    if (!qtyEl) return;

    let currentQty = parseInt(qtyEl.innerText.trim(), 10) || 1;
    let newQty = currentQty + delta;

    if (newQty < 1) {
        if (confirm('Hapus item ini dari keranjang?')) {
            removeCartItem(cartItemId);
        }
        return;
    }

    // 1. Optimistic Instant UI Update (0ms, no jumping/bouncing!)
    qtyEl.innerText = newQty;
    const multEl = document.getElementById(`item-multiplier-${cartItemId}`);
    if (multEl) multEl.innerText = `${newQty} x`;

    // 2. Debounce server sync
    if (qtyUpdateTimers[cartItemId]) {
        clearTimeout(qtyUpdateTimers[cartItemId]);
    }

    qtyUpdateTimers[cartItemId] = setTimeout(() => {
        executeCartQtyServerUpdate(cartItemId, newQty);
    }, 200);
}

function updateCartItemQty(cartItemId, newQty) {
    const qtyEl = document.getElementById(`item-qty-${cartItemId}`);
    let currentQty = qtyEl ? (parseInt(qtyEl.innerText.trim(), 10) || 1) : 1;
    changeCartItemQty(cartItemId, newQty - currentQty);
}

function executeCartQtyServerUpdate(cartItemId, newQty) {
    const destLatInput = document.getElementById('destination_lat');
    const destLngInput = document.getElementById('destination_lng');
    const destAddrInput = document.getElementById('destination_address');

    fetch(`/customer/cart/update-qty/${cartItemId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            quantity: newQty,
            destination_lat: destLatInput ? destLatInput.value : currentDestLat,
            destination_lng: destLngInput ? destLngInput.value : currentDestLng,
            destination_address: destAddrInput ? destAddrInput.value : currentDestAddr
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update item row subtotal
            const subtotalEl = document.getElementById(`item-subtotal-${cartItemId}`);
            if (subtotalEl) {
                subtotalEl.innerText = `Rp${data.item_subtotal_formatted}`;
            }

            // Update Total Items Subtotal in breakdown
            TOTAL_ITEMS_PRICE = data.total_items_price;
            const itemsSubtotalEl = document.getElementById('breakdown-items-subtotal');
            if (itemsSubtotalEl) {
                itemsSubtotalEl.innerText = `Rp${data.total_items_price_formatted}`;
            }

            // Update Delivery Fare
            if (data.estimated_fare !== undefined) {
                currentDeliveryFare = data.estimated_fare;
                const fareEl = document.getElementById('breakdown-fare-total');
                if (fareEl) fareEl.innerText = `Rp${data.estimated_fare_formatted}`;

                const fareInput = document.getElementById('delivery_fee_input');
                if (fareInput) fareInput.value = data.estimated_fare;
            }

            // Update Weight Category & Weight Fare
            if (data.weight_category) {
                const weightLabel = document.getElementById('bd-weight-label');
                if (weightLabel) weightLabel.innerText = data.weight_category.charAt(0).toUpperCase() + data.weight_category.slice(1);

                const weightRow = document.getElementById('bd-weight-fare-row');
                const weightFareEl = document.getElementById('bd-weight-fare');
                if (weightRow && weightFareEl) {
                    weightFareEl.innerText = `Rp${data.weight_fare_formatted}`;
                    if (data.weight_fare > 0) {
                        weightRow.classList.remove('hidden');
                    } else {
                        weightRow.classList.add('hidden');
                    }
                }
            }

            // Update cart badges in header and nav
            if (data.total_cart_count !== undefined) {
                const badges = document.querySelectorAll('.cart-badge, [id*="cart-count"], nav a[href*="cart"] span.bg-rose-600');
                badges.forEach(b => {
                    b.innerText = data.total_cart_count;
                    if (data.total_cart_count > 0) b.classList.remove('hidden');
                });
            }

            // Refresh Sticky Bar & Breakdown Grand Totals
            refreshTotalDisplays();
        } else {
            alert(data.error || 'Gagal mengubah kuantitas.');
        }
    })
    .catch(err => {
        console.error('Qty update error:', err);
    });
}

function removeCartItem(cartItemId) {
    const form = document.getElementById('remove-item-form');
    form.action = `/customer/cart/remove/${cartItemId}`;
    form.submit();
}

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function switchLocationMode(mode) {
    currentMode = mode;
    const tabGps = document.getElementById('tab-btn-gps');
    const tabSaved = document.getElementById('tab-btn-saved');
    const tabManual = document.getElementById('tab-btn-manual');
    const panelGps = document.getElementById('panel-location-gps');
    const panelSaved = document.getElementById('panel-location-saved');
    const panelManual = document.getElementById('panel-location-manual');
    const hiddenLocation = document.getElementById('delivery_location');
    const hiddenDestAddr = document.getElementById('destination_address');
    const routeDestDisplay = document.getElementById('route-dest-display');

    [tabGps, tabSaved, tabManual].forEach(t => {
        if (t) t.className = 'py-1.5 px-1.5 rounded-lg transition flex items-center justify-center gap-1 text-slate-500 hover:text-slate-800';
    });

    if (panelGps) panelGps.classList.add('hidden');
    if (panelSaved) panelSaved.classList.add('hidden');
    if (panelManual) panelManual.classList.add('hidden');

    if (mode === 'gps') {
        if (tabGps) tabGps.className = 'py-1.5 px-1.5 rounded-lg transition flex items-center justify-center gap-1 bg-white text-rose-600 shadow-2xs';
        if (panelGps) panelGps.classList.remove('hidden');

        setTimeout(() => { if (cartMap) cartMap.invalidateSize(); }, 200);

        const gpsAddr = document.getElementById('delivery_location_gps')?.value;
        if (hiddenLocation && gpsAddr) hiddenLocation.value = gpsAddr;
        if (hiddenDestAddr && gpsAddr) hiddenDestAddr.value = gpsAddr;
        if (routeDestDisplay && gpsAddr) routeDestDisplay.innerText = gpsAddr || 'Alamat Lokasi GPS';

        detectDeviceLocation(true);
    } else if (mode === 'saved') {
        if (tabSaved) tabSaved.className = 'py-1.5 px-1.5 rounded-lg transition flex items-center justify-center gap-1 bg-white text-rose-600 shadow-2xs';
        if (panelSaved) panelSaved.classList.remove('hidden');

        setTimeout(() => { if (cartMap) cartMap.invalidateSize(); }, 200);
    } else {
        if (tabManual) tabManual.className = 'py-1.5 px-1.5 rounded-lg transition flex items-center justify-center gap-1 bg-white text-rose-600 shadow-2xs';
        if (panelManual) panelManual.classList.remove('hidden');

        setTimeout(() => { if (cartMap) cartMap.invalidateSize(); }, 200);
    }
}

function selectSavedCartAddress(id, address, lat, lng) {
    const hiddenLocation = document.getElementById('delivery_location');
    const hiddenDestAddr = document.getElementById('destination_address');
    const inputLat = document.getElementById('destination_lat');
    const inputLng = document.getElementById('destination_lng');
    const routeDestDisplay = document.getElementById('route-dest-display');
    const gpsTextarea = document.getElementById('delivery_location_gps');
    const manualTextarea = document.getElementById('delivery_location_manual');
    const badge = document.getElementById('manual-status-badge');

    if (hiddenLocation) hiddenLocation.value = address;
    if (hiddenDestAddr) hiddenDestAddr.value = address;
    if (gpsTextarea) gpsTextarea.value = address;
    if (manualTextarea) manualTextarea.value = address;
    if (routeDestDisplay) routeDestDisplay.innerText = address;
    if (badge) badge.classList.remove('hidden');

    document.querySelectorAll('.saved-cart-address-card').forEach(card => {
        if (card.dataset.addressId === String(id)) {
            card.classList.add('border-rose-500', 'bg-rose-50/70', 'ring-1', 'ring-rose-500');
            card.classList.remove('border-slate-200', 'bg-white');
            const check = card.querySelector('.saved-check-badge');
            if (check) check.classList.remove('hidden');
        } else {
            card.classList.remove('border-rose-500', 'bg-rose-50/70', 'ring-1', 'ring-rose-500');
            card.classList.add('border-slate-200', 'bg-white');
            const check = card.querySelector('.saved-check-badge');
            if (check) check.classList.add('hidden');
        }
    });

    if (lat && lng && Number(lat) !== 0 && Number(lng) !== 0) {
        if (inputLat) inputLat.value = lat;
        if (inputLng) inputLng.value = lng;
        recalculateRoute(lat, lng);
    }
}

async function searchManualAddress() {
    const searchInput = document.getElementById('manual_search_input');
    const feedback = document.getElementById('search-feedback-text');
    const btnSearch = document.getElementById('btn-search-manual');
    const hiddenLocation = document.getElementById('delivery_location');
    const hiddenDestAddr = document.getElementById('destination_address');
    const manualAddrInput = document.getElementById('delivery_location_manual');
    const inputLat = document.getElementById('destination_lat');
    const inputLng = document.getElementById('destination_lng');
    const routeDestDisplay = document.getElementById('route-dest-display');
    const badge = document.getElementById('manual-status-badge');

    if (!searchInput || !searchInput.value.trim()) {
        alert('Ketik alamat terlebih dahulu.');
        return;
    }

    const query = searchInput.value.trim();
    if (btnSearch) btnSearch.innerText = '...';
    if (feedback) feedback.innerText = 'Mencari titik koordinat...';

    try {
        let res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id`, {
            headers: { 'Accept-Language': 'id' }
        });
        let data = await res.json();

        if (data && data.length > 0) {
            const place = data[0];
            const lat = parseFloat(place.lat);
            const lng = parseFloat(place.lon);
            const shortName = place.display_name;

            if (manualAddrInput) manualAddrInput.value = shortName;
            if (hiddenLocation) hiddenLocation.value = shortName;
            if (hiddenDestAddr) hiddenDestAddr.value = shortName;
            if (inputLat) inputLat.value = lat;
            if (inputLng) inputLng.value = lng;
            if (routeDestDisplay) routeDestDisplay.innerText = shortName;

            if (badge) badge.classList.remove('hidden');
            if (feedback) feedback.innerText = `✓ Titik ditemukan: ${place.name || query}`;

            recalculateRoute(lat, lng);
        } else {
            if (feedback) feedback.innerText = 'Titik tidak ditemukan, coba ketik nama kota/kecamatan.';
        }
    } catch (err) {
        console.error(err);
        if (feedback) feedback.innerText = 'Gagal mencari alamat otomatis.';
    } finally {
        if (btnSearch) btnSearch.innerText = 'Cari';
    }
}

function detectDeviceLocation(isAutomatic = false) {
    if (currentMode !== 'gps') return;

    const btnRefresh = document.getElementById('btn-gps-refresh-label');
    const inputLocationGps = document.getElementById('delivery_location_gps');
    const hiddenLocation = document.getElementById('delivery_location');
    const hiddenDestAddr = document.getElementById('destination_address');
    const inputLat = document.getElementById('destination_lat');
    const inputLng = document.getElementById('destination_lng');
    const routeDestDisplay = document.getElementById('route-dest-display');

    if (!navigator.geolocation) return;

    if (btnRefresh) btnRefresh.innerText = 'Membaca GPS...';

    navigator.geolocation.getCurrentPosition(
        async function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            if (inputLat) inputLat.value = lat;
            if (inputLng) inputLng.value = lng;

            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                    headers: { 'Accept-Language': 'id' }
                });
                
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.display_name) {
                        const addr = data.address || {};
                        let shortAddress = [
                            addr.road || addr.amenity || addr.building || '',
                            addr.neighbourhood || addr.suburb || addr.village || '',
                            addr.city || addr.town || ''
                        ].filter(Boolean).join(', ');

                        if (!shortAddress) shortAddress = data.display_name;

                        if (inputLocationGps) inputLocationGps.value = shortAddress;
                        if (hiddenLocation) hiddenLocation.value = shortAddress;
                        if (hiddenDestAddr) hiddenDestAddr.value = shortAddress;
                        if (routeDestDisplay) routeDestDisplay.innerText = shortAddress;
                    }
                }
            } catch (e) {}

            if (btnRefresh) btnRefresh.innerText = 'Perbarui Lokasi GPS';
            recalculateRoute(lat, lng);
        },
        function (error) {
            if (btnRefresh) {
                btnRefresh.innerText = error.code === error.PERMISSION_DENIED 
                    ? 'Izin GPS Ditolak di Browser' 
                    : 'GPS Belum Siap (Coba Lagi)';
                setTimeout(() => {
                    btnRefresh.innerText = 'Perbarui Lokasi GPS';
                }, 4000);
            }
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

function recalculateRoute(lat, lng) {
    fetch('{{ route('customer.cart.calculate-route') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            destination_lat: lat,
            destination_lng: lng
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data || !data.total_distance_km) return;

        const totalDist = data.total_distance_km;
        const estimatedFare = data.estimated_fare;
        const totalItems = data.total_items_price !== undefined ? data.total_items_price : TOTAL_ITEMS_PRICE;
        TOTAL_ITEMS_PRICE = totalItems;
        currentDeliveryFare = estimatedFare;

        const inputFee = document.getElementById('delivery_fee_input');
        if (inputFee) inputFee.value = estimatedFare;

        const routeBadge = document.getElementById('route-total-distance-badge');
        if (routeBadge) routeBadge.innerText = `${totalDist} KM Total`;

        const stickyDist = document.getElementById('sticky-distance-summary');
        if (stickyDist) stickyDist.innerText = `Termasuk ongkir (${totalDist} KM)`;

        refreshTotalDisplays();

        const bdItems = document.getElementById('breakdown-items-subtotal');
        if (bdItems) bdItems.innerText = formatRupiah(totalItems);

        const bdFare = document.getElementById('breakdown-fare-total');
        if (bdFare) bdFare.innerText = formatRupiah(estimatedFare);

        const bdExtraKm = document.getElementById('bd-extra-km');
        if (bdExtraKm && data.extra_km_distance !== undefined) bdExtraKm.innerText = data.extra_km_distance;

        const bdExtraKmRate = document.getElementById('bd-extra-km-rate');
        if (bdExtraKmRate && data.extra_km_rate !== undefined) {
            bdExtraKmRate.innerText = formatRupiah(data.extra_km_rate).replace('Rp ', '').replace('Rp', '');
        }

        const ongkirLabel = document.getElementById('ongkir-label-title');
        if (ongkirLabel) {
            ongkirLabel.innerText = data.is_multi_store ? '🛵 Ongkir Rute Berantai' : '🛵 Ongkir Pengantaran';
        }

        const routeHeader = document.getElementById('route-header-title');
        if (routeHeader) {
            routeHeader.innerText = data.is_multi_store ? 'Rute Pengantaran Berantai' : 'Rute Pengantaran';
        }

        const bdKmFare = document.getElementById('bd-km-fare');
        if (bdKmFare && data.km_fare !== undefined) bdKmFare.innerText = formatRupiah(data.km_fare);

        const bdStopsFare = document.getElementById('bd-stops-fare');
        if (bdStopsFare && data.extra_stops_fare !== undefined) bdStopsFare.innerText = formatRupiah(data.extra_stops_fare);

        const bdDropoffsFare = document.getElementById('bd-dropoffs-fare');
        if (bdDropoffsFare && data.extra_dropoffs_fare !== undefined) bdDropoffsFare.innerText = formatRupiah(data.extra_dropoffs_fare);

        const bdWeightRow = document.getElementById('bd-weight-fare-row');
        const bdWeightLabel = document.getElementById('bd-weight-label');
        const bdWeightFare = document.getElementById('bd-weight-fare');
        if (bdWeightRow && data.weight_fare !== undefined) {
            if (data.weight_fare > 0) {
                bdWeightRow.classList.remove('hidden');
                if (bdWeightLabel && data.weight_category) {
                    bdWeightLabel.innerText = data.weight_category.charAt(0).toUpperCase() + data.weight_category.slice(1);
                }
                if (bdWeightFare) {
                    bdWeightFare.innerText = formatRupiah(data.weight_fare);
                }
            } else {
                bdWeightRow.classList.add('hidden');
            }
        }

        if (data.ordered_stops) {
            CURRENT_ORDERED_STOPS = data.ordered_stops;
        }
        renderRouteOnMap(lat, lng, document.getElementById('delivery_location').value, CURRENT_ORDERED_STOPS);
    })
    .catch(err => console.error(err));
}

function initCartMap() {
    const mapElement = document.getElementById('cart-route-map');
    if (!mapElement || typeof L === 'undefined') return;

    if (cartMap !== null) {
        cartMap.remove();
        cartMap = null;
    }

    const defaultLat = CURRENT_ORDERED_STOPS.length > 0 ? parseFloat(CURRENT_ORDERED_STOPS[0].lat) : currentDestLat;
    const defaultLng = CURRENT_ORDERED_STOPS.length > 0 ? parseFloat(CURRENT_ORDERED_STOPS[0].lng) : currentDestLng;

    cartMap = L.map('cart-route-map', {
        scrollWheelZoom: false,
        zoomControl: true,
    }).setView([defaultLat, defaultLng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(cartMap);

    cartMarkersGroup = L.featureGroup().addTo(cartMap);
    renderRouteOnMap(currentDestLat, currentDestLng, currentDestAddr, CURRENT_ORDERED_STOPS);
}

function renderRouteOnMap(destLat, destLng, destAddr, customStops = null) {
    if (!cartMap || !cartMarkersGroup || typeof L === 'undefined') return;

    cartMarkersGroup.clearLayers();
    if (cartRoutePolyline) {
        cartMap.removeLayer(cartRoutePolyline);
        cartRoutePolyline = null;
    }

    const stopsToRender = customStops || CURRENT_ORDERED_STOPS;
    const latLngs = [];

    if (!stopsToRender || stopsToRender.length === 0) {
        latLngs.push([destLat, destLng]);
        const destIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="custom-dest-marker">★</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });
        L.marker([destLat, destLng], { icon: destIcon })
            .bindPopup(`<b>🏁 Alamat Tujuan Utama:</b><br><small>${destAddr}</small>`)
            .addTo(cartMarkersGroup);
        return;
    }

    let finalDestStop = null;
    const intermediateStops = [];

    stopsToRender.forEach(stop => {
        if (stop.id === 'final_destination' || stop.type === 'destination') {
            finalDestStop = stop;
        } else {
            intermediateStops.push(stop);
        }
    });

    intermediateStops.forEach((stop, idx) => {
        const sLat = parseFloat(stop.lat);
        const sLng = parseFloat(stop.lng);
        latLngs.push([sLat, sLng]);

        const isDropoff = stop.action === 'dropoff';
        const markerBg = isDropoff ? '#0284C7' : '#E11D48';
        const badgeLabel = stop.badge || (isDropoff ? '🔵 Antar Penerima' : '🛒 Titik Jemput');

        const markerIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="custom-stop-marker" style="background:${markerBg};">${idx + 1}</div>`,
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        L.marker([sLat, sLng], { icon: markerIcon })
            .bindPopup(`<b>Stop ${idx + 1}: ${stop.name}</b><br><span style="font-size:10px; color:${markerBg}; font-weight:bold;">${badgeLabel}</span><br><small>${stop.address}</small>`)
            .addTo(cartMarkersGroup);
    });

    const dLat = finalDestStop ? parseFloat(finalDestStop.lat) : destLat;
    const dLng = finalDestStop ? parseFloat(finalDestStop.lng) : destLng;
    const dAddr = finalDestStop ? (finalDestStop.address || destAddr) : destAddr;
    latLngs.push([dLat, dLng]);

    const destIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="custom-dest-marker">★</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14]
    });

    L.marker([dLat, dLng], { icon: destIcon })
        .bindPopup(`<b>🏁 Alamat Tujuan Utama:</b><br><small>${dAddr}</small>`)
        .addTo(cartMarkersGroup);

    if (latLngs.length > 1) {
        cartRoutePolyline = L.polyline(latLngs, {
            color: '#E11D48',
            weight: 3.5,
            opacity: 0.85,
            dashArray: '6, 6'
        }).addTo(cartMap);
    }

    if (cartMarkersGroup.getLayers().length > 0) {
        cartMap.fitBounds(cartMarkersGroup.getBounds().pad(0.18));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initCartMap();
});
</script>
@endsection
