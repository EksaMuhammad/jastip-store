@extends('layouts.support')

@section('title', 'Belanja (GoMart)')

@section('content')
<div class="min-h-screen bg-white pb-36">
    
    <!-- Top Search Header -->
    <div class="bg-white sticky top-0 z-40 px-4 py-3 border-b border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.dashboard') }}" class="text-slate-700 bg-slate-100 p-2 rounded-full hover:bg-slate-200 transition shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </a>
            <a href="{{ route('customer.search') }}" class="flex-grow">
                <div class="w-full border border-slate-200 rounded-full flex items-center py-2 px-4 shadow-sm hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span class="text-xs text-slate-400 font-semibold">Restok gula</span>
                </div>
            </a>
            <a href="{{ route('customer.cart.index') }}" class="relative text-slate-700 bg-slate-100 p-2 rounded-full hover:bg-slate-200 transition shrink-0" title="Keranjang Belanja">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                @php
                    $martCartCount = \App\Models\CartItem::whereHas('cart', fn($q) => $q->where('customer_id', auth('customer')->id()))->count();
                @endphp
                <span class="cart-badge {{ $martCartCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 bg-rose-600 text-white text-[10px] font-black w-4.5 h-4.5 rounded-full flex items-center justify-center border-2 border-white">{{ $martCartCount }}</span>
            </a>
        </div>
        
        <!-- Categories Scroll -->
        <div class="flex items-center gap-5 overflow-x-auto mt-4 pb-2 scrollbar-hide text-center px-2">
            <a href="{{ route('customer.mart') }}" class="flex flex-col items-center gap-1 shrink-0 relative pb-2 {{ !request('category') ? 'border-b-2 border-slate-900 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900' }}">
                <svg class="w-6 h-6 {{ !request('category') ? 'text-slate-900' : 'text-slate-400' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="text-[10px] font-bold">Semua</span>
            </a>
            @foreach($categories as $category)
            <a href="{{ route('customer.mart', ['category' => $category->id]) }}" class="flex flex-col items-center gap-1 shrink-0 relative pb-2 {{ request('category') == $category->id ? 'border-b-2 border-slate-900 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900' }} transition cursor-pointer">
                <div class="relative">
                    <svg class="w-6 h-6 {{ request('category') == $category->id ? 'text-rose-600' : 'text-slate-400' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path></svg>
                    @if(in_array($category->name, ['Makanan', 'Minuman', 'Rokok']))
                        <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-600 rounded-full border border-white"></div>
                    @endif
                </div>
                <span class="text-[10px] font-bold">{{ $category->name }}</span>
            </a>
            @endforeach
        </div>
    </div>

    <!-- Products Grid (Grouped by Category ideally, here we just show all) -->
    <div class="px-4 py-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-black text-slate-800 text-base">
                @if(request('category') && ($activeCat = $categories->find(request('category'))))
                    Kategori: {{ $activeCat->name }}
                @else
                    Rekomendasi Belanja
                @endif
            </h2>
            @if(request('category'))
                <a href="{{ route('customer.mart') }}" class="text-[10px] text-rose-600 font-bold">Reset filter</a>
            @else
                <a href="#" class="text-[10px] text-green-600 font-bold">Lihat semua</a>
            @endif
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($products as $product)
            <div class="border border-slate-200 rounded-2xl p-2.5 relative flex flex-col hover:border-green-400 transition bg-white shadow-sm">
                <div class="relative w-full aspect-square mb-3">
                    <div class="w-full h-full bg-slate-50 rounded-xl overflow-hidden flex items-center justify-center">
                        @if($product->image)
                            <img src="{{ $product->image_url ?? $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($product->name) }}&background=e2e8f0&color=475569" class="w-full h-full object-contain mix-blend-multiply p-2">
                        @endif
                    </div>
                    
                    <!-- Add to Cart Button: fully visible, unclipped, floating cleanly on corner -->
                    <button type="button" 
                            onclick="addToCart({{ $product->id }}, this)" 
                            title="Tambah ke keranjang"
                            class="absolute bottom-2 right-2 w-8 h-8 bg-green-600 hover:bg-green-700 active:scale-90 text-white rounded-full shadow-md shadow-green-950/20 flex items-center justify-center transition-all z-10 focus:outline-none cursor-pointer">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                </div>
                
                <div class="flex items-center gap-1 mb-1.5 flex-wrap">
                    <span class="bg-slate-100 text-slate-600 text-[9px] font-semibold px-2 py-0.5 rounded">{{ $product->merchant->name }}</span>
                    @if($product->category)
                        <span class="bg-rose-50 text-rose-600 text-[8px] font-bold px-1.5 py-0.5 rounded">{{ $product->category->name }}</span>
                    @endif
                </div>
                <h3 class="font-bold text-slate-800 text-xs leading-snug h-8 line-clamp-2 mb-1">{{ $product->name }}</h3>
                <div class="mt-auto flex flex-col">
                    @if($product->has_discount)
                        <div class="flex items-baseline gap-1">
                            <span class="font-black text-rose-600 text-sm">Rp{{ number_format($product->discount_price, 0, ',', '.') }}</span>
                            <span class="text-[9px] text-slate-400 line-through">Rp{{ number_format($product->estimated_price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-1 mt-0.5">
                            <span class="bg-rose-100 text-rose-600 text-[8px] font-bold px-1 py-0.5 rounded">-{{ $product->discount_percentage }}%</span>
                            @if($product->is_flash_sale)
                                <span class="bg-amber-100 text-amber-700 text-[8px] font-bold px-1 py-0.5 rounded">Flash Sale</span>
                            @endif
                        </div>
                    @else
                        <span class="font-black text-slate-800 text-sm">Rp{{ number_format($product->estimated_price, 0, ',', '.') }}</span>
                        @if($product->is_flash_sale)
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="bg-rose-100 text-rose-600 text-[8px] font-bold px-1 py-0.5 rounded">Flash Sale</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</div>

<script>
    function addToCart(productId, btn) {
        if (btn) btn.disabled = true;

        fetch(`/customer/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Gagal menambahkan ke keranjang');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update all cart badges across page & bottom nav
                const badges = document.querySelectorAll('.cart-badge, [id*="cart-count"], nav a[href*="cart"] span.bg-rose-600');
                badges.forEach(b => {
                    b.textContent = data.cart_count;
                    b.classList.remove('hidden');
                });

                // Micro-feedback checkmark on the clicked plus button
                if (btn) {
                    const originalSvg = btn.innerHTML;
                    btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                    btn.classList.add('bg-emerald-600', 'scale-110');
                    btn.innerHTML = `<svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>`;

                    setTimeout(() => {
                        btn.classList.remove('bg-emerald-600', 'scale-110');
                        btn.classList.add('bg-green-600', 'hover:bg-green-700');
                        btn.innerHTML = originalSvg;
                        btn.disabled = false;
                    }, 1200);
                }

                // Show polished toast notification
                let toast = document.getElementById('mart-toast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.id = 'mart-toast';
                    toast.className = 'fixed top-20 right-4 bg-slate-900/90 backdrop-blur text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-xl z-50 flex items-center gap-2 transition-all duration-300 transform translate-y-0 opacity-100 border border-slate-700';
                    document.body.appendChild(toast);
                }
                toast.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400"></span> <span>${data.message || 'Produk ditambahkan ke keranjang!'}</span>`;
                toast.style.display = 'flex';
                toast.style.opacity = '1';

                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => { toast.style.display = 'none'; }, 300);
                }, 2000);
            } else {
                if (btn) btn.disabled = false;
                alert(data.error || 'Terjadi kesalahan');
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            alert(err.message || 'Terjadi kesalahan saat menambahkan produk');
        });
    }
</script>
@endsection
