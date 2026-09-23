@extends('layouts.support')

@section('title', 'Panel Admin - Dashboard Utama')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]">
    
    <!-- LEFT SIDEBAR: Full height sticky -->
    <aside class="w-72 bg-slate-950 text-slate-350 flex flex-col justify-between shrink-0 border-r border-slate-800 hidden lg:flex sticky top-0 h-screen">
        <div>
            <!-- Sidebar Header Brand -->
            <div class="h-16 flex items-center px-6 border-b border-slate-850 gap-3">
                <div class="w-8 h-8 bg-rose-600 rounded-lg flex items-center justify-center font-bold text-white text-sm">
                    JK
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-white tracking-wide">JastipKuy</h3>
                    <span class="text-[8px] font-black text-rose-500 tracking-widest uppercase block -mt-0.5">ADMIN PORTAL</span>
                </div>
            </div>

            <!-- Profile Summary Box -->
            <div class="p-6 border-b border-slate-900 bg-slate-950">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-rose-500 rounded-full flex items-center justify-center font-bold text-white text-sm shrink-0">
                        AD
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-extrabold text-xs text-white truncate">Admin JastipKuy</h4>
                        <span class="text-[9px] text-slate-400 block mt-0.5 truncate">admin@jastipkuy.com</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigation Menu -->
            <nav class="p-4 space-y-1.5">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-wider block px-3 mb-2">Menu Utama</span>

                <!-- Menu: Dashboard Utama (ACTIVE) -->
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Utama</span>
                    </div>
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                </a>

                <!-- Menu: Verifikasi Jastiper -->
                <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Verifikasi Jastiper</span>
                    </div>
                </a>

                <!-- Menu: Verifikasi Pembayaran -->
                <a href="{{ route('admin.payments') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Verifikasi Pembayaran</span>
                    </div>
                </a>

                <!-- Menu: Kelola Wilayah -->
                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Kelola Wilayah</span>
                    </div>
                </button>

                <!-- Menu: Kelola Merchant -->
                <a href="{{ route('admin.merchants.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Kelola Merchant</span>
                    </div>
                </a>

                <!-- Menu: Manajemen Promo -->
                <a href="{{ route('admin.promos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Manajemen Promo</span>
                    </div>
                </a>

                <!-- Menu: Daftar Customer -->
                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </button>

                <!-- Menu: Withdraw Jastiper -->
                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Withdraw Jastiper</span>
                    </div>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer Action (Logout) -->
        <div class="p-4 border-t border-slate-900 bg-slate-950">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-rose-500 hover:bg-rose-500/10 font-bold text-xs transition text-left">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Keluar Panel</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- RIGHT CONTENT AREA: Header bar + dynamic content -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <!-- Mobile Sidebar Trigger -->
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Secure Admin Zone</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-900 rounded-full flex items-center justify-center font-bold text-white text-xs">
                    AD
                </div>
                <span class="text-xs font-bold text-slate-700 hidden sm:inline">Admin JastipKuy</span>
            </div>
        </header>

        <!-- Main Body Wrapper -->
        <main class="flex-grow p-6 lg:p-8 space-y-6">
            <div>
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-black text-slate-800">Manajemen Promo</h1>
                        <p class="text-xs text-slate-500 mt-1">Atur banner promo yang tayang di aplikasi pelanggan.</p>
                    </div>
                    <a href="{{ route('admin.promos.create') }}" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition">
                        + Tambah Promo
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="bg-emerald-50 text-emerald-600 px-4 py-3 rounded-2xl text-xs font-bold border border-emerald-100">
                {{ session('success') }}
            </div>
            @endif

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-[10px] uppercase tracking-wider border-b border-slate-200">
                            <th class="p-4 font-bold">Judul Promo</th>
                            <th class="p-4 font-bold">Tipe</th>
                            <th class="p-4 font-bold">Status</th>
                            <th class="p-4 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs">
                        @forelse($promos as $promo)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition">
                            <td class="p-4">
                                <p class="font-bold text-slate-800">{{ $promo->title }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ Str::limit($promo->description, 50) }}</p>
                            </td>
                            <td class="p-4">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-[10px] font-bold uppercase">{{ $promo->type }}</span>
                            </td>
                            <td class="p-4">
                                @if($promo->is_active)
                                    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-[10px] font-bold">Aktif</span>
                                @else
                                    <span class="bg-rose-100 text-rose-700 px-2.5 py-1 rounded-full text-[10px] font-bold">Nonaktif</span>
                                @endif
                            </td>
                            <td class="p-4 flex gap-2">
                                <a href="{{ route('admin.promos.edit', $promo->id) }}" class="p-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-lg transition">Edit</a>
                                <form action="{{ route('admin.promos.destroy', $promo->id) }}" method="POST" onsubmit="return confirm('Hapus promo ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 font-medium text-xs">Belum ada promo yang dibuat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </main>
        
    </div>

