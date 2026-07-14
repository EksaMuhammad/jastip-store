@extends('layouts.support')

@section('title', 'Tanya Jawab (FAQ) - Layanan Jasa Titip On-Demand')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-b from-rose-50/50 via-white to-transparent py-16 border-b border-slate-100">
    <div class="max-w-4xl mx-auto px-4 text-center space-y-4">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-rose-50 border border-rose-100 text-rose-600 text-xs font-semibold">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Pusat Bantuan
        </div>
        <h1 class="font-display font-black text-3xl sm:text-4xl text-slate-900 tracking-tight">Ada yang Bisa Kami Bantu?</h1>
        <p class="text-slate-500 text-sm sm:text-base max-w-lg mx-auto">
            Temukan jawaban cepat atas pertanyaan umum seputar penggunaan platform JastipKuy untuk Customer maupun Jastiper.
        </p>

        <!-- Search Bar -->
        <div class="max-w-xl mx-auto pt-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="faq-search" oninput="filterFAQ()" placeholder="Cari pertanyaan atau kata kunci (misal: escrow, KTP, tarif)..." class="w-full pl-11 pr-4 py-3.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition shadow-sm">
            </div>
        </div>
    </div>
</section>

<!-- Main FAQ Section -->
<section class="py-12 bg-white flex-grow">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Toggle Tabs -->
        <div class="flex justify-center mb-10">
            <div class="inline-flex p-1 bg-slate-100 rounded-lg">
                <button onclick="switchFaqTab('customer')" id="tab-btn-customer" class="px-6 py-2.5 rounded-md text-sm font-bold transition duration-200 bg-white text-slate-900 shadow-sm border border-slate-200/50">
                    Sebagai Customer
                </button>
                <button onclick="switchFaqTab('jastiper')" id="tab-btn-jastiper" class="px-6 py-2.5 rounded-md text-sm font-bold transition duration-200 text-slate-600 hover:text-slate-900">
                    Sebagai Jastiper
                </button>
            </div>
        </div>

        <!-- FAQ Content Wrapper -->
        <div id="faq-content">
            
            <!-- Category: Customer -->
            <div id="faq-cat-customer" class="space-y-4">
                <!-- Item 1 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Bagaimana cara melakukan pemesanan (titip barang) di JastipKuy?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Anda cukup membuka halaman utama JastipKuy, lalu gunakan kalkulator estimasi untuk menghitung perkiraan biaya. Setelah itu, buat pesanan dengan mengisi detail barang, toko asal, dan alamat pengantaran. Pembayaran Anda akan disimpan dengan aman di sistem Escrow (Rekening Bersama) JastipKuy sebelum diteruskan ke Jastiper setelah transaksi selesai.
                        </div>
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Apakah pembayaran di JastipKuy aman?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Sangat aman. JastipKuy menggunakan <strong>Escrow System (Rekening Bersama)</strong>. Uang yang Anda bayarkan akan ditahan oleh platform kami dan baru akan dicairkan ke Jastiper setelah Anda mengonfirmasi bahwa barang telah diterima dengan baik, aman, dan sesuai pesanan.
                        </div>
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Bagaimana jika barang yang saya titip tidak tersedia (habis) di toko?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Jika Jastiper mendatangi toko dan melaporkan bahwa barang belanjaan kosong/habis, dana pembelian barang beserta ongkos kirim akan dikembalikan 100% tanpa potongan ke saldo akun JastipKuy Anda. Saldo tersebut bisa Anda tarik kembali ke rekening bank pribadi kapan saja secara instan.
                        </div>
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Berapa tarif jasa titip dan pengantaran barang?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Tarif kami sangat transparan:
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li><strong>Biaya Jasa Jastip:</strong> Mulai dari Rp15.000 per barang (bervariasi tergantung kategori belanja seperti Makanan, Fashion, Elektronik).</li>
                                <li><strong>Biaya Pengantaran:</strong> Mulai dari Rp8.000 untuk 1 kg pertama, dengan penambahan Rp3.000 untuk setiap tambahan kilogram berikutnya.</li>
                                <li><strong>Biaya Platform:</strong> Biaya admin escrow tetap Rp5.000 per transaksi belanja.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category: Jastiper -->
            <div id="faq-cat-jastiper" class="space-y-4 hidden">
                <!-- Item 1 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Bagaimana cara mendaftar menjadi Jastiper Terverifikasi?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Untuk menjadi Jastiper resmi, daftarkan akun Anda lalu buka tab Jastiper di Dashboard. Unggah foto KTP asli dan lakukan swafoto (selfie) verifikasi wajah. Verifikasi dokumen dilakukan secara ketat oleh tim kami maksimal 1x24 jam untuk menjaga keamanan ekosistem transaksi.
                        </div>
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Kapan saldo hasil jastip saya bisa dicairkan?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Saldo belanja dan komisi jasa jastip Anda akan langsung dilepaskan dari rekening escrow ke dompet digital Anda seketika setelah Customer mengonfirmasi penerimaan barang, atau otomatis 24 jam setelah kurir menyatakan paket selesai dikirim (apabila tidak ada komplain).
                        </div>
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Bagaimana jika harga barang asli di toko berbeda dengan estimasi?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Jastiper dapat mengirimkan pengajuan penyesuaian nominal (Invoice Adjustment) melalui chat yang disertai foto struk/nota pembayaran asli toko. Customer akan menerima notifikasi persetujuan selisih harga tersebut sebelum pesanan dikirimkan.
                        </div>
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="faq-item group border border-slate-200/80 rounded-lg bg-white overflow-hidden transition-all duration-200 hover:border-rose-200 shadow-sm">
                    <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between p-5 text-left text-slate-900 focus:outline-none">
                        <span class="font-display font-bold text-sm sm:text-base leading-snug group-hover:text-rose-600 transition">Apakah ada batasan wilayah operasional untuk Jastiper?</span>
                        <span class="ml-4 flex-shrink-0 p-1 rounded-full bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </button>
                    <div class="faq-answer max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out">
                        <div class="p-5 pt-0 text-slate-600 text-sm leading-relaxed border-t border-slate-50">
                            Ya. JastipKuy memprioritaskan efisiensi pengantaran wilayah lokal (Malang Kota, Kabupaten Malang, dll). Jastiper disarankan hanya menerima pesanan belanja yang tokonya dan alamat kirimnya berada dalam radius operasional Anda agar menghemat waktu dan bahan bakar.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Empty Search State -->
            <div id="faq-empty-state" class="hidden text-center py-12 space-y-3">
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h4 class="font-display font-bold text-slate-700">Pencarian Tidak Ditemukan</h4>
                <p class="text-xs text-slate-500 max-w-xs mx-auto leading-relaxed">
                    Kami tidak menemukan hasil untuk kata kunci tersebut. Coba gunakan istilah lain atau cari kategori yang berbeda.
                </p>
            </div>
            
        </div>
    </div>
