@extends('layouts.support')

@section('title', 'Resto Sekitarmu - JastipKuy')

@section('content')
<div class="min-h-screen bg-[#F6F7F9] pb-24 text-slate-800" x-data="{
    detectingGps: false,
    init() {
        @if(!request('lat') || !request('lng'))
        if (navigator.geolocation) {
            this.detectingGps = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const url = new URL(window.location.href);
                    url.searchParams.set('lat', lat);
                    url.searchParams.set('lng', lng);
                    if (!url.searchParams.has('tab')) {
                        url.searchParams.set('tab', 'terdekat');
                    }
                    window.location.replace(url.toString());
                },
                (err) => {
                    this.detectingGps = false;
                },
                { enableHighAccuracy: true, timeout: 6000 }
            );
        }
        @endif
    },
    getUserLocation() {
        if (!navigator.geolocation) {
            alert('Perangkat Anda tidak mendukung fitur deteksi lokasi GPS.');
            return;
        }
        this.detectingGps = true;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const url = new URL(window.location.href);
                url.searchParams.set('lat', lat);
                url.searchParams.set('lng', lng);
                url.searchParams.set('tab', 'terdekat');
                window.location.href = url.toString();
            },
            (err) => {
                this.detectingGps = false;
                alert('Gagal mendeteksi lokasi GPS. Pastikan izin akses lokasi telah aktif pada browser Anda.');
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }
}">
    
    <!-- Top Header & Banner (Inspired by Image 4: Sekitarmu with Search & Pin) -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-rose-600 text-white relative overflow-hidden shadow-sm">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
        
        <!-- Big Map Pin Graphic Accent -->
        <div class="absolute -right-4 -bottom-6 w-28 h-28 opacity-20 pointer-events-none">
            <svg fill="currentColor" viewBox="0 0 24 24" class="w-full h-full text-yellow-300">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
        </div>

        <div class="max-w-md mx-auto px-4 pt-3 pb-4 relative z-10">
            <!-- Nav Bar -->
            <div class="flex items-center gap-2.5 mb-2">
                <a href="{{ route('customer.food') }}" class="text-white/90 hover:text-white p-1 -ml-1 rounded-full transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>

                <!-- Search Input with Live Form -->
                <form action="{{ route('customer.food.nearby') }}" method="GET" class="flex-1 min-w-0">
                    <input type="hidden" name="tab" value="{{ $activeTab ?? 'terkait' }}">
                    @if(request('lat') && request('lng'))
                        <input type="hidden" name="lat" value="{{ request('lat') }}">
                        <input type="hidden" name="lng" value="{{ request('lng') }}">
                    @endif
                    <div class="relative flex items-center">
                        <svg class="w-4 h-4 text-white/70 absolute left-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" 
                               name="q" 
                               value="{{ request('q') }}"
                               placeholder="Cari resto dalam Sekitarmu" 
                               class="w-full bg-white/20 backdrop-blur-md text-white placeholder-white/70 text-xs rounded-full pl-9 pr-3 py-2 border border-white/25 focus:outline-none focus:bg-white/30 focus:border-white transition">
                    </div>
                </form>

                <!-- Share Icon -->
                <button type="button" onclick="navigator.clipboard?.writeText(window.location.href); alert('Tautan kuliner disalin!')" class="text-white/90 hover:text-white p-1 rounded-full transition" title="Bagikan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                </button>

                <!-- Cart Icon with Badge -->
                @php
                    $foodCartCount = \App\Models\CartItem::whereHas('cart', fn($q) => $q->where('customer_id', auth('customer')->id()))->count();
                @endphp
                <a href="{{ route('customer.cart.index') }}" class="relative text-white/90 hover:text-white p-1 rounded-full transition" title="Keranjang">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    @if($foodCartCount > 0)
                        <span class="absolute -top-1 -right-1 bg-yellow-400 text-slate-900 text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow">
                            {{ $foodCartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    <!-- Section Title & Tabs Container (Image 4 Exact Header Form) -->
    <div class="bg-white border-b border-slate-100 sticky top-0 z-30 shadow-xs">
        <div class="max-w-md mx-auto px-4 pt-3">
            <!-- Title Section -->
            <div class="flex items-center justify-between mb-1 gap-2">
                <div class="min-w-0">
                    <h2 class="font-extrabold text-lg text-slate-900 tracking-tight">Sekitarmu</h2>
                    <p class="text-[11px] text-slate-500 font-medium truncate">
                        @if($hasCustomerLocation && $detectedArea)
                            Menampilkan resto terdekat dari posisi Anda di <span class="font-bold text-rose-600">{{ $detectedArea }}</span>.
                        @else
                            Temukan berbagai menu lezat dari Resto terdekat.
                        @endif
                    </p>
                </div>
                <button type="button" @click="getUserLocation()" class="flex items-center gap-1.5 text-[10px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 px-2.5 py-1.5 rounded-full transition shrink-0 shadow-2xs">
                    <svg class="w-3 h-3 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    <span x-text="detectingGps ? 'Membaca GPS...' : '{{ $hasCustomerLocation ? ($detectedArea ? '📍 ' . $detectedArea . ' (GPS Terhubung)' : 'GPS Terhubung') : 'Deteksi GPS' }}'"></span>
                </button>
            </div>

            <!-- Segmented Control Tabs (Terkait | Terdekat | Terlaris | Rating) -->
            <div class="flex items-center justify-between border-t border-slate-100 mt-2 text-xs font-bold">
                <a href="{{ route('customer.food.nearby', ['tab' => 'terkait', 'q' => request('q'), 'lat' => $custLat, 'lng' => $custLng]) }}" 
                   class="flex-1 text-center py-2.5 transition {{ ($activeTab ?? 'terkait') === 'terkait' ? 'text-rose-600 border-b-2 border-rose-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                    Terkait
                </a>
                <a href="{{ route('customer.food.nearby', ['tab' => 'terdekat', 'q' => request('q'), 'lat' => $custLat, 'lng' => $custLng]) }}" 
                   class="flex-1 text-center py-2.5 transition {{ ($activeTab ?? '') === 'terdekat' ? 'text-rose-600 border-b-2 border-rose-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                    Terdekat
                </a>
                <a href="{{ route('customer.food.nearby', ['tab' => 'terlaris', 'q' => request('q'), 'lat' => $custLat, 'lng' => $custLng]) }}" 
                   class="flex-1 text-center py-2.5 transition {{ ($activeTab ?? '') === 'terlaris' ? 'text-rose-600 border-b-2 border-rose-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                    Terlaris
                </a>
                <a href="{{ route('customer.food.nearby', ['tab' => 'rating', 'q' => request('q'), 'lat' => $custLat, 'lng' => $custLng]) }}" 
                   class="flex-1 text-center py-2.5 transition {{ ($activeTab ?? '') === 'rating' ? 'text-rose-600 border-b-2 border-rose-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                    Rating
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Chips (Jarak Resto ⌵ | Promo ⌵ | Kategori ⌵ | Filter) -->
    <div class="max-w-md mx-auto px-4 py-2.5">
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-hide text-xs font-medium">
            <!-- Jarak Resto Dropdown Pill (Hanya jika koordinat customer aktif) -->
            <div class="relative shrink-0" x-data="{ open: false }">
                <button @click="open = !open" class="bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-full flex items-center gap-1 shadow-2xs text-[11px] font-bold">
                    <span>{{ request('max_dist') ? '< ' . request('max_dist') . ' KM' : 'Jarak Resto' }}</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute left-0 mt-1 w-36 bg-white border border-slate-100 rounded-xl shadow-lg py-1 z-30 text-xs">
                    <a href="{{ route('customer.food.nearby', ['tab' => 'terdekat', 'max_dist' => 1, 'lat' => $custLat, 'lng' => $custLng]) }}" class="block px-3 py-1.5 hover:bg-rose-50 hover:text-rose-600 font-semibold">&lt; 1 KM</a>
                    <a href="{{ route('customer.food.nearby', ['tab' => 'terdekat', 'max_dist' => 3, 'lat' => $custLat, 'lng' => $custLng]) }}" class="block px-3 py-1.5 hover:bg-rose-50 hover:text-rose-600 font-semibold">&lt; 3 KM</a>
                    <a href="{{ route('customer.food.nearby', ['tab' => $activeTab, 'lat' => $custLat, 'lng' => $custLng]) }}" class="block px-3 py-1.5 hover:bg-rose-50 hover:text-rose-600 font-semibold">Semua Jarak</a>
                </div>
            </div>

            <!-- Promo Pill -->
            <div class="relative shrink-0" x-data="{ open: false }">
                <button @click="open = !open" class="bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-full flex items-center gap-1 shadow-2xs text-[11px] font-bold">
                    <span>Promo</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute left-0 mt-1 w-40 bg-white border border-slate-100 rounded-xl shadow-lg py-1 z-30 text-xs">
                    <a href="{{ route('customer.food.nearby', ['tab' => 'terlaris', 'lat' => $custLat, 'lng' => $custLng]) }}" class="block px-3 py-1.5 hover:bg-rose-50 hover:text-rose-600 font-semibold">Diskon Menu</a>
                    <a href="{{ route('customer.food.nearby', ['tab' => $activeTab, 'lat' => $custLat, 'lng' => $custLng]) }}" class="block px-3 py-1.5 hover:bg-rose-50 hover:text-rose-600 font-semibold">Semua Promo</a>
                </div>
            </div>

            <!-- Kategori Dropdown Pill -->
            <div class="relative shrink-0" x-data="{ open: false }">
                <button @click="open = !open" class="bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-full flex items-center gap-1 shadow-2xs text-[11px] font-bold">
                    <span>Kategori Resto</span>
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute left-0 mt-1 w-44 bg-white border border-slate-100 rounded-xl shadow-lg py-1 z-30 text-xs max-h-48 overflow-y-auto">
                    <a href="{{ route('customer.food.nearby', ['lat' => $custLat, 'lng' => $custLng]) }}" class="block px-3 py-1.5 hover:bg-rose-50 hover:text-rose-600 font-semibold">Semua Kategori</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('customer.food.nearby', ['category_id' => $cat->id, 'lat' => $custLat, 'lng' => $custLng]) }}" class="block px-3 py-1.5 hover:bg-rose-50 hover:text-rose-600 font-semibold truncate">{{ $cat->name }}</a>
                    @endforeach
                </div>
            </div>

            <!-- Filter Icon Pill -->
            <button type="button" @click="getUserLocation()" class="bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-full flex items-center gap-1 shadow-2xs text-[11px] font-bold shrink-0 ml-auto">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <span>Filter</span>
            </button>
        </div>
    </div>

    <!-- GPS Activation Notice (Jika belum ada koordinat customer) -->
    @if(!$hasCustomerLocation)
    <div class="max-w-md mx-auto px-4 mb-3">
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-3 flex items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-base shrink-0">📍</span>
                <p class="text-blue-900 font-medium text-[11px] leading-snug">
                    Aktifkan GPS untuk menampilkan jarak meter & urutan resto terdekat di peta.
                </p>
            </div>
            <button type="button" @click="getUserLocation()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] px-3 py-1.5 rounded-xl shrink-0 transition shadow-2xs">
                Hubungkan GPS
            </button>
        </div>
    </div>
    @endif

    <!-- Resto List (Image 4 Card Layout) -->
    <div class="max-w-md mx-auto px-3.5 sm:px-4 space-y-3 pt-1">
        @if($merchants->isEmpty())
            <div class="bg-white rounded-2xl p-8 border border-slate-100 text-center shadow-xs">
                <p class="text-sm font-bold text-slate-700">Belum ada resto terdata di sekitar lokasi ini</p>
                <p class="text-xs text-slate-400 mt-1">Coba gunakan filter jarak yang lebih luas atau cari di area lain.</p>
                <a href="{{ route('customer.food.nearby') }}" class="inline-block mt-3 bg-rose-600 text-white font-bold text-xs px-4 py-2 rounded-xl transition">
                    Tampilkan Semua Resto
                </a>
            </div>
        @else
            @foreach($merchants as $idx => $merchant)
                @php
                    $firstProduct = $merchant->products->first();
                    $merchantImage = $merchant->image ?: ($firstProduct?->image ?: null);
                    
                    // Rating nyata jika ada atau fallback
                    $ratingScore = '4.8';
                @endphp

                <!-- RESTO CARD (Exact Reference Style) -->
                <a href="{{ route('customer.merchant.show', $merchant->id) }}" class="block bg-white rounded-2xl p-3.5 border border-slate-100 shadow-xs hover:border-slate-200 transition group">
                    <div class="flex items-start gap-3">
                        <!-- Left Thumbnail with Ribbon Badge -->
                        <div class="relative w-20 h-20 sm:w-22 sm:h-22 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden shrink-0">
                            @if($idx % 2 === 0)
                                <div class="absolute top-0 left-0 bg-rose-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded-br-lg z-10 shadow-xs">
                                    Terlaris
                                </div>
                            @endif

                            @if($merchantImage)
                                <img src="{{ $merchantImage }}" alt="{{ $merchant->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @else
                                <div class="w-full h-full bg-rose-50 flex items-center justify-center text-rose-500 font-black text-sm">
                                    {{ strtoupper(substr($merchant->name, 0, 2)) }}
                                </div>
                            @endif
                        </div>

                        <!-- Right Info -->
                        <div class="flex-1 min-w-0">
                            <h3 class="font-extrabold text-xs sm:text-sm text-slate-900 group-hover:text-rose-600 transition truncate leading-snug">
                                {{ $merchant->name }}
                            </h3>
                            <p class="text-[10px] text-slate-400 mt-0.5 truncate font-medium">
                                {{ $merchant->description ?: $merchant->address }}
                            </p>

                            <!-- Metric Row: Rating & Real Distance (Only if calculated) -->
                            <div class="flex items-center gap-1.5 mt-1 text-[10px] font-bold text-slate-700 flex-wrap">
                                <span class="flex items-center text-amber-500">
                                    ★ <span class="text-slate-800 ml-0.5">{{ $ratingScore }}</span>
                                </span>
                                
                                @if($merchant->has_real_distance && $merchant->distance_formatted)
                                    <span class="text-slate-300">•</span>
                                    <span class="text-rose-600 font-black flex items-center gap-0.5">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                        {{ $merchant->distance_formatted }}
                                    </span>
                                @endif

                                @if($merchant->city_name)
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-500 font-semibold">{{ $merchant->city_name }}</span>
                                @elseif($merchant->address)
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-500 font-normal truncate max-w-[120px]">{{ $merchant->address }}</span>
                                @endif
                            </div>

                            <!-- Promo Tags (Diskon / Hemat / Sekitar) -->
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                <span class="text-[9px] font-bold text-rose-600 border border-rose-200 bg-rose-50/50 px-1.5 py-0.2 rounded">
                                    Menu Pilihan
                                </span>
                                @if($merchant->has_real_distance && $merchant->distance_km <= 3.0)
                                    <span class="text-[9px] font-black text-emerald-700 border border-emerald-300 bg-emerald-50 px-1.5 py-0.2 rounded flex items-center gap-0.5">
                                        ✓ Sekitar Anda
                                    </span>
                                @elseif($merchant->city_name && $detectedArea && strtolower($merchant->city_name) === strtolower($detectedArea))
                                    <span class="text-[9px] font-bold text-emerald-700 border border-emerald-300 bg-emerald-50 px-1.5 py-0.2 rounded">
                                        Area {{ $merchant->city_name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Deals Strip (Image 4 Style) -->
                    @if($firstProduct)
                    <div class="mt-2.5 pt-2 border-t border-slate-50 flex items-center justify-between text-[10px]">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="bg-gradient-to-r from-rose-500 to-orange-500 text-white font-black text-[8px] px-1 py-0.2 rounded uppercase tracking-wider shrink-0">
                                DEALS
                            </span>
                            <span class="text-slate-600 truncate font-medium">
                                {{ $firstProduct->name }}
                            </span>
                            <span class="text-rose-600 font-extrabold shrink-0">
                                Rp{{ number_format($firstProduct->final_price, 0, ',', '.') }}
                            </span>
                        </div>
                        <span class="text-slate-400 group-hover:text-rose-600 font-bold ml-1 shrink-0">
                            &gt;
                        </span>
                    </div>
                    @else
                    <div class="mt-2 pt-2 border-t border-slate-50 flex items-center justify-between text-[10px] text-slate-400">
                        <span class="font-medium">Lihat menu resto &gt;</span>
                        <span class="text-rose-600 font-bold">Buka Menu</span>
                    </div>
                    @endif
                </a>
            @endforeach

            <!-- Pagination Links -->
            <div class="pt-2">
                {{ $merchants->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
