@extends('layouts.support')

@section('title', 'Panel Admin - Pengaturan Tarif & Ketentuan Jastip')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F8FAFC]">
    
    <!-- LEFT SIDEBAR: Unified Persistent Component -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Top Navbar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dashboard') }}" class="p-2 -ml-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition lg:hidden">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </a>
                <div>
                    <h1 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">Master Pengaturan Tarif</h1>
                    <p class="text-[11px] text-slate-500 -mt-0.5 font-normal">Konfigurasi dinamis ongkir per kilometer, tambahan kategori, berat muatan, dan komisi platform</p>
                </div>
            </div>

            <!-- Segmented Control Tabs (Tarif vs Midtrans) -->
            <div class="flex items-center bg-slate-100 p-0.5 rounded-xl border border-slate-200 text-xs font-semibold">
                <a href="{{ route('admin.settings.tariffs') }}" class="px-3.5 py-1.5 rounded-lg bg-white text-slate-900 shadow-2xs transition">
                    Tarif & Biaya
                </a>
                <a href="{{ route('admin.settings.midtrans') }}" class="px-3.5 py-1.5 rounded-lg text-slate-500 hover:text-slate-900 transition">
                    Midtrans Gateway
                </a>
            </div>
        </header>

        <!-- Main Body -->
        <div class="p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

            <!-- Alerts -->
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-2.5 text-xs font-medium shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-xs shadow-2xs">
                    <p class="font-bold mb-1">Periksa kembali data input Anda:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.settings.tariffs.update') }}" method="POST" id="tariffForm">
                @csrf

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                    
                    <!-- LEFT 8 COLUMNS: Form Settings -->
                    <div class="xl:col-span-8 space-y-6">

                        <!-- SECTION 1: Tarif Jarak & Rute Pengantaran -->
                        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-2xs space-y-5">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 tracking-tight">Tarif Jarak & Rute Pengantaran</h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Komponen biaya jarak berdasarkan pergerakan kilometer GPS armada</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Tarif per KM -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Tarif per Kilometer
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="rate_per_km" 
                                            id="input_rate_per_km"
                                            value="{{ old('rate_per_km', $tariffs['rate_per_km']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-400">Biaya dikalikan dengan total jarak rute.</p>
                                </div>

                                <!-- Tarif Minimum (Min Fare) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Tarif Minimum Pesanan
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="min_fare" 
                                            id="input_min_fare"
                                            value="{{ old('min_fare', $tariffs['min_fare']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-400">Batas bawah ongkir terendah untuk jarak dekat.</p>
                                </div>

                                <!-- Tambahan Titik Ekstra / Multi-Stop -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Biaya Titik Ekstra Multi-Stop
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="extra_stop_fare" 
                                            id="input_extra_stop_fare"
                                            value="{{ old('extra_stop_fare', $tariffs['extra_stop_fare']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-400">Biaya per alamat ekstra mulai titik ke-3.</p>
                                </div>

                                <!-- Jarak Dasar Termasuk Base Fare (KM) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Jarak Dasar Ongkir Minimum
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <input 
                                            type="number" 
                                            name="base_included_km" 
                                            value="{{ old('base_included_km', $tariffs['base_included_km']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-3.5 pr-10 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="0.5"
                                        >
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-xs font-semibold text-slate-400">KM</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Jarak gratis awal sebelum tarif per KM bertambah.</p>
                                </div>

                                <!-- Multi Store: Tarif KM Tambahan Antar Toko -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Multi-Toko: Tarif KM Antar Merchant
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="multi_store_km_rate" 
                                            value="{{ old('multi_store_km_rate', $tariffs['multi_store_km_rate']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-400">Tarif per KM untuk pergerakan antar merchant.</p>
                                </div>

                                <!-- Multi Store: Biaya Ekstra Belanja Toko Tambahan -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Multi-Toko: Biaya Per Toko Tambahan
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="multi_store_extra_store_fare" 
                                            value="{{ old('multi_store_extra_store_fare', $tariffs['multi_store_extra_store_fare']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-400">Kompensasi belanja & parkir di toko berikutnya.</p>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: Biaya Tambahan Kategori Layanan (Tabel Rapi) -->
                        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-2xs space-y-4">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 tracking-tight">Biaya Tambahan Kategori Layanan</h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Penyesuaian biaya penanganan berdasarkan tipe aktivitas jastip yang diminta</p>
                                </div>
                            </div>

                            <div class="overflow-hidden border border-slate-200 rounded-xl">
                                <table class="w-full text-left text-xs divide-y divide-slate-100">
                                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-[10px] tracking-wider">
                                        <tr>
                                            <th class="px-4 py-3">Kategori Layanan</th>
                                            <th class="px-4 py-3 hidden sm:table-cell">Deskripsi Operasional</th>
                                            <th class="px-4 py-3 text-right w-44">Biaya Tambahan (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <!-- Beli & Antar -->
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                Beli & Antar (Custom)
                                                <span class="block text-[10px] text-slate-400 font-normal sm:hidden">Belanja pesanan offline</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] hidden sm:table-cell">
                                                Jastiper mencari & berbelanja di warung/toko fisik non-katalog
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="relative inline-flex items-center rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition w-36">
                                                    <span class="pl-2.5 text-[11px] font-semibold text-slate-400">Rp</span>
                                                    <input 
                                                        type="number" 
                                                        name="category_beli_antar" 
                                                        id="input_category_beli_antar"
                                                        value="{{ old('category_beli_antar', $tariffs['category_beli_antar']) }}" 
                                                        class="w-full bg-transparent py-1.5 pl-1.5 pr-2.5 text-right text-xs font-semibold text-slate-800 focus:outline-none"
                                                        required min="0" step="500"
                                                    >
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Ambil & Antar -->
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                Ambil & Antar
                                                <span class="block text-[10px] text-slate-400 font-normal sm:hidden">Pick-up paket siap ambil</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] hidden sm:table-cell">
                                                Penjemputan barang atau pesanan yang sudah dibayar di lokasi awal
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="relative inline-flex items-center rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition w-36">
                                                    <span class="pl-2.5 text-[11px] font-semibold text-slate-400">Rp</span>
                                                    <input 
                                                        type="number" 
                                                        name="category_ambil_antar" 
                                                        id="input_category_ambil_antar"
                                                        value="{{ old('category_ambil_antar', $tariffs['category_ambil_antar']) }}" 
                                                        class="w-full bg-transparent py-1.5 pl-1.5 pr-2.5 text-right text-xs font-semibold text-slate-800 focus:outline-none"
                                                        required min="0" step="500"
                                                    >
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Toko & Kirim -->
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                Toko & Kirim (Katalog)
                                                <span class="block text-[10px] text-slate-400 font-normal sm:hidden">Belanja merchant mitra</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] hidden sm:table-cell">
                                                Belanja produk dari katalog merchant terdaftar dalam aplikasi
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="relative inline-flex items-center rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition w-36">
                                                    <span class="pl-2.5 text-[11px] font-semibold text-slate-400">Rp</span>
                                                    <input 
                                                        type="number" 
                                                        name="category_toko_kirim" 
                                                        id="input_category_toko_kirim"
                                                        value="{{ old('category_toko_kirim', $tariffs['category_toko_kirim']) }}" 
                                                        class="w-full bg-transparent py-1.5 pl-1.5 pr-2.5 text-right text-xs font-semibold text-slate-800 focus:outline-none"
                                                        required min="0" step="500"
                                                    >
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Titip Dokumen -->
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                Titip Dokumen
                                                <span class="block text-[10px] text-slate-400 font-normal sm:hidden">Surat & berkas penting</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] hidden sm:table-cell">
                                                Pengantaran berkas, map dokumen, atau surat bersegel langsung
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="relative inline-flex items-center rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition w-36">
                                                    <span class="pl-2.5 text-[11px] font-semibold text-slate-400">Rp</span>
                                                    <input 
                                                        type="number" 
                                                        name="category_dokumen" 
                                                        id="input_category_dokumen"
                                                        value="{{ old('category_dokumen', $tariffs['category_dokumen']) }}" 
                                                        class="w-full bg-transparent py-1.5 pl-1.5 pr-2.5 text-right text-xs font-semibold text-slate-800 focus:outline-none"
                                                        required min="0" step="500"
                                                    >
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Multi-Stop -->
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                Multi-Stop (Titik Banyak)
                                                <span class="block text-[10px] text-slate-400 font-normal sm:hidden">Rute multi tujuan</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] hidden sm:table-cell">
                                                Tarif dasar handling untuk order dengan rute beberapa perhentian
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="relative inline-flex items-center rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition w-36">
                                                    <span class="pl-2.5 text-[11px] font-semibold text-slate-400">Rp</span>
                                                    <input 
                                                        type="number" 
                                                        name="category_multi_stop" 
                                                        id="input_category_multi_stop"
                                                        value="{{ old('category_multi_stop', $tariffs['category_multi_stop']) }}" 
                                                        class="w-full bg-transparent py-1.5 pl-1.5 pr-2.5 text-right text-xs font-semibold text-slate-800 focus:outline-none"
                                                        required min="0" step="500"
                                                    >
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Kirim Pihak Ketiga -->
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                Kirim Ekspedisi Logistik
                                                <span class="block text-[10px] text-slate-400 font-normal sm:hidden">Drop counter ekspedisi</span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] hidden sm:table-cell">
                                                Jastiper drop paket ke gerai logistik (JNE, J&T, SiCepat, Pos)
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="relative inline-flex items-center rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition w-36">
                                                    <span class="pl-2.5 text-[11px] font-semibold text-slate-400">Rp</span>
                                                    <input 
                                                        type="number" 
                                                        name="category_kirim_pihak_ketiga" 
                                                        id="input_category_kirim_pihak_ketiga"
                                                        value="{{ old('category_kirim_pihak_ketiga', $tariffs['category_kirim_pihak_ketiga']) }}" 
                                                        class="w-full bg-transparent py-1.5 pl-1.5 pr-2.5 text-right text-xs font-semibold text-slate-800 focus:outline-none"
                                                        required min="0" step="500"
                                                    >
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SECTION 3: Tambahan Kategori Berat -->
                        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-2xs space-y-4">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 tracking-tight">Biaya Tambahan Berdasarkan Berat Muatan</h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Kompensasi beban muatan kendaraan untuk barang sedang dan berat</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Ringan -->
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800">Ringan</span>
                                        <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/70 px-2 py-0.5 rounded">&lt; 5 kg</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Makanan, pakaian, belanjaan ringan.</p>
                                    <div class="relative rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="weight_ringan" 
                                            id="input_weight_ringan"
                                            value="{{ old('weight_ringan', $tariffs['weight_ringan']) }}" 
                                            class="w-full bg-transparent py-2 pl-8 pr-2.5 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                </div>

                                <!-- Sedang -->
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800">Sedang</span>
                                        <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/70 px-2 py-0.5 rounded">5 - 15 kg</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Kardus belanja, galon kecil, helm.</p>
                                    <div class="relative rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="weight_sedang" 
                                            id="input_weight_sedang"
                                            value="{{ old('weight_sedang', $tariffs['weight_sedang']) }}" 
                                            class="w-full bg-transparent py-2 pl-8 pr-2.5 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                </div>

                                <!-- Berat -->
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800">Berat</span>
                                        <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/70 px-2 py-0.5 rounded">&gt; 15 kg</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Galon besar, tabung gas, karung beras.</p>
                                    <div class="relative rounded-lg border border-slate-200 bg-white focus-within:border-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="weight_berat" 
                                            id="input_weight_berat"
                                            value="{{ old('weight_berat', $tariffs['weight_berat']) }}" 
                                            class="w-full bg-transparent py-2 pl-8 pr-2.5 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" step="500"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 4: Ketentuan Finansial Platform -->
                        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-2xs space-y-4">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-xs sm:text-sm text-slate-900 tracking-tight">Ketentuan Finansial & Transaksi</h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Persentase bagi hasil platform, tenggat waktu bayar, dan nominal top up dompet</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Komisi Platform -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Komisi Platform
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <input 
                                            type="number" 
                                            name="platform_commission_percent" 
                                            value="{{ old('platform_commission_percent', $tariffs['platform_commission_percent']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-3.5 pr-8 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="0" max="100" step="0.5"
                                        >
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-xs font-semibold text-slate-400">%</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Potongan dari ongkir pesanan selesai.</p>
                                </div>

                                <!-- Batas Waktu Pembayaran (Menit) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Batas Waktu Bayar
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <input 
                                            type="number" 
                                            name="payment_deadline_minutes" 
                                            value="{{ old('payment_deadline_minutes', $tariffs['payment_deadline_minutes']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-3.5 pr-14 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="5" step="5"
                                        >
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-[11px] font-semibold text-slate-400">Menit</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Tenggat waktu pelunasan tiket QRIS/VA.</p>
                                </div>

                                <!-- Minimal Top Up E-Wallet -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Minimal Top Up Dompet
                                    </label>
                                    <div class="relative rounded-xl border border-slate-200 bg-white focus-within:border-slate-800 focus-within:ring-1 focus-within:ring-slate-800 transition">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-semibold text-slate-400">Rp</span>
                                        <input 
                                            type="number" 
                                            name="min_topup_amount" 
                                            value="{{ old('min_topup_amount', $tariffs['min_topup_amount']) }}" 
                                            class="w-full bg-transparent py-2.5 pl-10 pr-3 text-xs font-semibold text-slate-800 focus:outline-none"
                                            required min="1000" step="1000"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-400">Batas minimal isi saldo customer.</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT 4 COLUMNS: Actions & Clean Live Simulator -->
                    <div class="xl:col-span-4 space-y-6 xl:sticky xl:top-24">

                        <!-- Action Submit Box -->
                        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-2xs space-y-3">
                            <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider">Aksi Penyimpanan</h4>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                Pengaturan tarif yang disimpan akan langsung berlaku di seluruh kalkulasi pesanan customer dan jastiper secara real-time.
                            </p>

                            <div class="pt-1">
                                <button 
                                    type="submit"
                                    class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs tracking-wide transition shadow-sm flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span>Simpan Perubahan Tarif</span>
                                </button>
                            </div>
                        </div>

                        <!-- KALKULATOR SIMULASI REAL-TIME (Sleek SaaS Card) -->
                        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-2xs space-y-4">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-xs text-slate-900 tracking-tight">Simulasi Tarif Live</h4>
                                        <span class="text-[10px] text-slate-400">Pengujian kalkulasi otomatis</span>
                                    </div>
                                </div>
                                <span class="text-[10px] bg-slate-100 text-slate-600 font-mono font-medium px-2 py-0.5 rounded-md">Live Preview</span>
                            </div>

                            <!-- Slider Jarak -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-medium">
                                    <span class="text-slate-600">Jarak Rute:</span>
                                    <span class="font-bold text-slate-900 font-mono" id="sim_distance_display">5.0 KM</span>
                                </div>
                                <input 
                                    type="range" 
                                    id="sim_distance" 
                                    min="0.5" 
                                    max="30" 
                                    step="0.5" 
                                    value="5.0" 
                                    class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-slate-900"
                                >
                            </div>

                            <!-- Pilihan Kategori -->
                            <div class="space-y-1 text-xs">
                                <label class="text-slate-600 font-medium">Kategori Layanan:</label>
                                <select id="sim_category" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-xl p-2 font-medium focus:outline-none focus:border-slate-800">
                                    <option value="beli-antar">Beli & Antar (Custom)</option>
                                    <option value="ambil-antar">Ambil & Antar</option>
                                    <option value="toko-kirim">Toko & Kirim (Katalog)</option>
                                    <option value="dokumen">Titip Dokumen</option>
                                    <option value="multi-stop">Multi-Stop</option>
                                    <option value="kirim-pihak-ketiga">Kirim ke Ekspedisi</option>
                                </select>
                            </div>

                            <!-- Pilihan Berat -->
                            <div class="space-y-1 text-xs">
                                <label class="text-slate-600 font-medium">Kategori Berat:</label>
                                <select id="sim_weight" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs rounded-xl p-2 font-medium focus:outline-none focus:border-slate-800">
                                    <option value="ringan">Ringan (&lt; 5 kg)</option>
                                    <option value="sedang">Sedang (5 - 15 kg)</option>
                                    <option value="berat">Berat (&gt; 15 kg)</option>
                                </select>
                            </div>

                            <!-- Box Hasil Estimasi -->
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-2 mt-2">
                                <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Total Estimasi Ongkir:</div>
                                <div class="text-2xl font-black text-slate-900 font-mono tracking-tight" id="sim_result_fare">
                                    Rp 30.000
                                </div>
                                <div class="text-[10px] text-slate-500 pt-1.5 border-t border-slate-200 leading-relaxed font-mono" id="sim_breakdown">
                                    (5 KM × Rp 5.000) + Kategori (Rp 5.000) + Berat (Rp 0) = Rp 30.000
                                </div>
                            </div>
                        </div>

                        <!-- Reset to Factory Settings -->
                        <div class="pt-2 text-center">
                            <button 
                                type="button" 
                                onclick="document.getElementById('resetTariffForm').submit()" 
                                class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-500 hover:text-rose-600 transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Kembalikan ke Nilai Default Awal</span>
                            </button>
                        </div>

                    </div>

                </div>
            </form>

            <!-- Hidden Reset Form -->
            <form id="resetTariffForm" action="{{ route('admin.settings.tariffs.reset') }}" method="POST" class="hidden" onsubmit="return confirm('Apakah Anda yakin ingin mereset seluruh pengaturan tarif ke nilai standar pabrikan?')">
                @csrf
            </form>

        </div>

    </main>
