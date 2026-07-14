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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            h1, h2, h3, h4, .font-display {
                font-family: 'Outfit', sans-serif;
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

        @yield('scripts')
    </body>
</html>
