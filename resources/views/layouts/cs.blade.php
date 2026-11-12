<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link class="js-favicon" rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2220%22 fill=%22%234F46E5%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22central%22 text-anchor=%22middle%22 fill=%22%23ffffff%22 font-family=%22system-ui,sans-serif%22 font-size=%2250%22 font-weight=%22bold%22>CS</text></svg>">

    <title>@yield('title', 'Portal Customer Service') - JastipKuy</title>

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
        [x-cloak] {
            display: none !important;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
    </style>
    @yield('styles')
</head>
<body class="bg-[#F3F4F6] text-slate-800 antialiased selection:bg-slate-900 selection:text-white min-h-screen flex flex-col">

    <!-- CS DESK SIDEBAR -->
    @include('layouts.cs-sidebar')

    <!-- MAIN WORKSPACE -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen @yield('workspace-overflow', '')">
        @yield('content')
    </div>

    <!-- HEARTBEAT & REAL-TIME QUEUE ALERT SCRIPT -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            function sendHeartbeat() {
                fetch('{{ route("cs.heartbeat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.has_urgent_queue) {
                        const alertEl = document.getElementById('cs-urgent-queue-alert');
                        if (alertEl) {
                            alertEl.classList.remove('hidden');
                        }
                    }
                })
                .catch(err => {
                    console.warn('CS Heartbeat ping failed:', err);
                });
            }

            // Ping heartbeat every 30 seconds
            setInterval(sendHeartbeat, 30000);
        });
    </script>

    @yield('scripts')

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.Alpine === 'undefined') {
                const alpineScript = document.createElement('script');
                alpineScript.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js';
                alpineScript.defer = true;
                document.head.appendChild(alpineScript);
            }
        });
    </script>
</body>
</html>

