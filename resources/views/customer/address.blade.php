@extends('layouts.support')

@section('title', 'Alamat Tersimpan - JastipKuy')

@section('content')
<div class="min-h-screen bg-[#F8FAFC] pb-28">

    <!-- Top Header Bar with Back to Profile -->
    <div class="bg-white border-b border-slate-200/80 sticky top-0 z-30 px-4 py-3.5 shadow-2xs">
        <div class="max-w-2xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('customer.profile') }}" 
                   class="w-10 h-10 rounded-full bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 transition flex items-center justify-center border border-slate-200 cursor-pointer group"
                   title="Kembali ke Profil">
                    <svg class="w-5 h-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h1 class="font-display font-black text-lg text-slate-900 leading-tight">Alamat Tersimpan</h1>
                    <p class="text-[11px] text-slate-500 font-medium">Ambil & kelola alamat pengantaran belanja</p>
                </div>
            </div>

            <!-- Quick Link to Order Form -->
            <a href="{{ route('customer.booking') }}" 
               class="hidden sm:inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 px-3 py-1.5 rounded-xl transition">
                <span>Buat Pesanan</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-4 py-6 space-y-6" x-data="{ showForm: {{ empty($savedAddresses) ? 'true' : 'false' }} }">

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl flex items-center gap-3 text-xs font-bold shadow-xs">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-xs font-bold space-y-1">
                @foreach($errors->all() as $err)
                    <p>⚠️ {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <!-- Card: Tambah Alamat Baru -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div>
                    <h2 class="font-display font-extrabold text-base text-slate-900">Kelola Alamat Pengantaran</h2>
                    <p class="text-xs text-slate-500">Simpan beberapa alamat (Rumah, Kantor, Kos) untuk checkout lebih cepat.</p>
                </div>
            </div>

            <!-- Tombol Tambah Alamat (Jika form sedang tertutup) -->
            <div x-show="!showForm" class="pt-1">
                <button type="button" 
                        @click="showForm = true"
                        class="w-full bg-rose-600 hover:bg-rose-700 active:scale-[0.99] text-white font-bold text-sm py-3.5 px-4 rounded-2xl transition shadow-md shadow-rose-600/20 flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah Alamat</span>
                </button>
            </div>

            <!-- Form Tambah Alamat Baru -->
            <form x-show="showForm" 
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0 -translate-y-2"
                  x-transition:enter-end="opacity-100 translate-y-0"
                  id="form-save-address" 
                  action="{{ route('customer.address.save') }}" 
                  method="POST" 
                  class="space-y-4 pt-3 border-t border-slate-100">
                @csrf
                <input type="hidden" name="latitude" id="input-lat">
                <input type="hidden" name="longitude" id="input-lng">

                <!-- Opsi Ambil Alamat GPS Otomatis -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-3.5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">📍 Deteksi Cepat via GPS Ponsel</span>
                        <span id="gps-status" class="text-[11px] text-slate-500 font-medium"></span>
                    </div>
                    <button type="button" 
                            id="btn-get-gps"
                            onclick="detectCurrentLocation()"
                            class="w-full bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 font-bold text-xs py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
                        <svg id="gps-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span id="gps-label">Pakai Titik GPS Saat Ini</span>
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Nama / Label Alamat</label>
                    <div class="flex gap-2 flex-wrap mb-2">
                        <button type="button" onclick="setLabel('Rumah')" class="px-3 py-1 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 rounded-lg text-xs font-bold text-slate-700 transition">Rumah</button>
                        <button type="button" onclick="setLabel('Kantor')" class="px-3 py-1 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 rounded-lg text-xs font-bold text-slate-700 transition">Kantor</button>
                        <button type="button" onclick="setLabel('Kos / Apartemen')" class="px-3 py-1 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 rounded-lg text-xs font-bold text-slate-700 transition">Kos</button>
                        <button type="button" onclick="setLabel('Toko')" class="px-3 py-1 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 rounded-lg text-xs font-bold text-slate-700 transition">Toko</button>
                        <button type="button" onclick="setLabel('Orang Tua')" class="px-3 py-1 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 rounded-lg text-xs font-bold text-slate-700 transition">Ortu</button>
                    </div>
                    <input type="text" name="label" id="input-label" placeholder="Contoh: Rumah, Kos Putri, Kantor" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-2.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-200 outline-none" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Alamat Lengkap</label>
                    <textarea name="address" id="input-address" rows="3" class="w-full text-xs font-semibold border border-slate-200 rounded-xl p-3 focus:border-rose-500 focus:ring-1 focus:ring-rose-200 outline-none leading-relaxed" placeholder="Alamat lengkap (nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota)..." required></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Patokan / Catatan Pengantaran (Opsional)</label>
                    <input type="text" name="notes" id="input-notes" placeholder="Contoh: Pagar hijau, depan warkop, titip di sekuriti pos satpam" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-2.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-200 outline-none">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 px-4 rounded-xl transition shadow-sm shadow-rose-600/20 cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <span>Simpan Alamat Ini</span>
                    </button>
                    @if(!empty($savedAddresses) && count($savedAddresses) > 0)
                        <button type="button" @click="showForm = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-3 px-4 rounded-xl transition cursor-pointer">
                            Batal
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Daftar Alamat Tersimpan -->
        <div class="space-y-3">
            <div class="px-1">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">
                    Daftar Alamat Saya @if(!empty($savedAddresses)) ({{ count($savedAddresses) }}) @endif
                </h3>
            </div>

            @if(!empty($savedAddresses) && count($savedAddresses) > 0)
                <div class="space-y-3">
                    @foreach($savedAddresses as $addr)
                        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs space-y-3 transition hover:border-rose-200">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0 font-bold text-sm">
                                        @if(stripos($addr['label'] ?? '', 'kantor') !== false)
                                            🏢
                                        @elseif(stripos($addr['label'] ?? '', 'kos') !== false)
                                            🏠
                                        @elseif(stripos($addr['label'] ?? '', 'toko') !== false)
                                            🏪
                                        @else
                                            📍
                                        @endif
                                    </div>
                                    <div class="space-y-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="font-extrabold text-sm text-slate-900 leading-tight">{{ $addr['label'] }}</h4>
                                            @if(!empty($addr['created_at']))
                                                <span class="text-[10px] text-slate-400 font-medium">{{ $addr['created_at'] }}</span>
                                            @endif
                                            <span class="text-emerald-700 bg-emerald-50 border border-emerald-200/80 text-[9px] font-extrabold px-1.5 py-0.5 rounded-md">
                                                Siap Pakai
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-700 leading-relaxed font-medium break-words">{{ $addr['address'] }}</p>
                                        @if(!empty($addr['notes']))
                                            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium pt-0.5">
                                                <span class="text-rose-500">📍</span>
                                                <span class="italic">Patokan: {{ $addr['notes'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi per Alamat -->
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                                <a href="{{ route('customer.booking') }}?saved_address_id={{ $addr['id'] ?? '' }}" 
                                   class="inline-flex items-center gap-1.5 font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 px-3 py-1.5 rounded-xl transition">
                                    <span>Gunakan untuk Pesan</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>

                                <form action="{{ route('customer.address.delete', $addr['id'] ?? '') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alamat {{ addslashes($addr['label'] ?? '') }} ini?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 text-slate-400 hover:text-rose-600 font-bold transition px-2 py-1 rounded-lg hover:bg-rose-50 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Default / Empty State -->
                <div class="bg-white rounded-3xl p-8 border border-slate-200 text-center space-y-3 shadow-2xs">
                    <div class="w-14 h-14 bg-rose-50 text-rose-600 border border-rose-100 rounded-2xl flex items-center justify-center mx-auto text-2xl">
                        📍
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-800">Belum Ada Alamat Tersimpan</h4>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 leading-relaxed">
                            Simpan alamat rumah, kantor, atau kos Anda dengan menekan tombol <strong>"+ Tambah Alamat"</strong> di atas.
                        </p>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    function setLabel(val) {
        document.getElementById('input-label').value = val;
    }

    function detectCurrentLocation() {
        const btn = document.getElementById('btn-get-gps');
        const icon = document.getElementById('gps-icon');
        const label = document.getElementById('gps-label');
        const status = document.getElementById('gps-status');

        if (!navigator.geolocation) {
            alert('Browser Anda tidak mendukung Geolocation GPS.');
            return;
        }

        label.innerText = 'Mencari titik GPS...';
        btn.classList.add('opacity-80');
        status.innerText = 'Mendeteksi sensor GPS...';

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                document.getElementById('input-lat').value = lat;
                document.getElementById('input-lng').value = lng;
                status.innerText = 'Titik GPS ditemukan. Mengambil nama jalan...';

                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                        headers: { 'Accept-Language': 'id' }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        const a = data.address || {};
                        let cleanAddr = [
                            a.road || a.amenity || a.building || '',
                            a.neighbourhood || a.suburb || a.village || '',
                            a.city || a.town || a.county || '',
                            a.state || ''
                        ].filter(Boolean).join(', ');

                        if (!cleanAddr) cleanAddr = data.display_name;

                        document.getElementById('input-address').value = cleanAddr;
                        if (!document.getElementById('input-label').value) {
                            document.getElementById('input-label').value = 'Rumah (GPS)';
                        }
                        status.innerText = '✓ Lokasi GPS berhasil terbaca.';
                    } else {
                        document.getElementById('input-address').value = `Area Pengantaran (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                        status.innerText = '✓ Titik GPS terbaca.';
                    }
                } catch (e) {
                    document.getElementById('input-address').value = `Area Pengantaran (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                    status.innerText = '✓ Titik GPS terbaca.';
                }

                label.innerText = 'Ambil Ulang GPS 📍';
                btn.classList.remove('opacity-80');
            },
            (error) => {
                btn.classList.remove('opacity-80');
                label.innerText = 'Pakai Titik GPS Saat Ini';
                status.innerText = 'Gagal mengakses GPS.';
                alert('Gagal mendeteksi lokasi GPS. Pastikan izin lokasi (Location Permission) diaktifkan pada browser/ponsel Anda.');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }
</script>
@endsection
