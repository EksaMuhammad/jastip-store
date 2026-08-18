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

            // Addon Modal State
            addonModalOpen: false,
            addonOrderId: null,
            addonDescription: '',
            addonLoading: false,

            openAddonModal(orderId) {
                this.addonOrderId = orderId;
                this.addonDescription = '';
                this.addonModalOpen = true;
            },

            closeAddonModal() {
                this.addonModalOpen = false;
                this.addonOrderId = null;
            },

            async submitAddon() {
                if (!this.addonDescription.trim() || this.addonLoading || !this.addonOrderId) return;
                this.addonLoading = true;
                try {
                    const path = `/customer/orders/${this.addonOrderId}/addon`;
                    const res = await fetch(path, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ description: this.addonDescription })
                    });
                    const data = await res.json();
                    customerNotify(data.message || (data.success ? 'Berhasil mengajukan tambahan item!' : 'Gagal'), data.success);
                    if (data.success) {
                        this.closeAddonModal();
                        await this.fetchOrders(true);
                    }
                } catch (e) {
                    customerNotify('Terjadi kesalahan koneksi.', false);
                } finally {
                    this.addonLoading = false;
                }
            },

            async submitAddonPayment(orderId, addonId, method) {
                if (this.actionLoading) return;
                this.actionLoading = true;
                try {
                    const res = await fetch(`/customer/orders/${orderId}/addon/${addonId}/pay`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ payment_method: method })
                    });
                    const data = await res.json();
                    customerNotify(data.message, data.success);
                    if (data.success) {
                        await this.fetchOrders(true);
                    }
                } catch (e) {
                    customerNotify('Gagal memproses pembayaran tambahan.', false);
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
        <div class="bg-telkomsel-pattern-card text-white rounded-3xl p-5 shadow-lg border border-rose-500/20 relative overflow-hidden">
            
            <div class="flex items-stretch justify-between relative z-10 gap-3">
                <!-- Left Section: Balance & Brand -->
                <div class="flex flex-col justify-between space-y-3 pr-4 border-r border-white/20">
                    <div class="flex items-center gap-1.5">
                        <!-- Tiny GoPay-style Logo -->
                        <span class="text-[10px] font-black tracking-tighter bg-white text-rose-600 px-2 py-0.5 rounded-sm uppercase">JK PAY</span>
                    </div>
                    <div>
                        <div class="text-2xl font-black font-display tracking-tight">Rp{{ number_format($balance, 0, ',', '.') }}</div>
                        <span class="text-[8px] text-rose-100 font-semibold block">Tap untuk riwayat dompet</span>
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
                    <a href="{{ route('customer.wallet') }}" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Isi Saldo</span>
                    </a>

                    <!-- Riwayat -->
                    <a href="{{ route('customer.wallet') }}" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                        <div class="w-9 h-9 bg-white/15 group-hover:bg-white/25 rounded-xl flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <span class="text-[9px] font-bold text-white tracking-wide">Riwayat</span>
                    </a>

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
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 px-1">
                
                <!-- 1. Beli-Antar -->
                <a href="{{ route('customer.orders.create') }}?cat=beli-antar" class="group bg-white border border-slate-200 hover:border-rose-400 hover:shadow-md hover:shadow-rose-500/10 rounded-2xl p-3 flex flex-col items-center justify-center text-center transition duration-200 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                    <img src="{{ asset('images/services/beli-antar.png') }}" alt="Beli-Antar" class="w-20 h-16 object-contain mb-2 group-hover:scale-110 transition-transform drop-shadow-sm rounded-xl">
                    <span class="text-[11px] font-bold text-slate-800 leading-tight mb-0.5">Beli-Antar</span>
                    <span class="text-[8px] text-slate-500 font-medium">Jastip Kuliner / Makanan</span>
                </a>

                <!-- 2. Ambil & Antar -->
                <a href="{{ route('customer.orders.create') }}?cat=ambil-antar" class="group bg-white border border-slate-200 hover:border-sky-400 hover:shadow-md hover:shadow-sky-500/10 rounded-2xl p-3 flex flex-col items-center justify-center text-center transition duration-200 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                    <img src="{{ asset('images/services/ambil-antar.png') }}" alt="Ambil & Antar" class="w-20 h-16 object-contain mb-2 group-hover:scale-110 transition-transform drop-shadow-sm rounded-xl">
                    <span class="text-[11px] font-bold text-slate-800 leading-tight mb-0.5">Ambil & Antar</span>
                    <span class="text-[8px] text-slate-500 font-medium">Ambil barang / COD</span>
                </a>

                <!-- 3. Toko Kirim -->
                <a href="{{ route('customer.orders.create') }}?cat=toko-kirim" class="group bg-white border border-slate-200 hover:border-amber-400 hover:shadow-md hover:shadow-amber-500/10 rounded-2xl p-3 flex flex-col items-center justify-center text-center transition duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                    <img src="{{ asset('images/services/toko-kirim.png') }}" alt="Toko Kirim" class="w-20 h-16 object-contain mb-2 group-hover:scale-110 transition-transform drop-shadow-sm rounded-xl">
                    <span class="text-[11px] font-bold text-slate-800 leading-tight mb-0.5">Toko Kirim</span>
                    <span class="text-[8px] text-slate-500 font-medium">Belanja Minimarket/Pasar</span>
                </a>

                <!-- 4. Dokumen Kecil -->
                <a href="{{ route('customer.orders.create') }}?cat=dokumen" class="group bg-white border border-slate-200 hover:border-emerald-400 hover:shadow-md hover:shadow-emerald-500/10 rounded-2xl p-3 flex flex-col items-center justify-center text-center transition duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <img src="{{ asset('images/services/dokumen.png') }}" alt="Dokumen Kecil" class="w-20 h-16 object-contain mb-2 group-hover:scale-110 transition-transform drop-shadow-sm rounded-xl">
                    <span class="text-[11px] font-bold text-slate-800 leading-tight mb-0.5">Dokumen Kecil</span>
                    <span class="text-[8px] text-slate-500 font-medium">Kirim surat / dokumen</span>
                </a>

                <!-- 5. Multi-Stop -->
                <a href="{{ route('customer.orders.create') }}?cat=multi-stop" class="group bg-white border border-slate-200 hover:border-purple-400 hover:shadow-md hover:shadow-purple-500/10 rounded-2xl p-3 flex flex-col items-center justify-center text-center transition duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                    <img src="{{ asset('images/services/multi-stop.png') }}" alt="Multi-Stop" class="w-20 h-16 object-contain mb-2 group-hover:scale-110 transition-transform drop-shadow-sm rounded-xl">
                    <span class="text-[11px] font-bold text-slate-800 leading-tight mb-0.5">Multi-Stop</span>
                    <span class="text-[8px] text-slate-500 font-medium">Banyak titik belanja/antar</span>
                </a>

                <!-- 6. Pihak Ketiga -->
                <a href="{{ route('customer.orders.create') }}?cat=kirim-pihak-ketiga" class="group bg-white border border-slate-200 hover:border-indigo-400 hover:shadow-md hover:shadow-indigo-500/10 rounded-2xl p-3 flex flex-col items-center justify-center text-center transition duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <img src="{{ asset('images/services/kirim-pihak-ketiga.png') }}" alt="Pihak Ketiga" class="w-20 h-16 object-contain mb-2 group-hover:scale-110 transition-transform drop-shadow-sm rounded-xl">
                    <span class="text-[11px] font-bold text-slate-800 leading-tight mb-0.5">Pihak Ketiga</span>
                    <span class="text-[8px] text-slate-500 font-medium">Ekspedisi / Agen Kirim</span>
                </a>

            </div>
        </div>

        <!-- Promos Ads Banner Carousel (Redesign Iklan Gojek) -->
        <div class="space-y-3">
            <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Promo Rekomendasi</h3>
            
            <div class="w-full bg-telkomsel-pattern-promo text-white rounded-3xl p-5 shadow-sm relative overflow-hidden border border-rose-400/20">
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
                                            x-text="order.status === 'menunggu_pembayaran' ? 'Menunggu Pembayaran' : (order.status === 'deal' ? 'Deal Terbentuk' : (order.status === 'diproses' ? 'Sedang Diproses' : (order.status === 'barang_diambil' ? 'Barang Diambil' : (order.status === 'sedang_diantar' ? 'Sedang Diantar' : (order.status === 'tiba_tujuan' ? 'Tiba di Tujuan' : (order.status === 'ada_tawaran' ? 'Ada Tawaran Masuk' : 'Menunggu Jastiper')))))))"></span>
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

                        <!-- Konfirmasi Terima Barang: muncul saat order 'tiba_tujuan' -->
                        <template x-if="order.status === 'tiba_tujuan'">
                            <div class="flex gap-2">
                                <form :action="`/customer/orders/${order.id}/confirm`" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                        Konfirmasi Diterima
                                    </button>
                                </form>
                                <a :href="`/customer/orders/${order.id}/report`" class="w-full bg-white border border-amber-500 hover:bg-amber-50 text-amber-600 font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block flex items-center justify-center">
                                    Barang Tidak Sesuai
                                </a>
                            </div>
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

                        <!-- Tambahan Item (Addon) UI untuk Customer -->
                        <template x-if="['diproses', 'barang_diambil'].includes(order.status)">
                            <div class="mt-1.5">
                                <button type="button" @click="openAddonModal(order.id)" class="w-full bg-white border border-rose-500 text-rose-600 hover:bg-rose-50 font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide flex items-center justify-center gap-1.5 shadow-sm">
                                    <span>➕</span>
                                    <span>Minta Tambahan Item</span>
                                </button>
                            </div>
                        </template>

                        <!-- Riwayat Tambahan Item -->
                        <template x-if="order.addons && order.addons.length > 0">
                            <div class="mt-3 bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
                                <h4 class="text-[9px] font-black text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    Riwayat Tambahan
                                </h4>
                                <div class="space-y-2">
                                    <template x-for="addon in order.addons" :key="addon.id">
                                        <div class="flex flex-col bg-slate-50 border border-slate-100 p-2.5 rounded-lg gap-2">
                                            <div class="flex justify-between items-start">
                                                <div class="min-w-0 pr-2 flex-1">
                                                    <p class="text-[10px] font-bold text-slate-800 line-clamp-2" x-text="addon.description"></p>
                                                </div>
                                                <div class="shrink-0 text-right">
                                                    <template x-if="addon.payment_status === 'pending_jastiper'">
                                                        <span class="text-[8px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">Menunggu Jastiper</span>
                                                    </template>
                                                    <template x-if="addon.payment_status === 'rejected'">
                                                        <span class="text-[8px] bg-slate-200 text-slate-500 px-2 py-0.5 rounded-full font-bold line-through">Ditolak</span>
                                                    </template>
                                                    <template x-if="['pending_payment', 'paid_transfer', 'paid_cod'].includes(addon.payment_status)">
                                                        <div class="flex flex-col items-end">
                                                            <template x-if="addon.payment_status === 'pending_payment'">
                                                                <span class="text-[8px] bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold mb-1">Pilih Pembayaran</span>
                                                            </template>
                                                            <template x-if="addon.payment_status === 'paid_transfer'">
                                                                <span class="text-[8px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold mb-1">Lunas (TF)</span>
                                                            </template>
                                                            <template x-if="addon.payment_status === 'paid_cod'">
                                                                <span class="text-[8px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold mb-1">Bayar COD</span>
                                                            </template>
                                                            <span class="text-[9px] font-black text-rose-600" x-text="'+ Rp ' + new Intl.NumberFormat('id-ID').format(addon.additional_fare)"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            
                                            <!-- Action Buttons for pending payment -->
                                            <template x-if="addon.payment_status === 'pending_payment'">
                                                <div class="grid grid-cols-2 gap-2 mt-1 border-t border-slate-100 pt-2">
                                                    <button type="button" @click="submitAddonPayment(order.id, addon.id, 'transfer')" class="bg-rose-600 hover:bg-rose-700 text-white text-[9px] font-bold py-1.5 rounded-md uppercase tracking-wider transition">Transfer</button>
                                                    <button type="button" @click="submitAddonPayment(order.id, addon.id, 'cod')" class="bg-slate-800 hover:bg-slate-900 text-white text-[9px] font-bold py-1.5 rounded-md uppercase tracking-wider transition">Bayar COD</button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
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
                                                <span class="inline-flex items-center gap-1 border px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider"
                                                    :class="{
                                                        'bg-amber-700 text-amber-50 border-amber-800': offer.badge_level === 'bronze' || !offer.badge_level,
                                                        'bg-slate-400 text-slate-900 border-slate-500': offer.badge_level === 'silver',
                                                        'bg-amber-400 text-amber-900 border-amber-500': offer.badge_level === 'gold',
                                                        'bg-cyan-300 text-cyan-900 border-cyan-400': offer.badge_level === 'platinum'
                                                    }"
                                                    :title="'Jastiper ' + (offer.badge_level ? offer.badge_level.charAt(0).toUpperCase() + offer.badge_level.slice(1) : 'Bronze')">
                                                    <span x-text="offer.badge_level === 'platinum' ? '💎' : (offer.badge_level === 'gold' ? '👑' : (offer.badge_level === 'silver' ? '🛡️' : '🥉'))"></span>
                                                    <span x-text="offer.badge_level || 'bronze'"></span>
                                                </span>
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

        <!-- Pesanan Selesai — Ajakan Beri Rating (Sprint 8 — Epic 6 Bagian 1) -->
        @if($unratedCompletedOrders->isNotEmpty())
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-3">
                <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Beri Rating Pesanan Anda</h3>
                <div class="space-y-2.5">
                    @foreach($unratedCompletedOrders as $order)
                        <div class="flex items-center justify-between gap-3 p-3.5 bg-amber-50 border border-amber-100 rounded-2xl">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 bg-amber-400/20 rounded-full flex items-center justify-center shrink-0 text-base">
                                    ⭐
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-[11px] text-slate-800 truncate">{{ $order->description }}</p>
                                    <p class="text-[9px] text-slate-500 font-medium mt-0.5">
                                        Jastiper: {{ $order->jastiper->name ?? '-' }}
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('customer.orders.rating.form', ['id' => $order->id]) }}" class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white font-bold text-[9px] px-3.5 py-2 rounded-full transition uppercase tracking-wider whitespace-nowrap shadow-sm shrink-0">
                                Beri Rating
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


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

    <!-- Global Addon Modal untuk Customer -->
    <div x-show="addonModalOpen" class="fixed inset-0 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="z-index: 9999; display: none;" x-transition>
        <div @click.away="closeAddonModal()" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5 overflow-hidden relative">
            <div class="flex justify-between items-center mb-3 border-b border-slate-100 pb-2">
                <h3 class="text-sm font-black text-slate-800">Minta Tambahan Item</h3>
                <button @click="closeAddonModal()" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-[10px] text-slate-500 mb-3 leading-normal">Ada yang kelupaan? Tulis detail barang tambahan yang ingin dititip ke jastiper.</p>
            
            <textarea x-model="addonDescription" class="w-full border-slate-200 border rounded-xl p-3 text-[11px] focus:ring-rose-500 focus:border-rose-500 mb-4 bg-slate-50 resize-none outline-none" rows="3" placeholder="Misal: Tolong sekalian belikan air mineral 1 botol..."></textarea>
            
            <button @click="submitAddon()" :disabled="addonLoading || !addonDescription.trim()" class="w-full bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-[10px] font-bold py-3 rounded-xl uppercase tracking-wider transition flex items-center justify-center">
                <span x-show="!addonLoading">Kirim Permintaan</span>
                <span x-show="addonLoading" class="flex items-center gap-2">
                    <svg class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Mengirim...
                </span>
            </button>
        </div>
    </div>

</div>

@include('components.chat.order-chat-modal', [
    'viewerRole' => 'customer',
    'chatSendUrlTemplate' => route('customer.orders.chat.send', ['id' => '__ID__']),
    'chatHistoryUrlTemplate' => route('customer.orders.chat.history', ['id' => '__ID__']),
])
@endsection