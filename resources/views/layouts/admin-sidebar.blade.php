<!-- UNIFIED ADMIN SIDEBAR: Fixed, persistent across all admin pages -->
<aside class="w-72 bg-slate-950 text-slate-350 flex flex-col justify-between shrink-0 border-r border-slate-800 hidden lg:flex sticky top-0 h-screen select-none">
    <div class="flex flex-col min-h-0 flex-1">
        <!-- Sidebar Header Brand -->
        <div class="h-16 flex items-center px-6 border-b border-slate-850 gap-3 shrink-0">
            <div class="w-8 h-8 bg-slate-800 border border-slate-700 rounded-xl flex items-center justify-center font-bold text-white text-sm">
                JK
            </div>
            <div>
                <h3 class="font-bold text-sm text-white tracking-wide">JastipKuy</h3>
                <span class="text-[9px] font-semibold text-slate-400 tracking-wider uppercase block -mt-0.5 font-mono">Admin Portal</span>
            </div>
        </div>

        <!-- Profile Summary Box -->
        <div class="p-5 border-b border-slate-900 bg-slate-950 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-850 border border-slate-750 rounded-full flex items-center justify-center font-bold text-white text-xs shrink-0 shadow-inner">
                    AD
                </div>
                <div class="min-w-0">
                    <h4 class="font-bold text-xs text-white truncate">Administrator</h4>
                    <span class="text-[10px] text-slate-400 block mt-0.5 truncate">admin@jastipkuy.com</span>
                </div>
            </div>
        </div>

        <!-- Sidebar Navigation Menu with Categorized Segments -->
        <nav class="p-3.5 space-y-4 overflow-y-auto flex-1 custom-scrollbar">

            <!-- SEGMEN 1: MENU UTAMA -->
            <div class="space-y-1">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block px-3 mb-1.5">Menu Utama</span>

                <!-- Dashboard Utama -->
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Utama</span>
                    </div>
                    @if(request()->routeIs('admin.dashboard'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Master Mutasi Saldo -->
                <a href="{{ route('admin.mutations.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.mutations.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.mutations.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span>Master Mutasi Saldo</span>
                    </div>
                    @if(request()->routeIs('admin.mutations.*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Monitoring & Log Aktivitas Seluruh User -->
                <a href="{{ route('admin.activities.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.activities.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.activities.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>Monitoring Log Aktivitas</span>
                    </div>
                    @if(request()->routeIs('admin.activities.*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>
            </div>

            <!-- SEGMEN 2: OPERASIONAL & VERIFIKASI -->
            <div class="space-y-1">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block px-3 mb-1.5">Operasional</span>

                <!-- Verifikasi Pembayaran -->
                <a href="{{ route('admin.payments') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.payments*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.payments*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Verifikasi Pembayaran</span>
                    </div>
                    @if(request()->routeIs('admin.payments*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Verifikasi Jastiper -->
                <a href="{{ route('admin.verification') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.verification*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.verification*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Verifikasi Jastiper</span>
                    </div>
                    @if(request()->routeIs('admin.verification*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Withdraw Jastiper -->
                <a href="{{ route('admin.withdraws') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.withdraws*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.withdraws*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Withdraw Jastiper</span>
                    </div>
                    @if(request()->routeIs('admin.withdraws*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Pengaduan & Sengketa Komplain -->
                @php
                    $pendingReportsCount = \App\Models\Report::where('status', 'menunggu')->count();
                @endphp
                <a href="{{ route('admin.reports.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.reports*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.reports*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Pengaduan & Komplain</span>
                    </div>
                    @if($pendingReportsCount > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-900/60 text-rose-300 border border-rose-800/80">
                            {{ $pendingReportsCount }}
                        </span>
                    @elseif(request()->routeIs('admin.reports*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Live Chat Bantuan Pelanggan (Customer Support) -->
                @php
                    $unreadSupportAdminCount = \App\Models\SupportMessage::unreadForAdmin()->count();
                @endphp
                <a href="{{ route('admin.support.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.support*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.support*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        <span>Chat Bantuan CS</span>
                    </div>
                    @if($unreadSupportAdminCount > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-900/60 text-rose-300 border border-rose-800/80">
                            {{ $unreadSupportAdminCount }}
                        </span>
                    @elseif(request()->routeIs('admin.support*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>
            </div>

            <!-- SEGMEN 3: DATA MASTER & MITRA -->
            <div class="space-y-1">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block px-3 mb-1.5">Master Data & Mitra</span>

                <!-- Kelola Merchant -->
                <a href="{{ route('admin.merchants.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ (request()->routeIs('admin.merchants.*') || request()->routeIs('admin.products.*')) ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ (request()->routeIs('admin.merchants.*') || request()->routeIs('admin.products.*')) ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Kelola Merchant</span>
                    </div>
                    @if(request()->routeIs('admin.merchants.*') || request()->routeIs('admin.products.*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Manajemen Promo -->
                <a href="{{ route('admin.promos.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.promos.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.promos.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Manajemen Promo</span>
                    </div>
                    @if(request()->routeIs('admin.promos.*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Daftar Pelanggan -->
                <a href="{{ route('admin.customers.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.customers.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.customers.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                    @if(request()->routeIs('admin.customers.*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Daftar Jastiper -->
                <a href="{{ route('admin.jastipers.index') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.jastipers.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.jastipers.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Daftar Jastiper</span>
                    </div>
                    @if(request()->routeIs('admin.jastipers.*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Kelola Wilayah -->
                <button type="button" onclick="if(typeof showMaintenanceToast === 'function') { showMaintenanceToast(event); } else { alert('Fitur Kelola Wilayah sedang dalam pemeliharaan.'); }" 
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-850 font-medium text-xs transition text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Kelola Wilayah</span>
                    </div>
                </button>
            </div>

            <!-- SEGMEN 4: PENGATURAN SISTEM -->
            <div class="space-y-1">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block px-3 mb-1.5">Pengaturan Sistem</span>

                <!-- Pengaturan Tarif & Biaya -->
                <a href="{{ route('admin.settings.tariffs') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.settings.tariffs*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.settings.tariffs*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        <span>Pengaturan Tarif & Biaya</span>
                    </div>
                    @if(request()->routeIs('admin.settings.tariffs*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>

                <!-- Gateway Midtrans -->
                <a href="{{ route('admin.settings.midtrans') }}" 
                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ request()->routeIs('admin.settings.midtrans*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-850 font-medium' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.settings.midtrans*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Gateway Midtrans</span>
                    </div>
                    @if(request()->routeIs('admin.settings.midtrans*'))
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    @endif
                </a>
            </div>

        </nav>
    </div>

    <!-- Sidebar Footer Action (Logout) -->
    <div class="p-3.5 border-t border-slate-900 bg-slate-950 shrink-0">
        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-850 font-medium text-xs transition text-left cursor-pointer">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Keluar Sistem</span>
            </button>
        </form>
    </div>
</aside>
