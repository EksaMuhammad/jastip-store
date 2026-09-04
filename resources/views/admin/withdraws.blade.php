@extends('layouts.support')

@section('title', 'Panel Admin - Withdraw Jastiper')

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

                <!-- Menu: Kelola Wilayah -->
                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Kelola Wilayah</span>
                    </div>
                </button>

                <!-- Menu: Mitra Jastiper -->
                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Mitra Jastiper</span>
                    </div>
                </button>

                <!-- Menu: Daftar Customer -->
                <button onclick="showMaintenanceToast(event)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-bold text-xs transition text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Daftar Pelanggan</span>
                    </div>
                </button>

                <!-- Menu: Withdraw Jastiper (ACTIVE — Sprint 8 Bagian 3, dulunya dummy "Keuangan & Tarik Dana") -->
                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Withdraw Jastiper</span>
                    </div>
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
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

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Secure Admin Zone</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-900 rounded-full flex items-center justify-center font-bold text-white text-xs">AD</div>
                <span class="text-xs font-bold text-slate-700 hidden sm:inline">Admin JastipKuy</span>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow p-6 lg:p-8 space-y-5">

            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold">{{ session('error') }}</div>
            @endif

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="font-extrabold text-lg text-slate-900">Pengajuan Withdraw Jastiper</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Setujui atau tolak permintaan tarik saldo dari mitra jastiper.</p>
                </div>

                <!-- Filter Status -->
                <div class="flex bg-white border border-slate-200 rounded-full p-1 shadow-sm">
                    @foreach(['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $key => $label)
                        <a href="{{ route('admin.withdraws', ['status' => $key]) }}"
                           class="text-[10px] font-bold uppercase tracking-wide px-3.5 py-2 rounded-full transition {{ $status === $key ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-wider">Jastiper</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-wider">Rekening Tujuan</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-wider">Diajukan</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($withdraws as $wr)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-xs font-bold text-slate-800">{{ $wr->jastiper->name ?? '—' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $wr->jastiper->phone_number ?? '' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-xs font-black text-slate-900">Rp{{ number_format((float) $wr->amount, 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-xs font-semibold text-slate-700">{{ $wr->bank_name }} — {{ $wr->bank_account_number }}</p>
                                    <p class="text-[10px] text-slate-400">a.n. {{ $wr->bank_account_holder }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-[10px] text-slate-500">{{ $wr->created_at->format('d/m/Y H:i') }}</p>
                                    @if($wr->status !== 'menunggu')
                                        <p class="text-[9px] text-slate-400 mt-0.5">
                                            {{ $wr->status === 'disetujui' ? 'Disetujui' : 'Ditolak' }} oleh {{ $wr->processedByAdmin->name ?? 'Admin' }}
                                            @if($wr->admin_note)
                                                — "{{ $wr->admin_note }}"
                                            @endif
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if($wr->status === 'menunggu')
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('admin.withdraws.approve', $wr->id) }}" method="POST" onsubmit="return confirm('Setujui pengajuan withdraw ini? Saldo jastiper akan langsung dipotong.');">
                                                @csrf
                                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold px-3 py-2 rounded-full uppercase tracking-wide transition">Setujui</button>
                                            </form>
                                            <button onclick="document.getElementById('reject-modal-{{ $wr->id }}').classList.remove('hidden')" class="bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-3 py-2 rounded-full uppercase tracking-wide transition">Tolak</button>
                                        </div>

                                        <!-- Modal Tolak -->
                                        <div id="reject-modal-{{ $wr->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                                            <div class="bg-white rounded-3xl w-full max-w-sm p-5 space-y-3 text-left">
                                                <h3 class="font-extrabold text-sm text-slate-900">Tolak Pengajuan</h3>
                                                <form action="{{ route('admin.withdraws.reject', $wr->id) }}" method="POST" class="space-y-3">
                                                    @csrf
                                                    <textarea name="admin_note" required rows="3" placeholder="Alasan penolakan..." class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                                                    <div class="flex gap-2">
                                                        <button type="button" onclick="document.getElementById('reject-modal-{{ $wr->id }}').classList.add('hidden')" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2.5 rounded-full transition">Batal</button>
                                                        <button type="submit" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold py-2.5 rounded-full transition">Kirim</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-xs text-slate-400">Tidak ada pengajuan withdraw untuk status ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $withdraws->links() }}</div>
        </main>
    </div>
</div>

<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('hidden');
        }
    }
</script>
@endsection