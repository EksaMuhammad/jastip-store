@extends('layouts.support')

@section('title', 'Dashboard Customer')

@section('content')
<div class="min-h-screen bg-[#F8FAFC] py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Banner Section -->
        <div class="bg-gradient-to-r from-rose-600 to-rose-700 text-white rounded-3xl p-6 sm:p-8 mb-8 shadow-md relative overflow-hidden">
            <!-- Background Accent Circle -->
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative z-10">
                <div class="flex items-center gap-4">
                    <!-- Avatar with initials -->
                    <div class="w-14 h-14 bg-white/20 border-2 border-white/30 rounded-2xl flex items-center justify-center font-bold text-xl shadow-inner">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                    <div>
                        <span class="text-[10px] text-rose-100 font-bold uppercase tracking-widest block">Selamat Datang</span>
                        <h1 class="font-display font-black text-2xl md:text-3xl leading-tight">{{ $customer->name }}</h1>
                        <p class="text-xs text-rose-100/80 mt-1">Kelola kebutuhan jasa titip belanjaan Anda di wilayah Malang.</p>
                    </div>
                </div>

                <!-- Logout Button Form -->
                <form action="{{ route('logout') }}" method="POST" class="shrink-0 w-full md:w-auto">
                    @csrf
                    <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-5 py-3 rounded-xl border border-slate-950 transition duration-150 uppercase tracking-wider">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Keluar Sesi
                    </button>
                </form>
            </div>
        </div>

        <!-- Dashboard Responsive Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- COLUMN 1: Profile & Wallet Balance -->
            <div class="space-y-8">
                <!-- Profile details -->
                <div class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm">
                    <h3 class="font-display font-bold text-base text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Detail Akun Anda
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Nama Lengkap</span>
                            <span class="text-sm font-bold text-slate-800">{{ $customer->name }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Nomor Handphone</span>
                            <span class="text-sm font-bold text-slate-800">+62 {{ $customer->phone_number }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Status Akun</span>
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-md mt-1">
                                <span class="w-1.5 h-1.5 bg-rose-600 rounded-full"></span>
                                Aktif (Customer)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Wallet Card (Gopay / OVO Style but Rose Color) -->
                <div class="bg-slate-900 text-white rounded-3xl p-6 shadow-md border border-slate-800 flex flex-col justify-between">
                    <div class="space-y-2 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Saldo Dompet Jastip</span>
                            <span class="text-[8px] bg-rose-600 text-white px-2 py-0.5 rounded-sm font-bold uppercase">JastipKuy Pay</span>
                        </div>
                        <div class="text-3xl font-black font-display tracking-tight text-rose-500">Rp0</div>
                        <span class="text-[8px] text-slate-500 font-semibold block">ID Pemilik: #CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    
                    <div class="flex items-center gap-2 pt-4 border-t border-slate-800">
                        <button onclick="showMaintenanceToast(event)" class="flex-1 inline-flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-2.5 rounded-xl transition shadow-md shadow-rose-600/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Top Up
                        </button>
                        <button onclick="showMaintenanceToast(event)" class="flex-1 inline-flex items-center justify-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs py-2.5 rounded-xl transition border border-slate-700/60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Riwayat
                        </button>
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: Layanan Jasa Titip (Main Services Grid) -->
            <div class="space-y-6 lg:col-span-2">
                <div class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm">
                    <div class="border-b border-slate-100 pb-3 mb-6">
                        <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Layanan Titip Belanja</h3>
                        <p class="text-xs text-slate-400 mt-1">Pilih kategori belanjaan yang ingin Anda titipkan ke kurir Jastiper.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Titip Makanan -->
                        <button onclick="showMaintenanceToast(event)" class="text-left p-4 bg-slate-50 hover:bg-rose-50/20 border border-slate-100 hover:border-rose-200 rounded-2xl transition duration-150 flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-rose-100 group-hover:scale-105 rounded-xl flex items-center justify-center transition duration-150 shrink-0 shadow-sm">
                                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide group-hover:text-rose-600 transition">Titip Kuliner</h4>
                                <p class="text-[10px] text-slate-500 mt-1 leading-normal">Pesan makanan, minuman, kue, atau camilan khas dari warung dan restoran terdekat.</p>
                            </div>
                        </button>

                        <!-- Titip Minimarket -->
                        <button onclick="showMaintenanceToast(event)" class="text-left p-4 bg-slate-50 hover:bg-rose-50/20 border border-slate-100 hover:border-rose-200 rounded-2xl transition duration-150 flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-blue-100 group-hover:scale-105 rounded-xl flex items-center justify-center transition duration-150 shrink-0 shadow-sm">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide group-hover:text-rose-600 transition">Titip Minimarket</h4>
                                <p class="text-[10px] text-slate-500 mt-1 leading-normal">Titip sabun mandi, detergen, camilan kemasan, atau tisu dari Indomaret, Alfamart, dll.</p>
                            </div>
                        </button>

                        <!-- Belanja Pasar -->
                        <button onclick="showMaintenanceToast(event)" class="text-left p-4 bg-slate-50 hover:bg-rose-50/20 border border-slate-100 hover:border-rose-200 rounded-2xl transition duration-150 flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-emerald-100 group-hover:scale-105 rounded-xl flex items-center justify-center transition duration-150 shrink-0 shadow-sm">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide group-hover:text-rose-600 transition">Belanja Pasar</h4>
                                <p class="text-[10px] text-slate-500 mt-1 leading-normal">Beli sayur mayur segar, bumbu masak, daging ayam/sapi segar langsung dari pasar tradisional.</p>
                            </div>
                        </button>

                        <!-- Titip Bebas -->
                        <button onclick="showMaintenanceToast(event)" class="text-left p-4 bg-slate-50 hover:bg-rose-50/20 border border-slate-100 hover:border-rose-200 rounded-2xl transition duration-150 flex items-start gap-4 group">
                            <div class="w-12 h-12 bg-amber-100 group-hover:scale-105 rounded-xl flex items-center justify-center transition duration-150 shrink-0 shadow-sm">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 10.742l-1.977-1.977a2.25 2.25 0 113.182-3.182l1.977 1.977M21 21l-3.5-3.5m0 0A7.5 7.5 0 1118 6.5a7.5 7.5 0 010 11z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide group-hover:text-rose-600 transition">Titip Bebas</h4>
                                <p class="text-[10px] text-slate-500 mt-1 leading-normal">Beli produk kustom apa saja di toko spesifik pilihan Anda yang tidak ada di kategori lain.</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Active Order Tracker Section (Horizontal Expanded for Desktop) -->
                <div class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm">
                    <h3 class="font-display font-black text-sm text-slate-800 uppercase tracking-wider mb-4">Pelacakan Pesanan Aktif</h3>
                    
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-4 text-left">
                            <div class="w-12 h-12 bg-slate-200/50 rounded-full flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-xs text-slate-700">Belum Ada Belanjaan Aktif</h4>
                                <p class="text-[10px] text-slate-400 mt-0.5 leading-normal max-w-md">Setelah Anda membuat permintaan jasa titip, riwayat pemesanan & posisi kurir Jastiper akan muncul langsung di sini.</p>
                            </div>
                        </div>

                        <a href="{{ url('/#calculator') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-6 py-3 rounded-full transition shadow-md shadow-rose-600/10 uppercase tracking-wider whitespace-nowrap">
                            Buat Permintaan Jastip
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
