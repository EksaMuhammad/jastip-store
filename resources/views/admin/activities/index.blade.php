@extends('layouts.support')

@section('title', 'Monitoring & Log Aktivitas Seluruh Pengguna - Admin Portal')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F8FAFC]" x-data="adminActivityMonitoring()">
    
    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 shrink-0 z-40">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-500 hover:text-slate-800 text-xs font-semibold flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Dashboard Utama
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-bold text-slate-800">Log Aktivitas & Audit Trail</span>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Sistem Audit Aktif
                </span>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-1 p-4 lg:p-6 space-y-4">

            <!-- STATIC TOP PANEL: Title, Metrics & Controls (Statis, tidak ikut scroll) -->
            <div class="shrink-0 space-y-3">
                
                <!-- Title & Quick Links -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl lg:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-slate-900 text-white flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </span>
                            Log Aktivitas Pengguna
                        </h1>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Perekaman komprehensif riwayat transaksi, pergerakan kurir di lokasi, penyesuaian harga toko, refund pembatalan, dan tiket sengketa CS.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.reports.index') }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-xs flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Tiket Komplain ({{ \App\Models\Report::where('status', 'menunggu')->count() }})</span>
                        </a>
                        <a href="{{ route('admin.cs.index') }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-xs flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span>Tim & Monitor CS</span>
                        </a>
                    </div>
                </div>

                <!-- STATISTIC SUMMARY CARDS (CLEAN ENTERPRISE METRICS, NO AI-SLOP EMOJIS) -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                    <!-- Total Aktivitas -->
                    <div class="bg-white rounded-xl border border-slate-200 p-2.5 lg:p-3 shadow-xs">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Total Log</div>
                        <div class="text-lg lg:text-xl font-bold text-slate-900 leading-tight">{{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-[9px] text-slate-500 font-medium mt-0.5">Keseluruhan rekaman</div>
                    </div>

                    <!-- Hari Ini -->
                    <div class="bg-white rounded-xl border border-slate-200 p-2.5 lg:p-3 shadow-xs">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-700 mb-0.5">Hari Ini (24 Jam)</div>
                        <div class="text-lg lg:text-xl font-bold text-emerald-600 leading-tight">{{ number_format($stats['today'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-[9px] text-slate-500 font-medium mt-0.5">Aktivitas terkini</div>
                    </div>

                    <!-- Pesanan & Pengantaran -->
                    <div class="bg-white rounded-xl border border-slate-200 p-2.5 lg:p-3 shadow-xs">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-blue-700 mb-0.5">Pesanan & Antar</div>
                        <div class="text-lg lg:text-xl font-bold text-blue-600 leading-tight">{{ number_format($stats['orders'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-[9px] text-slate-500 font-medium mt-0.5">Order & pergerakan kurir</div>
                    </div>

                    <!-- Tiket Komplain & Bantuan CS -->
                    <div class="bg-white rounded-xl border border-slate-200 p-2.5 lg:p-3 shadow-xs">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-rose-700 mb-0.5">Tiket & Komplain</div>
                        <div class="text-lg lg:text-xl font-bold text-rose-600 leading-tight">{{ number_format($stats['tickets'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-[9px] text-slate-500 font-medium mt-0.5">Laporan & eskalasi CS</div>
                    </div>

                    <!-- Penyesuaian Harga Toko -->
                    <div class="bg-white rounded-xl border border-slate-200 p-2.5 lg:p-3 shadow-xs">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-amber-700 mb-0.5">Harga Toko</div>
                        <div class="text-lg lg:text-xl font-bold text-amber-600 leading-tight">{{ number_format($stats['adjustments'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-[9px] text-slate-500 font-medium mt-0.5">Penyesuaian & selisih</div>
                    </div>

                    <!-- Top-Up & Keuangan -->
                    <div class="bg-white rounded-xl border border-slate-200 p-2.5 lg:p-3 shadow-xs">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-indigo-700 mb-0.5">Top-Up & Keuangan</div>
                        <div class="text-lg lg:text-xl font-bold text-indigo-600 leading-tight">{{ number_format($stats['finance'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-[9px] text-slate-500 font-medium mt-0.5">Transaksi saldo e-wallet</div>
                    </div>
                </div>

                <!-- CONTROLS & FILTER BAR -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5">
                    <form action="{{ route('admin.activities.index') }}" method="GET" class="space-y-2.5">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2.5">
                            
                            <!-- Pencarian Keyword -->
                            <div class="lg:col-span-2">
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Cari Log</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </span>
                                    <input type="text" name="search" value="{{ $search }}"
                                        placeholder="Cari user, judul, deskripsi, IP..."
                                        class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition">
                                </div>
                            </div>

                            <!-- Filter Peran Pelaku -->
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Peran Pelaku</label>
                                <select name="user_type" class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition">
                                    <option value="all" {{ $userType === 'all' || !$userType ? 'selected' : '' }}>Semua Peran</option>
                                    <option value="customer" {{ $userType === 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="jastiper" {{ $userType === 'jastiper' ? 'selected' : '' }}>Mitra Jastiper</option>
                                    <option value="admin" {{ $userType === 'admin' ? 'selected' : '' }}>Admin CS</option>
                                    <option value="system" {{ $userType === 'system' ? 'selected' : '' }}>Sistem</option>
                                </select>
                            </div>

                            <!-- Filter Kategori Aktivitas -->
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Kategori</label>
                                <select name="category" class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition">
                                    <option value="all" {{ $category === 'all' || !$category ? 'selected' : '' }}>Semua Kategori</option>
                                    <option value="pesanan" {{ $category === 'pesanan' ? 'selected' : '' }}>Pesanan & Deal</option>
                                    <option value="pengantaran" {{ $category === 'pengantaran' ? 'selected' : '' }}>Pengantaran Kurir</option>
                                    <option value="harga_toko" {{ $category === 'harga_toko' ? 'selected' : '' }}>Perubahan Harga Toko</option>
                                    <option value="komplain_tiket" {{ $category === 'komplain_tiket' ? 'selected' : '' }}>Tiket Komplain</option>
                                    <option value="chat_cs" {{ $category === 'chat_cs' ? 'selected' : '' }}>Chat Bantuan CS</option>
                                    <option value="keuangan" {{ $category === 'keuangan' ? 'selected' : '' }}>Keuangan & Saldo</option>
                                </select>
                            </div>

                            <!-- Filter Rentang Waktu -->
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Periode Waktu</label>
                                <select name="date" class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition">
                                    <option value="all" {{ $dateFilter === 'all' || !$dateFilter ? 'selected' : '' }}>Semua Riwayat</option>
                                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Hari Ini</option>
                                    <option value="yesterday" {{ $dateFilter === 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                                    <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                                    <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Baris 2: Order ID & Actions -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-medium text-slate-500">Filter Order ID:</span>
                                <div class="relative w-32">
                                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-slate-400 text-xs font-mono">#</span>
                                    <input type="number" name="order_id" value="{{ $orderId }}" placeholder="12"
                                        class="w-full pl-5 pr-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition">
                                </div>
                                @if($orderId)
                                    <a href="{{ route('admin.activities.index') }}" class="text-[10px] text-rose-600 font-semibold hover:underline">Reset Order ID</a>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 ml-auto">
                                @if($search || ($userType !== 'all' && $userType) || ($category !== 'all' && $category) || $orderId || $dateFilter !== 'all')
                                    <a href="{{ route('admin.activities.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                                        Reset
                                    </a>
                                @endif
                                <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SCROLLABLE ACTIVITY STREAM LOGS: STRUCTURED DATA TABLE -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Tabel Log Audit Aktivitas</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Urutan kronologis seluruh aksi pengguna dan sistem dari yang paling baru.</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full">
                        {{ number_format($activities->total(), 0, ',', '.') }} Log Tercatat
                    </span>
                </div>

                @if($activities->isEmpty())
                    <div class="flex-1 flex items-center justify-center p-8">
                        <div class="text-center space-y-2">
                            <div class="w-10 h-10 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <h4 class="font-bold text-xs text-slate-800">Tidak Ada Log Aktivitas</h4>
                            <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                                Tidak ditemukan rekaman aktivitas yang sesuai dengan filter pencarian saat ini.
                            </p>
                            <a href="{{ route('admin.activities.index') }}" class="inline-block px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition mt-2">
                                Tampilkan Semua Data
                            </a>
                        </div>
                    </div>
                @else
                    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto relative">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200 shadow-2xs">
                                <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-2.5 px-4 w-40 bg-slate-50">Waktu</th>
                                    <th class="py-2.5 px-4 w-48 bg-slate-50">Pelaku</th>
                                    <th class="py-2.5 px-4 w-36 bg-slate-50">Kategori</th>
                                    <th class="py-2.5 px-4 min-w-[280px] bg-slate-50">Aktivitas & Rincian</th>
                                    <th class="py-2.5 px-4 w-28 text-center bg-slate-50">No. Pesanan</th>
                                    <th class="py-2.5 px-4 w-28 text-right bg-slate-50">Opsi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($activities as $act)
                                    @php
                                        $roleBadge = $act->role_badge;
                                        $catMeta = $act->category_meta;
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <!-- Kolom Waktu -->
                                        <td class="py-3.5 px-4 align-top">
                                            <div class="font-mono text-slate-800 text-[11px] font-semibold whitespace-nowrap">
                                                {{ $act->created_at->format('d M Y') }}
                                            </div>
                                            <div class="font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                                {{ $act->created_at->format('H:i:s') }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5 whitespace-nowrap">
                                                {{ $act->created_at->diffForHumans() }}
                                            </div>
                                        </td>

                                        <!-- Kolom Pelaku -->
                                        <td class="py-3.5 px-4 align-top">
                                            <div class="font-semibold text-slate-900 text-xs truncate max-w-[170px]" title="{{ $act->user_name }}">
                                                {{ $act->user_name }}
                                            </div>
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1.5 text-[10px] font-medium px-2 py-0.5 rounded border {{ $roleBadge['bg'] }}">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $roleBadge['dot'] }}"></span>
                                                    <span>{{ $roleBadge['label'] }}</span>
                                                </span>
                                            </div>
                                            @if($act->ip_address)
                                                <div class="text-[10px] font-mono text-slate-400 mt-1">
                                                    {{ $act->ip_address }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Kolom Kategori & Aksi -->
                                        <td class="py-3.5 px-4 align-top">
                                            <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded border {{ $catMeta['color'] }}">
                                                {{ $catMeta['label'] }}
                                            </span>
                                            <div class="text-[10px] font-mono text-slate-400 mt-1 truncate max-w-[130px]" title="{{ $act->action }}">
                                                {{ $act->action }}
                                            </div>
                                        </td>

                                        <!-- Kolom Aktivitas & Rincian Deskripsi -->
                                        <td class="py-3.5 px-4 align-top">
                                            <div class="font-bold text-slate-900 text-xs leading-snug">
                                                {{ $act->title }}
                                            </div>
                                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                                {{ $act->description }}
                                            </p>

                                            <!-- Metadata Properties Pill (Jika Ada Finansial, Selisih, Refund) -->
                                            @if(!empty($act->properties) && is_array($act->properties))
                                                <div class="mt-2 flex flex-wrap gap-1.5 text-[10px]">
                                                    @if(isset($act->properties['topup_amount']) || ($act->category === 'keuangan' && isset($act->properties['amount'])))
                                                        @php $tAmt = $act->properties['topup_amount'] ?? $act->properties['amount']; @endphp
                                                        <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold px-2 py-0.5 rounded">
                                                            Top-Up: +Rp {{ number_format($tAmt, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                    @if(!empty($act->properties['channel']))
                                                        <span class="bg-slate-100 text-slate-700 border border-slate-200 font-semibold px-2 py-0.5 rounded uppercase">
                                                            {{ str_replace('_', ' ', $act->properties['channel']) }}
                                                        </span>
                                                    @endif
                                                    @if(!empty($act->properties['gateway_reference']))
                                                        <span class="bg-slate-50 text-slate-600 border border-slate-200 font-mono px-2 py-0.5 rounded">
                                                            Ref: {{ $act->properties['gateway_reference'] }}
                                                        </span>
                                                    @endif
                                                    @if(isset($act->properties['total_refund']) && $act->properties['total_refund'] > 0)
                                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold px-2 py-0.5 rounded">
                                                            Refund: Rp {{ number_format($act->properties['total_refund'], 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                    @if(isset($act->properties['jastiper_compensation']) && $act->properties['jastiper_compensation'] > 0)
                                                        <span class="bg-blue-50 text-blue-700 border border-blue-200 font-semibold px-2 py-0.5 rounded">
                                                            Ongkir Kurir: Rp {{ number_format($act->properties['jastiper_compensation'], 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                    @if(isset($act->properties['difference']))
                                                        @php $diff = $act->properties['difference']; @endphp
                                                        <span class="bg-amber-50 text-amber-700 border border-amber-200 font-semibold px-2 py-0.5 rounded">
                                                            Selisih: {{ $diff >= 0 ? '+' : '' }}Rp {{ number_format($diff, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                    @if(isset($act->properties['status']))
                                                        <span class="bg-slate-100 text-slate-700 border border-slate-200 font-semibold px-2 py-0.5 rounded">
                                                            Status: {{ $act->properties['status'] }}
                                                        </span>
                                                    @endif
                                                    @if(!empty($act->properties['photo']))
                                                        <a href="{{ Storage::url($act->properties['photo']) }}" target="_blank" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 font-bold px-2 py-0.5 rounded transition shadow-2xs">
                                                            <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                            <span>Lihat {{ ($act->properties['photo_type'] ?? '') === 'delivery_photo' ? 'Foto Tiba' : 'Foto Ambil' }}</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Kolom No. Pesanan / Transaksi -->
                                        <td class="py-3.5 px-4 align-top text-center whitespace-nowrap">
                                            @if($act->order_id)
                                                <button type="button" @click="openOrderTimeline({{ $act->order_id }})"
                                                    class="inline-block font-mono text-xs font-bold text-slate-800 hover:text-rose-600 hover:bg-slate-100 px-2 py-1 rounded border border-slate-200 transition cursor-pointer">
                                                    #{{ $act->order_id }}
                                                </button>
                                            @elseif(!empty($act->properties['topup_id']))
                                                <span class="inline-block font-mono text-[11px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">
                                                    #TOP-{{ $act->properties['topup_id'] }}
                                                </span>
                                            @else
                                                <span class="text-slate-300 font-mono">-</span>
                                            @endif
                                        </td>

                                        <!-- Kolom Opsi -->
                                        <td class="py-3.5 px-4 align-top text-right whitespace-nowrap">
                                            @if($act->order_id)
                                                <button type="button" @click="openOrderTimeline({{ $act->order_id }})"
                                                    class="text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                                    Jejak Detail
                                                </button>
                                            @elseif(!empty($act->properties['gateway_reference']))
                                                <a href="{{ route('admin.mutations.index', ['search' => $act->properties['gateway_reference']]) }}"
                                                    class="inline-block text-xs font-semibold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2.5 py-1 rounded-lg transition">
                                                    Lihat Mutasi
                                                </a>
                                            @else
                                                <span class="text-[11px] text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <div class="p-3 border-t border-slate-100 bg-slate-50/50 shrink-0">
                        {{ $activities->links() }}
                    </div>
                @endif
            </div>

        </main>
    </div>

    <!-- MODAL INVESTIGASI DETAIL JEJAK PESANAN (TABULAR FORMAT) -->
    <div x-show="timelineModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div @click.away="timelineModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-4xl w-full overflow-hidden text-left max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
                <div>
                    <h3 class="font-bold text-sm text-white">
                        Jejak Aktivitas Pesanan #<span x-text="timelineOrder.id || ''"></span>
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5" x-text="timelineOrder.description || ''"></p>
                </div>
                <button type="button" @click="timelineModalOpen = false" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-sm font-semibold transition">
                    ✕
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="loadingTimeline" class="p-12 text-center space-y-2">
                <div class="w-7 h-7 border-2 border-slate-900 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-xs text-slate-500 font-semibold">Memuat log jejak aktivitas pesanan...</p>
            </div>

            <!-- Modal Body (Data Tables) -->
            <div x-show="!loadingTimeline" class="flex-1 overflow-y-auto p-6 space-y-5">
                
                <!-- TABEL 1: RINGKASAN DATA PESANAN -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Informasi Pesanan Terkait</h4>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left text-xs bg-white">
                            <tbody>
                                <tr class="border-b border-slate-100">
                                    <td class="py-2.5 px-3.5 bg-slate-50 font-semibold text-slate-500 w-36">Customer</td>
                                    <td class="py-2.5 px-3.5 text-slate-900">
                                        <span class="font-bold" x-text="timelineOrder.customer_name"></span>
                                        <span class="text-slate-400 ml-1 text-[11px]" x-show="timelineOrder.customer_phone" x-text="'(' + timelineOrder.customer_phone + ')'"></span>
                                    </td>
                                    <td class="py-2.5 px-3.5 bg-slate-50 font-semibold text-slate-500 w-36">Tarif Kurir</td>
                                    <td class="py-2.5 px-3.5 font-mono text-slate-900 font-semibold">
                                        Rp <span x-text="Number(timelineOrder.fare || 0).toLocaleString('id-ID')"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 px-3.5 bg-slate-50 font-semibold text-slate-500">Mitra Jastiper</td>
                                    <td class="py-2.5 px-3.5 text-slate-900">
                                        <span class="font-bold" x-text="timelineOrder.jastiper_name"></span>
                                        <span class="text-slate-400 ml-1 text-[11px]" x-show="timelineOrder.jastiper_phone" x-text="'(' + timelineOrder.jastiper_phone + ')'"></span>
                                    </td>
                                    <td class="py-2.5 px-3.5 bg-slate-50 font-semibold text-slate-500">Status Saat Ini</td>
                                    <td class="py-2.5 px-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide inline-block"
                                              :class="timelineOrder.status === 'selesai' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (timelineOrder.status === 'dibatalkan' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-blue-50 text-blue-700 border border-blue-200')"
                                              x-text="timelineOrder.status"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TABEL 2: KRONOLOGI LOG AKTIVITAS (AUDIT TRAIL URUT KRONOLOGIS) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Kronologi Peristiwa (Audit Trail)</h4>
                        <span class="text-[11px] text-slate-400 font-mono" x-text="timelineEvents.length + ' peristiwa' "></span>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left text-xs bg-white">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-2.5 px-3 w-12 text-center">#</th>
                                    <th class="py-2.5 px-3 w-36">Waktu Kejadian</th>
                                    <th class="py-2.5 px-3 w-36">Pelaku</th>
                                    <th class="py-2.5 px-3 w-28">Kategori</th>
                                    <th class="py-2.5 px-4">Deskripsi Aktivitas & Rincian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(event, idx) in timelineEvents" :key="event.id">
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <!-- No urutan -->
                                        <td class="py-3 px-3 text-center font-mono text-[11px] font-bold text-slate-400 align-top" x-text="idx + 1"></td>
                                        
                                        <!-- Waktu -->
                                        <td class="py-3 px-3 align-top whitespace-nowrap">
                                            <div class="font-mono text-[11px] font-semibold text-slate-800" x-text="event.date"></div>
                                            <div class="font-mono text-[11px] text-slate-500" x-text="event.time"></div>
                                        </td>

                                        <!-- Pelaku -->
                                        <td class="py-3 px-3 align-top">
                                            <div class="font-semibold text-slate-900 text-xs" x-text="event.user_name"></div>
                                            <span class="inline-block text-[9px] font-semibold uppercase px-1.5 py-0.5 rounded mt-0.5"
                                                  :class="event.user_type === 'customer' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (event.user_type === 'jastiper' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (event.user_type === 'admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200'))"
                                                  x-text="event.user_type"></span>
                                        </td>

                                        <!-- Kategori -->
                                        <td class="py-3 px-3 align-top">
                                            <span class="text-[10px] font-semibold text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded inline-block"
                                                  x-text="event.category"></span>
                                            <div class="text-[9px] font-mono text-slate-400 mt-0.5" x-text="event.action"></div>
                                        </td>

                                        <!-- Deskripsi & Rincian -->
                                        <td class="py-3 px-4 align-top">
                                            <div class="font-bold text-slate-900 text-xs" x-text="event.title"></div>
                                            <div class="text-xs text-slate-600 mt-0.5 leading-relaxed" x-text="event.description"></div>

                                            <!-- Metadata Finansial atau Properti -->
                                            <template x-if="event.properties && typeof event.properties === 'object'">
                                                <div class="mt-1.5 flex flex-wrap gap-1.5 text-[10px]">
                                                    <template x-if="event.properties.total_refund > 0">
                                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded font-semibold"
                                                              x-text="'Refund: Rp ' + Number(event.properties.total_refund).toLocaleString('id-ID')"></span>
                                                    </template>
                                                    <template x-if="event.properties.jastiper_compensation > 0">
                                                        <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded font-semibold"
                                                              x-text="'Kompensasi: Rp ' + Number(event.properties.jastiper_compensation).toLocaleString('id-ID')"></span>
                                                    </template>
                                                    <template x-if="event.properties.difference !== undefined">
                                                        <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded font-semibold"
                                                              x-text="'Selisih: Rp ' + Number(event.properties.difference).toLocaleString('id-ID')"></span>
                                                    </template>
                                                    <template x-if="event.properties.photo">
                                                        <a :href="'/storage/' + event.properties.photo" target="_blank" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 px-2 py-0.5 rounded font-bold transition">
                                                            <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                            <span x-text="event.properties.photo_type === 'delivery_photo' ? 'Foto Serah Terima' : 'Foto Ambil Barang'"></span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
                <span class="text-[11px] text-slate-400 font-mono">
                    Audit trail data terekam secara otomatis dan permanen.
                </span>
                <button type="button" @click="timelineModalOpen = false" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg transition cursor-pointer">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    function adminActivityMonitoring() {
        return {
            timelineModalOpen: false,
            loadingTimeline: false,
            timelineOrder: {},
            timelineEvents: [],

            async openOrderTimeline(orderId) {
                this.timelineModalOpen = true;
                this.loadingTimeline = true;
                this.timelineOrder = {};
                this.timelineEvents = [];

                try {
                    const res = await fetch(`/admin/activity-logs/order/${orderId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.timelineOrder = data.order;
                        this.timelineEvents = data.timeline;
                    } else {
                        alert(data.message || 'Gagal memuat timeline pesanan.');
                        this.timelineModalOpen = false;
                    }
                } catch (e) {
                    alert('Terjadi kesalahan memuat data.');
                    this.timelineModalOpen = false;
                } finally {
                    this.loadingTimeline = false;
                }
            }
        };
    }
</script>
@endsection
