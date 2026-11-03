@extends('layouts.support')

@section('title', 'Pencarian Layanan, Toko & Produk')

@section('content')
<div class="min-h-screen bg-slate-50/50 pb-16">
    <!-- Header Pencarian (Sticky Top Bar) -->
    <div class="sticky top-0 z-50 bg-white border-b border-slate-200/80 px-4 py-3 shadow-xs">
        <div class="max-w-2xl mx-auto flex items-center gap-3">
            <a href="{{ route('customer.dashboard') }}" class="text-slate-500 hover:text-slate-800 p-1.5 rounded-full hover:bg-slate-100 transition" title="Kembali ke Beranda">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <form action="{{ route('customer.search') }}" method="GET" class="flex-grow relative">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari layanan, toko, makanan, ekspedisi..." autofocus 
                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-800 placeholder-slate-400 pl-10 pr-4 py-2.5 rounded-full text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150 h-[40px]">
                <div class="absolute left-3.5 top-2.5 text-slate-400 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                @if(request('q'))
                    <a href="{{ route('customer.search') }}" class="absolute right-3.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold" title="Hapus pencarian">
                        ✕
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-4 py-5 space-y-6">
        @if(request()->filled('q'))
            @php
                $hasServices = isset($matchedServices) && $matchedServices->isNotEmpty();
                $hasMerchants = isset($merchants) && $merchants->isNotEmpty();
                $hasProducts = isset($products) && $products->isNotEmpty();
                $hasAnyResult = $hasServices || $hasMerchants || $hasProducts;
            @endphp

            @if($hasAnyResult)
                <!-- 1. Hasil Pencarian: Layanan JastipKuy Utama (Tracking Menu 4 Layanan) -->
                @if($hasServices)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-4 sm:p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🚀</span>
                                <div>
                                    <h3 class="font-display font-black text-xs text-slate-900 uppercase tracking-wider">Layanan Belanja Jastip Terkait</h3>
                                    <p class="text-[10px] text-slate-400">Cocok dengan kata kunci "{{ request('q') }}"</p>
                                </div>
                            </div>
                            <span class="text-[9px] font-extrabold bg-rose-50 text-rose-600 border border-rose-200 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                {{ $matchedServices->count() }} Layanan
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach($matchedServices as $svc)
                                <a href="{{ url($svc['url']) }}" class="group bg-slate-50/70 hover:bg-white border border-slate-200 hover:border-rose-400 hover:shadow-md hover:shadow-rose-500/10 rounded-2xl p-3 flex items-center gap-3 transition duration-150">
                                    <div class="w-12 h-12 bg-white rounded-xl border border-slate-200 p-1 flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-105 transition-transform">
                                        <img src="{{ asset($svc['image']) }}" alt="{{ $svc['name'] }}" class="w-10 h-10 object-contain">
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-extrabold text-xs text-slate-900 group-hover:text-rose-600 transition">{{ $svc['name'] }}</h4>
                                            <span class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded border {{ $svc['badge_color'] }}">
                                                {{ $svc['subtitle'] }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 line-clamp-1 mt-0.5 leading-snug">{{ $svc['description'] }}</p>
                                    </div>
                                    <div class="w-7 h-7 rounded-full bg-white group-hover:bg-rose-600 text-slate-400 group-hover:text-white border border-slate-200 group-hover:border-rose-600 flex items-center justify-center text-xs font-bold shrink-0 transition shadow-2xs">
                                        →
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- 2. Hasil Pencarian: Toko / Mitra Merchant -->
                @if($hasMerchants)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-4 sm:p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🏪</span>
                                <h3 class="font-display font-black text-xs text-slate-900 uppercase tracking-wider">Toko & Restoran ({{ $merchants->count() }})</h3>
                            </div>
                        </div>

                        <div class="space-y-2">
                            @foreach($merchants as $merchant)
                                <a href="{{ route('customer.merchant.show', $merchant->id) }}" class="flex items-center gap-3 p-3 bg-slate-50/70 hover:bg-white border border-slate-200 hover:border-slate-300 rounded-2xl transition shadow-2xs group">
                                    <div class="w-11 h-11 bg-rose-50 text-rose-600 border border-rose-100 rounded-2xl flex items-center justify-center font-black text-sm shrink-0 shadow-2xs group-hover:scale-105 transition">
                                        {{ strtoupper(substr($merchant->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <h4 class="font-extrabold text-xs text-slate-900 group-hover:text-rose-600 transition">{{ $merchant->name }}</h4>
                                        <p class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">{{ $merchant->address ?: 'Area Malang Raya & Jawa Timur' }}</p>
                                    </div>
                                    <span class="text-xs text-slate-400 group-hover:text-rose-600 font-bold shrink-0">Buka Toko →</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- 3. Hasil Pencarian: Menu / Produk -->
                @if($hasProducts)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-4 sm:p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🍽️</span>
                                <h3 class="font-display font-black text-xs text-slate-900 uppercase tracking-wider">Menu & Produk Titipan ({{ $products->count() }})</h3>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach($products as $product)
                                <a href="{{ route('customer.merchant.show', $product->merchant_id) }}" class="flex items-center gap-3 p-2.5 bg-slate-50/70 hover:bg-white border border-slate-200 hover:border-rose-300 rounded-2xl transition shadow-2xs group">
                                    <div class="w-14 h-14 bg-slate-200 rounded-xl overflow-hidden shrink-0 flex items-center justify-center border border-slate-200">
                                        @if($product->image)
                                            <img src="{{ $product->image_url ?? $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-150">
                                        @else
                                            <span class="text-xs font-bold text-slate-400">{{ strtoupper(substr($product->name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <h4 class="font-bold text-xs text-slate-800 line-clamp-1 group-hover:text-rose-600 transition">{{ $product->name }}</h4>
                                        <p class="text-[9px] text-slate-400 line-clamp-1">{{ $product->merchant->name ?? 'Mitra JastipKuy' }}</p>
                                        <div class="mt-1">
                                            @if($product->has_discount)
                                                <div class="flex items-baseline gap-1">
                                                    <span class="text-rose-600 font-black text-xs">Rp {{ number_format($product->discount_price, 0, ',', '.') }}</span>
                                                    <span class="text-[9px] text-slate-400 line-through">Rp {{ number_format($product->estimated_price, 0, ',', '.') }}</span>
                                                </div>
                                            @else
                                                <span class="text-slate-900 font-extrabold text-xs">Rp {{ number_format($product->estimated_price, 0, ',', '.') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <!-- Kosong / Tidak Ada Hasil Sama Sekali -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-8 text-center space-y-4 shadow-xs">
                    <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center text-2xl mx-auto shadow-inner">
                        🔍
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-800">Tidak Menemukan "{{ request('q') }}"</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto leading-relaxed">
                            Tidak ada layanan, toko, atau makanan yang cocok dengan kata kunci tersebut. Anda tetap bisa menitipkan belanjaan apapun secara manual!
                        </p>
                    </div>
                    <div class="pt-2 flex flex-col sm:flex-row justify-center gap-2">
                        <a href="{{ route('customer.orders.create') }}" class="bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs px-5 py-2.5 rounded-full transition shadow-md shadow-rose-600/20">
                            + Buat Pesanan Manual
                        </a>
                        <a href="{{ route('customer.search') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-full transition">
                            Lihat 4 Layanan Utama
                        </a>
                    </div>
                </div>
            @endif

        @else
            <!-- Discovery / Initial State (Belum Mengetik Pencarian) -->
            
            <!-- Quick Action: Buat Pesanan Manual Custom -->
            <a href="{{ route('customer.orders.create') }}" class="block bg-gradient-to-r from-rose-600 to-rose-700 text-white rounded-3xl p-4 sm:p-5 shadow-md hover:scale-[1.01] transition duration-150">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-white/20 rounded-2xl flex items-center justify-center text-xl shrink-0 backdrop-blur-xs">
                        ✍️
                    </div>
                    <div class="min-w-0 flex-grow text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[9px] font-black uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded-full">Bebas Titip</span>
                            <h3 class="font-black text-sm text-white leading-tight">Pesanan Jastip Manual</h3>
                        </div>
                        <p class="text-[11px] text-rose-100 mt-0.5 leading-snug font-medium">Ketik nama toko & belanjaan apa saja yang Anda inginkan secara mandiri.</p>
                    </div>
                    <span class="text-white text-sm font-black shrink-0">→</span>
                </div>
            </a>

            <!-- 4 Layanan Belanja Jastip Utama (Menu 4 Layanan) -->
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-xs space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div>
                        <h3 class="font-display font-black text-xs text-slate-900 uppercase tracking-wider">Layanan Belanja Jastip</h3>
                        <p class="text-[10px] text-slate-400">Pilih salah satu dari 4 kategori layanan JastipKuy</p>
                    </div>
                    <span class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">4 Menu</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3">
                    @php
                        $displayServices = isset($allServices) ? $allServices : collect(\App\Http\Controllers\Customer\SearchController::$services);
                    @endphp

                    @foreach($displayServices as $svc)
                        <a href="{{ url($svc['url']) }}" class="group bg-slate-50/70 hover:bg-white border border-slate-200 hover:border-rose-400 hover:shadow-md hover:shadow-rose-500/10 rounded-2xl p-3 flex flex-col items-center justify-center text-center transition duration-150">
                            <div class="w-14 h-12 sm:w-16 sm:h-14 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                                <img src="{{ asset($svc['image']) }}" alt="{{ $svc['name'] }}" class="w-full h-full object-contain drop-shadow-xs rounded-xl">
                            </div>
                            <span class="text-xs font-bold text-slate-800 leading-tight mb-0.5 group-hover:text-rose-600 transition">{{ $svc['name'] }}</span>
                            <span class="text-[9px] text-slate-500 font-medium leading-tight">{{ $svc['subtitle'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Kata Kunci Populer & Rekomendasi -->
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-xs space-y-3">
                <h3 class="font-display font-black text-xs text-slate-900 uppercase tracking-wider">Pencarian Populer</h3>
                <div class="flex flex-wrap gap-2 pt-1">
                    <a href="?q=beli+antar" class="px-3.5 py-1.5 bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 rounded-full text-xs font-bold transition flex items-center gap-1">
                        <span>🍔</span> Beli-Antar
                    </a>
                    <a href="?q=toko+kirim" class="px-3.5 py-1.5 bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 rounded-full text-xs font-bold transition flex items-center gap-1">
                        <span>🛒</span> Toko Kirim
                    </a>
                    <a href="?q=ambil+antar" class="px-3.5 py-1.5 bg-sky-50 border border-sky-200 text-sky-700 hover:bg-sky-100 rounded-full text-xs font-bold transition flex items-center gap-1">
                        <span>📦</span> Ambil & Antar
                    </a>
                    <a href="?q=ekspedisi" class="px-3.5 py-1.5 bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 rounded-full text-xs font-bold transition flex items-center gap-1">
                        <span>✈️</span> Pihak Ketiga (Ekspedisi)
                    </a>
                    <a href="?q=mie+gacoan" class="px-3.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200 rounded-full text-xs font-semibold transition">
                        Mie Gacoan
                    </a>
                    <a href="?q=indomaret" class="px-3.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200 rounded-full text-xs font-semibold transition">
                        Indomaret
                    </a>
                    <a href="?q=kopi" class="px-3.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200 rounded-full text-xs font-semibold transition">
                        Kopi
                    </a>
                    <a href="?q=pasar" class="px-3.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200 rounded-full text-xs font-semibold transition">
                        Pasar Tradisional
                    </a>
                    <a href="?q=dokumen" class="px-3.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200 rounded-full text-xs font-semibold transition">
                        Dokumen & Surat
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
