@extends('layouts.support')

@section('title', 'Pencarian')

@section('content')
<div class="min-h-screen bg-white">
    <!-- Header -->
    <div class="sticky top-0 z-50 bg-white border-b border-slate-200/80 px-4 py-3 flex items-center gap-3">
        <a href="{{ route('customer.dashboard') }}" class="text-slate-600 hover:text-slate-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <form action="{{ route('customer.search') }}" method="GET" class="flex-grow relative">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Mau jastip apa?" autofocus class="w-full bg-[#F3F4F6] border-none text-slate-700 pl-10 pr-4 py-2.5 rounded-full text-sm font-semibold focus:ring-0 focus:outline-none focus:bg-slate-100">
            <div class="absolute left-3 top-2.5 text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </form>
    </div>

    @if(request()->has('q') && request('q') != '')
        <!-- Results -->
        <div class="p-4 space-y-4">
            @if(isset($merchants) && $merchants->count() > 0)
                <div>
                    <h3 class="font-bold text-slate-800 mb-2">Toko / Restoran</h3>
                    <div class="space-y-2">
                        @foreach($merchants as $merchant)
                        <a href="{{ route('customer.merchant.show', $merchant->id) }}" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl hover:bg-slate-100 transition">
                            <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-500 font-bold shrink-0">
                                {{ substr($merchant->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-bold text-sm text-slate-800">{{ $merchant->name }}</div>
                                <div class="text-[10px] text-slate-500 line-clamp-1">{{ $merchant->address }}</div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(isset($products) && $products->count() > 0)
                <div>
                    <h3 class="font-bold text-slate-800 mb-2">Menu / Produk</h3>
                    <div class="space-y-2">
                        @foreach($products as $product)
                        <a href="{{ route('customer.merchant.show', $product->merchant_id) }}" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl hover:bg-slate-100 transition">
                            <div class="flex-grow">
                                <div class="font-bold text-sm text-slate-800">{{ $product->name }}</div>
                                <div class="text-[10px] text-slate-500">{{ $product->merchant->name }}</div>
                                <div class="text-xs font-bold text-slate-900 mt-1">Rp {{ number_format($product->estimated_price, 0, ',', '.') }}</div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($merchants->count() == 0 && $products->count() == 0)
                <div class="text-center py-10">
                    <p class="text-slate-500 text-sm mb-4">Tidak ada hasil untuk "{{ request('q') }}"</p>
                    <a href="{{ route('customer.orders.create') }}" class="inline-block bg-rose-600 text-white font-bold py-2 px-4 rounded-full text-xs">
                        Buat Pesanan Manual
                    </a>
                </div>
            @endif
        </div>
    @else
        <!-- Discovery / Default State -->
        <div class="p-4">
            <!-- Pilihan Manual -->
            <a href="{{ route('customer.orders.create') }}" class="flex items-start gap-4 p-4 border border-slate-100 rounded-2xl shadow-sm hover:border-rose-200 transition mb-6">
                <div class="bg-rose-500 w-12 h-12 rounded-xl flex items-center justify-center shadow-md shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-slate-800">Pesanan Jastip Manual</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug">Tidak nemu barang atau toko yang dicari? Ketik sendiri pesananmu di sini.</p>
                </div>
            </a>
            
            <!-- Layanan JastipKuy -->
            <h3 class="font-bold text-slate-800 mb-3 text-sm">Layanan JastipKuy</h3>
            <div class="grid grid-cols-3 gap-3 mb-6">
                <a href="{{ route('customer.food') }}" class="flex flex-col items-center justify-center p-3 border border-slate-100 rounded-2xl hover:border-rose-300 transition">
                    <img src="{{ asset('images/services/beli-antar.png') }}" alt="Kuliner" class="w-10 h-10 mb-2 object-contain">
                    <span class="text-[10px] font-bold text-slate-700">Kuliner</span>
                </a>
                <a href="{{ route('customer.mart') }}" class="flex flex-col items-center justify-center p-3 border border-slate-100 rounded-2xl hover:border-amber-300 transition">
                    <img src="{{ asset('images/services/toko-kirim.png') }}" alt="Belanja" class="w-10 h-10 mb-2 object-contain">
                    <span class="text-[10px] font-bold text-slate-700">Belanja</span>
                </a>
                <a href="{{ route('customer.orders.create') }}?cat=ambil-antar" class="flex flex-col items-center justify-center p-3 border border-slate-100 rounded-2xl hover:border-sky-300 transition">
                    <img src="{{ asset('images/services/ambil-antar.png') }}" alt="Ambil Barang" class="w-10 h-10 mb-2 object-contain">
                    <span class="text-[10px] font-bold text-slate-700">Ambil Barang</span>
                </a>
            </div>

            <!-- Pencarian Populer -->
            <h3 class="font-bold text-slate-800 mb-3 text-sm">Pencarian populer</h3>
            <div class="flex flex-wrap gap-2">
                <a href="?q=kfc" class="px-4 py-1.5 border border-slate-200 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-50">kfc</a>
                <a href="?q=kopi" class="px-4 py-1.5 border border-slate-200 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-50">kopi</a>
                <a href="?q=mie" class="px-4 py-1.5 border border-slate-200 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-50">mie</a>
                <a href="?q=susu" class="px-4 py-1.5 border border-slate-200 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-50">susu</a>
            </div>
        </div>
    @endif
</div>
@endsection
