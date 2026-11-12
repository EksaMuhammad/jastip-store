@extends('layouts.support')

@section('title', 'Panel Admin - Manajemen & Monitoring Petugas CS')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]" x-data="csManagement()">
    
    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
        
        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 shrink-0 sticky top-0 z-40 shadow-xs">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-500 hover:text-slate-800 text-xs font-bold flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Dashboard Utama
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-black text-slate-800">Manajemen Tim CS</span>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('cs.login') }}" target="_blank" class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    <span>Buka Portal CS</span>
                </a>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="header-online-count">{{ $onlineCs }} CS Online</span>
                </span>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 p-4 lg:p-6 space-y-5">

            <!-- Title & Quick Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </span>
                        Manajemen & Monitoring Petugas CS
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola akun staf Customer Service, pantau ketersediaan online, dan monitor penanganan antrean pelanggan secara real-time.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="openCreateModal = true" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-xs flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Tambah Petugas CS</span>
                    </button>
                </div>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold flex items-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-bold shadow-2xs">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- ZERO-NEGLECT ALERT: Antrean Customer Belum Ditangani -->
            @if($unassignedChatsCount > 0 || $unassignedReportsCount > 0)
            <div class="bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-transparent border border-amber-300 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black text-lg shrink-0 shadow-xs">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-xs font-black text-amber-950 uppercase tracking-wider">Antrean Pelanggan Membutuhkan Petugas</h3>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Terdapat <strong>{{ $unassignedChatsCount }} chat bantuan</strong> dan <strong>{{ $unassignedReportsCount }} tiket komplain</strong> yang belum diambil oleh petugas CS.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300">
                        {{ $freeCs > 0 ? $freeCs . ' CS Siap Melayani' : 'Belum Ada CS Free' }}
                    </span>
                </div>
            </div>
            @endif

            <!-- 5 KPI MONITORING CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Card 1: Total CS -->
                <div class="bg-white rounded-2xl border border-slate-200 p-3.5 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Total Staf CS</span>
                    <h3 class="text-xl font-black text-slate-900 mt-1">{{ $totalCs }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">Akun terdaftar</p>
                </div>

                <!-- Card 2: CS Online -->
                <div class="bg-white rounded-2xl border border-emerald-100 p-3.5 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">CS Sedang Online</span>
                    <h3 class="text-xl font-black text-emerald-700 mt-1 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ $onlineCs }}</span>
                    </h3>
                    <p class="text-[10px] text-emerald-600/70 mt-0.5">Aktif 10 menit terakhir</p>
                </div>

                <!-- Card 3: Free / Tersedia -->
                <div class="bg-white rounded-2xl border border-sky-100 p-3.5 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600 block">Tersedia (Free)</span>
                    <h3 class="text-xl font-black text-sky-700 mt-1">{{ $freeCs }}</h3>
                    <p class="text-[10px] text-sky-600/70 mt-0.5">Siap terima order/chat baru</p>
                </div>

                <!-- Card 4: Sibuk / Menangani -->
                <div class="bg-white rounded-2xl border border-amber-100 p-3.5 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 block">Sedang Menangani</span>
                    <h3 class="text-xl font-black text-amber-700 mt-1">{{ $busyCs }}</h3>
                    <p class="text-[10px] text-amber-600/70 mt-0.5">Aktif melayani customer</p>
                </div>

                <!-- Card 5: Offline -->
                <div class="bg-white rounded-2xl border border-slate-200 p-3.5 shadow-xs col-span-2 lg:col-span-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Offline / Istirahat</span>
                    <h3 class="text-xl font-black text-slate-600 mt-1">{{ $offlineCs }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">Tidak bertugas saat ini</p>
                </div>
            </div>

            <!-- TABEL DAFTAR & MONITORING PETUGAS CS -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <!-- Header Card Toolbar -->
                <div class="px-5 py-3.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <span>Daftar & Status Petugas Customer Service</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-200/80 text-slate-700 font-bold">
                                {{ $officers->total() }} Petugas
                            </span>
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Pantau akun login, status ketersediaan, serta beban chat dan komplain yang sedang ditangani.</p>
                    </div>

                    <!-- Search & Filter Form -->
                    <form action="{{ route('admin.cs.index') }}" method="GET" class="flex items-center gap-2">
                        <select name="status" onchange="this.form.submit()" class="text-xs font-bold border border-slate-200 rounded-xl px-2.5 py-1.5 bg-white focus:outline-none">
                            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="online" {{ $statusFilter === 'online' ? 'selected' : '' }}>Online Saja</option>
                            <option value="tersedia" {{ $statusFilter === 'tersedia' ? 'selected' : '' }}>Tersedia (Free)</option>
                            <option value="sibuk" {{ $statusFilter === 'sibuk' ? 'selected' : '' }}>Sedang Sibuk</option>
                            <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>Offline</option>
                        </select>
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari CS..." class="text-xs font-medium border border-slate-200 rounded-xl pl-7 pr-3 py-1.5 bg-white focus:outline-none w-36 sm:w-48">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </form>
                </div>

                <!-- Table Body -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Nama & Email CS</th>
                                <th class="py-3 px-4">Kontak / No HP</th>
                                <th class="py-3 px-4 text-center">Status Kehadiran</th>
                                <th class="py-3 px-4 text-center">Kondisi Kerja</th>
                                <th class="py-3 px-4 text-center">Beban Aktif</th>
                                <th class="py-3 px-4">Aktivitas Terakhir</th>
                                <th class="py-3 px-4 text-center">Status Akun</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700 bg-white">
                            @forelse($officers as $officer)
                            @php
                                $isOnline = $officer->isReallyOnline();
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- Nama & Email -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($officer->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                                <span>{{ $officer->name }}</span>
                                            </h4>
                                            <span class="text-[11px] text-slate-500 font-mono block">{{ $officer->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- No HP -->
                                <td class="py-4 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $officer->phone_number ?: '-' }}
                                </td>

                                <!-- Status Online/Offline -->
                                <td class="py-4 px-4 text-center">
                                    @if($isOnline)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Online
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            Offline
                                        </span>
                                    @endif
                                </td>

                                <!-- Kondisi Kerja (Tersedia / Sibuk / Istirahat) -->
                                <td class="py-4 px-4 text-center">
                                    @if(!$isOnline)
                                        <span class="text-[11px] text-slate-400 font-semibold">-</span>
                                    @elseif($officer->work_status === 'tersedia')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-sky-50 text-sky-700 border border-sky-200">
                                            🟢 Siap Melayani (Free)
                                        </span>
                                    @elseif($officer->work_status === 'sibuk')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-200">
                                            🟡 Menangani Customer
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-slate-100 text-slate-700 border border-slate-200">
                                            ☕ Sedang Istirahat
                                        </span>
                                    @endif
                                </td>

                                <!-- Beban Aktif -->
                                <td class="py-4 px-4 text-center">
                                    <div class="inline-flex items-center gap-2 bg-slate-50 px-2.5 py-1 rounded-xl border border-slate-200">
                                        <span title="Chat Bantuan Aktif" class="font-bold text-[11px] {{ $officer->active_conversations_count > 0 ? 'text-indigo-600 font-black' : 'text-slate-500' }}">
                                            💬 {{ $officer->active_conversations_count }} chat
                                        </span>
                                        <span class="text-slate-300">|</span>
                                        <span title="Tiket Komplain Ditangani" class="font-bold text-[11px] {{ $officer->active_reports_count > 0 ? 'text-rose-600 font-black' : 'text-slate-500' }}">
                                            ⚖️ {{ $officer->active_reports_count }} tiket
                                        </span>
                                    </div>
                                </td>

                                <!-- Waktu Aktif -->
                                <td class="py-4 px-4 text-slate-500 text-[11px]">
                                    {{ $officer->last_active_at ? $officer->last_active_at->diffForHumans() : 'Belum pernah login' }}
                                </td>

                                <!-- Status Akun (Aktif / Nonaktif) -->
                                <td class="py-4 px-4 text-center">
                                    <form action="{{ route('admin.cs.toggle-status', $officer->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-0.5 rounded-md text-[10px] font-bold transition {{ $officer->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' }}">
                                            {{ $officer->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Modal Trigger -->
                                        <button type="button" @click="openEditModal({{ json_encode($officer) }})" class="p-1.5 text-slate-500 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition" title="Edit Akun CS">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>

                                        <!-- Hapus Akun -->
                                        <form action="{{ route('admin.cs.destroy', $officer->id) }}" method="POST" onsubmit="return confirm('Hapus akun CS {{ $officer->name }}?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition" title="Hapus Akun">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-slate-700 text-sm">Belum Ada Petugas CS</h4>
                                    <p class="text-xs text-slate-400 mt-1">Tambahkan akun Customer Service pertama Anda agar pelanggan dapat terlayani secara khusus.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($officers->hasPages())
                <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
                    {{ $officers->links() }}
                </div>
                @endif
            </div>

        </main>
        
    </div>

    <!-- MODAL CREATE CS -->
    <div x-show="openCreateModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-fade-in" @click.outside="openCreateModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-sm text-slate-900">Buat Akun Petugas CS</h3>
                        <p class="text-[11px] text-slate-500">Staf ini dapat login ke dashboard CS khusus.</p>
                    </div>
                </div>
                <button @click="openCreateModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">&times;</button>
            </div>

            <form action="{{ route('admin.cs.store') }}" method="POST" class="pt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap CS</label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso (CS)" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Login</label>
                    <input type="email" name="email" required placeholder="cs.budi@jastipkuy.com" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                    <input type="text" name="phone_number" placeholder="08123456789" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Akun</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="openCreateModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-xs">Simpan & Aktifkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT CS -->
    <div x-show="openEditModalState" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-fade-in" @click.outside="openEditModalState = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-sm text-slate-900">Perbarui Akun CS</h3>
                        <p class="text-[11px] text-slate-500" x-text="editForm.name"></p>
                    </div>
                </div>
                <button @click="openEditModalState = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">&times;</button>
            </div>

            <form :action="'/admin/cs-officers/' + editForm.id" method="POST" class="pt-4 space-y-3.5">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap CS</label>
                    <input type="text" name="name" x-model="editForm.name" required class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Login</label>
                    <input type="email" name="email" x-model="editForm.email" required class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                    <input type="text" name="phone_number" x-model="editForm.phone_number" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kondisi Kerja</label>
                    <select name="work_status" x-model="editForm.work_status" class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                        <option value="tersedia">🟢 Siap Melayani (Tersedia / Free)</option>
                        <option value="sibuk">🟡 Sedang Menangani Customer (Sibuk)</option>
                        <option value="istirahat">☕ Sedang Istirahat</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Ganti Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Password baru..." class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-800">
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="openEditModalState = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function csManagement() {
        return {
            openCreateModal: false,
            openEditModalState: false,
            editForm: {
                id: null,
                name: '',
                email: '',
                phone_number: '',
                work_status: 'tersedia'
            },
            openEditModal(officer) {
                this.editForm = {
                    id: officer.id,
                    name: officer.name,
                    email: officer.email,
                    phone_number: officer.phone_number || '',
                    work_status: officer.work_status || 'tersedia'
                };
                this.openEditModalState = true;
            }
        };
    }

    // Auto-polling status CS setiap 15 detik
    setInterval(() => {
        fetch('{{ route("admin.cs.monitoring") }}')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.officers) {
                    const onlineCount = data.officers.filter(o => o.is_online).length;
                    const badge = document.getElementById('header-online-count');
                    if (badge) badge.innerText = onlineCount + ' CS Online';
                }
            })
            .catch(err => console.log('Monitoring sync paused'));
    }, 15000);
</script>
@endsection

