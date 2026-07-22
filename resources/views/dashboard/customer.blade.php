@extends('layouts.support')

@section('title', 'Dashboard Customer')

@section('content')
<div class="min-h-screen bg-[#F3F4F6] pb-16">
    <!-- Desktop Search Header & Profile (Gojek App Bar Style) -->
    <div class="bg-white border-b border-slate-200/80 sticky top-20 z-40 px-4 py-3.5 shadow-sm">
        <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
            
            <!-- Search Bar (Gojek Style) -->
            <div class="relative flex-grow">
                <input 
                    type="text" 
                    placeholder="Cari makanan khas Malang, minimarket, atau jastiper..." 
                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-700 pl-11 pr-4 py-2.5 rounded-full text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150"
                >
                <div class="absolute left-4 top-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <!-- Profile Info (Gojek Circular Profile Icon) -->
            <div class="flex items-center gap-3">
                <div class="relative group cursor-pointer">
                    <div class="w-10 h-10 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center font-bold text-sm text-rose-500 shadow-md">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                </div>
                
                <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 p-2.5 rounded-full transition border border-slate-200" title="Keluar Sesi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-md mx-auto px-4 mt-6 space-y-6">
        
        <!-- Welcome Greeting -->
        <div>
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Selamat Datang</span>
            <h2 class="font-display font-black text-xl text-slate-900 leading-none mt-1">{{ $customer->name }}</h2>
        </div>

        <!-- Gopay Card Layout (JastipKuy Pay) -->
        <div class="bg-gradient-to-r from-sky-600 to-sky-700 text-white rounded-3xl p-5 shadow-lg border border-sky-500/20 relative overflow-hidden">
            <!-- Decorative Accent circles inside card -->
            <div class="absolute -right-12 -top-12 w-28 h-28 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute -left-6 -bottom-6 w-20 h-20 bg-white/5 rounded-full blur-md"></div>
            
            <div class="flex items-stretch justify-between relative z-10 gap-3">
                <!-- Left Section: Balance & Brand -->
                <div class="flex flex-col justify-between space-y-3 pr-4 border-r border-white/20">
                    <div class="flex items-center gap-1.5">
                        <!-- Tiny GoPay-style Logo -->
                        <span class="text-[10px] font-black tracking-tighter bg-white text-sky-700 px-2 py-0.5 rounded-sm uppercase">JK PAY</span>
                    </div>
                    <div>
                        <div class="text-2xl font-black font-display tracking-tight">Rp0</div>
                        <span class="text-[8px] text-sky-200 font-semibold block">Tap untuk riwayat dompet</span>
                    </div>
                </div>

                <!-- Right Section: Quick Action Buttons (Gojek Icon Row Style) -->
                <div class="flex-grow grid grid-cols-4 gap-2 items-center justify-between text-center">
                    
                    <!-- Bayar (Pay) -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 11v3m5-13H7m0 10h10m-5-10v10"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Bayar</span>
                    </button>

                    <!-- Top Up -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Isi Saldo</span>
                    </button>

                    <!-- Riwayat -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Riwayat</span>
                    </button>

                    <!-- Eksplor -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Lainnya</span>
                    </button>

                </div>
            </div>
        </div>

        <!-- Instant Booking Banner (Gojek Promo style) -->
        <a href="{{ route('customer.booking') }}" class="block bg-slate-950 border border-slate-800 text-white rounded-3xl p-4 shadow-sm hover:scale-[1.01] transition duration-150 relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-rose-600/20 rounded-full blur-lg"></div>
            <div class="flex justify-between items-center gap-3 relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-rose-600 rounded-2xl flex items-center justify-center text-lg shrink-0">
                        🗺️
                    </div>
                    <div>
                        <h4 class="font-display font-black text-xs uppercase tracking-wider text-rose-500">Booking Jastiper</h4>
                        <p class="text-[10px] text-slate-300 font-semibold mt-0.5 leading-tight">Lihat Jastiper yang sedang check-in di Mie Gacoan & Toko terdekat!</p>
                    </div>
                </div>
                <span class="text-xs bg-rose-600 hover:bg-rose-700 text-white font-extrabold px-3 py-1.5 rounded-full uppercase tracking-wider shrink-0">
                    Cek
                </span>
            </div>
        </a>

        <!-- JastipKuy Services Horizontal Row (Menu Grid Redesign with Flexbox to force horizontal layout) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-4">
            <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Layanan Belanja Jastip</h3>
            
            <div class="flex items-start justify-between text-center px-1">
                
                <!-- Jastip Kuliner -->
                <a href="{{ route('customer.orders.create') }}?cat=beli-antar" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-rose-600 hover:bg-rose-700 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-rose-600/20 shrink-0">
                        <!-- Food Cloche Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 00-9 9h18a9 9 0 00-9-9zM3 12h18M5 12v3a2 2 0 002 2h10a2 2 0 002-2v-3M12 2v1" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Kuliner<br><span class="text-[8px] text-slate-500 font-medium">(Kuliner)</span></span>
                </a>

                <!-- Jastip Minimarket -->
                <a href="{{ route('customer.orders.create') }}?cat=toko-kirim" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-sky-600 hover:bg-sky-700 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-sky-600/20 shrink-0">
                        <!-- Shopping Bag Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Toko<br><span class="text-[8px] text-slate-500 font-medium">(Minimarket)</span></span>
                </a>

                <!-- Jastip Pasar -->
                <a href="{{ route('customer.orders.create') }}?cat=ambil-antar" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-amber-500 hover:bg-amber-600 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-amber-500/20 shrink-0">
                        <!-- Shopping Cart Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Pasar<br><span class="text-[8px] text-slate-500 font-medium">(Pasar)</span></span>
                </a>

                <!-- Jastip Bebas -->
                <a href="{{ route('customer.orders.create') }}?cat=kirim-pihak-ketiga" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-emerald-600 hover:bg-emerald-700 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-emerald-600/20 shrink-0">
                        <!-- 3D Box Package Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Bebas<br><span class="text-[8px] text-slate-500 font-medium">(Titip Bebas)</span></span>
                </a>

            </div>
        </div>

        <!-- Promos Ads Banner Carousel (Redesign Iklan Gojek) -->
        <div class="space-y-3">
            <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Promo Rekomendasi</h3>
            
            <div class="w-full bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-3xl p-5 shadow-sm relative overflow-hidden border border-rose-400/20">
                <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-lg"></div>
                <div class="relative z-10 space-y-3">
                    <span class="text-[8px] bg-white text-rose-600 px-2 py-0.5 rounded-full font-black uppercase tracking-wider">Promo Khusus</span>
                    <div>
                        <h4 class="font-display font-black text-sm">Diskon Ongkir Jastip s.d 50% 🚀</h4>
                        <p class="text-[9px] text-rose-100 mt-1 max-w-[240px]">Belanja di mana saja se-Malang Raya lebih murah menggunakan kurir mitra JastipKuy Pro.</p>
                    </div>
                    <div class="text-[8px] text-rose-200/90 font-mono">*Syarat & ketentuan berlaku.</div>
                </div>
            </div>
        </div>

        <!-- Pelacakan Pesanan Aktif (Active Order Tracker Card) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-4">
            <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Pesanan Aktif Anda</h3>
            
            @forelse($orders as $order)
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 text-left">
                        <div class="w-10 h-10 bg-rose-50 rounded-full flex items-center justify-center shrink-0 text-lg">
                            @php
                                $categoryIcons = [
                                    'beli-antar' => '🍔',
                                    'ambil-antar' => '🛍️',
                                    'toko-kirim' => '🛒',
                                    'dokumen' => '📄',
                                    'multi-stop' => '📍',
                                    'kirim-pihak-ketiga' => '🚀',
                                ];
                                $catIcon = $categoryIcons[$order->category] ?? '📦';
                            @endphp
                            {{ $catIcon }}
                        </div>
                        <div>
                            <h4 class="font-bold text-[11px] text-slate-750 line-clamp-1">{{ $order->description }}</h4>
                            <p class="text-[9px] text-slate-400 leading-normal mt-0.5">
                                Status: 
                                <span class="font-extrabold uppercase {{ $order->status === 'menunggu_tawaran' ? 'text-amber-500' : 'text-emerald-500' }}">
                                    {{ $order->status === 'menunggu_tawaran' ? 'Menunggu Jastiper' : 'Sedang Diproses' }}
                                </span>
                            </p>
                            @if($order->jastiper)
                                <p class="text-[8px] text-slate-500 mt-0.5">Mitra Jastiper: <b>{{ $order->jastiper->name }}</b> ({{ $order->jastiper->phone_number }})</p>
                            @endif
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="text-[8px] uppercase font-bold text-slate-400 block tracking-wide">Ongkos Kirim</span>
                        <span class="text-xs font-black text-rose-600">Rp {{ number_format($order->estimated_fare, 0, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 text-left">
                        <div class="w-10 h-10 bg-slate-200/50 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-[11px] text-slate-700">Belum Ada Belanjaan Aktif</h4>
                            <p class="text-[9px] text-slate-400 leading-normal mt-0.5">Riwayat & posisi kurir akan muncul di sini setelah memesan.</p>
                        </div>
                    </div>

                    <a href="{{ route('customer.orders.create') }}" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] px-4 py-2.5 rounded-full transition uppercase tracking-wider whitespace-nowrap shadow-sm">
                        Pesan Jastip
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Jastiper Favorit Real-time Status -->
        @if($customer->favorites->isNotEmpty())
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-3">
                <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Jastiper Favorit Anda</h3>
                <div class="space-y-2">
                    @foreach($customer->favorites as $fav)
                        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs shadow-inner" x-data="{ available: @json($fav->is_available), checkin: @json($fav->checkin_location) }">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2.5 h-2.5 rounded-full" :class="available ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></div>
                                <div>
                                    <span class="font-bold text-slate-800">{{ $fav->name }}</span>
                                    <span class="text-[9px] text-slate-400 block mt-0.5" x-text="checkin ? '📍 Sedang di ' + checkin : 'Tidak check-in'"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="
                                    fetch('/customer/jastiper/{{ $fav->id }}/availability')
                                        .then(r => r.json())
                                        .then(data => {
                                            available = data.is_available;
                                            checkin = data.checkin_location;
                                        })
                                " class="bg-white hover:bg-slate-100 border border-slate-200 px-2 py-1 rounded-full text-[9px] font-bold text-slate-600 transition shrink-0 uppercase tracking-wider">
                                    🔄 Cek
                                </button>
                                <a :href="available ? '{{ route('customer.orders.create') }}?jastiper_id={{ $fav->id }}' : '#'" :class="available ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-sm' : 'bg-slate-200 text-slate-400 cursor-not-allowed'" class="px-3 py-1 rounded-full text-[9px] font-black transition shrink-0 uppercase tracking-wider">
                                    Pesan
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
