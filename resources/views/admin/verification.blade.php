@extends('layouts.support')

@section('title', 'Panel Admin - Verifikasi Akun Jastiper')

@section('content')
<div class="min-h-screen bg-[#F3F4F6] py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Flex Layout for Sidebar and Content -->
        <div class="flex flex-col lg:flex-row gap-8 items-start">
            
            <!-- SIDEBAR COLUMN -->
            <div class="w-full lg:w-[280px] shrink-0 space-y-6">
                <!-- Admin Brand & Profile Panel -->
                <div class="bg-slate-950 text-white rounded-3xl p-6 shadow-md border border-slate-800 relative overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-20 h-20 bg-white/5 rounded-full blur-lg"></div>
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black tracking-tighter bg-rose-600 text-white px-2.5 py-1 rounded-md uppercase">ADMIN PANEL</span>
                            <span class="text-[10px] font-bold text-slate-400">JastipKuy</span>
                        </div>
                        
                        <div class="pt-2 border-t border-slate-800 flex items-center gap-3">
                            <div class="w-10 h-10 bg-rose-500 rounded-full flex items-center justify-center font-bold text-white text-sm">
                                AD
                            </div>
                            <div>
                                <h4 class="font-extrabold text-xs text-white">Admin JastipKuy</h4>
                                <span class="text-[9px] text-slate-400 block mt-0.5">admin@jastipkuy.com</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu Sidebar (Gojek Admin Style) -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-sm space-y-2">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block px-3 mb-2">Menu Utama</span>
                    
                    <!-- Menu: Verifikasi Jastiper (ACTIVE) -->
                    <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-50 text-rose-600 font-bold text-xs transition">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span>Verifikasi Jastiper</span>
                        </div>
                        <span class="text-[8px] bg-rose-600 text-white font-bold px-2 py-0.5 rounded-full uppercase">Aktif</span>
                    </a>

                    <!-- Menu: Kelola Wilayah (MOCK) -->
                    <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-650 hover:bg-slate-50 font-bold text-xs transition">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Kelola Wilayah</span>
                        </div>
                    </button>

                    <!-- Menu: Daftar Mitra Jastiper (MOCK) -->
                    <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-650 hover:bg-slate-50 font-bold text-xs transition">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span>Mitra Jastiper</span>
                        </div>
                    </button>

                    <!-- Menu: Daftar Customer (MOCK) -->
                    <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-650 hover:bg-slate-50 font-bold text-xs transition">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Daftar Pelanggan</span>
                        </div>
                    </button>

                    <!-- Menu: Keuangan & Saldo (MOCK) -->
                    <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-650 hover:bg-slate-50 font-bold text-xs transition">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <span>Keuangan & Tarik Dana</span>
                        </div>
                    </button>

                    <!-- Menu: Logout (FORM) -->
                    <div class="pt-4 border-t border-slate-100 mt-2">
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-2xl text-rose-600 hover:bg-rose-50 font-bold text-xs transition text-left">
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                <span>Keluar Panel</span>
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- MAIN CONTENT COLUMN -->
            <div class="flex-grow w-full">
                <!-- Livewire Component -->
                @livewire('admin.admin-verification')
            </div>

        </div>

    </div>
</div>
@endsection
