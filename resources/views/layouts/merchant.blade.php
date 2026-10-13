<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Mitra Merchant') - JastipKuy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand -->
            <div class="flex items-center gap-4">
                <a href="{{ route('merchant.dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-500 flex items-center justify-center text-white text-lg shadow-md shadow-rose-500/20">
                        🏪
                    </div>
                    <div>
                        <span class="font-black text-slate-900 text-base leading-tight block">
                            Jastip<span class="text-rose-600">Kuy</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Mitra Merchant</span>
                    </div>
                </a>

                <!-- Store Badge -->
                <div class="hidden sm:flex items-center gap-2 pl-4 border-l border-slate-200">
                    <span class="text-xs font-bold text-slate-700">{{ auth()->guard('merchant')->user()->name }}</span>
                    @if(auth()->guard('merchant')->user()->type === 'food')
                        <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-[9px] font-bold uppercase tracking-wider">Kuliner (Food)</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[9px] font-bold uppercase tracking-wider">Minimarket (Mart)</span>
                    @endif
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('merchant.dashboard') }}" class="px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('merchant.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Dashboard
                </a>
                <a href="{{ route('merchant.products.index') }}" class="px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('merchant.products.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Kelola Produk
                </a>
                <a href="{{ route('merchant.categories.index') }}" class="px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('merchant.categories.*') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Kelola Kategori
                </a>
                <a href="{{ route('merchant.profile') }}" class="px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('merchant.profile') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Profil Toko
                </a>

                <!-- Logout -->
                <form action="{{ route('merchant.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold transition flex items-center gap-1.5" title="Keluar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span class="hidden md:inline">Keluar</span>
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-sm animate-fade-in">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2.5 shadow-sm animate-fade-in">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} JastipKuy Platform. Portal Mandiri Mitra Merchant.</p>
    </footer>

</body>
</html>
