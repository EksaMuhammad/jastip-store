@extends('layouts.support')

@section('title', 'Profil Saya')

@section('content')
<div class="min-h-screen bg-[#F8FAFC]">

    {{-- ===== HEADER ===== --}}
    <div class="relative bg-rose-600 pt-5 pb-20 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full"></div>
        <div class="absolute top-10 -right-4 w-28 h-28 bg-white/10 rounded-full"></div>
        <div class="absolute -bottom-6 left-1/3 w-24 h-24 bg-white/10 rounded-full"></div>

        <div class="relative z-10 max-w-lg mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('customer.dashboard') }}" class="text-white/80 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="font-display font-black text-xl text-white tracking-tight">Profil Saya</h1>
            </div>
            
            <button type="button" onclick="showMaintenanceToast(event)" class="w-8 h-8 rounded-full bg-white/15 text-white flex items-center justify-center text-xs font-bold hover:bg-white/25 transition">
                ?
            </button>
        </div>
    </div>

    {{-- ===== MAIN CONTENT CONTAINER ===== --}}
    <div class="max-w-lg mx-auto px-4 -mt-14 pb-28 space-y-4 relative z-10">

        {{-- ===== USER PROFILE CARD (Gojek Reference Style) ===== --}}
        <div class="bg-white rounded-3xl shadow-[0_8px_30px_-4px_rgba(0,0,0,0.08)] border border-slate-100 p-5 overflow-hidden">
            <div class="flex items-center gap-4">
                <!-- Avatar Circle -->
                <div class="relative shrink-0">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center text-white font-black text-xl font-display shadow-lg shadow-rose-200 ring-4 ring-white">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                </div>

                <!-- User Details -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display font-black text-base text-slate-900 truncate leading-tight">{{ $customer->name }}</h2>
                        <button type="button" onclick="document.getElementById('editProfileModal').classList.remove('hidden')" class="text-slate-400 hover:text-slate-600 transition" title="Edit Profil">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-1.5 mt-1 text-slate-500">
                        <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <p class="text-xs font-semibold text-slate-600">+62 {{ $customer->phone_number }}</p>
                    </div>

                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full">
                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Akun Aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Membership / Rewards Banner Pill (Gojek Reference Style) -->
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between bg-amber-500/10 border border-amber-400/30 p-2.5 rounded-2xl cursor-pointer hover:bg-amber-500/20 transition" onclick="showMaintenanceToast(event)">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    <span class="text-xs font-extrabold text-amber-900">JastipKuy Plus Member</span>
                </div>
                <div class="flex items-center gap-1 text-[11px] font-bold text-amber-700">
                    <span>Reward Eksklusif</span>
                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </div>

            <!-- Quick Stats Row -->
            <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-3 gap-3 text-center">
                <div>
                    <div class="font-display font-black text-lg text-slate-900">{{ $completedOrdersCount ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">Order Selesai</div>
                </div>
                <div class="border-x border-slate-100">
                    <div class="font-display font-black text-lg text-rose-600">Rp {{ number_format($balance, 0, ',', '.') }}</div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">Saldo JK Pay</div>
                </div>
                <div>
                    <div class="font-display font-black text-lg text-slate-900">{{ $favoriteCount ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">Jastiper Favorit</div>
                </div>
            </div>
        </div>



        {{-- ===== SECTION 1: PREFERENSI / PENGATURAN AKUN ===== --}}
        <div>
            <p class="text-xs font-black text-slate-400 uppercase tracking-widest px-1 mb-2">Preferensi Akun</p>
            <div class="bg-white rounded-3xl shadow-[0_4px_15px_-4px_rgba(0,0,0,0.06)] border border-slate-100 overflow-hidden divide-y divide-slate-100">

                <!-- 1. Keamanan Akun -->
                <button type="button" onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition group text-left">
                    <div class="flex items-center gap-3.5">
                        <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-rose-50 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition">Keamanan Akun</span>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">Kata sandi, OTP & verifikasi perangkat</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </button>

                <!-- 2. Metode Pembayaran & Dompet -->
                <a href="{{ route('customer.wallet') }}" class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="flex items-center gap-3.5">
                        <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-rose-50 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition">Metode Pembayaran & Saldo</span>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">Saldo JK PAY: <b class="text-rose-600">Rp {{ number_format($balance, 0, ',', '.') }}</b></p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <!-- 3. Alamat Tersimpan -->
                <a href="{{ route('customer.booking') }}" class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="flex items-center gap-3.5">
                        <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-rose-50 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition">Alamat Tersimpan</span>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">Rumah, kantor & lokasi favorit di Malang</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <!-- 4. Jastiper Favorit -->
                <a href="{{ route('customer.booking') }}" class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="flex items-center gap-3.5">
                        <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-rose-50 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition">Jastiper Favorit Saya</span>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">{{ $favoriteCount }} mitra terdaftar di favorit</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

            </div>
        </div>

        {{-- ===== SECTION 2: BANTUAN & INFORMASI ===== --}}
        <div>
            <p class="text-xs font-black text-slate-400 uppercase tracking-widest px-1 mb-2">Bantuan & Informasi</p>
            <div class="bg-white rounded-3xl shadow-[0_4px_15px_-4px_rgba(0,0,0,0.06)] border border-slate-100 overflow-hidden divide-y divide-slate-100">

                <a href="{{ route('faq') }}" class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="flex items-center gap-3.5">
                        <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-rose-50 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition">Pusat Bantuan & FAQ</span>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">Panduan penggunaan & kendala jastip</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('terms') }}" class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="flex items-center gap-3.5">
                        <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-rose-50 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition">Ketentuan Layanan</span>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">Syarat & aturan penggunaan JastipKuy</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('privacy') }}" class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="flex items-center gap-3.5">
                        <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-rose-50 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <div>
                            <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition">Kebijakan Privasi</span>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">Perlindungan & privasi data pengguna</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

            </div>
        </div>

        {{-- ===== SECTION 3: LOGOUT BUTTON ===== --}}
        <div class="pt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full bg-white border border-slate-200 hover:bg-rose-50 hover:border-rose-300 text-rose-600 font-bold text-sm py-4 rounded-3xl transition duration-150 flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Keluar dari Akun</span>
                </button>
            </form>
        </div>

        <p class="text-center text-[10px] text-slate-400 font-semibold pt-1">
            JastipKuy v1.0 &bull; Platform Jasa Titip On-Demand Terpercaya
        </p>

    </div>