</div>

<!-- Script Live Calculator Simulation -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const simDistance = document.getElementById('sim_distance');
        const simDistanceDisplay = document.getElementById('sim_distance_display');
        const simCategory = document.getElementById('sim_category');
        const simWeight = document.getElementById('sim_weight');
        const simResultFare = document.getElementById('sim_result_fare');
        const simBreakdown = document.getElementById('sim_breakdown');

        function formatRupiah(num) {
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }

        function calculateLive() {
            const distance = parseFloat(simDistance.value) || 0;
            simDistanceDisplay.textContent = distance.toFixed(1) + ' KM';

            const ratePerKm = parseFloat(document.getElementById('input_rate_per_km').value) || 5000;
            const minFare = parseFloat(document.getElementById('input_min_fare').value) || 10000;

            const category = simCategory.value;
            const weight = simWeight.value;

            let catAddition = 0;
            if (category === 'beli-antar') catAddition = parseFloat(document.getElementById('input_category_beli_antar').value) || 5000;
            else if (category === 'ambil-antar') catAddition = parseFloat(document.getElementById('input_category_ambil_antar').value) || 3000;
            else if (category === 'toko-kirim') catAddition = parseFloat(document.getElementById('input_category_toko_kirim').value) || 2000;
            else if (category === 'dokumen') catAddition = parseFloat(document.getElementById('input_category_dokumen').value) || 0;
            else if (category === 'multi-stop') catAddition = parseFloat(document.getElementById('input_category_multi_stop').value) || 15000;
            else if (category === 'kirim-pihak-ketiga') catAddition = parseFloat(document.getElementById('input_category_kirim_pihak_ketiga').value) || 7000;

            let weightAddition = 0;
            if (weight === 'ringan') weightAddition = parseFloat(document.getElementById('input_weight_ringan').value) || 0;
            else if (weight === 'sedang') weightAddition = parseFloat(document.getElementById('input_weight_sedang').value) || 10000;
            else if (weight === 'berat') weightAddition = parseFloat(document.getElementById('input_weight_berat').value) || 25000;

            const kmCost = distance * ratePerKm;
            let rawTotal = kmCost + catAddition + weightAddition;
            let finalFare = Math.max(minFare, rawTotal);
            finalFare = Math.ceil(finalFare / 500) * 500;

            simResultFare.textContent = formatRupiah(finalFare);
            simBreakdown.textContent = `(${distance} KM × ${formatRupiah(ratePerKm)}) + Kat (${formatRupiah(catAddition)}) + Berat (${formatRupiah(weightAddition)}) = ${formatRupiah(finalFare)}`;
        }

        simDistance.addEventListener('input', calculateLive);
        simCategory.addEventListener('change', calculateLive);
        simWeight.addEventListener('change', calculateLive);

        document.querySelectorAll('#tariffForm input').forEach(input => {
            input.addEventListener('input', calculateLive);
        });

        calculateLive();
    });
</script>
@endsection
