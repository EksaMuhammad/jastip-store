@extends('layouts.support')

@section('title', 'Kelola Produk - ' . $merchant->name)

@section('content')
<div class="min-h-screen bg-slate-50 flex">

    <!-- Sidebar Desktop (sama dengan dashboard admin) -->
    <aside class="hidden lg:flex flex-col w-64 bg-slate-950 border-r border-slate-900 fixed h-full z-20">
        <div class="p-6 flex items-center gap-3 bg-slate-950/50 backdrop-blur-md border-b border-slate-900/50">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 flex items-center justify-center shadow-lg shadow-rose-500/20">
                <span class="font-display font-black text-white text-lg">JK</span>
            </div>
            <div>
                <h1 class="font-display font-bold text-white text-base leading-tight">JastipKuy</h1>
                <p class="text-[10px] font-medium text-rose-400 uppercase tracking-widest">Admin Portal</p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto py-6 custom-scrollbar">
            <nav class="px-4 space-y-1.5">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-wider block px-3 mb-2">Menu Utama</span>

                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Utama</span>
                    </div>
                </a>

                <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Verifikasi Jastiper</span>
                    </div>
                </a>

                <a href="{{ route('admin.payments') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Verifikasi Pembayaran</span>
                    </div>
                </a>

                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Kelola Wilayah</span>
                    </div>
                </button>

                <!-- ACTIVE MENU: Kelola Merchant -->
                <a href="{{ route('admin.merchants.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Kelola Merchant</span>
                    </div>
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                </a>

                <!-- Menu: Manajemen Promo -->
                <a href="{{ route('admin.promos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Manajemen Promo</span>
                    </div>
                </a>

                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </button>

                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Withdraw Jastiper</span>
                    </div>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-900 bg-slate-950">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-rose-500 hover:bg-rose-500/10 font-bold text-xs transition text-left">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Keluar Panel</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
        <!-- Top Navbar -->
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <button onclick="toggleMobileSidebar()" class="lg:hidden p-2 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.merchants.index') }}" class="p-2 hover:bg-slate-100 rounded-full transition text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <span class="px-3 py-1 rounded-full bg-rose-50 border border-rose-100 text-[10px] font-black text-rose-500 uppercase tracking-widest">Produk Merchant: {{ $merchant->name }}</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-3 px-4 py-2 rounded-full bg-slate-50 border border-slate-100">
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center">
                        <span class="text-white text-xs font-bold">AD</span>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-800">{{ auth()->guard('admin')->user()->name }}</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6 lg:p-10">
            <div class="max-w-5xl mx-auto space-y-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-display font-black text-2xl text-slate-900">Katalog Produk</h2>
                        <p class="text-sm text-slate-500 mt-1">Kelola item/menu untuk merchant <strong>{{ $merchant->name }}</strong>.</p>
                    </div>
                    <button onclick="document.getElementById('modal-create-product').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition flex items-center gap-2 shadow-lg shadow-slate-900/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Tambah Produk
                    </button>
                </div>

                @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 text-emerald-700">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <p class="text-sm font-semibold">{{ session('success') }}</p>
                </div>
                @endif

                <!-- Products List -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Info Produk</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Harga Estimasi</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $p)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-4">
                                            @if($p->image)
                                                <img src="{{ $p->image }}" class="w-12 h-12 rounded-xl object-cover bg-slate-100">
                                            @else
                                                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">
                                                    {{ $p->name }}
                                                    @if($p->is_flash_sale)
                                                        <span class="ml-2 px-2 py-0.5 rounded bg-rose-100 text-rose-700 text-[8px] uppercase font-black">Promo</span>
                                                    @endif
                                                </p>
                                                <p class="text-[10px] text-slate-400 max-w-[200px] truncate">{{ $p->description }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="text-xs font-bold text-slate-900">Rp{{ number_format((float)$p->estimated_price, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($p->is_available)
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-bold uppercase tracking-widest">Tersedia</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-bold uppercase tracking-widest">Habis</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button onclick="openEditProductModal({{ json_encode($p) }})" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Edit Produk">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini?');" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-500 transition" title="Hapus Produk">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-slate-500 text-sm">Belum ada data produk untuk merchant ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>{{ $products->links() }}</div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Produk -->
<div id="modal-create-product" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-create-product').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-3xl w-full max-w-lg p-6 overflow-hidden shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-black text-xl text-slate-900">Tambah Produk</h3>
            <button onclick="document.getElementById('modal-create-product').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <form action="{{ route('admin.products.store', $merchant->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Produk <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Estimasi (Rp) <span class="text-rose-500">*</span></label>
                <input type="number" name="estimated_price" required min="0" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">URL Gambar (Opsional)</label>
                <input type="url" name="image" placeholder="https://..." class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi (Opsional)</label>
                <textarea name="description" rows="2" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
            </div>
            
            <div class="flex items-center gap-6 pt-2">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_available" id="is_available_create" value="1" checked class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                    <label for="is_available_create" class="text-sm font-semibold text-slate-700">Tersedia (Stok ada)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_flash_sale" id="is_flash_sale_create" value="1" class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                    <label for="is_flash_sale_create" class="text-sm font-semibold text-slate-700">Flash Sale / Promo</label>
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('modal-create-product').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Produk -->
<div id="modal-edit-product" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-edit-product').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-3xl w-full max-w-lg p-6 overflow-hidden shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-black text-xl text-slate-900">Edit Produk</h3>
            <button onclick="document.getElementById('modal-edit-product').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <form id="edit-product-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Produk <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="edit_prod_name" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Estimasi (Rp) <span class="text-rose-500">*</span></label>
                <input type="number" name="estimated_price" id="edit_prod_price" required min="0" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">URL Gambar (Opsional)</label>
                <input type="url" name="image" id="edit_prod_image" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi (Opsional)</label>
                <textarea name="description" id="edit_prod_description" rows="2" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
            </div>
            
            <div class="flex items-center gap-6 pt-2">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_available" id="edit_prod_available" value="1" class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                    <label for="edit_prod_available" class="text-sm font-semibold text-slate-700">Tersedia (Stok ada)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_flash_sale" id="edit_prod_flash" value="1" class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                    <label for="edit_prod_flash" class="text-sm font-semibold text-slate-700">Flash Sale / Promo</label>
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('modal-edit-product').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditProductModal(product) {
        document.getElementById('edit_prod_name').value = product.name;
        document.getElementById('edit_prod_price').value = parseInt(product.estimated_price);
        document.getElementById('edit_prod_image').value = product.image || '';
        document.getElementById('edit_prod_description').value = product.description || '';
        document.getElementById('edit_prod_available').checked = product.is_available;
        document.getElementById('edit_prod_flash').checked = product.is_flash_sale;
        
        document.getElementById('edit-product-form').action = `/admin/products/${product.id}`;
        
        document.getElementById('modal-edit-product').classList.remove('hidden');
    }

    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('hidden');
        }
    }
</script>
@endsection
