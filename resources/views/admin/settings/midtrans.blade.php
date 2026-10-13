@extends('layouts.support')

@section('title', 'Panel Admin - Pengaturan Midtrans')

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

                <!-- Menu: Dashboard Utama -->
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard Utama</span>
                    </div>
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

                <!-- Menu: Gateway Midtrans (ACTIVE) -->
                <a href="{{ route('admin.settings.midtrans') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Gateway Midtrans</span>
                    </div>
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                </a>

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
                <a href="{{ route('admin.customers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </a>

                <!-- Menu: Daftar Jastiper -->
                <a href="{{ route('admin.jastipers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Jastiper</span>
                    </div>
                </a>

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

    <!-- RIGHT MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-slate-400">Portal Admin</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-bold text-slate-700">Pengaturan</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-extrabold text-rose-600">Payment Gateway Midtrans</span>
            </div>
            <div>
                <a href="{{ route('admin.payments') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-rose-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Kembali ke Verifikasi</span>
                </a>
            </div>
        </header>

        <!-- Main Body -->
        <div class="p-6 sm:p-8 max-w-6xl w-full mx-auto space-y-6">

            <!-- Title & Alerts -->
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Pengaturan Midtrans Payment Gateway</h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Kelola kredensial Midtrans Server Key, Client Key, dan environment langsung dari portal tanpa perlu sentuh kode program.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($isActive)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                GATEWAY AKTIF
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-slate-100 text-slate-600 border border-slate-300">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                GATEWAY NONAKTIF
                            </span>
                        @endif

                        @if($isProduction)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-purple-50 text-purple-700 border border-purple-200">
                                🚀 PRODUCTION (LIVE)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-700 border border-amber-200">
                                🧪 SANDBOX (TESTING)
                            </span>
                        @endif
                    </div>
                </div>

                @if(session('success'))
                    <div class="mt-4 bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex items-center gap-3 text-emerald-800 text-xs font-bold shadow-xs">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
            </div>

            <!-- Form & Live Connection Test Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Form Kolom Kiri (2 Kolom) -->
                <div class="lg:col-span-2 space-y-6">
                    <form action="{{ route('admin.settings.midtrans.update') }}" method="POST" class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
                        @csrf

                        <!-- Section: Status & Mode -->
                        <div class="border-b border-slate-100 pb-6 space-y-4">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Status & Lingkungan Midtrans
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Toggle Aktifkan Gateway -->
                                <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition {{ $isActive ? 'bg-emerald-50/50 border-emerald-200' : '' }}">
                                    <input type="checkbox" name="is_active" value="1" {{ $isActive ? 'checked' : '' }} class="w-4 h-4 text-rose-600 rounded focus:ring-rose-500">
                                    <div>
                                        <div class="font-bold text-xs text-slate-800">Aktifkan Payment Gateway</div>
                                        <div class="text-[11px] text-slate-500">Izinkan Snap Midtrans untuk transaksi Top Up & Checkout.</div>
                                    </div>
                                </label>

                                <!-- Pilihan Sandbox vs Production -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-slate-700">Mode Lingkungan</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer text-xs font-bold hover:bg-slate-50 transition {{ !$isProduction ? 'bg-amber-50/80 border-amber-300 text-amber-800' : 'text-slate-600' }}">
                                            <input type="radio" name="is_production" value="0" {{ !$isProduction ? 'checked' : '' }} class="text-amber-600">
                                            <span>Sandbox (Test)</span>
                                        </label>
                                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer text-xs font-bold hover:bg-slate-50 transition {{ $isProduction ? 'bg-purple-50/80 border-purple-300 text-purple-800' : 'text-slate-600' }}">
                                            <input type="radio" name="is_production" value="1" {{ $isProduction ? 'checked' : '' }} class="text-purple-600">
                                            <span>Production (Live)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Kredensial Kunci API -->
                        <div class="border-b border-slate-100 pb-6 space-y-4">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Kredensial API Midtrans
                            </h3>

                            <!-- Merchant ID -->
                            <div class="space-y-1.5">
                                <label for="merchant_id" class="block text-xs font-bold text-slate-700">
                                    Merchant ID <span class="text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <input
                                    type="text"
                                    id="merchant_id"
                                    name="merchant_id"
                                    value="{{ old('merchant_id', $merchantId) }}"
                                    placeholder="Contoh: G123456789"
                                    class="w-full bg-[#F9FAFB] border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-mono text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition"
                                >
                                <p class="text-[10px] text-slate-400">Merchant ID dari dashboard Midtrans akun Anda.</p>
                            </div>

                            <!-- Client Key -->
                            <div class="space-y-1.5">
                                <label for="client_key" class="block text-xs font-bold text-slate-700">
                                    Client Key <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="client_key"
                                    name="client_key"
                                    value="{{ old('client_key', $clientKey) }}"
                                    placeholder="SB-Mid-client-xxxxxxxxxxxxxxxx"
                                    class="w-full bg-[#F9FAFB] border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-mono text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition"
                                >
                                <p class="text-[10px] text-slate-400">Digunakan oleh frontend Snap JS browser customer saat memunculkan popup pembayaran.</p>
                            </div>

                            <!-- Server Key -->
                            <div class="space-y-1.5">
                                <label for="server_key" class="block text-xs font-bold text-slate-700">
                                    Server Key <span class="text-rose-500">* (Rahasia)</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="password"
                                        id="server_key"
                                        name="server_key"
                                        value="{{ old('server_key', $serverKey) }}"
                                        placeholder="SB-Mid-server-xxxxxxxxxxxxxxxx"
                                        class="w-full bg-[#F9FAFB] border border-slate-200 rounded-xl px-4 py-2.5 pr-20 text-xs font-mono text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition"
                                    >
                                    <button
                                        type="button"
                                        onclick="toggleKeyVisibility()"
                                        id="toggle-vis-btn"
                                        class="absolute right-2 top-2 px-2.5 py-1 text-[10px] font-bold text-slate-500 hover:text-slate-800 bg-slate-200/60 rounded-lg transition"
                                    >
                                        Lihat
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-400">Kunci rahasia API backend untuk otentikasi transaksi dan kalkulasi verifikasi signature webhook.</p>
                            </div>
                        </div>

                        <!-- Section: Pilihan Kanal Pembayaran yang Diizinkan -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Saluran Pembayaran Snap yang Diizinkan
                            </h3>
                            <p class="text-[11px] text-slate-500">Pilih opsi pembayaran yang ingin ditampilkan dalam popup Midtrans Snap:</p>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                                @php
                                    $paymentOptions = [
                                        'qris' => 'QRIS (Semua E-Wallet)',
                                        'gopay' => 'GoPay',
                                        'shopeepay' => 'ShopeePay',
                                        'bca_va' => 'BCA Virtual Account',
                                        'bni_va' => 'BNI Virtual Account',
                                        'bri_va' => 'BRI Virtual Account',
                                        'echannel' => 'Mandiri Bill',
                                        'permata_va' => 'Permata VA',
                                        'cimb_va' => 'CIMB Niaga VA',
                                    ];
                                @endphp
                                @foreach($paymentOptions as $key => $label)
                                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="enabled_payments[]"
                                            value="{{ $key }}"
                                            {{ in_array($key, $enabledPayments) ? 'checked' : '' }}
                                            class="w-3.5 h-3.5 text-rose-600 rounded"
                                        >
                                        <span class="text-slate-700 font-semibold text-[11px]">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 flex flex-col sm:flex-row items-center gap-3 justify-between">
                            <button
                                type="button"
                                onclick="runTestConnection()"
                                id="btn-test-conn"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-2"
                            >
                                <span class="text-base">⚡</span>
                                <span>Uji Koneksi Server Key</span>
                            </button>

                            <button
                                type="submit"
                                class="w-full sm:w-auto px-8 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs uppercase tracking-wider transition shadow-md shadow-rose-600/20"
                            >
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>

                    <!-- Feedback Box Uji Koneksi Live -->
                    <div id="test-result-box" class="hidden rounded-2xl p-4 text-xs font-semibold space-y-1 animate-fade-in"></div>
                </div>

                <!-- Kolom Kanan: Panduan Konfigurasi Webhook & Dashboard Midtrans -->
                <div class="space-y-6">
                    
                    <!-- Box 1: Webhook & URL Redirect -->
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xs">
                                🔗
                            </div>
                            <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide">URL Callback Midtrans</h4>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Salin URL di bawah ini ke menu <b>Settings > Configuration</b> di Dashboard Midtrans Anda agar sistem menerima webhook notifikasi otomatis (ACC 2 otomatis):
                        </p>

                        <!-- Notification Webhook URL -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-600 uppercase">Payment Notification URL</label>
                            <div class="flex items-center gap-1.5">
                                <input
                                    type="text"
                                    readonly
                                    id="webhook-url-input"
                                    value="{{ $webhookUrl }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-mono text-slate-700 select-all"
                                >
                                <button
                                    type="button"
                                    onclick="copyToClipboard('webhook-url-input', this)"
                                    class="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-[10px] transition"
                                >
                                    Salin
                                </button>
                            </div>
                        </div>

                        <!-- Finish Redirect URL -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-600 uppercase">Finish / Unfinish Redirect URL</label>
                            <div class="flex items-center gap-1.5">
                                <input
                                    type="text"
                                    readonly
                                    id="finish-url-input"
                                    value="{{ $finishUrl }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-mono text-slate-700 select-all"
                                >
                                <button
                                    type="button"
                                    onclick="copyToClipboard('finish-url-input', this)"
                                    class="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-[10px] transition"
                                >
                                    Salin
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Box 2: Panduan Langkah Pengaturan untuk Pemilik Aplikasi -->
                    <div class="bg-slate-950 text-white rounded-3xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-rose-600 text-white flex items-center justify-center font-bold text-xs">
                                📖
                            </div>
                            <h4 class="font-extrabold text-xs uppercase tracking-wide text-rose-400">Panduan Siap Pakai</h4>
                        </div>
                        <ol class="space-y-3 text-[11px] text-slate-350 list-decimal list-inside leading-relaxed">
                            <li>
                                Buat / masuk ke akun di <a href="https://dashboard.midtrans.com" target="_blank" class="text-rose-400 hover:underline font-bold">dashboard.midtrans.com</a>.
                            </li>
                            <li>
                                Buka menu <b>Settings > Access Keys</b>.
                            </li>
                            <li>
                                Salin <b>Client Key</b> dan <b>Server Key</b> ke formulir di sebelah kiri.
                            </li>
                            <li>
                                Buka menu <b>Settings > Configuration</b>, lalu tempel <b>Payment Notification URL</b> yang ada di kartu atas.
                            </li>
                            <li>
                                Klik tombol <b>Uji Koneksi Server Key</b> untuk memastikan kredensial terhubung dengan benar.
                            </li>
                            <li>
                                Klik <b>Simpan Pengaturan</b>. Aplikasi Anda langsung siap menerima pembayaran otomatis!
                            </li>
                        </ol>
                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<script>
