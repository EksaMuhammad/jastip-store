@extends('layouts.merchant')

@section('title', 'Kelola Kategori Produk Toko')

@section('content')
<div class="space-y-6" x-data="{ editModalOpen: false, editCatId: null, editCatName: '' }">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="font-display font-black text-2xl text-slate-900">Kelola Kategori Produk</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $merchant->type === 'mart' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                    {{ $merchant->type === 'mart' ? '🏪 Minimarket (Mart)' : '🍽️ Kuliner (Food)' }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Atur dan tambahkan kategori produk untuk toko <span class="font-bold text-slate-700">{{ $merchant->name }}</span>. Kategori yang Anda tambahkan di sini akan langsung tampil pada pilihan produk dan filter customer.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('merchant.products.index') }}" class="px-4 py-2.5 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Kembali ke Produk</span>
            </a>
            <a href="{{ route('merchant.products.create') }}" class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-rose-600 to-orange-500 hover:from-rose-500 hover:to-orange-400 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-rose-600/20 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                <span>+ Tambah Produk</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Input Kategori Baru -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm sticky top-24">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-sm">
                        🏷️
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-sm">Tambah Kategori Baru</h2>
                        <p class="text-[11px] text-slate-400">Input kategori langsung dari panel web</p>
                    </div>
                </div>

                <form action="{{ route('merchant.categories.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Kategori <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            required 
                            placeholder="{{ $merchant->type === 'mart' ? 'Misal: Rokok, Makanan, Minuman, Sembako' : 'Misal: Aneka Nasi, Minuman Segar, Camilan' }}" 
                            value="{{ old('name') }}"
                            class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none transition"
                        >
                        @error('name')
                            <p class="text-rose-600 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 text-[11px] text-slate-500 space-y-1">
                        <div class="font-bold text-slate-700 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Info Kategori:</span>
                        </div>
                        <p>Kategori yang dibuat akan langsung dapat dipilih saat menambahkan produk baru toko ini dan menjadi filter kategori di aplikasi pelanggan.</p>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-md flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        <span>Simpan Kategori</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Kategori -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900 text-sm">Daftar Kategori Tersedia ({{ $categories->count() }})</h2>
                        <p class="text-[11px] text-slate-400">Kategori aktif yang dapat digunakan untuk produk toko Anda</p>
                    </div>
                </div>

                @if($categories->isEmpty())
                    <div class="p-12 text-center space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 text-xl">
                            🏷️
                        </div>
                        <h3 class="font-bold text-slate-700 text-sm">Belum Ada Kategori</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Gunakan formulir di sebelah kiri untuk menginput kategori produk pertama Anda.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($categories as $category)
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/70 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl {{ $category->merchant_id ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center font-bold text-base shrink-0">
                                        🏷️
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 text-sm">{{ $category->name }}</span>
                                            @if($category->merchant_id === $merchant->id)
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">
                                                    Khusus Toko Anda
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-medium">
                                                    Kategori Standar
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-0.5">
                                            <a href="{{ route('merchant.products.index', ['category_id' => $category->id]) }}" class="hover:text-rose-600 hover:underline flex items-center gap-1 font-semibold text-slate-500">
                                                <span>{{ $category->products_count }} Produk Toko</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 sm:self-center self-end">
                                    @if($category->merchant_id === $merchant->id)
                                        <!-- Tombol Edit (Kategori Toko Mandiri) -->
                                        <button 
                                            type="button"
                                            @click="editCatId = {{ $category->id }}; editCatName = '{{ addslashes($category->name) }}'; editModalOpen = true"
                                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-1"
                                            title="Ubah Nama Kategori"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            <span>Ubah</span>
                                        </button>

                                        <!-- Tombol Hapus (Kategori Toko Mandiri) -->
                                        <form 
                                            action="{{ route('merchant.categories.destroy', $category->id) }}" 
                                            method="POST" 
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori \'{{ $category->name }}\'? Produk yang menggunakan kategori ini tidak akan terhapus, melainkan kategorinya menjadi kosong.');"
                                            class="inline"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition flex items-center gap-1"
                                                title="Hapus Kategori"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic px-2 py-1">
                                            Bawaan Sistem
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Modal Ubah Nama Kategori -->
    <div 
        x-show="editModalOpen" 
        style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
        x-cloak
    >
        <div 
            @click.away="editModalOpen = false" 
            class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 space-y-4"
        >
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-base">Ubah Nama Kategori</h3>
                <button @click="editModalOpen = false" class="p-1 rounded-xl text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('merchant/categories') }}/' + editCatId" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Kategori Baru <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        x-model="editCatName" 
                        required 
                        class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button 
                        type="button" 
                        @click="editModalOpen = false" 
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
