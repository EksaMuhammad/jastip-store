@extends('layouts.support')

@section('title', 'Dashboard Customer')

@section('content')
<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Banner -->
        <div class="bg-white border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] rounded-sm p-6 sm:p-8 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-sm uppercase tracking-wide">Customer Account</span>
                <h1 class="font-display font-black text-3xl text-slate-900 mt-2">Selamat Datang, {{ $customer->name }}!</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola kebutuhan jasa titip, pantau pesanan aktif, dan nikmati kemudahan berbelanja.</p>
            </div>
            
            <!-- Logout Button Form -->
            <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm px-5 py-3 rounded-sm border-2 border-slate-900 shadow-[2px_2px_0px_0px_rgba(244,63,94,1)] hover:shadow-none transition duration-150 uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Keluar Sesi
                </button>
            </form>
        </div>

        <!-- Dashboard Grid Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Account Info Card -->
            <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 border-b border-slate-100 pb-3 mb-4">Detail Profil</h3>
                    <div class="space-y-4">
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Nama Lengkap</span>
                            <span class="text-sm font-bold text-slate-800">{{ $customer->name }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Nomor Handphone</span>
                            <span class="text-sm font-bold text-slate-800">+62 {{ $customer->phone_number }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Status Akun</span>
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-sm mt-1">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                Aktif
                            </span>
                        </div>
                    </div>
                </div>
                <div class="pt-6 mt-6 border-t border-slate-100">
                    <button onclick="showMaintenanceToast(event)" class="w-full text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-300 font-bold text-xs py-2.5 rounded-sm transition">
                        Edit Profil
                    </button>
                </div>
            </div>

            <!-- Mock Wallet Balance Card -->
            <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 border-b border-slate-100 pb-3 mb-4">Dompet Jastip</h3>
                    <div class="bg-gradient-to-br from-slate-900 to-slate-850 text-white p-5 border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(244,63,94,1)] rounded-sm mb-4">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Saldo Tersedia</span>
                        <div class="text-2xl font-black font-display tracking-tight mt-1">Rp0</div>
                        <div class="text-[9px] text-slate-400 font-medium mt-4">ID Pemilik: #CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="showMaintenanceToast(event)" class="flex-grow bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 rounded-sm transition">
                        Top Up
                    </button>
                    <button onclick="showMaintenanceToast(event)" class="flex-grow border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs py-2.5 rounded-sm transition">
                        Riwayat
                    </button>
                </div>
            </div>

            <!-- Transaction Stats Card -->
            <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 border-b border-slate-100 pb-3 mb-4">Aktivitas Belanja</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border border-slate-150 p-4 rounded-sm text-center">
                            <span class="text-2xl font-black text-slate-900 block">0</span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Permintaan Aktif</span>
                        </div>
                        <div class="border border-slate-150 p-4 rounded-sm text-center">
                            <span class="text-2xl font-black text-slate-900 block">0</span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Total Transaksi</span>
                        </div>
                    </div>
                </div>
                <div class="pt-6 mt-6 border-t border-slate-100">
                    <a href="{{ url('/#calculator') }}" class="block text-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-2.5 rounded-sm transition uppercase tracking-wider">
                        Buat Permintaan Baru
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
