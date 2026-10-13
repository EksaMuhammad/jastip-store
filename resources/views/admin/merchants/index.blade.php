@extends('layouts.support')

@section('title', 'Kelola Merchant')

@section('content')
<div class="min-h-screen bg-slate-50 flex">

    <!-- Sidebar Desktop (sama dengan dashboard admin) -->
    <aside class="hidden lg:flex flex-col w-64 bg-slate-950 border-r border-slate-900 fixed h-full z-20">
        <div class="p-6 flex items-center gap-3 bg-slate-950/50 backdrop-blur-md border-b border-slate-900/50">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 flex items-center justify-center shadow-lg shadow-rose-500/20">
                <span class="font-display font-black text-white text-lg">JK</span>
            </div>
            <div>
                <h1 class="font-display font-bold text-white text-base leading-tight">JastipKuy</h1>
                <p class="text-[10px] font-medium text-rose-400 uppercase tracking-widest">Admin Portal</p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto py-6 custom-scrollbar">
            <nav class="px-4 space-y-1.5">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-wider block px-3 mb-2">Menu Utama</span>

                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
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

                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Kelola Wilayah</span>
                    </div>
                </button>

                <!-- ACTIVE MENU: Kelola Merchant -->
                <a href="{{ route('admin.merchants.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Kelola Merchant</span>
                    </div>
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                </a>

                <!-- Menu: Manajemen Promo -->
                <a href="{{ route('admin.promos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Manajemen Promo</span>
                    </div>
                </a>

                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </button>

                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Withdraw Jastiper</span>
                    </div>
                </a>
            </nav>
        </div>

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

    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
        <!-- Top Navbar -->
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <button onclick="toggleMobileSidebar()" class="lg:hidden p-2 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full bg-rose-50 border border-rose-100 text-[10px] font-black text-rose-500 uppercase tracking-widest">Secure Admin Zone</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-3 px-4 py-2 rounded-full bg-slate-50 border border-slate-100">
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center">
                        <span class="text-white text-xs font-bold">AD</span>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-800">{{ auth()->guard('admin')->user()->name }}</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6 lg:p-10">
            <div class="max-w-5xl mx-auto space-y-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-display font-black text-2xl text-slate-900">Pendaftaran & Akun Mitra Merchant</h2>
                        <p class="text-sm text-slate-500 mt-1">Daftarkan nama, bidang usaha, dan akun login mitra. Mitra akan login mandiri ke Portal Merchant untuk mengelola katalog produk & foto.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('merchant.login') }}" target="_blank" class="px-4 py-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 hover:bg-rose-100 font-bold text-xs transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            Buka Portal Mitra ↗
                        </a>
                        <button onclick="document.getElementById('modal-create-merchant').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition flex items-center gap-2 shadow-lg shadow-slate-900/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Daftarkan Mitra Baru
                        </button>
                    </div>
                </div>

                @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 text-emerald-700">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <p class="text-sm font-semibold">{{ session('success') }}</p>
                </div>
                @endif

                @if($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Merchants List -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Merchant / Toko</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Bidang Usaha</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Akun Login Mitra</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Produk Toko</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($merchants as $m)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-4">
                                            @if($m->image)
                                                <img src="{{ $m->image }}" class="w-12 h-12 rounded-xl object-cover bg-slate-100">
                                            @else
                                                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-400">
                                                    {{ strtoupper(substr($m->name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">{{ $m->name }}</p>
                                                <p class="text-[10px] text-slate-400 max-w-[200px] truncate">{{ $m->address }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($m->type == 'food')
                                            <span class="px-2.5 py-1 rounded-full bg-orange-100 text-orange-700 text-[9px] font-bold uppercase tracking-widest">Kuliner (Food)</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 text-[9px] font-bold uppercase tracking-widest">Minimarket (Mart)</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-xs">
                                            <p class="font-bold text-slate-700">{{ $m->email ?? 'Belum disetel' }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $m->phone_number ? 'HP: ' . $m->phone_number : 'Password Terenkripsi' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-bold text-slate-800 text-xs">{{ $m->products_count ?? $m->products()->count() }} Produk</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($m->is_active)
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-bold uppercase tracking-widest">Aktif</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-bold uppercase tracking-widest">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.products.index', $m->id) }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Lihat Produk Toko">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            </a>
                                            <button onclick="openEditModal({{ json_encode($m) }})" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Edit Akun & Data Merchant">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <form action="{{ route('admin.merchants.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Hapus merchant ini dan semua produk di dalamnya?');" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-500 transition" title="Hapus Merchant">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500 text-sm">Belum ada data merchant yang terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>{{ $merchants->links() }}</div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Merchant (Admin Form: Nama Toko, Bidang Usaha, Data Login) -->
<div id="modal-create-merchant" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-create-merchant').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-3xl w-full max-w-lg p-6 overflow-hidden shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="font-black text-xl text-slate-900">Daftarkan Mitra Merchant Baru</h3>
                <p class="text-xs text-slate-400 mt-0.5">Admin buatkan data usaha & akun login untuk pemilik toko.</p>
            </div>
            <button onclick="document.getElementById('modal-create-merchant').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <form action="{{ route('admin.merchants.store') }}" method="POST" class="space-y-4">
            @csrf
            
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 space-y-3">
                <p class="text-[10px] font-black uppercase tracking-wider text-rose-600">1. Informasi Usaha / Toko</p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Toko / Usaha <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: RM Padang Sederhana, Toko Berkah Jaya..." class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis / Bidang Usaha <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <option value="food">Kuliner / Resto / Minuman (GoFood)</option>
                        <option value="mart">Minimarket / Sembako / Belanja Harian (GoMart)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lokasi Toko <span class="text-rose-500">*</span></label>
                    <textarea name="address" rows="2" required placeholder="Alamat lengkap lokasi toko untuk penjemputan jastiper..." class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="bg-rose-50/50 p-3.5 rounded-2xl border border-rose-100 space-y-3">
                <p class="text-[10px] font-black uppercase tracking-wider text-rose-600">2. Akses Login Portal Mitra Merchant</p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Login Mitra <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" required placeholder="mitra@toko.com" class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Login <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp / HP Mitra (Opsional)</label>
                    <input type="text" name="phone_number" placeholder="08xxxxxxxxxx" class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="is_active_create" value="1" checked class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                <label for="is_active_create" class="text-xs font-semibold text-slate-700">Aktifkan Toko di Katalog Customer</label>
            </div>

            <div class="pt-3 flex gap-3">
                <button type="button" onclick="document.getElementById('modal-create-merchant').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition shadow-md shadow-slate-900/10">Simpan & Berikan Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Merchant -->
<div id="modal-edit-merchant" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-edit-merchant').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-3xl w-full max-w-lg p-6 overflow-hidden shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-black text-xl text-slate-900">Edit Data & Akun Mitra Merchant</h3>
            <button onclick="document.getElementById('modal-edit-merchant').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <form id="edit-merchant-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 space-y-3">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Informasi Usaha</p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Toko / Usaha <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="edit_name" required class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis / Bidang Usaha <span class="text-rose-500">*</span></label>
                    <select name="type" id="edit_type" required class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
                        <option value="food">Kuliner / Resto / Minuman (GoFood)</option>
                        <option value="mart">Minimarket / Sembako / Belanja Harian (GoMart)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lokasi Toko <span class="text-rose-500">*</span></label>
                    <textarea name="address" id="edit_address" rows="2" required class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 space-y-3">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Akun Login Mitra</p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Login Mitra <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" id="edit_email" required class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Biarkan kosong jika tidak ganti" class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp / HP</label>
                    <input type="text" name="phone_number" id="edit_phone_number" class="w-full border border-slate-200 bg-white rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                <label for="edit_is_active" class="text-xs font-semibold text-slate-700">Aktifkan Toko di Katalog Customer</label>
            </div>

            <div class="pt-3 flex gap-3">
                <button type="button" onclick="document.getElementById('modal-edit-merchant').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(merchant) {
        document.getElementById('edit_name').value = merchant.name;
        document.getElementById('edit_type').value = merchant.type;
        document.getElementById('edit_address').value = merchant.address;
        document.getElementById('edit_email').value = merchant.email || '';
        document.getElementById('edit_phone_number').value = merchant.phone_number || '';
        document.getElementById('edit_is_active').checked = merchant.is_active;
        
        document.getElementById('edit-merchant-form').action = `/admin/merchants/${merchant.id}`;
        
        document.getElementById('modal-edit-merchant').classList.remove('hidden');
    }

    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('hidden');
        }
    }
</script>
@endsection
