@extends('layouts.support')

@section('title', 'Dashboard Customer')

@section('content')
<div class="min-h-screen bg-slate-50 flex flex-col justify-between">
    <!-- Centered Mobile Mockup Container -->
    <div class="w-full max-w-md mx-auto bg-white min-h-screen shadow-2xl border-x border-slate-100 flex flex-col justify-between pb-20 relative">
        
        <!-- Header Section -->
        <div class="bg-gradient-to-b from-emerald-500 to-emerald-600 text-white px-6 pt-8 pb-16 rounded-b-[2.5rem] shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Initial Avatar -->
                    <div class="w-12 h-12 bg-white/20 border border-white/30 rounded-full flex items-center justify-center font-bold text-lg shadow-inner">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                    <div>
                        <span class="text-[10px] text-emerald-100 font-bold uppercase tracking-wider block">Selamat Datang</span>
                        <h1 class="font-display font-black text-lg leading-tight">{{ $customer->name }}</h1>
                    </div>
                </div>

                <!-- Logout Form -->
                <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="w-9 h-9 bg-white/10 hover:bg-white/20 border border-white/25 rounded-full flex items-center justify-center transition" title="Keluar Sesi">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
            
            <div class="mt-2 text-xs text-emerald-100 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Customer Wilayah Malang</span>
            </div>
        </div>

        <!-- Float Wallet Balance Widget (Gopay Style) -->
        <div class="px-4 -mt-10">
            <div class="bg-slate-900 text-white rounded-3xl p-5 shadow-lg border border-slate-800 flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-[9px] uppercase font-bold text-slate-400 tracking-widest block">Saldo Dompet Jastip</span>
                    <div class="text-2xl font-black font-display tracking-tight text-emerald-400">Rp0</div>
                    <span class="text-[8px] text-slate-500 font-semibold block">ID: #CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                
                <div class="flex items-center gap-3.5">
                    <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-1 group">
                        <div class="w-10 h-10 bg-slate-800 group-hover:bg-slate-700 rounded-2xl flex items-center justify-center transition border border-slate-700/60">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-300">Top Up</span>
                    </button>

                    <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-1 group">
                        <div class="w-10 h-10 bg-slate-800 group-hover:bg-slate-700 rounded-2xl flex items-center justify-center transition border border-slate-700/60">
                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-300">Riwayat</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Services Section -->
        <div class="px-6 mt-8 flex-grow">
            <h3 class="font-display font-black text-sm text-slate-800 uppercase tracking-wider mb-4">Layanan Jasa Titip</h3>
            
            <div class="grid grid-cols-4 gap-4 text-center">
                <!-- Titip Makanan -->
                <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 bg-rose-50 border border-rose-100 group-hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-sm">
                        <svg class="w-7 h-7 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-extrabold text-slate-700 leading-tight">Titip Kuliner</span>
                </button>

                <!-- Titip Minimarket -->
                <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 bg-blue-50 border border-blue-100 group-hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-sm">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <span class="text-[10px] font-extrabold text-slate-700 leading-tight">Minimarket</span>
                </button>

                <!-- Belanja Pasar -->
                <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 bg-emerald-50 border border-emerald-100 group-hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-sm">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-extrabold text-slate-700 leading-tight">Belanja Pasar</span>
                </button>

                <!-- Titip Bebas -->
                <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 bg-amber-50 border border-amber-100 group-hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-sm">
                        <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 10.742l-1.977-1.977a2.25 2.25 0 113.182-3.182l1.977 1.977M21 21l-3.5-3.5m0 0A7.5 7.5 0 1118 6.5a7.5 7.5 0 010 11z"></path></svg>
                    </div>
                    <span class="text-[10px] font-extrabold text-slate-700 leading-tight">Titip Bebas</span>
                </button>
            </div>

            <!-- Active Order Tracker Section -->
            <div class="mt-8">
                <h3 class="font-display font-black text-sm text-slate-800 uppercase tracking-wider mb-3">Pesanan Aktif</h3>
                
                <!-- Cozy App-like Active Order Tracker Card -->
                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-5 text-center flex flex-col items-center justify-center py-6">
                    <div class="w-12 h-12 bg-slate-200/50 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h4 class="font-bold text-xs text-slate-700">Belum Ada Pesanan Aktif</h4>
                    <p class="text-[10px] text-slate-400 mt-1 max-w-xs leading-relaxed">Punya belanjaan yang ingin dititip? Buat permintaan baru dan biarkan Jastiper terdekat membelikannya untuk Anda.</p>
                    
                    <a href="{{ url('/#calculator') }}" class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-6 py-2.5 rounded-full transition shadow-md shadow-emerald-500/20 uppercase tracking-wider block">
                        Buat Permintaan Baru
                    </a>
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Mobile Navigation Bar -->
        <div class="absolute bottom-0 inset-x-0 bg-white border-t border-slate-100 py-2.5 px-6 flex items-center justify-between text-center z-10">
            <a href="{{ route('customer.dashboard') }}" class="flex flex-col items-center gap-0.5 text-emerald-600">
                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-wider">Home</span>
            </a>

            <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-wider">Orderan</span>
            </button>

            <button onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span class="text-[9px] font-black uppercase tracking-wider">Profil</span>
            </button>
        </div>

    </div>
</div>
@endsection
