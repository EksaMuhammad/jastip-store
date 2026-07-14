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
                <p class="text-sm leading-relaxed text-slate-400">
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
                    <li><a href="{{ route('about') }}" class="hover:text-white transition">Tentang Kami</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-white transition">Tanya Jawab (FAQ)</a></li>
                    <li><a href="{{ route('about') }}#contact" class="hover:text-white transition">Hubungi Kami</a></li>
                    <li><a href="#" class="hover:text-white transition">Karir / Jastiper</a></li>
                </ul>
            </div>

            <!-- Col 3: Legalitas -->
            <div>
                <h4 class="font-display font-bold text-white text-sm uppercase tracking-wider mb-4">Legal & Aturan</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('privacy') }}" class="hover:text-white transition">Kebijakan Privasi</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-white transition">Syarat & Ketentuan</a></li>
                    <li><a href="{{ route('terms') }}#pembatalan" class="hover:text-white transition">Kebijakan Pembatalan</a></li>
                    <li><a href="{{ route('terms') }}#escrow" class="hover:text-white transition">Ketentuan Escrow</a></li>
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

        <!-- Footer Bottom Meta Info -->
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
