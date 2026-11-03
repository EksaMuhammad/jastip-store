@extends('layouts.support')

@section('title', 'Panel Admin - Pengaduan & Komplain Pesanan')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]">

    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Dispute Resolution Center</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-900 rounded-full flex items-center justify-center font-bold text-white text-xs">AD</div>
                <span class="text-xs font-semibold text-slate-700 hidden sm:inline">Admin JastipKuy</span>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow p-6 lg:p-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Page Title & Header -->
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="font-bold text-xl text-slate-900 tracking-tight">Pengaduan & Komplain Pesanan</h1>
                    <p class="text-xs text-slate-500 mt-1">Investigasi sengketa barang, ketidaksesuaian pesanan, dan eksekusi pengembalian dana.</p>
                </div>

                <!-- Tabs Status -->
                <div class="flex bg-white border border-slate-200 rounded-lg p-1 shadow-xs">
                    <a href="{{ route('admin.reports.index', array_merge(request()->except('page'), ['status' => 'menunggu'])) }}"
                       class="text-[11px] font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-md transition {{ $status === 'menunggu' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}">
                        Menunggu ({{ $stats['pending'] }})
                    </a>
                    <a href="{{ route('admin.reports.index', array_merge(request()->except('page'), ['status' => 'selesai'])) }}"
                       class="text-[11px] font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-md transition {{ $status === 'selesai' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}">
                        Selesai ({{ $stats['resolved'] }})
                    </a>
                    <a href="{{ route('admin.reports.index', array_merge(request()->except('page'), ['status' => 'all'])) }}"
                       class="text-[11px] font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-md transition {{ $status === 'all' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}">
                        Semua ({{ $stats['total'] }})
                    </a>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Pengaduan</span>
                        <span class="text-xl font-bold text-slate-900">{{ number_format($stats['total'], 0, ',', '.') }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 block">Menunggu Investigasi</span>
                        <span class="text-xl font-bold text-amber-600">{{ number_format($stats['pending'], 0, ',', '.') }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Kasus Selesai</span>
                        <span class="text-xl font-bold text-emerald-600">{{ number_format($stats['resolved'], 0, ',', '.') }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Total Refund Dana</span>
                        <span class="text-lg font-bold text-slate-900">Rp {{ number_format($stats['total_refund'], 0, ',', '.') }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Filters & Search Form -->
            <form action="{{ route('admin.reports.index') }}" method="GET" class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <input type="hidden" name="status" value="{{ $status }}">

                <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[220px]">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari ID pengaduan, order, atau nama..."
                               class="w-full text-xs rounded-xl pl-9 pr-3 py-2 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
                    </div>

                    <!-- Category Filter -->
                    <select name="category" onchange="this.form.submit()" class="text-xs rounded-xl px-3 py-2 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 bg-white">
                        <option value="all">Semua Kategori</option>
                        <option value="barang_tidak_sesuai" {{ $category === 'barang_tidak_sesuai' ? 'selected' : '' }}>Barang Tidak Sesuai</option>
                        <option value="barang_rusak" {{ $category === 'barang_rusak' ? 'selected' : '' }}>Barang Rusak / Basi</option>
                        <option value="barang_kurang" {{ $category === 'barang_kurang' ? 'selected' : '' }}>Pesanan Kurang</option>
                        <option value="selisih_harga_ongkir" {{ $category === 'selisih_harga_ongkir' ? 'selected' : '' }}>Selisih Harga / Biaya</option>
                        <option value="lainnya" {{ $category === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition">
                        Terapkan
                    </button>
                    @if($search || ($category && $category !== 'all'))
                        <a href="{{ route('admin.reports.index', ['status' => $status]) }}" class="text-slate-500 hover:text-slate-800 text-xs font-semibold px-2 py-2">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <!-- Table Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase tracking-wider font-extrabold text-slate-500">
                                <th class="py-3.5 px-4">Pengaduan</th>
                                <th class="py-3.5 px-4">Pesanan</th>
                                <th class="py-3.5 px-4">Customer & Jastiper</th>
                                <th class="py-3.5 px-4">Kategori & Masalah</th>
                                <th class="py-3.5 px-4">Status & Putusan</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($reports as $rep)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <!-- Pengaduan ID & Date -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-md text-[11px]">
                                                 #REP-{{ $rep->id }}
                                            </span>
                                            @if($rep->evidence_image)
                                                <span title="Ada Bukti Foto" class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">Foto</span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-400 block mt-1">
                                            {{ $rep->created_at->format('d M Y, H:i') }} WIB
                                        </span>
                                    </td>

                                    <!-- Pesanan Details -->
                                    <td class="py-4 px-4 min-w-[200px]">
                                        <div class="font-bold text-slate-900 truncate max-w-[220px]">
                                            Order #{{ $rep->order_id }}: {{ $rep->order->description ?? '-' }}
                                        </div>
                                        <div class="text-[11px] font-semibold text-slate-600 mt-0.5 font-mono">
                                            Rp {{ number_format($rep->order->agreed_fare ?? 0, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    <!-- Customer & Jastiper -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">Cust</span>
                                                <span class="font-bold text-slate-800">{{ $rep->order->customer->name ?? 'Customer' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">Jast</span>
                                                <span class="text-slate-600">{{ $rep->order->jastiper->name ?? 'Belum ada' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kategori & Masalah -->
                                    <td class="py-4 px-4 min-w-[220px]">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wider mb-1">
                                            {{ str_replace('_', ' ', $rep->category) }}
                                        </span>
                                        <p class="text-slate-600 text-[11px] line-clamp-2 leading-relaxed">
                                            {{ $rep->description }}
                                        </p>
                                    </td>

                                    <!-- Status & Putusan -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        @if($rep->status === 'menunggu')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Menunggu Investigasi
                                            </span>
                                        @else
                                            <div class="space-y-0.5">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                    Kasus Selesai
                                                </span>
                                                <div class="text-[10px] text-slate-500 font-medium">
                                                    @if($rep->resolution_type === 'refund_full')
                                                        <span class="text-slate-800 font-semibold">Refund Penuh (Rp {{ number_format($rep->refund_amount, 0, ',', '.') }})</span>
                                                    @elseif($rep->resolution_type === 'refund_partial')
                                                        <span class="text-slate-800 font-semibold">Refund Sebagian (Rp {{ number_format($rep->refund_amount, 0, ',', '.') }})</span>
                                                    @else
                                                        <span class="text-slate-600">Komplain Ditolak</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.reports.show', $rep->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold text-xs transition {{ $rep->status === 'menunggu' ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                                            <span>{{ $rep->status === 'menunggu' ? 'Investigasi' : 'Rincian' }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        <div class="w-12 h-12 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="font-bold text-sm text-slate-700">Tidak ada pengaduan pesanan</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Semua pesanan berjalan lancar tanpa sengketa yang perlu ditindaklanjuti.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reports->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $reports->links() }}
                    </div>
                @endif
            </div>

        </main>
    </div>
</div>
@endsection
