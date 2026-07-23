@extends('layouts.support')

@section('title', 'Dashboard Jastiper')

@section('styles')
    <!-- Leaflet CSS & JS loaded in Head to avoid race conditions -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endsection

@section('content')

<script>
    // Helper toast ringan, pakai #toast-container yang sudah disediakan layouts.support
    function jastiperNotify(message, success = true) {
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

    // Alpine component untuk seluruh dashboard jastiper: filter kategori,
    // feed order dengan polling real-time, dan bidding (individual + bulk).
    // Catatan: toggle online/offline SENGAJA tidak ditaruh di sini — itu pakai
    // <form> HTML biasa (submit langsung ke server), supaya tombolnya selalu
    // pasti berfungsi walau ada masalah pada JS/Alpine di bagian lain halaman.
    function jastiperDashboard(config) {
        return {
            online: config.online,
            verified: config.verified,
            category: '',
            orders: [],
            selected: [],
            orderPrices: {},
            loading: false,
            initialFeedLoaded: false,
            feedMessage: null,
            pollHandle: null,
            feedUrl: config.feedUrl,
            offerUrlTemplate: config.offerUrlTemplate,
            multiOfferUrl: config.multiOfferUrl,
            csrfToken: config.csrfToken,

            init() {
                if (this.verified && this.online) {
                    this.fetchFeed();
                }

                // Polling tiap 8 detik selama online & akun terverifikasi
                this.pollHandle = setInterval(() => {
                    if (this.verified && this.online) {
                        this.fetchFeed(true);
                    }
                }, 8000);
            },

            async fetchFeed(silent = false) {
                if (!silent) this.loading = true;

                try {
                    const path = new URL(this.feedUrl, window.location.origin).pathname;
                    const url = new URL(path, window.location.origin);
                    if (this.category) {
                        url.searchParams.set('category', this.category);
                    }

                    const res = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json();

                    this.orders = data.orders || [];
                    this.feedMessage = data.message;
                    // Buang seleksi yang order-nya sudah tidak ada lagi di feed terbaru
                    this.selected = this.selected.filter(id => this.orders.some(o => o.id === id));
                    // Isi harga tawaran default (harga tawaran aktif jastiper ini kalau sudah
                    // pernah bid, atau harga estimasi kalau belum) — hanya sekali per order,
                    // supaya tidak menimpa harga yang sedang diketik ulang oleh jastiper.
                    this.orders.forEach(o => {
                        if (this.orderPrices[o.id] === undefined) {
                            this.orderPrices[o.id] = o.my_offer_price ?? o.estimated_fare;
                        }
                    });
                } catch (e) {
                    this.feedMessage = 'Gagal memuat daftar order. Periksa koneksi Anda.';
                } finally {
                    this.loading = false;
                    this.initialFeedLoaded = true;
                }
            },

            toggleSelect(id) {
                if (this.selected.includes(id)) {
                    this.selected = this.selected.filter(x => x !== id);
                } else {
                    this.selected.push(id);
                }
            },

            // Kirim/ubah SATU tawaran dengan harga custom yang diketik jastiper.
            async submitOffer(orderId) {
                if (this.loading) return;

                const price = Number(this.orderPrices[orderId]);
                if (!price || price < 5000) {
                    jastiperNotify('Harga tawaran minimal Rp 5.000.', false);
                    return;
                }

                this.loading = true;
                try {
                    const path = new URL(this.offerUrlTemplate.replace('__ID__', orderId), window.location.origin).pathname;
                    const url = new URL(path, window.location.origin);
                    const res = await fetch(url.toString(), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ offered_price: price }),
                    });
                    const data = await res.json();

                    jastiperNotify(data.message, data.success);
                    if (data.success) await this.fetchFeed(true);
                } catch (e) {
                    jastiperNotify('Gagal mengirim tawaran. Coba lagi.', false);
                } finally {
                    this.loading = false;
                }
            },

            // Kirim tawaran BANYAK order sekaligus, masing-masing di harga estimasi otomatis
            // (kalau mau harga custom, pakai form 1-per-1 di card masing-masing sebelum ini).
            async submitOffers(ids) {
                if (!ids.length || this.loading) return;
                this.loading = true;

                try {
                    const path = new URL(this.multiOfferUrl, window.location.origin).pathname;
                    const url = new URL(path, window.location.origin);
                    const res = await fetch(url.toString(), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ order_ids: ids }),
                    });
                    const data = await res.json();

                    jastiperNotify(data.message, data.success);
                    this.selected = [];
                    await this.fetchFeed(true);
                } catch (e) {
                    jastiperNotify('Gagal mengirim tawaran sekaligus. Coba lagi.', false);
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>

<div class="min-h-screen bg-[#F3F4F6] pb-16"
    x-data='jastiperDashboard({
        online: @json($jastiper->work_status !== "offline"),
        verified: @json($jastiper->verification_status === "approved"),
        csrfToken: @json(csrf_token()),
        feedUrl: @json(route("jastiper.orders.feed")),
        offerUrlTemplate: @json(route("jastiper.orders.offer", ["id" => "__ID__"])),
        multiOfferUrl: @json(route("jastiper.orders.multi-offer")),
    })'
