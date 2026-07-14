@extends('layouts.support')

@section('title', 'Kebijakan Privasi - Perlindungan Data Pengguna')

@section('content')
<!-- Hero Header Section -->
<section class="bg-gradient-to-b from-slate-50 to-transparent py-14 border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl space-y-4">
            <h1 class="font-display font-black text-3xl sm:text-4xl text-slate-900 tracking-tight">Kebijakan Privasi</h1>
            <p class="text-slate-500 text-sm sm:text-base leading-relaxed">
                Terakhir Diperbarui: 14 Juli 2026. Kami berkomitmen untuk melindungi data pribadi seluruh pengguna platform JastipKuy. Dokumen ini menjelaskan pengumpulan, penggunaan, dan keamanan informasi Anda.
            </p>
        </div>
    </div>
</section>

<!-- Content Section -->
<section class="py-12 bg-white flex-grow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
            
            <!-- Sticky Sidebar Navigation (Table of Contents) -->
            <aside class="hidden lg:block lg:col-span-1">
                <div class="sticky top-28 space-y-2 border-l border-slate-200/80 pl-4 py-1">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Navigasi Bab</p>
                    <a href="#pengumpulan" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1 text-rose-600 font-semibold border-l-2 border-rose-500 -ml-[18px] pl-[16px]">1. Informasi Dikumpulkan</a>
                    <a href="#penggunaan" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">2. Penggunaan Data</a>
                    <a href="#keamanan" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">3. Proteksi & Enkripsi</a>
                    <a href="#pihak-ketiga" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">4. Berbagi Informasi</a>
                    <a href="#hak" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">5. Hak Pengguna</a>
                </div>
            </aside>

            <!-- Legal Documents Body -->
            <article class="col-span-1 lg:col-span-3 prose prose-slate max-w-none space-y-10 text-slate-600 leading-relaxed text-sm sm:text-base">
                
                <!-- Section 1 -->
                <section id="pengumpulan" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">1. Informasi yang Kami Kumpulkan</h2>
                    <p>
                        JastipKuy mengumpulkan data tertentu untuk memastikan kelancaran transaksi, keamanan escrow, dan operasional pengantaran berbasis wilayah:
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>Data Akun:</strong> Nama lengkap, alamat email, nomor telepon (WhatsApp), alamat pengiriman, dan kata sandi yang disandikan.</li>
                        <li><strong>Data Identitas Jastiper (KYC):</strong> Foto KTP asli serta foto selfie wajah guna verifikasi keabsahan profil kurir demi keselamatan pengguna lain.</li>
                        <li><strong>Data Lokasi (GPS):</strong> Data koordinat geografis wilayah untuk mempertemukan Customer dengan Jastiper terdekat di area operasional yang sama.</li>
                        <li><strong>Data Transaksi:</strong> Rincian barang belanjaan, nominal pembayaran escrow, foto struk belanja toko, serta riwayat chat dalam aplikasi.</li>
                    </ul>
                </section>

                <!-- Section 2 -->
                <section id="penggunaan" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">2. Cara Kami Menggunakan Data Anda</h2>
                    <p>
                        Informasi pribadi yang terkumpul digunakan semata-mata untuk meningkatkan pelayanan kami:
                    </p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Memproses pesanan jasa titip dan memfasilitasi pengantaran barang secara akurat ke alamat Customer.</li>
                        <li>Mengirimkan notifikasi pembayaran, status transaksi escrow, dan pengingat pengiriman melalui WhatsApp/SMS.</li>
                        <li>Melakukan verifikasi akun Jastiper demi meminimalkan risiko kejahatan keuangan atau penipuan belanja.</li>
                        <li>Membantu proses resolusi sengketa/komplain oleh tim Customer Service apabila terjadi masalah pada barang belanjaan.</li>
                    </ol>
                </section>

                <!-- Section 3 -->
                <section id="keamanan" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">3. Proteksi, Penyimpanan, dan Enkripsi</h2>
                    <p>
                        Kami menempatkan aspek keamanan data sebagai prioritas utama:
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Seluruh data sensitif (seperti foto KTP dan password) disimpan dengan enkripsi standar industri (AES-256 dan Bcrypt hashing).</li>
                        <li>Akses ke database kami batasi secara ketat dan hanya dapat dibuka oleh staf verifikator resmi yang terikat kontrak kerahasiaan.</li>
                        <li>Kami secara berkala memperbarui patch keamanan sistem server kami untuk mencegah upaya peretasan atau kebocoran data.</li>
                    </ul>
                </section>

                <!-- Section 4 -->
                <section id="pihak-ketiga" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">4. Berbagi Informasi dengan Pihak Ketiga</h2>
                    <p>
                        JastipKuy berkomitmen untuk <strong>tidak pernah menjual atau menyewakan</strong> data pribadi pengguna kepada agen periklanan atau pihak ketiga mana pun.
                    </p>
                    <p>
                        Data hanya dapat dibagikan kepada mitra pihak ketiga terpilih dalam kondisi terbatas:
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>Penyedia Gateway Pembayaran:</strong> Untuk melakukan proses verifikasi transfer escrow (VA, E-Wallet).</li>
                        <li><strong>Kepatuhan Hukum:</strong> Apabila diperintahkan oleh aparat penegak hukum Indonesia secara resmi berdasarkan peraturan perundang-undangan yang berlaku.</li>
                    </ul>
                </section>

                <!-- Section 5 -->
                <section id="hak" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">5. Hak Akses & Penghapusan Data Pengguna</h2>
                    <p>
                        Anda memiliki kendali penuh atas informasi pribadi Anda di platform kami:
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Anda berhak melihat, mengedit, dan melengkapi data profil Anda kapan saja langsung dari menu pengaturan Dashboard.</li>
                        <li>Anda dapat mengajukan permohonan penutupan akun dan penghapusan data secara permanen dengan menghubungi tim CS kami, selama Anda tidak memiliki transaksi aktif yang berjalan dalam sistem escrow.</li>
                    </ul>
                </section>

            </article>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    // Simple ScrollSpy implementation for sticky navigation highlight
    window.addEventListener('DOMContentLoaded', () => {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                const id = entry.target.getAttribute('id');
                if (entry.intersectionRatio > 0) {
                    document.querySelectorAll('.toc-link').forEach(link => {
                        link.classList.remove('text-rose-600', 'font-semibold', 'border-l-2', 'border-rose-500', '-ml-[18px]', 'pl-[16px]');
                    });
                    const activeLink = document.querySelector(`.toc-link[href="#${id}"]`);
                    if (activeLink) {
                        activeLink.classList.add('text-rose-600', 'font-semibold', 'border-l-2', 'border-rose-500', '-ml-[18px]', 'pl-[16px]');
                    }
                }
            });
        }, {
            rootMargin: '-15% 0px -75% 0px'
        });

        // Track all sections
        document.querySelectorAll('article section').forEach(section => {
            observer.observe(section);
        });
    });
</script>
@endsection
