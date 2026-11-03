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
    
    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40 shadow-xs">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Live Analytics Hub</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
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

            <!-- SECTION 1: KEY PERFORMANCE INDICATORS (FINANCIAL & BUSINESS KPI) -->
            <div class="space-y-4">
                
                <!-- ROW 1: 4 KARTU FINANSIAL UTAMA OWNER (Untung Bersih, Kas Masuk, Kas Keluar & Refund, Escrow) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Card 1: Keuntungan Bersih Platform (Laba Komisi Aplikasi) -->
                    <div class="bg-gradient-to-br from-emerald-500/10 via-white to-white border-2 border-emerald-300 rounded-2xl p-5 shadow-xs hover:shadow-md transition relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-black text-emerald-700 uppercase tracking-wider block">Laba Bersih Aplikasi</span>
                                    <h4 class="font-extrabold text-sm text-slate-900 mt-0.5">Keuntungan Komisi</h4>
                                </div>
                                <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-100/90 border border-emerald-200 px-2 py-0.5 rounded-full uppercase shrink-0">
                                    100% Hak Owner
                                </span>
                            </div>
                            <div class="mt-3">
                                <span class="text-2xl font-black text-emerald-600 tracking-tight">
                                    Rp {{ number_format($platformCommissionEarned, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-emerald-100/80 flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">Nilai Order Selesai (GMV):</span>
                            <span class="font-black text-slate-800">Rp {{ number_format($grossOrderFare, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Card 2: Total Kas Masuk (Cash-In) -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div>
                                <span class="text-[10px] font-black text-sky-600 uppercase tracking-wider block">Total Arus Masuk</span>
                                <h4 class="font-extrabold text-sm text-slate-900 mt-0.5">Kas Masuk (Cash-In)</h4>
                            </div>
                            <div class="mt-3">
                                <span class="text-2xl font-black text-slate-900 tracking-tight">
                                    Rp {{ number_format($totalCashIn, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-100 grid grid-cols-2 gap-2 text-[10px]">
                            <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                                <span class="text-slate-400 block text-[9px]">Bayar Order:</span>
                                <strong class="text-slate-800 text-[11px]">Rp {{ number_format($cashInPayments, 0, ',', '.') }}</strong>
                            </div>
                            <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                                <span class="text-slate-400 block text-[9px]">Topup JK Pay:</span>
                                <strong class="text-slate-800 text-[11px]">Rp {{ number_format($cashInTopups, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Total Kas Keluar & Pengeluaran (Cash-Out) -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div>
                                <span class="text-[10px] font-black text-rose-600 uppercase tracking-wider block">Total Pengeluaran</span>
                                <h4 class="font-extrabold text-sm text-slate-900 mt-0.5">Kas Keluar (Cash-Out)</h4>
                            </div>
                            <div class="mt-3">
                                <span class="text-2xl font-black text-rose-600 tracking-tight">
                                    Rp {{ number_format($totalCashOut, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-100 grid grid-cols-2 gap-2 text-[10px]">
                            <div class="bg-rose-50/60 p-1.5 rounded-lg border border-rose-100">
                                <span class="text-rose-600 font-bold block text-[9px]">Withdraw ({{ $withdrawCount }}x):</span>
                                <strong class="text-rose-900 text-[11px]">Rp {{ number_format($cashOutWithdraws, 0, ',', '.') }}</strong>
                            </div>
                            <div class="bg-amber-50/70 p-1.5 rounded-lg border border-amber-200/80">
                                <span class="text-amber-700 font-bold block text-[9px]">Refund ({{ $refundCount }}x):</span>
                                <strong class="text-amber-900 text-[11px]">Rp {{ number_format($totalRefund, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Net Cash Flow (Arus Kas Bersih) -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-wider block">Posisi Kas Riil</span>
                                    <h4 class="font-extrabold text-sm text-slate-900 mt-0.5">Arus Kas Bersih (Net Flow)</h4>
                                </div>
                                <span class="text-[10px] {{ $netCashFlow >= 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200' }} border px-2 py-0.5 rounded-full font-bold shrink-0">
                                    {{ $netCashFlow >= 0 ? 'Surplus Kas' : 'Defisit Kas' }}
                                </span>
                            </div>
                            <div class="mt-3">
                                <span class="text-2xl font-black {{ $netCashFlow >= 0 ? 'text-emerald-600' : 'text-rose-600' }} tracking-tight">
                                    {{ $netCashFlow >= 0 ? '+' : '' }}Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">Kalkulasi Arus Kas:</span>
                            <span class="font-bold text-slate-700">
                                Kas Masuk - Kas Keluar
                            </span>
                        </div>
                    </div>

                </div>

                <!-- ROW 2: MINI STRIP OPERASIONAL (Pesanan Masuk, Aktif, Selesai, Driver Siaga) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white border border-slate-200/80 p-3 rounded-2xl shadow-xs">
                    <div class="flex items-center gap-3 px-3 py-1.5 border-r border-slate-100 last:border-0">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Pesanan Masuk</span>
                            <div class="flex items-baseline gap-1.5">
                                <strong class="text-sm font-black text-slate-800">{{ number_format($totalOrdersFiltered, 0, ',', '.') }}</strong>
                                <span class="text-[9px] text-slate-400 truncate">(All: {{ number_format($totalOrdersAllTime, 0, ',', '.') }})</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 px-3 py-1.5 border-r border-slate-100 last:border-0">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Sedang Berjalan</span>
                            <div class="flex items-baseline gap-1.5">
                                <strong class="text-sm font-black text-amber-600">{{ number_format($activeOrdersCount, 0, ',', '.') }}</strong>
                                <span class="text-[9px] text-emerald-600 font-bold">({{ number_format($completedOrdersCount, 0, ',', '.') }} Selesai)</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 px-3 py-1.5 border-r border-slate-100 last:border-0">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Batal / Sengketa</span>
                            <div class="flex items-baseline gap-1.5">
                                <strong class="text-sm font-black text-rose-600">{{ number_format($cancelledOrdersCount, 0, ',', '.') }}</strong>
                                <span class="text-[9px] text-slate-400">order</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 px-3 py-1.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Driver Siaga</span>
                            <div class="flex items-baseline gap-1.5">
                                <strong class="text-sm font-black text-slate-800">{{ number_format($totalActiveDriversCount, 0, ',', '.') }}</strong>
                                <span class="text-[9px] text-emerald-600 font-bold">{{ $totalOnlineTersedia }} Online</span>
                            </div>
                        </div>
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
                                <h3 class="font-black text-sm text-slate-800">Analisis Keuangan Masuk, Keluar & Laba</h3>
                                <p class="text-[11px] text-slate-400">Arus dana pembayaran, penarikan, refund & komisi</p>
                            </div>
                        </div>

                        <!-- Bar Chart Canvas -->
                        <div class="relative h-48 w-full flex items-center justify-center my-3">
                            <canvas id="financeChart"></canvas>
                        </div>
                    </div>

                    <!-- Financial Summary Breakdown Cards (3 Kolom: Masuk, Keluar & Refund, Laba Komisi) -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 text-xs">
                        <div class="bg-sky-50/70 p-2 rounded-xl border border-sky-100">
                            <div class="flex items-center gap-1 text-sky-700 font-bold text-[9px] uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Kas Masuk
                            </div>
                            <div class="mt-1 font-black text-sky-800 text-xs truncate">
                                Rp {{ number_format($totalCashIn, 0, ',', '.') }}
                            </div>
                            <div class="text-[8px] text-sky-600 mt-0.5 truncate">
                                Order & Topup
                            </div>
                        </div>

                        <div class="bg-rose-50/70 p-2 rounded-xl border border-rose-100">
                            <div class="flex items-center gap-1 text-rose-700 font-bold text-[9px] uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Pengeluaran
                            </div>
                            <div class="mt-1 font-black text-rose-800 text-xs truncate">
                                Rp {{ number_format($totalCashOut, 0, ',', '.') }}
                            </div>
                            <div class="text-[8px] text-rose-600 mt-0.5 truncate">
                                Wd & Refund
                            </div>
                        </div>

                        <div class="bg-emerald-50/70 p-2 rounded-xl border border-emerald-100">
                            <div class="flex items-center gap-1 text-emerald-700 font-bold text-[9px] uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Untung Bersih
                            </div>
                            <div class="mt-1 font-black text-emerald-800 text-xs truncate">
                                Rp {{ number_format($platformCommissionEarned, 0, ',', '.') }}
                            </div>
                            <div class="text-[8px] text-emerald-600 mt-0.5 truncate">
                                Laba Komisi
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
                            <select name="status_filter" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                <option value="all" {{ request('status_filter') === 'all' ? 'selected' : '' }}>Semua Status</option>
                                <option value="aktif" {{ request('status_filter') === 'aktif' ? 'selected' : '' }}>Sedang Aktif</option>
                                <option value="selesai" {{ request('status_filter') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                <option value="menunggu_tawaran" {{ request('status_filter') === 'menunggu_tawaran' ? 'selected' : '' }}>Menunggu Tawaran</option>
                                <option value="menunggu_pembayaran" {{ request('status_filter') === 'menunggu_pembayaran' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                                <option value="deal" {{ request('status_filter') === 'deal' ? 'selected' : '' }}>Deal / Diproses</option>
                                <option value="dibatalkan" {{ request('status_filter') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>

                            <!-- Category Filter -->
                            <select name="category_filter" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
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
                                    class="text-xs bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 w-48 sm:w-60">
                                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>

                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-3 py-2 rounded-xl transition cursor-pointer">
                                Filter
                            </button>
                            
                            @if(request()->hasAny(['search', 'status_filter', 'category_filter']))
                            <a href="{{ route('admin.dashboard', ['date_range' => $dateFilter]) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 hover:underline px-2 py-2">
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
                                    @if($order->status === 'bermasalah')
                                        @php
                                            $activeReport = $order->reports ? $order->reports->where('status', 'menunggu')->first() : null;
                                        @endphp
                                        <a href="{{ $activeReport ? route('admin.reports.show', $activeReport->id) : route('admin.reports.index') }}" 
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider border bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100 transition">
                                            <svg class="w-3 h-3 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            <span>Bermasalah (Tindak)</span>
                                        </a>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                                            {{ str_replace('_', ' ', $order->status) }}
                                        </span>
                                    @endif

                                    @if($order->pickup_photo || $order->delivery_photo)
                                        <div class="mt-1 flex items-center gap-1.5">
                                            @if($order->pickup_photo)
                                                <a href="{{ Storage::url($order->pickup_photo) }}" target="_blank" class="inline-flex items-center gap-1 text-[9.5px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded hover:bg-indigo-100 transition" title="Foto Ambil Barang: {{ $order->pickup_time?->format('H:i') }}">
                                                    <svg class="w-2.5 h-2.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <span>Foto Ambil</span>
                                                </a>
                                            @endif
                                            @if($order->delivery_photo)
                                                <a href="{{ Storage::url($order->delivery_photo) }}" target="_blank" class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded hover:bg-emerald-100 transition" title="Foto Serah Terima: {{ $order->delivery_time?->format('H:i') }}">
                                                    <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <span>Foto Tiba</span>
                                                </a>
                                            @endif
                                        </div>
                                    @endif
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
            <!-- SECTION 5: RINGKASAN BUKU BESAR MUTASI & AKSES MASTER DATA -->
            <div id="master-mutasi" class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden scroll-mt-24">
                
                <!-- Section Header -->
                <div class="p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <div class="flex items-center gap-2.5 mb-1">
                            <span class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </span>
                            <h2 class="text-lg font-bold text-slate-900 tracking-tight">Ringkasan Mutasi Keuangan & Buku Besar (Ledger)</h2>
                            <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold uppercase tracking-wider font-mono border border-slate-200">
                                Transparansi Arus Kas
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">Rekapitulasi total mutasi kredit & debit saldo dompet digital platform. Untuk audit transaksi detail per pengguna, silakan buka halaman Master Data Mutasi.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.mutations.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-sm flex items-center gap-2">
                            <span>Buka Master Data Mutasi Lengkap</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                </div>

                <!-- Ledger KPI Cards -->
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-emerald-50/50 rounded-xl border border-emerald-100 p-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full">
                                Total Kredit (Uang Masuk Saldo)
                            </span>
                            <h4 class="text-xl font-black text-emerald-700 mt-2">
                                Rp {{ number_format($mutationTotalKredit, 0, ',', '.') }}
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-1">Akumulasi seluruh mutasi kredit (top-up, transfer saldo masuk)</p>
                        </div>

                        <div class="bg-rose-50/50 rounded-xl border border-rose-100 p-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 bg-rose-100/70 px-2 py-0.5 rounded-full">
                                Total Debit (Uang Keluar Saldo)
                            </span>
                            <h4 class="text-xl font-black text-rose-700 mt-2">
                                Rp {{ number_format($mutationTotalDebit, 0, ',', '.') }}
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-1">Akumulasi seluruh mutasi debit (bayar belanja, withdraw)</p>
                        </div>

                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-700 bg-white px-2 py-0.5 rounded-full border border-slate-200">
                                Total Baris Mutasi
                            </span>
                            <h4 class="text-xl font-black text-slate-900 mt-2">
                                {{ number_format($mutationTotalCount, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">catatan</span>
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-1">Seluruh log mutasi tercatat aman di database dengan integritas audit</p>
                        </div>

                        <div class="bg-indigo-50/50 rounded-xl border border-indigo-100 p-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-100/70 px-2 py-0.5 rounded-full">
                                Posisi Net Mutasi
                            </span>
                            <h4 class="text-xl font-black {{ $mutationNet >= 0 ? 'text-indigo-700' : 'text-rose-700' }} mt-2">
                                {{ $mutationNet >= 0 ? '+' : '' }}Rp {{ number_format($mutationNet, 0, ',', '.') }}
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-1">Selisih total kredit terhadap debit di ekosistem saldo</p>
                        </div>
                    </div>

                    <!-- Quick Navigation & Tips Card -->
                    <div class="p-5 rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Pusat Audit Finansial & Operasional</h3>
                            </div>
                            <p class="text-xs text-slate-350 max-w-2xl leading-relaxed">
                                Tabel mutasi detail dipusatkan di halaman Master Data Mutasi agar dashboard utama tetap ringan, cepat, dan fokus pada analitik performa. Di halaman master data, Anda dapat melakukan pencarian detail nama / no HP, audit timestamp, serta filter kategori sumber transaksi.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                            <a href="{{ route('admin.withdraws') }}" class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-white transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Approval Withdraw
                            </a>
                            <a href="{{ route('admin.payments') }}" class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-white transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Approval Top-up
                            </a>
                            <a href="{{ route('admin.mutations.index') }}" class="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs transition shadow-xs flex items-center gap-1.5">
                                <span>Buka Master Mutasi</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </main>
        
    </div>

</div>

<!-- Mobile Sidebar Overlay -->
<div id="mobile-sidebar" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex">
    <div class="w-72 bg-slate-950 h-full flex flex-col justify-between p-4 shadow-xl text-slate-350">
        <div>
            <div class="h-16 flex items-center px-4 gap-3">
                <div class="w-8 h-8 bg-slate-800 border border-slate-700 rounded-lg flex items-center justify-center font-bold text-white text-sm">
                    JK
                </div>
                <div>
                    <h3 class="font-bold text-sm text-white tracking-wide">JastipKuy</h3>
                    <span class="text-[9px] font-semibold text-slate-400 tracking-wider uppercase block -mt-0.5 font-mono">Admin Portal</span>
                </div>
            </div>

            <nav class="mt-6 space-y-1.5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-800 text-white font-semibold text-xs">
                    <span>Dashboard Utama</span>
                </a>
                <a href="{{ route('admin.mutations.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Master Data Mutasi</span>
                </a>
                <a href="{{ route('admin.activities.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Monitoring Log Aktivitas</span>
                </a>
                <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Verifikasi Jastiper</span>
                </a>
                <a href="{{ route('admin.payments') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Verifikasi Pembayaran</span>
                </a>
                <button onclick="showMaintenanceToast(event); toggleMobileSidebar()" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs text-left cursor-pointer">
                    <span>Kelola Wilayah</span>
                </button>
                <a href="{{ route('admin.merchants.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Kelola Merchant</span>
                </a>
                <a href="{{ route('admin.promos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Manajemen Promo</span>
                </a>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Daftar Pelanggan</span>
                </a>
                <a href="{{ route('admin.jastipers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Daftar Jastiper</span>
                </a>
                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs">
                    <span>Withdraw Jastiper</span>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-900">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white font-medium text-xs cursor-pointer">
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
                                        return 'Rp ' + Number(value).toLocaleString('id-ID');
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