function toggleKeyVisibility() {
    const input = document.getElementById('server_key');
    const btn = document.getElementById('toggle-vis-btn');
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerText = 'Sembunyikan';
    } else {
        input.type = 'password';
        btn.innerText = 'Lihat';
    }
}

function copyToClipboard(elementId, btn) {
    const input = document.getElementById(elementId);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    
    const originalText = btn.innerText;
    btn.innerText = 'Tersalin! ✓';
    btn.classList.add('bg-emerald-600', 'text-white');
    setTimeout(() => {
        btn.innerText = originalText;
        btn.classList.remove('bg-emerald-600', 'text-white');
    }, 2000);
}

function runTestConnection() {
    const serverKey = document.getElementById('server_key').value;
    const isProd = document.querySelector('input[name="is_production"]:checked')?.value || 0;
    const resultBox = document.getElementById('test-result-box');
    const testBtn = document.getElementById('btn-test-conn');

    if (!serverKey) {
        resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-700 block';
        resultBox.innerHTML = '⚠️ Masukkan Server Key terlebih dahulu di formulir sebelum menguji koneksi.';
        return;
    }

    testBtn.disabled = true;
    testBtn.innerHTML = '<span>⏳ Menguji Koneksi Midtrans...</span>';

    fetch('{{ route("admin.settings.midtrans.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            server_key: serverKey,
            is_production: isProd
        })
    })
    .then(res => res.json())
    .then(data => {
        testBtn.disabled = false;
        testBtn.innerHTML = '<span class="text-base">⚡</span><span>Uji Koneksi Server Key</span>';

        if (data.success) {
            resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-emerald-50 border border-emerald-200 text-emerald-800 block';
            resultBox.innerHTML = '<b>✓ ' + data.message + '</b>';
        } else {
            resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-800 block';
            resultBox.innerHTML = '<b>✕ ' + data.message + '</b>';
        }
    })
    .catch(err => {
        testBtn.disabled = false;
        testBtn.innerHTML = '<span class="text-base">⚡</span><span>Uji Koneksi Server Key</span>';
        resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-800 block';
        resultBox.innerHTML = '⚠️ Gagal melakukan pengecekan: ' + err.message;
    });
}
</script>
@endsection
