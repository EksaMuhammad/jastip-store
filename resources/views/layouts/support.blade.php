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
        
        <style>
            body, h1, h2, h3, h4, .font-display {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
        </style>
        @yield('styles')
    </head>
    <body class="bg-[#F8FAFC] text-slate-800 antialiased selection:bg-rose-600 selection:text-white flex flex-col min-h-screen">

        <!-- Header -->
        @include('layouts.header')

        <!-- Main Content -->
        <main class="flex-grow">
            @yield('content')
        </main>

        <!-- Footer -->
        @include('layouts.footer')

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

        @yield('scripts')
    </body>
</html>
