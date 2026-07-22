<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') - JastipKuy</title>
        <meta name="description" content="@yield('meta_description', 'Platform Jasa Titip On-Demand Wilayah Terpercaya')">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

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
        @if(!request()->is('admin/*', 'admin'))
            @include('layouts.header')
        @endif

        <!-- Main Content -->
        <main class="flex-grow {{ request()->is('customer/*', 'jastiper/*', 'customer', 'jastiper') ? 'pb-20 md:pb-0' : '' }}">
            @yield('content')
        </main>

        <!-- Sticky Bottom Navigation for Mobile Dashboards -->
        @if(request()->is('customer/*', 'customer'))
            <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200/80 shadow-[0_-4px_12px_rgba(0,0,0,0.03)] px-6 py-2 flex justify-around items-center">
                
                <!-- Beranda -->
                <a href="{{ route('customer.dashboard') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/dashboard') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>Beranda</span>
                </a>

                <!-- Pesan Jastip -->
                <a href="{{ route('customer.orders.create') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/orders/create') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <div class="w-10 h-10 -mt-5 bg-rose-600 text-white rounded-full flex items-center justify-center shadow-lg shadow-rose-600/35 border-4 border-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <span class="text-rose-600">Pesan</span>
                </a>

                <!-- Booking -->
                <a href="{{ route('customer.booking') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('customer/booking') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Booking</span>
                </a>

                <!-- Profil -->
                <a href="#" onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-1 text-[9px] font-bold text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Profil</span>
                </a>

            </div>
        @elseif(request()->is('jastiper/*', 'jastiper'))
            <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200/80 shadow-[0_-4px_12px_rgba(0,0,0,0.03)] px-6 py-2 flex justify-around items-center">
                
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

                <!-- Verifikasi Akun -->
                <a href="{{ route('jastiper.verification') }}" class="flex flex-col items-center gap-1 text-[9px] font-bold {{ request()->is('jastiper/verification') ? 'text-rose-600' : 'text-slate-400 hover:text-slate-600' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Verifikasi</span>
                </a>

                <!-- Profil Jastiper -->
                <a href="#" onclick="showMaintenanceToast(event)" class="flex flex-col items-center gap-1 text-[9px] font-bold text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Profil</span>
                </a>

            </div>
        @endif

        <!-- Footer -->
        @if(!request()->is('customer/*', 'jastiper/*', 'admin/*', 'customer', 'jastiper', 'admin'))
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
        </script>

        @livewireScripts
        @yield('scripts')
    </body>
</html>
