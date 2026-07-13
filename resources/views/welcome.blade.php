<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

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
    </head>
    <body class="bg-[#F8FAFC] text-slate-800 antialiased selection:bg-rose-600 selection:text-white">

        <!-- Top Notification Bar -->
        <div class="bg-slate-900 text-white py-2 px-4 text-center text-xs font-medium tracking-wide">
            🚀 JastipKuy Wilayah Baru: Layanan Kini Tersedia di Area Jakarta Selatan & Sekitarnya!
        </div>

        <!-- Navbar Header -->
        <header id="navbar" class="sticky top-0 z-50 bg-white border-b border-slate-200/80 shadow-sm backdrop-blur-md bg-white/95">
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
                    <nav class="hidden md:flex items-center gap-8">
                        <a href="#hero" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Beranda</a>
                        <a href="#cara-kerja" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Cara Kerja</a>
                        <a href="#layanan" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Layanan</a>
                        <a href="#keunggulan" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Mengapa Kami</a>
                        <a href="#testimoni" class="text-sm font-semibold text-slate-600 hover:text-rose-600 transition">Testimoni</a>
                    </nav>

                    <!-- Authentication / Action Buttons -->
                    <div class="flex items-center gap-3">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" id="btn-dashboard" class="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" id="btn-login" class="inline-flex items-center justify-center border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2.5 rounded-sm transition">
                                    Masuk
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" id="btn-register" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                                        Daftar
                                    </a>
                                @endif
                            @endauth
                        @else
                            <!-- Fallback Links when Laravel Breeze is not active yet -->
                            <a href="#" id="btn-login-fallback" class="inline-flex items-center justify-center border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-semibold text-sm px-4 py-2.5 rounded-sm transition">
                                Masuk
                            </a>
                            <a href="#" id="btn-register-fallback" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm px-4 py-2.5 rounded-sm shadow-sm transition">
                                Daftar Sekarang
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main>
            <!-- Hero Section -->
            <section id="hero" class="relative overflow-hidden py-16 lg:py-24 bg-white border-b border-slate-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        
                        <!-- Left Hero Info Column -->
                        <div class="lg:col-span-7 flex flex-col items-start space-y-6 lg:pr-6">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-sm bg-rose-50 border border-rose-100 text-xs font-bold text-rose-600 uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                Jasa Titip On-Demand & Escrow Aman
                            </span>
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

                        <!-- Right Hero Form (Calculator Simulation) -->
                        <div id="calculator" class="lg:col-span-5">
                            <div class="bg-white border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] rounded-sm p-6 sm:p-8">
                                <div class="border-b border-slate-100 pb-4 mb-5">
                                    <h2 class="font-display font-extrabold text-xl text-slate-900 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        Simulasi Tarif Jastip
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-1">Cek perkiraan komisi Jastiper & ongkir wilayah Anda secara transparan.</p>
                                </div>

                                <form id="jastip-calculator" class="space-y-4" onsubmit="event.preventDefault(); calculateEstimates();">
                                    <!-- Input Wilayah -->
                                    <div>
                                        <label for="calc-wilayah" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Wilayah Pembelian</label>
                                        <select id="calc-wilayah" class="w-full bg-slate-50 border border-slate-300 focus:border-rose-600 focus:ring-1 focus:ring-rose-600 text-slate-800 text-sm font-medium p-3 rounded-sm outline-none transition" onchange="calculateEstimates()">
                                            <option value="1" data-radius="5">Jakarta Selatan (Area Terdekat)</option>
                                            <option value="2" data-radius="10">Depok & Sekitarnya (+ Rp5,000)</option>
                                            <option value="3" data-radius="15">Tangerang/Bekasi (+ Rp12,000)</option>
                                        </select>
                                    </div>

                                    <!-- Kategori Barang -->
                                    <div>
                                        <label for="calc-kategori" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori Barang</label>
                                        <select id="calc-kategori" class="w-full bg-slate-50 border border-slate-300 focus:border-rose-600 focus:ring-1 focus:ring-rose-600 text-slate-800 text-sm font-medium p-3 rounded-sm outline-none transition" onchange="calculateEstimates()">
                                            <option value="kuliner" data-fee="15000">Kuliner & Makanan (Fee Jastip Hemat)</option>
                                            <option value="fashion" data-fee="25000">Fashion & Pakaian (Premium Box/Bag)</option>
                                            <option value="elektronik" data-fee="50000">Gadget & Elektronik (Jaminan Safety)</option>
                                            <option value="lainnya" data-fee="20000">Lain-lain (Belanja Harian/Supermarket)</option>
                                        </select>
                                    </div>

                                    <!-- Range Slider Berat / Bobot -->
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label for="calc-weight" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Perkiraan Berat Barang</label>
                                            <span id="weight-label" class="text-sm font-bold text-rose-600">1 kg</span>
                                        </div>
                                        <input type="range" id="calc-weight" min="1" max="10" value="1" class="w-full h-2 bg-slate-200 rounded-sm appearance-none cursor-pointer accent-rose-600" oninput="updateWeightLabel(this.value); calculateEstimates();">
                                    </div>

                                    <!-- Perkiraan Harga Barang -->
                                    <div>
                                        <label for="calc-price" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Harga Barang Yang Dititip (Rp)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-3 text-slate-400 font-bold text-sm">Rp</span>
                                            <input type="number" id="calc-price" value="100000" min="10000" step="5000" class="w-full bg-slate-50 border border-slate-300 focus:border-rose-600 focus:ring-1 focus:ring-rose-600 text-slate-800 text-sm font-bold pl-9 pr-3 py-3 rounded-sm outline-none transition" oninput="calculateEstimates()">
                                        </div>
                                    </div>

                                    <!-- Hasil Perhitungan / Live Estimates -->
                                    <div class="bg-slate-50 border border-slate-200 p-4 space-y-2 rounded-sm text-sm">
                                        <div class="flex justify-between text-slate-600">
                                            <span>Komisi Jastiper:</span>
                                            <span id="out-jastip-fee" class="font-semibold text-slate-900">Rp15,000</span>
                                        </div>
                                        <div class="flex justify-between text-slate-600">
                                            <span>Biaya Pengantaran Wilayah:</span>
                                            <span id="out-delivery-fee" class="font-semibold text-slate-900">Rp8,000</span>
                                        </div>
                                        <div class="flex justify-between text-slate-600">
                                            <span>Biaya Proteksi Escrow (Aman):</span>
                                            <span id="out-platform-fee" class="font-semibold text-emerald-600">Rp5,000</span>
                                        </div>
                                        <div class="border-t border-slate-200 pt-2 flex justify-between font-bold text-base text-slate-900">
                                            <span>Total Estimasi Jasa:</span>
                                            <span id="out-total-fee" class="text-rose-600">Rp28,000</span>
                                        </div>
                                    </div>

                                    <!-- CTA Belanja -->
                                    <button type="button" id="btn-calc-submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-center py-3.5 rounded-sm transition tracking-wide text-sm">
                                        BUAT PERMINTAAN SEKARANG
                                    </button>
                                </form>
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
                            <span class="text-rose-600 font-extrabold text-xs uppercase tracking-wider">📦 Ragam Layanan Jastip</span>
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
                            <div class="w-10 h-10 bg-rose-50 border border-rose-100 rounded-sm flex items-center justify-center mb-6">
                                <div class="w-4 h-4 bg-rose-600 rounded-sm"></div>
                            </div>
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Kuliner & Makanan</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Ngidam kuliner khas dari luar area? Titip makanan hangat atau oleh-oleh kuliner basah dengan pengiriman cepat di hari yang sama.
                            </p>
                        </div>

                        <!-- Card 2 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <div class="w-10 h-10 bg-emerald-50 border border-emerald-100 rounded-sm flex items-center justify-center mb-6">
                                <div class="w-4 h-4 bg-emerald-600 rounded-sm"></div>
                            </div>
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Fashion & Aksesoris</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Titip produk fashion branded, sneakers edisi terbatas, baju thrift butik pilihan, atau aksesoris lokal dari mal maupun pameran besar.
                            </p>
                        </div>

                        <!-- Card 3 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <div class="w-10 h-10 bg-indigo-50 border border-indigo-100 rounded-sm flex items-center justify-center mb-6">
                                <div class="w-4 h-4 bg-indigo-600 rounded-sm"></div>
                            </div>
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Gadget & Elektronik</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Cari sparepart komputer, aksesoris handphone unik, atau gadget kecil yang hanya tersedia di pusat grosir elektronik tertentu? Kami siap bantu.
                            </p>
                        </div>

                        <!-- Card 4 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <div class="w-10 h-10 bg-amber-50 border border-amber-100 rounded-sm flex items-center justify-center mb-6">
                                <div class="w-4 h-4 bg-amber-600 rounded-sm"></div>
                            </div>
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Oleh-oleh Wilayah</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Beli oleh-oleh kerajinan lokal, gantungan kunci, bakpia, keripik tempe khas daerah tertentu tanpa harus keluar biaya tiket perjalanan.
                            </p>
                        </div>

                        <!-- Card 5 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <div class="w-10 h-10 bg-violet-50 border border-violet-100 rounded-sm flex items-center justify-center mb-6">
                                <div class="w-4 h-4 bg-violet-600 rounded-sm"></div>
                            </div>
                            <h3 class="font-display font-bold text-xl text-slate-900 mb-2">Dokumen & Berkas</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Urusan titip penyerahan berkas fisik, dokumen kantor penting, atau pengambilan berkas resmi instansi di wilayah kota tujuan Anda secara privat.
                            </p>
                        </div>

                        <!-- Card 6 -->
                        <div class="bg-white border border-slate-200 hover:border-slate-300 hover:shadow-sm p-8 rounded-sm transition">
                            <div class="w-10 h-10 bg-sky-50 border border-sky-100 rounded-sm flex items-center justify-center mb-6">
                                <div class="w-4 h-4 bg-sky-600 rounded-sm"></div>
                            </div>
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
                        <span class="text-rose-500 font-extrabold text-xs uppercase tracking-wider">🛡️ Keamanan & Transparansi</span>
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
                        <span class="text-rose-600 font-extrabold text-xs uppercase tracking-wider">⭐ Pengalaman Pengguna</span>
                        <h2 class="font-display font-black text-3xl sm:text-4xl text-slate-900 mt-2 tracking-tight">
                            Ulasan Nyata Komunitas JastipKuy
                        </h2>
                        <p class="text-slate-600 mt-3 text-base">
                            Berikut testimoni jujur dari para penitip belanjaan dan Jastiper kami di wilayah operasional Jakarta Selatan.
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
                                "Sangat terbantu titip batagor bandung dari mal di Jakarta Selatan langsung sampai sore hari dalam kondisi hangat. Escrow system membuat transaksi sangat tenang karena uang tidak akan ditransfer ke kurir sebelum barang kita terima secara lengkap."
                            </p>
                            <div class="text-xs text-slate-400 font-semibold">📍 Jakarta Selatan &bull; Transaksi Selesai: 48 Kali</div>
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
                        <span class="text-rose-600 font-extrabold text-xs uppercase tracking-wider">📸 Galeri Operasional</span>
                        <h2 class="font-display font-black text-3xl sm:text-4xl text-slate-900 mt-2 tracking-tight">
                            Dokumentasi Aktivitas Jastip
                        </h2>
                        <p class="text-slate-600 mt-3 text-base">
                            Berikut adalah ruang dokumentasi untuk foto-foto aktivitas belanja, pengiriman, dan serah terima barang secara riil di lapangan.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Placeholder Foto 1 -->
                        <div class="bg-[#F8FAFC] border-2 border-dashed border-slate-300 p-8 rounded-sm text-center flex flex-col items-center justify-center min-h-[300px] hover:border-rose-500 transition group">
                            <div class="w-16 h-16 bg-white border border-slate-200 rounded-sm flex items-center justify-center shadow-sm text-slate-400 group-hover:text-rose-600 transition mb-6">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h4 class="font-display font-bold text-slate-800 mb-2">Foto Barang Belanjaan</h4>
                            <p class="text-xs text-slate-500 max-w-[200px] leading-relaxed">
                                [Ruang Foto: Struk pembelian & detail barang belanjaan di kasir. Rasio 4:3]
                            </p>
                        </div>

                        <!-- Placeholder Foto 2 -->
                        <div class="bg-[#F8FAFC] border-2 border-dashed border-slate-300 p-8 rounded-sm text-center flex flex-col items-center justify-center min-h-[300px] hover:border-rose-500 transition group">
                            <div class="w-16 h-16 bg-white border border-slate-200 rounded-sm flex items-center justify-center shadow-sm text-slate-400 group-hover:text-rose-600 transition mb-6">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h4 class="font-display font-bold text-slate-800 mb-2">Foto Proses Pengantaran</h4>
                            <p class="text-xs text-slate-500 max-w-[200px] leading-relaxed">
                                [Ruang Foto: Jastiper terverifikasi membawa belanjaan menggunakan motor. Rasio 4:3]
                            </p>
                        </div>

                        <!-- Placeholder Foto 3 -->
                        <div class="bg-[#F8FAFC] border-2 border-dashed border-slate-300 p-8 rounded-sm text-center flex flex-col items-center justify-center min-h-[300px] hover:border-rose-500 transition group">
                            <div class="w-16 h-16 bg-white border border-slate-200 rounded-sm flex items-center justify-center shadow-sm text-slate-400 group-hover:text-rose-600 transition mb-6">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <h4 class="font-display font-bold text-slate-800 mb-2">Foto Serah Terima Barang</h4>
                            <p class="text-xs text-slate-500 max-w-[200px] leading-relaxed">
                                [Ruang Foto: Serah terima barang pesanan ke customer di depan rumah. Rasio 4:3]
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA Banner Section (Professional design style) -->
            <section class="py-16 bg-slate-900 text-white relative">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
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

        <!-- Footer -->
        <footer class="bg-slate-950 text-slate-400 pt-16 pb-8 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    
                    <!-- Col 1: Brand Info -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="relative w-8 h-8">
                                <div class="absolute inset-0 bg-rose-600 rounded-sm transform rotate-6"></div>
                                <div class="absolute inset-0 bg-slate-900 rounded-sm flex items-center justify-center border border-slate-800 shadow-sm">
                                    <span class="font-display font-black text-white text-[10px] tracking-tighter">JK</span>
                                </div>
                            </div>
                            <span class="font-display font-extrabold text-xl text-white tracking-tight">JastipKuy</span>
                        </div>
                        <p class="text-sm leading-relaxed">
                            Platform kurir & jasa titip (Jastip) on-demand terpercaya dengan keamanan pembayaran escrow pertama di Indonesia.
                        </p>
                        <div class="text-xs text-slate-500 font-semibold">
                            &copy; 2026 JastipKuy. Seluruh hak cipta dilindungi.
                        </div>
                    </div>

                    <!-- Col 2: Perusahaan -->
                    <div>
                        <h4 class="font-display font-bold text-white text-sm uppercase tracking-wider mb-4">Perusahaan</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition">Tentang Kami</a></li>
                            <li><a href="#" class="hover:text-white transition">Karir / Jastiper</a></li>
                            <li><a href="#" class="hover:text-white transition">Kontak Media</a></li>
                            <li><a href="#" class="hover:text-white transition">Hubungi CS</a></li>
                        </ul>
                    </div>

                    <!-- Col 3: Legalitas -->
                    <div>
                        <h4 class="font-display font-bold text-white text-sm uppercase tracking-wider mb-4">Legal & Aturan</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition">Kebijakan Privasi</a></li>
                            <li><a href="#" class="hover:text-white transition">Syarat & Ketentuan Penggunaan</a></li>
                            <li><a href="#" class="hover:text-white transition">Kebijakan Pembatalan</a></li>
                            <li><a href="#" class="hover:text-white transition">Ketentuan Escrow Keuangan</a></li>
                        </ul>
                    </div>

                    <!-- Col 4: Sosial Media -->
                    <div>
                        <h4 class="font-display font-bold text-white text-sm uppercase tracking-wider mb-4">Sosial Media</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-rose-500 transition">Instagram</a></li>
                            <li><a href="#" class="hover:text-rose-500 transition">Facebook</a></li>
                            <li><a href="#" class="hover:text-rose-500 transition">Twitter / X</a></li>
                            <li><a href="#" class="hover:text-rose-500 transition">LinkedIn</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Footer Bottom Meta Info (Laravel Version check) -->
                <div class="border-t border-slate-800 pt-8 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500 gap-4">
                    <div>
                        Sistem berjalan di: Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </div>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-slate-300 transition">Mitra Jastip</a>
                        <a href="#" class="hover:text-slate-300 transition">Lokasi Layanan</a>
                        <a href="#" class="hover:text-slate-300 transition">Peta Situs</a>
                    </div>
                </div>
            </div>
        </footer>

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

                // Delivery Fee Calculation based on Wilayah & Weight
                let baseDeliveryFee = 8000;
                const selectedWilayah = parseInt(wilayahSelect.value);
                if (selectedWilayah === 2) {
                    baseDeliveryFee += 5000; // Depok
                } else if (selectedWilayah === 3) {
                    baseDeliveryFee += 12000; // Tangerang/Bekasi
                }

                // Weight adjustment
                const deliveryFee = baseDeliveryFee + ((weightVal - 1) * 3000); // 3000 per additional kg
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
