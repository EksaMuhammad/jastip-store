@extends('layouts.support')

@section('title', 'Syarat & Ketentuan Penggunaan Layanan')

@section('content')
<!-- Hero Header Section -->
<section class="bg-gradient-to-b from-slate-50 to-transparent py-14 border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl space-y-4">
            <h1 class="font-display font-black text-3xl sm:text-4xl text-slate-900 tracking-tight">Syarat & Ketentuan Penggunaan</h1>
            <p class="text-slate-500 text-sm sm:text-base leading-relaxed">
                Terakhir Diperbarui: 14 Juli 2026. Harap baca dokumen legalitas ini secara saksama sebelum mulai menggunakan seluruh fitur di platform JastipKuy.
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
                    <a href="#definisi" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1 text-rose-600 font-semibold border-l-2 border-rose-500 -ml-[18px] pl-[16px]">1. Ketentuan Umum</a>
                    <a href="#akun" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">2. Akun & Verifikasi</a>
                    <a href="#escrow" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">3. Sistem Escrow</a>
                    <a href="#barang" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">4. Aturan & Batasan</a>
                    <a href="#pembatalan" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">5. Pembatalan & Refund</a>
                    <a href="#sanksi" class="toc-link block text-sm font-medium text-slate-500 hover:text-rose-600 transition py-1">6. Sanksi & Perselisihan</a>
                </div>
            </aside>

            <!-- Legal Documents Body -->
            <article class="col-span-1 lg:col-span-3 prose prose-slate max-w-none space-y-10 text-slate-600 leading-relaxed text-sm sm:text-base">
                
                <!-- Section 1 -->
                <section id="definisi" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">1. Ketentuan Umum & Definisi</h2>
                    <p>
                        Selamat datang di platform JastipKuy. Dengan mengakses, mengunduh aplikasi, atau melakukan transaksi di dalam platform kami, Anda secara otomatis menyatakan setuju atas seluruh syarat, ketentuan, serta kebijakan privasi yang berlaku di JastipKuy.
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>Platform:</strong> Aplikasi seluler dan situs web JastipKuy yang menyediakan layanan kurir titip belanja on-demand.</li>
                        <li><strong>Customer (Penitip):</strong> Pengguna terdaftar yang memesan jasa titip beli barang melalui platform JastipKuy.</li>
                        <li><strong>Jastiper (Kurir Jastip):</strong> Pengguna terdaftar yang melakukan verifikasi data dan menawarkan jasa untuk membelikan serta mengantarkan barang pesanan Customer.</li>
                    </ul>
                </section>

                <!-- Section 2 -->
                <section id="akun" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">2. Pendaftaran Akun & Keamanan Data</h2>
                    <p>
                        Untuk menggunakan fitur penuh transaksi belanja, setiap pengguna wajib membuat akun dengan data yang valid.
                    </p>
                    <p>
                        Khusus bagi pengguna yang ingin mendaftar sebagai **Jastiper**, platform mewajibkan proses verifikasi identitas resmi (KYC) berupa unggah kartu identitas **KTP** dan swafoto. Data KTP tersebut disimpan secara terenkripsi dan hanya digunakan untuk validasi legalitas transaksi guna meminimalkan penyalahgunaan platform.
                    </p>
                    <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-md text-amber-900 text-sm">
                        <span class="font-bold">PENTING:</span> Setiap akun hanya dapat digunakan oleh pemilik data yang sah. JastipKuy berhak menonaktifkan akun yang terbukti dipindahtangankan atau menggunakan identitas palsu.
                    </div>
                </section>

                <!-- Section 3 -->
                <section id="escrow" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">3. Sistem Escrow (Rekening Bersama)</h2>
                    <p>
                        Demi melindungi transaksi finansial antara Customer dan Jastiper, JastipKuy menerapkan sistem pembayaran escrow otomatis:
                    </p>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Customer melakukan transfer total dana belanja + ongkos kirim + biaya platform ke Rekening Escrow resmi JastipKuy.</li>
                        <li>Setelah dana terkonfirmasi masuk ke escrow, Jastiper akan mendapatkan notifikasi untuk mulai membelikan barang di toko/pusat perbelanjaan.</li>
                        <li>Jastiper mengantarkan barang pesanan.</li>
                        <li>Setelah Customer menerima barang dan mengonfirmasi "Pesanan Selesai" di aplikasi, dana dalam escrow akan dilepaskan secara utuh ke saldo dompet digital Jastiper.</li>
                    </ol>
                </section>

                <!-- Section 4 -->
                <section id="barang" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">4. Aturan Belanja & Batasan Barang</h2>
                    <p>
                        JastipKuy tidak mengizinkan pemesanan barang yang melanggar hukum atau membahayakan keselamatan pengantaran.
                    </p>
                    <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-md text-rose-950 text-sm">
                        <span class="font-bold">Barang yang DILARANG keras:</span>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>Narkotika, psikotropika, obat-obatan keras terlarang.</li>
                            <li>Senjata tajam, senjata api, bahan mudah meledak/terbakar.</li>
                            <li>Minuman keras, alkohol, rokok/vape, dan produk tembakau lainnya.</li>
                            <li>Hewan peliharaan atau satwa dilindungi.</li>
                            <li>Barang tiruan ilegal (kw ilegal), dokumen palsu, atau hasil kejahatan.</li>
                        </ul>
                    </div>
                </section>

                <!-- Section 5 -->
                <section id="pembatalan" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">5. Kebijakan Pembatalan & Refund</h2>
                    <p>
                        Kebijakan pembatalan pesanan yang telah dibayar diatur sebagai berikut:
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong>Sebelum Jastiper Jalan:</strong> Customer dapat membatalkan pesanan secara sepihak dan dana akan di-refund 100% apabila status Jastiper belum mulai menuju ke lokasi toko pembelanjaan.</li>
                        <li><strong>Setelah Pembelian/Dalam Pengiriman:</strong> Pesanan yang sudah dibelikan oleh Jastiper tidak dapat dibatalkan secara sepihak oleh Customer.</li>
                        <li><strong>Barang Kosong:</strong> Jika Jastiper membatalkan transaksi karena seluruh barang pesanan habis/kosong di toko, dana Customer akan dikembalikan 100% tanpa potongan admin.</li>
                    </ul>
                </section>

                <!-- Section 6 -->
                <section id="sanksi" class="scroll-mt-24 space-y-4">
                    <h2 class="font-display font-black text-xl sm:text-2xl text-slate-900 border-b border-slate-100 pb-3">6. Penyelesaian Perselisihan & Sanksi</h2>
                    <p>
                        Jika terjadi sengketa transaksi (misalnya barang rusak, tidak lengkap, atau tidak sesuai struk pembelian):
                    </p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Customer wajib mengajukan tombol <strong>Komplain / Dispute</strong> maksimal 24 jam setelah kurir menyelesaikan status pengantaran di sistem aplikasi.</li>
                        <li>Tim verifikasi JastipKuy akan bertindak sebagai mediator netral dengan memeriksa bukti foto barang, struk toko asli, serta log chat internal aplikasi.</li>
                        <li>Jastiper yang terbukti melakukan manipulasi harga atau menipu Customer akan dibekukan saldonya, dinonaktifkan akunnya secara permanen, dan dilaporkan ke pihak berwajib jika terjadi kerugian finansial yang signifikan.</li>
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
