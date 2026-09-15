@extends('layouts.support')

@section('title', $merchant->name)

@section('content')
<div class="min-h-screen bg-[#F3F4F6] pb-24 relative">
    
    <!-- Merchant Header Image & Back Button -->
    <div class="relative h-48 bg-slate-300">
        @if($merchant->image)
            <img src="{{ $merchant->image }}" class="w-full h-full object-cover">
        @else
            <img src="https://ui-avatars.com/api/?name={{ urlencode($merchant->name) }}&background=e2e8f0&color=64748b&size=500" class="w-full h-full object-cover">
        @endif
        
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        
        <a href="{{ url()->previous() == url()->current() ? route('customer.dashboard') : url()->previous() }}" class="absolute top-4 left-4 bg-white/30 backdrop-blur-md p-2 rounded-full text-white hover:bg-white/50 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
    </div>

    <!-- Merchant Info Card -->
    <div class="px-4 -mt-12 relative z-10">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
            <h1 class="font-extrabold text-xl text-slate-800 mb-1">{{ $merchant->name }}</h1>
            <p class="text-xs text-slate-500 mb-3">{{ $merchant->address }}</p>
            
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-700">
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    <span>4.8</span>
                </div>
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>25 mnt • 2.5 km</span>
                </div>
            </div>
            
            @if($merchant->description)
            <div class="mt-3 p-3 bg-slate-50 rounded-xl text-xs text-slate-600 border border-slate-100">
                {{ $merchant->description }}
            </div>
            @endif
        </div>
    </div>

    <!-- Product List -->
    <div class="mt-4 px-4 pb-10">
        <h2 class="font-black text-slate-800 text-lg mb-4">Daftar Menu</h2>
        
        <div class="space-y-4">
            @forelse($merchant->products as $product)
            <div class="flex items-center gap-3 bg-white p-3 rounded-2xl shadow-sm border border-slate-100">
                <div class="w-20 h-20 bg-slate-100 rounded-xl overflow-hidden shrink-0">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($product->name) }}&background=cbd5e1&color=475569" class="w-full h-full object-cover">
                </div>
                <div class="flex-grow py-1">
                    <h3 class="font-bold text-sm text-slate-800 mb-0.5">{{ $product->name }}</h3>
                    <p class="text-[10px] text-slate-500 mb-2 line-clamp-2">{{ $product->description }}</p>
                    <div class="font-black text-slate-800 text-sm">Rp{{ number_format($product->estimated_price, 0, ',', '.') }}</div>
                </div>
                <div class="shrink-0 flex items-end justify-end self-stretch py-1 pr-1">
                    <!-- Add button -->
                    <button onclick="addToCart({{ $product->id }})" class="w-8 h-8 rounded-full border-2 border-rose-600 text-rose-600 flex items-center justify-center hover:bg-rose-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="text-center py-10">
                <p class="text-slate-500 text-sm">Belum ada menu di toko ini.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Sticky Floating Cart Widget (Only visible if something is added) -->
    <!-- In a real app, this state would be managed globally via Alpine/Livewire. Here we use a simple static link for demonstration -->
    <div class="fixed bottom-24 left-4 right-4 z-50">
        <a href="{{ route('customer.cart.index') }}" class="flex items-center justify-between bg-rose-600 text-white rounded-full p-4 shadow-lg shadow-rose-600/30 font-bold hover:bg-rose-700 transition">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-full relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span class="absolute -top-1 -right-1 bg-white text-rose-600 text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center" id="cart-counter">!</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm">Lihat Keranjang</span>
                    <span class="text-[10px] font-medium opacity-80">Harga estimasi</span>
                </div>
            </div>
            <div class="flex items-center gap-1 text-sm">
                Checkout
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
            </div>
        </a>
    </div>

</div>

<script>
    function addToCart(productId) {
        fetch(`/customer/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animate counter or show toast
                const counter = document.getElementById('cart-counter');
                counter.classList.add('animate-ping');
                setTimeout(() => counter.classList.remove('animate-ping'), 500);
            } else {
                alert(data.error || 'Terjadi kesalahan');
            }
        });
    }
</script>
@endsection
