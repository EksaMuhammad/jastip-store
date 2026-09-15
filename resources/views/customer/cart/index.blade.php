@extends('layouts.support')

@section('title', 'Keranjang Belanja')

@section('content')
<div class="min-h-screen bg-[#F3F4F6] pb-24">
    
    <!-- Header -->
    <div class="bg-white sticky top-0 z-40 px-4 py-3 border-b border-slate-200/80 shadow-sm flex items-center justify-between">
        <a href="{{ url()->previous() }}" class="text-slate-700 p-2 -ml-2 rounded-full hover:bg-slate-100 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <h1 class="font-extrabold text-base text-slate-800">Checkout</h1>
        <div class="w-8"></div> <!-- Spacer -->
    </div>

    @if(!$cart || $cart->cartItems->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 px-4">
        <div class="w-24 h-24 bg-slate-200 rounded-full flex items-center justify-center text-slate-400 mb-4">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        </div>
        <h2 class="font-bold text-lg text-slate-800 mb-1">Keranjang masih kosong</h2>
        <p class="text-sm text-slate-500 text-center mb-6">Mulai cari makanan atau barang kebutuhanmu sekarang!</p>
        <a href="{{ route('customer.dashboard') }}" class="bg-rose-600 text-white font-bold py-3 px-6 rounded-full shadow-md">Kembali ke Dashboard</a>
    </div>
    @else
    <form action="{{ route('customer.cart.checkout') }}" method="POST" class="p-4 space-y-4">
        @csrf
        
        <!-- Delivery Info Card -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
            <h2 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                Alamat Pengiriman
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Tujuan Pengiriman</label>
                    <textarea name="delivery_location" required rows="2" class="w-full border-slate-200 rounded-xl focus:ring-rose-500 focus:border-rose-500 text-sm" placeholder="Contoh: Jl. Sudirman No 12, Pintu Pagar Hitam">{{ session('customer_location', 'Malang, Jawa Timur') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Order Items Card -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
            <h2 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path></svg>
                Pesanan Anda ({{ $cart->merchant_id ? $cart->cartItems->first()->product->merchant->name : 'Merchant' }})
            </h2>
            
            <div class="space-y-4 divide-y divide-slate-100">
                @php $totalBarang = 0; @endphp
                @foreach($cart->cartItems as $item)
                @php $totalBarang += ($item->product->estimated_price * $item->quantity); @endphp
                <div class="pt-3 first:pt-0 flex justify-between items-start">
                    <div class="flex items-start gap-3">
                        <div class="font-bold text-sm text-rose-600 bg-rose-50 px-2 py-0.5 rounded">{{ $item->quantity }}x</div>
                        <div>
                            <div class="font-bold text-sm text-slate-800">{{ $item->product->name }}</div>
                            <div class="text-[10px] text-slate-500 line-clamp-1">{{ $item->product->description }}</div>
                        </div>
                    </div>
                    <div class="font-bold text-sm text-slate-800 shrink-0">Rp{{ number_format($item->product->estimated_price * $item->quantity, 0, ',', '.') }}</div>
                </div>
                @endforeach
            </div>
            
            <a href="{{ route('customer.dashboard') }}" class="mt-4 block text-center text-xs font-bold text-rose-600 border border-rose-200 bg-rose-50 rounded-xl py-2 hover:bg-rose-100 transition">
                + Tambah pesanan lain
            </a>
        </div>

        <!-- Billing Summary Card -->
        @php
            $ongkir = 15000;
            $dpBarang = $totalBarang * 0.5;
            $totalBayarSekarang = $dpBarang + $ongkir;
        @endphp
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
            <h2 class="font-bold text-slate-800 text-sm mb-3">Ringkasan Pembayaran</h2>
            
            <div class="space-y-2 text-xs">
                <div class="flex justify-between items-center text-slate-600">
                    <span>Estimasi Harga Barang</span>
                    <span>Rp{{ number_format($totalBarang, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-slate-600">
                    <span>Ongkos Kirim (Flat)</span>
                    <span>Rp{{ number_format($ongkir, 0, ',', '.') }}</span>
                </div>
                
                <hr class="border-slate-100 my-2">
                
                <div class="bg-blue-50 border border-blue-100 p-3 rounded-xl mb-3">
                    <h3 class="font-bold text-blue-800 text-xs mb-1">Skema Downpayment (DP) 50%</h3>
                    <p class="text-[10px] text-blue-600 mb-2 leading-relaxed">Untuk mencegah pesanan fiktif, Anda wajib membayar DP 50% + Ongkir melalui JKPay <b>setelah pesanan disetujui Jastiper</b>.</p>
                    
                    <div class="flex justify-between items-center text-blue-800 font-semibold mb-1">
                        <span>DP Barang (50%)</span>
                        <span>Rp{{ number_format($dpBarang, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-blue-800 font-semibold mb-1">
                        <span>Ongkir</span>
                        <span>Rp{{ number_format($ongkir, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="mt-2 pt-2 border-t border-blue-200 flex justify-between items-center">
                        <span class="font-black text-blue-900 text-sm">Total DP (Dibayar Nanti)</span>
                        <span class="font-black text-blue-900 text-sm">Rp{{ number_format($totalBayarSekarang, 0, ',', '.') }}</span>
                    </div>
                </div>
                
                <div class="flex items-start gap-2 bg-yellow-50 text-yellow-800 p-3 rounded-xl text-[10px] leading-relaxed">
                    <svg class="w-4 h-4 shrink-0 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span>Sisa tagihan barang (Sesuai struk / nota pembelian) akan dibayar secara Tunai/Cash kepada Jastiper saat barang tiba.</span>
                </div>
            </div>
        </div>

        <input type="hidden" name="delivery_fee" value="{{ $ongkir }}">

        <!-- Bottom Checkout Sticky Bar -->
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-200 z-50 flex items-center justify-between shadow-[0_-4px_10px_rgba(0,0,0,0.05)]">
            <div class="flex flex-col">
                <span class="text-[10px] text-slate-500 font-medium">Estimasi Total DP</span>
                <span class="font-black text-rose-600 text-lg">Rp{{ number_format($totalBayarSekarang, 0, ',', '.') }}</span>
            </div>
            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-black px-6 py-3 rounded-full text-sm shadow-md transition">
                Cari Jastiper
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
