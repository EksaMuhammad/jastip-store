<div
    wire:poll.3s="refreshStatus"
    x-data="{
        deadline: @js(optional($payment?->payment_deadline)->toIso8601String()),
        remaining: null,
        expired: false,
        tick() {
            if (!this.deadline) { this.remaining = null; return; }
            const diff = Math.max(0, Math.floor((new Date(this.deadline) - new Date()) / 1000));
            this.expired = diff <= 0;
            const m = Math.floor(diff / 60).toString().padStart(2, '0');
            const s = (diff % 60).toString().padStart(2, '0');
            this.remaining = m + ':' + s;
        }
    }"
    x-init="tick(); setInterval(() => tick(), 1000)"
    x-on:order-diproses.window="setTimeout(() => { window.location = @js(route('customer.dashboard')) }, 1200)"
    x-on:order-dibatalkan.window="setTimeout(() => { window.location = @js(route('customer.dashboard')) }, 1200)"
>
    <div class="max-w-xl mx-auto px-4 py-8">

        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center gap-2">
            <a href="{{ route('customer.dashboard') }}" class="text-xs font-bold text-rose-600 hover:underline">Dashboard</a>
            <span class="text-slate-400">/</span>
            <span class="text-xs font-bold text-slate-500">Pembayaran Order #{{ $order->id }}</span>
        </div>

        <!-- Ringkasan order -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Tagihan</p>
                    <p class="text-2xl font-black text-slate-900 font-display">Rp{{ number_format((float) $order->agreed_fare, 0, ',', '.') }}</p>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full font-mono
                    {{ $order->status === 'menunggu_pembayaran' ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }}">
                    {{ str_replace('_', ' ', $order->status) }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-2 line-clamp-2">{{ $order->description }}</p>

            @if($payment && $payment->status === 'menunggu' && $payment->payment_deadline)
                <div class="mt-4 pt-4 border-t border-dashed border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">Selesaikan pembayaran dalam</span>
                    <span
                        class="text-sm font-black font-mono px-2.5 py-1 rounded-lg"
                        :class="expired ? 'bg-rose-50 text-rose-600' : 'bg-slate-900 text-white'"
                        x-text="expired ? 'Waktu habis' : remaining"
                    ></span>
                </div>
            @endif
        </div>

        <!-- Flash messages -->
        @if(!empty($flashError) || session('error'))
            <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-xl px-4 py-3">
                {{ $flashError ?: session('error') }}
            </div>
        @endif
        @if(!empty($flashSuccess) || session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold rounded-xl px-4 py-3">
                {{ $flashSuccess ?: session('success') }}
            </div>
        @endif

        @if(!$payment || !in_array($payment->status, ['menunggu'], true))
            <!-- Sudah final (lunas/gagal/kedaluwarsa) atau order sudah tidak menunggu pembayaran -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center shadow-sm">
                @if($payment && $payment->status === 'lunas')
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl mb-3 shadow-inner">
                        ✓
                    </div>
                    <p class="font-black text-slate-900 text-lg">Pembayaran Sukses & Lunas!</p>
                    <p class="text-xs text-slate-500 mt-1">Transaksi Anda telah tercatat sah di sistem JastipKuy.</p>
                    <div class="mt-5 flex flex-col sm:flex-row items-center justify-center gap-2.5">
                        <a href="{{ route('customer.orders.invoice', $order->id) }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition shadow-md">
                            <span>📄</span> Cetak / Unduh Nota Invoice
                        </a>
                        <a href="{{ route('customer.orders.show', $order->id) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition shadow-sm">
                            <span>🛵</span> Pantau Pesanan →
                        </a>
                    </div>
                @elseif($payment && $payment->status === 'kedaluwarsa')
                    <p class="text-4xl mb-2">⏰</p>
                    <p class="font-black text-slate-900">Waktu pembayaran sudah habis</p>
                    <p class="text-xs text-slate-500 mt-1">Order ini otomatis dibatalkan. Silakan buat pesanan baru.</p>
                @elseif($payment && $payment->status === 'gagal')
                    <p class="text-4xl mb-2">✕</p>
                    <p class="font-black text-slate-900">Pembayaran tidak berhasil</p>
                    <p class="text-xs text-slate-500 mt-1">Order ini sudah tidak aktif.</p>
                @else
                    <p class="font-black text-slate-900">Tidak ada pembayaran yang menunggu untuk order ini.</p>
                @endif

                <a href="{{ route('customer.dashboard') }}" class="inline-block mt-4 text-xs font-bold text-rose-600 hover:underline">Kembali ke Dashboard</a>
            </div>
        @else

            <!-- ===== STEP: pilih metode ===== -->
            @if($step === 'pilih_metode')
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pilih Metode Pembayaran</p>

                    <!-- 1. Tombol Saldo Wallet dengan Indikator Saldo & Tombol Top-up Cepat -->
                    <div class="rounded-xl border-2 {{ $walletBalance < $requiredAmount ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200 hover:border-rose-300 hover:bg-rose-50/40' }} p-4 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full {{ $walletBalance < $requiredAmount ? 'bg-amber-500' : 'bg-rose-600' }} text-white flex items-center justify-center text-lg shrink-0">👛</span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">Bayar dengan Saldo Wallet</span>
                                        @if($walletBalance >= $requiredAmount)
                                            <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ✓ Saldo Cukup
                                            </span>
                                        @else
                                            <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300">
                                                ⚠️ Kurang Rp{{ number_format($requiredAmount - $walletBalance, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="block text-xs text-slate-600 mt-0.5">
                                        Saldo Anda saat ini: <b class="text-slate-900 font-mono">Rp{{ number_format($walletBalance, 0, ',', '.') }}</b>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Keterangan & Aksi -->
                        <div class="mt-3 pt-3 border-t border-slate-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            @if($walletBalance >= $requiredAmount)
                                <p class="text-[11px] text-slate-500">Saldo cukup untuk membayar tagihan Rp{{ number_format($requiredAmount, 0, ',', '.') }} secara instan.</p>
                                <button wire:click="payWithWallet" wire:loading.attr="disabled" wire:target="payWithWallet"
                                    type="button"
                                    class="py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span wire:loading.remove wire:target="payWithWallet">Bayar Sekarang</span>
                                    <span wire:loading wire:target="payWithWallet">Memproses...</span>
                                </button>
                            @else
                                <p class="text-[11px] text-amber-800 leading-tight">
                                    Saldo kurang <b class="text-rose-600 font-mono">Rp{{ number_format($requiredAmount - $walletBalance, 0, ',', '.') }}</b>. Silakan top-up terlebih dahulu.
                                </p>
                                <div class="flex items-center gap-2">
                                    <button wire:click="proceedToTopup" wire:loading.attr="disabled" wire:target="proceedToTopup"
                                        type="button"
                                        class="py-2 px-3.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <span wire:loading.remove wire:target="proceedToTopup">Top-Up Rp{{ number_format($requiredAmount - $walletBalance, 0, ',', '.') }}</span>
                                        <span wire:loading wire:target="proceedToTopup">Membuat Tiket...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- 2. Pilihan Pembayaran via Midtrans Snap (Accordion / Expandable) -->
                    <div class="rounded-xl border-2 transition {{ $selectedMethod === 'midtrans' ? 'border-sky-500 bg-sky-50/30 shadow-sm' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50/60' }} p-4">
                        <div wire:click="selectMethod('midtrans')" class="flex items-center justify-between cursor-pointer select-none">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full {{ $selectedMethod === 'midtrans' ? 'bg-sky-600 text-white' : 'bg-slate-900 text-white' }} flex items-center justify-center text-lg shrink-0 transition">
                                    💳
                                </span>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-slate-900">Bayar via Midtrans Snap</span>
                                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 border border-sky-300">
                                            ⚡ Otomatis Lunas
                                        </span>
                                    </div>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">
                                        GoPay, QRIS, ShopeePay, Virtual Account Bank
                                    </span>
                                </div>
                            </div>
                            <div class="text-slate-400 pl-2">
                                <svg class="w-5 h-5 transition-transform duration-200 {{ $selectedMethod === 'midtrans' ? 'rotate-180 text-sky-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Opsi Lanjutan & Opsi Suksesin Muncul Saat Dipilih -->
                        @if($selectedMethod === 'midtrans')
                            <div class="mt-3 pt-3 border-t border-slate-200/70 space-y-3">
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Pilih GoPay QRIS, ShopeePay, atau Virtual Account Bank (BCA, BNI, BRI, Mandiri) langsung di satu pop-up interaktif.
                                </p>

                                <div class="flex flex-col sm:flex-row gap-2">
                                    <!-- Tombol Buka Pop-up Midtrans -->
                                    <button 
                                        wire:click="openMidtransSnap" 
                                        type="button"
                                        class="flex-1 py-2.5 px-4 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-black text-xs uppercase tracking-wider transition shadow-md shadow-sky-500/20 flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        <span>Buka Pop-up Midtrans</span>
                                    </button>

                                    <!-- Opsi Suksesinnya (Simulasi / Auto Selesai) -->
                                    <button 
                                        wire:click="markPaymentSuccess('auto_instant')" 
                                        type="button"
                                        class="py-2.5 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                                        title="Simulasi bayar lunas otomatis"
                                    >
                                        <span>⚡ Otomatis Lunas</span>
                                    </button>

                                    <!-- Cek Status Midtrans -->
                                    <button 
                                        wire:click="checkMidtransStatus" 
                                        wire:loading.attr="disabled"
                                        type="button"
                                        class="py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-sky-300 font-bold text-xs transition flex items-center justify-center gap-1 cursor-pointer"
                                        title="Cek status lunas langsung ke Midtrans"
                                    >
                                        <span wire:loading.remove wire:target="checkMidtransStatus">🔄 Cek Status</span>
                                        <span wire:loading wire:target="checkMidtransStatus">Memeriksa...</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- 3. Transfer Bank (Virtual Account) -->
                    <button wire:click="selectChannel('bank_transfer_va')"
                        class="w-full flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 hover:border-rose-300 hover:bg-rose-50/40 transition text-left cursor-pointer">
                        <span class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center text-lg">🏦</span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Transfer Bank (Virtual Account)</span>
                            <span class="block text-[11px] text-slate-500">BCA, BNI, BRI, Permata, Mandiri</span>
                        </span>
                    </button>

                    <!-- 4. QRIS -->
                    <button wire:click="selectChannel('qris')" wire:loading.attr="disabled" wire:target="selectChannel('qris')"
                        class="w-full flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 hover:border-rose-300 hover:bg-rose-50/40 transition text-left cursor-pointer">
                        <span class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center text-lg">🔳</span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">QRIS</span>
                            <span class="block text-[11px] text-slate-500">Scan pakai aplikasi e-wallet / m-banking apa saja</span>
                        </span>
                        <span wire:loading wire:target="selectChannel('qris')" class="ml-auto text-[10px] font-bold text-emerald-600">Memproses...</span>
                    </button>
                </div>
            @endif

            <!-- ===== STEP: pilih bank ===== -->
            @if($step === 'pilih_bank')
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pilih Bank</p>
                        <button wire:click="backToMethod" class="text-[11px] font-bold text-slate-400 hover:text-rose-600">‹ Ganti metode</button>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($supportedBanks as $bank)
                            <button wire:click="chooseBank('{{ $bank }}')" wire:loading.attr="disabled" wire:target="chooseBank('{{ $bank }}')"
                                class="p-4 rounded-xl border-2 border-slate-200 hover:border-rose-400 hover:bg-rose-50/40 transition font-black text-sm uppercase text-slate-700">
                                {{ $bank }}
                                <span wire:loading wire:target="chooseBank('{{ $bank }}')" class="block text-[9px] font-bold text-rose-600 normal-case mt-1">Membuat VA...</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- ===== STEP: tampil VA ===== -->
            @if($step === 'tampil_va' && $payment->va_number)
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Virtual Account {{ strtoupper($selectedBank ?? '') }}</p>
                        <button wire:click="backToMethod" class="text-[11px] font-bold text-slate-400 hover:text-rose-600">‹ Ganti metode</button>
                    </div>

                    <div
                        x-data="{ copied: false }"
                        class="bg-slate-900 text-white rounded-xl p-4 flex items-center justify-between gap-3"
                    >
                        <span class="font-mono text-lg tracking-widest font-black">{{ $payment->va_number }}</span>
                        <button
                            x-on:click="navigator.clipboard.writeText('{{ $payment->va_number }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="text-[10px] font-bold bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition"
                        >
                            <span x-show="!copied">Salin</span>
                            <span x-show="copied" x-cloak>Tersalin!</span>
                        </button>
                    </div>

                    <p class="text-[11px] text-slate-500 mt-3">Transfer <b>tepat</b> sejumlah Rp{{ number_format((float) $order->agreed_fare, 0, ',', '.') }} ke nomor VA di atas. Status akan berubah otomatis begitu pembayaran kami terima.</p>

                    <div class="mt-3 flex flex-col sm:flex-row gap-2">
                        <button 
                            wire:click="checkMidtransStatus" 
                            wire:loading.attr="disabled"
                            type="button"
                            class="flex-1 py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="checkMidtransStatus">🔄 Cek Status Pembayaran (Midtrans)</span>
                            <span wire:loading wire:target="checkMidtransStatus">Memeriksa ke Midtrans...</span>
                        </button>
                        <button 
                            wire:click="markPaymentSuccess('auto_instant')" 
                            type="button"
                            class="py-2 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                            title="Simulasi bayar lunas otomatis"
                        >
                            <span>⚡ Otomatis Lunas</span>
                        </button>
                    </div>

                    <div class="mt-4 pt-4 border-t border-dashed border-slate-200">
                        @include('livewire.customer._payment-manual-proof')
                    </div>
                </div>
            @endif

            <!-- ===== STEP: tampil QRIS ===== -->
            @if($step === 'tampil_qris' && $payment->qr_string)
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm text-center">
                    <div class="flex items-center justify-between mb-3 text-left">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Scan QRIS</p>
                        <button wire:click="backToMethod" class="text-[11px] font-bold text-slate-400 hover:text-rose-600">‹ Ganti metode</button>
                    </div>

                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($payment->qr_string) }}"
                        alt="QRIS"
                        class="mx-auto rounded-xl border border-slate-200 p-2"
                        width="220" height="220"
                    >
                    <p class="text-[11px] text-slate-500 mt-3">Buka aplikasi e-wallet atau m-banking, pilih Scan QRIS, lalu bayar Rp{{ number_format((float) $order->agreed_fare, 0, ',', '.') }}.</p>

                    <div class="mt-3 flex flex-col sm:flex-row gap-2 justify-center">
                        <button 
                            wire:click="checkMidtransStatus" 
                            wire:loading.attr="disabled"
                            type="button"
                            class="flex-1 py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm inline-flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="checkMidtransStatus">🔄 Cek Status Pembayaran (Midtrans)</span>
                            <span wire:loading wire:target="checkMidtransStatus">Memeriksa ke Midtrans...</span>
                        </button>
                        <button 
                            wire:click="markPaymentSuccess('auto_instant')" 
                            type="button"
                            class="py-2 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs uppercase tracking-wider transition shadow-sm inline-flex items-center justify-center gap-1.5 cursor-pointer"
                            title="Simulasi bayar lunas otomatis"
                        >
                            <span>⚡ Otomatis Lunas</span>
                        </button>
                    </div>

                    <div class="mt-4 pt-4 border-t border-dashed border-slate-200 text-left">
                        @include('livewire.customer._payment-manual-proof')
                    </div>
                </div>
            @endif

            <!-- Batalkan pesanan -->
            <div class="mt-4 text-center">
                @if(!$showCancelConfirm)
                    <button wire:click="confirmCancel" class="text-[11px] font-bold text-slate-400 hover:text-rose-600 underline underline-offset-2">
                        Batalkan Pesanan
                    </button>
                @else
                    <div class="bg-white border border-rose-200 rounded-xl p-4 inline-block">
                        <p class="text-xs font-bold text-slate-700 mb-3">Yakin batalkan pesanan ini?</p>
                        <div class="flex gap-2 justify-center">
                            <button wire:click="cancelOrder" wire:loading.attr="disabled" class="text-[11px] font-bold bg-rose-600 text-white px-3 py-1.5 rounded-lg hover:bg-rose-700">Ya, Batalkan</button>
                            <button wire:click="cancelCancel" class="text-[11px] font-bold bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg hover:bg-slate-200">Tidak</button>
                        </div>
                    </div>
                @endif
            </div>

        @endif
    </div>

    <!-- Modal Peringatan Saldo Kurang & Aksi Top-Up Cepat -->
    @if($showInsufficientBalanceModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 text-center space-y-4 animate-scaleIn">
                <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto text-2xl shadow-inner">
                    👛
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900">Saldo E-Wallet Kurang</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Saldo Anda tidak mencukupi untuk melunasi tagihan pesanan ini secara instan.
                    </p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-xs space-y-2 text-left">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium">Total Tagihan Pesanan:</span>
                        <span class="font-bold text-slate-800 font-mono">Rp{{ number_format($requiredAmount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium">Saldo E-Wallet Anda:</span>
                        <span class="font-bold text-slate-800 font-mono">Rp{{ number_format($currentBalance, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm">
                        <span class="font-bold text-rose-600">Kekurangan Saldo:</span>
                        <span class="font-black text-rose-600 font-mono">Rp{{ number_format($shortageAmount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-500 leading-relaxed text-left">
                    💡 Silakan top-up minimal <b>Rp{{ number_format($shortageAmount, 0, ',', '.') }}</b> terlebih dahulu, atau bayar langsung tagihan ini menggunakan <b>Midtrans Snap (QRIS / Virtual Account)</b> tanpa perlu top-up.
                </p>

                <div class="space-y-2 pt-2">
                    <button wire:click="proceedToTopup" wire:loading.attr="disabled" wire:target="proceedToTopup"
                        type="button"
                        class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-wider transition shadow-md shadow-amber-500/20 flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span wire:loading.remove wire:target="proceedToTopup">+ Top-Up Saldo Rp{{ number_format($shortageAmount, 0, ',', '.') }} Sekarang</span>
                        <span wire:loading wire:target="proceedToTopup">Menyiapkan Tiket Top-Up...</span>
                    </button>

                    <button wire:click="openMidtransSnap" type="button"
                        class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Bayar Langsung via Midtrans Snap</span>
                    </button>

                    <button wire:click="closeInsufficientBalanceModal" type="button"
                        class="w-full py-2 px-4 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 font-semibold text-xs transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Midtrans Mock Snap Popup -->
    @if($showMockSnapModal && $payment)
        @include('livewire.customer._mock-snap-modal', [
            'amount' => $payment->amount,
            'reference' => $payment->gateway_reference,
            'vaNumber' => $payment->va_number,
            'qrString' => $payment->qr_string,
            'successAction' => 'markPaymentSuccess'
        ])
    @endif

    @if(config('services.midtrans.client_key'))
        <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            window.addEventListener('open-midtrans-snap', (event) => {
                const token = event.detail.token || (event.detail[0] && event.detail[0].token);
                if (window.snap && token) {
                    window.snap.pay(token, {
                        onSuccess: function (result) {
                            @this.call('markPaymentSuccess', result.payment_type || 'midtrans', result.transaction_id);
                        },
                        onPending: function (result) {
                            console.log('Midtrans Snap Pending:', result);
                        },
                        onError: function (result) {
                            console.error('Midtrans Snap Error:', result);
                            alert('Pembayaran gagal atau dibatalkan.');
                        },
                        onClose: function () {
                            console.log('Midtrans Snap closed by customer');
                        }
                    });
                }
            });
        });
    </script>
</div>