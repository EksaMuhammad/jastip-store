@extends('layouts.merchant')

@section('title', 'Katalog Produk Toko')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display font-black text-2xl text-slate-900">Kelola Menu & Produk Toko</h1>
            <p class="text-xs text-slate-500 mt-0.5">Produk dan foto yang Anda simpan di sini akan langsung tampil pada pilihan toko di dashboard customer.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('merchant.categories.index') }}" class="px-4 py-3 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center gap-2">
                <span>🏷️ Kelola Kategori</span>
            </a>
            <a href="{{ route('merchant.products.create') }}" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-rose-600 to-orange-500 hover:from-rose-500 hover:to-orange-400 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-rose-600/20 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                <span>+ Tambah Produk Baru</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <form method="GET" action="{{ route('merchant.products.index') }}" class="flex-1 flex gap-2">
            <div class="relative flex-1">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nama menu / produk toko..." 
                    class="w-full bg-slate-50 border border-slate-200 pl-10 pr-4 py-2.5 rounded-2xl text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button type="submit" class="px-4 py-2.5 bg-slate-900 text-white rounded-2xl text-xs font-bold hover:bg-slate-800 transition">
                Cari
            </button>
            @if(request('search') || request('status'))
                <a href="{{ route('merchant.products.index') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl text-xs font-bold transition flex items-center">
                    Reset
                </a>
            @endif
        </form>

        <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
            <a href="{{ route('merchant.products.index', array_merge(request()->query(), ['category_id' => ''])) }}" class="px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition {{ !request('category_id') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('merchant.products.index', array_merge(request()->query(), ['category_id' => $cat->id])) }}" class="px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition {{ request('category_id') == $cat->id ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $cat->name }}
                </a>
            @endforeach
            <span class="h-4 w-px bg-slate-200 shrink-0 mx-1"></span>
            <a href="{{ route('merchant.products.index', array_merge(request()->query(), ['status' => 'ready'])) }}" class="px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition {{ request('status') === 'ready' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Ready
            </a>
            <a href="{{ route('merchant.products.index', array_merge(request()->query(), ['status' => 'empty'])) }}" class="px-3 py-2 rounded-xl text-xs font-bold shrink-0 transition {{ request('status') === 'empty' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Habis
            </a>
        </div>
    </div>

    <!-- Grid Produk Toko -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        @forelse($products as $product)
            <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col justify-between group">
                <div>
                    <!-- Foto Produk -->
                    <div class="h-44 bg-slate-100 relative overflow-hidden">
                        @if($product->image)
                            <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-1">
                                <svg class="w-8 h-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-[10px] font-bold">Belum Ada Foto</span>
                            </div>
                        @endif

                        <!-- Badge Status -->
                        <div class="absolute top-3 left-3">
                            <form action="{{ route('merchant.products.toggle', $product->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider backdrop-blur-md shadow-sm border transition {{ $product->is_available ? 'bg-emerald-500/90 text-white border-emerald-400' : 'bg-slate-900/80 text-rose-300 border-slate-700' }}" title="Klik untuk ubah status">
                                    {{ $product->is_available ? '✓ Ready' : '✕ Habis' }}
                                </button>
                            </form>
                        </div>

                        <div class="absolute top-3 right-3 flex flex-col gap-1 items-end">
                            @if($product->is_flash_sale)
                                <div class="bg-rose-600 text-white text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full shadow">
                                    ⚡ Flash Sale
                                </div>
                            @endif
                            @if($product->has_discount)
                                <div class="bg-amber-500 text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full shadow">
                                    Diskon {{ $product->discount_percentage }}%
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Info Produk -->
                    <div class="p-4 space-y-1.5">
                        @if($product->category)
                            <span class="inline-block px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 border border-rose-100">
                                {{ $product->category->name }}
                            </span>
                        @endif
                        <h3 class="font-bold text-slate-800 text-sm line-clamp-1 group-hover:text-rose-600 transition">{{ $product->name }}</h3>
                        <p class="text-xs text-slate-400 line-clamp-2 h-8">{{ $product->description ?? 'Tidak ada deskripsi.' }}</p>
                        <div class="pt-1">
                            @if($product->has_discount)
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="text-base font-black text-rose-600">Rp{{ number_format($product->discount_price, 0, ',', '.') }}</span>
                                    <span class="text-xs text-slate-400 line-through">Rp{{ number_format($product->estimated_price, 0, ',', '.') }}</span>
                                </div>
                                <span class="text-[10px] text-emerald-600 font-bold">Hemat Rp{{ number_format($product->discount_amount, 0, ',', '.') }}</span>
                            @else
                                <p class="text-base font-black text-slate-900">Rp{{ number_format($product->estimated_price, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="p-4 pt-0 border-t border-slate-100 flex items-center justify-between gap-2 mt-2">
                    <a href="{{ route('merchant.products.edit', $product->id) }}" class="flex-1 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs text-center transition">
                        Edit
                    </a>
                    <form action="{{ route('merchant.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini dari katalog toko Anda?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 transition" title="Hapus Produk">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-slate-200">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                    🔍
                </div>
                <h3 class="font-bold text-slate-800 text-base">Tidak Ada Produk yang Ditemukan</h3>
                <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci lain atau tambahkan produk baru ke toko Anda.</p>
                <a href="{{ route('merchant.products.create') }}" class="inline-block mt-4 px-6 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                    + Tambah Produk Sekarang
                </a>
            </div>
        @endforelse
    </div>

    <div class="pt-4">
        {{ $products->links() }}
    </div>

</div>
@endsection
