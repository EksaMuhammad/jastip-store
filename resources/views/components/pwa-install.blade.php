<!-- Progressive Web App (PWA) Manager & Install Widget -->
@php
    $hasBottomNav = (request()->is('customer/*', 'customer') && !request()->is('customer/cart', 'customer/cart/*', 'customer/orders/*', 'customer/support*')) || request()->is('jastiper/*', 'jastiper');
@endphp
<div id="pwa-install-container" class="relative z-40" x-data="pwaComponent()" x-cloak>
    
    <!-- Floating PWA Install Banner (Mobile & Desktop) -->
    <div x-show="showFloatingBanner && !isInstalled" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         class="fixed {{ $hasBottomNav ? 'bottom-20' : 'bottom-4' }} left-4 right-4 sm:left-auto sm:right-6 sm:bottom-6 sm:max-w-md bg-slate-900/95 backdrop-blur-md text-white p-4 sm:p-5 rounded-2xl shadow-2xl border border-slate-800 z-40 flex items-center gap-4">
        
        <!-- App Icon -->
        <div class="w-12 h-12 bg-rose-600 rounded-xl flex items-center justify-center shrink-0 shadow-md shadow-rose-600/30 border border-rose-500/30">
            <span class="font-display font-black text-white text-base tracking-tighter">JK</span>
        </div>

        <!-- Text Info -->
        <div class="flex-1 min-w-0">
            <h4 class="font-display font-bold text-sm text-white truncate flex items-center gap-1.5">
                <span>Install Aplikasi JastipKuy</span>
                <span class="bg-rose-500/20 text-rose-400 text-[10px] font-extrabold px-1.5 py-0.2 rounded">PWA</span>
            </h4>
            <p class="text-xs text-slate-300 line-clamp-1 mt-0.5">Akses cepat langsung dari home screen kamu.</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" 
                    @click="installApp()" 
                    class="bg-rose-600 hover:bg-rose-700 active:scale-95 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition shadow-sm cursor-pointer flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Install</span>
            </button>
            <button type="button" 
                    @click="dismissBanner()" 
                    class="text-slate-400 hover:text-white text-xs font-semibold px-2 py-2 transition cursor-pointer">
                Nanti
            </button>
        </div>
    </div>

    <!-- Modal Petunjuk Install Khusus iOS Safari -->
    <div x-show="showIosModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm"
         style="display: none;">
        
        <div @click.outside="showIosModal = false"
             class="bg-white text-slate-900 rounded-3xl p-6 max-w-sm w-full shadow-2xl space-y-5 border border-slate-200 animate-slide-up">
            
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-rose-600 rounded-2xl flex items-center justify-center shadow-md shadow-rose-200 text-white font-black text-lg">
                        JK
                    </div>
                    <div>
                        <h3 class="font-display font-extrabold text-base text-slate-900">Install di iOS / Safari</h3>
                        <p class="text-xs text-slate-500">Pasang JastipKuy di Home Screen</p>
                    </div>
                </div>
                <button type="button" @click="showIosModal = false" class="text-slate-400 hover:text-slate-600 p-1 font-bold text-lg cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- Steps for iOS -->
            <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs">
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-[11px] shrink-0 mt-0.5">1</span>
                    <p class="text-slate-700">
                        Ketuk tombol <strong>Bagikan (Share)</strong> 
                        <svg class="w-4 h-4 inline-block text-blue-500 -mt-1 mx-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                        di bilah bawah peramban Safari.
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-[11px] shrink-0 mt-0.5">2</span>
                    <p class="text-slate-700">
                        Gulir ke bawah dan pilih opsi <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong> 
                        <svg class="w-4 h-4 inline-block text-slate-700 -mt-1 mx-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>.
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-[11px] shrink-0 mt-0.5">3</span>
                    <p class="text-slate-700">
                        Ketuk <strong>Tambah (Add)</strong> di pojok kanan atas. Aplikasi JastipKuy siap digunakan!
                    </p>
                </div>
            </div>

            <button type="button" 
                    @click="showIosModal = false" 
                    class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-3 rounded-xl transition cursor-pointer">
                Mengerti
            </button>
        </div>
    </div>

</div>

