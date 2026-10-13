@extends('layouts.merchant')

@section('title', 'Edit Produk - ' . $product->name)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Breadcrumb & Title -->
    <div>
        <a href="{{ route('merchant.products.index') }}" class="text-xs font-bold text-slate-400 hover:text-slate-600 flex items-center gap-1.5 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Daftar Produk
        </a>
        <h1 class="font-display font-black text-2xl text-slate-900">Edit Produk: {{ $product->name }}</h1>
        <p class="text-xs text-slate-500 mt-0.5">Perbarui nama, harga, deskripsi, atau ganti foto produk.</p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Edit Produk -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <form action="{{ route('merchant.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Upload Foto Produk -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Foto Produk / Menu
                </label>
                <div class="border-2 border-dashed border-slate-200 hover:border-rose-400 rounded-3xl p-6 text-center bg-slate-50 transition cursor-pointer relative" onclick="document.getElementById('image-input').click()">
                    <input 
                        type="file" 
                        name="image" 
                        id="image-input" 
                        accept="image/*" 
                        class="hidden" 
                        onchange="previewProductImage(this)"
                    >
                    @if($product->image)
                        <div id="existing-image-container" class="space-y-2">
                            <img id="image-preview" src="{{ $product->image }}" alt="{{ $product->name }}" class="max-h-64 mx-auto rounded-2xl object-contain shadow-md">
                            <p class="text-[11px] font-bold text-rose-600 mt-2">Klik di sini untuk mengganti foto produk</p>
                        </div>
                    @else
                        <div id="image-placeholder" class="space-y-2">
                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center mx-auto shadow-sm text-rose-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <p class="text-xs font-bold text-slate-700">Pilih berkas foto baru</p>
                            <p class="text-[10px] text-slate-400">Format: JPG, PNG, WEBP (Maksimal 3MB)</p>
                        </div>
                        <div id="image-preview-container" class="hidden">
                            <img id="image-preview-new" src="#" alt="Pratinjau Foto" class="max-h-64 mx-auto rounded-2xl object-contain shadow-md">
                            <p class="text-[11px] font-bold text-rose-600 mt-2">Klik lagi untuk mengganti foto</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kategori, Nama & Harga -->
            <div class="space-y-4">
                <div class="space-y-1.5" x-data="{ isNewCategory: false }">
                    <div class="flex items-center justify-between">
                        <label for="category_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Kategori Produk
                        </label>
                        <div class="flex items-center gap-2.5">
                            <a href="{{ route('merchant.categories.index') }}" target="_blank" class="text-[11px] font-semibold text-slate-400 hover:text-slate-700 hover:underline">
                                Kelola Kategori ↗
                            </a>
                            <button 
                                type="button" 
                                @click="isNewCategory = !isNewCategory"
                                class="text-[11px] font-bold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1"
                            >
                                <span x-text="isNewCategory ? '← Pilih kategori yang ada' : '+ Ketik Kategori Baru'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown Kategori Ada -->
                    <div x-show="!isNewCategory">
                        <select 
                            name="category_id" 
                            id="category_id" 
                            class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none"
                        >
                            <option value="">-- Pilih Kategori yang Ada --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Input Kategori Baru -->
                    <div x-show="isNewCategory" style="display: none;">
                        <input 
                            type="text" 
                            name="new_category_name" 
                            id="new_category_name" 
                            placeholder="Ketik kategori baru (contoh: Rokok / Makanan / Minuman)..." 
                            class="w-full border border-rose-300 bg-rose-50/40 focus:bg-white rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none placeholder-slate-400"
                        >
                        <p class="text-[10px] text-slate-400 mt-1">Kategori baru akan otomatis disimpan di toko ini dan dapat dipakai produk lainnya.</p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Nama Menu / Produk <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        value="{{ old('name', $product->name) }}" 
                        required 
                        class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="estimated_price" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Harga Asli / Normal (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-xs font-bold text-slate-400">Rp</span>
                            <input 
                                type="number" 
                                name="estimated_price" 
                                id="estimated_price" 
                                value="{{ old('estimated_price', (int)$product->estimated_price) }}" 
                                required 
                                min="0" 
                                oninput="calcDiscountPreview()"
                                class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl pl-10 pr-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none"
                            >
                        </div>
                        <p class="text-[10px] text-slate-400">Harga standar sebelum promo.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="discount_price" class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
                            <span>Harga Diskon / Promo (Rp)</span>
                            <span class="text-[10px] text-slate-400 font-normal lowercase">(opsional)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-xs font-bold text-rose-500">Rp</span>
                            <input 
                                type="number" 
                                name="discount_price" 
                                id="discount_price" 
                                value="{{ old('discount_price', $product->discount_price ? (int)$product->discount_price : '') }}" 
                                min="0" 
                                placeholder="Kosongkan jika tanpa diskon" 
                                oninput="calcDiscountPreview()"
                                class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl pl-10 pr-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none"
                            >
                        </div>
                        <p class="text-[10px] text-slate-400">Jika diisi, harga ini yang dibayar customer dan harga normal dicoret.</p>
                    </div>
                </div>

                <!-- Preview Diskon Banner -->
                <div id="discount-preview-card" class="hidden p-3.5 rounded-2xl bg-gradient-to-r from-rose-50 to-orange-50 border border-rose-200 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-lg bg-rose-600 text-white font-black text-[11px]" id="preview-percent">-0%</span>
                        <span class="font-bold text-slate-700" id="preview-text">Potongan Diskon</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 line-through block" id="preview-original">Rp0</span>
                        <span class="font-black text-rose-600 text-sm" id="preview-final">Rp0</span>
                    </div>
                </div>
            </div>

            <!-- Deskripsi Produk -->
            <div class="space-y-1.5">
                <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Deskripsi / Keterangan Menu
                </label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="3" 
                    class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl p-4 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none"
                >{{ old('description', $product->description) }}</textarea>
            </div>

            <!-- Pengaturan Status -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Status Ketersediaan</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_available" value="1" {{ $product->is_available ? 'checked' : '' }} class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                        <span class="text-xs font-bold text-slate-700">Produk Ready (Bisa dipesan sekarang)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_flash_sale" value="1" {{ $product->is_flash_sale ? 'checked' : '' }} class="w-4 h-4 text-rose-600 rounded border-slate-300 focus:ring-rose-500">
                        <span class="text-xs font-bold text-slate-700">Tandai Flash Sale</span>
                    </label>
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('merchant.products.index') }}" class="px-5 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-slate-900/20">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    function previewProductImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgPrev = document.getElementById('image-preview') || document.getElementById('image-preview-new');
                if (imgPrev) {
                    imgPrev.src = e.target.result;
                }
                const newCont = document.getElementById('image-preview-container');
                if (newCont) {
                    newCont.classList.remove('hidden');
                }
                const placeholder = document.getElementById('image-placeholder');
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function calcDiscountPreview() {
        const origInput = document.getElementById('estimated_price');
        const discInput = document.getElementById('discount_price');
        const card = document.getElementById('discount-preview-card');
        const previewPercent = document.getElementById('preview-percent');
        const previewText = document.getElementById('preview-text');
        const previewOrig = document.getElementById('preview-original');
        const previewFinal = document.getElementById('preview-final');

        const original = parseFloat(origInput.value) || 0;
        const discount = parseFloat(discInput.value) || 0;

        if (original > 0 && discount > 0 && discount < original) {
            const diff = original - discount;
            const percent = Math.round((diff / original) * 100);
            previewPercent.innerText = `-${percent}%`;
            previewText.innerText = `Diskon Aktif! Hemat Rp${diff.toLocaleString('id-ID')}`;
            previewOrig.innerText = `Rp${original.toLocaleString('id-ID')}`;
            previewFinal.innerText = `Rp${discount.toLocaleString('id-ID')}`;
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', calcDiscountPreview);
</script>
@endsection
