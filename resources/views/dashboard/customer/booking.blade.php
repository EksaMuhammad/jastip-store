@extends('layouts.support')

@section('title', 'Booking Jastiper')

@section('content')
<div class="min-h-screen bg-[#F3F4F6] py-8 pb-24" x-data="{ tab: 'checkin' }">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Breadcrumb / Navigation -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('customer.dashboard') }}" class="text-xs font-bold text-rose-600 hover:underline">Dashboard</a>
                <span class="text-slate-400">/</span>
                <span class="text-xs font-bold text-slate-500">Booking Jastiper</span>
            </div>
            
            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Instant Booking</span>
        </div>

        <!-- Title Section -->
        <div class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Cari & Booking Jastiper</h2>
                <p class="text-xs text-slate-400 mt-1">Pilih Jastiper yang sedang check-in di lokasi belanja atau pesan kembali Jastiper favorit Anda.</p>
            </div>
            
            <!-- Tab Buttons (Gojek Rounded Style) -->
            <div class="bg-slate-100 p-1.5 rounded-full flex gap-1 shrink-0 border border-slate-200">
                <button 
                    @click="tab = 'checkin'" 
                    :class="tab === 'checkin' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-5 py-2 rounded-full text-xs font-extrabold transition duration-150 uppercase tracking-wide focus:outline-none flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span>Check-in List</span>
                </button>
                <button 
                    @click="tab = 'favorites'" 
                    :class="tab === 'favorites' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-5 py-2 rounded-full text-xs font-extrabold transition duration-150 uppercase tracking-wide focus:outline-none flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span>Favorit & Riwayat</span>
                </button>
            </div>
        </div>

        <!-- Success/Error Alerts -->
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-100 p-4 text-emerald-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- TAB 1: CHECK-IN LIST -->
        <div x-show="tab === 'checkin'" class="space-y-4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($checkinJastipers as $jastiper)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-sm space-y-4 hover:border-rose-500/35 transition duration-150 relative overflow-hidden flex flex-col justify-between">
                        
                        <!-- Header Card -->
                        <div class="flex justify-between items-start gap-3">
                            <div class="flex items-center gap-3">
                                <!-- Initial Avatar -->
                                <div class="w-11 h-11 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center font-bold text-sm text-rose-500 shadow-sm shrink-0">
                                    {{ strtoupper(substr($jastiper->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-extrabold text-xs text-slate-800 leading-none">{{ $jastiper->name }}</h4>
                                        <!-- Trust Badge Level -->
                                        @php
                                            $badgeColors = [
                                                'bronze' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'silver' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                'gold' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                                'platinum' => 'bg-rose-100 text-rose-700 border-rose-200'
                                            ];
                                            $level = $jastiper->badge ? $jastiper->badge->badge_level : 'bronze';
                                            $badgeColor = $badgeColors[$level] ?? $badgeColors['bronze'];
                                        @endphp
                                        <span class="text-[8px] font-black uppercase border px-1.5 py-0.5 rounded-sm {{ $badgeColor }} tracking-wider">
                                            {{ $level }}
                                        </span>
                                    </div>
                                    
                                    <!-- Ratings and Response Time -->
                                    <div class="flex items-center gap-2.5 mt-1.5 text-[9px] text-slate-400 font-semibold">
                                        <span class="text-amber-500 flex items-center gap-0.5">
                                            <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            {{ $jastiper->badge ? number_format($jastiper->badge->avg_rating, 1) : '5.0' }}
                                        </span>
                                        <span>•</span>
                                        <span class="flex items-center gap-0.5">
                                            <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ $jastiper->badge ? $jastiper->badge->avg_response_time_minutes : '5' }} m respon
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Favorite Toggle Form -->
                            @php
                                $isFavorited = $favorites->contains($jastiper->id);
                            @endphp
                            <form action="{{ route('customer.jastiper.favorite', $jastiper->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 rounded-full transition duration-150 {{ $isFavorited ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-slate-50 text-slate-350 hover:bg-slate-100 hover:text-slate-500' }}" title="{{ $isFavorited ? 'Hapus dari Favorit' : 'Tambah ke Favorit' }}">
                                    <svg class="w-4.5 h-4.5" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <!-- Check-in Location Info Box -->
                        <div class="bg-rose-50/50 border border-rose-100/60 p-3.5 rounded-2xl flex items-start gap-3 shadow-sm">
                            <div class="p-1.5 bg-rose-100 text-rose-600 rounded-xl shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-[8px] text-rose-500 uppercase tracking-widest font-black block">Sedang Check-in di</span>
                                <p class="text-xs font-black text-slate-800 leading-tight mt-0.5">{{ $jastiper->checkin_location }}</p>
                                <span class="text-[8px] text-slate-400 block mt-1">Check-in: {{ $jastiper->checked_in_at ? $jastiper->checked_in_at->diffForHumans() : 'Baru saja' }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-2">
                            <a href="{{ route('customer.orders.create') }}?jastiper_id={{ $jastiper->id }}&location={{ urlencode($jastiper->checkin_location) }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] py-3.5 rounded-2xl transition uppercase tracking-wider shadow-sm text-center flex items-center justify-center gap-1.5">
                                <span>Booking Langsung</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>

                    </div>
                @empty
                    <div class="md:col-span-2 bg-white border border-slate-200/80 rounded-3xl p-10 text-center flex flex-col items-center justify-center space-y-3">
                        <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </div>
                        <h4 class="font-bold text-xs text-slate-700 uppercase tracking-wider">Belum Ada Jastiper Check-in</h4>
                        <p class="text-[10px] text-slate-400 max-w-[280px] leading-normal mx-auto">Saat ini belum ada Jastiper yang mengumumkan posisi belanjanya di Malang. Silakan pesan lewat orderan umum.</p>
                        <a href="{{ route('customer.orders.create') }}" class="inline-block bg-slate-900 hover:bg-slate-800 text-white font-bold text-[9px] px-5 py-2.5 rounded-full uppercase tracking-wider">Buat Orderan Umum</a>
                    </div>
                @endforelse
            </div>

        </div>

        <!-- TAB 2: FAVORIT & RIWAYAT -->
        <div x-show="tab === 'favorites'" class="space-y-4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                       @if($favoriteJastipers->isEmpty() && $historyJastipers->isEmpty())
                <!-- Case: Both lists are empty -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-12 text-center flex flex-col items-center justify-center space-y-3.5 shadow-sm">
                    <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-1">
                        <svg class="w-6 h-6 text-slate-350" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-xs text-slate-700 uppercase tracking-wider">Belum Ada Jastiper Favorit atau Riwayat</h4>
                    <p class="text-[10px] text-slate-400 max-w-[285px] leading-normal mx-auto">Anda belum menyukai Jastiper manapun atau memiliki riwayat booking langsung. Sukai Jastiper favorit Anda dari tab Check-in List!</p>
                </div>
            @else
                <!-- Section: Jastiper Favorit -->
                @if($favoriteJastipers->isNotEmpty())
                    <div class="space-y-3">
                        <h3 class="font-display font-black text-[10px] text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" /></svg>
                            <span>Jastiper Favorit Anda</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($favoriteJastipers as $jastiper)
                                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-sm space-y-4 hover:border-rose-500/35 transition duration-150 flex flex-col justify-between">
                                    <div class="flex justify-between items-start gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center font-bold text-sm text-rose-500 shadow-sm shrink-0">
                                                {{ strtoupper(substr($jastiper->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-extrabold text-xs text-slate-800 leading-none">{{ $jastiper->name }}</h4>
                                                    <span class="text-[8px] font-black uppercase border px-1.5 py-0.5 rounded-sm bg-yellow-100 text-yellow-700 border-yellow-200 tracking-wider">
                                                        {{ $jastiper->badge ? $jastiper->badge->badge_level : 'Bronze' }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1.5 text-[9px] text-slate-400 font-semibold">
                                                    <span class="text-amber-500 flex items-center gap-0.5">
                                                        <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                                        {{ $jastiper->badge ? number_format($jastiper->badge->avg_rating, 1) : '5.0' }}
                                                    </span>
                                                    <span>•</span>
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $jastiper->is_available ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                        {{ $jastiper->is_available ? 'Tersedia' : 'Sibuk' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <form action="{{ route('customer.jastiper.favorite', $jastiper->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="p-2 rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 transition duration-150" title="Hapus dari Favorit">
                                                <svg class="w-4.5 h-4.5" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-200/60 p-3.5 rounded-2xl flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-semibold">Status Real-time:</span>
                                        <span class="font-extrabold uppercase flex items-center gap-1.5 {{ $jastiper->is_available ? 'text-emerald-600' : 'text-slate-500' }}">
                                            <span class="w-2 h-2 rounded-full {{ $jastiper->is_available ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                            {{ $jastiper->is_available ? 'Ready Booking' : 'Offline / Sibuk' }}
                                        </span>
                                    </div>
                                    <div class="pt-2">
                                        @if($jastiper->is_available)
                                            <a href="{{ route('customer.orders.create') }}?jastiper_id={{ $jastiper->id }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] py-3.5 rounded-2xl transition uppercase tracking-wider shadow-sm text-center flex items-center justify-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" /></svg>
                                                <span>Booking Kembali</span>
                                            </a>
                                        @else
                                            <button disabled class="w-full bg-slate-200 text-slate-400 font-extrabold text-[10px] py-3.5 rounded-2xl uppercase tracking-wider cursor-not-allowed text-center flex items-center justify-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                <span>Jastiper Sedang Sibuk</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if($historyJastipers->isNotEmpty())
                        <hr class="border-slate-200/60 my-6">
                    @endif
                @endif

                <!-- Section: Riwayat Booking Jastiper -->
                @if($historyJastipers->isNotEmpty())
                    <div class="space-y-3">
                        <h3 class="font-display font-black text-[10px] text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Pernah Membantu Anda (Riwayat)</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($historyJastipers as $jastiper)
                                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-sm space-y-4 hover:border-rose-500/35 transition duration-150 flex flex-col justify-between">
                                    <div class="flex justify-between items-start gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center font-bold text-sm text-rose-500 shadow-sm shrink-0">
                                                {{ strtoupper(substr($jastiper->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-extrabold text-xs text-slate-800 leading-none">{{ $jastiper->name }}</h4>
                                                    <span class="text-[8px] font-black uppercase border px-1.5 py-0.5 rounded-sm bg-slate-100 text-slate-700 border-slate-200 tracking-wider">
                                                        {{ $jastiper->badge ? $jastiper->badge->badge_level : 'Bronze' }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1.5 text-[9px] text-slate-400 font-semibold">
                                                    <span class="text-amber-500 flex items-center gap-0.5">
                                                        <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                                        {{ $jastiper->badge ? number_format($jastiper->badge->avg_rating, 1) : '5.0' }}
                                                    </span>
                                                    <span>•</span>
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $jastiper->is_available ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                        {{ $jastiper->is_available ? 'Tersedia' : 'Sibuk' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <form action="{{ route('customer.jastiper.favorite', $jastiper->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="p-2 rounded-full bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition duration-150" title="Sukai / Tambah Ke Favorit">
                                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="bg-slate-50 border border-slate-200/60 p-3.5 rounded-2xl flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-semibold">Status Real-time:</span>
                                        <span class="font-extrabold uppercase flex items-center gap-1.5 {{ $jastiper->is_available ? 'text-emerald-600' : 'text-slate-500' }}">
                                            <span class="w-2 h-2 rounded-full {{ $jastiper->is_available ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                            {{ $jastiper->is_available ? 'Ready Booking' : 'Offline / Sibuk' }}
                                        </span>
                                    </div>
                                    <div class="pt-2">
                                        @if($jastiper->is_available)
                                            <a href="{{ route('customer.orders.create') }}?jastiper_id={{ $jastiper->id }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] py-3.5 rounded-2xl transition uppercase tracking-wider shadow-sm text-center flex items-center justify-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                <span>Booking Lagi</span>
                                            </a>
                                        @else
                                            <button disabled class="w-full bg-slate-200 text-slate-400 font-extrabold text-[10px] py-3.5 rounded-2xl uppercase tracking-wider cursor-not-allowed text-center flex items-center justify-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                <span>Jastiper Sedang Sibuk</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>

        </div>

    </div>
</div>
@endsection
