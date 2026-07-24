@extends('layouts.support')

@section('title', 'Dashboard Customer')

@section('content')

<script>
    // Helper toast ringan, konsisten dengan pola yang dipakai di dashboard jastiper.
    function customerNotify(message, success = true) {
        const container = document.getElementById('toast-container');
        if (!container) { alert(message); return; }

        const toast = document.createElement('div');
        toast.className = "pointer-events-auto bg-slate-900 text-white border-2 border-slate-900 p-4 rounded-sm flex items-center gap-3 animate-toast-slide-in text-xs font-bold tracking-wide transform transition-all duration-300"
            + (success ? " shadow-[4px_4px_0px_0px_rgba(16,185,129,1)]" : " shadow-[4px_4px_0px_0px_rgba(244,63,94,1)]");
        toast.innerHTML = `
            <span class="text-base">${success ? '✅' : '⚠️'}</span>
            <div><p class="font-sans font-semibold text-slate-100 normal-case">${message}</p></div>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Alpine component untuk dashboard customer: polling order aktif + tawaran masuk,
    // pilih tawaran (deal), perluas radius, dan batalkan pesanan.
    function customerDashboard(config) {
        return {
            csrfToken: config.csrfToken,
            activeFeedUrl: config.activeFeedUrl,
            acceptOfferUrlTemplate: config.acceptOfferUrlTemplate,
            expandRadiusUrlTemplate: config.expandRadiusUrlTemplate,
            cancelOrderUrlTemplate: config.cancelOrderUrlTemplate,
            paymentPageUrlTemplate: config.paymentPageUrlTemplate,
            orders: [],
            actionLoading: false,
            initialLoaded: false,
            pollHandle: null,

            init() {
                this.fetchOrders();
                this.pollHandle = setInterval(() => this.fetchOrders(true), 6000);
            },

            async fetchOrders(silent = false) {
                try {
                    const path = new URL(this.activeFeedUrl, window.location.origin).pathname;
                    const res = await fetch(path, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.orders = data.orders || [];
                } catch (e) {
                    // Polling diam-diam gagal (mis. koneksi putus sesaat) — jangan ganggu UI,
                    // biarkan coba lagi di siklus polling berikutnya.
                } finally {
                    this.initialLoaded = true;
                }
            },

            isSearching(order) {
                return order.status === 'menunggu_tawaran' || order.status === 'ada_tawaran';
            },

            needsPayment(order) {
                return order.status === 'menunggu_pembayaran';
            },

            paymentUrl(order) {
                return new URL(this.paymentPageUrlTemplate.replace('__ID__', order.id), window.location.origin).pathname;
            },

            isTimeout(order) {
                return this.isSearching(order) && order.seconds_since_created > 120;
            },

            elapsedLabel(order) {
                const mins = Math.floor(order.seconds_since_created / 60);
                return mins < 1 ? 'Baru saja' : `${mins} menit lalu`;
            },

            async acceptOffer(offerId) {
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const path = new URL(this.acceptOfferUrlTemplate.replace('__ID__', offerId), window.location.origin).pathname;
                    const res = await fetch(path, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                    });
                    const data = await res.json();
                    customerNotify(data.message, data.success);
                    if (data.success) await this.fetchOrders(true);
                } catch (e) {
                    customerNotify('Gagal memilih tawaran. Coba lagi.', false);
                } finally {
                    this.actionLoading = false;
                }
            },

            async expandRadius(orderId) {
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const path = new URL(this.expandRadiusUrlTemplate.replace('__ID__', orderId), window.location.origin).pathname;
                    const res = await fetch(path, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                    });
                    const data = await res.json();
                    customerNotify(data.message, data.success);
                    if (data.success) await this.fetchOrders(true);
                } catch (e) {
                    customerNotify('Gagal memperluas radius. Coba lagi.', false);
                } finally {
                    this.actionLoading = false;
                }
            },

            async cancelOrder(orderId) {
                if (this.actionLoading) return;
                if (!confirm('Yakin ingin membatalkan pesanan ini?')) return;

                this.actionLoading = true;
                try {
                    const path = new URL(this.cancelOrderUrlTemplate.replace('__ID__', orderId), window.location.origin).pathname;
                    const res = await fetch(path, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                    });
                    const data = await res.json();
                    customerNotify(data.message, data.success);
                    if (data.success) await this.fetchOrders(true);
                } catch (e) {
                    customerNotify('Gagal membatalkan pesanan. Coba lagi.', false);
                } finally {
                    this.actionLoading = false;
                }
            },
        };
    }
</script>

<div class="min-h-screen bg-[#F3F4F6] pb-16"
    x-data='customerDashboard({
        csrfToken: @json(csrf_token()),
        activeFeedUrl: @json(route("customer.orders.active-feed")),
        acceptOfferUrlTemplate: @json(route("customer.offers.accept", ["id" => "__ID__"])),
        expandRadiusUrlTemplate: @json(route("customer.orders.expand-radius", ["id" => "__ID__"])),
        cancelOrderUrlTemplate: @json(route("customer.orders.cancel", ["id" => "__ID__"])),
        paymentPageUrlTemplate: @json(route("customer.orders.payment.page", ["id" => "__ID__"])),
    })'
>
    <!-- Desktop Search Header & Profile (Gojek App Bar Style) -->
    <div class="bg-white border-b border-slate-200/80 sticky top-20 z-40 px-4 py-3.5 shadow-sm">
        <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
            
            <!-- Search Bar (Gojek Style) -->
            <div class="relative flex-grow">
                <input 
                    type="text" 
                    placeholder="Cari makanan khas Malang, minimarket, atau jastiper..." 
                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-700 pl-11 pr-4 py-2.5 rounded-full text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150"
                >
                <div class="absolute left-4 top-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <!-- Profile Info (Gojek Circular Profile Icon) -->
            <div class="flex items-center gap-3">
                <div class="relative group cursor-pointer">
                    <div class="w-10 h-10 bg-slate-900 border border-slate-800 rounded-full flex items-center justify-center font-bold text-sm text-rose-500 shadow-md">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                </div>
                
                <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 p-2.5 rounded-full transition border border-slate-200" title="Keluar Sesi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-md mx-auto px-4 mt-6 space-y-6">
        
        <!-- Welcome Greeting -->
        <div>
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Selamat Datang</span>
            <h2 class="font-display font-black text-xl text-slate-900 leading-none mt-1">{{ $customer->name }}</h2>
        </div>

        <!-- Gopay Card Layout (JastipKuy Pay) -->
        <div class="bg-gradient-to-r from-sky-600 to-sky-700 text-white rounded-3xl p-5 shadow-lg border border-sky-500/20 relative overflow-hidden">
            <!-- Decorative Accent circles inside card -->
            <div class="absolute -right-12 -top-12 w-28 h-28 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute -left-6 -bottom-6 w-20 h-20 bg-white/5 rounded-full blur-md"></div>
            
            <div class="flex items-stretch justify-between relative z-10 gap-3">
                <!-- Left Section: Balance & Brand -->
                <div class="flex flex-col justify-between space-y-3 pr-4 border-r border-white/20">
                    <div class="flex items-center gap-1.5">
                        <!-- Tiny GoPay-style Logo -->
                        <span class="text-[10px] font-black tracking-tighter bg-white text-sky-700 px-2 py-0.5 rounded-sm uppercase">JK PAY</span>
                    </div>
                    <div>
                        <div class="text-2xl font-black font-display tracking-tight">Rp0</div>
                        <span class="text-[8px] text-sky-200 font-semibold block">Tap untuk riwayat dompet</span>
                    </div>
                </div>

                <!-- Right Section: Quick Action Buttons (Gojek Icon Row Style) -->
                <div class="flex-grow grid grid-cols-4 gap-2 items-center justify-between text-center">
                    
                    <!-- Bayar (Pay) -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 11v3m5-13H7m0 10h10m-5-10v10"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Bayar</span>
                    </button>

                    <!-- Top Up -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Isi Saldo</span>
                    </button>

                    <!-- Riwayat -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Riwayat</span>
                    </button>

                    <!-- Eksplor -->
                    <button onclick="showMaintenanceToast(event)" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Lainnya</span>
                    </button>

                </div>
            </div>
        </div>

        <!-- Instant Booking Banner (Gojek Promo style) -->
        <a href="{{ route('customer.booking') }}" class="block bg-slate-950 border border-slate-800 text-white rounded-3xl p-4 shadow-sm hover:scale-[1.01] transition duration-150 relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-rose-600/20 rounded-full blur-lg"></div>
            <div class="flex justify-between items-center gap-3 relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-rose-600 rounded-2xl flex items-center justify-center shrink-0 shadow-md shadow-rose-600/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-display font-black text-xs uppercase tracking-wider text-rose-500">Booking Jastiper</h4>
                        <p class="text-[10px] text-slate-300 font-semibold mt-0.5 leading-tight">Lihat Jastiper yang sedang check-in di Mie Gacoan & Toko terdekat!</p>
                    </div>
                </div>
                <span class="text-xs bg-rose-600 hover:bg-rose-700 text-white font-extrabold px-3 py-1.5 rounded-full uppercase tracking-wider shrink-0">
                    Cek
                </span>
            </div>
        </a>

        <!-- JastipKuy Services Horizontal Row (Menu Grid Redesign with Flexbox to force horizontal layout) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-4">
            <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Layanan Belanja Jastip</h3>
            
            <div class="flex items-start justify-between text-center px-1">
                
                <!-- Jastip Kuliner -->
                <a href="{{ route('customer.orders.create') }}?cat=beli-antar" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-rose-600 hover:bg-rose-700 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-rose-600/20 shrink-0">
                        <!-- Food Cloche Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 00-9 9h18a9 9 0 00-9-9zM3 12h18M5 12v3a2 2 0 002 2h10a2 2 0 002-2v-3M12 2v1" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Kuliner<br><span class="text-[8px] text-slate-500 font-medium">(Kuliner)</span></span>
                </a>

                <!-- Jastip Minimarket -->
                <a href="{{ route('customer.orders.create') }}?cat=toko-kirim" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-sky-600 hover:bg-sky-700 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-sky-600/20 shrink-0">
                        <!-- Shopping Bag Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Toko<br><span class="text-[8px] text-slate-500 font-medium">(Minimarket)</span></span>
                </a>

                <!-- Jastip Pasar -->
                <a href="{{ route('customer.orders.create') }}?cat=ambil-antar" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-amber-500 hover:bg-amber-600 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-amber-500/20 shrink-0">
                        <!-- Shopping Cart Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Pasar<br><span class="text-[8px] text-slate-500 font-medium">(Pasar)</span></span>
                </a>

                <!-- Jastip Bebas -->
                <a href="{{ route('customer.orders.create') }}?cat=kirim-pihak-ketiga" class="group flex flex-col items-center gap-2 focus:outline-none w-20">
                    <div class="w-12 h-12 bg-emerald-600 hover:bg-emerald-700 hover:scale-105 rounded-2xl flex items-center justify-center transition duration-150 shadow-md shadow-emerald-600/20 shrink-0">
                        <!-- 3D Box Package Icon -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-850 leading-tight">Jastip Bebas<br><span class="text-[8px] text-slate-500 font-medium">(Titip Bebas)</span></span>
                </a>

            </div>
        </div>

        <!-- Promos Ads Banner Carousel (Redesign Iklan Gojek) -->
        <div class="space-y-3">
            <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Promo Rekomendasi</h3>
            
            <div class="w-full bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-3xl p-5 shadow-sm relative overflow-hidden border border-rose-400/20">
                <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-lg"></div>
                <div class="relative z-10 space-y-3">
                    <span class="text-[8px] bg-white text-rose-600 px-2 py-0.5 rounded-full font-black uppercase tracking-wider">Promo Khusus</span>
                    <div>
                        <h4 class="font-display font-black text-sm">Diskon Ongkir Jastip s.d 50% 🚀</h4>
                        <p class="text-[9px] text-rose-100 mt-1 max-w-[240px]">Belanja di mana saja se-Malang Raya lebih murah menggunakan kurir mitra JastipKuy Pro.</p>
                    </div>
                    <div class="text-[8px] text-rose-200/90 font-mono">*Syarat & ketentuan berlaku.</div>
                </div>
            </div>
        </div>

        <!-- Pelacakan Pesanan Aktif (Active Order Tracker + Bidding List) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-4">
            <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Pesanan Aktif Anda</h3>

            <!-- Loading skeleton saat pertama kali load -->
            <div x-show="!initialLoaded" class="space-y-3">
                <div class="h-20 bg-slate-100 rounded-2xl animate-pulse"></div>
            </div>

            <!-- Empty state -->
            <div x-show="initialLoaded && orders.length === 0" x-cloak>
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-2xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 text-left">
                        <div class="w-10 h-10 bg-slate-200/50 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-[11px] text-slate-700">Belum Ada Belanjaan Aktif</h4>
                            <p class="text-[9px] text-slate-400 leading-normal mt-0.5">Riwayat & posisi kurir akan muncul di sini setelah memesan.</p>
                        </div>
                    </div>

                    <a href="{{ route('customer.orders.create') }}" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] px-4 py-2.5 rounded-full transition uppercase tracking-wider whitespace-nowrap shadow-sm">
                        Pesan Jastip
                    </a>
                </div>
            </div>

            <!-- Daftar order aktif (real-time via polling) -->
            <div class="space-y-3">
                <template x-for="order in orders" :key="order.id">
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">

                        <!-- Header: deskripsi + estimasi/agreed fare -->
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 text-left min-w-0">
                                <div class="w-10 h-10 bg-rose-50 rounded-full flex items-center justify-center shrink-0 text-lg">📦</div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-[11px] text-slate-750 line-clamp-1" x-text="order.description"></h4>
                                    <p class="text-[9px] text-slate-400 leading-normal mt-0.5">
                                        Status:
                                        <span class="font-extrabold uppercase"
                                            :class="order.status === 'menunggu_pembayaran' ? 'text-rose-500' : (['deal', 'diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan'].includes(order.status) ? 'text-emerald-500' : 'text-amber-500')"
                                            x-text="order.status === 'menunggu_pembayaran' ? 'Menunggu Pembayaran' : (order.status === 'deal' ? 'Deal Terbentuk' : (order.status === 'diproses' ? 'Sedang Diproses' : (order.status === 'ada_tawaran' ? 'Ada Tawaran Masuk' : 'Menunggu Jastiper')))"></span>
                                    </p>
                                    <p x-show="order.jastiper" x-cloak class="text-[8px] text-slate-500 mt-0.5">
                                        Mitra Jastiper: <b x-text="order.jastiper?.name"></b> (<span x-text="order.jastiper?.phone_number"></span>)
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[8px] uppercase font-bold text-slate-400 block tracking-wide" x-text="order.status === 'deal' ? 'Ongkos Disepakati' : 'Estimasi Ongkir'"></span>
                                <span class="text-xs font-black text-rose-600" x-text="order.status === 'deal' ? order.agreed_fare_formatted : order.estimated_fare_formatted"></span>
                            </div>
                        </div>

                        <!-- Banner Bayar Sekarang: muncul begitu order 'menunggu_pembayaran' -->
                        <template x-if="needsPayment(order)">
                            <a :href="paymentUrl(order)"
                                class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide flex items-center justify-center gap-1.5">
                                <span>💳</span>
                                <span>Bayar Sekarang</span>
                            </a>
                        </template>

                        <!-- Tombol Chat: hanya muncul begitu order sudah deal (jastiper terkunci) -->
                        <template x-if="!isSearching(order)">
                            <button type="button"
                                @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: order.id, orderLabel: order.description } }))"
                                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide flex items-center justify-center gap-1.5">
                                <span>💬</span>
                                <span>Chat dengan <span x-text="order.jastiper?.name || 'Jastiper'"></span></span>
                            </button>
                        </template>

                        <!-- Widget Timeout: muncul kalau masih mencari & sudah lewat 2 menit -->
                        <template x-if="isSearching(order)">
                            <div>
                                <div class="flex items-center gap-1.5 px-1">
                                    <span class="flex gap-0.5">
                                        <span class="w-1 h-1 bg-amber-500 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                        <span class="w-1 h-1 bg-amber-500 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                        <span class="w-1 h-1 bg-amber-500 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                                    </span>
                                    <span class="text-[9px] font-bold text-amber-600">Mencari Jastiper... (<span x-text="elapsedLabel(order)"></span>)</span>
                                </div>

                                <div x-show="isTimeout(order)" x-cloak class="mt-2 bg-amber-50 border border-amber-100 rounded-xl p-3 space-y-2">
                                    <p class="text-[9px] font-semibold text-amber-700">Belum ada tawaran cocok?</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" @click="expandRadius(order.id)" :disabled="actionLoading"
                                            class="bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white font-bold text-[9px] py-2 rounded-xl transition uppercase tracking-wide">
                                            Perluas Radius
                                        </button>
                                        <button type="button" @click="cancelOrder(order.id)" :disabled="actionLoading"
                                            class="bg-slate-200 hover:bg-slate-300 disabled:opacity-50 text-slate-700 font-bold text-[9px] py-2 rounded-xl transition uppercase tracking-wide">
                                            Batalkan Pesanan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- List Tawaran Masuk (Bidding List) -->
                        <div x-show="order.offers && order.offers.length > 0" x-cloak class="space-y-2 pt-1">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-wider px-1">Tawaran Masuk (<span x-text="order.offers?.length"></span>)</p>
                            <template x-for="offer in order.offers" :key="offer.offer_id">
                                <div class="bg-white border border-slate-200 rounded-2xl p-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 bg-slate-900 text-rose-400 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0"
                                            x-text="offer.jastiper_name.substring(0,2).toUpperCase()"></div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-bold text-slate-800 truncate" x-text="offer.jastiper_name"></p>
                                            <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                                <span class="text-[8px] font-bold text-amber-500" x-show="offer.rating_avg">⭐ <span x-text="offer.rating_avg"></span> (<span x-text="offer.completed_orders_count"></span> Selesai)</span>
                                                <span class="text-[8px] font-bold text-slate-400" x-show="!offer.rating_avg">Jastiper Baru</span>
                                                <span class="text-[7px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-wider"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-600': offer.response_speed_tier === 'fast',
                                                        'bg-sky-50 text-sky-600': offer.response_speed_tier === 'medium',
                                                        'bg-slate-100 text-slate-500': offer.response_speed_tier === 'normal',
                                                    }"
                                                    x-text="offer.response_speed_label"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 flex flex-col items-end gap-1">
                                        <span class="text-sm font-black text-rose-600" x-text="offer.offered_price_formatted"></span>
                                        <button type="button" @click="acceptOffer(offer.offer_id)" :disabled="actionLoading"
                                            class="bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-[8px] font-black px-3 py-1.5 rounded-full uppercase tracking-wider whitespace-nowrap">
                                            Pilih Jastiper
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>


        <!-- Jastiper Favorit Real-time Status -->
        @if($customer->favorites->isNotEmpty())
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-3">
                <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Jastiper Favorit Anda</h3>
                <div class="space-y-2">
                    @foreach($customer->favorites as $fav)
                        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs shadow-inner" x-data='{ available: @json($fav->is_available), checkin: @json($fav->checkin_location) }'>
                            <div class="flex items-center gap-2.5">
                                <div class="w-2.5 h-2.5 rounded-full" :class="available ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></div>
                                <div>
                                    <span class="font-bold text-slate-800">{{ $fav->name }}</span>
                                    <span class="text-[9px] text-slate-400 block mt-0.5" x-text="checkin ? 'Check-in: ' + checkin : 'Tidak check-in'"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="
                                    fetch('/customer/jastiper/{{ $fav->id }}/availability')
                                        .then(r => r.json())
                                        .then(data => {
                                            available = data.is_available;
                                            checkin = data.checkin_location;
                                        })
                                " class="bg-white hover:bg-slate-105 border border-slate-200 px-2.5 py-1 rounded-full text-[9px] font-bold text-slate-600 transition shrink-0 uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-2.5 h-2.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3-3 3 3m-3-3v12" />
                                    </svg>
                                    <span>Cek</span>
                                </button>
                                <a :href="available ? '{{ route('customer.orders.create') }}?jastiper_id={{ $fav->id }}' : '#'" :class="available ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-sm' : 'bg-slate-200 text-slate-400 cursor-not-allowed'" class="px-3.5 py-1.5 rounded-full text-[9px] font-black transition shrink-0 uppercase tracking-wider">
                                    Pesan
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>

@include('components.chat.order-chat-modal', [
    'viewerRole' => 'customer',
    'chatSendUrlTemplate' => route('customer.orders.chat.send', ['id' => '__ID__']),
    'chatHistoryUrlTemplate' => route('customer.orders.chat.history', ['id' => '__ID__']),
])
@endsection