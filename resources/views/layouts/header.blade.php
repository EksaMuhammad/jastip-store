<!-- Top Notification Bar -->
<div class="bg-slate-900 text-white py-2 px-4 text-center text-xs font-medium tracking-wide">
    🚀 JastipKuy Malang: Layanan Kini Tersedia di Area Malang Raya & Sekitarnya!
</div>

<!-- Navbar Header -->
<header id="navbar" class="sticky top-0 z-50 bg-white border-b border-slate-200/80 shadow-sm backdrop-blur-md bg-white/95">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 sm:h-20">
            <!-- Logo Section -->
            <a href="{{ url('/') }}" class="group flex items-center gap-3">
                <div class="relative w-9 h-9">
                    <div class="absolute inset-0 bg-rose-600 rounded-sm transform rotate-6 transition-transform group-hover:rotate-12 duration-300"></div>
                    <div class="absolute inset-0 bg-slate-900 rounded-sm flex items-center justify-center border border-slate-800 shadow-sm">
                        <span class="font-display font-black text-white text-xs tracking-tighter">JK</span>
                    </div>
                </div>
                <span class="font-display font-extrabold text-2xl text-slate-900 tracking-tight">
                    Jastip<span class="text-rose-600">Kuy</span>
                </span>
            </a>

            <!-- Navigation Links -->
            @if(request()->is('customer/*', 'customer'))
                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ route('customer.dashboard') }}" class="text-sm font-semibold {{ request()->is('customer/dashboard') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Beranda</a>
                    <a href="{{ route('customer.orders.create') }}" class="text-sm font-semibold {{ request()->is('customer/orders/create') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Pesan Jastip</a>
                    <a href="#" onclick="showMaintenanceToast(event)" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Riwayat</a>
                    <a href="#" onclick="showMaintenanceToast(event)" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Profil Saya</a>
                </nav>
            @elseif(request()->is('jastiper/*', 'jastiper'))
                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ route('jastiper.dashboard') }}" class="text-sm font-semibold {{ request()->is('jastiper/dashboard') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Beranda</a>
                    <a href="{{ route('jastiper.area') }}" class="text-sm font-semibold {{ request()->is('jastiper/area') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Area Kerja</a>
                    <a href="{{ route('jastiper.verification') }}" class="text-sm font-semibold {{ request()->is('jastiper/verification') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Verifikasi Akun</a>
                    <a href="#" onclick="showMaintenanceToast(event)" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Profil Jastiper</a>
                </nav>
            @else
                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ request()->is('/') ? '#hero' : url('/#hero') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Beranda</a>
                    <a href="{{ request()->is('/') ? '#cara-kerja' : url('/#cara-kerja') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Cara Kerja</a>
                    <a href="{{ request()->is('/') ? '#layanan' : url('/#layanan') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Layanan</a>
                    <a href="{{ request()->is('/') ? '#keunggulan' : url('/#keunggulan') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Mengapa Kami</a>
                    <a href="{{ request()->is('/') ? '#testimoni' : url('/#testimoni') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Testimoni</a>
                </nav>
            @endif

            <!-- Authentication / Action Buttons -->
            <div class="flex items-center gap-3">
                @if (Auth::guard('customer')->check())
                    <a href="{{ route('customer.dashboard') }}" id="btn-dashboard" class="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Dashboard
                    </a>
                @elseif (Auth::guard('jastiper')->check())
                    <a href="{{ route('jastiper.dashboard') }}" id="btn-dashboard" class="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Dashboard
                    </a>
                @elseif (Auth::guard('admin')->check())
                    <a href="{{ route('admin.verification') }}" id="btn-dashboard" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Admin Panel
                    </a>
                @else
                    <a href="{{ route('login') }}" id="btn-login" class="inline-flex items-center justify-center border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2.5 rounded-sm transition">
                        Masuk
                    </a>
                    <a href="{{ route('login') }}" id="btn-register" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Daftar Sekarang
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
