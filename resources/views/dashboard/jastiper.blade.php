@extends('layouts.support')

@section('title', 'Dashboard Jastiper')

@section('content')
<div class="min-h-screen bg-slate-50 flex flex-col justify-between" x-data="{ online: true }">
    <!-- Centered Mobile Mockup Container -->
    <div class="w-full max-w-md mx-auto bg-white min-h-screen shadow-2xl border-x border-slate-100 flex flex-col justify-between pb-20 relative">
        
        <!-- Header Section -->
        <div class="bg-gradient-to-b from-slate-900 to-slate-800 text-white px-6 pt-8 pb-14 rounded-b-[2.5rem] shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Avatar with verification status ring -->
                    <div class="relative">
                        <div class="w-12 h-12 bg-slate-700 border-2 border-white/20 rounded-full flex items-center justify-center font-bold text-lg text-emerald-400">
                            {{ strtoupper(substr($jastiper->name, 0, 2)) }}
                        </div>
                        @if ($jastiper->verification_status === 'approved')
                            <span class="absolute -bottom-1 -right-1 bg-emerald-500 text-white w-5 h-5 rounded-full border-2 border-slate-900 flex items-center justify-center text-[10px]" title="Terverifikasi">✓</span>
                        @endif
                    </div>
                    
                    <div>
                        <div class="flex items-center gap-1">
                            <h1 class="font-display font-black text-base leading-tight">{{ $jastiper->name }}</h1>
                            @if ($jastiper->verification_status === 'approved')
                                <span class="text-[8px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded-sm font-bold uppercase tracking-wider">Pro</span>
                            @endif
                        </div>
                        <span class="text-[9px] text-slate-400 font-semibold block mt-0.5">{{ $jastiper->wilayah ? $jastiper->wilayah->name : 'Tanpa Wilayah' }} (Radius {{ $jastiper->radius_km }}km)</span>
                    </div>
                </div>

                <!-- Online / Offline Toggle Switch -->
                <button type="button" @click="online = !online" class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border transition" :class="online ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-slate-800 border-slate-700 text-slate-400'">
                    <span class="w-2 h-2 rounded-full" :class="online ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" x-text="online ? 'Online' : 'Offline'"></span>
                </button>
            </div>
            
            <!-- Quick Verification Status Alert Banner -->
            <div class="mt-4">
                @if ($jastiper->verification_status === 'approved')
                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-2.5 flex items-center justify-between text-[10px] text-emerald-300">
                        <span class="font-semibold">✓ Akun Anda Aktif & Terverifikasi</span>
                        <a href="{{ route('jastiper.verification') }}" class="font-bold underline hover:text-white transition">Detil ↗</a>
                    </div>
                @elseif ($jastiper->verification_status === 'menunggu')
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-2.5 flex items-center justify-between text-[10px] text-amber-300">
                        <span class="font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-amber-400 rounded-full animate-ping"></span>
                            Dokumen Anda sedang ditinjau Admin
                        </span>
                        <a href="{{ route('jastiper.verification') }}" class="font-bold underline hover:text-white transition">Pantau ↗</a>
                    </div>
                @elseif ($jastiper->verification_status === 'rejected')
                    <div class="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-2.5 flex flex-col gap-1 text-[10px] text-rose-300">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">❌ Verifikasi Ditolak</span>
                            <a href="{{ route('jastiper.verification') }}" class="font-bold underline hover:text-white transition">Ajukan Ulang ↗</a>
                        </div>
                        @if ($jastiper->latestVerification)
                            <span class="text-[9px] text-rose-400 italic">Alasan: "{{ $jastiper->latestVerification->rejection_reason }}"</span>
                        @endif
                    </div>
                @else
                    <div class="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-2.5 flex items-center justify-between text-[10px] text-rose-300 animate-pulse">
                        <span class="font-bold">⚠️ Mohon selesaikan verifikasi akun KTP</span>
                        <a href="{{ route('jastiper.verification') }}" class="font-bold bg-rose-600 hover:bg-rose-700 text-white px-2 py-0.5 rounded-sm transition">Mulai ↗</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Float Balance Widget (ATM Style Card) -->
        <div class="px-4 -mt-8">
            <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white rounded-3xl p-5 shadow-lg border border-emerald-500/30">
                <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <span class="text-[9px] uppercase font-bold text-emerald-100 tracking-wider block">Total Pendapatan (Wallet)</span>
                        <div class="text-3xl font-black font-display tracking-tight">Rp0</div>
                    </div>
                    
                    <button onclick="showMaintenanceToast(event)" class="bg-slate-900/40 hover:bg-slate-900/60 border border-white/20 text-white text-[10px] font-bold px-4 py-2 rounded-full transition uppercase tracking-wide">
                        Tarik Saldo
                    </button>
                </div>

                <!-- Daily stats block -->
                <div class="grid grid-cols-3 gap-2 border-t border-white/10 pt-4 mt-4 text-center">
                    <div>
                        <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Bulan Ini</span>
                        <span class="text-xs font-black block mt-0.5">Rp0</span>
                    </div>
                    <div>
                        <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Penyelesaian</span>
                        <span class="text-xs font-black block mt-0.5">100%</span>
                    </div>
                    <div>
                        <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Rating Anda</span>
                        <span class="text-xs font-black block mt-0.5 text-amber-300">★ 5.0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Jobs & Order Feed -->
        <div class="px-6 mt-6 flex-grow">
            
            <div class="flex items-center justify-between mb-3.5">
                <h3 class="font-display font-black text-sm text-slate-800 uppercase tracking-wider">Orderan Masuk</h3>
                <span class="text-[9px] font-bold bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full text-slate-500 uppercase tracking-wide" x-show="online">Mencari Orderan...</span>
            </div>

            <!-- Conditional Content based on Online/Offline -->
            <div x-show="!online" class="py-10 text-center flex flex-col items-center justify-center" x-cloak>
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-2.5">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
                <h4 class="font-bold text-xs text-slate-700">Status Anda Offline</h4>
                <p class="text-[9px] text-slate-400 mt-1 max-w-[200px]">Ubah status Anda ke Online di pojok kanan atas untuk mulai melihat orderan jastip masuk.</p>
            </div>

            <div x-show="online" class="space-y-4" x-transition>
                
                @if ($jastiper->verification_status !== 'approved')
                    <!-- If not approved, cannot accept orders yet -->
                    <div class="bg-rose-50 border border-rose-100 rounded-2xl p-5 text-center flex flex-col items-center justify-center">
                        <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0-8v6m0 5h.01M4.93 19h14.14a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.2 16a2 2 0 001.73 3z"></path></svg>
                        </div>
                        <h4 class="font-bold text-xs text-slate-700">Fitur Order Dikunci</h4>
                        <p class="text-[9px] text-slate-400 mt-1 max-w-[240px]">Akun Anda harus terverifikasi terlebih dahulu untuk dapat menerima dan melihat orderan masuk terdekat.</p>
                        <a href="{{ route('jastiper.verification') }}" class="mt-3 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] px-5 py-2 rounded-full transition uppercase tracking-wider">Verifikasi Akun ↗</a>
                    </div>
                @else
                    <!-- Simulated Driver Card Request (Gojek Driver Style) -->
                    <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-sm hover:shadow-md transition">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-3 mb-3">
                            <div>
                                <span class="text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100 px-2 py-0.5 rounded-full uppercase tracking-wider">Jasa Titip Kuliner</span>
                                <h4 class="font-black text-sm text-slate-800 mt-1.5">Titip Nasi Goreng Gila Malang</h4>
                                <span class="text-[9px] text-slate-400 block mt-0.5">Toko: Warung Pak Kumis (1.2 km dari Anda)</span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[9px] uppercase font-bold text-slate-400 tracking-wider block">Tarif Komisi</span>
                                <span class="text-sm font-black text-emerald-600">+ Rp15.000</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-[10px] text-slate-500 mb-4">
                            <div>Total Belanjaan: <span class="font-bold text-slate-800">Rp42.000</span></div>
                            <div class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span>Kirim ke: Lowokwaru, Malang</span>
                            </div>
                        </div>

                        <!-- Driver actions -->
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="showMaintenanceToast(event)" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] py-2 rounded-xl transition uppercase tracking-wide">
                                Terima Job
                            </button>
                            <button onclick="showMaintenanceToast(event)" class="border border-slate-200 hover:bg-slate-50 text-slate-500 font-bold text-[10px] py-2 rounded-xl transition uppercase tracking-wide">
                                Abaikan
                            </button>
                        </div>
                    </div>
                @endif
                
            </div>
        </div>

        <!-- Sticky Bottom Mobile Navigation Bar -->
        <div class="absolute bottom-0 inset-x-0 bg-white border-t border-slate-100 py-2.5 px-6 flex items-center justify-between text-center z-10">
            <a href="{{ route('jastiper.dashboard') }}" class="flex flex-col items-center gap-0.5 text-slate-900 font-black">
                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-wider">Home</span>
            </a>

            <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-wider">Riwayat</span>
            </button>

            <!-- Profile / Logout Form as button -->
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="text-[9px] font-black uppercase tracking-wider">Keluar</span>
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
