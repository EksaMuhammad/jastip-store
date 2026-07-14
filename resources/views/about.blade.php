@extends('layouts.support')

@section('title', 'Tentang Kami & Kontak Hubung')

@section('content')
<!-- Hero Section -->
<section class="relative bg-slate-900 text-white py-20 overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-rose-600/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-slate-800/20 rounded-full blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center space-y-6">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-800 border border-slate-700 text-rose-400 text-xs font-semibold">
            🤝 Lebih Dekat Dengan Kami
        </div>
        <h1 class="font-display font-black text-4xl sm:text-5xl lg:text-6xl tracking-tight leading-none">
            Menghubungkan Wilayah,<br />Memberdayakan <span class="text-rose-500">Jastiper Lokal</span>
        </h1>
        <p class="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
            JastipKuy adalah platform jasa titip on-demand pertama di Indonesia yang menggabungkan kemudahan belanja wilayah mikro dengan keamanan sistem pembayaran escrow.
        </p>
    </div>
</section>

<!-- Company Info & Mission -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            
            <!-- Story & Vision -->
            <div class="space-y-6">
                <h2 class="font-display font-black text-3xl text-slate-900 leading-tight">
                    Misi Kami: Mempermudah Pemenuhan Kebutuhan Belanja Wilayah Anda
                </h2>
                <p class="text-slate-600 leading-relaxed text-base">
                    JastipKuy lahir dari sebuah masalah sederhana: banyaknya kebutuhan belanja harian, kuliner legendaris, atau produk khusus di suatu wilayah yang sulit dijangkau karena keterbatasan waktu atau jarak pengiriman ekspedisi biasa yang mahal dan lambat.
                </p>
                <p class="text-slate-600 leading-relaxed text-base">
                    Kami hadir sebagai jembatan yang menghubungkan Customer dengan ratusan Jastiper lokal terverifikasi di wilayah sekitar. Dengan sistem operasional berbasis wilayah mikro, barang belanjaan Anda bisa dibeli dan diantarkan langsung ke rumah Anda dalam hitungan jam secara lebih efisien dan ekonomis.
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Stat 1 -->
                <div class="bg-slate-50 p-8 rounded-xl border border-slate-100 space-y-2 hover:border-rose-100 hover:bg-rose-50/20 transition duration-300">
                    <span class="font-display font-black text-4xl sm:text-5xl text-rose-600 block">12k+</span>
                    <span class="font-bold text-slate-800 text-sm block">Transaksi Selesai</span>
                    <span class="text-slate-400 text-xs block">Pengantaran aman terkonfirmasi</span>
                </div>
                <!-- Stat 2 -->
                <div class="bg-slate-50 p-8 rounded-xl border border-slate-100 space-y-2 hover:border-rose-100 hover:bg-rose-50/20 transition duration-300">
                    <span class="font-display font-black text-4xl sm:text-5xl text-slate-900 block">450+</span>
                    <span class="font-bold text-slate-800 text-sm block">Jastiper Terverifikasi</span>
                    <span class="text-slate-400 text-xs block">Verifikasi identitas KTP ketat</span>
                </div>
                <!-- Stat 3 -->
                <div class="bg-slate-50 p-8 rounded-xl border border-slate-100 space-y-2 hover:border-rose-100 hover:bg-rose-50/20 transition duration-300">
                    <span class="font-display font-black text-4xl sm:text-5xl text-slate-900 block">99.8%</span>
                    <span class="font-bold text-slate-800 text-sm block">Success Rate</span>
                    <span class="text-slate-400 text-xs block">Dispute terselesaikan damai</span>
                </div>
                <!-- Stat 4 -->
                <div class="bg-slate-50 p-8 rounded-xl border border-slate-100 space-y-2 hover:border-rose-100 hover:bg-rose-50/20 transition duration-300">
                    <span class="font-display font-black text-4xl sm:text-5xl text-rose-600 block">&lt;3 Jam</span>
                    <span class="font-bold text-slate-800 text-sm block">Rata-rata Pengiriman</span>
                    <span class="text-slate-400 text-xs block">Setelah barang dibelikan di toko</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section id="contact" class="py-20 bg-slate-50 border-t border-slate-100 scroll-mt-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
            
            <!-- Contact Information Info -->
            <div class="lg:col-span-5 space-y-8">
                <div class="space-y-4">
                    <h2 class="font-display font-black text-3xl text-slate-900 leading-tight">Hubungi Tim Kami</h2>
                    <p class="text-slate-500 text-sm sm:text-base leading-relaxed">
                        Punya pertanyaan mengenai kerja sama kemitraan wilayah, keluhan transaksi escrow, atau ingin memberikan masukan? Kami siap melayani Anda sepenuh hati.
                    </p>
                </div>

                <!-- Info Cards -->
                <div class="space-y-4 pt-2">
                    <div class="flex gap-4 p-4 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <div class="w-10 h-10 bg-rose-50 text-rose-600 flex items-center justify-center rounded-lg flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Email Hubungan</span>
                            <a href="mailto:support@jastipkuy.com" class="text-sm font-semibold text-slate-900 hover:text-rose-600 transition block mt-0.5">support@jastipkuy.com</a>
                        </div>
                    </div>

                    <div class="flex gap-4 p-4 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <div class="w-10 h-10 bg-rose-50 text-rose-600 flex items-center justify-center rounded-lg flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">WhatsApp Hotline CS</span>
                            <a href="https://wa.me/6281234567890" target="_blank" class="text-sm font-semibold text-slate-900 hover:text-rose-600 transition block mt-0.5">+62 812-3456-7890</a>
                        </div>
                    </div>

                    <div class="flex gap-4 p-4 bg-white rounded-lg border border-slate-100 shadow-sm">
                        <div class="w-10 h-10 bg-rose-50 text-rose-600 flex items-center justify-center rounded-lg flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Kantor Operasional</span>
                            <span class="text-sm font-semibold text-slate-900 block mt-0.5">Sudirman Central Business District (SCBD), Tower A, Jakarta Selatan, 12190</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interactive Contact Form -->
            <div class="lg:col-span-7 bg-white p-8 rounded-2xl border border-slate-200/80 shadow-md relative overflow-hidden">
                
                <!-- Form State -->
                <form id="contact-form" onsubmit="submitContactForm(event)" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Nama Lengkap -->
                        <div class="space-y-2">
                            <label for="contact-name" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Nama Lengkap</label>
                            <input type="text" id="contact-name" name="name" required placeholder="Masukkan nama Anda..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white rounded-lg text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition">
                        </div>
                        <!-- Email -->
                        <div class="space-y-2">
                            <label for="contact-email" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Alamat Email</label>
                            <input type="email" id="contact-email" name="email" required placeholder="name@domain.com" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white rounded-lg text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition">
                        </div>
                    </div>

                    <!-- Subject Dropdown -->
                    <div class="space-y-2">
                        <label for="contact-subject" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Perihal Hubung</label>
                        <select id="contact-subject" name="subject" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition">
                            <option value="" disabled selected>Pilih alasan menghubungi kami...</option>
                            <option value="dispute">Komplain / Dispute Transaksi Escrow</option>
                            <option value="partnership">Kemitraan Wilayah Operasional</option>
                            <option value="jastiper">Pertanyaan Pendaftaran Jastiper</option>
                            <option value="other">Lainnya / Masukan Umum</option>
                        </select>
                    </div>

                    <!-- Message Textarea -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label for="contact-message" class="text-xs font-bold text-slate-700 uppercase tracking-wider block">Isi Pesan Anda</label>
                            <span id="char-count" class="text-[10px] text-slate-400 font-mono">0 / 500 karakter</span>
                        </div>
                        <textarea id="contact-message" name="message" required rows="5" maxlength="500" oninput="updateCharCount(this)" placeholder="Tuliskan pesan Anda secara lengkap..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white rounded-lg text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition resize-none"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" id="submit-btn" class="w-full inline-flex items-center justify-center bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm px-6 py-4 rounded-lg shadow-sm transition hover:shadow-md active:scale-[0.99] duration-150">
                            <span id="btn-text">Kirim Pesan CS</span>
                            <svg id="btn-spinner" class="w-4 h-4 ml-2 animate-spin hidden" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Success Feedback Modal (Hidden by default) -->
                <div id="success-feedback" class="absolute inset-0 bg-white p-8 flex flex-col items-center justify-center text-center space-y-6 transition-all duration-300 opacity-0 pointer-events-none">
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center shadow-inner border border-emerald-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="space-y-2">
                        <h4 class="font-display font-black text-xl text-slate-900">Pesan Berhasil Terkirim!</h4>
                        <p class="text-sm text-slate-500 max-w-sm mx-auto">
                            Terima kasih, pesan Anda telah masuk ke antrean customer support kami. Agen kami akan segera menghubungi Anda kembali melalui WhatsApp atau Email dalam 1x24 jam.
                        </p>
                    </div>
                    <div>
                        <button onclick="resetContactForm()" class="inline-flex items-center justify-center border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 font-semibold text-xs px-5 py-2.5 rounded-lg transition">
                            Kirim Pesan Lainnya
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function updateCharCount(textarea) {
        const currentLength = textarea.value.length;
        document.getElementById('char-count').innerText = currentLength + ' / 500 karakter';
    }

    function submitContactForm(event) {
        event.preventDefault();

        // Perform simple input validations
        const name = document.getElementById('contact-name').value.trim();
        const email = document.getElementById('contact-email').value.trim();
        const subject = document.getElementById('contact-subject').value;
        const message = document.getElementById('contact-message').value.trim();

        if (!name || !email || !subject || !message) {
            alert('Semua bidang formulir wajib diisi!');
            return;
        }

        // Show loading spinner
        const submitBtn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        const btnSpinner = document.getElementById('btn-spinner');

        submitBtn.disabled = true;
        btnText.innerText = 'Mengirim Pesan...';
        btnSpinner.classList.remove('hidden');

        // Simulate API call processing for 1.5 seconds
        setTimeout(() => {
            // Hide spinner and enable btn
            submitBtn.disabled = false;
            btnText.innerText = 'Kirim Pesan CS';
            btnSpinner.classList.add('hidden');

            // Toggle visual success layout
            const successCard = document.getElementById('success-feedback');
            successCard.classList.remove('opacity-0', 'pointer-events-none');
            successCard.classList.add('opacity-100');
        }, 1500);
    }

    function resetContactForm() {
        // Reset form inputs
        document.getElementById('contact-form').reset();
        document.getElementById('char-count').innerText = '0 / 500 karakter';

        // Hide success card
        const successCard = document.getElementById('success-feedback');
        successCard.classList.remove('opacity-100');
        successCard.classList.add('opacity-0', 'pointer-events-none');
    }
</script>
@endsection
