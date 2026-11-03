@extends('layouts.support')

@section('title', 'Panel Admin - Daftar Pelanggan')

@section('content')
<div class="flex min-h-screen lg:h-screen lg:overflow-hidden bg-[#F3F4F6]">
    
    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0 lg:h-screen lg:overflow-hidden">
        
        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 shrink-0 z-40">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Customer Directory</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-900 rounded-full flex items-center justify-center font-bold text-white text-xs">
                    AD
                </div>
                <span class="text-xs font-bold text-slate-700 hidden sm:inline">Admin JastipKuy</span>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 flex flex-col min-h-0 p-4 lg:p-6 gap-3.5 overflow-hidden">
            
            <!-- FROZEN TOP SECTION: Header & Search Toolbar -->
            <div class="shrink-0 space-y-3">
                <!-- Header & Metrics -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl lg:text-2xl font-black text-slate-800 tracking-tight">Daftar Pelanggan</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola dan pantau data seluruh pengguna pelanggan (Customer) terdaftar</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="bg-white px-3.5 py-1.5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs">
                                {{ $totalCustomersCount }}
                            </div>
                            <div class="text-xs">
                                <span class="text-slate-400 text-[9px] uppercase font-bold block">Total User</span>
                                <span class="font-bold text-slate-700 text-xs">{{ $activeCustomersCount }} pernah order</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter & Search Toolbar (Frozen) -->
                <div class="bg-white border border-slate-200 rounded-2xl p-3 lg:p-3.5 shadow-xs">
                    <form action="{{ route('admin.customers.index') }}" method="GET" class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Search Box -->
                            <div class="relative">
                                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, nomor HP, ID..."
                                    class="text-xs bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 w-64">
                                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>

                            <!-- Has Orders Filter -->
                            <select name="has_orders" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                <option value="all" {{ $hasOrders === 'all' ? 'selected' : '' }}>Semua Aktivitas</option>
                                <option value="yes" {{ $hasOrders === 'yes' ? 'selected' : '' }}>Pernah Membuat Order</option>
                                <option value="no" {{ $hasOrders === 'no' ? 'selected' : '' }}>Belum Pernah Order</option>
                            </select>

                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                                Cari
                            </button>

                            @if(!empty($search) || $hasOrders !== 'all')
                            <a href="{{ route('admin.customers.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 underline px-2">
                                Reset Filter
                            </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Customer Table (Scrollable Area) -->
            <div class="flex-1 flex flex-col min-h-0 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                <div class="flex-1 min-h-0 overflow-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 sticky top-0 z-10 shadow-2xs">
                            <tr>
                                <th class="px-5 py-3.5 bg-slate-50">Customer ID</th>
                                <th class="px-5 py-3.5 bg-slate-50">Nama & Kontak</th>
                                <th class="px-5 py-3.5 bg-slate-50">Saldo E-Wallet</th>
                                <th class="px-5 py-3.5 bg-slate-50">Total Pesanan</th>
                                <th class="px-5 py-3.5 bg-slate-50">Total Belanja (Deal Selesai)</th>
                                <th class="px-5 py-3.5 bg-slate-50">Rating Diberikan</th>
                                <th class="px-5 py-3.5 bg-slate-50">Terdaftar Sejak</th>
                                <th class="px-5 py-3.5 bg-slate-50 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($customers as $customer)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                        #CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-rose-50 text-rose-600 font-black flex items-center justify-center text-xs border border-rose-100">
                                            {{ strtoupper(substr($customer->name ?? 'C', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-xs">{{ $customer->name ?? 'Tanpa Nama' }}</div>
                                            <div class="text-[11px] font-mono text-slate-400">{{ $customer->phone_number }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $balance = $customer->wallet ? $customer->wallet->balance : 0;
                                    @endphp
                                    <span class="font-black text-xs {{ $balance > 0 ? 'text-emerald-600' : 'text-slate-500' }}">
                                        Rp {{ number_format($balance, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($customer->orders_count > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-100">
                                            {{ $customer->orders_count }} Pesanan
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[10px] italic">0 Pesanan</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-700">
                                    Rp {{ number_format($customer->total_spent ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                                    {{ $customer->ratings_count }} Ulasan
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-[11px] text-slate-400">
                                    {{ $customer->created_at ? $customer->created_at->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('admin.support.index', ['customer_id' => $customer->id]) }}" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 transition shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                        <span>Chat Support</span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    Tidak ada data customer yang cocok dengan pencarian.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($customers->hasPages())
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 shrink-0">
                    {{ $customers->links() }}
                </div>
                @endif
            </div>

        </main>
    </div>

</div>

<!-- Mobile Sidebar Overlay -->
<div id="mobile-sidebar" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex">
    <div class="w-72 bg-slate-950 h-full flex flex-col justify-between p-4 shadow-xl text-slate-350">
        <div>
            <div class="h-16 flex items-center px-4 gap-3">
                <div class="w-8 h-8 bg-rose-600 rounded-lg flex items-center justify-center font-bold text-white text-sm">
                    JK
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-white tracking-wide">JastipKuy</h3>
                    <span class="text-[8px] font-black text-rose-500 tracking-widest uppercase block -mt-0.5">ADMIN PORTAL</span>
                </div>
            </div>

            <nav class="mt-6 space-y-1.5">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Dashboard Utama</span>
                </a>
                <a href="{{ route('admin.verification') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Verifikasi Jastiper</span>
                </a>
                <a href="{{ route('admin.payments') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Verifikasi Pembayaran</span>
                </a>
                <button onclick="showMaintenanceToast(event); toggleMobileSidebar()" class="w-full flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs text-left">
                    <span>Kelola Wilayah</span>
                </button>
                <a href="{{ route('admin.merchants.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Kelola Merchant</span>
                </a>
                <a href="{{ route('admin.promos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Manajemen Promo</span>
                </a>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl bg-rose-600/10 text-rose-500 font-bold text-xs">
                    <span>Daftar Pelanggan</span>
                </a>
                <a href="{{ route('admin.jastipers.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Daftar Jastiper</span>
                </a>
                <a href="{{ route('admin.withdraws') }}" class="flex items-center justify-between px-3 py-2.5 rounded-2xl text-slate-400 hover:text-white font-bold text-xs">
                    <span>Withdraw Jastiper</span>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-900">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-rose-500 font-bold text-xs">
                    <span>Keluar Panel</span>
                </button>
            </form>
        </div>
    </div>
    <div class="flex-grow h-full" onclick="toggleMobileSidebar()"></div>
</div>

<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        if (sidebar.classList.contains('hidden')) {
            sidebar.classList.remove('hidden');
        } else {
            sidebar.classList.add('hidden');
        }
    }
</script>
@endsection