</div>
@endsection

@section('scripts')
<div id="editProfileModal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('editProfileModal').classList.add('hidden')"></div>
    <div class="absolute bottom-0 left-0 right-0 max-w-lg mx-auto bg-white rounded-t-[32px] p-6 shadow-2xl transform transition-transform animate-slideUp">
        <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-6"></div>
        
        <h3 class="font-display font-black text-lg text-slate-800 mb-2">Edit Profil</h3>
        <p class="text-xs text-slate-500 mb-6">Ubah detail akun Anda di bawah ini.</p>

        <form action="{{ route('customer.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-700 mb-2">Nama Lengkap</label>
                <input type="text" name="name" value="{{ $customer->name }}" class="w-full text-sm border-slate-200 rounded-xl px-4 py-3 focus:border-rose-500 focus:ring focus:ring-rose-200 transition" required>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-700 mb-2">Nomor Telepon</label>
                <input type="text" value="+62 {{ $customer->phone_number }}" class="w-full text-sm border-slate-200 bg-slate-50 text-slate-500 rounded-xl px-4 py-3 cursor-not-allowed" disabled>
                <p class="text-[10px] text-slate-400 mt-1">Nomor telepon tidak dapat diubah dari sini.</p>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('editProfileModal').classList.add('hidden')" class="w-1/3 py-3.5 rounded-2xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition text-sm">
                    Batal
                </button>
                <button type="submit" class="w-2/3 py-3.5 rounded-2xl font-bold text-white bg-rose-600 hover:bg-rose-700 transition shadow-lg shadow-rose-500/30 text-sm">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function showMaintenanceToast(event) {
        event?.preventDefault();
        
        // Cek apakah ada toast lama, jika ada hapus
        const oldToast = document.getElementById('maintenance-toast');
        if (oldToast) oldToast.remove();

        const toast = document.createElement('div');
        toast.id = 'maintenance-toast';
        toast.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[100] bg-slate-800 text-white px-4 py-3 rounded-2xl shadow-xl flex items-center gap-3 animate-slideDown max-w-[90vw] w-[350px]';
        toast.innerHTML = `
            <div class="w-8 h-8 bg-slate-700 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold leading-tight">Fitur Belum Tersedia</p>
                <p class="text-[10px] text-slate-300 mt-0.5">Mohon maaf, halaman ini masih dalam tahap pengembangan.</p>
            </div>
        `;
        document.body.appendChild(toast);

        // Hapus setelah 3 detik
        setTimeout(() => {
            if(toast.parentElement) {
                toast.classList.replace('animate-slideDown', 'animate-slideUp');
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    }
</script>
<style>
    @keyframes slideDown {
        from { transform: translate(-50%, -100%); opacity: 0; }
        to { transform: translate(-50%, 0); opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .animate-slideDown {
        animation: slideDown 0.3s ease-out forwards;
    }
    .animate-slideUp {
        animation: slideUp 0.3s ease-out forwards;
    }
</style>
@endsection
