<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Customer Service Helpdesk - JastipKuy</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body, h1, h2, h3, h4, .font-display {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-[#F3F4F6] text-slate-800 antialiased selection:bg-slate-900 selection:text-white min-h-screen flex items-center justify-center p-4">

    <div class="relative w-full max-w-md my-8">
        <!-- Logo Section -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center font-bold text-sm shadow-xs">
                    CS
                </div>
                <div class="text-left">
                    <span class="font-display font-extrabold text-2xl text-slate-900 tracking-tight block">
                        Jastip<span class="text-slate-600">Kuy</span>
                    </span>
                    <span class="text-[9px] uppercase bg-slate-200 text-slate-700 font-bold tracking-wider px-2 py-0.5 rounded-md inline-block font-mono">
                        CS Helpdesk Portal
                    </span>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-1">Portal Khusus Petugas Customer Support & Resolusi Sengketa</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white border border-slate-200 shadow-xs rounded-2xl p-6 sm:p-8 space-y-5">

            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 p-3 text-emerald-800 text-xs font-semibold rounded-xl flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-rose-50 border border-rose-200 p-3 text-rose-800 text-xs font-semibold rounded-xl flex items-start gap-2">
                    <svg class="w-4 h-4 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('cs.login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Email CS -->
                <div class="space-y-1">
                    <label for="email" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Email Petugas CS</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email', 'cs1@jastipkuy.com') }}"
                        required 
                        autofocus
                        placeholder="cs1@jastipkuy.com"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition"
                    >
                </div>

                <!-- Password -->
                <div class="space-y-1">
                    <label for="password" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Kata Sandi</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        value="password123"
                        required 
                        placeholder="••••••••"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition"
                    >
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center text-xs text-slate-600 select-none cursor-pointer">
                        <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span class="ml-2 font-medium">Ingat sesi saya</span>
                    </label>
                    <span class="text-[10px] text-slate-500 font-mono">Demo: password123</span>
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                        Masuk ke Workspace CS
                    </button>
                </div>
            </form>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <a href="{{ route('admin.login') }}" class="hover:text-slate-700 transition">
                    Portal Admin →
                </a>
                <a href="{{ route('login') }}" class="hover:text-slate-700 transition">
                    Portal Pengguna →
                </a>
            </div>
        </div>
    </div>

</body>
</html>
