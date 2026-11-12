@extends('layouts.support')

@section('title', 'Riwayat Pesanan Jastiper')

@section('content')
<div class="min-h-screen bg-[#F8FAFC]">

    {{-- ===== HEADER ===== --}}
    <div class="relative bg-rose-600 pt-5 pb-20 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full"></div>
        <div class="absolute top-10 -right-4 w-28 h-28 bg-white/10 rounded-full"></div>
        <div class="absolute -bottom-6 left-1/3 w-24 h-24 bg-white/10 rounded-full"></div>

        <div class="relative z-10 max-w-lg mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('jastiper.dashboard') }}" class="text-white/80 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="font-display font-black text-xl text-white tracking-tight">Riwayat Pesanan</h1>
                    <p class="text-[11px] text-white/80 font-medium">Rekap seluruh pesanan jastip yang telah selesai & ditangani</p>
                </div>
            </div>
            <span class="text-2xl">📋</span>
        </div>
    </div>

    {{-- ===== MAIN CONTAINER ===== --}}
    <div class="max-w-lg mx-auto px-4 -mt-14 pb-28 space-y-4 relative z-10">

        {{-- ===== STATISTIK RINGKAS ===== --}}
        <div class="bg-white rounded-2xl shadow-[0_8px_30px_-4px_rgba(0,0,0,0.08)] border border-slate-100 p-4">
            <div class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-100">
                <div class="px-1">
                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Selesai</span>
                    <span class="text-lg font-black text-emerald-600 font-display mt-0.5 block">{{ $totalCompleted }}</span>
                </div>
                <div class="px-1">
                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Komplain/Batal</span>
                    <span class="text-lg font-black text-rose-600 font-display mt-0.5 block">{{ $totalCancelled }}</span>
                </div>
                <div class="px-1">
                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Total Ongkir</span>
                    <span class="text-xs font-black text-slate-900 font-display mt-1 block truncate">
                        Rp {{ number_format($totalEarned, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ===== FILTER TABS ===== --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
            <a href="{{ route('jastiper.history', ['tab' => 'semua']) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'semua' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                Semua
            </a>
            <a href="{{ route('jastiper.history', ['tab' => 'selesai']) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'selesai' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                ✓ Selesai ({{ $totalCompleted }})
            </a>
            <a href="{{ route('jastiper.history', ['tab' => 'batal']) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'batal' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                ⚠️ Komplain / Batal ({{ $totalCancelled }})
            </a>
        </div>

        {{-- ===== DAFTAR PESANAN ===== --}}
        @if($orders->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-xs">
                <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📋</div>
                <h4 class="font-bold text-slate-800 text-sm">Belum Ada Riwayat</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto leading-normal">
                    @if($tab === 'selesai')
                        Belum ada pesanan berstatus selesai.
                    @elseif($tab === 'batal')
                        Bagus! Tidak ada pesanan yang dibatalkan atau bermasalah.
                    @else
                        Pesanan yang telah Anda selesaikan akan tercatat otomatis di sini.
                    @endif
                </p>
                <a href="{{ route('jastiper.dashboard') }}" class="inline-block mt-4 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold rounded-xl shadow-xs transition">
                    Kembali ke Beranda
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($orders as $order)
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs space-y-3 transition hover:border-slate-300">
                        
                        {{-- Header Kartu Pesanan --}}
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-[11px] font-black text-slate-800">#Order {{ $order->id }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">• {{ $order->updated_at->format('d M Y, H:i') }}</span>
                            </div>

                            @if($order->status === 'selesai')
                                <span class="text-[9px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    ✓ Selesai
                                </span>
                            @elseif($order->status === 'bermasalah')
                                <span class="text-[9px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                    ⚠️ Dalam Komplain
                                </span>
                            @else
                                <span class="text-[9px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200">
                                    ✕ Dibatalkan
                                </span>
                            @endif
                        </div>

                        {{-- Info Customer & Merchant --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-extrabold text-xs text-slate-900 line-clamp-1">
                                    {{ $order->merchant->name ?? ($order->store_name ?: 'Pesanan Jastip') }}
                                </h4>
                                <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                    {{ $order->description }}
                                </p>
                                <div class="flex items-center gap-2 mt-1.5 text-[10px] text-slate-500 font-medium">
                                    <span class="flex items-center gap-1">
                                        <span>👤</span> {{ $order->customer->name ?? 'Customer' }}
                                    </span>
                                    <span>•</span>
                                    <span class="flex items-center gap-1">
                                        <span>📍</span> {{ $order->destination_address ? Str::limit($order->destination_address, 25) : 'Alamat Tujuan' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Nominal Fare --}}
                            <div class="text-right shrink-0">
                                <span class="text-[10px] font-bold text-slate-400 block">Ongkir Diterima</span>
                                <span class="text-sm font-black {{ $order->status === 'selesai' ? 'text-emerald-600' : 'text-slate-400 line-through' }}">
                                    Rp {{ number_format($order->agreed_fare ?? $order->estimated_fare, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        {{-- Hasil Komplain / CS Decision jika ada --}}
                        @if($order->reports && $order->reports->isNotEmpty())
                            @php
                                $latestReport = $order->reports->sortByDesc('id')->first();
                            @endphp
                            <div class="bg-amber-50/90 border border-amber-200 rounded-xl p-2.5 space-y-1 text-left">
                                <div class="flex items-center justify-between text-[10px] font-extrabold text-amber-900">
                                    <span class="flex items-center gap-1">
                                        <span>⚖️</span> Catatan Sengketa & Investigasi CS:
                                    </span>
                                    <span class="text-[9px] uppercase px-1.5 py-0.2 rounded bg-amber-200/80 text-amber-900">
                                        {{ $latestReport->status }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-amber-800 leading-snug">
                                    <strong>Keputusan:</strong> {{ $latestReport->admin_decision ?: ($latestReport->admin_notes ?: 'Sengketa telah diselesaikan sesuai protokol pengaduan.') }}
                                </p>
                            </div>
                        @endif

                    </div>
                @endforeach

                {{-- Pagination Links --}}
                <div class="pt-2">
                    {{ $orders->links() }}
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
