@extends('layouts.support')

@section('title', 'Panel Admin - Daftar Jastiper')

@section('content')
<div class="flex min-h-screen bg-[#F3F4F6]">
    
    <!-- LEFT SIDEBAR -->
    <aside class="w-72 bg-slate-950 text-slate-350 flex flex-col justify-between shrink-0 border-r border-slate-800 hidden lg:flex sticky top-0 h-screen">
        <div>
            <!-- Sidebar Header Brand -->
            <div class="h-16 flex items-center px-6 border-b border-slate-850 gap-3">
                <div class="w-8 h-8 bg-rose-600 rounded-lg flex items-center justify-center font-bold text-white text-sm shadow-md shadow-rose-600/30">
                    JK
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-white tracking-wide">JastipKuy</h3>
                    <span class="text-[8px] font-black text-rose-500 tracking-widest uppercase block -mt-0.5">ADMIN PORTAL</span>
                </div>
            </div>

            <!-- Profile Summary Box -->
            <div class="p-6 border-b border-slate-900 bg-slate-950">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-rose-500 rounded-full flex items-center justify-center font-bold text-white text-sm shrink-0">
                        AD
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-extrabold text-xs text-white truncate">Admin JastipKuy</h4>
                        <span class="text-[9px] text-slate-400 block mt-0.5 truncate">admin@jastipkuy.com</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigation Menu -->
            <nav class="p-4 space-y-1.5">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-wider block px-3 mb-2">Menu Utama</span>

                <!-- Menu: Dashboard Utama -->
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Utama</span>
                    </div>
                </a>

                <!-- Menu: Verifikasi Jastiper -->
                <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Verifikasi Jastiper</span>
                    </div>
                </a>

                <!-- Menu: Verifikasi Pembayaran -->
                <a href="{{ route('admin.payments') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Verifikasi Pembayaran</span>
                    </div>
                </a>

                <!-- Menu: Kelola Wilayah -->
                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Kelola Wilayah</span>
                    </div>
                </button>

                <!-- Menu: Kelola Merchant -->
                <a href="{{ route('admin.merchants.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Kelola Merchant</span>
                    </div>
                </a>

                <!-- Menu: Manajemen Promo -->
                <a href="{{ route('admin.promos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Manajemen Promo</span>
                    </div>
                </a>

                <!-- Menu: Daftar Pelanggan -->
                <a href="{{ route('admin.customers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </a>

                <!-- Menu: Daftar Jastiper (ACTIVE) -->
                <a href="{{ route('admin.jastipers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition border border-rose-500/20">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Daftar Jastiper</span>
                    </div>
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                </a>

                <!-- Menu: Withdraw Jastiper -->
                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Withdraw Jastiper</span>
                    </div>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-900 bg-slate-950">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-rose-500 hover:bg-rose-500/10 font-bold text-xs transition text-left">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Keluar Panel</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Jastiper & Driver Directory</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-900 rounded-full flex items-center justify-center font-bold text-white text-xs">
                    AD
                </div>
                <span class="text-xs font-bold text-slate-700 hidden sm:inline">Admin JastipKuy</span>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow p-6 lg:p-8 space-y-6">
            
            <!-- Header & Metrics -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Daftar Mitra Jastiper</h1>
                    <p class="text-xs text-slate-500 mt-1">Data lengkap seluruh mitra driver & jastiper, status kerja, wilayah, dan performa pesanan</p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="bg-white px-4 py-2 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                            {{ $totalJastiperCount }}
                        </div>
                        <div class="text-xs">
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Mitra Terdaftar</span>
                            <span class="font-bold text-emerald-600">{{ $totalOnlineCount }} sedang online</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
                <form action="{{ route('admin.jastipers.index') }}" method="GET" class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Search Box -->
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, HP, lokasi check-in..."
                                class="text-xs bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-4 py-2.5 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500 w-64">
                            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>

                        <!-- Work Status Filter -->
                        <select name="work_status" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <option value="all" {{ $workStatus === 'all' ? 'selected' : '' }}>Semua Status Kerja</option>
                            <option value="tersedia" {{ $workStatus === 'tersedia' ? 'selected' : '' }}>🟢 Tersedia (Menerima Order)</option>
                            <option value="standby" {{ $workStatus === 'standby' ? 'selected' : '' }}>🟡 Standby / Rehat</option>
                            <option value="offline" {{ $workStatus === 'offline' ? 'selected' : '' }}>⚪ Offline</option>
                        </select>

                        <!-- Verification Status Filter -->
                        <select name="verification_status" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <option value="all" {{ $verificationStatus === 'all' ? 'selected' : '' }}>Semua Status Akun</option>
                            <option value="approved" {{ $verificationStatus === 'approved' ? 'selected' : '' }}>✓ Terverifikasi (Approved)</option>
                            <option value="pending" {{ $verificationStatus === 'pending' ? 'selected' : '' }}>⏳ Menunggu Verifikasi</option>
                            <option value="rejected" {{ $verificationStatus === 'rejected' ? 'selected' : '' }}>✕ Ditolak</option>
                        </select>

                        <!-- Wilayah Filter -->
                        <select name="wilayah_id" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <option value="all" {{ $wilayahId === 'all' ? 'selected' : '' }}>Semua Wilayah</option>
                            @foreach($wilayahList as $wil)
                            <option value="{{ $wil->id }}" {{ (string)$wilayahId === (string)$wil->id ? 'selected' : '' }}>{{ $wil->name }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">
                            Cari
                        </button>

                        @if(!empty($search) || $workStatus !== 'all' || $verificationStatus !== 'all' || $wilayahId !== 'all')
                        <a href="{{ route('admin.jastipers.index') }}" class="text-xs font-bold text-rose-600 hover:underline px-2">
                            Reset Filter
                        </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Jastiper Table -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">ID Jastiper</th>
                                <th class="px-5 py-3.5">Nama & Kontak</th>
                                <th class="px-5 py-3.5">Status Kerja</th>
                                <th class="px-5 py-3.5">Verifikasi KTP</th>
                                <th class="px-5 py-3.5">Lokasi Check-in</th>
                                <th class="px-5 py-3.5">Saldo Dompet</th>
                                <th class="px-5 py-3.5">Pesanan (Aktif / Selesai)</th>
                                <th class="px-5 py-3.5">Rating</th>
                                <th class="px-5 py-3.5">Wilayah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($jastipers as $jastiper)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                        #JAS-{{ str_pad($jastiper->id, 4, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-black flex items-center justify-center text-xs">
                                            {{ strtoupper(substr($jastiper->name ?? 'J', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                {{ $jastiper->name ?? 'Tanpa Nama' }}
                                                @if($jastiper->badge)
                                                <span class="text-[9px] bg-amber-50 text-amber-700 px-1.5 py-0.2 rounded border border-amber-200 font-bold">
                                                    {{ $jastiper->badge->title ?? 'Badge' }}
                                                </span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-400">{{ $jastiper->phone_number }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($jastiper->work_status === 'tersedia')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Tersedia
                                        </span>
                                    @elseif($jastiper->work_status === 'standby')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            Standby
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                            Offline
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($jastiper->verification_status === 'approved')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Approved
                                        </span>
                                    @elseif($jastiper->verification_status === 'pending')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($jastiper->checkin_location)
                                        <div class="flex items-center gap-1 text-slate-700 font-semibold text-xs">
                                            <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                            <span class="truncate max-w-[170px]">{{ $jastiper->checkin_location }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-[10px] italic">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $balance = $jastiper->wallet ? $jastiper->wallet->balance : 0;
                                    @endphp
                                    <span class="font-black text-xs {{ $balance > 0 ? 'text-indigo-600' : 'text-slate-500' }}">
                                        Rp {{ number_format($balance, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        @if($jastiper->active_orders_count > 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            {{ $jastiper->active_orders_count }} Aktif
                                        </span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ $jastiper->completed_orders_count }} Selesai
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <span class="text-amber-500">★</span>
                                        <span class="font-bold text-slate-800 text-xs">
                                            {{ $jastiper->ratings_avg_rating ? number_format($jastiper->ratings_avg_rating, 1) : '-' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">({{ $jastiper->ratings_count }})</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-medium">
                                    {{ $jastiper->wilayah->name ?? 'Semua Wilayah' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center text-slate-400">
                                    <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Tidak ada data jastiper yang cocok dengan kriteria pencarian.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($jastipers->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $jastipers->links() }}
                </div>
                @endif
            </div>

        </main>
    </div>

</div>

<!-- Mobile Sidebar Overlay -->
<div id="mobile-sidebar" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex">
    <div class="w-72 bg-slate-950 h-full flex flex-col justify-between p-4 shadow-xl text-slate-350">
        <div>
            <div class="h-16 flex items-center px-4 gap-3">
                <div class="w-8 h-8 bg-rose-600 rounded-lg flex items-center justify-center font-bold text-white text-sm">
                    JK
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-white tracking-wide">JastipKuy</h3>
                    <span class="text-[8px] font-black text-rose-500 tracking-widest uppercase block -mt-0.5">ADMIN PORTAL</span>
                </div>
            </div>

            <nav class="mt-6 space-y-1.5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Dashboard Utama</span>
                </a>
                <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Verifikasi Jastiper</span>
                </a>
                <a href="{{ route('admin.payments') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Verifikasi Pembayaran</span>
                </a>
                <button onclick="showMaintenanceToast(event); toggleMobileSidebar()" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs text-left">
                    <span>Kelola Wilayah</span>
                </button>
                <a href="{{ route('admin.merchants.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Kelola Merchant</span>
                </a>
                <a href="{{ route('admin.promos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Manajemen Promo</span>
                </a>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Daftar Pelanggan</span>
                </a>
                <a href="{{ route('admin.jastipers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs">
                    <span>Daftar Jastiper</span>
                </a>
                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Withdraw Jastiper</span>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-900">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-rose-500 font-bold text-xs">
                    <span>Keluar Panel</span>
                </button>
            </form>
        </div>
    </div>
    <div class="flex-grow h-full" onclick="toggleMobileSidebar()"></div>
</div>

<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        if (sidebar.classList.contains('hidden')) {
            sidebar.classList.remove('hidden');
        } else {
            sidebar.classList.add('hidden');
        }
    }
</script>
@endsection
