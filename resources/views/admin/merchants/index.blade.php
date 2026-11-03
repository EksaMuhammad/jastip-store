@extends('layouts.support')

@section('title', 'Kelola Merchant')

@section('content')
<div class="min-h-screen bg-slate-50 flex">

    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <div class="flex-1 flex flex-col min-w-0 flex-col min-h-screen">
        <!-- Top Navbar -->
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <button onclick="toggleMobileSidebar()" class="lg:hidden p-2 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-[10px] font-bold text-slate-700 uppercase tracking-wider font-mono">Secure Admin Zone</span>
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
