@extends('layouts.support')

@section('title', 'Keranjang Belanja - Multi Toko')

@section('content')
<div class="min-h-screen bg-[#F8FAFC] pb-36">
    
    <!-- Top Header (Dedicated Mobile-First Checkout Header) -->
    <div class="bg-white/95 backdrop-blur-md sticky top-0 z-40 px-3 sm:px-4 py-2.5 sm:py-3 border-b border-slate-200/80 shadow-xs">
        <div class="max-w-xl mx-auto flex items-center justify-between gap-2">
            <a href="{{ route('customer.dashboard') }}" class="text-slate-600 p-1.5 -ml-1 rounded-full hover:bg-slate-100 transition inline-flex items-center gap-1 text-xs font-bold shrink-0">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            
            <div class="text-center min-w-0 flex-1">
                <h1 class="font-black text-sm sm:text-base text-slate-800 truncate">Keranjang Belanja</h1>
                @if($cart && !$cart->cartItems->isEmpty())
                    <p class="text-[10px] sm:text-[11px] font-bold text-rose-600 truncate">
                        {{ count($groupedItems) }} Toko • {{ $cart->cartItems->sum('quantity') }} Barang
                    </p>
                @endif
            </div>

            <div class="w-8 shrink-0"></div> <!-- Balance spacer -->
        </div>
    </div>

    <!-- Alert Flash Notifications -->
    @if(session('success'))
    <div class="max-w-xl mx-auto px-3 sm:px-4 pt-3">
        <div class="p-2.5 sm:p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="truncate">{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="max-w-xl mx-auto px-3 sm:px-4 pt-3">
        <div class="p-2.5 sm:p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span class="truncate">{{ session('error') }}</span>
        </div>
    </div>
    @endif

    @if(!$cart || $cart->cartItems->isEmpty())
    <!-- Empty Cart State -->
    <div class="flex flex-col items-center justify-center py-20 px-4 max-w-sm mx-auto text-center">
        <div class="w-20 h-20 bg-rose-50 border-2 border-dashed border-rose-200 rounded-3xl flex items-center justify-center text-rose-500 mb-4 shadow-inner">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
        <h2 class="font-black text-lg text-slate-800 mb-1">Keranjang Belanja Kosong</h2>
        <p class="text-xs text-slate-500 leading-relaxed mb-5">
            Kamu bisa memilih barang dari <b>berbagai toko berbeda</b> sekaligus dalam satu rute jastip!
        </p>
        <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs sm:text-sm py-3 px-6 rounded-full shadow-md shadow-rose-600/25 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            Mulai Belanja
        </a>
    </div>
    @else
    
    <div class="max-w-xl mx-auto p-3 sm:p-4 space-y-3.5">
        
        <!-- Multi-Store Info Notification (Muncul jika belanja > 1 toko) -->
        @if(count($groupedItems) > 1)
        <div class="bg-gradient-to-r from-rose-500 to-pink-600 rounded-2xl p-3 sm:p-3.5 text-white shadow-sm shadow-rose-500/15 relative overflow-hidden">
            <div class="flex items-start gap-2.5">
                <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-xs shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full bg-white/20 text-[9px] font-black uppercase tracking-wider mb-0.5">
                        Multi-Store Jastip
                    </div>
                    <h3 class="font-extrabold text-xs sm:text-sm leading-tight">
                        Rute Gabungan {{ count($groupedItems) }} Toko Aktif
                    </h3>
                    <p class="text-[10px] text-white/90 mt-0.5 leading-relaxed">
                        Jastiper belanja berantai dari <b>Toko 1 ➔ Toko 2 ➔ langsung ke Alamat Tujuanmu</b>.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('customer.cart.checkout') }}" method="POST" id="checkout-form" class="space-y-3.5">
            @csrf

            <!-- 1. ALAMAT PENGIRIMAN (DUAL-MODE: GPS PERANGKAT VS INPUT MANUAL TEMPAT LAIN) -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl shadow-xs border border-slate-200/80">
                <!-- Header Card Alamat -->
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        </span>
                        <div>
                            <h2 class="font-black text-slate-800 text-xs sm:text-sm truncate">Alamat Pengiriman</h2>
                            <p class="text-[10px] text-slate-400">Pilih titik antar sesuai kebutuhanmu</p>
                        </div>
                    </div>
                </div>

                <!-- Tab Segmented Control: GPS vs Manual -->
                <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 rounded-xl mb-3 text-xs font-bold">
                    <button 
                        type="button" 
                        id="tab-btn-gps"
                        onclick="switchLocationMode('gps')"
                        class="py-2 px-2.5 rounded-lg transition flex items-center justify-center gap-1.5 bg-white text-rose-600 shadow-2xs border border-slate-200/50"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3" stroke-width="2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v3m0 14v3m10-10h-3M5 12H2"/>
                        </svg>
                        <span>Lokasi Saya (GPS)</span>
                    </button>
                    <button 
                        type="button" 
                        id="tab-btn-manual"
                        onclick="switchLocationMode('manual')"
                        class="py-2 px-2.5 rounded-lg transition flex items-center justify-center gap-1.5 text-slate-500 hover:text-slate-800"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Kirim ke Tempat Lain</span>
                    </button>
                </div>

                <!-- PANEL 1: MODE GPS (Lokasi HP Saat Ini) -->
                <div id="panel-location-gps" class="space-y-2">
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                        <span class="font-bold uppercase tracking-wider">Lokasi Terbaca via GPS</span>
                        <span id="gps-status-badge" class="font-bold text-emerald-600 inline-flex items-center gap-0.5">
                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            GPS Aktif
                        </span>
                    </div>

                    <div class="relative">
                        <textarea 
                            id="delivery_location_gps" 
                            rows="2" 
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-xs sm:text-sm leading-relaxed p-2.5 bg-slate-50/50" 
                            placeholder="Alamat akan terisi otomatis via GPS..."
                        >{{ session('customer_location', 'Sedengan Mijen, Sidoarjo') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <span class="text-[10px] text-slate-400">Kurang pas? Kamu bisa memperbarui titik GPS:</span>
                        <button 
                            type="button" 
                            onclick="detectDeviceLocation(false)" 
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200/80 shrink-0"
                        >
                            <svg class="w-3 h-3 text-rose-600 animate-spin-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span id="btn-gps-refresh-label">Ambil Ulang GPS</span>
                        </button>
                    </div>
                </div>

                <!-- PANEL 2: MODE MANUAL (Kirim ke Alamat Lain / Tempat Lain) -->
                <div id="panel-location-manual" class="space-y-2.5 hidden">
                    <div class="p-2.5 bg-indigo-50/70 border border-indigo-100 rounded-xl text-indigo-900 text-[10px] sm:text-[11px] leading-relaxed flex items-start gap-2">
                        <span class="text-sm shrink-0">📍</span>
                        <span>
                            <b>Kirim ke tempat lain:</b> Masukkan alamat tujuan (misal kosan, rumah teman, atau kantor di Malang) meskipun saat ini kamu sedang berada di tempat lain. Jarak dan ongkir akan dihitung ke alamat ini.
                        </span>
                    </div>

                    <!-- Input Pencarian Alamat / Area -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                            Cari Alamat / Jalan Tujuan
                        </label>
                        <div class="flex items-center gap-1.5">
                            <div class="relative flex-1">
                                <input 
                                    type="text" 
                                    id="manual_search_input"
                                    placeholder="Contoh: Jl. Soekarno Hatta, Dinoyo, Klojen..." 
                                    class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-xs p-2.5 pl-8"
                                    onkeydown="if(event.key === 'Enter'){ event.preventDefault(); searchManualAddress(); }"
                                >
                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <button 
                                type="button" 
                                onclick="searchManualAddress()" 
                                id="btn-search-manual"
                                class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl transition shrink-0"
                            >
                                Cari Titik
                            </button>
                        </div>
                        <p id="search-feedback-text" class="text-[10px] text-slate-400 mt-1 hidden"></p>
                    </div>

                    <!-- Pilihan Cepat Area Populer Malang Raya -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                            Atau Pilih Cepat Area Tujuan di Malang:
                        </label>
                        <div class="flex flex-wrap gap-1.5">
                            <button 
                                type="button" 
                                onclick="selectQuickArea('Klojen / Pusat Kota', -7.9829, 112.6308)"
                                class="quick-area-chip text-[10px] font-bold py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200/80 transition"
                            >
                                🏛️ Klojen (Pusat Kota)
                            </button>
                            <button 
                                type="button" 
                                onclick="selectQuickArea('Lowokwaru / Suhat', -7.9445, 112.6180)"
                                class="quick-area-chip text-[10px] font-bold py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200/80 transition"
                            >
                                🎓 Lowokwaru / Suhat
                            </button>
                            <button 
                                type="button" 
                                onclick="selectQuickArea('Dinoyo / MT Haryono', -7.9483, 112.6138)"
                                class="quick-area-chip text-[10px] font-bold py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200/80 transition"
                            >
                                🛍️ Dinoyo / MT Haryono
                            </button>
                            <button 
                                type="button" 
                                onclick="selectQuickArea('Blimbing / Araya', -7.9350, 112.6450)"
                                class="quick-area-chip text-[10px] font-bold py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200/80 transition"
                            >
                                🏡 Blimbing / Araya
                            </button>
                            <button 
                                type="button" 
                                onclick="selectQuickArea('Sukun', -8.0050, 112.6150)"
                                class="quick-area-chip text-[10px] font-bold py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200/80 transition"
                            >
                                🌳 Sukun
                            </button>
                            <button 
                                type="button" 
                                onclick="selectQuickArea('Sawojajar / Kedungkandang', -7.9800, 112.6550)"
                                class="quick-area-chip text-[10px] font-bold py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200/80 transition"
                            >
                                🏢 Sawojajar
                            </button>
                            <button 
                                type="button" 
                                onclick="selectQuickArea('Batu / Dau', -7.9150, 112.5650)"
                                class="quick-area-chip text-[10px] font-bold py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200/80 transition"
                            >
                                ⛰️ Dau / Batu
                            </button>
                        </div>
                    </div>

                    <!-- Textarea Catatan & Patokan Lengkap -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] text-slate-400 mb-1">
                            <span class="font-bold uppercase tracking-wider">Detail Alamat & Patokan Rumah</span>
                            <span id="manual-status-badge" class="font-bold text-emerald-600 hidden items-center gap-0.5">
                                ✓ Titik Dipilih
                            </span>
                        </div>
                        <textarea 
                            id="delivery_location_manual" 
                            rows="2" 
                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-xs sm:text-sm leading-relaxed p-2.5" 
                            placeholder="Contoh: Jl. Bunga Coklat No. 15, Rumah Cat Biru Pagar Putih"
                        ></textarea>
                    </div>
                </div>

                <!-- Hidden inputs yang dikirim ke Controller -->
                <input type="hidden" name="delivery_location" id="delivery_location" value="{{ session('customer_location', 'Malang, Jawa Timur') }}">
                <input type="hidden" name="destination_lat" id="destination_lat" value="-7.9839">
                <input type="hidden" name="destination_lng" id="destination_lng" value="112.6214">
                <input type="hidden" name="delivery_fee" id="delivery_fee_input" value="{{ $routeCalculation['estimated_fare'] }}">
            </div>

            <!-- 2. VISUALISASI RUTE BERANTAI MULTI-TOKO -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl shadow-xs border border-slate-200/80">
                <!-- Header Rute (Clean & Compact) -->
                <div class="flex items-center justify-between gap-2 pb-2.5 mb-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                            🗺️
                        </span>
                        <div class="min-w-0">
                            <h2 class="font-black text-slate-800 text-xs sm:text-sm truncate">Rute Pengantaran Berantai</h2>
                            <p class="text-[10px] text-slate-400 truncate">Urutan belanja jastiper ke lokasimu</p>
                        </div>
                    </div>
                    
                    <span id="route-total-distance-badge" class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-900 text-white font-black text-[11px] shrink-0 tracking-tight">
                        {{ $routeCalculation['total_distance_km'] }} KM Total
                    </span>
                </div>

                <!-- Visual Step-by-Step Chain Timeline -->
                <div class="relative pl-5 sm:pl-6 space-y-3.5 my-2.5 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-gradient-to-b before:from-rose-500 before:via-indigo-500 before:to-emerald-500">
                    
                    <!-- Toko-Toko Singgah -->
                    @foreach($merchantLocations as $idx => $mLoc)
                    <div class="relative">
                        <!-- Bullet point icon -->
                        <span class="absolute -left-5 sm:-left-6 top-1 w-4.5 h-4.5 sm:w-5 sm:h-5 rounded-full bg-white border-2 border-rose-500 shadow-2xs flex items-center justify-center text-[9px] sm:text-[10px] font-black text-rose-600">
                            {{ $idx + 1 }}
                        </span>
                        
                        <div class="bg-slate-50/80 p-2 sm:p-2.5 rounded-xl border border-slate-100 transition">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-rose-600 truncate">
                                    Toko {{ $idx + 1 }}
                                </span>
                                <span class="text-[9px] sm:text-[10px] text-slate-400 font-semibold shrink-0">Titik Belanja</span>
                            </div>
                            <div class="font-bold text-xs text-slate-800 mt-0.5 truncate">{{ $mLoc['name'] }}</div>
                            <div class="text-[10px] text-slate-500 truncate mt-0.5">{{ $mLoc['address'] ?? 'Kota Malang' }}</div>
                        </div>

                        <!-- Leg Distance Indicator to Next Stop -->
                        @if(isset($routeCalculation['legs'][$idx]))
                        @php $leg = $routeCalculation['legs'][$idx]; @endphp
                        <div class="my-1.5 pl-1 flex items-center gap-1.5 flex-wrap">
                            <div class="w-2.5 border-t border-dashed border-slate-300"></div>
                            <span class="leg-distance-badge inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-white px-2 py-0.5 rounded-md border border-slate-200/90 shadow-2xs" data-leg-index="{{ $idx }}">
                                <svg class="w-2.5 h-2.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                <span class="leg-dist-text truncate max-w-[210px] sm:max-w-none">
                                    Jarak: <b>{{ $leg['distance_km'] }} KM</b>
                                    @if($leg['to_type'] === 'merchant')
                                        (ke Toko {{ $idx + 2 }})
                                    @else
                                        (ke Tujuan)
                                    @endif
                                </span>
                            </span>
                        </div>
                        @endif
                    </div>
                    @endforeach

                    <!-- Alamat Tujuan Akhir (Customer) -->
                    <div class="relative">
                        <!-- Bullet point icon -->
                        <span class="absolute -left-5 sm:-left-6 top-1 w-4.5 h-4.5 sm:w-5 sm:h-5 rounded-full bg-emerald-500 border-2 border-white shadow-2xs flex items-center justify-center text-[9px] sm:text-[10px] text-white">
                            ✓
                        </span>
                        
                        <div class="bg-emerald-50/70 p-2 sm:p-2.5 rounded-xl border border-emerald-100">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-emerald-700">Tujuan Akhir</span>
                                <span class="text-[9px] sm:text-[10px] text-emerald-600 font-semibold shrink-0">Alamat Penerima</span>
                            </div>
                            <div class="font-bold text-xs text-emerald-950 mt-0.5 truncate" id="route-dest-display">
                                {{ session('customer_location', 'Alamat Pengiriman Customer') }}
                            </div>
                            <div class="text-[9px] sm:text-[10px] text-emerald-700 mt-0.5">Barang diantar langsung setelah belanja selesai</div>
                        </div>
                    </div>
                </div>

                <!-- Info Rincian Ongkir & Jarak -->
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 bg-slate-50/60 -mx-3.5 sm:-mx-4 -mb-3.5 sm:-mb-4 p-2.5 sm:p-3 rounded-b-2xl flex items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-1 text-slate-600 text-[10px] sm:text-[11px] truncate">
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="truncate">Estimasi Ongkir Jastip:</span>
                    </div>
                    <div class="font-black text-xs sm:text-sm text-slate-800 shrink-0" id="route-fare-display">
                        Rp{{ number_format($routeCalculation['estimated_fare'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <!-- 3. DAFTAR BARANG PER TOKO (MULTI-MERCHANT GROUPED ITEMS) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h2 class="font-black text-slate-800 text-xs sm:text-sm flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                            🛍️
                        </span>
                        Daftar Belanjaan
                    </h2>
                    <span class="text-[11px] font-bold text-slate-400">
                        {{ count($groupedItems) }} Toko
                    </span>
                </div>

                @php $totalBarang = 0; @endphp

                @foreach($groupedItems as $mId => $items)
                @php
                    $merchant = $merchants->get($mId);
                    $storeSubtotal = 0;
                    foreach($items as $it) {
                        $storeSubtotal += ($it->product->final_price * $it->quantity);
                        $totalBarang += ($it->product->final_price * $it->quantity);
                    }
                @endphp
                
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                    <!-- Store Header Card -->
                    <div class="bg-slate-50/90 px-3.5 py-2.5 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="px-1.5 py-0.5 rounded-md bg-rose-600 text-white font-black text-[9px] shrink-0">
                                Toko {{ $loop->iteration }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-black text-xs text-slate-800 truncate">{{ $merchant->name ?? 'Toko' }}</h3>
                                <p class="text-[9px] text-slate-400 truncate">{{ $merchant->address ?? 'Kota Malang' }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[9px] text-slate-400 block leading-tight">Subtotal</span>
                            <span class="font-bold text-xs text-slate-700">Rp{{ number_format($storeSubtotal, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Items in This Store -->
                    <div class="divide-y divide-slate-100 p-3 sm:p-3.5 space-y-2.5">
                        @foreach($items as $item)
                        <div class="pt-2.5 first:pt-0 flex items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <!-- Product Thumbnail / Icon -->
                                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-slate-100 border border-slate-200/60 shrink-0 overflow-hidden flex items-center justify-center">
                                    @if($item->product->image_url)
                                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-lg">📦</span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <h4 class="font-bold text-xs text-slate-800 truncate max-w-[140px] sm:max-w-none">{{ $item->product->name }}</h4>
                                        @if($item->product->has_discount)
                                            <span class="bg-rose-50 text-rose-600 text-[8px] font-black px-1 py-0.2 rounded border border-rose-200 shrink-0">
                                                -{{ $item->product->discount_percentage }}%
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] font-semibold text-rose-600 mt-0.5">
                                        Rp{{ number_format($item->product->final_price, 0, ',', '.') }}
                                        @if($item->product->has_discount)
                                            <span class="text-[9px] text-slate-400 line-through ml-1">
                                                Rp{{ number_format($item->product->estimated_price, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Qty Modifier & Subtotal Item -->
                            <div class="flex items-center gap-2.5 shrink-0">
                                <!-- Qty Controls -->
                                <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                                    <button 
                                        type="button" 
                                        onclick="updateCartItemQty({{ $item->id }}, {{ $item->quantity - 1 }})" 
                                        class="w-6 h-6 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition font-bold text-xs"
                                        title="Kurangi Qty"
                                    >
                                        -
                                    </button>
                                    <span class="w-6 text-center font-bold text-xs text-slate-800 bg-white py-0.5">
                                        {{ $item->quantity }}
                                    </span>
                                    <button 
                                        type="button" 
                                        onclick="updateCartItemQty({{ $item->id }}, {{ $item->quantity + 1 }})" 
                                        class="w-6 h-6 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition font-bold text-xs"
                                        title="Tambah Qty"
                                    >
                                        +
                                    </button>
                                </div>

                                <!-- Item Total & Remove button -->
                                <div class="text-right min-w-[65px]">
                                    <div class="font-black text-xs text-slate-800">
                                        Rp{{ number_format($item->product->final_price * $item->quantity, 0, ',', '.') }}
                                    </div>
                                    <button 
                                        type="button" 
                                        onclick="removeCartItem({{ $item->id }})" 
                                        class="text-[9px] text-slate-400 hover:text-rose-600 transition inline-flex items-center gap-0.5 mt-0.5"
                                    >
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                <!-- Tombol Belanja Tambahan dari Toko Lain -->
                <a href="{{ route('customer.dashboard') }}" class="block w-full text-center py-2 px-3 rounded-xl border border-dashed border-rose-300 bg-rose-50/50 hover:bg-rose-50 text-rose-600 text-xs font-bold transition">
                    + Tambah Barang dari Toko Lain
                </a>
            </div>

            <!-- 4. RINGKASAN PEMBAYARAN & SKEMA DP 50% -->
            @php
                $initialOngkir = $routeCalculation['estimated_fare'];
                $dpBarang = $totalBarang * 0.5;
                $totalDpAwal = $dpBarang + $initialOngkir;
            @endphp
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl shadow-xs border border-slate-200/80 space-y-2.5">
                <h2 class="font-black text-slate-800 text-xs sm:text-sm">Ringkasan Pembayaran</h2>
                
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Total Harga Barang</span>
                        <span class="font-bold text-slate-800">Rp{{ number_format($totalBarang, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600">
                        <span class="truncate pr-2">
                            Ongkir Jastip ({{ $routeCalculation['total_distance_km'] }} KM, {{ count($merchantLocations) }} Toko)
                        </span>
                        <span class="font-bold text-slate-800 shrink-0" id="billing-ongkir-display">Rp{{ number_format($initialOngkir, 0, ',', '.') }}</span>
                    </div>

                    @if($routeCalculation['extra_stops_count'] > 0)
                    <div class="flex justify-between items-center text-[10px] text-slate-500 pl-2.5 border-l-2 border-rose-200">
                        <span>Biaya Singgah ({{ $routeCalculation['extra_stops_count'] }} Toko Tambahan)</span>
                        <span class="font-medium text-slate-700 shrink-0">+Rp{{ number_format($routeCalculation['extra_stops_fare'], 0, ',', '.') }}</span>
                    </div>
                    @endif
                    
                    <hr class="border-slate-100 my-1.5">
                    
                    <!-- Box Skema DP 50% -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50/40 border border-blue-200/80 p-3 rounded-xl">
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <h3 class="font-black text-blue-900 text-xs">Skema Downpayment (DP) 50%</h3>
                            <span class="px-1.5 py-0.2 rounded-full bg-blue-100 text-blue-700 text-[8px] font-black uppercase">Wajib DP</span>
                        </div>
                        <p class="text-[9px] sm:text-[10px] text-blue-700/80 mb-2 leading-relaxed">
                            DP 50% barang + ongkir dibayar via JKPay / Midtrans <b>setelah pesanan diterima Jastiper</b>.
                        </p>
                        
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between items-center text-blue-900">
                                <span>DP Barang (50%)</span>
                                <span class="font-bold">Rp{{ number_format($dpBarang, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-blue-900">
                                <span>Ongkir Penuh</span>
                                <span class="font-bold" id="dp-ongkir-display">Rp{{ number_format($initialOngkir, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        
                        <div class="mt-2 pt-1.5 border-t border-blue-200 flex justify-between items-center">
                            <span class="font-black text-blue-950 text-xs">Total DP Dibayar</span>
                            <span class="font-black text-blue-950 text-sm" id="billing-total-dp-display">
                                Rp{{ number_format($totalDpAwal, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-1.5 bg-amber-50/70 border border-amber-200/60 text-amber-900 p-2 rounded-xl text-[10px] leading-relaxed">
                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        <span>Sisa 50% harga barang dibayar <b>Tunai/Cash</b> ke Jastiper saat barang tiba sesuai nota asli.</span>
                    </div>
                </div>
            </div>

            <!-- 5. STICKY BOTTOM CHECKOUT BAR (PERFECT MOBILE PROPORTIONS) -->
            <div class="fixed bottom-0 left-0 right-0 p-3 sm:p-4 bg-white/95 backdrop-blur-md border-t border-slate-200 z-50 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
                <div class="max-w-xl mx-auto flex items-center justify-between gap-2.5">
                    <div class="flex flex-col min-w-0">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Total DP (50% + Ongkir)</span>
                        <div class="flex items-baseline gap-1 flex-wrap">
                            <span class="font-black text-rose-600 text-base sm:text-lg leading-tight" id="sticky-total-dp">
                                Rp{{ number_format($totalDpAwal, 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-semibold" id="sticky-distance-summary">
                                ({{ $routeCalculation['total_distance_km'] }} KM)
                            </span>
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-extrabold px-4.5 py-2.5 sm:px-6 sm:py-3 rounded-full text-xs sm:text-sm shadow-md shadow-rose-600/30 transition inline-flex items-center gap-1.5 shrink-0"
                    >
                        <span>Cari Jastiper</span>
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </div>
        </form>

    </div>
    @endif
</div>

<!-- Form tersembunyi untuk Update Qty & Remove -->
<form id="remove-item-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const TOTAL_BARANG = {{ $totalBarang ?? 0 }};

/**
 * Update kuantitas barang di keranjang belanja
 */
function updateCartItemQty(cartItemId, newQty) {
    if (newQty < 1) {
        if (confirm('Hapus barang ini dari keranjang?')) {
            removeCartItem(cartItemId);
        }
        return;
    }

    fetch(`/customer/cart/update-qty/${cartItemId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ quantity: newQty })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Gagal mengubah kuantitas.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
    });
}

/**
 * Hapus barang dari keranjang belanja
 */
function removeCartItem(cartItemId) {
    const form = document.getElementById('remove-item-form');
    form.action = `/customer/cart/remove/${cartItemId}`;
    form.submit();
}

/**
 * Format angka ke format Rupiah
 */
function formatRupiah(num) {
    return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

let currentMode = 'gps';

/**
 * Ganti Mode Alamat: GPS vs Kirim ke Tempat Lain (Manual)
 */
function switchLocationMode(mode) {
    currentMode = mode;
    const tabGps = document.getElementById('tab-btn-gps');
    const tabManual = document.getElementById('tab-btn-manual');
    const panelGps = document.getElementById('panel-location-gps');
    const panelManual = document.getElementById('panel-location-manual');
    const hiddenLocation = document.getElementById('delivery_location');
    const routeDestDisplay = document.getElementById('route-dest-display');

    if (mode === 'gps') {
        // Tab Styles
        tabGps.className = 'py-2 px-2.5 rounded-lg transition flex items-center justify-center gap-1.5 bg-white text-rose-600 shadow-2xs border border-slate-200/50';
        tabManual.className = 'py-2 px-2.5 rounded-lg transition flex items-center justify-center gap-1.5 text-slate-500 hover:text-slate-800';
        
        // Panels
        panelGps.classList.remove('hidden');
        panelManual.classList.add('hidden');

        // Sync hidden location
        const gpsAddr = document.getElementById('delivery_location_gps').value;
        if (hiddenLocation) hiddenLocation.value = gpsAddr;
        if (routeDestDisplay) routeDestDisplay.innerText = gpsAddr || 'Alamat Lokasi GPS';

        // Re-read or refresh GPS
        detectDeviceLocation(true);
    } else {
        // Tab Styles
        tabManual.className = 'py-2 px-2.5 rounded-lg transition flex items-center justify-center gap-1.5 bg-white text-rose-600 shadow-2xs border border-slate-200/50';
        tabGps.className = 'py-2 px-2.5 rounded-lg transition flex items-center justify-center gap-1.5 text-slate-500 hover:text-slate-800';

        // Panels
        panelManual.classList.remove('hidden');
        panelGps.classList.add('hidden');

        // Jika manual belum ada isinya, default ke Malang Kota
        const manualAddrInput = document.getElementById('delivery_location_manual');
        if (!manualAddrInput.value || manualAddrInput.value.trim() === '') {
            selectQuickArea('Klojen / Pusat Kota', -7.9829, 112.6308);
        } else {
            if (hiddenLocation) hiddenLocation.value = manualAddrInput.value;
            if (routeDestDisplay) routeDestDisplay.innerText = manualAddrInput.value;
        }
    }
}

/**
 * Pilih cepat area populer di Malang Raya
 */
function selectQuickArea(areaName, lat, lng) {
    const hiddenLocation = document.getElementById('delivery_location');
    const manualAddrInput = document.getElementById('delivery_location_manual');
    const searchInput = document.getElementById('manual_search_input');
    const inputLat = document.getElementById('destination_lat');
    const inputLng = document.getElementById('destination_lng');
    const routeDestDisplay = document.getElementById('route-dest-display');
    const badge = document.getElementById('manual-status-badge');
    const feedback = document.getElementById('search-feedback-text');

    // Highlight chip
    document.querySelectorAll('.quick-area-chip').forEach(btn => {
        btn.classList.remove('bg-rose-600', 'text-white', 'border-rose-600');
        btn.classList.add('bg-slate-100', 'text-slate-700');
        if (btn.innerText.includes(areaName.split(' ')[0])) {
            btn.classList.remove('bg-slate-100', 'text-slate-700');
            btn.classList.add('bg-rose-600', 'text-white', 'border-rose-600');
        }
    });

    const fullAddr = `${areaName}, Kota Malang`;
    if (searchInput) searchInput.value = areaName;
    if (manualAddrInput) manualAddrInput.value = fullAddr;
    if (hiddenLocation) hiddenLocation.value = fullAddr;
    if (inputLat) inputLat.value = lat;
    if (inputLng) inputLng.value = lng;
    if (routeDestDisplay) routeDestDisplay.innerText = fullAddr;

    if (badge) {
        badge.innerText = `✓ Titik: ${areaName}`;
        badge.classList.remove('hidden');
        badge.classList.add('inline-flex');
    }

    if (feedback) {
        feedback.innerText = `✓ Titik berhasil diset ke ${fullAddr} (${lat}, ${lng})`;
        feedback.className = 'text-[10px] text-emerald-600 font-semibold mt-1 block';
    }

    // Hitung ulang rute multi-toko berantai ke titik baru ini
    recalculateRoute(lat, lng);
}

/**
 * Cari alamat manual secara geocoding (OpenStreetMap)
 */
async function searchManualAddress() {
    const searchInput = document.getElementById('manual_search_input');
    const feedback = document.getElementById('search-feedback-text');
    const btnSearch = document.getElementById('btn-search-manual');
    const hiddenLocation = document.getElementById('delivery_location');
    const manualAddrInput = document.getElementById('delivery_location_manual');
    const inputLat = document.getElementById('destination_lat');
    const inputLng = document.getElementById('destination_lng');
    const routeDestDisplay = document.getElementById('route-dest-display');
    const badge = document.getElementById('manual-status-badge');

    if (!searchInput || !searchInput.value.trim()) {
        alert('Silakan ketik nama jalan atau area terlebih dahulu.');
        return;
    }

    const query = searchInput.value.trim();
    if (btnSearch) btnSearch.innerText = 'Mencari...';
    if (feedback) {
        feedback.innerText = 'Sedang mencari titik koordinat alamat...';
        feedback.className = 'text-[10px] text-indigo-600 font-semibold mt-1 block';
    }

    try {
        // Coba cari dengan embel-embel Malang terlebih dahulu untuk akurasi jastip lokal
        let res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ' Malang')}&limit=1`, {
            headers: { 'Accept-Language': 'id' }
        });
        let data = await res.json();

        // Jika tidak ketemu, cari secara umum
        if (!data || data.length === 0) {
            res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`, {
                headers: { 'Accept-Language': 'id' }
            });
            data = await res.json();
        }

        if (data && data.length > 0) {
            const place = data[0];
            const lat = parseFloat(place.lat);
            const lng = parseFloat(place.lon);
            const shortName = place.display_name;

            if (manualAddrInput) manualAddrInput.value = shortName;
            if (hiddenLocation) hiddenLocation.value = shortName;
            if (inputLat) inputLat.value = lat;
            if (inputLng) inputLng.value = lng;
            if (routeDestDisplay) routeDestDisplay.innerText = shortName;

            if (badge) {
                badge.innerText = `✓ Titik Ditemukan`;
                badge.classList.remove('hidden');
                badge.classList.add('inline-flex');
            }

            if (feedback) {
                feedback.innerText = `✓ Ditemukan: ${place.name || query} (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                feedback.className = 'text-[10px] text-emerald-600 font-semibold mt-1 block';
            }

            // Hitung ulang rute multi-toko berantai
            recalculateRoute(lat, lng);
        } else {
            if (feedback) {
                feedback.innerText = 'Titik tidak ditemukan. Silakan klik salah satu pilihan area cepat di bawah.';
                feedback.className = 'text-[10px] text-rose-500 font-semibold mt-1 block';
            }
        }
    } catch (err) {
        console.error('Nominatim search error:', err);
        if (feedback) {
            feedback.innerText = 'Gagal mencari alamat otomatis. Silakan pilih salah satu area cepat di bawah.';
            feedback.className = 'text-[10px] text-rose-500 font-semibold mt-1 block';
        }
    } finally {
        if (btnSearch) btnSearch.innerText = 'Cari Titik';
    }
}

/**
 * Deteksi koordinat GPS perangkat dan kalkulasi rute berantai multi-toko secara real-time
 */
function detectDeviceLocation(isAutomatic = false) {
    if (currentMode !== 'gps') return; // Jangan override jika user sedang mode manual

    const btnRefresh = document.getElementById('btn-gps-refresh-label');
    const badge = document.getElementById('gps-status-badge');
    const inputLocationGps = document.getElementById('delivery_location_gps');
    const hiddenLocation = document.getElementById('delivery_location');
    const inputLat = document.getElementById('destination_lat');
    const inputLng = document.getElementById('destination_lng');
    const routeDestDisplay = document.getElementById('route-dest-display');

    if (!navigator.geolocation) {
        if (!isAutomatic) {
            alert('Browser Anda tidak mendukung deteksi lokasi (Geolocation).');
        }
        return;
    }

    if (btnRefresh) btnRefresh.innerText = 'Membaca...';

    navigator.geolocation.getCurrentPosition(
        async function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            if (inputLat) inputLat.value = lat;
            if (inputLng) inputLng.value = lng;

            try {
                // Reverse geocoding via OpenStreetMap Nominatim
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
                            addr.city || addr.town || addr.county || 'Malang'
                        ].filter(Boolean).join(', ');

                        if (!shortAddress) shortAddress = data.display_name;

                        if (inputLocationGps) inputLocationGps.value = shortAddress;
                        if (hiddenLocation) hiddenLocation.value = shortAddress;
                        if (routeDestDisplay) routeDestDisplay.innerText = shortAddress;
                    }
                }
            } catch (err) {
                console.warn('Reverse geocoding error:', err);
                const fallback = `Lokasi GPS: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                if (inputLocationGps) inputLocationGps.value = fallback;
                if (hiddenLocation) hiddenLocation.value = fallback;
            }

            // Hitung ulang rute multi-toko & tarif via server endpoint
            recalculateRoute(lat, lng);

            if (btnRefresh) btnRefresh.innerText = 'Ambil Ulang GPS';
            if (badge) {
                badge.classList.remove('hidden');
                badge.classList.add('inline-flex');
            }
        },
        function (error) {
            console.warn('Geolocation error:', error.message);
            if (btnRefresh) btnRefresh.innerText = 'Ambil Ulang GPS';
            if (!isAutomatic) {
                let msg = 'Gagal mengakses GPS perangkat.';
                if (error.code === error.PERMISSION_DENIED) {
                    msg = 'Izin lokasi browser ditolak. Mohon izinkan akses lokasi di setelan browser.';
                }
                alert(msg);
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 30000
        }
    );
}

/**
 * Panggil endpoint AJAX untuk kalkulasi rute multi-toko berantai
 */
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
        const dpBarang = TOTAL_BARANG * 0.5;
        const totalDp = dpBarang + estimatedFare;

        // Update hidden input delivery fee
        const inputFee = document.getElementById('delivery_fee_input');
        if (inputFee) inputFee.value = estimatedFare;

        // Update badges
        const routeBadge = document.getElementById('route-total-distance-badge');
        if (routeBadge) routeBadge.innerText = `${totalDist} KM Total`;

        const routeFare = document.getElementById('route-fare-display');
        if (routeFare) routeFare.innerText = formatRupiah(estimatedFare);

        const billingOngkir = document.getElementById('billing-ongkir-display');
        if (billingOngkir) billingOngkir.innerText = formatRupiah(estimatedFare);

        const dpOngkir = document.getElementById('dp-ongkir-display');
        if (dpOngkir) dpOngkir.innerText = formatRupiah(estimatedFare);

        const billingTotalDp = document.getElementById('billing-total-dp-display');
        if (billingTotalDp) billingTotalDp.innerText = formatRupiah(totalDp);

        const stickyTotalDp = document.getElementById('sticky-total-dp');
        if (stickyTotalDp) stickyTotalDp.innerText = formatRupiah(totalDp);

        const stickyDist = document.getElementById('sticky-distance-summary');
        if (stickyDist) stickyDist.innerText = `(${totalDist} KM)`;

        // Update leg terakhir di timeline
        const legs = data.legs || [];
        if (legs.length > 0) {
            const lastLegIdx = legs.length - 1;
            const lastLeg = legs[lastLegIdx];
            const badgeEl = document.querySelector(`.leg-distance-badge[data-leg-index="${lastLegIdx}"] .leg-dist-text`);
            if (badgeEl) {
                badgeEl.innerHTML = `Jarak: <b>${lastLeg.distance_km} KM</b> (ke Tujuan)`;
            }
        }
    })
    .catch(err => {
        console.error('Recalculate route error:', err);
    });
}

// Sinkronkan alamat ketikan dengan display rute
document.addEventListener('DOMContentLoaded', function () {
    const inputGps = document.getElementById('delivery_location_gps');
    const inputManual = document.getElementById('delivery_location_manual');
    const hiddenLocation = document.getElementById('delivery_location');
    const routeDestDisplay = document.getElementById('route-dest-display');

    if (inputGps) {
        inputGps.addEventListener('input', function () {
            if (currentMode === 'gps') {
                if (hiddenLocation) hiddenLocation.value = this.value;
                if (routeDestDisplay) routeDestDisplay.innerText = this.value || 'Alamat Lokasi GPS';
            }
        });
    }

    if (inputManual) {
        inputManual.addEventListener('input', function () {
            if (currentMode === 'manual') {
                if (hiddenLocation) hiddenLocation.value = this.value;
                if (routeDestDisplay) routeDestDisplay.innerText = this.value || 'Alamat Tujuan Pengiriman';
            }
        });
    }

    // Jalankan deteksi GPS awal hanya jika ada di mode GPS
    detectDeviceLocation(true);
});
</script>
@endsection
