@extends('layouts.support')

@section('title', 'Dashboard Jastiper')

@section('styles')
    <!-- Leaflet CSS & JS loaded in Head to avoid race conditions -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endsection

@section('content')
<div class="min-h-screen bg-[#F3F4F6] pb-16" x-data="{ online: true }">
    <!-- Active Status Bar (Gojek Driver Status Banner) -->
    <div class="bg-slate-950 text-white border-b border-slate-800 sticky top-20 z-40 px-4 py-3.5 shadow-sm">
        <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
            
            <!-- Driver Info & Status -->
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 bg-slate-800 border border-slate-700 rounded-full flex items-center justify-center font-bold text-sm text-rose-500 shadow-md">
                        {{ strtoupper(substr($jastiper->name, 0, 2)) }}
                    </div>
                    <!-- Indicator dot -->
                    <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border border-slate-950 transition duration-150" :class="online ? 'bg-emerald-500 animate-pulse' : 'bg-slate-500'"></span>
                </div>
                <div>
                    <h2 class="font-display font-extrabold text-sm text-white">{{ $jastiper->name }}</h2>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block" x-text="online ? 'Menerima Order' : 'Istirahat (Offline)'"></span>
                </div>
            </div>

            <!-- Toggle Button (Gopartner Style Switcher) -->
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    @click="online = !online" 
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    :class="online ? 'bg-emerald-600' : 'bg-slate-700'"
                >
                    <span 
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        :class="online ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>

                <!-- Logout -->
                <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-300 p-2.5 rounded-full transition border border-slate-700" title="Keluar Sesi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-md mx-auto px-4 mt-6 space-y-6">
        
        <!-- Verification Status Banner if pending/rejected -->
        @if ($jastiper->verification_status !== 'approved')
            <div class="p-4 rounded-3xl border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] bg-amber-50">
                <div class="flex gap-3">
                    <span class="text-xl">⚠️</span>
                    <div class="space-y-1">
                        <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide">Akun Belum Aktif</h4>
                        <p class="text-[10px] text-slate-500 leading-normal">
                            @if ($jastiper->verification_status === 'menunggu')
                                Dokumen verifikasi Anda sedang ditinjau oleh admin. Mohon tunggu maksimal 1x24 jam.
                            @elseif ($jastiper->verification_status === 'rejected')
                                Pengajuan verifikasi Anda ditolak dengan alasan: "{{ $jastiper->latestVerification->rejection_reason }}". Silakan ajukan ulang.
                            @else
                                Anda belum mengunggah berkas KTP & Selfie. Selesaikan verifikasi sekarang untuk mulai menerima order.
                            @endif
                        </p>
                        <div class="pt-2">
                            <a href="{{ route('jastiper.verification') }}" class="inline-block bg-slate-900 hover:bg-slate-800 text-white font-bold text-[9px] px-3.5 py-1.5 rounded-sm uppercase tracking-wider shadow-sm">
                                @if($jastiper->verification_status === 'rejected') Ajukan Ulang @elseif($jastiper->verification_status === 'menunggu') Pantau Dokumen @else Mulai Verifikasi @endif
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Driver Earnings Card (Gopartner Style Performance Metrics) -->
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white rounded-3xl p-5 shadow-lg border border-emerald-500/20 relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
            
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-[9px] uppercase font-bold text-emerald-100 tracking-wider block">Pendapatan Jastiper Hari Ini</span>
                    <div class="text-3xl font-black font-display tracking-tight">Rp0</div>
                    <span class="text-[8px] text-emerald-200/80 font-semibold block">ID: #JSTP-{{ str_pad($jastiper->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                
                <button onclick="showMaintenanceToast(event)" class="bg-slate-950/40 hover:bg-slate-950/60 border border-white/20 text-white text-[9px] font-bold px-3.5 py-2 rounded-full transition uppercase tracking-wide">
                    Tarik Saldo
                </button>
            </div>

            <!-- Perf Metrics Grid -->
            <div class="grid grid-cols-3 gap-2 border-t border-white/10 pt-4 mt-5 text-center">
                <div>
                    <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Bulan Ini</span>
                    <span class="text-xs font-black block mt-0.5">Rp0</span>
                </div>
                <div>
                    <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Penyelesaian</span>
                    <span class="text-xs font-black block mt-0.5">100%</span>
                </div>
                <div>
                    <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Rating</span>
                    <span class="text-xs font-black block mt-0.5 text-amber-300">★ 5.0</span>
                </div>
            </div>
        </div>

        <!-- Check-in Lokasi Widget -->
        @if ($jastiper->verification_status === 'approved')
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-3.5">
                <div class="border-b border-slate-100 pb-2.5 flex justify-between items-center">
                    <div>
                        <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Check-in Lokasi Belanja</h3>
                        <p class="text-[9px] text-slate-400 mt-0.5">Kabarkan posisi Anda agar Customer dapat membooking Anda secara instan.</p>
                    </div>
                    <span class="w-2.5 h-2.5 rounded-full {{ $jastiper->checkin_location ? 'bg-emerald-500 animate-pulse' : 'bg-slate-350' }}"></span>
                </div>

                @if ($jastiper->checkin_location)
                    <div class="bg-rose-50/50 border border-rose-100/60 p-3 rounded-2xl flex items-center justify-between gap-4">
                        <div class="flex items-start gap-2.5">
                            <div class="p-1.5 bg-rose-100 text-rose-600 rounded-xl shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-[8px] text-rose-500 uppercase tracking-widest font-black block">Sedang Aktif di</span>
                                <p class="text-xs font-black text-slate-800 mt-0.5">{{ $jastiper->checkin_location }}</p>
                                <span class="text-[8px] text-slate-400 block mt-0.5">Sejak {{ $jastiper->checked_in_at ? $jastiper->checked_in_at->diffForHumans() : 'Baru saja' }}</span>
                            </div>
                        </div>
                        <form action="{{ route('jastiper.checkin') }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="bg-slate-900 hover:bg-slate-850 text-white text-[9px] font-black px-4 py-2.5 rounded-xl uppercase tracking-wider transition shrink-0 shadow-sm">
                                Check-out
                            </button>
                        </form>
                    </div>
                @else
                    <form action="{{ route('jastiper.checkin') }}" method="POST" class="space-y-3" x-data="{ location: 'Mie Gacoan Lowokwaru', customLocation: '' }">
                        @csrf
                        <input type="hidden" name="action" value="checkin">
                        
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label for="location_select" class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pilih Lokasi Populer</label>
                                <select id="location_select" x-model="location" class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-3.5 py-2.5 rounded-xl text-[11px] font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150">
                                    <option value="Mie Gacoan Lowokwaru">Mie Gacoan Lowokwaru</option>
                                    <option value="Starbucks Ijen">Starbucks Ijen</option>
                                    <option value="Matos (Malang Town Square)">Matos (Malang Town Square)</option>
                                    <option value="Pasar Besar Malang">Pasar Besar Malang</option>
                                    <option value="Bakso Bakar Pak Man">Bakso Bakar Pak Man</option>
                                    <option value="custom">-- Tulis Kustom --</option>
                                </select>
                            </div>

                            <div x-show="location === 'custom'">
                                <label for="location_custom" class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Tempat Kustom</label>
                                <input type="text" id="location_custom" x-model="customLocation" placeholder="Misal: Indomaret Veteran..." class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-3.5 py-2.5 rounded-xl text-[11px] font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150">
                            </div>
                        </div>

                        <!-- Fallback input name if not custom -->
                        <input type="hidden" name="location_name" :value="location !== 'custom' ? location : customLocation">

                        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] py-3.5 rounded-2xl transition uppercase tracking-wider shadow-sm flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span>Umumkan Check-in Saya</span>
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <!-- Live Area Map Card (Leaflet Map on Dashboard) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-3">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Area Kerja & Radius</h3>
                    <p class="text-[9px] text-slate-400 mt-0.5 leading-normal">
                        Wilayah: <b>{{ $jastiper->wilayah ? $jastiper->wilayah->name : 'Belum Ditentukan' }}</b> | Radius: <b>{{ number_format($jastiper->radius_km, 1) }} KM</b>
                    </p>
                </div>
                @if ($jastiper->verification_status === 'approved')
                    <a href="{{ route('jastiper.area') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[9px] px-3.5 py-2 rounded-full border border-slate-200 transition uppercase tracking-wider">
                        Atur Area
                    </a>
                @endif
            </div>
            
            <!-- Map Container -->
            <div id="leaflet-jastiper-dash-map" class="h-44 w-full border border-slate-200 rounded-2xl bg-slate-100 z-10"></div>
        </div>

        <!-- Driver Request Feed (Gojek Driver Request Card Redesign) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Permintaan Order Terdekat</h3>
                <span class="text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 px-2.5 py-0.5 rounded-full" x-show="online">Mencari...</span>
            </div>

            <!-- Case: Offline -->
            <div x-show="!online" class="py-10 text-center flex flex-col items-center justify-center">
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
                <h4 class="font-bold text-xs text-slate-700">Status Anda: Offline</h4>
                <p class="text-[9px] text-slate-400 mt-1 max-w-[220px] leading-normal mx-auto">Nyalakan status "Menerima Order" di banner atas untuk melihat permintaan belanjaan masuk.</p>
            </div>

            <!-- Case: Online -->
            <div x-show="online" class="space-y-4">
                @if ($jastiper->verification_status !== 'approved')
                    <!-- Locked if not verified -->
                    <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-5 text-center flex flex-col items-center justify-center">
                        <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h4 class="font-bold text-xs text-slate-700">Orderan Dikunci</h4>
                        <p class="text-[9px] text-slate-400 mt-1 max-w-[200px] leading-normal mx-auto">Selesaikan verifikasi akun KTP Anda terlebih dahulu di bagian atas untuk membuka akses order.</p>
                    </div>
                @else
                    <!-- Direct Request Bookings List -->
                    @if($directOrders->isNotEmpty())
                        <div class="space-y-3">
                            <h4 class="font-display font-black text-[10px] text-rose-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-450 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-650"></span>
                                </span>
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span>Booking Langsung Khusus Untuk Anda</span>
                            </h4>
                            @foreach($directOrders as $direct)
                                <div class="bg-rose-50/50 border border-rose-200/70 rounded-2xl p-4 space-y-3 relative overflow-hidden">
                                    <div class="flex justify-between items-start border-b border-rose-100 pb-2.5">
                                        <div>
                                            <span class="text-[8px] font-black bg-rose-650 text-rose-600 border border-rose-200 px-2.5 py-0.5 rounded-full uppercase tracking-wider">Direct Request</span>
                                            <h4 class="font-extrabold text-xs text-slate-800 mt-2 leading-tight">{{ $direct->description }}</h4>
                                            <span class="text-[9px] text-slate-400 block mt-0.5">Asal: {{ $direct->origin_address ?: '-' }}</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block font-mono">Ongkir</span>
                                            <span class="text-xs font-black text-rose-600">Rp {{ number_format($direct->estimated_fare, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2 text-[9px] text-slate-500">
                                        <div class="flex items-start gap-1">
                                            <span class="leading-tight">Antar: <b>{{ $direct->destination_address }}</b></span>
                                        </div>
                                        <div>Customer: <b>{{ $direct->customer->name }}</b> ({{ $direct->customer->phone_number }})</div>
                                        <div>Kategori Berat: <span class="font-bold text-slate-700 capitalize">{{ $direct->weight_category }}</span></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 pt-1.5">
                                        <form action="{{ route('jastiper.orders.direct-accept', $direct->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                                Terima Booking
                                            </button>
                                        </form>
                                        <form action="{{ route('jastiper.orders.direct-reject', $direct->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-850 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide text-center block focus:outline-none">
                                                Tolak & Buka Ke Umum
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr class="border-slate-100 my-4">
                    @endif
                    @forelse($orders as $order)
                        <!-- Real Driver Request Card (Gojek App Style) -->
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3 hover:border-slate-300 transition animate-fade-in">
                            
                            <!-- Header info card -->
                            <div class="flex justify-between items-start border-b border-slate-200/60 pb-2.5">
                                <div>
                                    @php
                                        $categoryNames = [
                                            'beli-antar' => 'Titip Kuliner',
                                            'ambil-antar' => 'Titip Ambil',
                                            'toko-kirim' => 'Titip Toko',
                                            'dokumen' => 'Dokumen Kecil',
                                            'multi-stop' => 'Multi-Stop',
                                            'kirim-pihak-ketiga' => 'Titip Ekspedisi',
                                        ];
                                        $catLabel = $categoryNames[$order->category] ?? 'Jastip';
                                    @endphp
                                    <span class="text-[8px] font-black bg-rose-50 text-rose-600 border border-rose-100 px-2 py-0.5 rounded-full uppercase tracking-wider">{{ $catLabel }}</span>
                                    <h4 class="font-extrabold text-xs text-slate-800 mt-1.5 leading-tight">{{ $order->description }}</h4>
                                    <span class="text-[9px] text-slate-400 block mt-0.5">Asal: {{ $order->origin_address ?: '-' }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block">Komisi Jastip</span>
                                    <span class="text-xs font-black text-rose-600">+ Rp {{ number_format($order->estimated_fare, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Details & Addresses -->
                            <div class="space-y-2 text-[9px] text-slate-500">
                                <div class="flex items-start gap-1">
                                    <svg class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span class="leading-tight">Antar: {{ $order->destination_address }}</span>
                                </div>
                                <div>Penerima: <span class="font-extrabold text-slate-800">{{ $order->recipient_name }}</span> ({{ $order->recipient_phone }})</div>
                                <div>Kategori Berat: <span class="font-bold text-slate-700 capitalize">{{ $order->weight_category }}</span></div>
                            </div>

                            <!-- Action buttons -->
                            <div class="pt-1">
                                <form action="{{ route('jastiper.orders.accept', $order->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                        Terima Orderan Ini
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center flex flex-col items-center justify-center">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h4 class="font-bold text-xs text-slate-700">Tidak Ada Orderan Aktif</h4>
                            <p class="text-[9px] text-slate-400 mt-1 max-w-[220px] leading-normal mx-auto">Saat ini belum ada permintaan jastip baru di wilayah operasional Anda.</p>
                        </div>
                    @endforelse
                @endif
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let map;
        let marker;
        let circle;

        let currentLat = parseFloat(@json($jastiper->current_lat ?? -7.9839));
        let currentLng = parseFloat(@json($jastiper->current_lng ?? 112.6214));
        let currentRadius = parseFloat(@json($jastiper->radius_km ?? 5.00));

        function initDashMap() {
            if (typeof L === 'undefined') {
                return;
            }

            let container = L.DomUtil.get('leaflet-jastiper-dash-map');
            if (container !== null && container._leaflet_id !== undefined && container._leaflet_id !== null) {
                return;
            }

            map = L.map('leaflet-jastiper-dash-map', {
                zoomControl: false,
                attributionControl: false
            }).setView([currentLat, currentLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18
            }).addTo(map);

            const jastiperIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });

            marker = L.marker([currentLat, currentLng], {
                icon: jastiperIcon
            }).addTo(map);

            circle = L.circle([currentLat, currentLng], {
                color: '#e11d48',
                fillColor: '#f43f5e',
                fillOpacity: 0.1,
                radius: currentRadius * 1000
            }).addTo(map);

            setTimeout(() => {
                if (map) {
                    map.invalidateSize();
                }
            }, 300);
        }

        // Jalankan init map dengan safety check
        let initAttempts = 0;
        function tryInitDashMap() {
            if (typeof L !== 'undefined') {
                initDashMap();
            } else if (initAttempts < 50) {
                initAttempts++;
                setTimeout(tryInitDashMap, 100);
            }
        }

        tryInitDashMap();
    });
</script>
@endsection
