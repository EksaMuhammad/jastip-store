@extends('layouts.support')

@section('title', 'Admin Login')

@section('content')
<div class="relative min-h-[85vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-900 overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-500/20 rounded-full filter blur-3xl opacity-50 animate-pulse"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-rose-500/20 rounded-full filter blur-3xl opacity-50 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="relative z-10 w-full max-w-md">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-4">
                <div class="relative w-12 h-12">
                    <div class="absolute inset-0 bg-rose-600 rounded-sm transform rotate-6"></div>
                    <div class="absolute inset-0 bg-slate-950 rounded-sm flex items-center justify-center border border-slate-800 shadow-lg">
                        <span class="font-display font-black text-white text-sm tracking-tighter">ADM</span>
                    </div>
                </div>
                <span class="font-display font-extrabold text-3xl text-white tracking-tight">
                    Jastip<span class="text-rose-600">Kuy</span> <span class="text-xs uppercase bg-slate-800 text-slate-400 font-mono font-bold tracking-widest px-2.5 py-1 rounded-sm border border-slate-700 ml-1.5">Admin</span>
                </span>
            </div>
            <p class="text-xs text-slate-400">Silakan login menggunakan akun administrator Anda.</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-950 border-2 border-slate-800 shadow-[8px_8px_0px_0px_rgba(244,63,94,1)] rounded-sm p-6 sm:p-8 space-y-6">
            
            @if($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/30 p-4 text-rose-400 text-xs font-semibold rounded-sm flex items-start gap-2.5">
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

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-5">
                @csrf
                
                <!-- Email Address -->
                <div class="space-y-2">
                    <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Email Admin</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}"
                        required 
                        autocomplete="email"
                        placeholder="admin@jastipkuy.com"
                        class="w-full bg-slate-900 border-2 border-slate-800 text-white px-3.5 py-3 rounded-sm text-sm font-semibold outline-none focus:border-rose-600 transition"
                    >
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full bg-slate-900 border-2 border-slate-800 text-white px-3.5 py-3 rounded-sm text-sm font-semibold outline-none focus:border-rose-600 transition"
                    >
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input 
                        id="remember" 
                        name="remember" 
                        type="checkbox" 
                        class="h-4 w-4 rounded border-slate-800 text-rose-600 focus:ring-rose-500 bg-slate-900"
                    >
                    <label for="remember" class="ml-2 block text-xs text-slate-400 font-semibold cursor-pointer select-none">
                        Ingat sesi login saya
                    </label>
                </div>

                <!-- Action Button -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm py-3.5 rounded-sm border-2 border-slate-950 shadow-[4px_4px_0px_0px_rgba(255,255,255,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition duration-150 uppercase tracking-wide"
                    >
                        🔒 Masuk Ke Panel Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
