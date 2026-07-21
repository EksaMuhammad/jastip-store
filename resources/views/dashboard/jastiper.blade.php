@extends('layouts.support')

@section('title', 'Dashboard Jastiper')

@section('content')
<div class="min-h-screen bg-[#F8FAFC] py-10" x-data="{ online: true }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Banner Section -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-3xl p-6 sm:p-8 mb-8 shadow-md relative overflow-hidden">
            <!-- Accent pattern -->
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-rose-600/10 rounded-full blur-2xl"></div>
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative z-10">
                <div class="flex items-center gap-4">
                    <!-- Avatar with initials & checkmark -->
                    <div class="relative">
                        <div class="w-14 h-14 bg-slate-700 border-2 border-white/20 rounded-2xl flex items-center justify-center font-bold text-xl text-rose-500 shadow-inner">
                            {{ strtoupper(substr($jastiper->name, 0, 2)) }}
                        </div>
                        @if ($jastiper->verification_status === 'approved')
                            <span class="absolute -bottom-1 -right-1 bg-rose-600 text-white w-5 h-5 rounded-full border-2 border-slate-900 flex items-center justify-center text-[10px]" title="Terverifikasi">✓</span>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="font-display font-black text-2xl leading-tight">{{ $jastiper->name }}</h1>
                            @if ($jastiper->verification_status === 'approved')
                                <span class="text-[9px] bg-rose-600/20 text-rose-400 px-2 py-0.5 rounded-md font-bold uppercase tracking-wider">Mitra Pro</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Wilayah Kerja: <span class="text-white font-bold">{{ $jastiper->wilayah ? $jastiper->wilayah->name : 'Belum Ditentukan' }}</span> (Radius {{ $jastiper->radius_km }} KM)</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                    <!-- Active Status Toggle -->
                    <button type="button" @click="online = !online" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border transition w-full sm:w-auto" :class="online ? 'bg-rose-600/10 border-rose-500/30 text-rose-400' : 'bg-slate-800 border-slate-700 text-slate-400'">
                        <span class="w-2.5 h-2.5 rounded-full" :class="online ? 'bg-rose-500 animate-pulse' : 'bg-slate-500'"></span>
                        <span class="text-xs font-bold uppercase tracking-wider" x-text="online ? 'Status: Aktif Menerima Order' : 'Status: Istirahat (Offline)'"></span>
                    </button>

                    <!-- Logout Button -->
                    <form action="{{ route('logout') }}" method="POST" class="shrink-0 w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-750 text-white font-bold text-xs px-5 py-3.5 rounded-xl border border-slate-900 transition duration-150 uppercase tracking-wider">
                            Keluar Sesi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Dashboard Responsive Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- COLUMN 1: Profile & Verification Status Info -->
            <div class="space-y-8">
                <!-- Account Info -->
                <div class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm">
                    <h3 class="font-display font-bold text-base text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-7 4h10"></path></svg>
                        Status & Profil Jastiper
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Status On-Duty</span>
                            <p class="text-[10px] text-slate-400 mt-0.5 leading-normal" x-text="online ? 'Sistem aktif mencari orderan masuk di sekitar wilayah operasional Anda.' : 'Anda sedang offline. Aktifkan status di banner atas untuk menerima order.'"></p>
                        </div>
                        
                        <div>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Status Verifikasi Akun</span>
                            <span class="mt-1.5 block">
                                @if ($jastiper->verification_status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-sm">
                                        Approved (Aktif)
                                    </span>
                                @elseif ($jastiper->verification_status === 'menunggu')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-0.5 rounded-sm">
                                        Ditinjau Admin
                                    </span>
                                @elseif ($jastiper->verification_status === 'rejected')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-sm">
                                        Ditolak Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-100 px-2.5 py-0.5 rounded-sm">
                                        Belum Diverifikasi
                                    </span>
                                @endif
                            </span>

                            @if ($jastiper->verification_status === 'rejected' && $jastiper->latestVerification)
                                <div class="bg-rose-50 border border-rose-100 p-3 rounded-2xl text-[10px] text-rose-700 font-semibold leading-normal mt-2">
                                    ❌ Alasan Penolakan: "{{ $jastiper->latestVerification->rejection_reason }}"
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Verification Button Action -->
                    <div class="pt-4 mt-6 border-t border-slate-100">
                        @if ($jastiper->verification_status === 'approved')
                            <a href="{{ route('jastiper.area') }}" class="block text-center bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs py-3 rounded-xl transition border border-slate-200">
                                Edit Profil & Wilayah
                            </a>
                        @elseif ($jastiper->verification_status === 'menunggu')
                            <a href="{{ route('jastiper.verification') }}" class="block text-center bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs py-3 rounded-xl transition uppercase tracking-wide">
                                Pantau Pengajuan ↗
                            </a>
                        @elseif ($jastiper->verification_status === 'rejected')
                            <a href="{{ route('jastiper.verification') }}" class="block text-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl transition uppercase tracking-wide">
                                Ajukan Ulang Dokumen ↗
                            </a>
                        @else
                            <a href="{{ route('jastiper.verification') }}" class="block text-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl transition uppercase tracking-wide animate-pulse">
                                Mulai Verifikasi Akun ↗
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: Wallet Income & Stats -->
            <div class="space-y-8">
                <!-- Driver Wallet ATM Card -->
                <div class="bg-gradient-to-br from-rose-600 to-rose-700 text-white rounded-3xl p-6 shadow-md border border-rose-500/20">
                    <div class="flex justify-between items-start">
                        <div class="space-y-1">
                            <span class="text-[9px] uppercase font-bold text-rose-100 tracking-wider block">Dompet Pendapatan Jastip</span>
                            <div class="text-3xl font-black font-display tracking-tight">Rp0</div>
                            <span class="text-[8px] text-rose-200/80 font-semibold block">ID: #JSTP-{{ str_pad($jastiper->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        
                        <button onclick="showMaintenanceToast(event)" class="bg-slate-900/40 hover:bg-slate-900/60 border border-white/20 text-white text-[10px] font-bold px-4 py-2.5 rounded-full transition uppercase tracking-wide">
                            Tarik Saldo
                        </button>
                    </div>

                    <!-- Statistics grid inside card -->
                    <div class="grid grid-cols-3 gap-2 border-t border-white/10 pt-4 mt-6 text-center">
                        <div>
                            <span class="text-[8px] font-bold text-rose-100 uppercase tracking-wide block">Bulan Ini</span>
                            <span class="text-xs font-black block mt-0.5">Rp0</span>
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-rose-100 uppercase tracking-wide block">Penyelesaian</span>
                            <span class="text-xs font-black block mt-0.5">100%</span>
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-rose-100 uppercase tracking-wide block">Rating Anda</span>
                            <span class="text-xs font-black block mt-0.5 text-amber-300">★ 5.0</span>
                        </div>
                    </div>
                </div>

                <!-- Summary instructions -->
                <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm text-xs text-slate-500 space-y-3">
                    <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">💡 Tip Jastiper</h4>
                    <p class="leading-relaxed">Pastikan Anda selalu berada di dekat wilayah operasional Anda untuk memperbesar peluang mendapatkan orderan belanjaan.</p>
                    <button onclick="showMaintenanceToast(event)" class="text-[10px] font-bold text-rose-600 hover:underline block">Lihat Panduan Jastiper ↗</button>
                </div>
            </div>

            <!-- COLUMN 3: Nearby Order Feed (Driver Job Board) -->
            <div class="space-y-6">
                <div class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm">
                    <div class="border-b border-slate-100 pb-3 mb-4 flex justify-between items-center">
                        <h3 class="font-display font-black text-sm text-slate-800 uppercase tracking-wider">Orderan Terdekat</h3>
                        <span class="text-[9px] font-bold bg-slate-50 border border-slate-200 px-2.5 py-0.5 rounded-full text-slate-500" x-show="online">Mencari...</span>
                    </div>

                    <!-- Case: Status Offline -->
                    <div x-show="!online" class="py-10 text-center flex flex-col items-center justify-center" x-cloak>
                        <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        </div>
                        <h4 class="font-bold text-xs text-slate-700">Status Offline</h4>
                        <p class="text-[10px] text-slate-400 mt-1 max-w-[200px] leading-normal">Aktifkan status Anda ke Online di tombol pojok kanan atas untuk menerima tawaran belanja.</p>
                    </div>

                    <!-- Case: Status Online -->
                    <div x-show="online" class="space-y-4" x-transition>
                        @if ($jastiper->verification_status !== 'approved')
                            <!-- Locked if not verified -->
                            <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-5 text-center flex flex-col items-center justify-center">
                                <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <h4 class="font-bold text-xs text-slate-700">Orderan Dikunci</h4>
                                <p class="text-[9px] text-slate-400 mt-1 max-w-[200px] leading-normal">Selesaikan verifikasi akun KTP Anda di kolom profil (kiri) untuk membuka akses order.</p>
                            </div>
                        @else
                            <!-- Mock Driver Request Card (Gojek Driver Request card design) -->
                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 hover:border-slate-200 transition">
                                <div class="flex justify-between items-start border-b border-slate-200/50 pb-2 mb-3">
                                    <div>
                                        <span class="text-[8px] font-bold bg-rose-50 text-rose-600 border border-rose-100 px-2 py-0.5 rounded-full uppercase tracking-wider">Titip Kuliner</span>
                                        <h4 class="font-extrabold text-xs text-slate-800 mt-1.5 leading-tight">Titip Nasi Goreng Gila Malang</h4>
                                        <span class="text-[9px] text-slate-400 block mt-0.5">Warung Pak Kumis (1.2 km dari Anda)</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block">Tarif Komisi</span>
                                        <span class="text-xs font-black text-rose-600">+ Rp15.000</span>
                                    </div>
                                </div>

                                <div class="space-y-1.5 text-[9px] text-slate-500 mb-4">
                                    <div>Total Belanjaan: <span class="font-bold text-slate-800">Rp42.000</span></div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span>Pengantaran: Lowokwaru, Malang</span>
                                    </div>
                                </div>

                                <!-- Accept/Ignore buttons -->
                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="showMaintenanceToast(event)" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] py-2 rounded-xl transition uppercase tracking-wide shadow-sm shadow-rose-600/10">
                                        Terima
                                    </button>
                                    <button onclick="showMaintenanceToast(event)" class="border border-slate-200 hover:bg-slate-100 text-slate-500 font-bold text-[10px] py-2 rounded-xl transition uppercase tracking-wide">
                                        Abaikan
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