</section>

<!-- Support Footer Banner -->
<section class="py-12 bg-slate-50 border-t border-slate-100 text-center">
    <div class="max-w-lg mx-auto px-4 space-y-4">
        <h4 class="font-display font-bold text-lg text-slate-900">Pertanyaan Anda Belum Terjawab?</h4>
        <p class="text-sm text-slate-500">
            Hubungi tim support atau CS JastipKuy untuk mendapatkan penanganan lebih lanjut mengenai transaksi Anda.
        </p>
        <div class="pt-2">
            <a href="{{ route('about') }}#contact" class="inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm px-6 py-3 rounded-md shadow-sm transition">
                Hubungi Support
            </a>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    let activeTab = 'customer';

    function switchFaqTab(tab) {
        activeTab = tab;
        const btnCust = document.getElementById('tab-btn-customer');
        const btnJastip = document.getElementById('tab-btn-jastiper');
        const catCust = document.getElementById('faq-cat-customer');
        const catJastip = document.getElementById('faq-cat-jastiper');

        // Reset search input on tab change
        document.getElementById('faq-search').value = '';

        if (tab === 'customer') {
            btnCust.classList.add('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200/50');
            btnCust.classList.remove('text-slate-600', 'hover:text-slate-900');
            btnJastip.classList.remove('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200/50');
            btnJastip.classList.add('text-slate-600', 'hover:text-slate-900');
            
            catCust.classList.remove('hidden');
            catJastip.classList.add('hidden');
        } else {
            btnJastip.classList.add('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200/50');
            btnJastip.classList.remove('text-slate-600', 'hover:text-slate-900');
            btnCust.classList.remove('bg-white', 'text-slate-900', 'shadow-sm', 'border', 'border-slate-200/50');
            btnCust.classList.add('text-slate-600', 'hover:text-slate-900');
            
            catJastip.classList.remove('hidden');
            catCust.classList.add('hidden');
        }
        
        // Hide empty state and show all items in the activated category
        document.getElementById('faq-empty-state').classList.add('hidden');
        const items = document.querySelectorAll(`#faq-cat-${activeTab} .faq-item`);
        items.forEach(item => item.classList.remove('hidden'));
        
        // Close all accordions
        closeAllAccordions();
    }

    function toggleFaq(button) {
        const item = button.closest('.faq-item');
        const answer = item.querySelector('.faq-answer');
        const svg = button.querySelector('svg');
        const isOpen = answer.style.maxHeight && answer.style.maxHeight !== '0px';

        // Close all other accordions first
        closeAllAccordions();

        if (!isOpen) {
            answer.style.maxHeight = answer.scrollHeight + "px";
            answer.style.opacity = "1";
            svg.classList.add('rotate-180');
            item.classList.add('border-rose-300', 'ring-1', 'ring-rose-500/5');
        }
    }

    function closeAllAccordions() {
        const items = document.querySelectorAll('.faq-item');
        items.forEach(item => {
            const answer = item.querySelector('.faq-answer');
            const svg = item.querySelector('button svg');
            answer.style.maxHeight = "0px";
            answer.style.opacity = "0";
            svg.classList.remove('rotate-180');
            item.classList.remove('border-rose-300', 'ring-1', 'ring-rose-500/5');
        });
    }

    function filterFAQ() {
        const query = document.getElementById('faq-search').value.toLowerCase();
        const activeContainerId = `faq-cat-${activeTab}`;
        const items = document.querySelectorAll(`#${activeContainerId} .faq-item`);
        let matchCount = 0;

        items.forEach(item => {
            const textContent = item.textContent.toLowerCase();
            if (textContent.includes(query)) {
                item.classList.remove('hidden');
                matchCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        const emptyState = document.getElementById('faq-empty-state');
        if (matchCount === 0) {
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
        }
    }
</script>
@endsection
