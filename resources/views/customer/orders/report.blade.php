@extends('layouts.support')

@section('title', 'Pengaduan & Komplain Pesanan #' . $order->id . ' - JastipKuy')

@section('content')
<div class="min-h-screen bg-[#F8FAFC] pb-24 text-slate-800" x-data="reportForm()">
    <!-- Top Sticky Header -->
    <div class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-slate-100 px-4 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.orders.show', $order->id) }}" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest block">Bantuan & Sengketa</span>
                <h1 class="font-extrabold text-sm text-slate-900">Pengaduan Pesanan #{{ $order->id }}</h1>
            </div>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg {{ $order->status === 'bermasalah' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
            {{ strtoupper(str_replace('_', ' ', $order->status)) }}
        </span>
    </div>

    <div class="max-w-xl mx-auto p-4 space-y-4">

        <!-- Flash Message Alerts -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3 shadow-xs animate-fade-in">
                <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 text-sm font-black">✓</div>
                <div>
                    <h4 class="font-black text-xs text-emerald-900">Pengaduan Terkirim</h4>
                    <p class="text-xs text-emerald-700 mt-0.5 leading-relaxed">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3 shadow-xs animate-fade-in">
                <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 text-sm font-black">!</div>
                <div>
                    <h4 class="font-black text-xs text-rose-900">Terjadi Kendala</h4>
                    <p class="text-xs text-rose-700 mt-0.5 leading-relaxed">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-700 space-y-1">
                <p class="font-bold">Mohon perbaiki data berikut:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Order Summary Card -->
        <div class="bg-white rounded-3xl p-4 border border-slate-100 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span class="text-xs font-bold text-slate-500">Pesanan Jastip</span>
                </div>
                <span class="text-xs font-black text-rose-600">
                    Rp {{ number_format($order->agreed_fare + $order->approvedPriceAdjustmentsTotal(), 0, ',', '.') }}
                </span>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200/60 flex items-center justify-center text-xl shrink-0">
                    🛍️
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-extrabold text-sm text-slate-900 line-clamp-1">{{ $order->description }}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Jastiper: <strong class="text-slate-800">{{ $order->jastiper?->name ?? 'Belum ada jastiper' }}</strong>
                    </p>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        Tujuan: {{ Str::limit($order->destination_address, 45) }}
                    </p>
                </div>
            </div>
        </div>

        @if($pendingReport)
            <!-- Active Investigation Callout -->
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-3xl p-5 shadow-xs space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 text-lg shadow-sm">
                        ⚖️
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-sm text-amber-950">Pengaduan Sedang Diinvestigasi</h3>
                            <span class="px-2 py-0.5 text-[9px] font-black rounded-md bg-amber-200 text-amber-900">#REP-{{ $pendingReport->id }}</span>
                        </div>
                        <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                            Pengaduan Anda telah tercatat dan pembayaran pesanan telah ditahan otomatis di virtual escrow. Tim Admin JastipKuy sedang meninjau bukti yang Anda lampirkan.
                        </p>
                    </div>
                </div>

                <div class="bg-white/80 rounded-2xl p-4 border border-amber-100 space-y-2 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Kategori Masalah:</span>
                        <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                            {{ str_replace('_', ' ', $pendingReport->category) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Waktu Pengajuan:</span>
                        <span class="font-semibold text-slate-700">{{ $pendingReport->created_at->format('d M Y, H:i') }} WIB</span>
                    </div>
                    <div class="pt-1">
                        <span class="text-slate-500 font-medium block mb-1">Rincian Kendala Anda:</span>
                        <p class="text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed font-normal">
                            {{ $pendingReport->description }}
                        </p>
                    </div>

                    @if($pendingReport->evidence_image)
                        <div class="pt-2">
                            <span class="text-slate-500 font-medium block mb-1">Foto Bukti Terlampir:</span>
                            <a href="{{ asset('storage/' . $pendingReport->evidence_image) }}" target="_blank" class="block group relative overflow-hidden rounded-xl border border-slate-200 max-w-[200px]">
                                <img src="{{ asset('storage/' . $pendingReport->evidence_image) }}" alt="Bukti Komplain" class="w-full h-36 object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-[10px] font-bold text-white">
                                    Buka Foto
                                </div>
                            </a>
                        </div>
                    @endif
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="button"
                        @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $order->id }}, orderLabel: '{{ addslashes($order->description) }}' } }))"
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 rounded-xl transition text-center shadow-xs">
                        Buka Chat Pesanan
                    </button>
                    <a href="{{ route('customer.orders.show', $order->id) }}" class="flex-1 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs py-2.5 rounded-xl border border-slate-200 transition text-center">
                        Kembali ke Rincian
                    </a>
                </div>
            </div>
        @else
            <!-- Complaint Submission Form -->
            <form action="{{ route('customer.orders.report.store', $order->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <!-- Buyer Protection Guarantee Notice -->
                <div class="bg-blue-50 border border-blue-200/80 rounded-3xl p-4 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 text-sm font-black">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-xs text-blue-950">JastipKuy Buyer Protection</h4>
                        <p class="text-[11px] text-blue-800 mt-0.5 leading-relaxed">
                            Dana pembayaran Anda aman di Virtual Escrow. Jika barang tidak sesuai, rusak, atau kurang, Admin akan menahan pencairan dan mengembalikan dana ke Dompet Anda setelah verifikasi.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-xs space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-2">
                            Pilih Kategori Masalah <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label class="flex items-center gap-2.5 p-3 rounded-2xl border transition cursor-pointer"
                                :class="category === 'barang_tidak_sesuai' ? 'border-rose-500 bg-rose-50/50 text-rose-950 ring-1 ring-rose-500' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="category" value="barang_tidak_sesuai" x-model="category" class="text-rose-600 focus:ring-rose-500">
                                <div>
                                    <span class="text-xs font-bold block">Barang Tidak Sesuai</span>
                                    <span class="text-[10px] text-slate-500 block leading-tight">Varian/ukuran/warna berbeda</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-2xl border transition cursor-pointer"
                                :class="category === 'barang_rusak' ? 'border-rose-500 bg-rose-50/50 text-rose-950 ring-1 ring-rose-500' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="category" value="barang_rusak" x-model="category" class="text-rose-600 focus:ring-rose-500">
                                <div>
                                    <span class="text-xs font-bold block">Barang Rusak / Basi</span>
                                    <span class="text-[10px] text-slate-500 block leading-tight">Cacat fisik, pecah, atau basi</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-2xl border transition cursor-pointer"
                                :class="category === 'barang_kurang' ? 'border-rose-500 bg-rose-50/50 text-rose-950 ring-1 ring-rose-500' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="category" value="barang_kurang" x-model="category" class="text-rose-600 focus:ring-rose-500">
                                <div>
                                    <span class="text-xs font-bold block">Pesanan / Item Kurang</span>
                                    <span class="text-[10px] text-slate-500 block leading-tight">Ada barang yang tertinggal</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-2xl border transition cursor-pointer"
                                :class="category === 'selisih_harga_ongkir' ? 'border-rose-500 bg-rose-50/50 text-rose-950 ring-1 ring-rose-500' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="category" value="selisih_harga_ongkir" x-model="category" class="text-rose-600 focus:ring-rose-500">
                                <div>
                                    <span class="text-xs font-bold block">Selisih Biaya / Struk</span>
                                    <span class="text-[10px] text-slate-500 block leading-tight">Kelebihan tagihan/ongkir</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-2xl border transition cursor-pointer sm:col-span-2"
                                :class="category === 'lainnya' ? 'border-rose-500 bg-rose-50/50 text-rose-950 ring-1 ring-rose-500' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="category" value="lainnya" x-model="category" class="text-rose-600 focus:ring-rose-500">
                                <div>
                                    <span class="text-xs font-bold block">Masalah Lainnya</span>
                                    <span class="text-[10px] text-slate-500 block leading-tight">Kendala di luar kategori di atas</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Description Textarea -->
                    <div>
                        <label for="description" class="block text-xs font-black text-slate-800 mb-1.5">
                            Jelaskan Ketidaksesuaian / Kendala <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            required
                            placeholder="Contoh: Barang yang diantar varian cokelat padahal pesanan saya varian keju. Kemasan juga robek saat diserahkan jastiper..."
                            class="w-full text-xs rounded-2xl border border-slate-200 p-3 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 leading-relaxed"></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">Minimal 10 karakter agar admin dapat memahami kronologi masalah dengan akurat.</p>
                    </div>

                    <!-- Evidence Photo Upload -->
                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1.5">
                            Unggah Foto Bukti Fisik / Barang (Mendukung iPhone HEIC, JPG, PNG, WEBP)
                        </label>
                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-4 text-center hover:bg-slate-50 transition relative">
                            <input
                                type="file"
                                name="evidence_image"
                                accept="image/*,.heic,.heif,image/heic,image/heif"
                                @change="previewImage($event)"
                                class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
                            <div x-show="!imagePreview" class="space-y-1">
                                <svg class="w-8 h-8 text-slate-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-xs font-bold text-slate-700 block">Pilih atau Seret Foto Bukti di sini</span>
                                <span class="text-[10px] text-slate-400 block">Format: JPG, PNG, WEBP, HEIC (iPhone) • Maksimal 20MB</span>
                            </div>
                            <div x-show="imagePreview" class="relative inline-block mt-1">
                                <img :src="imagePreview" alt="Preview Bukti" class="h-40 rounded-xl object-cover border border-slate-200 shadow-sm"
                                     x-on:error="previewError = true" x-show="!previewError">
                                <div x-show="isHeic" class="mt-2 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-mono text-[11px] font-bold border border-slate-200">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Foto iPhone (HEIC): <span x-text="fileName" class="truncate max-w-[180px]"></span>
                                    </span>
                                </div>
                                <button type="button" @click="clearImage()" class="absolute -top-2 -right-2 bg-slate-900 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shadow-md">✕</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs py-3.5 rounded-2xl transition shadow-xs flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Kirim Pengaduan ke Admin</span>
                </button>
            </form>
        @endif

    </div>
</div>

<script>
    function reportForm() {
        return {
            category: 'barang_tidak_sesuai',
            imagePreview: null,
            fileName: '',
            isHeic: false,
            previewError: false,
            previewImage(event) {
                const file = event.target.files[0];
                if (file) {
                    this.fileName = file.name;
                    const ext = (file.name.split('.').pop() || '').toLowerCase();
                    this.isHeic = (ext === 'heic' || ext === 'heif' || file.type.includes('heic') || file.type.includes('heif'));
                    this.previewError = false;
                    this.imagePreview = URL.createObjectURL(file);
                }
            },
            clearImage() {
                this.imagePreview = null;
                this.fileName = '';
                this.isHeic = false;
                this.previewError = false;
                const input = document.querySelector('input[name="evidence_image"]');
                if (input) input.value = '';
            }
        };
    }
</script>
@endsection
