<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link class="js-favicon" rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2220%22 fill=%22%23EC0A23%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22central%22 text-anchor=%22middle%22 fill=%22%23ffffff%22 font-family=%22system-ui,sans-serif%22 font-size=%2250%22 font-weight=%22bold%22>JK</text></svg>">

        <title>@yield('title') - JastipKuy</title>
        <meta name="description" content="@yield('meta_description', 'Platform Jasa Titip On-Demand Wilayah Terpercaya')">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

        <!-- PWA Meta & Manifest -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#EC0A23">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="JastipKuy">
        <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
        <link rel="apple-touch-icon" sizes="512x512" href="/images/icons/icon-512x512.png">

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        @livewireStyles
        
        <style>
            body, h1, h2, h3, h4, .font-display {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
        </style>
        @yield('styles')
    </head>
    <body class="bg-[#F8FAFC] text-slate-800 antialiased selection:bg-rose-600 selection:text-white flex flex-col min-h-screen">

        <!-- Header -->
        @if(!request()->is('admin/*', 'admin', 'login', 'register', 'customer/cart', 'customer/cart/*', 'customer/orders/*', 'customer/support*'))
            @include('layouts.header')
        @endif

        <!-- Main Content -->
        <main class="flex-grow {{ request()->is('customer/*', 'jastiper/*', 'customer', 'jastiper') && !request()->is('customer/orders/*', 'customer/support*') ? 'pb-20 md:pb-0' : '' }}">
            @yield('content')
        </main>

        <!-- Sticky Bottom Navigation for Mobile Dashboards -->
        @if(request()->is('customer/*', 'customer') && !request()->is('customer/cart', 'customer/cart/*', 'customer/orders/*', 'customer/support*'))
            <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200/80 shadow-[0_-4px_12px_rgba(0,0,0,0.03)] px-4 py-2 flex justify-around items-center">
                
                <!-- Beranda -->
                <a href="{{ route('customer.dashboard') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/dashboard') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>Beranda</span>
                </a>

                <!-- Keranjang Terpadu -->
                @php
                    $mobileCartCount = \App\Models\CartItem::whereHas('cart', fn($q) => $q->where('customer_id', auth('customer')->id()))->count();
                @endphp
                <a href="{{ route('customer.cart.index') }}" class="relative flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/cart*') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        @if($mobileCartCount > 0)
                            <span class="absolute -top-1 -right-2 bg-rose-600 text-white text-[8px] font-black w-3.5 h-3.5 rounded-full flex items-center justify-center border border-white">
                                {{ $mobileCartCount }}
                            </span>
                        @endif
                    </div>
                    <span>Keranjang</span>
                </a>

                <!-- Pesan Jastip (Middle) -->
                <a href="{{ route('customer.orders.create') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/orders/create') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <div class="w-10 h-10 -mt-5 bg-rose-600 text-white rounded-full flex items-center justify-center shadow-lg shadow-rose-600/35 border-4 border-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <span class="text-rose-600">Pesan</span>
                </a>

                <!-- Aktivitas -->
                <a href="{{ route('customer.activity') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/activity') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <span>Aktivitas</span>
                </a>

                <!-- Profil -->
                <a href="{{ route('customer.profile') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/profile*') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Profil</span>
                </a>

            </div>
        @elseif(request()->is('jastiper/*', 'jastiper'))
            <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200/80 shadow-[0_-4px_12px_rgba(0,0,0,0.03)] px-4 py-2 grid grid-cols-4 items-center">
                
                <!-- Beranda / Driver Portal -->
                <a href="{{ route('jastiper.dashboard') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('jastiper/dashboard') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>Beranda</span>
                </a>

                <!-- Area Kerja -->
                <a href="{{ route('jastiper.area') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('jastiper/area') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Area Kerja</span>
                </a>

                <!-- Riwayat Pesanan -->
                <a href="{{ route('jastiper.history') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('jastiper/history*') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Riwayat</span>
                </a>

                <!-- Profil Jastiper -->
                <a href="{{ route('jastiper.profile') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('jastiper/profile') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Profil</span>
                </a>

            </div>
        @endif

        <!-- Footer -->
        @if(!request()->is('customer/*', 'jastiper/*', 'admin/*', 'customer', 'jastiper', 'admin', 'login', 'register'))
            @include('layouts.footer')
        @endif

        <!-- Toast Container -->
        <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 pointer-events-none"></div>

        <style>
            @keyframes toast-slide-in {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .animate-toast-slide-in {
                animation: toast-slide-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
        </style>

        <script>
            function showMaintenanceToast(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                const container = document.getElementById('toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = "pointer-events-auto bg-slate-900 text-white border-2 border-slate-900 p-4 rounded-sm shadow-[4px_4px_0px_0px_rgba(244,63,94,1)] flex items-center gap-3 animate-toast-slide-in text-xs font-bold font-mono tracking-wide transform transition-all duration-300";
                toast.innerHTML = `
                    <span class="text-base">🛠️</span>
                    <div>
                        <p class="font-black text-slate-100 uppercase tracking-widest">UNDER MAINTENANCE</p>
                        <p class="text-slate-400 font-medium mt-0.5 font-sans">Fitur ini sedang dalam pengembangan & pemeliharaan.</p>
                    </div>
                `;

                container.appendChild(toast);

                // Auto remove after 4 seconds
                setTimeout(() => {
                    toast.classList.add('translate-y-2', 'opacity-0');
                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                }, 4000);
            }

            window.jastiperNotify = function(message, success = true) {
                const container = document.getElementById('toast-container');
                if (!container) { alert(message); return; }

                const toast = document.createElement('div');
                toast.className = "pointer-events-auto bg-slate-900 text-white border-2 p-4 rounded-xl flex items-center gap-3 animate-toast-slide-in text-xs font-bold tracking-wide transform transition-all duration-300 shadow-xl"
                    + (success ? " border-emerald-500 shadow-emerald-500/10" : " border-rose-500 shadow-rose-500/10");
                toast.innerHTML = `
                    <span class="text-base">${success ? '✅' : '⚠️'}</span>
                    <div><p class="font-sans font-semibold text-slate-100 normal-case">${message}</p></div>
                `;
                container.appendChild(toast);
                setTimeout(() => {
                    toast.classList.add('translate-y-2', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 4500);
            };

            @if(session('success'))
                document.addEventListener('DOMContentLoaded', function() {
                    window.jastiperNotify(@json(session('success')), true);
                });
            @endif
            @if(session('error'))
                document.addEventListener('DOMContentLoaded', function() {
                    window.jastiperNotify(@json(session('error')), false);
                });
            @endif
            @if(isset($errors) && $errors->any())
                document.addEventListener('DOMContentLoaded', function() {
                    window.jastiperNotify(@json($errors->first()), false);
                });
            @endif
        </script>

        @livewireScripts
        <script>
            window.searchAutocomplete = function(config) {
                return {
                    query: config.initialQuery || '',
                    open: false,
                    loading: false,
                    results: null,
                    endpoint: config.endpoint,
                    searchUrl: config.searchUrl,
                    async performSearch() {
                        const q = this.query ? this.query.trim() : '';
                        if (q.length < 1) {
                            this.results = null;
                            this.open = false;
                            this.loading = false;
                            return;
                        }
                        this.loading = true;
                        try {
                            const res = await fetch(`${this.endpoint}?q=${encodeURIComponent(q)}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if ((this.query ? this.query.trim() : '') === q) {
                                    this.results = data;
                                    this.open = true;
                                }
                            }
                        } catch (err) {
                            console.error('Search error:', err);
                        } finally {
                            this.loading = false;
                        }
                    },
                    handleSubmit(e) {
                        if (!this.query || this.query.trim().length === 0) {
                            e.preventDefault();
                        }
                    }
                };
            };

            (function() {
                function bootAlpine() {
                    if (window.Alpine && !window.Alpine.__jastip_started) {
                        try {
                            const hasLivewire = document.querySelectorAll('[wire\\:id]').length > 0;
                            if (!hasLivewire && typeof window.Alpine.start === 'function') {
                                window.Alpine.start();
                                window.Alpine.__jastip_started = true;
                            }
                        } catch (e) {
                            console.warn('Alpine boot notice:', e);
                        }
                    }
                }

                if (typeof window.Alpine === 'undefined') {
                    const s = document.createElement('script');
                    s.src = 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js';
                    s.defer = true;
                    s.onload = bootAlpine;
                    document.head.appendChild(s);
                } else {
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', bootAlpine);
                    } else {
                        bootAlpine();
                    }
                }
            })();
        </script>
        @yield('scripts')
        <!-- PWA Component -->
        @include('components.pwa-install')
    </body>
</html>
