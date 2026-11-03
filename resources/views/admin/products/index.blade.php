@extends('layouts.support')

@section('title', 'Kelola Produk - ' . $merchant->name)

@section('content')
<div class="min-h-screen bg-slate-50 flex">

    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <div class="flex-1 flex flex-col min-w-0 flex-col min-h-screen">
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
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    @if($p->category)
                                                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-600 border border-rose-100 text-[8px] uppercase font-black">{{ $p->category->name }}</span>
                                                    @endif
                                                    <p class="text-sm font-bold text-slate-800">{{ $p->name }}</p>
                                                    @if($p->is_flash_sale)
                                                        <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-700 text-[8px] uppercase font-black">Promo</span>
                                                    @endif
                                                </div>
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
                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Produk</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <select name="category_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="new_category_name" placeholder="Atau ketik kategori baru..." class="w-full border border-rose-200 bg-rose-50/30 rounded-xl px-4 py-2.5 text-xs font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
            </div>
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
                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Produk</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <select name="category_id" id="edit_prod_category" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="new_category_name" placeholder="Atau ketik kategori baru..." class="w-full border border-rose-200 bg-rose-50/30 rounded-xl px-4 py-2.5 text-xs font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
            </div>
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
        document.getElementById('edit_prod_category').value = product.category_id || '';
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
