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
        if (typeof window.jastiperNotify === 'function') {
            window.jastiperNotify(message, success);
            return;
        }
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
                    
                    this.selected = this.selected.filter(id => this.orders.some(o => o.id === id));
                    
                    this.orders.forEach(o => {
                        if (this.orderPrices[o.id] === undefined) {
                            this.orderPrices[o.id] = o.my_offer_price ?? o.estimated_fare;
                        }
                    });

                    const recentOffers = data.recent_offers || [];
                    let notifiedIds = JSON.parse(localStorage.getItem('jastiper_notified_offers') || '[]');
                    let shouldReload = false;
                    
                    recentOffers.forEach(off => {
                        if (!notifiedIds.includes(off.id)) {
                            if (off.status === 'accepted') {
                                jastiperNotify(`Selamat! Tawaran Anda untuk "${off.order_description}" telah DISETUJUI oleh Customer! 🎉`, true);
                                shouldReload = true;
                            } else if (off.status === 'rejected') {
                                jastiperNotify(`Tawaran Anda untuk "${off.order_description}" belum terpilih / ditolak.`, false);
                            }
                            notifiedIds.push(off.id);
                        }
                    });
                    
                    if (notifiedIds.length > 50) {
                        notifiedIds = notifiedIds.slice(notifiedIds.length - 50);
                    }
                    localStorage.setItem('jastiper_notified_offers', JSON.stringify(notifiedIds));

                    if (shouldReload) {
                        setTimeout(() => window.location.reload(), 2500);
                    }
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

            respondModalOpen: false,
            respondAddonDesc: '',
            respondUrl: '',

            openRespondModal(url, description) {
                this.respondUrl = url;
                this.respondAddonDesc = description;
                this.respondModalOpen = true;
            },
            closeRespondModal() {
                this.respondModalOpen = false;
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
    <div class="bg-slate-950 text-white border-b border-slate-800 sticky top-16 sm:static z-40 sm:z-auto px-4 py-3.5 shadow-sm sm:shadow-none">
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
        <div class="bg-telkomsel-pattern-card text-white rounded-3xl p-5 shadow-lg border border-rose-500/20 relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <span class="text-[9px] uppercase font-bold text-rose-100 tracking-wider block">Pendapatan Jastiper Hari Ini</span>
                    <div class="text-3xl font-black font-display tracking-tight">Rp {{ number_format($todayEarnings, 0, ',', '.') }}</div>
                    <span class="text-[8px] text-rose-200/80 font-semibold block">ID: #JSTP-{{ str_pad($jastiper->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>

                <a href="{{ route('jastiper.earnings') }}" class="bg-slate-950/30 hover:bg-slate-950/50 border border-white/20 text-white text-[9px] font-bold px-3.5 py-2 rounded-full transition uppercase tracking-wide inline-block text-center">
                    Tarik Saldo
                </a>
            </div>

            <!-- Perf Metrics Grid -->
            <div class="grid grid-cols-3 gap-2 border-t border-white/10 pt-4 mt-5 text-center">
                <div>
                    <span class="text-[8px] font-bold text-rose-100 uppercase tracking-wide block">Bulan Ini</span>
                    <span class="text-xs font-black block mt-0.5">Rp {{ number_format($monthEarnings, 0, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-[8px] font-bold text-rose-100 uppercase tracking-wide block">Penyelesaian</span>
                    <span class="text-xs font-black block mt-0.5">{{ $completionRate }}%</span>
                </div>
                <div>
                    <span class="text-[8px] font-bold text-rose-100 uppercase tracking-wide block">Rating</span>
                    <div class="mt-0.5">
                        <x-badge-display :level="$jastiper->badge->badge_level ?? 'bronze'" :rating="$ratingAvg" />
                    </div>
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
                    <form action="{{ route('jastiper.checkin') }}" method="POST" id="form-jastiper-checkin" class="space-y-3" x-data="{ location: 'Mie Gacoan Lowokwaru', customLocation: '' }">
                        @csrf
                        <input type="hidden" name="action" value="checkin">
                        <input type="hidden" name="lat" id="checkin_lat">
                        <input type="hidden" name="lng" id="checkin_lng">

                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label for="location_select" class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pilih Lokasi Populer</label>
                                <select id="location_select" name="location_name_select" x-model="location" class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-3.5 py-2.5 rounded-xl text-[11px] font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150">
                                    <option value="Mie Gacoan Lowokwaru">Mie Gacoan Lowokwaru (Malang)</option>
                                    <option value="Starbucks Ijen">Starbucks Ijen (Malang)</option>
                                    <option value="Matos (Malang Town Square)">Matos (Malang)</option>
                                    <option value="Lippo Plaza Sidoarjo">Lippo Plaza (Sidoarjo)</option>
                                    <option value="Alun-Alun Sidoarjo">Alun-Alun (Sidoarjo)</option>
                                    <option value="Tunjungan Plaza Surabaya">Tunjungan Plaza (Surabaya)</option>
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
                        @if($jastiper->checkin_location)
                            <span class="inline-flex items-center gap-1 text-emerald-600 font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Sedang Aktif di: <b>{{ $jastiper->checkin_location }}</b>
                            </span>
                            <span class="text-slate-300 mx-1">•</span>
                        @endif
                        Wilayah: <b id="dash-wilayah-text">{{ $jastiper->wilayah ? $jastiper->wilayah->name : 'Belum Ditentukan' }}</b> | Radius: <b>{{ number_format($jastiper->radius_km, 1) }} KM</b>
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

        <!-- Sengketa / Komplain Sedang Diinvestigasi -->
        @if(isset($disputedOrders) && $disputedOrders->isNotEmpty())
            <div class="bg-amber-50 border-2 border-amber-300 p-4 sm:p-5 rounded-3xl shadow-sm space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-amber-900">
                        <span class="text-xl">⚠️</span>
                        <div>
                            <h3 class="font-display font-black text-xs uppercase tracking-wider">
                                Pesanan Dalam Investigasi Komplain ({{ $disputedOrders->count() }})
                            </h3>
                            <p class="text-[10px] text-amber-800 font-medium leading-tight">Customer mengajukan komplain resmi. Pencairan dana ditahan sementara oleh Tim CS.</p>
                        </div>
                    </div>
                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-600 text-white shrink-0">
                        Investigasi CS
                    </span>
                </div>

                <div class="space-y-2.5">
                    @foreach($disputedOrders as $disp)
                        @php
                            $report = $disp->reports->first();
                        @endphp
                        <div class="bg-white border border-amber-200 rounded-2xl p-3.5 space-y-2 text-left shadow-2xs">
                            <div class="flex items-start justify-between gap-2 border-b border-amber-100 pb-2">
                                <div>
                                    <span class="text-[8px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300">
                                        Pesanan #{{ $disp->id }}
                                    </span>
                                    <h4 class="font-extrabold text-xs text-slate-900 mt-1.5 leading-snug">{{ $disp->description }}</h4>
                                    <span class="text-[10px] text-slate-500 font-medium block mt-0.5">Pelanggan: <strong>{{ $disp->customer->name ?? 'Customer' }}</strong> ({{ $disp->customer->phone_number ?? '-' }})</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[8px] uppercase font-bold text-slate-400 block">Tarif Deal</span>
                                    <span class="text-xs font-black text-amber-700">Rp {{ number_format($disp->agreed_fare ?? $disp->estimated_fare, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            @if($report)
                                <div class="bg-amber-50/70 p-2.5 rounded-xl text-[11px] text-amber-950 space-y-1">
                                    <div class="flex items-center gap-1 font-bold text-[10px] text-amber-800">
                                        <span>Keluhan Customer:</span>
                                        <span class="capitalize">{{ str_replace('_', ' ', $report->category) }}</span>
                                    </div>
                                    <p class="italic text-[10px] text-amber-900">"{{ $report->description }}"</p>
                                </div>
                            @endif

                            <div class="flex items-center justify-between pt-1">
                                <span class="text-[9px] text-slate-400 font-medium">Status: Menunggu Keputusan CS</span>
                                <button type="button" 
                                        @click="openOrderChat({{ $disp->id }}, '{{ addslashes($disp->description) }}', 'customer')"
                                        class="bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-[10px] px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                    <span>💬 Chat / Klarifikasi</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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
                                    @php
                                        $statusLabels = [
                                            'deal' => 'Deal Terbentuk',
                                            'diproses' => 'Sedang Diproses',
                                            'barang_diambil' => 'Barang Diambil',
                                            'sedang_diantar' => 'Sedang Diantar',
                                            'tiba_tujuan' => 'Tiba di Tujuan',
                                        ];
                                        $label = $statusLabels[$active->status] ?? $active->status;
                                        $colorClass = $active->status === 'deal' ? 'bg-sky-55 text-sky-600 border border-sky-200' : 'bg-emerald-55 text-emerald-600 border border-emerald-200';
                                    @endphp
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider {{ $colorClass }}">
                                        {{ $label }}
                                    </span>
                                    <h4 class="font-extrabold text-xs text-slate-800 mt-2 leading-tight">{{ $active->description }}</h4>
                                    <span class="text-[9px] text-slate-400 block mt-0.5">Asal: {{ $active->origin_address ?: '-' }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider block">Tarif Deal</span>
                                    <span class="text-xs font-black text-rose-600">Rp {{ number_format($active->agreed_fare ?? $active->estimated_fare, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Foto Referensi Barang dari Customer (Pesanan Aktif) -->
                            @if($active->reference_photo_url)
                                <div class="bg-amber-50/80 border border-amber-200/90 rounded-2xl p-2.5 flex items-center justify-between gap-3 shadow-2xs">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-12 h-12 rounded-xl bg-white border border-amber-300 overflow-hidden shrink-0 shadow-xs">
                                            <img src="{{ $active->reference_photo_url }}" 
                                                 alt="Foto Referensi" 
                                                 class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition"
                                                 @click="$dispatch('open-image-lightbox', { url: '{{ $active->reference_photo_url }}', title: 'Foto Referensi Barang: {{ addslashes($active->merchant->name ?? 'Titipan Customer') }}' })">
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1">
                                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-900">📸 Foto Referensi Barang</span>
                                                <span class="text-[8px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.2 rounded">Dari Customer</span>
                                            </div>
                                            <p class="text-[8.5px] text-amber-800/80 truncate mt-0.5">Contoh wujud barang yang diminta customer</p>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            @click="$dispatch('open-image-lightbox', { url: '{{ $active->reference_photo_url }}', title: 'Foto Referensi Barang: {{ addslashes($active->merchant->name ?? 'Titipan Customer') }}' })" 
                                            class="shrink-0 px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-extrabold text-[9px] rounded-xl shadow-2xs transition cursor-pointer flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Lihat Foto</span>
                                    </button>
                                </div>
                            @endif

                            @if($active->items->isNotEmpty())
                                <div class="bg-white border border-rose-100 rounded-2xl p-3 shadow-2xs space-y-2">
                                    <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-1.5">
                                        <span class="flex items-center gap-1.5 text-rose-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                            <span>Rincian Belanjaan ({{ $active->items->count() }} Item)</span>
                                        </span>
                                        @if($active->merchant)
                                            <span class="text-slate-500 font-bold">🏬 {{ $active->merchant->name }}</span>
                                        @endif
                                    </div>
                                    <div class="space-y-1.5">
                                        @foreach($active->items as $item)
                                            <div class="flex items-start justify-between text-[10px] gap-2 bg-slate-50/70 border border-slate-100 rounded-xl px-2.5 py-1.5">
                                                <div class="flex items-start gap-2 min-w-0">
                                                    @if($item->image_url)
                                                        <img src="{{ $item->image_url }}" 
                                                             alt="{{ $item->clean_name }}" 
                                                             class="w-8 h-8 rounded-lg object-cover border border-slate-200 shrink-0 cursor-pointer hover:opacity-80"
                                                             @click="$dispatch('open-image-lightbox', { url: '{{ $item->image_url }}', title: '{{ addslashes($item->clean_name) }}' })">
                                                    @else
                                                        <span class="w-5 h-5 rounded-lg bg-rose-100 text-rose-600 font-black text-[9px] flex items-center justify-center shrink-0 mt-0.5">{{ $item->quantity }}x</span>
                                                    @endif
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1">
                                                            @if($item->image_url)
                                                                <span class="text-[8.5px] font-black text-rose-600">{{ $item->quantity }}x</span>
                                                            @endif
                                                            <span class="font-black text-slate-800 block leading-snug">{{ $item->clean_name }}</span>
                                                        </div>
                                                        @if($item->notes)
                                                            <span class="text-[8.5px] text-amber-700 font-medium block leading-tight mt-0.5">📝 Catatan: {{ $item->notes }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($item->actual_price || $item->estimated_price)
                                                    <span class="text-slate-600 font-bold shrink-0 text-[9px] mt-0.5">Rp {{ number_format($item->actual_price ?? $item->estimated_price, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-1.5 text-[9px] text-slate-500">
                                <div><span class="font-bold">Antar:</span> {{ $active->destination_address }}</div>
                                <div><span class="font-bold">Customer:</span> {{ $active->customer->name }} (<a href="tel:{{ $active->customer->phone_number }}" class="text-rose-600 hover:underline font-bold">{{ $active->customer->phone_number }}</a>)</div>
                                <div><span class="font-bold">Penerima:</span> {{ $active->recipient_name }} ({{ $active->recipient_phone }})</div>
                            </div>

                            <!-- Multi-Stop Titik Singgah Belanja (Step-by-step jastiper) -->
                            @if($active->multi_stops && count($active->multi_stops) > 1)
                                <div class="bg-amber-50/70 border border-amber-200/90 rounded-2xl p-3 space-y-2.5">
                                    <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-wider text-amber-900 border-b border-amber-200/60 pb-1.5">
                                        <span class="flex items-center gap-1.5">
                                            <span>📍</span>
                                            <span>Titik Belanja Multi-Toko ({{ count($active->multi_stops) }} Titik)</span>
                                        </span>
                                        @php
                                            $completedStops = collect($active->multi_stops)->where('status', 'completed')->count();
                                        @endphp
                                        <span class="text-amber-800 font-extrabold">{{ $completedStops }} / {{ count($active->multi_stops) }} Selesai</span>
                                    </div>

                                    <div class="space-y-2">
                                        @php
                                            $firstPendingFound = false;
                                        @endphp
                                        @foreach($active->multi_stops as $sIdx => $st)
                                            @php
                                                $isCompleted = ($st['status'] ?? '') === 'completed';
                                                $stopNum = $st['stop_number'] ?? ($sIdx + 1);
                                                $isActiveNow = false;
                                                if (!$isCompleted && !$firstPendingFound) {
                                                    $isActiveNow = true;
                                                    $firstPendingFound = true;
                                                }
                                            @endphp
                                            <div class="p-2.5 rounded-xl border transition {{ $isCompleted ? 'bg-emerald-50/70 border-emerald-200 text-emerald-950' : ($isActiveNow ? 'bg-white border-rose-300 ring-2 ring-rose-400/30 text-slate-800' : 'bg-slate-100/60 border-slate-200 text-slate-400') }}">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="flex items-start gap-2 min-w-0">
                                                        <span class="w-5 h-5 rounded-full flex items-center justify-center font-black text-[9px] shrink-0 mt-0.5 {{ $isCompleted ? 'bg-emerald-600 text-white' : ($isActiveNow ? 'bg-rose-600 text-white animate-pulse' : 'bg-slate-300 text-slate-600') }}">
                                                            {{ $isCompleted ? '✓' : $stopNum }}
                                                        </span>
                                                        <div class="min-w-0">
                                                            <p class="font-extrabold text-[10px] truncate leading-tight">{{ $st['name'] ?? 'Toko Singgah' }}</p>
                                                            <p class="text-[9px] text-slate-500 truncate mt-0.5">{{ $st['address'] ?? '-' }}</p>
                                                            @if(!empty($st['notes']))
                                                                <p class="text-[8.5px] text-amber-800 italic mt-0.5">Catatan: {{ $st['notes'] }}</p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="shrink-0 text-right">
                                                        @if($isCompleted)
                                                            <span class="inline-block text-[8.5px] font-black text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md uppercase">
                                                                Selesai
                                                            </span>
                                                            @if(!empty($st['photo']))
                                                                <button type="button" @click="$dispatch('open-image-lightbox', { url: '{{ Storage::url($st['photo']) }}', title: 'Bukti Stop #{{ $stopNum }}: {{ addslashes($st['name'] ?? 'Toko') }}' })" class="block text-indigo-600 font-bold hover:underline text-[8.5px] mt-1">
                                                                    Lihat Foto
                                                                </button>
                                                            @endif
                                                        @elseif($isActiveNow)
                                                            <span class="inline-block text-[8.5px] font-black text-rose-700 bg-rose-100 px-2 py-0.5 rounded-md uppercase animate-pulse">
                                                                Aktif Belanja
                                                            </span>
                                                        @else
                                                            <span class="inline-block text-[8.5px] font-bold text-slate-400 bg-slate-200/80 px-1.5 py-0.5 rounded-md uppercase">
                                                                Antre
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($isActiveNow && in_array($active->status, ['diproses', 'barang_diambil']))
                                                    <div class="mt-2 pt-2 border-t border-rose-100">
                                                        <button type="button" 
                                                            @click="$dispatch('open-proof-modal', { 
                                                                orderId: {{ $active->id }}, 
                                                                status: 'stop_completed', 
                                                                title: 'Foto Bukti Stop #{{ $stopNum }}: {{ addslashes($st['name'] ?? 'Toko') }}', 
                                                                subtitle: 'Ambil/unggah foto barang belanjaan atau nota fisik di toko ini sebelum lanjut ke titik berikutnya.',
                                                                buttonText: 'Konfirmasi Stop Selesai & Lanjut',
                                                                actionUrl: '{{ route('jastiper.orders.stop.complete', [$active->id, $stopNum]) }}'
                                                            })"
                                                            class="w-full bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-black text-[9px] py-2 rounded-xl transition uppercase tracking-wide flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                            <span>Upload Foto & Selesaikan Stop #{{ $stopNum }}</span>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Bukti Foto Lapangan Jika Sudah Ada -->
                            @if($active->pickup_photo || $active->delivery_photo)
                                <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-2.5 space-y-2 text-[9px]">
                                    @if($active->pickup_photo)
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <img src="{{ Storage::url($active->pickup_photo) }}" alt="Foto Ambil" class="w-7 h-7 rounded-lg object-cover border border-slate-300 shrink-0">
                                                <div class="min-w-0">
                                                    <span class="font-bold text-slate-800 block truncate">Bukti Ambil di Toko</span>
                                                    <span class="text-slate-400 font-mono text-[8.5px] block">{{ $active->pickup_time ? $active->pickup_time->format('H:i') . ' WIB' : 'Tercatat' }}</span>
                                                </div>
                                            </div>
                                            <button type="button" @click="$dispatch('open-image-lightbox', { url: '{{ Storage::url($active->pickup_photo) }}', title: 'Bukti Pengambilan Barang di Toko' })" class="text-indigo-600 font-bold hover:underline shrink-0 text-[9px]">
                                                Lihat
                                            </button>
                                        </div>
                                    @endif
                                    @if($active->delivery_photo)
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <img src="{{ Storage::url($active->delivery_photo) }}" alt="Foto Tiba" class="w-7 h-7 rounded-lg object-cover border border-slate-300 shrink-0">
                                                <div class="min-w-0">
                                                    <span class="font-bold text-slate-800 block truncate">Bukti Serah Terima</span>
                                                    <span class="text-slate-400 font-mono text-[8.5px] block">{{ $active->delivery_time ? $active->delivery_time->format('H:i') . ' WIB' : 'Tercatat' }}</span>
                                                </div>
                                            </div>
                                            <button type="button" @click="$dispatch('open-image-lightbox', { url: '{{ Storage::url($active->delivery_photo) }}', title: 'Bukti Serah Terima di Tujuan' })" class="text-emerald-600 font-bold hover:underline shrink-0 text-[9px]">
                                                Lihat
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Action button to update status -->
                            <div class="pt-1 flex gap-2">
                                @if($active->status === 'deal')
                                    <form action="{{ route('jastiper.orders.start-process', $active->id) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                            Mulai Belanja (Proses)
                                        </button>
                                    </form>
                                @elseif($active->status === 'diproses')
                                    @if($active->multi_stops && count($active->multi_stops) > 1)
                                        @php
                                            $allStopsDone = collect($active->multi_stops)->every(fn($s) => ($s['status'] ?? '') === 'completed');
                                        @endphp
                                        @if($allStopsDone)
                                            <form action="{{ route('jastiper.orders.update-status', $active->id) }}" method="POST" class="w-full">
                                                @csrf
                                                <input type="hidden" name="status" value="sedang_diantar">
                                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                                    Semua Stop Selesai! Mulai Mengantar ke Customer
                                                </button>
                                            </form>
                                        @else
                                            <div class="w-full text-center text-[9px] font-bold text-amber-800 bg-amber-100/80 p-2 rounded-xl border border-amber-200">
                                                Selesaikan belanja per stop di atas & upload foto bukti untuk tiap titik.
                                            </div>
                                        @endif
                                    @else
                                        <button type="button" 
                                            @click="$dispatch('open-proof-modal', { 
                                                orderId: {{ $active->id }}, 
                                                status: 'barang_diambil', 
                                                title: 'Foto Bukti Pengambilan Barang', 
                                                subtitle: 'Ambil foto barang belanjaan atau struk fisik toko sebelum memulai pengantaran.',
                                                buttonText: 'Konfirmasi Barang Diambil',
                                                actionUrl: '{{ route('jastiper.orders.update-status', $active->id) }}'
                                            })"
                                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span>Konfirmasi Barang Diambil</span>
                                        </button>
                                    @endif
                                @elseif($active->status === 'barang_diambil')
                                    <form action="{{ route('jastiper.orders.update-status', $active->id) }}" method="POST" class="w-full">
                                        @csrf
                                        <input type="hidden" name="status" value="sedang_diantar">
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm text-center block focus:outline-none">
                                            Mulai Mengantar
                                        </button>
                                    </form>
                                @elseif($active->status === 'sedang_diantar')
                                    <button type="button" 
                                        @click="$dispatch('open-proof-modal', { 
                                            orderId: {{ $active->id }}, 
                                            status: 'tiba_tujuan', 
                                            title: 'Foto Bukti Serah Terima Paket', 
                                            subtitle: 'Ambil foto paket di lokasi tujuan atau serah terima dengan penerima.',
                                            buttonText: 'Konfirmasi Tiba di Tujuan',
                                            actionUrl: '{{ route('jastiper.orders.update-status', $active->id) }}'
                                        })"
                                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide shadow-sm flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Konfirmasi Tiba di Tujuan</span>
                                    </button>
                                @elseif($active->status === 'tiba_tujuan')
                                    <div class="w-full bg-gray-200 text-gray-500 font-bold text-[9px] py-2.5 rounded-xl uppercase tracking-wide shadow-sm text-center block">
                                        Menunggu Konfirmasi Customer
                                    </div>
                                @endif
                            </div>

                            <!-- Tombol Chat: room chat sudah otomatis terbentuk sejak status 'deal' -->
                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $active->id }}, orderLabel: @js($active->description) } }))"
                                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-[9px] py-2.5 rounded-xl transition uppercase tracking-wide flex items-center justify-center gap-1.5">
                                <span>💬</span>
                                <span>Chat dengan {{ $active->customer->name }}</span>
                            </button>

                            <!-- Addon Jastiper -->
                            @if($active->addons->isNotEmpty())
                                <div class="mt-3 bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
                                    <h4 class="text-[9px] font-black text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Permintaan Tambahan Item ({{ $active->addons->count() }})
                                    </h4>
                                    <div class="space-y-2">
                                        @foreach($active->addons as $addon)
                                            <div class="bg-slate-50 border border-slate-100 p-2.5 rounded-lg text-left">
                                                <p class="text-[10px] font-bold text-slate-800 line-clamp-2 mb-2">{{ $addon->description }}</p>
                                                
                                                @if($addon->payment_status === 'pending_jastiper')
                                                    <div>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <button @click="$dispatch('open-respond-modal', { url: '{{ route('jastiper.addons.respond', $addon->id) }}', description: '{{ addslashes($addon->description) }}' })" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] font-bold py-1.5 rounded-md uppercase tracking-wider transition">Setujui & Isi Harga</button>
                                                            <form action="{{ route('jastiper.addons.respond', $addon->id) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="action" value="reject">
                                                                <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 text-slate-600 text-[9px] font-bold py-1.5 rounded-md uppercase tracking-wider transition">Tolak</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @elseif($addon->payment_status === 'rejected')
                                                    <span class="text-[8px] bg-slate-200 text-slate-500 px-2 py-0.5 rounded-full font-bold line-through block w-fit">Ditolak</span>
                                                @else
                                                    <div class="flex items-end justify-between">
                                                        <div class="flex flex-col items-start gap-1">
                                                            @if($addon->payment_status === 'pending_payment')
                                                                <span class="text-[8px] bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold">Menunggu Pembayaran</span>
                                                            @elseif($addon->payment_status === 'paid_transfer')
                                                                <span class="text-[8px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Lunas (TF)</span>
                                                            @elseif($addon->payment_status === 'paid_cod')
                                                                <span class="text-[8px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">Tagih COD</span>
                                                            @endif
                                                        </div>
                                                        <span class="text-[10px] font-black text-rose-600">+ Rp {{ number_format($addon->additional_fare, 0, ',', '.') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <hr class="border-slate-200 my-4">
        @endif

        <!-- Riwayat Pesanan Jastiper Terdahulu (History Link) -->
        @if(isset($pastOrders) && $pastOrders->isNotEmpty())
            <a href="{{ route('jastiper.history') }}" class="block bg-white border border-slate-200/80 p-4 rounded-3xl shadow-xs hover:border-slate-300 transition group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shadow-inner">
                            📋
                        </div>
                        <div>
                            <h3 class="font-display font-extrabold text-xs text-slate-800 uppercase tracking-wider group-hover:text-rose-600 transition">
                                Riwayat Pesanan Anda ({{ $pastOrders->count() }})
                            </h3>
                            <p class="text-[10px] text-slate-400 font-medium">Lihat seluruh riwayat pesanan selesai & komplain</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-[11px] font-extrabold text-rose-600">
                        <span>Buka</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
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

                                    @if($direct->items->isNotEmpty())
                                        <div class="bg-white border border-rose-200/80 rounded-2xl p-3 shadow-2xs space-y-2">
                                            <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-1.5">
                                                <span class="flex items-center gap-1.5 text-rose-600">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                                    <span>Rincian Belanjaan ({{ $direct->items->count() }} Item)</span>
                                                </span>
                                                @if($direct->merchant)
                                                    <span class="text-slate-500 font-bold">🏬 {{ $direct->merchant->name }}</span>
                                                @endif
                                            </div>
                                            <div class="space-y-1.5">
                                                @foreach($direct->items as $item)
                                                    <div class="flex items-start justify-between text-[10px] gap-2 bg-slate-50/70 border border-slate-100 rounded-xl px-2.5 py-1.5">
                                                        <div class="flex items-start gap-2 min-w-0">
                                                            <span class="w-5 h-5 rounded-lg bg-rose-100 text-rose-600 font-black text-[9px] flex items-center justify-center shrink-0 mt-0.5">{{ $item->quantity }}x</span>
                                                            <div class="min-w-0">
                                                                <span class="font-black text-slate-800 block leading-snug">{{ $item->clean_name }}</span>
                                                                @if($item->notes)
                                                                    <span class="text-[8.5px] text-amber-700 font-medium block leading-tight mt-0.5">📝 Catatan: {{ $item->notes }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if($item->actual_price || $item->estimated_price)
                                                            <span class="text-slate-600 font-bold shrink-0 text-[9px] mt-0.5">Rp {{ number_format($item->actual_price ?? $item->estimated_price, 0, ',', '.') }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

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
                                                    <template x-if="order.merchant_name">
                                                        <span class="text-[8px] font-black bg-slate-900 text-white px-2 py-0.5 rounded-full uppercase tracking-wider" x-text="'🏬 ' + order.merchant_name"></span>
                                                    </template>
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

                                    <!-- Foto Referensi Barang dari Customer (Feed Order Tersedia) -->
                                    <template x-if="order.reference_photo_url">
                                        <div class="bg-amber-50/80 border border-amber-200/90 rounded-2xl p-2.5 flex items-center justify-between gap-3 shadow-2xs">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="w-12 h-12 rounded-xl bg-white border border-amber-300 overflow-hidden shrink-0 shadow-xs">
                                                    <img :src="order.reference_photo_url" 
                                                         alt="Foto Referensi" 
                                                         class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition"
                                                         @click="$dispatch('open-image-lightbox', { url: order.reference_photo_url, title: 'Foto Referensi Barang: ' + (order.merchant_name || 'Titipan Customer') })">
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-[9px] font-black uppercase tracking-wider text-amber-900">📸 Foto Referensi Barang</span>
                                                        <span class="text-[8px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.2 rounded">Dari Customer</span>
                                                    </div>
                                                    <p class="text-[8.5px] text-amber-800/80 truncate mt-0.5">Contoh wujud barang yang diminta customer</p>
                                                </div>
                                            </div>
                                            <button type="button" 
                                                    @click="$dispatch('open-image-lightbox', { url: order.reference_photo_url, title: 'Foto Referensi Barang: ' + (order.merchant_name || 'Titipan Customer') })" 
                                                    class="shrink-0 px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-extrabold text-[9px] rounded-xl shadow-2xs transition cursor-pointer flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Lihat Foto</span>
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Box Daftar Belanjaan / Makanan (Rincian Item Jelas untuk Jastiper) -->
                                    <template x-if="order.items && order.items.length > 0">
                                        <div class="bg-white border border-rose-200/80 rounded-2xl p-3 shadow-2xs space-y-2">
                                            <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-1.5">
                                                <span class="flex items-center gap-1.5 text-rose-600">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                                    <span>Daftar Pesanan Belanja (<span x-text="order.items.length"></span> Item)</span>
                                                </span>
                                                <span class="text-slate-400 font-bold" x-text="order.category === 'beli-antar' ? '🍽️ Menu Makanan' : '🛍️ Barang Titipan'"></span>
                                            </div>
                                            <div class="space-y-1.5">
                                                <template x-for="(item, idx) in order.items" :key="item.id || idx">
                                                    <div class="flex items-start justify-between text-[10px] gap-2 bg-slate-50/70 border border-slate-100 rounded-xl px-2.5 py-1.5">
                                                        <div class="flex items-start gap-2 min-w-0">
                                                            <template x-if="item.image_url">
                                                                <img :src="item.image_url" 
                                                                     alt="Foto Item" 
                                                                     class="w-8 h-8 rounded-lg object-cover border border-slate-200 shrink-0 cursor-pointer hover:opacity-80"
                                                                     @click="$dispatch('open-image-lightbox', { url: item.image_url, title: item.name_clean })">
                                                            </template>
                                                            <span x-show="!item.image_url" class="w-5 h-5 rounded-lg bg-rose-100 text-rose-600 font-black text-[9px] flex items-center justify-center shrink-0 mt-0.5" x-text="item.quantity + 'x'"></span>
                                                            <div class="min-w-0">
                                                                <div class="flex items-center gap-1">
                                                                    <span x-show="item.image_url" class="text-[8.5px] font-black text-rose-600" x-text="item.quantity + 'x'"></span>
                                                                    <span class="font-black text-slate-800 block leading-snug" x-text="item.name_clean"></span>
                                                                </div>
                                                                <span x-show="item.notes" class="text-[8.5px] text-amber-700 font-medium block leading-tight mt-0.5" x-text="'📝 Catatan: ' + item.notes"></span>
                                                            </div>
                                                        </div>
                                                        <span class="text-slate-600 font-bold shrink-0 text-[9px] mt-0.5" x-show="item.price > 0" x-text="item.price_formatted"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="space-y-2 text-[9px] text-slate-500">
                                        <!-- Khusus Multi-Stop: Tampilkan rute titik-titik belanja -->
                                        <template x-if="order.multi_stops && order.multi_stops.length > 0">
                                            <div class="bg-purple-50/70 border border-purple-100 rounded-xl p-2.5 space-y-1.5 my-1">
                                                <div class="flex items-center gap-1 font-bold text-purple-900 text-[10px]">
                                                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                    <span x-text="'Rute Multi-Stop (' + order.multi_stops.length + ' Titik Toko):'"></span>
                                                </div>
                                                <div class="space-y-1 pl-1">
                                                    <template x-for="(stop, sIdx) in order.multi_stops" :key="sIdx">
                                                        <div class="flex items-start gap-1.5 text-slate-700">
                                                            <span class="w-4 h-4 rounded-full bg-purple-600 text-white font-black text-[8px] flex items-center justify-center shrink-0 mt-0.5" x-text="sIdx + 1"></span>
                                                            <div class="min-w-0">
                                                                <span class="font-bold text-purple-950" x-text="stop.address || 'Titik ' + (sIdx + 1)"></span>
                                                                <span x-show="stop.notes" class="text-slate-500 block italic" x-text="'Barang: ' + stop.notes"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Khusus Pihak Ketiga: Tampilkan Ekspedisi -->
                                        <template x-if="order.courier_agency">
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 text-[9px] font-bold">
                                                <span>Agen Ekspedisi:</span>
                                                <strong x-text="order.courier_agency"></strong>
                                            </div>
                                        </template>

                                        <div class="flex items-start gap-1">
                                            <svg class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <span class="leading-tight font-medium" x-text="'Tujuan Antar: ' + order.destination_address"></span>
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
                                                placeholder="17.000"
                                                class="w-full bg-white border border-slate-200 rounded-xl pl-8 pr-3 py-2.5 text-[10px] font-bold text-slate-800 focus:outline-none focus:border-rose-400 transition">
                                            <div x-show="orderPrices[order.id] > 0" class="text-[9px] font-bold text-rose-600 mt-1" x-text="'Tawaran: Rp ' + Number(orderPrices[order.id] || 0).toLocaleString('id-ID')"></div>
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

    <!-- Global Modal Isi Harga Tambahan Item untuk Jastiper -->
    <div x-data="{
            respondModalOpen: false,
            respondAddonDesc: '',
            respondUrl: '',
            openRespondModal(url, description) {
                this.respondUrl = url;
                this.respondAddonDesc = description;
                this.respondModalOpen = true;
            },
            closeRespondModal() {
                this.respondModalOpen = false;
            }
        }"
        @open-respond-modal.window="openRespondModal($event.detail.url, $event.detail.description)"
        x-show="respondModalOpen" 
        x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" 
        style="display: none;" 
        x-transition>
        
        <div @click.away="closeRespondModal()" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5 overflow-hidden relative">
            <div class="flex justify-between items-center mb-3 border-b border-slate-100 pb-2">
                <h3 class="text-sm font-black text-slate-800">Tentukan Harga Tambahan</h3>
                <button @click="closeRespondModal()" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-[10px] text-slate-500 mb-3 leading-normal">Berapa biaya tambahan (harga barang + upah) untuk item ini?</p>
            <div class="bg-slate-50 p-2 rounded-lg mb-3 border border-slate-100">
                <p class="text-[10px] font-bold text-slate-700" x-text="respondAddonDesc"></p>
            </div>
            
            <form :action="respondUrl" method="POST">
                @csrf
                <input type="hidden" name="action" value="accept">
                <div class="mb-4 text-left">
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Biaya (Rp)</label>
                    <input type="number" name="additional_fare" required min="1000" placeholder="15.000" class="w-full bg-slate-50 border border-slate-200 text-slate-750 px-3.5 py-2.5 rounded-xl text-xs font-black focus:outline-none focus:bg-white focus:border-emerald-500 transition duration-150">
                </div>
                
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold py-3 rounded-xl uppercase tracking-wider transition flex items-center justify-center">
                    Kirim & Setujui
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Ambil Foto Bukti Pengambilan / Pengantaran Barang -->
    <div x-data="{
            proofModalOpen: false,
            orderId: null,
            status: '',
            title: '',
            subtitle: '',
            buttonText: '',
            actionUrl: '',
            photoPreview: null,
            photoFile: null,
            isSubmitting: false,
            isCompressing: false,
            compressStatus: '',
            errorMessage: null,
            openModal(data) {
                this.orderId = data.orderId;
                this.status = data.status || '';
                this.title = data.title || 'Foto Bukti';
                this.subtitle = data.subtitle || 'Ambil atau pilih foto barang/struk.';
                this.buttonText = data.buttonText || 'Konfirmasi & Kirim';
                this.actionUrl = data.actionUrl;
                this.photoPreview = null;
                this.photoFile = null;
                this.isSubmitting = false;
                this.isCompressing = false;
                this.compressStatus = '';
                this.errorMessage = null;
                const cam = document.getElementById('camera_proof_input');
                if (cam) cam.value = '';
                const gal = document.getElementById('gallery_proof_input');
                if (gal) gal.value = '';
                this.proofModalOpen = true;
            },
            closeModal() {
                if (this.isSubmitting) return;
                this.proofModalOpen = false;
            },
            async handleFileSelect(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) return;

                this.errorMessage = null;
                this.isCompressing = true;
                this.compressStatus = 'Mengoptimalkan foto kamera...';

                try {
                    // Jika tipe bukan image atau format HEIC/HEIF mentah, fallback langsung
                    if (!file.type || !file.type.startsWith('image/')) {
                        this.photoFile = file;
                        this.photoPreview = URL.createObjectURL(file);
                        this.isCompressing = false;
                        return;
                    }

                    // Kompresi client-side via HTML5 Canvas (max 1920x1920, JPEG quality 0.85)
                    const compressed = await this.compressImage(file, 1920, 1920, 0.85);
                    this.photoFile = compressed.file;
                    this.photoPreview = compressed.previewUrl;
                } catch (err) {
                    console.warn('Canvas compression fallback to raw file:', err);
                    this.photoFile = file;
                    this.photoPreview = URL.createObjectURL(file);
                } finally {
                    this.isCompressing = false;
                    this.compressStatus = '';
                }
            },
            compressImage(file, maxWidth, maxHeight, quality) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
                            let width = img.width;
                            let height = img.height;

                            if (width > maxWidth || height > maxHeight) {
                                if (width > height) {
                                    height = Math.round((height * maxWidth) / width);
                                    width = maxWidth;
                                } else {
                                    width = Math.round((width * maxHeight) / height);
                                    height = maxHeight;
                                }
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);

                            canvas.toBlob((blob) => {
                                if (!blob) {
                                    resolve({ file, previewUrl: e.target.result });
                                    return;
                                }
                                const cleanName = (file.name || 'camera_photo').replace(/\.[^/.]+$/, '') + '.jpg';
                                const compressedFile = new File([blob], cleanName, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                resolve({
                                    file: compressedFile,
                                    previewUrl: URL.createObjectURL(blob)
                                });
                            }, 'image/jpeg', quality);
                        };
                        img.onerror = () => resolve({ file, previewUrl: e.target.result });
                        img.src = e.target.result;
                    };
                    reader.onerror = () => resolve({ file, previewUrl: URL.createObjectURL(file) });
                    reader.readAsDataURL(file);
                });
            },
            removePhoto() {
                this.photoPreview = null;
                this.photoFile = null;
                this.errorMessage = null;
                this.isCompressing = false;
                const cam = document.getElementById('camera_proof_input');
                if (cam) cam.value = '';
                const gal = document.getElementById('gallery_proof_input');
                if (gal) gal.value = '';
            },
            async submitProof() {
                if (this.isSubmitting || this.isCompressing) return;

                if (!this.photoFile && !this.photoPreview) {
                    this.errorMessage = 'Silakan ambil foto dengan kamera atau pilih dari galeri terlebih dahulu.';
                    if (window.jastiperNotify) {
                        window.jastiperNotify(this.errorMessage, false);
                    }
                    return;
                }

                this.isSubmitting = true;
                this.errorMessage = null;

                try {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    if (this.status) {
                        formData.append('status', this.status);
                    }
                    if (this.photoFile) {
                        formData.append('photo', this.photoFile, this.photoFile.name || 'proof.jpg');
                    }

                    const response = await fetch(this.actionUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json().catch(() => null);

                    if (response.ok && (!data || data.success !== false)) {
                        const msg = (data && data.message) ? data.message : 'Bukti foto berhasil dikonfirmasi!';
                        if (window.jastiperNotify) {
                            window.jastiperNotify(msg, true);
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 600);
                    } else {
                        let errMsg = 'Gagal menyimpan foto bukti. Silakan coba lagi.';
                        if (data && data.message) {
                            errMsg = data.message;
                        } else if (data && data.errors) {
                            errMsg = Object.values(data.errors).flat()[0] || errMsg;
                        }
                        this.errorMessage = errMsg;
                        if (window.jastiperNotify) {
                            window.jastiperNotify(errMsg, false);
                        }
                        this.isSubmitting = false;
                    }
                } catch (error) {
                    console.error('Submit proof error:', error);
                    this.errorMessage = 'Koneksi terputus ke server. Periksa jaringan Anda dan tekan tombol kirim lagi.';
                    if (window.jastiperNotify) {
                        window.jastiperNotify(this.errorMessage, false);
                    }
                    this.isSubmitting = false;
                }
            }
        }"
        @open-proof-modal.window="openModal($event.detail)"
        x-show="proofModalOpen" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" 
        style="display: none;" 
        x-transition>
        
        <div @click.away="closeModal()" class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-5 overflow-hidden relative border border-slate-100 max-h-[92vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="text-left min-w-0">
                        <h3 class="text-xs font-black text-slate-800 truncate" x-text="title"></h3>
                        <span class="text-[9px] text-slate-400 block line-clamp-1" x-text="subtitle"></span>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form @submit.prevent="submitProof()" class="overflow-y-auto space-y-3">
                <!-- Native Inputs with ID for Native Label Binding -->
                <input type="file" 
                    id="camera_proof_input"
                    name="photo"
                    accept="image/*" 
                    capture="environment" 
                    @change="handleFileSelect($event)" 
                    class="sr-only">

                <input type="file" 
                    id="gallery_proof_input"
                    accept="image/*,.heic,.heif,image/heic,image/heif" 
                    @change="handleFileSelect($event)" 
                    class="sr-only">

                <!-- Alert Error jika ada -->
                <template x-if="errorMessage">
                    <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-[10px] font-bold flex items-start gap-2">
                        <span class="text-xs shrink-0">⚠️</span>
                        <span x-text="errorMessage" class="leading-tight"></span>
                    </div>
                </template>

                <!-- Area Pilihan Ambil / Unggah Foto -->
                <template x-if="!photoPreview && !isCompressing">
                    <div class="space-y-2">
                        <!-- Tombol Buka Kamera Langsung via Native Label -->
                        <label for="camera_proof_input" 
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white rounded-2xl p-4 text-left transition flex items-center gap-3.5 group cursor-pointer shadow-sm select-none">
                            <div class="w-10 h-10 rounded-xl bg-white/10 group-hover:bg-white/20 flex items-center justify-center shrink-0 transition">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-black block">Ambil Foto dengan Kamera</span>
                                <span class="text-[9.5px] text-slate-300 block mt-0.5">Buka kamera HP langsung untuk jepret barang / struk</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </label>

                        <!-- Tombol Pilih dari Galeri / Berkas via Native Label -->
                        <label for="gallery_proof_input" 
                            class="w-full bg-slate-50 hover:bg-slate-100/90 border border-slate-200 text-slate-800 rounded-2xl p-3.5 text-left transition flex items-center gap-3 group cursor-pointer select-none">
                            <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-bold block">Pilih dari Galeri / File</span>
                                <span class="text-[9px] text-slate-400 block mt-0.5">Pilih foto yang sudah pernah difoto sebelumnya</span>
                            </div>
                        </label>
                    </div>
                </template>

                <!-- Loading State saat Kompresi Gambar di Browser -->
                <template x-if="isCompressing">
                    <div class="border-2 border-dashed border-indigo-200 bg-indigo-50/50 rounded-2xl p-6 text-center flex flex-col items-center justify-center gap-2">
                        <svg class="animate-spin w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs font-black text-indigo-900" x-text="compressStatus || 'Memproses foto...'"></span>
                        <span class="text-[9px] text-indigo-600 font-medium">Mengompresi otomatis agar hemat kuota & cepat terunggah</span>
                    </div>
                </template>

                <!-- Image Preview setelah Foto Terpilih -->
                <template x-if="photoPreview && !isCompressing">
                    <div class="space-y-2">
                        <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 h-44 flex items-center justify-center shadow-inner">
                            <img :src="photoPreview" class="w-full h-full object-cover" alt="Preview Foto Bukti">
                            <button type="button" @click="removePhoto()" class="absolute top-2 right-2 w-7 h-7 rounded-full bg-slate-900/80 text-white flex items-center justify-center hover:bg-slate-900 transition cursor-pointer shadow-md">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-[9.5px] px-1">
                            <span class="text-emerald-600 font-bold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Foto Siap & Optimal
                            </span>
                            <div class="flex items-center gap-2">
                                <label for="camera_proof_input" class="text-slate-600 font-bold hover:underline cursor-pointer">
                                    Foto Ulang
                                </label>
                                <span class="text-slate-300">•</span>
                                <label for="gallery_proof_input" class="text-slate-600 font-bold hover:underline cursor-pointer">
                                    Galeri
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Submit Buttons -->
                <div class="pt-2 space-y-2 shrink-0">
                    <button type="submit" 
                        :disabled="isSubmitting || isCompressing || (!photoFile && !photoPreview)"
                        class="w-full bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed text-white text-[10px] font-black py-3 rounded-xl uppercase tracking-wider transition flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                        <svg x-show="isSubmitting" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg x-show="!isSubmitting" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="isSubmitting ? 'Mengunggah & Menyimpan...' : (isCompressing ? 'Memproses Foto...' : buttonText)"></span>
                    </button>
                    
                    <button type="button" @click="closeModal()" :disabled="isSubmitting" class="w-full text-slate-400 hover:text-slate-600 disabled:opacity-50 text-[10px] font-bold py-1 transition cursor-pointer">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Global Image Lightbox Modal -->
    <div x-data="{
            lightboxOpen: false,
            lightboxUrl: '',
            lightboxTitle: '',
            openLightbox(data) {
                this.lightboxUrl = data.url;
                this.lightboxTitle = data.title || 'Foto Bukti';
                this.lightboxOpen = true;
            },
            closeLightbox() {
                this.lightboxOpen = false;
            }
        }"
        @open-image-lightbox.window="openLightbox($event.detail)"
        x-show="lightboxOpen"
        x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
        style="display: none;"
        x-transition>
        
        <div @click.away="closeLightbox()" class="max-w-lg w-full bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-800 flex flex-col">
            <div class="p-3.5 bg-slate-950/90 border-b border-slate-800 flex items-center justify-between text-white">
                <span class="text-xs font-bold" x-text="lightboxTitle"></span>
                <button type="button" @click="closeLightbox()" class="text-slate-400 hover:text-white p-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-2 flex items-center justify-center bg-black/40 max-h-[75vh] overflow-auto">
                <img :src="lightboxUrl" class="max-w-full max-h-[70vh] rounded-xl object-contain shadow-lg" alt="Foto">
            </div>
            <div class="p-3 bg-slate-950/90 border-t border-slate-800 flex justify-end">
                <a :href="lightboxUrl" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-bold transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Buka Ukuran Penuh
                </a>
            </div>
        </div>
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
                attributionControl: false,
                scrollWheelZoom: false
            }).setView([currentLat, currentLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18
            }).addTo(map);

            const isCheckin = @json(!empty($jastiper->checkin_location));
            const checkinName = @json($jastiper->checkin_location ?? '');

            const jastiperIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });

            marker = L.marker([currentLat, currentLng], {
                icon: jastiperIcon
            }).addTo(map);

            if (isCheckin) {
                marker.bindPopup(`
                    <div class="text-xs p-1 font-sans text-center">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[9px] uppercase tracking-wider inline-block mb-0.5">Sedang Check-in</span>
                        <strong class="text-slate-800 text-xs block">${checkinName}</strong>
                        <span class="text-[9px] text-emerald-600 font-semibold block mt-0.5">Siap menerima pesanan langsung</span>
                    </div>
                `).openPopup();
            }

            circle = L.circle([currentLat, currentLng], {
                color: isCheckin ? '#059669' : '#e11d48',
                fillColor: isCheckin ? '#10b981' : '#f43f5e',
                fillOpacity: 0.12,
                radius: currentRadius * 1000
            }).addTo(map);

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const latEl = document.getElementById('checkin_lat');
                    const lngEl = document.getElementById('checkin_lng');
                    if (latEl && lngEl) {
                        latEl.value = pos.coords.latitude;
                        lngEl.value = pos.coords.longitude;
                    }
                }, () => {}, { timeout: 5000, maximumAge: 60000 });
            }

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