<!-- Client-side PWA Script Controller -->
<script>
    // 1. Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then((registration) => {
                    console.log('JastipKuy Service Worker registered with scope:', registration.scope);
                })
                .catch((error) => {
                    console.warn('JastipKuy Service Worker registration failed:', error);
                });
        });
    }

    // 2. Global PWA State & Capture Prompt
    window.pwaDeferredPrompt = null;
    window.isPwaStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default browser mini-infobar on mobile
        e.preventDefault();
        window.pwaDeferredPrompt = e;
        window.dispatchEvent(new CustomEvent('pwa-prompt-available'));
    });

    window.addEventListener('appinstalled', () => {
        window.pwaDeferredPrompt = null;
        window.isPwaStandalone = true;
        localStorage.setItem('jastipkuy_pwa_installed', 'true');
        window.dispatchEvent(new CustomEvent('pwa-installed-success'));
    });

    // 3. Alpine Component Definition
    function pwaComponent() {
        return {
            isInstalled: window.isPwaStandalone || localStorage.getItem('jastipkuy_pwa_installed') === 'true',
            isIos: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,
            showFloatingBanner: false,
            showIosModal: false,

            init() {
                // If already running standalone or installed, don't show
                if (this.isInstalled) return;

                const dismissed = localStorage.getItem('jastipkuy_pwa_dismissed');
                const lastDismissedTime = dismissed ? parseInt(dismissed, 10) : 0;
                const cooldownPassed = (Date.now() - lastDismissedTime) > (24 * 60 * 60 * 1000); // 1 day cooldown

                if (cooldownPassed) {
                    // Delay banner appearance slightly for polished UX
                    setTimeout(() => {
                        this.showFloatingBanner = true;
                    }, 2500);
                }

                window.addEventListener('pwa-prompt-available', () => {
                    if (cooldownPassed && !this.isInstalled) {
                        this.showFloatingBanner = true;
                    }
                });

                window.addEventListener('pwa-installed-success', () => {
                    this.isInstalled = true;
                    this.showFloatingBanner = false;
                });
            },

            async installApp() {
                if (this.isIos) {
                    this.showIosModal = true;
                    return;
                }

                if (window.pwaDeferredPrompt) {
                    try {
                        window.pwaDeferredPrompt.prompt();
                        const { outcome } = await window.pwaDeferredPrompt.userChoice;
                        if (outcome === 'accepted') {
                            this.isInstalled = true;
                            this.showFloatingBanner = false;
                            localStorage.setItem('jastipkuy_pwa_installed', 'true');
                        }
                    } catch (err) {
                        console.error('Error invoking PWA install prompt:', err);
                    } finally {
                        window.pwaDeferredPrompt = null;
                    }
                } else {
                    // Fallback instructions for desktop or browsers without prompt event
                    this.showManualInstallInfo();
                }
            },

            showManualInstallInfo() {
                alert('Untuk menginstall aplikasi JastipKuy:\n\n1. Di Chrome/Edge laptop: Klik ikon install (komputer/panah bawah) di bilah alamat URL paling kanan.\n2. Di HP Android: Buka menu titik tiga (⋮) lalu pilih "Tambahkan ke Layar Utama" / "Install Aplikasi".\n3. Di iPhone/iPad: Tekan tombol Bagikan (Share) lalu pilih "Tambah ke Layar Utama".');
            },

            dismissBanner() {
                this.showFloatingBanner = false;
                localStorage.setItem('jastipkuy_pwa_dismissed', Date.now().toString());
            }
        };
    }

    // Global helper callable from anywhere (e.g. from the in-page card button)
    window.triggerPwaInstall = function() {
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        if (isIos) {
            const container = document.getElementById('pwa-install-container');
            if (container && container._x_dataStack && container._x_dataStack[0]) {
                container._x_dataStack[0].showIosModal = true;
                return;
            }
            alert('Untuk pengguna iPhone/iPad: Ketuk ikon Bagikan (Share) di Safari, lalu pilih "Tambah ke Layar Utama".');
            return;
        }

        if (window.pwaDeferredPrompt) {
            window.pwaDeferredPrompt.prompt().then((res) => {
                if (res.outcome === 'accepted') {
                    localStorage.setItem('jastipkuy_pwa_installed', 'true');
                }
                window.pwaDeferredPrompt = null;
            });
        } else {
            alert('Aplikasi siap diinstall!\n\n• Di Laptop (Chrome/Edge): Klik ikon Install di ujung kanan kolom alamat web (URL bar).\n• Di HP Android: Tekan tombol menu titik tiga (⋮) lalu pilih "Tambahkan ke Layar Utama" / "Install Aplikasi".\n• Di iPhone/iPad: Tekan tombol Bagikan (Share) lalu pilih "Tambah ke Layar Utama".');
        }
    };
</script>

