@extends('layouts.support')

@section('title', 'Panel Admin - Withdraw Jastiper')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]">

    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Secure Admin Zone</span>
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
                                    <p class="text-xs font-black text-slate-900">Rp {{ number_format((float) $wr->amount, 0, ',', '.') }}</p>
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