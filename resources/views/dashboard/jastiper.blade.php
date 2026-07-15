@extends('layouts.support')

@section('title', 'Dashboard Jastiper')

@section('content')
<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Banner -->
        <div class="bg-white border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] rounded-sm p-6 sm:p-8 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-sm uppercase tracking-wide">Jastiper Account</span>
                <h1 class="font-display font-black text-3xl text-slate-900 mt-2">Selamat Datang, {{ $jastiper->name }}!</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola radius wilayah operasional Anda, terima tawaran jasa titip, dan hasilkan komisi belanja.</p>
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
                    <h3 class="font-display font-bold text-lg text-slate-900 border-b border-slate-100 pb-3 mb-4">Profil & Wilayah Kerja</h3>
                    <div class="space-y-4">
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Nama Lengkap</span>
                            <span class="text-sm font-bold text-slate-800">{{ $jastiper->name }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Nomor Handphone</span>
                            <span class="text-sm font-bold text-slate-800">+62 {{ $jastiper->phone_number }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Wilayah Operasional</span>
                            <span class="text-sm font-bold text-slate-800">{{ $jastiper->wilayah ? $jastiper->wilayah->name : 'Tidak Terdaftar' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Radius Pengantaran</span>
                            <span class="text-sm font-bold text-slate-800">{{ $jastiper->radius_km }} KM</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-semibold block uppercase">Status Verifikasi Akun</span>
                            <span class="mt-1 block">
                                @if ($jastiper->verification_status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 rounded-sm">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                        Approved (Aktif)
                                    </span>
                                @elseif ($jastiper->verification_status === 'menunggu')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-0.5 rounded-sm">
                                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-ping"></span>
                                        Menunggu Persetujuan
                                    </span>
                                @elseif ($jastiper->verification_status === 'rejected')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-sm">
                                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                        Ditolak Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-100 px-2.5 py-0.5 rounded-sm">
                                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                        Belum Diverifikasi
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                <div class="pt-6 mt-6 border-t border-slate-100">
                    <button onclick="showMaintenanceToast(event)" class="w-full text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-300 font-bold text-xs py-2.5 rounded-sm transition">
                        Edit Wilayah / Profil
                    </button>
                </div>
            </div>

            <!-- Dompet Jastiper Balance Card -->
            <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 border-b border-slate-100 pb-3 mb-4">Pendapatan Jastip</h3>
                    <div class="bg-gradient-to-br from-slate-900 to-slate-850 text-white p-5 border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(16,185,129,1)] rounded-sm mb-4">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Saldo Komisi (Wallet)</span>
                        <div class="text-2xl font-black font-display tracking-tight mt-1">Rp0</div>
                        <div class="text-[9px] text-slate-400 font-medium mt-4">ID Pemilik: #JSTP-{{ str_pad($jastiper->id, 4, '0', STR_PAD_LEFT) }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="showMaintenanceToast(event)" class="flex-grow bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 rounded-sm transition">
                        Tarik Saldo
                    </button>
                    <button onclick="showMaintenanceToast(event)" class="flex-grow border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs py-2.5 rounded-sm transition">
                        Mutasi Wallet
                    </button>
                </div>
            </div>

            <!-- Jastip Stats & Availability Card -->
            <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 border-b border-slate-100 pb-3 mb-4">Aktivitas Mengirim</h3>
                    <div class="space-y-4 mb-4">
                        <div class="flex justify-between items-center bg-slate-50 border border-slate-200 p-3 rounded-sm">
                            <span class="text-xs font-bold text-slate-700">Status Ketersediaan</span>
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-100 border border-emerald-200 px-2 py-0.5 rounded-sm">
                                AKTIF
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="border border-slate-150 p-4 rounded-sm text-center">
                                <span class="text-2xl font-black text-slate-900 block">0</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Tawaran Dikirim</span>
                            </div>
                            <div class="border border-slate-150 p-4 rounded-sm text-center">
                                <span class="text-2xl font-black text-slate-900 block">0</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">Kirim Berhasil</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-6 mt-6 border-t border-slate-100">
                    <a href="{{ url('/') }}" class="block text-center bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 rounded-sm transition uppercase tracking-wider">
                        Cari Order Titipan Terdekat
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
