@extends('layouts.merchant')

@section('title', 'Dashboard Toko')

@section('content')
<div class="space-y-6">

    <!-- Hero Banner Toko -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-lg">
        <div class="absolute right-0 top-0 bottom-0 w-1/3 bg-gradient-to-l from-rose-500/10 to-transparent pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                @if($merchant->image)
                    <img src="{{ $merchant->image }}" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover bg-white/10 border-2 border-white/20 shadow-md">
                @else
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white/10 border-2 border-white/20 flex items-center justify-center font-black text-2xl text-white shadow-md">
                        {{ strtoupper(substr($merchant->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-black">{{ $merchant->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full {{ $merchant->is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }} text-[10px] font-bold uppercase tracking-wider">
                            {{ $merchant->is_active ? 'Buka / Aktif' : 'Tutup' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-300 mt-1 max-w-xl">{{ $merchant->address }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Bidang: <b>{{ $merchant->type === 'food' ? 'Kuliner / Makanan & Minuman' : 'Minimarket / Kebutuhan Harian' }}</b></p>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <a href="{{ route('merchant.products.create') }}" class="flex-1 md:flex-none px-5 py-3 rounded-2xl bg-gradient-to-r from-rose-600 to-orange-500 hover:from-rose-500 hover:to-orange-400 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-rose-600/30 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    <span>+ Tambah Produk Baru</span>
                </a>
                <a href="{{ route('customer.merchant.show', $merchant->id) }}" target="_blank" class="px-4 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition flex items-center gap-1.5" title="Lihat di Katalog Customer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <span class="hidden sm:inline">Pratinjau Katalog</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Total Produk</p>
                <p class="text-2xl font-black text-slate-800 mt-1">{{ $totalProducts }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Semua item terdaftar</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-700 text-xl">
                📦
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-emerald-600 font-bold uppercase tracking-wider">Produk Ready (Aktif)</p>
                <p class="text-2xl font-black text-emerald-600 mt-1">{{ $availableProducts }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Tampil di katalog customer</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">
                ✓
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-rose-500 font-bold uppercase tracking-wider">Stok Habis</p>
                <p class="text-2xl font-black text-rose-600 mt-1">{{ $emptyProducts }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Sementara tidak dapat dipesan</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-500 text-xl">
                ✕
            </div>
        </div>
    </div>

    <!-- Produk Terbaru Toko -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display font-black text-lg text-slate-800">Daftar Produk Terbaru</h2>
                <p class="text-xs text-slate-400">Produk yang Anda tambahkan otomatis muncul di pilihan toko customer.</p>
            </div>
            <a href="{{ route('merchant.products.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                <span>Lihat Semua Produk</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
            @forelse($products as $p)
                <div class="border border-slate-200 rounded-2xl p-4 flex gap-3 hover:border-slate-300 transition bg-slate-50/50">
                    <div class="w-20 h-20 rounded-xl bg-slate-200 overflow-hidden shrink-0 border border-slate-200">
                        @if($p->image)
                            <img src="{{ $p->image }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs font-bold">
                                No Foto
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-bold text-sm text-slate-800 line-clamp-1">{{ $p->name }}</h3>
                                <form action="{{ route('merchant.products.toggle', $p->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full border {{ $p->is_available ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                        {{ $p->is_available ? 'Ready' : 'Habis' }}
                                    </button>
                                </form>
                            </div>
                            <div class="mt-1">
                                @if($p->has_discount)
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-black text-rose-600">Rp{{ number_format($p->discount_price, 0, ',', '.') }}</span>
                                        <span class="text-[10px] text-slate-400 line-through">Rp{{ number_format($p->estimated_price, 0, ',', '.') }}</span>
                                        <span class="text-[9px] font-black bg-rose-50 text-rose-600 border border-rose-200 px-1.5 py-0.2 rounded-md">-{{ $p->discount_percentage }}%</span>
                                    </div>
                                @else
                                    <p class="text-xs font-black text-slate-800">Rp{{ number_format($p->estimated_price, 0, ',', '.') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 text-xs">
                            <span class="text-[10px] text-slate-400">{{ $p->created_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('merchant.products.edit', $p->id) }}" class="text-slate-600 hover:text-slate-900 font-bold text-xs">Edit</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-2 text-xl">
                        🛒
                    </div>
                    <h4 class="font-bold text-slate-700 text-sm">Belum Ada Produk yang Didaftarkan</h4>
                    <p class="text-xs text-slate-400 mt-1">Mulai masukkan menu atau produk toko Anda agar pelanggan dapat langsung memesan!</p>
                    <a href="{{ route('merchant.products.create') }}" class="inline-block mt-4 px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                        Tambah Produk Pertama
                    </a>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
