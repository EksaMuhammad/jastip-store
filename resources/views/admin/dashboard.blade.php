@extends('layouts.support')

@section('title', 'Panel Admin - Dashboard Analytics')

@section('styles')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
    }
</style>
@endsection

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]" x-data="adminAnalytics()">
    
    <!-- LEFT SIDEBAR: Full height sticky -->
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
                    <div class="w-10 h-10 bg-rose-500 rounded-full flex items-center justify-center font-bold text-white text-sm shrink-0 shadow-inner">
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

                <!-- Menu: Dashboard Utama (ACTIVE) -->
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Utama</span>
                    </div>
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
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

                <!-- Menu: Gateway Midtrans (Settings) -->
                <a href="{{ route('admin.settings.midtrans') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Gateway Midtrans</span>
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

                <!-- Menu: Daftar Customer -->
                <a href="{{ route('admin.customers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </a>

                <!-- Menu: Daftar Jastiper -->
                <a href="{{ route('admin.jastipers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Daftar Jastiper</span>
                    </div>
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

        <!-- Sidebar Footer Action (Logout) -->
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
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40 shadow-xs">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Live Analytics Hub</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Realtime Data
                    </span>
                </div>
            </div>

            <!-- Date Range Selector Form -->
            <form action="{{ route('admin.dashboard') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="status_filter" value="{{ request('status_filter', 'all') }}">
                <input type="hidden" name="category_filter" value="{{ request('category_filter', 'all') }}">
                <input type="hidden" name="search" value="{{ request('search', '') }}">
                
                <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                    <button type="submit" name="date_range" value="today" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $dateFilter === 'today' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        Hari Ini
                    </button>
                    <button type="submit" name="date_range" value="yesterday" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $dateFilter === 'yesterday' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        Kemarin
                    </button>
                    <button type="submit" name="date_range" value="this_week" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $dateFilter === 'this_week' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        Minggu Ini
                    </button>
                    <button type="submit" name="date_range" value="this_month" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $dateFilter === 'this_month' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        Bulan Ini
                    </button>
                    <button type="submit" name="date_range" value="all" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $dateFilter === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        Semua
                    </button>
                </div>
            </form>
        </header>

        <!-- Main Body Wrapper -->
        <main class="flex-grow p-6 lg:p-8 space-y-6">
            
            <!-- Dashboard Title & Quick Timestamp -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                        Dashboard Analisis Utama
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold capitalize">
                            Periode: {{ str_replace('_', ' ', $dateFilter) }}
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Pemantauan volume pesanan, aktivitas driver/jastiper, preferensi servis, dan arus kas keuangan.</p>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500 font-medium bg-white px-3 py-2 rounded-xl border border-slate-200 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Update Terakhir: <strong class="text-slate-700 font-bold">{{ now()->format('d M Y, H:i:s') }} WIB</strong></span>
                </div>
            </div>

            <!-- SECTION 1: KEY PERFORMANCE INDICATORS (KPI CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Total Pesanan Masuk (Filtered) -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pesanan Masuk</span>
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-800">{{ number_format($totalOrdersFiltered, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-400 font-medium">order</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">All-time total</span>
                        <span class="font-bold text-slate-700">{{ number_format($totalOrdersAllTime, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Card 2: Pesanan Aktif Berjalan -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-amber-500 uppercase tracking-wider">Sedang Aktif</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-amber-600">{{ number_format($activeOrdersCount, 0, ',', '.') }}</span>
                        <span class="text-xs text-amber-700 font-bold px-1.5 py-0.5 bg-amber-50 rounded">Berjalan</span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">Pesanan Selesai</span>
                        <span class="font-bold text-emerald-600">{{ number_format($completedOrdersCount, 0, ',', '.') }} selesai</span>
                    </div>
                </div>

                <!-- Card 3: Driver / Jastiper Aktif Online -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Driver / Jastiper Aktif</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-800">{{ number_format($totalActiveDriversCount, 0, ',', '.') }}</span>
                        <span class="text-xs text-emerald-600 font-bold px-1.5 py-0.5 bg-emerald-50 rounded">
                            {{ $totalOnlineTersedia }} Tersedia
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">Standby / Rehat</span>
                        <span class="font-bold text-amber-600">{{ $totalOnlineStandby }} Jastiper</span>
                    </div>
                </div>

                <!-- Card 4: Net Flow Keuangan Platform -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Arus Kas Masuk - Keluar</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-xl font-black {{ $netCashFlow >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $netCashFlow >= 0 ? '+' : '' }}Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">Komisi Platform</span>
                        <span class="font-bold text-rose-600">Rp {{ number_format($platformCommissionEarned, 0, ',', '.') }}</span>
                    </div>
                </div>

            </div>

            <!-- SECTION 2: CHARTS & SERVICE ANALYTICS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Chart 1: Distribusi Servis / Kategori Terbanyak (Donut / Bar) -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="font-black text-sm text-slate-800">Servis Paling Laris</h3>
                                <p class="text-[11px] text-slate-400">Preferensi kategori pesanan periode ini</p>
                            </div>
                            @if($topService && $topService['count'] > 0)
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-100">
                                Top: {{ $topService['label'] }}
                            </span>
                            @endif
                        </div>

                        <!-- Canvas Donut Chart -->
                        <div class="relative h-48 w-full flex items-center justify-center my-3">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>

                    <!-- List Rincian Kategori -->
                    <div class="space-y-2 pt-3 border-t border-slate-100">
                        @foreach(array_slice($serviceBreakdown, 0, 4) as $serv)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 truncate max-w-[160px]">{{ $serv['label'] }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-800">{{ $serv['count'] }} order</span>
                                <span class="text-[10px] text-slate-400">({{ number_format($serv['total_val'], 0, ',', '.') }})</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Chart 2: Keuangan Masuk vs Keluar Detail -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="font-black text-sm text-slate-800">Analisis Keuangan Masuk & Keluar</h3>
                                <p class="text-[11px] text-slate-400">Arus dana payment, topup, dan withdraw</p>
                            </div>
                        </div>

                        <!-- Bar Chart Canvas -->
                        <div class="relative h-48 w-full flex items-center justify-center my-3">
                            <canvas id="financeChart"></canvas>
                        </div>
                    </div>

                    <!-- Financial Summary Breakdown Cards -->
                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs">
                        <div class="bg-emerald-50/60 p-2.5 rounded-xl border border-emerald-100">
                            <div class="flex items-center gap-1.5 text-emerald-700 font-bold text-[10px] uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Total Masuk
                            </div>
                            <div class="mt-1 font-black text-emerald-800 text-sm">
                                Rp {{ number_format($totalCashIn, 0, ',', '.') }}
                            </div>
                            <div class="text-[9px] text-emerald-600 mt-0.5">
                                Order: {{ number_format($cashInPayments, 0, ',', '.') }} | Topup: {{ number_format($cashInTopups, 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="bg-rose-50/60 p-2.5 rounded-xl border border-rose-100">
                            <div class="flex items-center gap-1.5 text-rose-700 font-bold text-[10px] uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Total Keluar
                            </div>
                            <div class="mt-1 font-black text-rose-800 text-sm">
                                Rp {{ number_format($totalCashOut, 0, ',', '.') }}
                            </div>
                            <div class="text-[9px] text-rose-600 mt-0.5">
                                Disbursed Jastiper Withdraws
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart 3: Distribusi Jam Sibuk Hari Ini -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="font-black text-sm text-slate-800">Tren Jam Pemesanan</h3>
                                <p class="text-[11px] text-slate-400">Intensitas pesanan per jam (00:00 - 23:00)</p>
                            </div>
                            <span class="text-[10px] font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded-full border border-sky-100">
                                24 Jam
                            </span>
                        </div>

                        <div class="relative h-48 w-full flex items-center justify-center my-3">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Puncak pesanan terdeteksi pada jam sibuk.</span>
                        <span class="font-bold text-slate-800">Total: {{ array_sum($hourlyDistribution) }} order</span>
                    </div>
                </div>

            </div>

            <!-- SECTION 3: TABEL DRIVER / JASTIPER AKTIF HARI INI -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="font-black text-sm text-slate-800 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Driver & Jastiper Aktif Saat Ini
                        </h2>
                        <p class="text-[11px] text-slate-400">Daftar mitra jastiper yang sedang online, standby, atau sedang mengantar pesanan</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-xl">
                            {{ $activeDriversList->count() }} Driver Terdeteksi
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 border-b border-slate-200">Mitra Jastiper</th>
                                <th class="px-5 py-3 border-b border-slate-200">Kontak</th>
                                <th class="px-5 py-3 border-b border-slate-200">Status Kerja</th>
                                <th class="px-5 py-3 border-b border-slate-200">Lokasi Check-in Terakhir</th>
                                <th class="px-5 py-3 border-b border-slate-200">Pesanan Aktif Ditangani</th>
                                <th class="px-5 py-3 border-b border-slate-200">Wilayah Operasional</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($activeDriversList as $driver)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs">
                                            {{ strtoupper(substr($driver->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $driver->name }}</div>
                                            <div class="text-[10px] text-slate-400">ID: #{{ str_pad($driver->id, 4, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ $driver->phone_number }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($driver->work_status === 'tersedia')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Tersedia (Menerima Order)
                                        </span>
                                    @elseif($driver->work_status === 'standby')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            Standby / Istirahat
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                            Offline
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($driver->checkin_location)
                                        <div class="flex items-center gap-1 text-slate-700 font-semibold">
                                            <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                            <span class="truncate max-w-[200px]">{{ $driver->checkin_location }}</span>
                                        </div>
                                        <div class="text-[9px] text-slate-400 mt-0.5">
                                            {{ $driver->checked_in_at ? $driver->checked_in_at->diffForHumans() : '-' }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">Belum check-in titik spesifik</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($driver->current_orders_count > 0)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ $driver->current_orders_count }} Pesanan Sedang Diproses
                                        </span>
                                    @else
                                        <span class="text-[10px] font-medium text-slate-400">Tidak ada pesanan aktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-medium">
                                    {{ $driver->wilayah->name ?? 'Semua Wilayah' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                    Saat ini belum ada driver yang sedang online atau check-in.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 4: TABEL PESANAN & ANALISIS HARIAN LENGKAP -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h2 class="font-black text-sm text-slate-800">Tabel Analisis Pesanan</h2>
                            <p class="text-[11px] text-slate-400">Telusuri seluruh riwayat pesanan dengan filter status, kategori, dan pencarian instan</p>
                        </div>

                        <!-- Search & Filter Form -->
                        <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="date_range" value="{{ $dateFilter }}">

                            <!-- Status Filter -->
                            <select name="status_filter" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500">
                                <option value="all" {{ request('status_filter') === 'all' ? 'selected' : '' }}>Semua Status</option>
                                <option value="aktif" {{ request('status_filter') === 'aktif' ? 'selected' : '' }}>⚡ Sedang Aktif</option>
                                <option value="selesai" {{ request('status_filter') === 'selesai' ? 'selected' : '' }}>✓ Selesai</option>
                                <option value="menunggu_tawaran" {{ request('status_filter') === 'menunggu_tawaran' ? 'selected' : '' }}>Menunggu Tawaran</option>
                                <option value="menunggu_pembayaran" {{ request('status_filter') === 'menunggu_pembayaran' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                                <option value="deal" {{ request('status_filter') === 'deal' ? 'selected' : '' }}>Deal / Diproses</option>
                                <option value="dibatalkan" {{ request('status_filter') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>

                            <!-- Category Filter -->
                            <select name="category_filter" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500">
                                <option value="all" {{ request('category_filter') === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                                <option value="beli-antar" {{ request('category_filter') === 'beli-antar' ? 'selected' : '' }}>Beli & Antar</option>
                                <option value="ambil-antar" {{ request('category_filter') === 'ambil-antar' ? 'selected' : '' }}>Ambil & Antar</option>
                                <option value="toko-kirim" {{ request('category_filter') === 'toko-kirim' ? 'selected' : '' }}>Toko / Katalog</option>
                                <option value="dokumen" {{ request('category_filter') === 'dokumen' ? 'selected' : '' }}>Dokumen</option>
                                <option value="multi-stop" {{ request('category_filter') === 'multi-stop' ? 'selected' : '' }}>Multi-Stop</option>
                                <option value="kirim-pihak-ketiga" {{ request('category_filter') === 'kirim-pihak-ketiga' ? 'selected' : '' }}>Ekspedisi 3rd Party</option>
                            </select>

                            <!-- Search Input -->
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ID, customer, driver..."
                                    class="text-xs bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500 w-48 sm:w-60">
                                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>

                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-3 py-2 rounded-xl transition">
                                Filter
                            </button>
                            
                            @if(request()->hasAny(['search', 'status_filter', 'category_filter']))
                            <a href="{{ route('admin.dashboard', ['date_range' => $dateFilter]) }}" class="text-xs font-bold text-rose-600 hover:underline px-2 py-2">
                                Reset
                            </a>
                            @endif
                        </form>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 border-b border-slate-200">Order ID</th>
                                <th class="px-5 py-3 border-b border-slate-200">Customer</th>
                                <th class="px-5 py-3 border-b border-slate-200">Mitra Jastiper</th>
                                <th class="px-5 py-3 border-b border-slate-200">Kategori Servis</th>
                                <th class="px-5 py-3 border-b border-slate-200">Nominal Transaksi</th>
                                <th class="px-5 py-3 border-b border-slate-200">Status</th>
                                <th class="px-5 py-3 border-b border-slate-200">Waktu Order</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($latestOrders as $order)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800">{{ $order->customer->name ?? '-' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $order->customer->phone_number ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($order->jastiper)
                                        <div class="font-bold text-slate-800">{{ $order->jastiper->name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">ID: #{{ str_pad($order->jastiper->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">Belum Ditugaskan</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ str_replace('-', ' ', $order->category) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-black text-rose-600">
                                    Rp {{ number_format($order->agreed_fare ?? $order->estimated_fare, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $statusBadges = [
                                            'menunggu_tawaran' => 'bg-sky-50 text-sky-700 border-sky-200',
                                            'ada_tawaran' => 'bg-sky-50 text-sky-700 border-sky-200',
                                            'menunggu_pembayaran' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'deal' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'diproses' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'barang_diambil' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'sedang_diantar' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'tiba_tujuan' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'diterima' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'selesai' => 'bg-emerald-100 text-emerald-800 border-emerald-300 font-black',
                                            'dibatalkan' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'bermasalah' => 'bg-rose-100 text-rose-800 border-rose-300',
                                        ];
                                        $badgeClass = $statusBadges[$order->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                    @endphp
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                                        {{ str_replace('_', ' ', $order->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-[11px] text-slate-500">
                                    {{ $order->created_at->format('d M Y, H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    Tidak ada data pesanan yang sesuai dengan filter.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($latestOrders->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $latestOrders->links() }}
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
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs">
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
                <a href="{{ route('admin.jastipers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
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

    function adminAnalytics() {
        return {
            init() {
                this.renderCategoryChart();
                this.renderFinanceChart();
                this.renderHourlyChart();
            },
            renderCategoryChart() {
                const ctx = document.getElementById('categoryChart');
                if (!ctx) return;

                const categoryData = @json($serviceBreakdown);
                const labels = categoryData.map(c => c.label);
                const data = categoryData.map(c => c.count);

                // If all counts are zero, show fallback sample distribution
                const hasData = data.some(val => val > 0);
                const chartData = hasData ? data : [1, 1, 1, 1, 1, 1];
                const chartLabels = labels;

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: [
                                '#E11D48', // rose
                                '#3B82F6', // blue
                                '#10B981', // emerald
                                '#F59E0B', // amber
                                '#8B5CF6', // purple
                                '#06B6D4'  // cyan
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const count = hasData ? context.parsed : 0;
                                        return ` ${context.label}: ${count} order`;
                                    }
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
            },
            renderFinanceChart() {
                const ctx = document.getElementById('financeChart');
                if (!ctx) return;

                const cashIn = {{ (float)$totalCashIn }};
                const cashOut = {{ (float)$totalCashOut }};
                const commission = {{ (float)$platformCommissionEarned }};

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Uang Masuk', 'Uang Keluar', 'Komisi App'],
                        datasets: [{
                            label: 'Nominal (Rp)',
                            data: [cashIn, cashOut, commission],
                            backgroundColor: [
                                '#10B981', // green for In
                                '#EF4444', // red for Out
                                '#E11D48'  // rose for Commission
                            ],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return ' Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + (value >= 1000000 ? (value/1000000).toFixed(1) + 'M' : (value/1000).toFixed(0) + 'k');
                                    },
                                    font: { size: 10 }
                                },
                                grid: { color: '#F1F5F9' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 11, weight: '600' } }
                            }
                        }
                    }
                });
            },
            renderHourlyChart() {
                const ctx = document.getElementById('hourlyChart');
                if (!ctx) return;

                const hourlyCounts = @json(array_values($hourlyDistribution));
                const labels = Array.from({length: 24}, (_, i) => String(i).padStart(2, '0') + ':00');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Pesanan Masuk',
                            data: hourlyCounts,
                            borderColor: '#0284C7',
                            backgroundColor: 'rgba(2, 132, 199, 0.08)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, font: { size: 10 } },
                                grid: { color: '#F1F5F9' }
                            },
                            x: {
                                ticks: {
                                    font: { size: 9 },
                                    maxTicksLimit: 8
                                },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        };
    }
</script>
@endsection