>
    <!-- Active Status Bar (Gojek Driver Status Banner) -->
    <div class="bg-slate-950 text-white border-b border-slate-800 sticky top-20 z-40 px-4 py-3.5 shadow-sm">
        <div class="max-w-4xl mx-auto flex items-center justify-between gap-4 flex-wrap">

            <!-- Driver Info & Status -->
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 bg-slate-800 border border-slate-700 rounded-full flex items-center justify-center font-bold text-sm text-rose-500 shadow-md">
                        {{ strtoupper(substr($jastiper->name, 0, 2)) }}
                    </div>
                    <!-- Indicator dot -->
                    <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border border-slate-950 transition duration-150 {{ $jastiper->work_status !== 'offline' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-500' }}"></span>
                </div>
                <div>
                    <h2 class="font-display font-extrabold text-sm text-white">{{ $jastiper->name }}</h2>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">{{ $jastiper->work_status !== 'offline' ? 'Menerima Order' : 'Istirahat (Offline)' }}</span>
                </div>
            </div>

            <!-- Toggle Online/Offline (form submit biasa, tidak bergantung JS) -->
            <div class="flex items-center gap-3">
                <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-full tracking-wider transition border shadow-sm flex items-center gap-1.5 {{ $jastiper->work_status !== 'offline' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25' : 'bg-slate-800 text-slate-400 border-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $jastiper->work_status !== 'offline' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500' }}"></span>
                    <span>{{ $jastiper->work_status !== 'offline' ? 'ONLINE' : 'OFFLINE' }}</span>
                </span>

                <form action="{{ route('jastiper.work-status.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="{{ $jastiper->work_status !== 'offline' ? 'offline' : 'tersedia' }}">
                    <button
                        type="submit"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $jastiper->work_status !== 'offline' ? 'bg-emerald-600' : 'bg-slate-700' }}"
                        title="Ubah Status Kerja"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $jastiper->work_status !== 'offline' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </form>

                <!-- Logout -->
                <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-slate-300 p-2.5 rounded-full transition border border-slate-700" title="Keluar Sesi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-md mx-auto px-4 mt-6 space-y-6">

        <!-- Verification Status Banner if pending/rejected -->
        @if ($jastiper->verification_status !== 'approved')
            <div class="p-4 rounded-3xl border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] bg-amber-50">
                <div class="flex gap-3">
                    <span class="text-xl">⚠️</span>
                    <div class="space-y-1">
                        <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide">Akun Belum Aktif</h4>
                        <p class="text-[10px] text-slate-500 leading-normal">
                            @if ($jastiper->verification_status === 'menunggu')
                                Dokumen verifikasi Anda sedang ditinjau oleh admin. Mohon tunggu maksimal 1x24 jam.
                            @elseif ($jastiper->verification_status === 'rejected')
                                Pengajuan verifikasi Anda ditolak dengan alasan: "{{ $jastiper->latestVerification->rejection_reason }}". Silakan ajukan ulang.
                            @else
                                Anda belum mengunggah berkas KTP & Selfie. Selesaikan verifikasi sekarang untuk mulai menerima order.
                            @endif
                        </p>
                        <div class="pt-2">
                            <a href="{{ route('jastiper.verification') }}" class="inline-block bg-slate-900 hover:bg-slate-800 text-white font-bold text-[9px] px-3.5 py-1.5 rounded-sm uppercase tracking-wider shadow-sm">
                                @if($jastiper->verification_status === 'rejected') Ajukan Ulang @elseif($jastiper->verification_status === 'menunggu') Pantau Dokumen @else Mulai Verifikasi @endif
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Driver Earnings Card (Gopartner Style Performance Metrics) -->
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white rounded-3xl p-5 shadow-lg border border-emerald-500/20 relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>

            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-[9px] uppercase font-bold text-emerald-100 tracking-wider block">Pendapatan Jastiper Hari Ini</span>
                    <div class="text-3xl font-black font-display tracking-tight">Rp0</div>
                    <span class="text-[8px] text-emerald-200/80 font-semibold block">ID: #JSTP-{{ str_pad($jastiper->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>

                <button onclick="showMaintenanceToast(event)" class="bg-slate-950/40 hover:bg-slate-950/60 border border-white/20 text-white text-[9px] font-bold px-3.5 py-2 rounded-full transition uppercase tracking-wide">
                    Tarik Saldo
                </button>
            </div>

            <!-- Perf Metrics Grid -->
            <div class="grid grid-cols-3 gap-2 border-t border-white/10 pt-4 mt-5 text-center">
                <div>
                    <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Bulan Ini</span>
                    <span class="text-xs font-black block mt-0.5">Rp0</span>
                </div>
                <div>
                    <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Penyelesaian</span>
                    <span class="text-xs font-black block mt-0.5">100%</span>
                </div>
                <div>
                    <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-wide block">Rating</span>
                    <span class="text-xs font-black block mt-0.5 text-amber-300">★ 5.0</span>
                </div>
            </div>
        </div>

        @if ($jastiper->verification_status === 'approved')
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-3.5">
                <div class="border-b border-slate-100 pb-2.5 flex justify-between items-center">
                    <div>
                        <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Check-in Lokasi Belanja</h3>
                        <p class="text-[9px] text-slate-400 mt-0.5">Kabarkan posisi Anda agar Customer dapat membooking Anda secara instan.</p>
                    </div>
                    <span class="w-2.5 h-2.5 rounded-full {{ $jastiper->work_status !== 'offline' && $jastiper->checkin_location ? 'bg-emerald-500 animate-pulse' : 'bg-slate-350' }}"></span>
                </div>

                @if ($jastiper->work_status === 'offline')
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center">
                        <p class="text-[10px] text-slate-500 font-semibold leading-normal">
                            Silakan ubah status ke <b>"Tersedia"</b> atau <b>"Standby"</b> di bilah hitam atas untuk mengumumkan check-in lokasi belanja.
                        </p>
                    </div>
                @else
                    @if ($jastiper->checkin_location)
                    <div class="bg-rose-50/50 border border-rose-100/60 p-3 rounded-2xl flex items-center justify-between gap-4">
                        <div class="flex items-start gap-2.5">
                            <div class="p-1.5 bg-rose-100 text-rose-600 rounded-xl shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-[8px] text-rose-500 uppercase tracking-widest font-black block">Sedang Aktif di</span>
                                <p class="text-xs font-black text-slate-800 mt-0.5">{{ $jastiper->checkin_location }}</p>
                                <span class="text-[8px] text-slate-400 block mt-0.5">Sejak {{ $jastiper->checked_in_at ? $jastiper->checked_in_at->diffForHumans() : 'Baru saja' }}</span>
                            </div>
                        </div>
                        <form action="{{ route('jastiper.checkin') }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="bg-slate-900 hover:bg-slate-850 text-white text-[9px] font-black px-4 py-2.5 rounded-xl uppercase tracking-wider transition shrink-0 shadow-sm">
                                Check-out
                            </button>
                        </form>
                    </div>
                @else
                    <form action="{{ route('jastiper.checkin') }}" method="POST" class="space-y-3" x-data="{ location: 'Mie Gacoan Lowokwaru', customLocation: '' }">
                        @csrf
                        <input type="hidden" name="action" value="checkin">

                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label for="location_select" class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pilih Lokasi Populer</label>
                                <select id="location_select" name="location_name_select" x-model="location" class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-3.5 py-2.5 rounded-xl text-[11px] font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150">
                                    <option value="Mie Gacoan Lowokwaru">Mie Gacoan Lowokwaru</option>
                                    <option value="Starbucks Ijen">Starbucks Ijen</option>
                                    <option value="Matos (Malang Town Square)">Matos (Malang Town Square)</option>
                                    <option value="Pasar Besar Malang">Pasar Besar Malang</option>
                                    <option value="Bakso Bakar Pak Man">Bakso Bakar Pak Man</option>
                                    <option value="custom">-- Tulis Kustom --</option>
                                </select>
                            </div>

                            <div x-show="location === 'custom'">
                                <label for="location_custom" class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Tempat Kustom</label>
                                <input type="text" id="location_custom" name="location_name_custom" x-model="customLocation" placeholder="Misal: Indomaret Veteran..." class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-3.5 py-2.5 rounded-xl text-[11px] font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[10px] py-3.5 rounded-2xl transition uppercase tracking-wider shadow-sm flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span>Umumkan Check-in Saya</span>
                        </button>
                    </form>
                @endif
                @endif
            </div>
        @endif

        <!-- Live Area Map Card (Leaflet Map on Dashboard) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-3">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Area Kerja & Radius</h3>
                    <p class="text-[9px] text-slate-400 mt-0.5 leading-normal">
                        Wilayah: <b>{{ $jastiper->wilayah ? $jastiper->wilayah->name : 'Belum Ditentukan' }}</b> | Radius: <b>{{ number_format($jastiper->radius_km, 1) }} KM</b>
                    </p>
                </div>
                @if ($jastiper->verification_status === 'approved')
                    <a href="{{ route('jastiper.area') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[9px] px-3.5 py-2 rounded-full border border-slate-200 transition uppercase tracking-wider">
                        Atur Area
                    </a>
                @endif
            </div>

            <!-- Map Container -->
            <div id="leaflet-jastiper-dash-map" class="h-44 w-full border border-slate-200 rounded-2xl bg-slate-100 z-10"></div>
        </div>

        <!-- Order Aktif Anda (Active Orders) -->
        @if($activeOrders->isNotEmpty())
            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-4">
                <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Order Aktif Anda ({{ $activeOrders->count() }})</span>
                </h3>
                <div class="space-y-3">
                    @foreach($activeOrders as $active)
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3 text-left">
                            <div class="flex justify-between items-start border-b border-slate-200/60 pb-2.5 gap-2">
                                <div>
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider {{ $active->status === 'deal' ? 'bg-sky-55 text-sky-600 border border-sky-200' : 'bg-emerald-55 text-emerald-600 border border-emerald-200' }}">
                                        {{ $active->status === 'deal' ? 'Deal Terbentuk' : 'Sedang Diproses' }}
                                    </span>
                                    <h4 class="font-extrabold text-xs text-slate-800 mt-2 leading-tight">{{ $active->description }}</h4>
                                    <span class="text-[9px] text-slate-400 block mt-0.5">Asal: {{ $active->origin_address ?: '-' }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block">Tarif Deal</span>
                                    <span class="text-xs font-black text-rose-600">Rp {{ number_format($active->agreed_fare ?? $active->estimated_fare, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="space-y-1.5 text-[9px] text-slate-500">
                                <div><span class="font-bold">Antar:</span> {{ $active->destination_address }}</div>
                                <div><span class="font-bold">Customer:</span> {{ $active->customer->name }} (<a href="tel:{{ $active->customer->phone_number }}" class="text-rose-600 hover:underline font-bold">{{ $active->customer->phone_number }}</a>)</div>
                                <div><span class="font-bold">Penerima:</span> {{ $active->recipient_name }} ({{ $active->recipient_phone }})</div>
                            </div>
                            
                            <!-- Action button to update status -->
                            <div class="pt-1 flex gap-2">
                                @if($active->status === 'deal')
                                    <form action="{{ route('jastiper.orders.start-process', $active->id) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                            Mulai Belanja (Proses)
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('jastiper.orders.complete', $active->id) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                            Selesaikan Orderan
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <!-- Tombol Chat: room chat sudah otomatis terbentuk sejak status 'deal' -->
                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $active->id }}, orderLabel: @js($active->description) } }))"
                                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide flex items-center justify-center gap-1.5">
                                <span>💬</span>
                                <span>Chat dengan {{ $active->customer->name }}</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <hr class="border-slate-200 my-4">
        @endif

        <!-- Driver Request Feed (Gojek Driver Request Card Redesign) -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Permintaan Order Terdekat</h3>
                <span class="text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 px-2.5 py-0.5 rounded-full" x-show="online && loading">Memuat...</span>
                <span class="text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 px-2.5 py-0.5 rounded-full" x-show="online && !loading">Mencari...</span>
            </div>

            @if ($jastiper->work_status === 'offline')
                <!-- Case: Offline -->
                <div class="py-10 text-center flex flex-col items-center justify-center">
                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </div>
                    <h4 class="font-bold text-xs text-slate-700">Status Anda: Offline</h4>
                    <p class="text-[9px] text-slate-400 mt-1 max-w-[220px] leading-normal mx-auto">Ubah status ke "Tersedia" di banner atas untuk melihat permintaan belanjaan masuk.</p>
                </div>
            @else
                <!-- Case: Online (tersedia / standby) -->
                <div class="space-y-4">
                @if ($jastiper->verification_status !== 'approved')
                    <!-- Locked if not verified -->
                    <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-5 text-center flex flex-col items-center justify-center">
                        <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h4 class="font-bold text-xs text-slate-700">Orderan Dikunci</h4>
                        <p class="text-[9px] text-slate-400 mt-1 max-w-[200px] leading-normal mx-auto">Selesaikan verifikasi akun KTP Anda terlebih dahulu di bagian atas untuk membuka akses order.</p>
                    </div>
                @else
                    <!-- Direct Request Bookings List (server-rendered, khusus untuk jastiper ini) -->
                    @if($directOrders->isNotEmpty())
                        <div class="space-y-3">
                            <h4 class="font-display font-black text-[10px] text-rose-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-450 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-650"></span>
                                </span>
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span>Booking Langsung Khusus Untuk Anda</span>
                            </h4>
                            @foreach($directOrders as $direct)
                                <div class="bg-rose-50/50 border border-rose-200/70 rounded-2xl p-4 space-y-3 relative overflow-hidden">
                                    <div class="flex justify-between items-start border-b border-rose-100 pb-2.5">
                                        <div>
                                            <span class="text-[8px] font-black bg-rose-650 text-rose-600 border border-rose-200 px-2.5 py-0.5 rounded-full uppercase tracking-wider">Direct Request</span>
                                            <h4 class="font-extrabold text-xs text-slate-800 mt-2 leading-tight">{{ $direct->description }}</h4>
                                            <span class="text-[9px] text-slate-400 block mt-0.5">Asal: {{ $direct->origin_address ?: '-' }}</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block font-mono">Ongkir</span>
                                            <span class="text-xs font-black text-rose-600">Rp {{ number_format($direct->estimated_fare, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2 text-[9px] text-slate-500">
                                        <div class="flex items-start gap-1">
                                            <span class="leading-tight">Antar: <b>{{ $direct->destination_address }}</b></span>
                                        </div>
                                        <div>Customer: <b>{{ $direct->customer->name }}</b> ({{ $direct->customer->phone_number }})</div>
                                        <div>Kategori Berat: <span class="font-bold text-slate-700 capitalize">{{ $direct->weight_category }}</span></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 pt-1.5">
                                        <form action="{{ route('jastiper.orders.direct-accept', $direct->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                                Terima Booking
                                            </button>
                                        </form>
                                        <form action="{{ route('jastiper.orders.direct-reject', $direct->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-850 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide text-center block focus:outline-none">
                                                Tolak & Buka Ke Umum
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr class="border-slate-100 my-4">
                    @endif

                        {{-- Selama online: filter kategori + feed real-time (Alpine) --}}
                        @php
                            $feedCategories = [
                                '' => 'Semua',
                                'beli-antar' => 'Titip Kuliner',
                                'ambil-antar' => 'Titip Ambil',
                                'toko-kirim' => 'Titip Toko',
                                'dokumen' => 'Dokumen Kecil',
                                'multi-stop' => 'Multi-Stop',
                                'kirim-pihak-ketiga' => 'Titip Ekspedisi',
                            ];
                        @endphp

                        <!-- Filter Kategori -->
                        <div class="flex gap-1.5 overflow-x-auto pb-1 -mx-1 px-1">
                            @foreach ($feedCategories as $catValue => $catLabel)
                                <button type="button"
                                    @click="category = @json($catValue); fetchFeed()"
                                    :disabled="loading"
                                    class="shrink-0 px-3 py-1.5 rounded-full text-[9px] font-bold uppercase tracking-wide border transition disabled:opacity-50"
                                    :class="category === @json($catValue) ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-500 border-slate-200 hover:border-slate-300'">
                                    {{ $catLabel }}
                                </button>
                            @endforeach
                        </div>

                        <!-- Pesan info dari server (mis. lokasi belum diset, dsb) -->
                        <div x-show="feedMessage && orders.length === 0" x-cloak class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center">
                            <p class="text-[10px] text-slate-500 font-semibold leading-normal" x-text="feedMessage"></p>
                        </div>

                        <!-- Loading skeleton (hanya saat load pertama kali) -->
                        <div x-show="loading && !initialFeedLoaded" class="space-y-3">
                            <div class="h-24 bg-slate-100 rounded-2xl animate-pulse"></div>
                            <div class="h-24 bg-slate-100 rounded-2xl animate-pulse"></div>
                        </div>

                        <!-- Empty state setelah feed pertama berhasil dimuat -->
                        <div x-show="initialFeedLoaded && !feedMessage && orders.length === 0" x-cloak class="py-10 text-center flex flex-col items-center justify-center">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h4 class="font-bold text-xs text-slate-700">Tidak Ada Orderan Aktif</h4>
                            <p class="text-[9px] text-slate-400 mt-1 max-w-[220px] leading-normal mx-auto">Saat ini belum ada permintaan jastip baru dalam radius & kategori yang Anda pilih.</p>
                        </div>

                        <!-- Daftar Order (real-time, bisa multi-select) -->
                        <div class="space-y-3">
                            <template x-for="order in orders" :key="order.id">
                                <div class="bg-slate-50 border rounded-2xl p-4 space-y-3 transition animate-fade-in"
                                    :class="selected.includes(order.id) ? 'border-rose-400 ring-2 ring-rose-100' : 'border-slate-200 hover:border-slate-300'">

                                    <div class="flex justify-between items-start border-b border-slate-200/60 pb-2.5 gap-2">
                                        <label class="flex items-start gap-2 cursor-pointer min-w-0">
                                            <input type="checkbox" class="mt-1 w-3.5 h-3.5 accent-rose-600 shrink-0"
                                                :checked="selected.includes(order.id)"
                                                @change="toggleSelect(order.id)">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-[8px] font-black bg-rose-50 text-rose-600 border border-rose-100 px-2 py-0.5 rounded-full uppercase tracking-wider" x-text="order.category_label"></span>
                                                    <span class="text-[8px] font-bold text-slate-400" x-text="order.distance_km + ' KM'"></span>
                                                </div>
                                                <h4 class="font-extrabold text-xs text-slate-800 mt-1.5 leading-tight" x-text="order.description"></h4>
                                                <span class="text-[9px] text-slate-400 block mt-0.5" x-text="'Asal: ' + (order.origin_address || '-')"></span>
                                            </div>
                                        </label>
                                        <div class="text-right shrink-0">
                                            <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block">Estimasi Ongkir</span>
                                            <span class="text-xs font-black text-slate-500" x-text="order.estimated_fare_formatted"></span>
                                        </div>
                                    </div>

                                    <div class="space-y-2 text-[9px] text-slate-500">
                                        <div class="flex items-start gap-1">
                                            <svg class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <span class="leading-tight" x-text="'Antar: ' + order.destination_address"></span>
                                        </div>
                                        <div>Penerima: <span class="font-extrabold text-slate-800" x-text="order.recipient_name"></span> (<span x-text="order.recipient_phone"></span>)</div>
                                        <div>Kategori Berat: <span class="font-bold text-slate-700 capitalize" x-text="order.weight_category"></span></div>
                                        <div class="text-slate-400" x-text="order.created_at"></div>
                                    </div>

                                    <!-- Status tawaran yang sudah dikirim (kalau ada) -->
                                    <div x-show="order.my_offer_price" x-cloak
                                        class="bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2">
                                        <span class="text-[9px] font-bold text-emerald-700">
                                            Tawaran Anda: <span x-text="order.my_offer_price_formatted"></span> (Menunggu Keputusan Customer)
                                        </span>
                                    </div>

                                    <!-- Form kirim / ubah tawaran harga -->
                                    <div class="flex items-center gap-2 pt-1">
                                        <div class="relative flex-grow">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-400 pointer-events-none">Rp</span>
                                            <input type="number" min="5000" step="500"
                                                x-model.number="orderPrices[order.id]"
                                                class="w-full bg-white border border-slate-200 rounded-xl pl-8 pr-3 py-2.5 text-[10px] font-bold text-slate-800 focus:outline-none focus:border-rose-400 transition">
                                        </div>
                                        <button type="button" @click="submitOffer(order.id)" :disabled="loading"
                                            class="shrink-0 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-bold text-[9px] px-4 py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm">
                                            <span x-text="order.my_offer_price ? 'Ubah Tawaran' : 'Kirim Tawaran'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                @endif
            </div>
            @endif
        </div>

    </div>

    <!-- Sticky Multi-Offer Bar -->
    <div x-show="selected.length > 0" x-transition x-cloak
        class="fixed bottom-16 md:bottom-4 left-1/2 -translate-x-1/2 z-40 bg-slate-950 text-white rounded-full shadow-lg px-4 py-2.5 flex items-center gap-3 border border-slate-700">
        <span class="text-[10px] font-bold whitespace-nowrap" x-text="selected.length + ' Order Dipilih'"></span>
        <button type="button" @click="submitOffers(selected)" :disabled="loading"
            class="bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-[9px] font-black px-3.5 py-1.5 rounded-full uppercase tracking-wider whitespace-nowrap">
            Kirim Tawaran Sekaligus (Estimasi)
        </button>
        <button type="button" @click="selected = []" class="text-slate-400 hover:text-white text-[9px] whitespace-nowrap">
            Batal
        </button>
    </div>
</div>

@include('components.chat.order-chat-modal', [
    'viewerRole' => 'jastiper',
    'chatSendUrlTemplate' => route('jastiper.orders.chat.send', ['id' => '__ID__']),
    'chatHistoryUrlTemplate' => route('jastiper.orders.chat.history', ['id' => '__ID__']),
])

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let map;
        let marker;
        let circle;

        let currentLat = parseFloat(@json($jastiper->current_lat ?? -7.9839));
        let currentLng = parseFloat(@json($jastiper->current_lng ?? 112.6214));
        let currentRadius = parseFloat(@json($jastiper->radius_km ?? 5.00));

        function initDashMap() {
            if (typeof L === 'undefined') {
                return;
            }

            let container = L.DomUtil.get('leaflet-jastiper-dash-map');
            if (container !== null && container._leaflet_id !== undefined && container._leaflet_id !== null) {
                return;
            }

            map = L.map('leaflet-jastiper-dash-map', {
                zoomControl: false,
                attributionControl: false
            }).setView([currentLat, currentLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18
            }).addTo(map);

            const jastiperIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });

            marker = L.marker([currentLat, currentLng], {
                icon: jastiperIcon
            }).addTo(map);

            circle = L.circle([currentLat, currentLng], {
                color: '#e11d48',
                fillColor: '#f43f5e',
                fillOpacity: 0.1,
                radius: currentRadius * 1000
            }).addTo(map);

            setTimeout(() => {
                if (map) {
                    map.invalidateSize();
                }
            }, 300);
        }

        // Jalankan init map dengan safety check
        let initAttempts = 0;
        function tryInitDashMap() {
            if (typeof L !== 'undefined') {
                initDashMap();
            } else if (initAttempts < 50) {
                initAttempts++;
                setTimeout(tryInitDashMap, 100);
            }
        }

        tryInitDashMap();
    });
</script>
@endsection