</div>

<!-- Mobile Sidebar Overlay (Dynamic via simple JS) -->
<div id="mobile-sidebar" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex">
    <div class="w-72 bg-slate-955 h-full flex flex-col justify-between p-4 shadow-xl text-slate-350">
        <div>
            <!-- Sidebar Header Brand -->
            <div class="h-16 flex items-center px-4 gap-3">
                <div class="w-8 h-8 bg-rose-600 rounded-lg flex items-center justify-center font-bold text-white text-sm">
                    JK
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-white tracking-wide">JastipKuy</h3>
                    <span class="text-[8px] font-black text-rose-500 tracking-widest uppercase block -mt-0.5">ADMIN PORTAL</span>
                </div>
            </div>

            <!-- Profile Summary Box -->
            <div class="p-4 border-b border-slate-900 bg-slate-950 mt-4 rounded-2xl">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-rose-500 rounded-full flex items-center justify-center font-bold text-white text-sm shrink-0">
                        AD
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-extrabold text-xs text-white truncate">Admin JastipKuy</h4>
                        <span class="text-[9px] text-slate-400 block mt-0.5 truncate">admin@jastipkuy.com</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigation Menu -->
            <nav class="mt-6 space-y-1.5">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-wider block px-3 mb-2">Menu Utama</span>

                <!-- Menu: Dashboard Utama -->
                <a href="{{ route('admin.dashboard') }}" onclick="toggleMobileSidebar()" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Utama</span>
                    </div>
                </a>

                <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Verifikasi Jastiper</span>
                    </div>
                </a>

                <a href="{{ route('admin.payments') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Verifikasi Pembayaran</span>
                    </div>
                </a>

                <button onclick="showMaintenanceToast(event); toggleMobileSidebar()" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Kelola Wilayah</span>
                    </div>
                </button>

                <a href="{{ route('admin.merchants.index') }}" onclick="toggleMobileSidebar()" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Kelola Merchant</span>
                    </div>
                </a>

                <!-- Menu: Manajemen Promo -->
                <a href="{{ route('admin.promos.index') }}" onclick="toggleMobileSidebar()" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Manajemen Promo</span>
                    </div>
                </a>

                <button onclick="showMaintenanceToast(event); toggleMobileSidebar()" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </button>

                <a href="{{ route('admin.withdraws') }}" onclick="toggleMobileSidebar()" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Withdraw Jastiper</span>
                    </div>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-900">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-rose-500 hover:bg-rose-500/10 font-bold text-xs transition text-left">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Keluar Panel</span>
                </button>
            </form>
        </div>
    </div>
    <!-- Overlay Click Close -->
    <div class="flex-grow h-full" onclick="toggleMobileSidebar()"></div>
</div>

<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        if (sidebar.classList.contains('hidden')) {
            sidebar.classList.remove('hidden');
        } else {
            sidebar.classList.add('hidden');
        }
    }
</script>
@endsection
