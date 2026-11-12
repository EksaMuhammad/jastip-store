@extends('layouts.cs')

@section('title', 'Daftar Tiket Komplain & Sengketa - CS Desk')

@section('content')
<!-- Header Bar -->
<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-20 shadow-xs">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-xs">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <div>
            <h1 class="font-extrabold text-sm text-slate-900">Tiket Pengaduan & Sengketa Pesanan</h1>
            <span class="text-[10px] text-slate-500 block -mt-0.5">Penanganan Keluhan, Verifikasi Bukti, & Keputusan Resolusi</span>
        </div>
    </div>

    <div>
        <a href="{{ route('cs.dashboard') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            ← Kembali ke Dashboard
        </a>
    </div>
</header>

<main class="flex-grow p-4 lg:p-6 space-y-4">

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 4 Stats Cards (Clean Admin Tone) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('cs.reports.index', ['tab' => 'my']) }}" 
           class="p-4 rounded-2xl border transition shadow-xs {{ $tab === 'my' ? 'bg-white border-slate-900 ring-2 ring-slate-900/10' : 'bg-white border-slate-200 hover:border-slate-300' }}">
            <span class="text-[9px] font-black text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full uppercase tracking-wider block w-fit">Tiket Saya (Aktif)</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900">{{ $stats['my_pending'] }}</span>
                <span class="text-xs font-semibold text-slate-500">tiket</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Ditugaskan ke Anda</span>
        </a>

        <a href="{{ route('cs.reports.index', ['tab' => 'queue']) }}" 
           class="p-4 rounded-2xl border transition relative shadow-xs {{ $tab === 'queue' ? 'bg-white border-amber-400 ring-2 ring-amber-400/20' : 'bg-white border-slate-200 hover:border-slate-300' }}">
            <span class="text-[9px] font-black text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full uppercase tracking-wider block w-fit">Antrean Bebas</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900">{{ $stats['unassigned_queue'] }}</span>
                <span class="text-xs font-semibold text-amber-700">belum diklaim</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Zero-neglect queue</span>
            @if($stats['unassigned_queue'] > 0)
                <span class="absolute top-4 right-4 w-2 h-2 rounded-full bg-amber-500"></span>
            @endif
        </a>

        <a href="{{ route('cs.reports.index', ['tab' => 'resolved']) }}" 
           class="p-4 rounded-2xl border transition shadow-xs {{ $tab === 'resolved' ? 'bg-white border-emerald-400 ring-2 ring-emerald-400/20' : 'bg-white border-slate-200 hover:border-slate-300' }}">
            <span class="text-[9px] font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full uppercase tracking-wider block w-fit">Tiket Selesai Saya</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900">{{ $stats['my_resolved'] }}</span>
                <span class="text-xs font-semibold text-emerald-700">selesai</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Telah diputuskan</span>
        </a>

        <a href="{{ route('cs.reports.index', ['tab' => 'all']) }}" 
           class="p-4 rounded-2xl border transition shadow-xs {{ $tab === 'all' ? 'bg-white border-slate-900 ring-2 ring-slate-900/10' : 'bg-white border-slate-200 hover:border-slate-300' }}">
            <span class="text-[9px] font-black text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full uppercase tracking-wider block w-fit">Total Seluruh Tiket</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900">{{ $stats['all_total'] }}</span>
                <span class="text-xs font-semibold text-slate-500">kasus</span>
            </div>
            <span class="text-[10px] text-slate-500 mt-1 block">Arsip sengketa pesanan</span>
        </a>
    </div>

    <!-- Filter Bar & Search -->
    <div class="bg-white p-3.5 lg:p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <!-- Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto">
            <a href="{{ route('cs.reports.index', array_merge(request()->query(), ['tab' => 'my'])) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'my' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                Ditugaskan ke Saya ({{ $stats['my_pending'] }})
            </a>
            <a href="{{ route('cs.reports.index', array_merge(request()->query(), ['tab' => 'queue'])) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'queue' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                Antrean Bebas ({{ $stats['unassigned_queue'] }})
            </a>
            <a href="{{ route('cs.reports.index', array_merge(request()->query(), ['tab' => 'resolved'])) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'resolved' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                Selesai ({{ $stats['my_resolved'] }})
            </a>
            <a href="{{ route('cs.reports.index', array_merge(request()->query(), ['tab' => 'all'])) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'all' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                Semua
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('cs.reports.index') }}" class="flex items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="text" name="search" value="{{ $search }}" 
                   placeholder="Cari ID tiket, order, atau nama..." 
                   class="w-full md:w-64 text-xs rounded-xl px-3 py-1.5 bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                Cari
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-black uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Tiket & Tanggal</th>
                        <th class="py-3 px-4">Order Terkait</th>
                        <th class="py-3 px-4">Pelanggan</th>
                        <th class="py-3 px-4">Jastiper</th>
                        <th class="py-3 px-4">Kategori Komplain</th>
                        <th class="py-3 px-4">Petugas CS</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($reports as $rep)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Tiket ID & Waktu -->
                            <td class="py-3 px-4">
                                <span class="font-extrabold text-slate-900 block">#REP-{{ $rep->id }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $rep->created_at->format('d M Y, H:i') }}</span>
                            </td>

                            <!-- Order ID -->
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">#Order {{ $rep->order_id }}</span>
                                <span class="text-[10px] text-slate-500 truncate block max-w-xs">{{ $rep->order?->description }}</span>
                            </td>

                            <!-- Pelanggan -->
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $rep->order?->customer?->name ?? '-' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $rep->order?->customer?->phone_number ?? '-' }}</span>
                            </td>

                            <!-- Jastiper -->
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $rep->order?->jastiper?->name ?? '-' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $rep->order?->jastiper?->phone_number ?? '-' }}</span>
                            </td>

                            <!-- Kategori Komplain -->
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ str_replace('_', ' ', $rep->category) }}
                                </span>
                                <p class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">
                                    {{ $rep->description }}
                                </p>
                            </td>

                            <!-- Petugas CS -->
                            <td class="py-3 px-4">
                                @if($rep->assignedCs)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold {{ $rep->assigned_cs_id === $cs->id ? 'text-slate-900' : 'text-slate-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                                        {{ $rep->assignedCs->name }}
                                        @if($rep->assigned_cs_id === $cs->id)
                                            <span class="text-[9px] bg-slate-100 text-slate-700 border border-slate-200 px-1 py-0.2 rounded font-semibold">(Anda)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Belum Diklaim
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 text-center">
                                @if($rep->status === 'menunggu')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Investigasi
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Selesai
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($rep->status === 'menunggu' && (!$rep->assigned_cs_id || $rep->cs_claim_status === 'unassigned'))
                                        <form action="{{ route('cs.reports.claim', $rep->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] transition shadow-xs cursor-pointer">
                                                Klaim Tiket
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('cs.reports.show', $rep->id) }}" 
                                       class="px-3 py-1 rounded-lg bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-bold text-[11px] transition shadow-2xs">
                                        Detail →
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <div class="w-10 h-10 mx-auto rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-700">Tidak ada pengaduan ditemukan</p>
                                <p class="text-[11px] text-slate-400">Seluruh komplain sudah tertangani dengan baik.</p>
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
@endsection
