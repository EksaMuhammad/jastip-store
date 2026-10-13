@extends('layouts.support')

@section('title', 'Login Mitra Merchant')

@section('content')
<div class="relative min-h-[90vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-900 overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/20 rounded-full filter blur-3xl opacity-50 animate-pulse"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-rose-500/20 rounded-full filter blur-3xl opacity-50 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="relative z-10 w-full max-w-md">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-rose-500/30 text-white font-black text-xl">
                    🏪
                </div>
                <span class="font-display font-extrabold text-3xl text-white tracking-tight">
                    Jastip<span class="text-rose-500">Kuy</span> 
                    <span class="text-[10px] uppercase bg-rose-500/20 text-rose-300 font-bold tracking-widest px-2.5 py-1 rounded-full border border-rose-500/30 ml-1.5">Mitra Merchant</span>
                </span>
            </div>
            <p class="text-xs text-slate-400">Portal Kelola Menu & Produk Toko Khusus Mitra Merchant</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-950 border border-slate-800 shadow-2xl rounded-3xl p-6 sm:p-8 space-y-6">
            
            @if(session('success'))
                <div class="bg-emerald-500/10 border border-emerald-500/30 p-4 text-emerald-400 text-xs font-semibold rounded-2xl flex items-start gap-2.5">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/30 p-4 text-rose-400 text-xs font-semibold rounded-2xl flex items-start gap-2.5">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('merchant.login.submit') }}" method="POST" class="space-y-5">
                @csrf
                
                <!-- Email Address -->
                <div class="space-y-2">
                    <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Email Akun Merchant</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}"
                        required 
                        autocomplete="email"
                        placeholder="mitra@toko.com"
                        class="w-full bg-slate-900 border border-slate-800 text-white px-4 py-3 rounded-2xl text-sm font-semibold outline-none focus:border-rose-500 transition placeholder:text-slate-600"
                    >
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Kata Sandi (Password)</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full bg-slate-900 border border-slate-800 text-white px-4 py-3 rounded-2xl text-sm font-semibold outline-none focus:border-rose-500 transition placeholder:text-slate-600"
                    >
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-rose-600 focus:ring-0 focus:ring-offset-0"
                        >
                        <span class="text-xs text-slate-400">Ingat sesi saya</span>
                    </label>
                    <span class="text-[11px] text-slate-500">Didaftarkan oleh Admin</span>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-rose-600 to-orange-500 hover:from-rose-500 hover:to-orange-400 text-white font-bold py-3.5 px-4 rounded-2xl text-xs uppercase tracking-wider transition duration-150 shadow-lg shadow-rose-600/20 active:scale-[0.99] flex items-center justify-center gap-2"
                >
                    <span>Masuk ke Dashboard Toko</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </form>

            <div class="pt-2 text-center border-t border-slate-900">
                <p class="text-[11px] text-slate-500">
                    Belum memiliki akun toko? Silakan hubungi <b>Admin JastipKuy</b> untuk pendaftaran merchant baru.
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.login') }}" class="text-[10px] text-slate-500 hover:text-slate-400">Login Administrator Portal →</a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
