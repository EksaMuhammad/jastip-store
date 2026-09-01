<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Favicon -->
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2220%22 fill=%22%23EC0A23%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22central%22 text-anchor=%22middle%22 fill=%22%23ffffff%22 font-family=%22system-ui,sans-serif%22 font-size=%2250%22 font-weight=%22bold%22>JK</text></svg>">

        <title>JastipKuy - Platform Jasa Titip On-Demand Wilayah Terpercaya</title>
        <meta name="description" content="JastipKuy menghubungkan Anda dengan Jastiper terdekat untuk membelikan barang apa pun. Aman, transparan dengan Escrow System, dan berbasis wilayah.">
        <meta name="keywords" content="jasa titip, jastip, jastipkuy, titip belanja, escrow, logistik, pengiriman wilayah">
        
        <!-- OpenGraph SEO -->
        <meta property="og:title" content="JastipKuy - Platform Jasa Titip On-Demand Wilayah">
        <meta property="og:description" content="Titip barang, makanan, fashion, dan elektronik dari mana saja dengan sistem rekening bersama aman dan kurir terverifikasi.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">

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
    </head>
    <body class="bg-[#F8FAFC] text-slate-800 antialiased selection:bg-rose-600 selection:text-white">

        @include('layouts.header')

        <main>
            <!-- Hero Section -->
            <section id="hero" class="relative overflow-hidden py-16 lg:py-24 bg-white border-b border-slate-100">
                <!-- Subtle Background Image Overlay -->
                <div class="absolute inset-0 z-0 opacity-[0.06] pointer-events-none bg-cover bg-center" style="background-image: url('{{ asset('images/hero-bg.png') }}');"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        
                        <!-- Left Hero Info Column -->
                        <div class="lg:col-span-7 flex flex-col items-start space-y-6 lg:pr-6">
                            <h1 class="font-display font-black text-4xl sm:text-5xl lg:text-6xl text-slate-900 leading-tight tracking-tight">
                                Titip Belanja Apa Saja, <br class="hidden sm:inline" />
                                <span class="text-rose-600">Aman & Sampai</span> Hari Ini.
                            </h1>
                            <p class="text-lg text-slate-600 max-w-2xl leading-relaxed">
                                JastipKuy adalah platform jasa titip on-demand berbasis wilayah. Hubungkan diri Anda dengan Jastiper terverifikasi di area terdekat untuk membelikan kuliner, fashion, gadget, atau kebutuhan harian dengan biaya transparan dan jaminan sistem rekening bersama (Escrow).
                            </p>
                            
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                                <a href="#calculator" id="btn-hero-cta" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-base px-6 py-3.5 rounded-sm shadow-sm transition duration-150">
                                    Mulai Titip Sekarang
                                </a>
                                <a href="#cara-kerja" class="inline-flex items-center justify-center border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-semibold text-base px-6 py-3.5 rounded-sm transition">
                                    Bagaimana Ini Bekerja?
                                </a>
                            </div>

                            <!-- Trust Quick Stats -->
                            <div class="grid grid-cols-3 gap-6 pt-6 border-t border-slate-100 w-full">
                                <div>
                                    <div class="text-2xl font-extrabold text-slate-900 font-display">15,000+</div>
                                    <div class="text-xs text-slate-500 font-semibold mt-1">Titipan Selesai</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-extrabold text-slate-900 font-display">1,200+</div>
                                    <div class="text-xs text-slate-500 font-semibold mt-1 font-sans">Jastiper Aktif</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-extrabold text-slate-900 font-display">99.8%</div>
                                    <div class="text-xs text-slate-500 font-semibold mt-1">Tingkat Kepuasan</div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Hero Mockup (Customer Dashboard) -->
                        <div class="lg:col-span-5 relative flex justify-center lg:justify-end">
                            <!-- Decorative blur elements -->
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-4/5 h-4/5 bg-rose-400/20 blur-3xl rounded-full z-0"></div>
                            
                            <!-- Phone Mockup Container -->
                            <div class="relative z-10 w-full max-w-[320px] bg-slate-900 rounded-[2.5rem] p-2.5 shadow-2xl border-4 border-slate-800 rotate-2 hover:rotate-0 transition-transform duration-500 group">
                                <!-- Phone Notch/Island -->
                                <div class="absolute top-4 left-1/2 -translate-x-1/2 w-24 h-6 bg-black rounded-full z-20 flex justify-center items-center shadow-inner">
                                    <div class="w-1.5 h-1.5 bg-slate-700/50 rounded-full mr-2"></div>
                                    <div class="w-1.5 h-1.5 bg-slate-700/50 rounded-full"></div>
                                </div>
                                
                                <!-- Screen Content -->
                                <div class="bg-slate-50 w-full h-[620px] rounded-[2rem] overflow-hidden flex flex-col relative border border-slate-800">
                                    
                                    <!-- Header -->
                                    <div class="bg-white px-5 pt-11 pb-4 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] z-10">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider mb-0.5">Lokasi Pengantaran</p>
                                                <div class="flex items-center gap-1 cursor-pointer">
                                                    <span class="font-display font-bold text-sm text-slate-900">Malang Kota</span>
                                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                                </div>
                                            </div>
                                            <div class="w-9 h-9 rounded-full bg-slate-100 overflow-hidden border border-slate-200 shadow-sm cursor-pointer hover:border-rose-300 transition">
                                                <img src="https://ui-avatars.com/api/?name=Budi+U&background=FFE4E6&color=E11D48" alt="Profile" class="w-full h-full object-cover">
                                            </div>
                                        </div>
                                        <!-- Search -->
                                        <div class="mt-4 relative group/search">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-slate-400 group-focus-within/search:text-rose-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                            </div>
                                            <input type="text" placeholder="Mau titip apa hari ini?" class="w-full bg-slate-100 border border-transparent rounded-xl py-2.5 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:border-rose-300 focus:ring-2 focus:ring-rose-100 outline-none transition cursor-pointer">
                                        </div>
                                    </div>

                                    <!-- Scrollable Content -->
                                    <div class="flex-1 overflow-y-auto px-5 py-5 pb-24 no-scrollbar space-y-6">
                                        
                                        <!-- Promo Banner -->
                                        <div class="bg-gradient-to-r from-rose-600 to-rose-500 rounded-2xl p-4 text-white shadow-md shadow-rose-200 relative overflow-hidden cursor-pointer hover:scale-[1.02] transition-transform">
                                            <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-md"></div>
                                            <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-black/10 rounded-full blur-sm"></div>
                                            <h3 class="font-display font-bold text-lg mb-1 relative z-10 leading-tight">Diskon Jastip<br>Mulai 50%</h3>
                                            <p class="text-[10px] text-rose-100 mb-3 relative z-10 opacity-90">Khusus makanan area Suhat.</p>
                                            <button class="bg-white text-rose-600 text-xs font-bold py-1.5 px-3.5 rounded-full relative z-10 shadow-sm hover:bg-slate-50 transition">Klaim Sekarang</button>
                                        </div>

                                        <!-- Categories -->
                                        <div>
                                            <h3 class="font-display font-bold text-sm text-slate-900 mb-3">Kategori Titipan</h3>
                                            <div class="grid grid-cols-4 gap-3">
                                                <div class="flex flex-col items-center gap-1.5 cursor-pointer group/cat">
                                                    <div class="w-12 h-12 bg-orange-50 group-hover/cat:bg-orange-100 border border-orange-100 rounded-2xl flex items-center justify-center shadow-sm transition p-2">
                                                        <img src="{{ asset('images/services/beli-antar.png') }}" alt="Makanan" class="w-full h-full object-contain drop-shadow-sm group-hover/cat:scale-110 transition-transform">
                                                    </div>
                                                    <span class="text-[10px] font-medium text-slate-600 text-center leading-tight group-hover/cat:text-slate-900 transition">Makanan</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 cursor-pointer group/cat">
                                                    <div class="w-12 h-12 bg-blue-50 group-hover/cat:bg-blue-100 border border-blue-100 rounded-2xl flex items-center justify-center shadow-sm transition p-2">
                                                        <img src="{{ asset('images/services/ambil-antar.png') }}" alt="Fashion" class="w-full h-full object-contain drop-shadow-sm group-hover/cat:scale-110 transition-transform">
                                                    </div>
                                                    <span class="text-[10px] font-medium text-slate-600 text-center leading-tight group-hover/cat:text-slate-900 transition">Fashion</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 cursor-pointer group/cat">
                                                    <div class="w-12 h-12 bg-purple-50 group-hover/cat:bg-purple-100 border border-purple-100 rounded-2xl flex items-center justify-center shadow-sm transition p-2">
                                                        <img src="{{ asset('images/services/kirim-pihak-ketiga.png') }}" alt="Elektronik" class="w-full h-full object-contain drop-shadow-sm group-hover/cat:scale-110 transition-transform">
                                                    </div>
                                                    <span class="text-[10px] font-medium text-slate-600 text-center leading-tight group-hover/cat:text-slate-900 transition">Elektronik</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 cursor-pointer group/cat">
                                                    <div class="w-12 h-12 bg-emerald-50 group-hover/cat:bg-emerald-100 border border-emerald-100 rounded-2xl flex items-center justify-center shadow-sm transition p-2">
                                                        <img src="{{ asset('images/services/toko-kirim.png') }}" alt="Lainnya" class="w-full h-full object-contain drop-shadow-sm group-hover/cat:scale-110 transition-transform">
                                                    </div>
                                                    <span class="text-[10px] font-medium text-slate-600 text-center leading-tight group-hover/cat:text-slate-900 transition">Lainnya</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Active Orders -->
                                        <div>
                                            <div class="flex justify-between items-end mb-3">
                                                <h3 class="font-display font-bold text-sm text-slate-900">Titipan Aktif</h3>
                                                <a href="#" class="text-[10px] text-rose-600 font-bold hover:text-rose-700">Lihat Semua</a>
                                            </div>
                                            
                                            <!-- Order Card -->
                                            <div class="bg-white p-3.5 rounded-2xl shadow-[0_4px_15px_-4px_rgba(0,0,0,0.06)] border border-slate-100 cursor-pointer hover:border-rose-200 transition group/card">
                                                <div class="flex justify-between items-start mb-3">
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="w-9 h-9 rounded-full bg-slate-100 overflow-hidden border border-slate-200 shadow-sm">
                                                            <img src="https://ui-avatars.com/api/?name=Ahmad+J&background=E2E8F0&color=475569" alt="Jastiper" class="w-full h-full object-cover">
                                                        </div>
                                                        <div>
                                                            <p class="text-[11px] font-bold text-slate-900 group-hover/card:text-rose-600 transition">Ahmad J.</p>
                                                            <p class="text-[9px] text-slate-500 font-medium">Jastiper &bull; <span class="text-emerald-600 font-semibold">Sedang Mengantar</span></p>
                                                        </div>
                                                    </div>
                                                    <span class="bg-rose-50 text-rose-600 border border-rose-100 text-[9px] font-bold px-2 py-0.5 rounded-md">Berlangsung</span>
                                                </div>
                                                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 mb-4">
                                                    <p class="text-[11px] font-bold text-slate-800 mb-0.5 line-clamp-1">Kopi Kenangan & 2 Item lainnya</p>
                                                    <p class="text-[10px] text-slate-500">Total Est: <span class="font-bold text-slate-700">Rp 85.000</span></p>
                                                </div>
                                                
                                                <!-- Progress Bar Minimalist -->
                                                <div class="w-full bg-slate-100 rounded-full h-1.5 mb-2 relative overflow-hidden">
                                                    <div class="absolute bg-emerald-500 h-1.5 rounded-full left-0 top-0 transition-all duration-1000" style="width: 75%"></div>
                                                </div>
                                                <div class="flex justify-between text-[8px] text-slate-400 font-semibold px-1">
                                                    <span class="text-emerald-600">Diterima</span>
                                                    <span class="text-emerald-600 text-center">Beli</span>
                                                    <span class="text-emerald-600 text-center">Antar</span>
                                                    <span class="text-right">Selesai</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Bottom Navigation -->
                                    <div class="absolute bottom-0 left-0 w-full bg-white border-t border-slate-100 px-6 py-3.5 flex justify-between items-center z-20 pb-5 rounded-b-[2rem] shadow-[0_-4px_15px_-4px_rgba(0,0,0,0.05)]">
                                        <div class="flex flex-col items-center text-rose-600 gap-1 cursor-pointer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                            <span class="text-[9px] font-bold">Beranda</span>
                                        </div>
                                        <div class="flex flex-col items-center text-slate-400 hover:text-rose-500 gap-1 cursor-pointer transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                            <span class="text-[9px] font-medium">Pesanan</span>
                                        </div>
                                        <div class="relative flex flex-col items-center text-slate-400 hover:text-rose-500 gap-1 cursor-pointer transition">
                                            <div class="absolute -top-0.5 -right-1 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-white shadow-sm"></div>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                            <span class="text-[9px] font-medium">Pesan</span>
                                        </div>
                                        <div class="flex flex-col items-center text-slate-400 hover:text-rose-500 gap-1 cursor-pointer transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span class="text-[9px] font-medium">Profil</span>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- Cara Kerja Section -->
            <section id="cara-kerja" class="py-20 bg-slate-50 border-b border-slate-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <h2 class="font-display font-black text-3xl sm:text-4xl text-slate-900 tracking-tight">
                            Sistem Transparan & Alur Kerja yang <span class="text-rose-600">Praktis</span>
                        </h2>
                        <p class="text-slate-600 mt-3 text-base">
                            JastipKuy dirancang sesederhana mungkin baik bagi Anda yang ingin menitip barang (Customer) maupun Anda yang ingin menghasilkan uang dengan bepergian (Jastiper).
                        </p>

                        <!-- Tab Selector Minimalist (Professional design style) -->
                        <div class="flex justify-center mt-8 gap-2 bg-slate-200/60 p-1.5 rounded-sm inline-flex">
                            <button id="tab-cust-btn" onclick="switchTab('cust')" class="px-5 py-2 font-semibold text-sm rounded-sm transition bg-white text-slate-900 shadow-sm border border-slate-200">
                                Untuk Customer (Penitip)
                            </button>
                            <button id="tab-jastip-btn" onclick="switchTab('jastip')" class="px-5 py-2 font-semibold text-sm rounded-sm transition text-slate-600 hover:text-slate-950">
                                Untuk Jastiper (Kurir)
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content 1: Customer -->
                    <div id="tab-cust-content" class="grid grid-cols-1 md:grid-cols-4 gap-6 transition duration-300">
                        <!-- Step 1 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-rose-600/20 block mb-4">01</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Post Permintaan</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Tulis barang yang ingin Anda beli, detail toko/wilayah pembelian, dan perkiraan harganya ke dalam sistem.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-rose-600/20 block mb-4">02</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Pilih Tawaran Jastip</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Para Jastiper terdekat di area toko akan mengajukan penawaran komisi. Pilih penawaran yang paling cocok.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-rose-600/20 block mb-4">03</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Bayar Aman (Escrow)</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Lakukan pembayaran ke rekening bersama JastipKuy. Uang ditahan dengan aman sampai barang Anda dikonfirmasi sampai.
                                </p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-rose-600/20 block mb-4">04</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Terima & Nilai Barang</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Barang diantarkan langsung ke alamat Anda. Konfirmasi pesanan selesai dan berikan rating bintang untuk Jastiper.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content 2: Jastiper (Hidden by default) -->
                    <div id="tab-jastip-content" class="grid grid-cols-1 md:grid-cols-4 gap-6 transition duration-300 hidden">
                        <!-- Step 1 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-slate-900/10 block mb-4">01</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Check-in Wilayah</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Aktifkan status ketersediaan Anda dan pilih radius wilayah operasional Anda saat ini di dashboard Jastiper.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-slate-900/10 block mb-4">02</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Tawarkan Tarif Jasa</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Lihat daftar permintaan titipan di dekat Anda, lalu ajukan nominal komisi jasa titip yang Anda kehendaki.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-slate-900/10 block mb-4">03</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Beli & Antarkan Barang</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Setelah transaksi deal, belanjakan barang sesuai pesanan dan kirimkan langsung ke alamat customer tujuan.
                                </p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="bg-white border border-slate-200 p-6 rounded-sm shadow-sm relative flex flex-col justify-between">
                            <div>
                                <span class="font-display font-black text-4xl text-slate-900/10 block mb-4">04</span>
                                <h3 class="font-display font-bold text-lg text-slate-900 mb-2">Terima Komisi</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    Begitu customer melakukan konfirmasi penerimaan barang, dana komisi langsung diteruskan ke e-wallet Jastip Anda.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Layanan / Kategori Jastip Section -->
            <section id="layanan" class="py-20 bg-white border-b border-slate-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between mb-16">
                        <div class="max-w-2xl">
                            <h2 class="font-display font-black text-3xl sm:text-4xl text-slate-900 mt-2 tracking-tight">
                                Apa Saja Yang Bisa Anda Titipkan?
                            </h2>
                            <p class="text-slate-600 mt-3 text-base">
                                JastipKuy siap melayani berbagai kebutuhan belanja Anda. Pilih kategori yang sesuai, dan biarkan Jastiper terdekat kami mengurus sisanya.
                            </p>
                        </div>
                        <a href="#calculator" class="text-rose-600 font-bold hover:text-rose-700 flex items-center gap-1.5 text-sm mt-4 md:mt-0 transition">
                            Coba Kalkulator Jastip &rarr;
                        </a>
                    </div>

                    <!-- Categories Grid (Professional flat cards, small corner radius) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Card 1 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <img src="{{ asset('images/services/beli-antar.png') }}" alt="Kuliner & Makanan" class="w-14 h-14 object-contain mb-6">
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Kuliner & Makanan</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Ngidam kuliner khas dari luar area? Titip makanan hangat atau oleh-oleh kuliner basah dengan pengiriman cepat di hari yang sama.
                            </p>
                        </div>

                        <!-- Card 2 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <img src="{{ asset('images/services/ambil-antar.png') }}" alt="Fashion & Aksesoris" class="w-14 h-14 object-contain mb-6">
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Fashion & Aksesoris</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Titip produk fashion branded, sneakers edisi terbatas, baju thrift butik pilihan, atau aksesoris lokal dari mal maupun pameran besar.
                            </p>
                        </div>

                        <!-- Card 3 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <img src="{{ asset('images/services/kirim-pihak-ketiga.png') }}" alt="Gadget & Elektronik" class="w-14 h-14 object-contain mb-6">
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Gadget & Elektronik</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Cari sparepart komputer, aksesoris handphone unik, atau gadget kecil yang hanya tersedia di pusat grosir elektronik tertentu? Kami siap bantu.
                            </p>
                        </div>

                        <!-- Card 4 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <img src="{{ asset('images/services/multi-stop.png') }}" alt="Oleh-oleh Wilayah" class="w-14 h-14 object-contain mb-6">
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Oleh-oleh Wilayah</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Beli oleh-oleh kerajinan lokal, gantungan kunci, bakpia, keripik tempe khas daerah tertentu tanpa harus keluar biaya tiket perjalanan.
                            </p>
                        </div>

                        <!-- Card 5 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <img src="{{ asset('images/services/dokumen.png') }}" alt="Dokumen & Berkas" class="w-14 h-14 object-contain mb-6">
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Dokumen & Berkas</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Urusan titip penyerahan berkas fisik, dokumen kantor penting, atau pengambilan berkas resmi instansi di wilayah kota tujuan Anda secara privat.
                            </p>
                        </div>

                        <!-- Card 6 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <img src="{{ asset('images/services/toko-kirim.png') }}" alt="Belanja Bulanan & Harian" class="w-14 h-14 object-contain mb-6">
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Belanja Bulanan & Harian</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Sibuk bekerja? Titip belanja mingguan sayur segar, buah-buahan, daging, serta keperluan groceries di supermarket langganan terdekat Anda.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Mengapa Harus JastipKuy Section (Trust Badges) -->
            <section id="keunggulan" class="py-20 bg-slate-900 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl mx-auto text-center mb-16">
                        <h2 class="font-display font-black text-3xl sm:text-4xl mt-2 tracking-tight">
                            Solusi Jasa Titip Paling Profesional & Terpercaya
                        </h2>
                        <p class="text-slate-400 mt-3 text-base">
                            Kami tidak sekadar menghubungkan kurir dengan pembeli, melainkan membangun ekosistem jastip yang aman dari penipuan dengan perlindungan finansial penuh.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- Benefit 1 -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="bg-slate-800 p-2.5 rounded-sm border border-slate-700 text-rose-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>
                            </div>
                            <h3 class="font-display font-bold text-lg">Sistem Rekening Bersama</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                Pembayaran ditahan di rekening penampung JastipKuy (Escrow). Jastiper baru akan menerima dana setelah barang sampai dengan selamat di tangan Anda.
                            </p>
                        </div>

                        <!-- Benefit 2 -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="bg-slate-800 p-2.5 rounded-sm border border-slate-700 text-rose-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                            </div>
                            <h3 class="font-display font-bold text-lg">Jastiper Terverifikasi</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                Keamanan Anda prioritas kami. Semua Jastiper melewati validasi KTP elektronik, nomor HP aktif, dan rekam jejak ulasan yang terpantau ketat.
                            </p>
                        </div>

                        <!-- Benefit 3 -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="bg-slate-800 p-2.5 rounded-sm border border-slate-700 text-rose-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <h3 class="font-display font-bold text-lg">Biaya yang Fair & Jelas</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                Tidak ada markup tarif sembunyi-sembunyi. Rincian komisi belanja, harga asli barang, dan ongkir wilayah diperlihatkan secara transparan di awal transaksi.
                            </p>
                        </div>

                        <!-- Benefit 4 -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="bg-slate-800 p-2.5 rounded-sm border border-slate-700 text-rose-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                </div>
                            </div>
                            <h3 class="font-display font-bold text-lg">Fitur Chat Interaktif</h3>
                            <p class="text-sm text-slate-400 leading-relaxed">
                                Hubungi Jastiper secara langsung di dalam platform untuk berdiskusi soal ketersediaan barang di rak toko, negosiasi komisi, hingga update pengiriman.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testimoni / Ratings Section -->
            <section id="testimoni" class="py-20 bg-slate-50 border-b border-slate-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <h2 class="font-display font-black text-3xl sm:text-4xl text-slate-900 mt-2 tracking-tight">
                            Ulasan Nyata Komunitas JastipKuy
                        </h2>
                        <p class="text-slate-600 mt-3 text-base">
                            Berikut testimoni jujur dari para penitip belanjaan dan Jastiper kami di wilayah operasional Malang.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Testimonial 1 (Customer Budi Utomo) -->
                        <div class="bg-white border border-slate-200 p-8 rounded-sm shadow-sm space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center font-display font-black text-lg rounded-sm">
                                        BU
                                    </div>
                                    <div>
                                        <h4 class="font-display font-bold text-base text-slate-900">Budi Utomo</h4>
                                        <span class="text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-sm">Penitip (Customer)</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 bg-amber-50 border border-amber-100 px-2.5 py-1 rounded-sm text-xs font-bold text-amber-700 shadow-sm">
                                    <svg class="w-3 h-3 fill-current text-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span>5.0</span>
                                </div>
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed italic">
                                "Sangat terbantu titip makanan khas dari mal di Malang Kota langsung sampai sore hari dalam kondisi hangat. Escrow system membuat transaksi sangat tenang karena uang tidak akan ditransfer ke kurir sebelum barang kita terima secara lengkap."
                            </p>
                            <div class="text-xs text-slate-400 font-semibold">📍 Malang Kota &bull; Transaksi Selesai: 48 Kali</div>
                        </div>

                        <!-- Testimonial 2 (Jastiper Siti Aminah) -->
                        <div class="bg-white border border-slate-200 p-8 rounded-sm shadow-sm space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center font-display font-black text-lg rounded-sm">
                                        SA
                                    </div>
                                    <div>
                                        <h4 class="font-display font-bold text-base text-slate-900">Siti Aminah</h4>
                                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-sm">Jastiper Terverifikasi</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 bg-amber-50 border border-amber-100 px-2.5 py-1 rounded-sm text-xs font-bold text-amber-700 shadow-sm">
                                    <svg class="w-3 h-3 fill-current text-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span>4.9</span>
                                </div>
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed italic">
                                "Saya biasa belanja bulanan di mal daerah Jaksel, jadi sekarang sekalian buka jastip di aplikasi JastipKuy. Lumayan banget untuk penghasilan tambahan sambil bepergian. Proses konfirmasi pencairan komisi ke wallet sangat cepat setelah barang diterima pembeli."
                            </p>
                            <div class="text-xs text-slate-400 font-semibold">📍 Wilayah Operasional: Jaksel &bull; Rating Jastiper</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ruang Foto Dokumentasi Section -->
            <section class="py-20 bg-white border-b border-slate-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <h2 class="font-display font-black text-3xl sm:text-4xl text-slate-900 mt-2 tracking-tight">
                            Dokumentasi Aktivitas Jastip
                        </h2>
                        <p class="text-slate-600 mt-3 text-base">
                            Berikut adalah ruang dokumentasi untuk foto-foto aktivitas belanja, pengiriman, dan serah terima barang secara riil di lapangan.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Placeholder Foto 1 -->
                        <div class="bg-[#F8FAFC] border-2 border-dashed border-slate-300 p-8 rounded-sm text-center flex flex-col items-center justify-center min-h-[300px] hover:border-rose-500 transition group relative overflow-hidden">
                            @if(file_exists(public_path('images/belanjaan.png')))
                                <img src="{{ asset('images/belanjaan.png') }}" alt="Foto Barang Belanjaan" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-white border border-slate-200 rounded-sm flex items-center justify-center shadow-sm text-slate-400 group-hover:text-rose-600 transition mb-6">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <h4 class="font-display font-bold text-slate-800 mb-2">Foto Barang Belanjaan</h4>
                                    <p class="text-xs text-slate-500 max-w-[200px] leading-relaxed">
                                        [Ruang Foto: Struk pembelian & detail barang belanjaan di kasir. Rasio 4:3]
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-2 font-mono">Simpan foto di: public/images/belanjaan.png</p>
                                </div>
                            @endif
                        </div>

                        <!-- Placeholder Foto 2 -->
                        <div class="bg-[#F8FAFC] border-2 border-dashed border-slate-300 p-8 rounded-sm text-center flex flex-col items-center justify-center min-h-[300px] hover:border-rose-500 transition group relative overflow-hidden">
                            @if(file_exists(public_path('images/pengantaran.png')))
                                <img src="{{ asset('images/pengantaran.png') }}" alt="Foto Proses Pengantaran" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-white border border-slate-200 rounded-sm flex items-center justify-center shadow-sm text-slate-400 group-hover:text-rose-600 transition mb-6">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <h4 class="font-display font-bold text-slate-800 mb-2">Foto Proses Pengantaran</h4>
                                    <p class="text-xs text-slate-500 max-w-[200px] leading-relaxed">
                                        [Ruang Foto: Jastiper terverifikasi membawa belanjaan menggunakan motor. Rasio 4:3]
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-2 font-mono">Simpan foto di: public/images/pengantaran.png</p>
                                </div>
                            @endif
                        </div>

                        <!-- Placeholder Foto 3 -->
                        <div class="bg-[#F8FAFC] border-2 border-dashed border-slate-300 p-8 rounded-sm text-center flex flex-col items-center justify-center min-h-[300px] hover:border-rose-500 transition group relative overflow-hidden">
                            @if(file_exists(public_path('images/serah-terima.png')))
                                <img src="{{ asset('images/serah-terima.png') }}" alt="Foto Serah Terima Barang" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-white border border-slate-200 rounded-sm flex items-center justify-center shadow-sm text-slate-400 group-hover:text-rose-600 transition mb-6">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <h4 class="font-display font-bold text-slate-800 mb-2">Foto Serah Terima Barang</h4>
                                    <p class="text-xs text-slate-500 max-w-[200px] leading-relaxed">
                                        [Ruang Foto: Serah terima barang pesanan ke customer di depan rumah. Rasio 4:3]
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-2 font-mono">Simpan foto di: public/images/serah-terima.png</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA Banner Section (Professional design style) -->
            <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
                <!-- Subtle Background Image Overlay -->
                <div class="absolute inset-0 z-0 opacity-[0.08] pointer-events-none bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1607344645866-009c320c5ab8?auto=format&fit=crop&w=1600&q=80');"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6 relative z-10">
                    <h2 class="font-display font-black text-3xl sm:text-4xl lg:text-5xl leading-tight">
                        Siap Menitip Barang atau Ingin <br />Mulai Menghasilkan Uang Bersama Kami?
                    </h2>
                    <p class="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto">
                        Unduh aplikasi JastipKuy di ponsel Anda atau daftarkan akun sekarang melalui web untuk menikmati kemudahan jastip on-demand terbaik.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                        <a href="#calculator" class="w-full sm:w-auto inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-base px-8 py-4 rounded-sm shadow-sm transition">
                            Mulai Titip Pertama Anda
                        </a>
                        <a href="#" class="w-full sm:w-auto inline-flex items-center justify-center border-2 border-white hover:bg-white hover:text-slate-900 text-white font-bold text-base px-8 py-3.5 rounded-sm transition">
                            Gabung Menjadi Jastiper
                        </a>
                    </div>
                </div>
            </section>
        </main>
        @include('layouts.footer')

        <!-- JS Calculator logic -->
        <script>
            function updateWeightLabel(value) {
                document.getElementById('weight-label').innerText = value + ' kg';
            }

            function calculateEstimates() {
                // Get form inputs
                const wilayahSelect = document.getElementById('calc-wilayah');
                const kategoriSelect = document.getElementById('calc-kategori');
                const weightVal = parseFloat(document.getElementById('calc-weight').value);
                const priceVal = parseFloat(document.getElementById('calc-price').value || 0);

                // Base values logic
                let baseJastipFee = parseFloat(kategoriSelect.options[kategoriSelect.selectedIndex].getAttribute('data-fee') || 15000);
                
                // Add scale for item price (high value items incur slightly more trust fee)
                if (priceVal > 500000) {
                    baseJastipFee += Math.floor((priceVal - 500000) * 0.02); // 2% of value over 500k
                }

                // Delivery Fee Calculation (Base Fee + Price Per KM after 2km)
                const selectedOption = wilayahSelect.options[wilayahSelect.selectedIndex];
                const distance = parseFloat(selectedOption.getAttribute('data-radius') || 5);
                
                const baseFee = 5000;
                const baseKmIncluded = 2;
                const pricePerKm = 2000;

                let baseDeliveryFee = baseFee;
                if (distance > baseKmIncluded) {
                    baseDeliveryFee += (distance - baseKmIncluded) * pricePerKm;
                }

                // Weight adjustment based on weight categories (ringan, sedang, berat)
                let weightFee = 0;
                if (weightVal > 1 && weightVal <= 5) {
                    weightFee = 3000; // Sedang
                } else if (weightVal > 5) {
                    weightFee = 7000; // Berat
                }

                const deliveryFee = baseDeliveryFee + weightFee;
                const platformFee = 5000; // Escrow fee fixed
                const totalEstimated = baseJastipFee + deliveryFee + platformFee;

                // Update outputs in UI
                document.getElementById('out-jastip-fee').innerText = formatRupiah(baseJastipFee);
                document.getElementById('out-delivery-fee').innerText = formatRupiah(deliveryFee);
                document.getElementById('out-platform-fee').innerText = formatRupiah(platformFee);
                document.getElementById('out-total-fee').innerText = formatRupiah(totalEstimated);
            }

            function formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number).replace("Rp", "Rp");
            }

            function switchTab(role) {
                const custBtn = document.getElementById('tab-cust-btn');
                const jastipBtn = document.getElementById('tab-jastip-btn');
                const custContent = document.getElementById('tab-cust-content');
                const jastipContent = document.getElementById('tab-jastip-content');

                if (role === 'cust') {
                    // Active button styling
                    custBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200');
                    custBtn.classList.remove('text-slate-600', 'hover:text-slate-950');

                    jastipBtn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200');
                    jastipBtn.classList.add('text-slate-600', 'hover:text-slate-950');

                    // Show content
                    custContent.classList.remove('hidden');
                    jastipContent.classList.add('hidden');
                } else {
                    // Active button styling
                    jastipBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200');
                    jastipBtn.classList.remove('text-slate-600', 'hover:text-slate-950');

                    custBtn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200');
                    custBtn.classList.add('text-slate-600', 'hover:text-slate-950');

                    // Show content
                    jastipContent.classList.remove('hidden');
                    custContent.classList.add('hidden');
                }
            }

            // Run initial calculate on load
            window.addEventListener('DOMContentLoaded', (event) => {
                calculateEstimates();
            });
        </script>
    </body>
</html>
