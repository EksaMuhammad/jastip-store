<!-- Top Notification Bar -->
<div class="bg-slate-900 text-white py-2 px-4 text-center text-xs font-medium tracking-wide">
    JastipKuy Malang: Layanan Kini Tersedia di Area Malang Raya & Sekitarnya!
</div>

<!-- Navbar Header -->
<header id="navbar" class="sticky top-0 z-50 bg-white border-b border-slate-200/80 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 sm:h-20">
            <!-- Logo Section -->
            <a href="{{ url('/') }}" class="group flex items-center gap-3">
                <div class="relative w-9 h-9">
                    <div class="absolute inset-0 bg-rose-600 rounded-sm transform rotate-6 transition-transform group-hover:rotate-12 duration-300"></div>
                    <div class="absolute inset-0 bg-slate-900 rounded-sm flex items-center justify-center border border-slate-800 shadow-sm">
                        <span class="font-display font-black text-white text-xs tracking-tighter">JK</span>
                    </div>
                </div>
                <span class="font-display font-extrabold text-2xl text-slate-900 tracking-tight">
                    Jastip<span class="text-rose-600">Kuy</span>
                </span>
            </a>

            <!-- Navigation Links -->
            @if(request()->is('customer/*', 'customer'))
                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ route('customer.dashboard') }}" class="text-sm font-semibold {{ request()->is('customer/dashboard') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Beranda</a>
                    <a href="{{ route('customer.booking') }}" class="text-sm font-semibold {{ request()->is('customer/booking') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Booking Jastiper</a>
                    <a href="{{ route('customer.orders.create') }}" class="text-sm font-semibold {{ request()->is('customer/orders/create') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Pesan Jastip</a>
                    <a href="{{ route('customer.cart.index') }}" class="text-sm font-semibold {{ request()->is('customer/cart*') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition flex items-center gap-1.5">
                        <span>Keranjang</span>
                        @php
                            $headerCartCount = \App\Models\CartItem::whereHas('cart', fn($q) => $q->where('customer_id', auth('customer')->id()))->count();
                        @endphp
                        @if($headerCartCount > 0)
                            <span class="bg-rose-600 text-white text-[10px] font-black px-1.5 py-0.2 rounded-full">{{ $headerCartCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('customer.activity', ['tab' => 'riwayat']) }}" class="text-sm font-semibold {{ request()->query('tab') === 'riwayat' ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Riwayat</a>
                    <a href="{{ route('customer.profile') }}" class="text-sm font-semibold {{ request()->is('customer/profile*') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Profil Saya</a>
                </nav>
            @elseif(request()->is('jastiper/*', 'jastiper'))
                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ route('jastiper.dashboard') }}" class="text-sm font-semibold {{ request()->is('jastiper/dashboard') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Beranda</a>
                    <a href="{{ route('jastiper.area') }}" class="text-sm font-semibold {{ request()->is('jastiper/area') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Area Kerja</a>
                    <a href="{{ route('jastiper.history') }}" class="text-sm font-semibold {{ request()->is('jastiper/history*') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Riwayat</a>
                    <a href="{{ route('jastiper.profile') }}" class="text-sm font-semibold {{ request()->is('jastiper/profile') ? 'text-rose-600 font-extrabold' : 'text-slate-600 hover:text-rose-600' }} transition">Profil Jastiper</a>
                </nav>
            @else
                <nav class="hidden md:flex items-center gap-8">
                    <a href="{{ request()->is('/') ? '#hero' : url('/#hero') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Beranda</a>
                    <a href="{{ request()->is('/') ? '#cara-kerja' : url('/#cara-kerja') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Cara Kerja</a>
                    <a href="{{ request()->is('/') ? '#layanan' : url('/#layanan') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Layanan</a>
                    <a href="{{ request()->is('/') ? '#keunggulan' : url('/#keunggulan') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Mengapa Kami</a>
                    <a href="{{ request()->is('/') ? '#testimoni' : url('/#testimoni') }}" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Testimoni</a>
                </nav>
            @endif

            <!-- Authentication / Action Buttons -->
            <div class="flex items-center gap-3">
                @if (request()->is('jastiper*', 'jastiper') && Auth::guard('jastiper')->check())
                    <a href="{{ route('jastiper.dashboard') }}" id="btn-dashboard" class="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Dashboard Jastiper
                    </a>
                @elseif (Auth::guard('customer')->check())
                    @php
                        $currentCustomerId = Auth::guard('customer')->id();

                        // 1. Notifikasi Perubahan Harga Toko (Memerlukan Tindakan Setujui/Tolak)
                        $pendingAdjustments = \App\Models\OrderPriceAdjustment::whereHas('order', fn($q) => $q->where('customer_id', $currentCustomerId))
                            ->where('status', 'pending')
                            ->with('order')
                            ->latest()
                            ->get();

                        // 2. Pesanan Dalam Peninjauan Sengketa / Komplain
                        $disputedOrders = \App\Models\Order::where('customer_id', $currentCustomerId)
                            ->where('status', 'bermasalah')
                            ->latest('updated_at')
                            ->get();

                        // 3. Pesan Chat Belum Dibaca
                        $unreadChatCount = \App\Models\Chat::whereHas('order', fn($q) => $q->where('customer_id', $currentCustomerId))
                            ->where('sender_role', '!=', 'customer')
                            ->where('is_read', false)
                            ->count();

                        // 4. Notifikasi Pesanan Aktif Berjalan (Belanja, Diambil, Diantar, Tiba di Tujuan)
                        $activeOrderUpdates = \App\Models\Order::where('customer_id', $currentCustomerId)
                            ->whereIn('status', ['diproses', 'barang_diambil', 'sedang_diantar', 'diantar', 'tiba_tujuan'])
                            ->with(['jastiper'])
                            ->latest('updated_at')
                            ->take(6)
                            ->get();

                        // 5. Pesanan Menunggu dengan Tawaran Baru Masuk dari Jastiper
                        $ordersWithOffers = \App\Models\Order::where('customer_id', $currentCustomerId)
                            ->whereIn('status', ['menunggu_tawaran', 'ada_tawaran'])
                            ->whereHas('offers', fn($q) => $q->where('status', 'pending'))
                            ->withCount(['offers' => fn($q) => $q->where('status', 'pending')])
                            ->latest('updated_at')
                            ->take(3)
                            ->get();

                        // 6. Top-Up Saldo E-Wallet Berhasil (Terbaru 2 Hari Terakhir)
                        $recentTopups = \App\Models\Topup::whereHas('wallet', fn($q) => $q->where('owner_role', 'customer')->where('owner_id', $currentCustomerId))
                            ->where('status', 'berhasil')
                            ->where('updated_at', '>=', now()->subDays(2))
                            ->latest('updated_at')
                            ->take(2)
                            ->get();

                        // 7. Pesanan Selesai Belum Diberi Rating (Terbaru 1 Hari Terakhir)
                        $unratedOrders = \App\Models\Order::where('customer_id', $currentCustomerId)
                            ->where('status', 'selesai')
                            ->whereDoesntHave('rating')
                            ->where('updated_at', '>=', now()->subDay())
                            ->latest('updated_at')
                            ->take(2)
                            ->get();

                        $actionableCount = $pendingAdjustments->count() + $disputedOrders->count();
                        $totalNotificationCount = $pendingAdjustments->count() 
                            + $disputedOrders->count() 
                            + ($unreadChatCount > 0 ? 1 : 0)
                            + $activeOrderUpdates->count()
                            + $ordersWithOffers->count()
                            + $recentTopups->count()
                            + $unratedOrders->count();
                    @endphp

                    <!-- Lonceng Notifikasi & Aktivitas Customer -->
                    <div class="relative" x-data="{ openNotifications: false }" @click.outside="openNotifications = false">
                        <button type="button" 
                                @click="openNotifications = !openNotifications"
                                id="btn-customer-notifications"
                                class="relative p-2 rounded-full bg-slate-100 hover:bg-amber-50 text-slate-700 hover:text-amber-600 transition shrink-0 focus:outline-none cursor-pointer"
                                title="Notifikasi & Pembaruan Aktivitas">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            
                            @if($totalNotificationCount > 0)
                                <span class="absolute -top-1 -right-1 bg-rose-600 text-white text-[10px] font-black min-w-[20px] h-5 px-1.5 rounded-full flex items-center justify-center leading-none border-2 border-white shadow-xs">
                                    {{ $totalNotificationCount > 9 ? '9+' : $totalNotificationCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Mobile Backdrop Overlay -->
                        <div x-show="openNotifications" 
                             x-cloak 
                             style="display: none;"
                             @click="openNotifications = false" 
                             :class="{ 'pointer-events-none': !openNotifications }"
                             class="fixed inset-0 bg-slate-900/40 backdrop-blur-2xs z-40 sm:hidden"></div>

                        <!-- Dropdown Notifikasi (Responsive & Perfectly Aligned on Mobile and Desktop) -->
                        <div x-show="openNotifications" 
                             x-cloak
                             style="display: none;"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                             class="absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] sm:w-96 max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden text-left">
                            
                            <!-- Header Dropdown -->
                            <div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🔔</span>
                                    <span class="font-extrabold text-xs tracking-tight">Notifikasi & Aktivitas</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($actionableCount > 0)
                                        <span class="bg-rose-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                            {{ $actionableCount }} Perlu Tindakan
                                        </span>
                                    @elseif($totalNotificationCount > 0)
                                        <span class="bg-slate-700 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            {{ $totalNotificationCount }} Pembaruan
                                        </span>
                                    @endif
                                    <button type="button" @click="openNotifications = false" class="text-slate-400 hover:text-white p-1 rounded-lg sm:hidden" title="Tutup">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- List Notifikasi -->
                            <div class="max-h-[420px] overflow-y-auto divide-y divide-slate-100 p-2 space-y-1.5">
                                
                                <!-- 1. Perubahan Harga Toko (Perlu Tindakan Customer) -->
                                @foreach($pendingAdjustments as $adj)
                                    <div onclick="window.location.href='{{ route('customer.orders.show', $adj->order_id) }}'"
                                         class="p-3 bg-amber-50 hover:bg-amber-100/90 rounded-xl border border-amber-200/90 transition cursor-pointer space-y-1.5 shadow-2xs">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-1.5 text-amber-900 font-extrabold text-xs">
                                                <span>🏷️</span>
                                                <span>Tindakan: Persetujuan Harga</span>
                                            </div>
                                            <span class="text-[9px] font-black text-rose-700 bg-rose-100 px-2 py-0.5 rounded-md uppercase tracking-wider animate-pulse">
                                                Perlu Respon
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-800 leading-snug">
                                            {{ $adj->item_name }} (<span class="text-rose-600 font-black">+Rp{{ number_format($adj->difference, 0, ',', '.') }}</span>)
                                        </p>
                                        <p class="text-[10px] text-amber-800 leading-tight">
                                            Jastiper meminta persetujuan kenaikan harga di kasir toko. Ketuk untuk menyetujui atau menolak.
                                        </p>
                                        <div class="pt-1 flex items-center justify-between text-[10px] text-slate-500 font-medium border-t border-amber-200/60">
                                            <span>Pesanan #{{ $adj->order_id }}</span>
                                            <span class="text-rose-600 font-extrabold flex items-center gap-0.5">Buka & Tinjau →</span>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- 2. Pengaduan Sengketa Dalam Peninjauan CS -->
                                @foreach($disputedOrders as $dispOrder)
                                    <div onclick="window.location.href='{{ route('customer.orders.report', $dispOrder->id) }}'"
                                         class="p-3 bg-rose-50/70 hover:bg-rose-100/80 rounded-xl border border-rose-200 transition cursor-pointer space-y-1.5 shadow-2xs">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-1.5 text-rose-900 font-extrabold text-xs">
                                                <span>⚖️</span>
                                                <span>Pengaduan Sengketa</span>
                                            </div>
                                            <span class="text-[9px] font-bold text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded-md">
                                                Investigasi CS
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-800 leading-snug truncate">
                                            {{ $dispOrder->description }}
                                        </p>
                                        <p class="text-[10px] text-slate-600 leading-tight">
                                            Pengaduan Anda sedang ditangani oleh Tim CS. Dana aman tertahan di Virtual Escrow.
                                        </p>
                                        <div class="pt-1 flex items-center justify-between text-[10px] text-slate-400 font-medium border-t border-rose-100">
                                            <span>Pesanan #{{ $dispOrder->id }}</span>
                                            <span class="text-rose-600 font-extrabold">Pantau Status →</span>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- 3. Pesan Chat Masuk -->
                                @if($unreadChatCount > 0)
                                    <div onclick="window.location.href='{{ route('customer.chats') }}'"
                                         class="p-3 bg-rose-50/70 hover:bg-rose-100/80 rounded-xl border border-rose-100 transition cursor-pointer space-y-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 text-rose-700 font-extrabold text-xs">
                                                <span>💬</span>
                                                <span>Pesan Baru di Chat</span>
                                            </div>
                                            <span class="text-[9px] bg-rose-600 text-white font-black px-1.5 py-0.2 rounded-full">
                                                {{ $unreadChatCount }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-600">Ada pesan terbaru dari Jastiper untuk pesanan Anda.</p>
                                    </div>
                                @endif

                                <!-- 4. Tawaran Jastiper Masuk -->
                                @foreach($ordersWithOffers as $offOrder)
                                    <div onclick="window.location.href='{{ route('customer.orders.show', $offOrder->id) }}'"
                                         class="p-3 bg-indigo-50/80 hover:bg-indigo-100/90 rounded-xl border border-indigo-200 transition cursor-pointer space-y-1 shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 text-indigo-900 font-extrabold text-xs">
                                                <span>🛵</span>
                                                <span>Penawaran Jastiper Masuk</span>
                                            </div>
                                            <span class="text-[9px] bg-indigo-600 text-white font-black px-2 py-0.5 rounded-full">
                                                {{ $offOrder->offers_count }} Tawaran
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-800 truncate">{{ $offOrder->description }}</p>
                                        <p class="text-[10px] text-indigo-800">Ada mitra jastiper yang mengajukan tawaran untuk pesanan Anda. Pilih dan sepakati tawaran!</p>
                                    </div>
                                @endforeach

                                <!-- 5. Status Pesanan Aktif Berjalan (Progres Stop / Belanja / Antar) -->
                                @foreach($activeOrderUpdates as $actOrder)
                                    @php
                                        $stops = $actOrder->multi_stops ?: [];
                                        $completedCount = collect($stops)->where('status', 'completed')->count();
                                        $totalStopsCount = count($stops);

                                        if ($totalStopsCount > 1 && $actOrder->status === 'diproses') {
                                            if ($completedCount > 0) {
                                                $statusText = "Stop #{$completedCount} selesai! Jastiper menuju Stop #" . ($completedCount + 1);
                                                $icon = "📦";
                                            } else {
                                                $statusText = "Jastiper menuju titik belanja Stop #1";
                                                $icon = "🛵";
                                            }
                                        } elseif ($actOrder->status === 'diproses') {
                                            $statusText = "Jastiper sedang membeli / belanja pesanan Anda di toko";
                                            $icon = "🛒";
                                        } elseif ($actOrder->status === 'barang_diambil') {
                                            $statusText = "Jastiper telah selesai mengambil barang di toko";
                                            $icon = "🛍️";
                                        } elseif (in_array($actOrder->status, ['sedang_diantar', 'diantar'])) {
                                            $statusText = "Jastiper sedang mengantar pesanan ke alamat Anda";
                                            $icon = "🚀";
                                        } elseif ($actOrder->status === 'tiba_tujuan') {
                                            $statusText = "Jastiper tiba di tujuan! Mohon konfirmasi penerimaan";
                                            $icon = "📍";
                                        } else {
                                            $statusText = "Pesanan sedang diproses jastiper";
                                            $icon = "🛵";
                                        }
                                    @endphp
                                    <div onclick="window.location.href='{{ route('customer.orders.show', $actOrder->id) }}'"
                                         class="p-3 bg-white hover:bg-slate-50 rounded-xl border border-slate-200 transition cursor-pointer space-y-1 shadow-2xs">
                                        <div class="flex items-center justify-between text-xs font-bold text-slate-900">
                                            <span class="truncate flex items-center gap-1.5">
                                                <span>{{ $icon }}</span>
                                                <span class="truncate">{{ $actOrder->description }}</span>
                                            </span>
                                            <span class="text-[9px] text-slate-400 font-normal shrink-0">{{ $actOrder->updated_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-[10px] font-bold text-emerald-700 leading-tight">
                                            {{ $statusText }}
                                        </p>
                                        @if($actOrder->jastiper)
                                            <p class="text-[9px] text-slate-500">Mitra: {{ $actOrder->jastiper->name }} ({{ $actOrder->jastiper->vehicle_plate ?? 'Motor' }})</p>
                                        @endif
                                    </div>
                                @endforeach

                                <!-- 6. Top-Up Berhasil Masuk -->
                                @foreach($recentTopups as $rt)
                                    <div onclick="window.location.href='{{ route('customer.wallet.topup.invoice', $rt->id) }}'"
                                         class="p-3 bg-emerald-50/70 hover:bg-emerald-100/80 rounded-xl border border-emerald-200 transition cursor-pointer space-y-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 text-emerald-800 font-extrabold text-xs">
                                                <span>💳</span>
                                                <span>Top-Up E-Wallet Berhasil</span>
                                            </div>
                                            <span class="text-[9px] bg-emerald-600 text-white font-bold px-1.5 py-0.2 rounded-md">
                                                Lunas
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-900">Rp{{ number_format($rt->amount, 0, ',', '.') }} telah ditambahkan ke saldo dompet.</p>
                                        <div class="pt-0.5 flex items-center justify-between text-[9px] text-emerald-700 font-semibold">
                                            <span>#TOPUP-{{ $rt->id }}</span>
                                            <span class="underline">Cetak Bukti →</span>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- 7. Pesanan Selesai Perlu Rating -->
                                @foreach($unratedOrders as $unOrder)
                                    <div onclick="window.location.href='{{ route('customer.orders.rating.form', $unOrder->id) }}'"
                                         class="p-3 bg-amber-50/80 hover:bg-amber-100/90 rounded-xl border border-amber-200 transition cursor-pointer space-y-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 text-amber-900 font-extrabold text-xs">
                                                <span>⭐</span>
                                                <span>Beri Ulasan Jastiper</span>
                                            </div>
                                            <span class="text-[9px] bg-amber-500 text-white font-bold px-1.5 py-0.2 rounded-md">
                                                Selesai
                                            </span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-800 truncate">{{ $unOrder->description }}</p>
                                        <p class="text-[10px] text-amber-800">Bagaimana pelayanan Jastiper? Ketuk untuk berikan bintang & ulasan.</p>
                                    </div>
                                @endforeach

                                <!-- Empty State -->
                                @if($totalNotificationCount === 0)
                                    <div class="py-8 text-center space-y-2">
                                        <span class="text-3xl block">✨</span>
                                        <p class="text-xs font-bold text-slate-700">Tidak ada notifikasi baru</p>
                                        <p class="text-[10px] text-slate-400">Pembaruan aktivitas pesanan & saldo akan muncul di sini.</p>
                                    </div>
                                @endif

                            </div>

                            <!-- Footer -->
                            <div class="p-2.5 bg-slate-50 border-t border-slate-100 text-center">
                                <a href="{{ route('customer.activity') }}" class="text-[11px] font-extrabold text-rose-600 hover:text-rose-700 block">
                                    Lihat Semua Aktivitas & Riwayat →
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif (Auth::guard('jastiper')->check())
                    <a href="{{ route('jastiper.dashboard') }}" id="btn-dashboard" class="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Dashboard
                    </a>
                @elseif (Auth::guard('admin')->check())
                    <a href="{{ route('admin.dashboard') }}" id="btn-dashboard" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Admin Panel
                    </a>
                    <a href="{{ route('login') }}" id="btn-login" class="inline-flex items-center justify-center border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2.5 rounded-sm transition">
                        Masuk
                    </a>
                @else
                    <a href="{{ route('login') }}" id="btn-login" class="inline-flex items-center justify-center border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2.5 rounded-sm transition">
                        Masuk
                    </a>
                    <a href="{{ route('login') }}" id="btn-register" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                        Daftar Sekarang
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
