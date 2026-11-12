@extends('layouts.support')

@section('title', 'Keamanan Akun & Kata Sandi - JastipKuy')

@section('content')
<div class="min-h-screen bg-[#F8FAFC] pb-28">

    <!-- Top Header Bar with Back to Profile -->
    <div class="bg-white border-b border-slate-200/80 sticky top-0 z-30 px-4 py-3.5 shadow-2xs">
        <div class="max-w-xl mx-auto flex items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('customer.profile') }}" 
                   class="w-10 h-10 rounded-full bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 transition flex items-center justify-center border border-slate-200 cursor-pointer group"
                   title="Kembali ke Profil">
                    <svg class="w-5 h-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h1 class="font-display font-black text-lg text-slate-900 leading-tight">Keamanan Akun</h1>
                    <p class="text-[11px] text-slate-500 font-medium">Ubah kata sandi & pengaturan akses login</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-xl mx-auto px-4 py-6 space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl flex items-start gap-3 text-xs font-bold shadow-xs">
                <span class="text-base leading-none">✅</span>
                <div class="flex-1">
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl flex items-start gap-3 text-xs shadow-xs">
                <span class="text-base leading-none">⚠️</span>
                <div class="space-y-1">
                    <p class="font-bold">Terjadi kendala pada pengisian formulir:</p>
                    <ul class="list-disc pl-4 space-y-0.5 font-medium">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Status Keamanan Akun Card --}}
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl {{ $hasPassword ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }} flex items-center justify-center shrink-0">
                    @if($hasPassword)
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    @else
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="font-display font-black text-sm sm:text-base text-slate-900 truncate">
                            {{ $hasPassword ? 'Kata Sandi Telah Aktif' : 'Login Mode OTP WhatsApp' }}
                        </h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $hasPassword ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $hasPassword ? 'Terlindungi' : 'Perlu Kata Sandi' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $hasPassword 
                            ? 'Akun Anda dilindungi dengan kata sandi mandiri. Anda dapat mengubahnya kapan saja.' 
                            : 'Akun Anda masuk via OTP WhatsApp. Buat kata sandi baru atau gunakan kata sandi default agar dapat login menggunakan password.' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Opsi 1-Click Default Password jika belum memiliki kata sandi --}}
        @if(!$hasPassword)
            <div class="bg-gradient-to-r from-rose-50 via-rose-50/60 to-orange-50 rounded-3xl p-5 border border-rose-200/80 shadow-xs space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm shadow-rose-200 font-black text-sm">
                        ⚡
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display font-extrabold text-sm text-rose-900">Pilihan Cepat: Kata Sandi Default</h3>
                        <p class="text-xs text-rose-700/90 mt-0.5">
                            Ingin langsung mengaktifkan kata sandi tanpa mengetik? Gunakan kata sandi standar: <span class="font-mono font-bold bg-white/80 px-2 py-0.5 rounded border border-rose-200 text-rose-800">password123</span>
                        </p>
                    </div>
                </div>

                <button type="button" 
                        onclick="applyDefaultPassword()" 
                        class="w-full bg-rose-600 hover:bg-rose-700 active:scale-[0.99] text-white font-bold text-xs py-3 px-4 rounded-xl transition shadow-md shadow-rose-600/20 flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Terapkan Kata Sandi Default (password123)</span>
                </button>
            </div>
        @endif

        {{-- Formulir Kata Sandi --}}
        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 space-y-5">
            <div>
                <h3 class="font-display font-black text-base text-slate-900">
                    {{ $hasPassword ? 'Ubah Kata Sandi Akun' : 'Buat Kata Sandi Baru' }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ $hasPassword ? 'Masukkan kata sandi lama Anda lalu tentukan kata sandi baru.' : 'Tentukan kata sandi baru untuk akun JastipKuy Anda.' }}
                </p>
            </div>

            <form id="password-form" action="{{ route('customer.security.password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Current Password (hanya tampil jika akun sudah memiliki password sebelumnya) --}}
                @if($hasPassword)
                    <div>
                        <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   required 
                                   autocomplete="current-password"
                                   placeholder="Masukkan kata sandi lama"
                                   class="w-full text-sm border-slate-200 rounded-xl px-4 py-3 pr-10 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 transition">
                            <button type="button" 
                                    onclick="togglePasswordVisibility('current_password', 'eye-curr')"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                <svg id="eye-curr" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- New Password --}}
                <div>
                    <label for="new_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                        {{ $hasPassword ? 'Kata Sandi Baru' : 'Kata Sandi' }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="new_password" 
                               name="password" 
                               required 
                               minlength="6"
                               autocomplete="new-password"
                               placeholder="Minimal 6 karakter"
                               class="w-full text-sm border-slate-200 rounded-xl px-4 py-3 pr-10 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 transition">
                        <button type="button" 
                                onclick="togglePasswordVisibility('new_password', 'eye-new')"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <svg id="eye-new" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required 
                               minlength="6"
                               autocomplete="new-password"
                               placeholder="Ketik ulang kata sandi baru"
                               class="w-full text-sm border-slate-200 rounded-xl px-4 py-3 pr-10 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 transition">
                        <button type="button" 
                                onclick="togglePasswordVisibility('password_confirmation', 'eye-confirm')"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <svg id="eye-confirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full bg-slate-900 hover:bg-slate-800 active:scale-[0.99] text-white font-bold text-sm py-3.5 px-4 rounded-xl transition shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ $hasPassword ? 'Perbarui Kata Sandi' : 'Simpan Kata Sandi' }}</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Tips Keamanan --}}
        <div class="bg-slate-100/80 rounded-2xl p-4 text-xs text-slate-600 space-y-2">
            <div class="flex items-center gap-2 font-bold text-slate-700">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Tips Keamanan Akun JastipKuy:</span>
            </div>
            <ul class="list-disc pl-5 space-y-1 text-slate-500 text-[11px]">
                <li>Gunakan kombinasi minimal 6 karakter dengan huruf dan angka.</li>
                <li>Jangan pernah membagikan kode OTP atau kata sandi kepada siapa pun termasuk kurir maupun tim support JastipKuy.</li>
                <li>Jika lupa kata sandi di kemudian hari, Anda tetap dapat masuk via nomor WhatsApp terdaftar.</li>
            </ul>
        </div>

    </div>
</div>

<script>
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
    }
}

function applyDefaultPassword() {
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('password_confirmation');
    if (newPass && confirmPass) {
        newPass.value = 'password123';
        confirmPass.value = 'password123';
        document.getElementById('password-form').submit();
    }
}
</script>
@endsection

