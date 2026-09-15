@extends('layouts.support')

@section('title', 'Kuliner (GoFood)')

@section('content')
<div class="min-h-screen bg-[#F9FAFB] pb-24">
    
    <!-- Top Search Header -->
    <div class="bg-white sticky top-0 z-40 px-4 py-3 border-b border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <a href="{{ route('customer.dashboard') }}" class="text-slate-700 bg-slate-100 p-2 rounded-full hover:bg-slate-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div class="flex-grow mx-3 bg-slate-100 py-1.5 px-3 rounded-full flex items-center justify-center gap-1.5 cursor-pointer hover:bg-slate-200 transition" onclick="document.getElementById('modal-location').classList.remove('hidden')">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                <span class="text-[10px] font-bold text-slate-800 line-clamp-1">{{ session('customer_location', 'Malang, Jawa Timur') }}</span>
                <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
            <a href="{{ route('customer.cart.index') }}" class="relative text-slate-700 bg-slate-100 p-2 rounded-full hover:bg-slate-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </a>
        </div>
        
        <a href="{{ route('customer.search') }}" class="block">
            <div class="w-full border border-slate-200 rounded-full flex items-center py-2 px-4 shadow-sm hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <span class="text-xs text-slate-400 font-semibold">Lagi mau mamam apa?</span>
                <div class="ml-auto flex items-center gap-2 border-l pl-2">
                    <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Quick Filters -->
    <div class="px-4 py-4 grid grid-cols-4 gap-2">
        <div class="flex flex-col items-center justify-center bg-white border border-slate-200 rounded-2xl py-3 shadow-sm shadow-slate-100">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mb-1 text-green-600"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg></div>
            <span class="text-[9px] font-bold text-slate-700">Resto<br>Terdekat</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-white border border-slate-200 rounded-2xl py-3 shadow-sm shadow-slate-100">
            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center mb-1 text-orange-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
            <span class="text-[9px] font-bold text-slate-700">Ongkir<br>MURAAAH</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-white border border-slate-200 rounded-2xl py-3 shadow-sm shadow-slate-100 relative">
            <span class="absolute -top-1.5 bg-slate-900 text-white text-[7px] font-bold px-1.5 py-0.5 rounded-full">-50%</span>
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mb-1 text-blue-600"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg></div>
            <span class="text-[9px] font-bold text-slate-700">Group<br>Order</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-white border border-slate-200 rounded-2xl py-3 shadow-sm shadow-slate-100 relative">
            <span class="absolute -top-1.5 bg-slate-200 text-slate-700 text-[7px] font-bold px-1.5 py-0.5 rounded-full">30rb</span>
            <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center mb-1 text-rose-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg></div>
            <span class="text-[9px] font-bold text-slate-700">GoFood<br>HEMAT</span>
        </div>
    </div>

    <!-- Flash Sale -->
    @if(isset($flashSales) && $flashSales->count() > 0)
    <div class="px-4 mb-6">
        <div class="bg-gradient-to-r from-rose-500 to-rose-400 rounded-2xl p-4 text-white shadow-md">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd"></path></svg>
                    <h2 class="font-black text-lg italic">Flash Sale</h2>
                </div>
                <div class="bg-white/20 px-2 py-0.5 rounded-full text-[10px] font-bold flex items-center gap-1 backdrop-blur-sm">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    03 : 25 : 06
                </div>
            </div>
            <p class="text-[10px] font-medium mb-3 opacity-90">Diskon mantap setiap hari mulai 20rb!</p>
            
            <div class="flex gap-3 overflow-x-auto pb-2 snap-x -mx-4 px-4 scrollbar-hide">
                @foreach($flashSales as $product)
                <a href="{{ route('customer.merchant.show', $product->merchant_id) }}" class="snap-start shrink-0 w-36 bg-white rounded-xl overflow-hidden shadow-sm relative group">
                    <div class="absolute top-2 left-2 bg-rose-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded z-10">-25%</div>
                    <div class="h-28 bg-slate-200 relative overflow-hidden">
                        @if($product->image)
                            <img src="{{ $product->image }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400">No Image</div>
                        @endif
                    </div>
                    <div class="p-2.5">
                        <div class="flex items-center gap-1 text-[9px] text-green-600 font-bold mb-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Promo
                        </div>
                        <div class="text-[10px] text-slate-500 truncate">{{ $product->merchant->name }}</div>
                        <div class="font-bold text-slate-800 text-xs leading-tight h-8 mt-0.5 line-clamp-2">{{ $product->name }}</div>
                        <div class="mt-1 flex items-baseline gap-1">
                            <span class="text-rose-600 font-black text-sm">Rp{{ number_format($product->estimated_price, 0, ',', '.') }}</span>
                            <span class="text-[9px] text-slate-400 line-through">Rp{{ number_format($product->estimated_price * 1.33, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Categories -->
    <div class="px-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-black text-slate-800 text-sm">Kuliner sesuai seleramu</h3>
            <a href="#" class="text-[10px] bg-green-100 text-green-700 px-3 py-1 rounded-full font-bold">Lihat Semua</a>
        </div>
        <div class="grid grid-cols-4 gap-y-4 gap-x-2">
            @foreach($categories as $category)
            <div class="flex flex-col items-center cursor-pointer">
                <div class="w-16 h-16 rounded-full bg-slate-200 overflow-hidden mb-1.5 shadow-sm border border-slate-100">
                    <!-- Dummy images based on category name -->
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($category->name) }}&background=random&color=fff&size=100" class="w-full h-full object-cover">
                </div>
                <span class="text-[9px] font-bold text-slate-700 text-center leading-tight">{{ $category->name }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Resto List -->
    <div class="px-4">
        <h3 class="font-black text-slate-800 text-sm mb-4">Resto Terpopuler</h3>
        <div class="space-y-4">
            @foreach($merchants as $merchant)
            <a href="{{ route('customer.merchant.show', $merchant->id) }}" class="flex bg-white p-3 rounded-2xl shadow-sm border border-slate-100 items-start gap-3 relative">
                <div class="w-20 h-20 rounded-xl bg-slate-200 shrink-0 overflow-hidden relative border border-slate-100">
                     <img src="https://ui-avatars.com/api/?name={{ urlencode($merchant->name) }}&background=f43f5e&color=fff&size=200" class="w-full h-full object-cover">
                </div>
                <div class="flex-grow pt-1">
                    <h4 class="font-extrabold text-slate-800 text-sm mb-0.5">{{ $merchant->name }}</h4>
                    <p class="text-[10px] text-slate-500 mb-1.5 line-clamp-1">{{ $merchant->address }}</p>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="flex items-center text-[10px] font-bold text-slate-700">
                            <svg class="w-3.5 h-3.5 text-amber-400 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            4.8
                        </div>
                        <span class="text-slate-300">•</span>
                        <span class="text-[10px] text-slate-500">2.5 km</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    <!-- Modal Location -->
    <div id="modal-location" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-location').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-3xl w-full max-w-sm p-6 overflow-hidden shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-black text-lg text-slate-900">Ubah Lokasi Pengiriman</h3>
                <button onclick="document.getElementById('modal-location').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="{{ route('customer.location.update') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 mb-2">Pilih atau Ketik Alamat Anda</label>
                    <textarea name="location" rows="3" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none" placeholder="Contoh: Jl. Ijen No 12, Malang">{{ session('customer_location', 'Malang, Jawa Timur') }}</textarea>
                </div>
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-black py-3 rounded-xl transition">
                    Simpan Lokasi
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
