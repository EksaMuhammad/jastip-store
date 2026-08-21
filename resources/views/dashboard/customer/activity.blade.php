@extends('layouts.support')

@section('title', 'Aktivitas Saya')

@section('content')
<div class="min-h-screen bg-[#F3F4F6] pb-16" 
     x-data="{ 
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'dalam_proses',
        orders: [],
        initialLoaded: false,
        pollHandle: null,
        
        init() {
            this.fetchOrders();
            this.pollHandle = setInterval(() => this.fetchOrders(true), 6000);
        },
        
        async fetchOrders(silent = false) {
            try {
                const res = await fetch('{{ route("customer.orders.active-feed") }}', { 
                    headers: { 'Accept': 'application/json' } 
                });
                const data = await res.json();
                this.orders = data.orders || [];
            } catch (e) {
                // Fail silently
            } finally {
                this.initialLoaded = true;
            }
        },
        
        getProgress(status, jastiperId) {
            let progress = 10;
            if (jastiperId) progress = 30;
            if (status === 'menunggu_pembayaran') progress = 40;
            if (['deal', 'diproses', 'barang_diambil'].includes(status)) progress = 70;
            if (['sedang_diantar', 'tiba_tujuan'].includes(status)) progress = 90;
            return progress;
        },
        
        getStatusLabel(status) {
            const labels = {
                'menunggu_tawaran': 'Mencari Jastiper',
                'ada_tawaran': 'Ada Tawaran Masuk',
                'menunggu_pembayaran': 'Menunggu Pembayaran',
                'deal': 'Deal Terbentuk',
                'diproses': 'Sedang Diproses',
                'barang_diambil': 'Barang Telah Diambil',
                'sedang_diantar': 'Sedang Diantar',
                'tiba_tujuan': 'Tiba di Tujuan'
            };
            return labels[status] || status.replace('_', ' ');
        }
     }">
    
    <!-- Top Navigation Header (Gojek Style) -->
    <div class="bg-white border-b border-slate-200 sticky top-20 z-40 px-4 py-3 shadow-sm">
        <div class="max-w-md mx-auto">
            <h2 class="font-display font-black text-lg text-slate-900 leading-none">Aktivitas</h2>
            
            <!-- Tabs Row -->
            <div class="flex justify-between items-center mt-4 border-b border-slate-100 text-xs font-bold text-slate-400">
                <button @click="activeTab = 'riwayat'" 
                        :class="activeTab === 'riwayat' ? 'text-rose-600 border-b-2 border-rose-600 pb-2' : 'pb-2'" 
                        class="flex-1 text-center transition">
                    Riwayat
                </button>
                <button @click="activeTab = 'dalam_proses'" 
                        :class="activeTab === 'dalam_proses' ? 'text-rose-600 border-b-2 border-rose-600 pb-2' : 'pb-2'" 
                        class="flex-1 text-center transition">
                    Dalam proses
                </button>
                <button @click="activeTab = 'terjadwal'" 
                        :class="activeTab === 'terjadwal' ? 'text-rose-600 border-b-2 border-rose-600 pb-2' : 'pb-2'" 
                        class="flex-1 text-center transition">
                    Terjadwal
                </button>
                <button @click="activeTab = 'draf'" 
                        :class="activeTab === 'draf' ? 'text-rose-600 border-b-2 border-rose-600 pb-2' : 'pb-2'" 
                        class="flex-1 text-center transition">
                    Draf
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-md mx-auto px-4 mt-4">
        
        <!-- ================= RIWAYAT TAB ================= -->
        <div x-show="activeTab === 'riwayat'" class="space-y-4 animate-fade-in" x-transition>

            <!-- Transaction Filters (GoPay style) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-full text-xs font-extrabold transition hover:bg-slate-50 flex items-center gap-1 shrink-0 shadow-sm">
                    <span>Semua Tanggal</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <button class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-full text-xs font-extrabold transition hover:bg-slate-50 flex items-center gap-1 shrink-0 shadow-sm">
                    <span>Layanan</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <button class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-full text-xs font-extrabold transition hover:bg-slate-50 flex items-center gap-1 shrink-0 shadow-sm">
                    <span>Metode</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </div>

            <!-- List of Past Orders Grouped by Date -->
            @if($pastOrders->isEmpty())
                <div class="bg-white border border-slate-200 rounded-3xl p-8 text-center shadow-sm">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <h4 class="font-bold text-slate-700 text-sm">Belum ada riwayat pesanan</h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto leading-normal">Setelah Anda memesan jastip dan selesai, daftarnya akan muncul di sini.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($pastOrders->groupBy(fn($order) => $order->created_at->format('d M Y')) as $date => $orders)
                        <div class="space-y-2">
                            <!-- Date Heading -->
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wider px-1 pt-2">{{ $date }}</h3>
                            
                            <!-- Card Container for this Date's Transactions -->
                            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100 shadow-sm">
                                @foreach($orders as $order)
                                    <div class="flex items-center justify-between p-3.5 hover:bg-slate-50 transition duration-150">
                                        
                                        <!-- Left: Circular Icon & details -->
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-11 h-11 bg-rose-50 border border-rose-100 rounded-full flex items-center justify-center shrink-0">
                                                @if($order->category === 'beli-antar' || $order->category === 'kuliner')
                                                    <img src="{{ asset('images/services/beli-antar.png') }}" class="w-7 h-7 object-contain">
                                                @elseif($order->category === 'ambil-antar')
                                                    <img src="{{ asset('images/services/ambil-antar.png') }}" class="w-7 h-7 object-contain">
                                                @elseif($order->category === 'toko-kirim')
                                                    <img src="{{ asset('images/services/toko-kirim.png') }}" class="w-7 h-7 object-contain">
                                                @elseif($order->category === 'dokumen')
                                                    <img src="{{ asset('images/services/dokumen.png') }}" class="w-7 h-7 object-contain">
                                                @elseif($order->category === 'multi-stop')
                                                    <img src="{{ asset('images/services/multi-stop.png') }}" class="w-7 h-7 object-contain">
                                                @else
                                                    <img src="{{ asset('images/services/kirim-pihak-ketiga.png') }}" class="w-7 h-7 object-contain">
                                                @endif
                                            </div>
                                            
                                            <div class="min-w-0">
                                                <h4 class="font-extrabold text-slate-800 text-xs truncate leading-tight">{{ $order->description }}</h4>
                                                <span class="text-[9px] text-slate-400 font-semibold mt-0.5 block truncate">
                                                    {{ $order->origin_address ?: 'Malang Raya' }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Right: Pricing & Payment Method -->
                                        <div class="text-right shrink-0">
                                            @if($order->status === 'dibatalkan')
                                                <span class="text-xs font-extrabold text-rose-600">-Rp{{ number_format($order->agreed_fare ?? $order->estimated_fare, 0, ',', '.') }}</span>
                                                <span class="text-[9px] font-bold text-rose-500 block">Dibatalkan</span>
                                            @else
                                                <span class="text-xs font-extrabold text-slate-800">-Rp{{ number_format($order->agreed_fare ?? $order->estimated_fare, 0, ',', '.') }}</span>
                                                <span class="text-[9px] font-bold text-emerald-600 block">Selesai</span>
                                            @endif
                                            <div class="flex items-center justify-end gap-1 mt-0.5 text-[8px] font-bold text-slate-400">
                                                <svg class="w-2.5 h-2.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                                <span>JK PAY</span>
                                            </div>
                                        </div>
                                        
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- ================= DALAM PROSES TAB ================= -->
        <div x-show="activeTab === 'dalam_proses'" class="space-y-4 animate-fade-in" x-transition>
            
            <!-- Loading state -->
            <div x-show="!initialLoaded" class="bg-white border border-slate-200 rounded-3xl p-8 text-center shadow-sm">
                <span class="text-xs text-slate-400 font-bold">Memuat pesanan aktif...</span>
            </div>

            <!-- Empty state -->
            <div x-show="initialLoaded && orders.length === 0" class="bg-white border border-slate-200 rounded-3xl p-8 text-center shadow-sm" x-cloak>
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h4 class="font-bold text-slate-700 text-sm">Tidak ada pesanan aktif</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto leading-normal">Pesan jastip Anda sekarang untuk mulai melacak lokasinya secara real-time.</p>
                <a href="{{ route('customer.orders.create') }}" class="inline-block mt-4 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] px-6 py-2.5 rounded-full transition uppercase tracking-wider shadow-sm">
                    Pesan Jastip
                </a>
            </div>

            <!-- List of Dynamic Active Orders (Real-time via Polling) -->
            <div x-show="initialLoaded && orders.length > 0" class="space-y-3" x-cloak>
                <template x-for="order in orders" :key="order.id">
                    <div class="bg-white border border-slate-200 rounded-3xl p-4 space-y-3 shadow-sm">
                        <!-- Header: Description & Fare -->
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <h4 class="font-extrabold text-slate-800 text-xs truncate" x-text="order.description"></h4>
                                <p class="text-[9px] text-slate-450 mt-0.5">Status: <span class="font-extrabold text-rose-500 uppercase" x-text="getStatusLabel(order.status)"></span></p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[8px] uppercase font-bold text-slate-400 block tracking-wide">Ongkir</span>
                                <span class="text-xs font-black text-rose-600" x-text="order.agreed_fare_formatted || order.estimated_fare_formatted"></span>
                            </div>
                        </div>

                        <!-- Progress Tracker (Visual Step Bar) -->
                        <div class="py-2">
                            <div class="flex items-center justify-between text-[8px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                <span :class="['menunggu_tawaran', 'ada_tawaran'].includes(order.status) ? 'text-rose-500 font-black' : 'text-slate-500'">1. Cari</span>
                                <span :class="order.status === 'menunggu_pembayaran' ? 'text-rose-500 font-black' : (order.jastiper ? 'text-slate-500' : '')">2. Bayar</span>
                                <span :class="['deal', 'diproses', 'barang_diambil'].includes(order.status) ? 'text-rose-500 font-black' : ''">3. Proses</span>
                                <span :class="['sedang_diantar', 'tiba_tujuan'].includes(order.status) ? 'text-rose-500 font-black' : ''">4. Antar</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden flex">
                                <div class="bg-rose-500 h-full transition-all duration-500" :style="'width: ' + getProgress(order.status, order.jastiper?.id) + '%'"></div>
                            </div>
                        </div>

                        <!-- Jastiper details if assigned -->
                        <template x-if="order.jastiper">
                            <div class="bg-slate-50 p-2.5 rounded-2xl flex items-center justify-between gap-3 border border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-slate-900 text-rose-500 font-bold text-xs flex items-center justify-center" x-text="order.jastiper.name.substring(0,2).toUpperCase()">
                                    </div>
                                    <div>
                                        <h5 class="text-[10px] font-black text-slate-800" x-text="order.jastiper.name"></h5>
                                        <p class="text-[8px] text-slate-400 mt-0.5" x-text="order.jastiper.phone_number"></p>
                                    </div>
                                </div>
                                <!-- Chat button -->
                                <button type="button" 
                                        @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: order.id, orderLabel: order.description } }))"
                                        class="bg-slate-900 text-white font-bold text-[9px] px-3.5 py-1.5 rounded-full transition uppercase tracking-wider flex items-center gap-1 shadow-sm">
                                    Chat
                                </button>
                            </div>
                        </template>

                        <!-- Contextual Action Buttons -->
                        <div class="flex gap-2">
                            <template x-if="order.status === 'menunggu_pembayaran'">
                                <a :href="'/customer/orders/' + order.id + '/payment'" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[9px] py-2.5 rounded-xl text-center uppercase tracking-wider block shadow-sm">
                                    Bayar Sekarang
                                </a>
                            </template>
                            
                            <template x-if="order.status === 'tiba_tujuan'">
                                <form :action="'/customer/orders/' + order.id + '/confirm'" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[9px] py-2.5 rounded-xl uppercase tracking-wider shadow-sm">
                                        Konfirmasi Diterima
                                    </button>
                                </form>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ================= TERJADWAL TAB ================= -->
        <div x-show="activeTab === 'terjadwal'" class="space-y-4 animate-fade-in" x-transition>
            <div class="bg-white border border-slate-200 rounded-3xl p-8 text-center shadow-sm">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <h4 class="font-bold text-slate-700 text-sm">Tidak ada pesanan terjadwal</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto leading-normal">Gunakan fitur belanja terjadwal untuk menjadwalkan jasa titip belanja rutin Anda secara otomatis.</p>
            </div>
        </div>

        <!-- ================= DRAF TAB ================= -->
        <div x-show="activeTab === 'draf'" class="space-y-4 animate-fade-in" x-transition>
            <div class="bg-white border border-slate-200 rounded-3xl p-8 text-center shadow-sm">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <h4 class="font-bold text-slate-700 text-sm">Tidak ada draf pesanan</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto leading-normal">Draf pesanan belanja yang belum Anda selesaikan konfirmasinya akan tersimpan di sini.</p>
            </div>
        </div>

    </div>
</div>

@include('components.chat.order-chat-modal', [
    'viewerRole' => 'customer',
    'chatSendUrlTemplate' => route('customer.orders.chat.send', ['id' => '__ID__']),
    'chatHistoryUrlTemplate' => route('customer.orders.chat.history', ['id' => '__ID__']),
])
@endsection
