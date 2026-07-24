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
        @if($flashError)
            <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-xl px-4 py-3">
                {{ $flashError }}
            </div>
        @endif
        @if($flashSuccess)
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold rounded-xl px-4 py-3">
                {{ $flashSuccess }}
            </div>
        @endif

        @if(!$payment || !in_array($payment->status, ['menunggu'], true))
            <!-- Sudah final (lunas/gagal/kedaluwarsa) atau order sudah tidak menunggu pembayaran -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center shadow-sm">
                @if($payment && $payment->status === 'lunas')
                    <p class="text-4xl mb-2">✅</p>
                    <p class="font-black text-slate-900">Pembayaran sudah lunas</p>
                    <p class="text-xs text-slate-500 mt-1">Pesanan Anda sedang diproses jastiper.</p>
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

                    <button wire:click="payWithWallet" wire:loading.attr="disabled" wire:target="payWithWallet"
                        class="w-full flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 hover:border-rose-300 hover:bg-rose-50/40 transition text-left">
                        <span class="w-10 h-10 rounded-full bg-rose-600 text-white flex items-center justify-center text-lg">👛</span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Bayar dengan Saldo Wallet</span>
                            <span class="block text-[11px] text-slate-500">Instan, langsung terpotong dari saldo Anda</span>
                        </span>
                        <span wire:loading wire:target="payWithWallet" class="ml-auto text-[10px] font-bold text-rose-600">Memproses...</span>
                    </button>

                    <button wire:click="selectChannel('bank_transfer_va')"
                        class="w-full flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 hover:border-rose-300 hover:bg-rose-50/40 transition text-left">
                        <span class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center text-lg">🏦</span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Transfer Bank (Virtual Account)</span>
                            <span class="block text-[11px] text-slate-500">BCA, BNI, BRI, Permata, Mandiri</span>
                        </span>
                    </button>

                    <button wire:click="selectChannel('qris')" wire:loading.attr="disabled" wire:target="selectChannel('qris')"
                        class="w-full flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 hover:border-rose-300 hover:bg-rose-50/40 transition text-left">
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
</div>