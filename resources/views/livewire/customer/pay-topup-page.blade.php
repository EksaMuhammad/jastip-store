<div
    wire:poll.3s="refreshStatus"
    x-on:topup-berhasil.window="setTimeout(() => { window.location = @js(route('customer.wallet')) }, 1200)"
>
    <div class="max-w-xl mx-auto mt-6 sm:mt-10 px-4 sm:px-0">
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 text-center">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-2">Selesaikan Pembayaran</h2>
            <p class="text-sm sm:text-base text-slate-500 mb-6 sm:mb-8">Anda akan melakukan Top Up saldo E-Wallet sebesar <span class="font-bold text-slate-700">Rp{{ number_format($topup->amount, 0, ',', '.') }}</span></p>

            <!-- Flash messages -->
            @if(!empty($flashError) || session('error'))
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-xl px-4 py-3 text-left">
                    {{ $flashError ?: session('error') }}
                </div>
            @endif
            @if(!empty($flashSuccess) || session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold rounded-xl px-4 py-3 text-left">
                    {{ $flashSuccess ?: session('success') }}
                </div>
            @endif

            @if($topup->status === 'menunggu_approval')
                <!-- TAHAP 1: Menunggu ACC Pengajuan dari Admin -->
                <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-6 mb-6 text-center space-y-3">
                    <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto shadow-inner animate-pulse">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="font-bold text-base text-amber-900">Pengajuan Top-Up Sedang Ditinjau</h3>
                    <p class="text-xs text-amber-700 leading-relaxed max-w-sm mx-auto">
                        Permintaan top-up saldo Anda sebesar <b>Rp{{ number_format($topup->amount, 0, ',', '.') }}</b> telah masuk ke antrian Admin. Kode pembayaran (QRIS / VA) akan otomatis muncul di halaman ini setelah disetujui.
                    </p>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100/80 rounded-full text-[10px] font-bold text-amber-800">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        Menunggu Persetujuan Admin (Tahap 1)
                    </div>
                </div>

            @elseif($topup->status === 'ditolak_pengajuan')
                <!-- Pengajuan ditolak admin -->
                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-6 mb-6 text-center space-y-3">
                    <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </div>
                    <h3 class="font-bold text-base text-rose-900">Pengajuan Top-Up Ditolak</h3>
                    <p class="text-xs text-rose-700 leading-relaxed max-w-sm mx-auto">
                        {{ $topup->rejection_note ?: 'Maaf, pengajuan top-up Anda ditolak oleh Admin.' }}
                    </p>
                </div>

            @elseif($topup->status === 'berhasil')
                <div class="p-6 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl mb-6 font-bold space-y-2">
                    <div class="text-3xl">🎉</div>
                    <div class="text-base">Top Up Berhasil Disetujui!</div>
                    <p class="text-xs text-emerald-600 font-normal">Saldo dompet sebesar <b>Rp{{ number_format($topup->amount, 0, ',', '.') }}</b> telah ditambahkan ke akun Anda.</p>
                </div>
            @elseif($topup->status === 'gagal')
                <div class="p-4 bg-rose-50 text-rose-700 rounded-xl mb-6 font-bold">
                    ✕ Top up ini gagal / bukti pembayaran ditolak. Silakan ajukan top up baru.
                </div>
            @else
                <!-- Tiket Pembayaran Midtrans Otomatis Aktif -->
                <div class="space-y-5 my-6">
                    <div class="bg-gradient-to-r from-sky-50 to-indigo-50 border border-sky-200 text-sky-900 rounded-2xl p-4 text-xs font-bold flex items-center justify-center gap-2 shadow-sm">
                        <span class="text-sm">⚡</span>
                        <span>Tiket Pembayaran Otomatis Aktif — Verifikasi Instan via Gateway Midtrans</span>
                    </div>

                    <!-- Kartu Tiket QRIS / Pembayaran -->
                    <div class="bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-3xl p-6 text-center space-y-4 shadow-sm relative overflow-hidden">
                        @if($topup->channel === 'bank_transfer_va' && $topup->va_number)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-100 text-indigo-800 text-[10px] font-extrabold rounded-full uppercase tracking-wider">
                                <span>BCA Virtual Account #{{ $topup->gateway_reference }}</span>
                            </div>

                            <div class="bg-slate-900 text-white rounded-2xl p-5 max-w-sm mx-auto shadow-md" x-data="{ copied: false }">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-1">Nomor Virtual Account BCA</span>
                                <div class="font-mono text-xl font-black tracking-widest text-emerald-400 mb-3">
                                    {{ $topup->va_number }}
                                </div>
                                <button 
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText('{{ $topup->va_number }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="text-xs font-bold bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl transition inline-flex items-center gap-1.5"
                                >
                                    <span x-show="!copied">📋 Salin Nomor VA</span>
                                    <span x-show="copied" x-cloak>✓ Tersalin!</span>
                                </button>
                            </div>

                            <div class="text-[11px] text-slate-500 max-w-sm mx-auto">
                                Transfer <b>tepat Rp{{ number_format($topup->amount, 0, ',', '.') }}</b> ke nomor VA di atas.
                                @if(!config('services.midtrans.is_production'))
                                    <div class="mt-2 text-indigo-600 font-bold">
                                        👉 Simulator Sandbox: <a href="https://simulator.sandbox.midtrans.com/bca/va/index" target="_blank" class="underline hover:text-indigo-800">Buka Simulator BCA VA Midtrans</a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-sky-100 text-sky-800 text-[10px] font-extrabold rounded-full uppercase tracking-wider">
                                <span>Tiket QRIS #{{ $topup->gateway_reference }}</span>
                            </div>

                            <!-- Render QR Code Tiket -->
                            <div class="space-y-2">
                                <div class="bg-white p-3.5 rounded-2xl inline-block shadow-md border border-slate-200">
                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(175)->generate($topup->qr_string ?: 'https://simulator.sandbox.midtrans.com/qris/' . $topup->gateway_reference) !!}
                                </div>
                                <p class="text-[11px] text-slate-500 max-w-xs mx-auto">
                                    Scan QRIS di atas menggunakan <b>GoPay, ShopeePay, BCA, Mandiri, Dana,</b> atau m-Banking Anda.
                                </p>
                            </div>
                        @endif

                        <!-- Notice Verifikasi Otomatis Callback -->
                        <div class="bg-emerald-50/80 border border-emerald-200 rounded-2xl p-3 text-left flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <div class="text-[11px] text-emerald-900 leading-snug">
                                <b class="font-bold">Verifikasi Otomatis & Saldo Instan:</b> Setelah dibayar, Payment Gateway Midtrans akan mengirimkan sinyal callback otomatis ke sistem. Saldo akan langsung masuk ke dompet Anda tanpa perlu konfirmasi manual admin.
                            </div>
                        </div>

                        <!-- Tombol Interaksi -->
                        <div class="flex flex-col sm:flex-row gap-2.5 justify-center pt-2">
                            @if(!$showMockSnapModal)
                                <button 
                                    wire:click="openMidtransSnap" 
                                    type="button"
                                    class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-md inline-flex items-center justify-center gap-1.5"
                                >
                                    <span>Buka Pop-up Midtrans</span>
                                </button>
                            @endif

                            <button 
                                wire:click="checkMidtransStatus" 
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-md shadow-indigo-600/20 inline-flex items-center justify-center gap-1.5"
                            >
                                <span wire:loading.remove wire:target="checkMidtransStatus">🔄 Cek Status Midtrans</span>
                                <span wire:loading wire:target="checkMidtransStatus">Memeriksa ke Midtrans...</span>
                            </button>

                            <button 
                                wire:click="markTopupSuccess('qris_instant', 'TRX-QRIS-{{ time() }}')" 
                                type="button"
                                class="px-5 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-md shadow-emerald-600/20 inline-flex items-center justify-center gap-1.5"
                            >
                                <span>⚡ Simulasikan Pembayaran Lunas</span>
                            </button>
                        </div>
                    </div>
                </div>

                @if($topup->payment_deadline)
                    <div class="text-sm text-slate-400 mb-6">
                        Bayar sebelum: <span class="font-bold text-slate-600">{{ \Carbon\Carbon::parse($topup->payment_deadline)->format('d M Y H:i') }}</span>
                    </div>
                @endif

                <!-- Upload Bukti Transfer Manual -->
                <div class="text-left bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200">
                    @if($topup->proof_image)
                        <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl mb-3">
                            <p class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                <span>✓</span> Bukti transfer sudah terkirim!
                            </p>
                            <p class="text-[10px] text-emerald-600 mt-0.5">Admin sedang memeriksa mutasi pembayaran Anda. Saldo akan otomatis bertambah setelah ACC kedua.</p>
                        </div>
                    @endif
                    <p class="text-[11px] font-bold text-slate-600 mb-1">Unggah Bukti Pembayaran / Struk Transfer</p>
                    <p class="text-[10px] text-slate-400 mb-3">Jika pembayaran tidak terkonfirmasi otomatis, kirim bukti bayar agar dicek dan di-ACC langsung oleh Admin.</p>

                    <form wire:submit.prevent="uploadProof" class="space-y-2">
                        <input type="file" wire:model="proof" accept=".jpg,.jpeg,.png,.pdf"
                            class="block w-full text-[11px] text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">

                        <div wire:loading wire:target="proof" class="text-[10px] font-bold text-slate-400">Mengunggah...</div>

                        @error('proof')
                            <p class="text-[11px] font-bold text-rose-600">{{ $message }}</p>
                        @enderror

                        @if($proof)
                            <button type="submit" wire:loading.attr="disabled" wire:target="uploadProof"
                                class="text-[11px] font-bold bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl shadow-sm transition">
                                Kirim Bukti Transfer ke Admin
                            </button>
                        @endif
                    </form>
                </div>
            @endif

            <div class="mt-8 pt-8 border-t border-slate-100">
                <a href="{{ route('customer.wallet') }}" class="text-rose-600 font-bold hover:text-rose-700 transition">Kembali ke Wallet</a>
            </div>
        </div>
    </div>

    <!-- Modal Midtrans Mock Snap Popup -->
    @if($showMockSnapModal)
        @include('livewire.customer._mock-snap-modal', [
            'amount' => $topup->amount,
            'reference' => $topup->gateway_reference,
            'vaNumber' => $topup->va_number,
            'qrString' => $topup->qr_string,
            'successAction' => 'markTopupSuccess'
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
                            @this.call('markTopupSuccess', result.payment_type || 'midtrans', result.transaction_id);
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