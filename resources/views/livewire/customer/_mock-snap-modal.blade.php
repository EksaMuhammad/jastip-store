<!-- Midtrans Snap Modal Pop-up (Replika Persis Desain Resmi Midtrans Snap) -->
<div 
    x-data="{ 
        activeTab: 'all', 
        selectedChannel: null, 
        copied: false,
        countdown: '23:58:51'
    }"
    class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
>
    <!-- Card Snap Container -->
    <div class="relative w-full max-w-[420px] bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Midtrans Snap -->
        <div class="p-6 pb-4 border-b border-slate-100">
            <!-- Brand & Close Button -->
            <div class="flex items-center justify-between mb-3">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-sky-600 text-white font-black text-xs tracking-wider shadow-sm">
                    <span>JastipKuy</span>
                </div>
                <button 
                    wire:click="closeMockSnapModal" 
                    type="button" 
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition"
                    title="Tutup Modal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Amount & Order Reference -->
            <div class="space-y-1">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight font-display">
                        Rp{{ number_format($amount, 0, ',', '.') }}
                    </h2>
                    <span class="text-[11px] font-bold text-sky-600 hover:underline cursor-pointer flex items-center gap-0.5">
                        Details <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-mono">
                    <span>Order ID #{{ $reference }}</span>
                    <button 
                        type="button"
                        x-on:click="navigator.clipboard.writeText('{{ $reference }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="hover:text-slate-600"
                        title="Salin Order ID"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                    <span x-show="copied" class="text-[10px] text-emerald-600 font-sans font-bold" x-cloak>Tersalin!</span>
                </div>
            </div>

            <!-- Choose within countdown -->
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span>Choose within</span>
                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md" x-text="countdown">23:58:51</span>
            </div>
        </div>

        <!-- Body / Payment Methods List -->
        <div class="p-4 max-h-[360px] overflow-y-auto space-y-3 divide-y divide-slate-100">
            
            <!-- Quick Last Payment Method -->
            <div class="pt-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Last payment method</p>
                <button 
                    type="button"
                    x-on:click="selectedChannel = selectedChannel === 'bca_va' ? null : 'bca_va'"
                    class="w-full p-3 rounded-2xl border border-slate-200 hover:border-sky-400 hover:bg-sky-50/30 transition flex items-center justify-between text-left group"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-6 bg-blue-700 text-white rounded font-black text-[10px] italic flex items-center justify-center tracking-wider">
                            BCA
                        </div>
                        <span class="text-xs font-bold text-slate-800">BCA virtual account</span>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- All Payment Methods -->
            <div class="pt-3 space-y-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">All payment methods</p>

                <!-- GoPay / QRIS -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden transition" :class="selectedChannel === 'qris' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'hover:border-slate-300'">
                    <button 
                        type="button"
                        x-on:click="selectedChannel = selectedChannel === 'qris' ? null : 'qris'"
                        class="w-full p-3.5 flex items-center justify-between text-left"
                    >
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-800 block">GoPay QRIS</span>
                            <div class="flex items-center gap-1.5 opacity-80">
                                <span class="px-1.5 py-0.5 bg-emerald-600 text-white text-[8px] font-black rounded">gopay</span>
                                <span class="px-1.5 py-0.5 bg-sky-600 text-white text-[8px] font-black rounded">QRIS</span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition" :class="selectedChannel === 'qris' ? 'rotate-90 text-sky-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <!-- QRIS Detail Drawer -->
                    <div x-show="selectedChannel === 'qris'" x-collapse class="p-4 bg-slate-50 border-t border-slate-200 text-center space-y-3">
                        <p class="text-[11px] font-semibold text-slate-600">Scan QR Code ini menggunakan GoPay, ShopeePay, BCA, atau m-Banking Anda:</p>
                        <div class="bg-white p-3 rounded-2xl inline-block shadow-sm border border-slate-200">
                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(160)->generate($qrString ?: 'https://simulator.sandbox.midtrans.com/qris/' . $reference) !!}
                        </div>
                        <p class="text-[10px] text-slate-400 font-mono">Kode QRIS Sandbox Aktif</p>
                        <button 
                            type="button" 
                            wire:click="{{ $successAction }}('qris', 'MOCK-TRX-QRIS-{{ time() }}')" 
                            class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Simulasikan Scan & Bayar Berhasil</span>
                        </button>
                    </div>
                </div>

                <!-- Virtual Account Group (BCA, BNI, BRI, Mandiri) -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden transition" :class="selectedChannel && selectedChannel.includes('_va') ? 'border-sky-500 ring-2 ring-sky-500/20' : 'hover:border-slate-300'">
                    <button 
                        type="button"
                        x-on:click="selectedChannel = selectedChannel && selectedChannel.includes('_va') ? null : 'bca_va'"
                        class="w-full p-3.5 flex items-center justify-between text-left"
                    >
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-800 block">Virtual account</span>
                            <div class="flex items-center gap-1.5">
                                <span class="px-1.5 py-0.5 bg-blue-700 text-white text-[8px] font-black rounded">BCA</span>
                                <span class="px-1.5 py-0.5 bg-orange-600 text-white text-[8px] font-black rounded">BNI</span>
                                <span class="px-1.5 py-0.5 bg-blue-900 text-white text-[8px] font-black rounded">BRI</span>
                                <span class="px-1.5 py-0.5 bg-amber-600 text-white text-[8px] font-black rounded">MANDIRI</span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition" :class="selectedChannel && selectedChannel.includes('_va') ? 'rotate-90 text-sky-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <!-- VA Detail Drawer -->
                    <div x-show="selectedChannel && selectedChannel.includes('_va')" x-collapse class="p-4 bg-slate-50 border-t border-slate-200 text-left space-y-3">
                        <div class="flex gap-1.5 overflow-x-auto pb-1 text-[10px] font-bold">
                            <button type="button" x-on:click="selectedChannel = 'bca_va'" :class="selectedChannel === 'bca_va' ? 'bg-blue-700 text-white' : 'bg-white text-slate-600 border'" class="px-2.5 py-1 rounded-lg transition">BCA</button>
                            <button type="button" x-on:click="selectedChannel = 'bni_va'" :class="selectedChannel === 'bni_va' ? 'bg-orange-600 text-white' : 'bg-white text-slate-600 border'" class="px-2.5 py-1 rounded-lg transition">BNI</button>
                            <button type="button" x-on:click="selectedChannel = 'bri_va'" :class="selectedChannel === 'bri_va' ? 'bg-blue-900 text-white' : 'bg-white text-slate-600 border'" class="px-2.5 py-1 rounded-lg transition">BRI</button>
                            <button type="button" x-on:click="selectedChannel = 'mandiri_va'" :class="selectedChannel === 'mandiri_va' ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 border'" class="px-2.5 py-1 rounded-lg transition">Mandiri</button>
                        </div>

                        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 space-y-1">
                            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Nomor Virtual Account</span>
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-base font-black text-slate-900 tracking-wider">
                                    {{ $vaNumber ?: '12345' . rand(10000000, 99999999) }}
                                </span>
                                <button 
                                    type="button" 
                                    x-on:click="navigator.clipboard.writeText('{{ $vaNumber ?: '1234588992211' }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="text-[10px] font-bold text-sky-600 hover:text-sky-700 bg-sky-50 px-2 py-1 rounded-md"
                                >
                                    Salin
                                </button>
                            </div>
                        </div>

                        <p class="text-[10px] text-slate-400 leading-relaxed">Transfer tepat sesuai nominal tagihan melalui ATM, Mobile Banking, atau Internet Banking.</p>

                        <button 
                            type="button" 
                            wire:click="{{ $successAction }}(selectedChannel || 'bank_transfer_va', 'MOCK-TRX-VA-{{ time() }}')" 
                            class="w-full py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold transition shadow-md shadow-blue-600/20 flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Simulasikan Transfer Berhasil</span>
                        </button>
                    </div>
                </div>

                <!-- ShopeePay QRIS -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden hover:border-slate-300 transition">
                    <button 
                        type="button"
                        x-on:click="selectedChannel = selectedChannel === 'shopeepay' ? null : 'shopeepay'"
                        class="w-full p-3.5 flex items-center justify-between text-left"
                    >
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-800 block">ShopeePay QRIS</span>
                            <div class="flex items-center gap-1.5">
                                <span class="px-1.5 py-0.5 bg-orange-500 text-white text-[8px] font-black rounded">ShopeePay</span>
                                <span class="px-1.5 py-0.5 bg-sky-600 text-white text-[8px] font-black rounded">QRIS</span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition" :class="selectedChannel === 'shopeepay' ? 'rotate-90 text-orange-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                    <div x-show="selectedChannel === 'shopeepay'" x-collapse class="p-4 bg-slate-50 border-t border-slate-200 text-center space-y-3">
                        <button 
                            type="button" 
                            wire:click="{{ $successAction }}('shopeepay', 'MOCK-TRX-SHOPEE-{{ time() }}')" 
                            class="w-full py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold transition shadow"
                        >
                            ✓ Simulasikan Bayar via ShopeePay
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- Tombol Cepat Otomatis Bayar Lunas (Permintaan User: "otomatis bayar aja") -->
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            <button 
                type="button"
                wire:click="{{ $successAction }}('auto_instant', 'AUTO-SETTLE-{{ time() }}')"
                class="w-full py-3 px-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-slate-900/20 flex items-center justify-center gap-2"
            >
                <span>⚡</span>
                <span>Bayar Otomatis (Simulasi Lunas Instan)</span>
            </button>
        </div>

        <!-- Footer Midtrans Snap (Logo & Language) -->
        <div class="px-6 py-3 bg-white border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
            <div class="flex items-center gap-1 cursor-pointer hover:text-slate-600">
                <span>🌐</span>
                <span class="font-bold">ID / EN</span>
            </div>
            <div class="flex items-center gap-1 font-semibold">
                <span>Powered by</span>
                <span class="font-black text-sky-600 tracking-tight text-xs">midtrans</span>
            </div>
        </div>

    </div>
</div>
