@extends('layouts.support')

@section('title', 'Master Data Mutasi Keseluruhan - Admin Portal')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen lg:h-screen lg:overflow-hidden bg-[#F3F4F6]">
    
    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0 lg:h-screen lg:overflow-hidden">
        
        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 shrink-0 z-40 shadow-xs">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-500 hover:text-slate-800 text-xs font-bold flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Dashboard Utama
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-black text-slate-800">Master Data Mutasi</span>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Audit Ledger Aktif
                </span>
            </div>
        </header>

        <!-- Main Body Content (Flex column with fixed top section and scrollable table) -->
        <main class="flex-1 flex flex-col min-h-0 p-4 lg:p-6 gap-3.5 overflow-hidden">

            <!-- FROZEN TOP SECTION: Title, Filter & KPI Cards -->
            <div class="shrink-0 space-y-3">
                <!-- Title & Subtitle Banner -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </span>
                            Master Data Mutasi Keseluruhan
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Transparansi penuh seluruh riwayat mutasi kredit (uang masuk) & debit (uang keluar) pengguna serta platform.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.dashboard') }}#master-mutasi" class="px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Lihat di Dashboard
                        </a>
                    </div>
                </div>

                <!-- CONTROLS: FILTER PERIODE HARI & PENCARIAN (FROZEN) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-3.5 lg:p-4">
                    <form action="{{ route('admin.mutations.index') }}" method="GET" class="space-y-3">
                        <!-- Single Hidden Days Input for Mutation Filter Form -->
                        <input type="hidden" name="days" id="filter_days_input" value="{{ $days }}">
                        
                        <!-- Top Row: Quick Day Range Selector -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 mb-2">
                                <label class="text-[11px] font-black text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Pilih Rentang Hari Mutasi:
                                </label>
                                <span class="text-xs font-semibold text-slate-500">
                                    Sedang Aktif: <strong class="text-slate-900">{{ $daysLabel }}</strong> ({{ number_format($totalCount, 0, ',', '.') }} baris data)
                                </span>
                            </div>

                            <!-- Quick Selection Pills -->
                            <div class="flex flex-wrap items-center gap-1.5 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                                @foreach([
                                    '1' => 'Hari Ini (1 Hari)',
                                    '3' => '3 Hari',
                                    '7' => '7 Hari',
                                    '14' => '14 Hari',
                                    '30' => '30 Hari (1 Bulan)',
                                    '60' => '60 Hari',
                                    '90' => '90 Hari',
                                    'all' => 'Semua Riwayat'
                                ] as $dayVal => $dayTitle)
                                    <button type="button" 
                                        onclick="document.getElementById('filter_days_input').value='{{ $dayVal }}'; this.closest('form').submit();"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $days === (string)$dayVal ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-100' }}">
                                        {{ $dayTitle }}
                                    </button>
                                @endforeach

                                <!-- Custom Numeric Days Input -->
                                <div class="flex items-center gap-1 ml-auto pt-1 sm:pt-0">
                                    <span class="text-[11px] font-bold text-slate-500">Kustom:</span>
                                    <input type="number" id="custom_days_val" min="1" max="365" value="{{ is_numeric($days) && !in_array($days, ['1','3','7','14','30','60','90']) ? $days : '' }}" 
                                        placeholder="Hari" 
                                        onkeydown="if(event.key === 'Enter') { event.preventDefault(); const v = this.value; if(v && v > 0){ document.getElementById('filter_days_input').value = v; this.closest('form').submit(); } }"
                                        class="w-16 px-2 py-0.5 text-xs font-bold text-center border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                    <span class="text-[11px] font-bold text-slate-500">Hari</span>
                                    <button type="button" 
                                        onclick="const v = document.getElementById('custom_days_val').value; if(v && v > 0) { document.getElementById('filter_days_input').value = v; this.closest('form').submit(); }"
                                        class="px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition cursor-pointer">
                                        Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Row: Filter Dropdowns & Search Input -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 pt-2 border-t border-slate-100">
                            
                            <!-- Filter Tipe / Arus Kas -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Arus Mutasi</label>
                                <select name="type" onchange="this.form.submit()" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-2.5 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua Arus (Kredit & Debit)</option>
                                    <option value="kredit" {{ $type === 'kredit' ? 'selected' : '' }}>Kredit / Uang Masuk (+)</option>
                                    <option value="debit" {{ $type === 'debit' ? 'selected' : '' }}>Debit / Uang Keluar (-)</option>
                                </select>
                            </div>

                            <!-- Filter Role Akun -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Entitas / Role</label>
                                <select name="role" onchange="this.form.submit()" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-2.5 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                    <option value="all" {{ $role === 'all' ? 'selected' : '' }}>Semua Pengguna</option>
                                    <option value="customer" {{ $role === 'customer' ? 'selected' : '' }}>Pelanggan (Customer)</option>
                                    <option value="jastiper" {{ $role === 'jastiper' ? 'selected' : '' }}>Mitra Jastiper</option>
                                </select>
                            </div>

                            <!-- Filter Sumber / Kategori -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Sumber Transaksi</label>
                                <select name="source" onchange="this.form.submit()" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-2.5 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                    <option value="all" {{ $source === 'all' ? 'selected' : '' }}>Semua Kategori Sumber</option>
                                    @foreach($availableSources as $sKey => $sLabel)
                                        <option value="{{ $sKey }}" {{ $source === $sKey ? 'selected' : '' }}>{{ $sLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Pencarian Kata Kunci -->
                            <div class="lg:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Cari Mutasi (Nama, HP, Deskripsi, ID)</label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-grow">
                                        <input type="text" name="search" value="{{ $search }}" placeholder="Ketik kata kunci..." 
                                            class="w-full text-xs font-medium border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-xs shrink-0">
                                        Filter
                                    </button>
                                    @if($search || $type !== 'all' || $role !== 'all' || $source !== 'all' || $days !== '7')
                                        <a href="{{ route('admin.mutations.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition shrink-0" title="Reset Filter">
                                            Reset
                                        </a>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- KPI SUMMARY CARDS (PERIODE TERPILIH - FROZEN) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    
                    <!-- Card 1: Total Kredit / Masuk -->
                    <div class="bg-white rounded-2xl border border-emerald-100 p-3.5 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                + UANG MASUK (KREDIT)
                            </span>
                            <h3 class="text-lg font-black text-slate-900 mt-1.5">
                                Rp {{ number_format($totalKredit, 0, ',', '.') }}
                            </h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Top-up & pendapatan pesanan masuk</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-base border border-emerald-100 shadow-inner">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        </div>
                    </div>

                    <!-- Card 2: Total Debit / Keluar -->
                    <div class="bg-white rounded-2xl border border-rose-100 p-3.5 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                                - UANG KELUAR (DEBIT)
                            </span>
                            <h3 class="text-lg font-black text-slate-900 mt-1.5">
                                Rp {{ number_format($totalDebit, 0, ',', '.') }}
                            </h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Withdraw, bayar order & potongan komisi</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-base border border-rose-100 shadow-inner">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        </div>
                    </div>

                    <!-- Card 3: Net Mutasi Periode -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3.5 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                                SELISIH ARUS KAS
                            </span>
                            <h3 class="text-lg font-black {{ $netMutasi >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1.5">
                                {{ $netMutasi >= 0 ? '+' : '' }}Rp {{ number_format($netMutasi, 0, ',', '.') }}
                            </h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Net posisi kas pada periode ini</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-base border border-indigo-100 shadow-inner">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                        </div>
                    </div>

                    <!-- Card 4: Total Frekuensi Mutasi -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-3.5 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                                FREKUENSI MUTASI
                            </span>
                            <h3 class="text-lg font-black text-slate-900 mt-1.5">
                                {{ number_format($totalCount, 0, ',', '.') }} <span class="text-xs font-bold text-slate-400">transaksi</span>
                            </h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Rentang: {{ $daysLabel }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-black text-base border border-amber-100 shadow-inner">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TABEL MASTER DATA MUTASI (SCROLLABLE CONTAINER) -->
            <div class="flex-1 flex flex-col min-h-0 bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 shrink-0">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <span>Daftar Mutasi Keuangan</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-200/80 text-slate-700 font-bold">
                                {{ $mutations->total() }} Data
                            </span>
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Semua entri debit dan kredit tercatat secara runtut berdasarkan waktu.</p>
                    </div>

                    <!-- Per Page Selector -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-bold">Baris per halaman:</span>
                        <form action="{{ route('admin.mutations.index') }}" method="GET" class="inline">
                            <input type="hidden" name="days" value="{{ $days }}">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <input type="hidden" name="role" value="{{ $role }}">
                            <input type="hidden" name="source" value="{{ $source }}">
                            <input type="hidden" name="search" value="{{ $search }}">
                            <select name="per_page" onchange="this.form.submit()" class="text-xs font-bold border border-slate-200 rounded-lg px-2.5 py-1 bg-white focus:outline-none">
                                @foreach([10, 15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" {{ $perPage === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Scrollable Table Body -->
                <div class="flex-1 min-h-0 overflow-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200 sticky top-0 z-10 shadow-2xs">
                            <tr>
                                <th class="py-3 px-4 bg-slate-50">No / ID & Waktu</th>
                                <th class="py-3 px-4 bg-slate-50">Pemilik Akun & Kontak</th>
                                <th class="py-3 px-4 bg-slate-50">Arus Mutasi</th>
                                <th class="py-3 px-4 bg-slate-50">Kategori / Sumber</th>
                                <th class="py-3 px-4 bg-slate-50 text-right">Nominal (Rp)</th>
                                <th class="py-3 px-4 bg-slate-50">Keterangan / Deskripsi</th>
                                <th class="py-3 px-4 bg-slate-50 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700 bg-white">
                            @forelse($mutations as $mut)
                            @php
                                $owner = $mut->wallet?->owner;
                                $ownerRole = $mut->wallet?->owner_role ?? 'unknown';
                                $isKredit = $mut->type === 'kredit';
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                
                                <!-- ID & Waktu -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                                            #MUT-{{ str_pad($mut->id, 5, '0', STR_PAD_LEFT) }}
                                        </span>
                                    </div>
                                    <span class="text-slate-600 block mt-1 font-semibold text-[11px]">
                                        {{ $mut->created_at->format('d M Y, H:i') }} WIB
                                    </span>
                                    <span class="text-[10px] text-slate-400 block">
                                        {{ $mut->created_at->diffForHumans() }}
                                    </span>
                                </td>

                                <!-- Pemilik Akun & Kontak -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 {{ $ownerRole === 'customer' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ strtoupper(substr($owner->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <h4 class="font-bold text-slate-900 truncate max-w-[150px]">
                                                    {{ $owner->name ?? 'Pengguna #' . ($mut->wallet?->owner_id ?? '-') }}
                                                </h4>
                                                @if($ownerRole === 'customer')
                                                    <span class="text-[9px] font-black px-1.5 py-0.2 rounded bg-sky-50 text-sky-700 border border-sky-200">
                                                        Customer
                                                    </span>
                                                @elseif($ownerRole === 'jastiper')
                                                    <span class="text-[9px] font-black px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Jastiper
                                                    </span>
                                                @else
                                                    <span class="text-[9px] font-black px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                                                        {{ ucfirst($ownerRole) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-[11px] text-slate-500 font-mono block">
                                                {{ $owner->phone_number ?? '-' }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 block mt-0.5">
                                                Saldo Dompet: <strong>Rp {{ number_format($mut->wallet?->balance ?? 0, 0, ',', '.') }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Arus Mutasi (Kredit/Debit) -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if($isKredit)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                            + KREDIT (Masuk)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                            - DEBIT (Keluar)
                                        </span>
                                    @endif
                                </td>

                                <!-- Kategori & Referensi -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @php
                                        $sourceBadgeColors = [
                                            'topup' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'order_completion' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'item_reimbursement' => 'bg-teal-50 text-teal-700 border-teal-200',
                                            'pembayaran_order' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'withdraw' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'refund' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'komisi' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        ];
                                        $badgeColor = $sourceBadgeColors[$mut->source] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        $sourceName = $availableSources[$mut->source] ?? ucfirst(str_replace('_', ' ', $mut->source));
                                    @endphp
                                    <span class="inline-block text-[11px] font-bold px-2 py-0.5 rounded-lg border {{ $badgeColor }}">
                                        {{ $sourceName }}
                                    </span>
                                    @if($mut->reference_order_id)
                                        <span class="block mt-1 text-[10px] font-mono font-bold text-slate-500">
                                            Ref: Order #{{ $mut->reference_order_id }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Nominal (Rp) -->
                                <td class="py-4 px-4 text-right whitespace-nowrap font-mono">
                                    <span class="text-sm font-black {{ $isKredit ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $isKredit ? '+' : '-' }}Rp {{ number_format($mut->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                <!-- Keterangan / Deskripsi -->
                                <td class="py-4 px-4 max-w-xs">
                                    <p class="text-xs text-slate-700 leading-snug line-clamp-2" title="{{ $mut->description }}">
                                        {{ $mut->description ?: 'Mutasi transaksi sistem.' }}
                                    </p>
                                </td>

                                <!-- Aksi / Detail Modal -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <button type="button" 
                                        onclick="showMutationDetail({{ json_encode([
                                            'id' => '#MUT-' . str_pad($mut->id, 5, '0', STR_PAD_LEFT),
                                            'waktu' => $mut->created_at->format('d M Y, H:i:s') . ' WIB (' . $mut->created_at->diffForHumans() . ')',
                                            'pemilik' => ($owner->name ?? 'User #' . ($mut->wallet?->owner_id ?? '-')),
                                            'role' => ucfirst($ownerRole),
                                            'phone' => ($owner->phone_number ?? '-'),
                                            'tipe' => $isKredit ? 'KREDIT (Uang Masuk)' : 'DEBIT (Uang Keluar)',
                                            'sumber' => $sourceName,
                                            'nominal' => ($isKredit ? '+' : '-') . 'Rp ' . number_format($mut->amount, 0, ',', '.'),
                                            'saldo_terkini' => 'Rp ' . number_format($mut->wallet?->balance ?? 0, 0, ',', '.'),
                                            'order_ref' => $mut->reference_order_id ? ('Order #' . $mut->reference_order_id) : '-',
                                            'deskripsi' => $mut->description ?: '-',
                                        ]) }})"
                                        class="px-2.5 py-1 text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Detail
                                    </button>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Mutasi Ditemukan</h4>
                                    <p class="text-xs text-slate-400 mt-1">Tidak ada data transaksi yang cocok dengan filter "{{ $daysLabel }}". Coba pilih rentang hari yang lebih panjang.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (Docked at bottom of table card) -->
                @if($mutations->hasPages())
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 shrink-0">
                    {{ $mutations->links() }}
                </div>
                @endif
            </div>

        </main>
        
    </div>

</div>

<!-- MODAL DETAIL MUTASI -->
<div id="mutation-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 transform transition-all animate-fade-in">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <div>
                    <h3 class="font-black text-sm text-slate-900">Rincian Lengkap Mutasi</h3>
                    <span id="modal-id" class="text-xs font-mono font-bold text-slate-500 block"></span>
                </div>
            </div>
            <button onclick="closeMutationDetail()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                &times;
            </button>
        </div>

        <div class="py-4 space-y-3 text-xs">
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Waktu Transaksi</span>
                <span id="modal-waktu" class="text-slate-800 font-semibold text-right"></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Nama Pemilik Akun</span>
                <span id="modal-pemilik" class="text-slate-800 font-bold"></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Peran (Role) & Kontak</span>
                <span id="modal-role-contact" class="text-slate-800 font-semibold text-right"></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Arus Mutasi</span>
                <span id="modal-tipe" class="font-black"></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Kategori Sumber</span>
                <span id="modal-sumber" class="text-slate-800 font-bold"></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Referensi Order</span>
                <span id="modal-order-ref" class="text-slate-800 font-mono font-bold"></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Nominal Transaksi</span>
                <span id="modal-nominal" class="text-sm font-black font-mono"></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-500 font-bold">Saldo Terkini Akun</span>
                <span id="modal-saldo" class="text-slate-800 font-bold font-mono"></span>
            </div>
            <div class="pt-2">
                <span class="text-slate-500 font-bold block mb-1">Catatan / Deskripsi Sistem:</span>
                <div id="modal-deskripsi" class="p-3 bg-slate-50 rounded-xl text-slate-700 leading-relaxed border border-slate-200"></div>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end">
            <button onclick="closeMutationDetail()" class="px-5 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function showMutationDetail(data) {
        document.getElementById('modal-id').innerText = data.id;
        document.getElementById('modal-waktu').innerText = data.waktu;
        document.getElementById('modal-pemilik').innerText = data.pemilik;
        document.getElementById('modal-role-contact').innerText = data.role + ' • ' + data.phone;
        document.getElementById('modal-tipe').innerText = data.tipe;
        document.getElementById('modal-tipe').className = data.tipe.includes('KREDIT') ? 'text-emerald-600 font-black' : 'text-rose-600 font-black';
        document.getElementById('modal-sumber').innerText = data.sumber;
        document.getElementById('modal-order-ref').innerText = data.order_ref;
        document.getElementById('modal-nominal').innerText = data.nominal;
        document.getElementById('modal-nominal').className = data.nominal.startsWith('+') ? 'text-emerald-600 font-black text-sm' : 'text-rose-600 font-black text-sm';
        document.getElementById('modal-saldo').innerText = data.saldo_terkini;
        document.getElementById('modal-deskripsi').innerText = data.deskripsi;
        
        document.getElementById('mutation-modal').classList.remove('hidden');
    }

    function closeMutationDetail() {
        document.getElementById('mutation-modal').classList.add('hidden');
    }
</script>
@endsection
