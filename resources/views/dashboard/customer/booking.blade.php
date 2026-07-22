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
                    class="px-5 py-2 rounded-full text-xs font-extrabold transition duration-150 uppercase tracking-wide focus:outline-none"
                >
                    🗺️ Check-in List
                </button>
                <button 
                    @click="tab = 'favorites'" 
                    :class="tab === 'favorites' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-5 py-2 rounded-full text-xs font-extrabold transition duration-150 uppercase tracking-wide focus:outline-none"
                >
                    ❤️ Favorit & Riwayat
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
                                    <div class="flex items-center gap-2 mt-1.5 text-[9px] text-slate-400 font-semibold">
                                        <span class="text-amber-500">★ {{ $jastiper->badge ? number_format($jastiper->badge->avg_rating, 1) : '5.0' }}</span>
                                        <span>•</span>
                                        <span>⚡ {{ $jastiper->badge ? $jastiper->badge->avg_response_time_minutes : '5' }} m respon</span>
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
                        <div class="bg-rose-50/50 border border-rose-100/60 p-3.5 rounded-2xl flex items-start gap-2.5 shadow-sm">
                            <span class="text-base shrink-0">📍</span>
                            <div>
                                <span class="text-[8px] text-rose-500 uppercase tracking-widest font-black block">Sedang Check-in di</span>
                                <p class="text-xs font-black text-slate-800 leading-tight mt-0.5">{{ $jastiper->checkin_location }}</p>
                                <span class="text-[8px] text-slate-400 block mt-1">Check-in: {{ $jastiper->checked_in_at ? $jastiper->checked_in_at->diffForHumans() : 'Baru saja' }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-2">
                            <a href="{{ route('customer.orders.create') }}?jastiper_id={{ $jastiper->id }}&location={{ urlencode($jastiper->checkin_location) }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] py-3 rounded-2xl transition uppercase tracking-wider shadow-sm text-center block">
                                🤝 Booking Langsung
                            </a>
                        </div>

                    </div>
                @empty
                    <div class="md:col-span-2 bg-white border border-slate-200/80 rounded-3xl p-10 text-center flex flex-col items-center justify-center space-y-3">
                        <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-2xl">🗺️</span>
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($favoriteJastipers as $jastiper)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-sm space-y-4 hover:border-rose-500/35 transition duration-150 flex flex-col justify-between">
                        
                        <!-- Header Card -->
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
                                        <span class="text-amber-500">★ {{ $jastiper->badge ? number_format($jastiper->badge->avg_rating, 1) : '5.0' }}</span>
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-1">
                                            <!-- Availability Dot -->
                                            <span class="w-1.5 h-1.5 rounded-full {{ $jastiper->is_available ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $jastiper->is_available ? 'Tersedia' : 'Sibuk' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Remove from Favorites -->
                            <form action="{{ route('customer.jastiper.favorite', $jastiper->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 transition duration-150" title="Hapus dari Favorit">
                                    <svg class="w-4.5 h-4.5" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <!-- Real-time Availability Widget -->
                        <div class="bg-slate-50 border border-slate-200/60 p-3.5 rounded-2xl flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Status Real-time:</span>
                            <span class="font-extrabold uppercase {{ $jastiper->is_available ? 'text-emerald-600' : 'text-slate-500' }}">
                                {{ $jastiper->is_available ? '🟢 Ready Booking' : '⚫ Offline / Sibuk' }}
                            </span>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-2">
                            @if($jastiper->is_available)
                                <a href="{{ route('customer.orders.create') }}?jastiper_id={{ $jastiper->id }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] py-3 rounded-2xl transition uppercase tracking-wider shadow-sm text-center block">
                                    ❤️ Booking Kembali
                                </a>
                            @else
                                <button disabled class="w-full bg-slate-200 text-slate-400 font-extrabold text-[10px] py-3 rounded-2xl uppercase tracking-wider cursor-not-allowed text-center block">
                                    ❌ Jastiper Sedang Sibuk
                                </button>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="md:col-span-2 bg-white border border-slate-200/80 rounded-3xl p-10 text-center flex flex-col items-center justify-center space-y-3">
                        <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-2xl">❤️</span>
                        </div>
                        <h4 class="font-bold text-xs text-slate-700 uppercase tracking-wider">Belum Ada Jastiper Favorit</h4>
                        <p class="text-[10px] text-slate-400 max-w-[280px] leading-normal mx-auto">Anda belum menambahkan Jastiper favorit. Sukai Jastiper Anda melalui menu Check-in List di sebelah kiri.</p>
                    </div>
                @endforelse
            </div>

        </div>

    </div>
</div>
@endsection
