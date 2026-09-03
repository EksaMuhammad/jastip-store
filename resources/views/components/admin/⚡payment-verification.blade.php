<?php

use Livewire\Component;
use App\Models\Payment;
use App\Models\Topup;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Komponen Livewire 4.3 single-file (SFC) — Tahap 6 (brief §3 Tahap 6).
 * Pola & gaya (filter tabs, grid card, modal detail) sengaja meniru persis
 * resources/views/components/admin/⚡admin-verification.blade.php (verifikasi
 * jastiper) yang sudah ada, supaya konsisten dengan UI admin lain.
 *
 * Beda penting dari verifikasi jastiper: tidak ada kolom rejection_reason di
 * tabel payments (skema payments Tahap 1 tidak menyertakannya, lihat brief
 * §1.2), jadi tombol "Tolak" di sini TIDAK meminta alasan tertulis — cuma
 * konfirmasi aksi. Kalau nanti dibutuhkan alasan tertulis, itu perlu migration
 * baru (di luar scope Tahap 6, dicatat sebagai keputusan terbuka di §4 brief).
 *
 * Query selalu di-scope ke payment yang punya proof_image (whereNotNull) —
 * halaman ini murni untuk verifikasi bukti transfer manual, bukan semua
 * histori payment (pembayaran via VA/QRIS/wallet yang sukses otomatis lewat
 * webhook/debit tidak butuh review admin sama sekali).
 */
new class extends Component
{
    public string $search = '';
    public string $filter_status = 'menunggu'; // menunggu, lunas, gagal, semua

    public $selected_id = null;

    public string $success_message = '';
    public string $error_message = '';

    public bool $showRejectConfirm = false;

    public function selectPayment($id)
    {
        $this->selected_id = $id;
        $this->success_message = '';
        $this->error_message = '';
        $this->showRejectConfirm = false;
    }

    public function closeDetail()
    {
        $this->selected_id = null;
        $this->showRejectConfirm = false;
    }

    public function confirmReject()
    {
        $this->showRejectConfirm = true;
    }

    public function cancelReject()
    {
        $this->showRejectConfirm = false;
    }

    public function approvePayment($id)
    {
        $this->verify($id, true);
    }

    public function rejectPayment($id)
    {
        $this->verify($id, false);
    }

    /**
     * Delegasikan penuh ke PaymentService::adminVerifyManualProof() (sudah
     * selesai & diuji sejak Tahap 2/3, cross-check status Midtrans otomatis
     * dijalankan di dalamnya kalau payment punya gateway_transaction_id).
     * Komponen ini TIDAK menduplikasi state-transition apa pun, murni UI.
     */
    private function verify($id, bool $approve): void
    {
        $payment = Payment::findOrFail($id);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $payment = app(PaymentService::class)->adminVerifyManualProof($payment, $admin, $approve);

            $this->success_message = $approve
                ? "Pembayaran order #{$payment->order_id} disetujui, pesanan akan mulai diproses."
                : "Bukti transfer order #{$payment->order_id} ditolak.";
        } catch (\Throwable $e) {
            Log::error('[ADMIN PAYMENT VERIFY] Gagal verifikasi: ' . $e->getMessage());
            $this->error_message = 'Gagal memproses verifikasi, silakan coba lagi.';
        }

        $this->selected_id = null;
        $this->showRejectConfirm = false;
    }

    /**
     * ===== BUGFIX =====
     * Sebelumnya halaman ini HANYA query model Payment (bukti transfer order),
     * jadi topup saldo e-wallet customer yang sudah upload bukti transfer
     * manual tidak pernah muncul di antrian admin — padahal customer di sisi
     * lain memang tidak diberi cara mengunggah bukti sama sekali (lihat
     * perbaikan di resources/views/livewire/customer/pay-topup-page.blade.php
     * & App\Livewire\Customer\PayTopupPage). Bagian di bawah ini menambahkan
     * antrian verifikasi topup terpisah, memakai state & pola yang sama
     * seperti verifikasi payment order di atas, cuma didelegasikan ke
     * WalletService::adminVerifyManualProof() alih-alih PaymentService.
     */
    public $selected_topup_id = null;

    public bool $showRejectConfirmTopup = false;

    public function selectTopup($id)
    {
        $this->selected_topup_id = $id;
        $this->success_message = '';
        $this->error_message = '';
        $this->showRejectConfirmTopup = false;
    }

    public function closeDetailTopup()
    {
        $this->selected_topup_id = null;
        $this->showRejectConfirmTopup = false;
    }

    public function confirmRejectTopup()
    {
        $this->showRejectConfirmTopup = true;
    }

    public function cancelRejectTopup()
    {
        $this->showRejectConfirmTopup = false;
    }

    public function approveTopup($id)
    {
        $this->verifyTopup($id, true);
    }

    public function rejectTopup($id)
    {
        $this->verifyTopup($id, false);
    }

    private function verifyTopup($id, bool $approve): void
    {
        $topup = Topup::findOrFail($id);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $topup = app(WalletService::class)->adminVerifyManualProof($topup, $admin, $approve);

            $this->success_message = $approve
                ? "Topup #{$topup->id} disetujui, saldo customer sudah ditambahkan."
                : "Bukti transfer topup #{$topup->id} ditolak.";
        } catch (\Throwable $e) {
            Log::error('[ADMIN TOPUP VERIFY] Gagal verifikasi: ' . $e->getMessage());
            $this->error_message = 'Gagal memproses verifikasi topup, silakan coba lagi.';
        }

        $this->selected_topup_id = null;
        $this->showRejectConfirmTopup = false;
    }
};
?>

<div class="space-y-6">
    @php
        $payments = \App\Models\Payment::with(['order.customer'])
            ->whereNotNull('proof_image')
            ->when($filter_status !== 'semua', function ($query) use ($filter_status) {
                $query->where('status', $filter_status);
            })
            ->when($search, function ($query) use ($search) {
                $query->whereHas('order', function ($q) use ($search) {
                    $q->where('id', 'like', '%' . $search . '%')
                      ->orWhereHas('customer', function ($qc) use ($search) {
                          $qc->where('name', 'like', '%' . $search . '%')
                             ->orWhere('phone_number', 'like', '%' . $search . '%');
                      });
                });
            })
            ->latest()
            ->get();

        $selected_payment = $selected_id
            ? \App\Models\Payment::with(['order.customer', 'order.jastiper'])->find($selected_id)
            : null;

        // BUGFIX: antrian verifikasi topup e-wallet, sebelumnya tidak ada sama
        // sekali di halaman ini. Status topup pakai kata 'berhasil' (bukan
        // 'lunas' seperti Payment), jadi tab filter yang sama (menunggu/lunas/
        // gagal/semua) dipetakan ke nilai status topup yang setara supaya satu
        // set tab bisa dipakai bareng untuk kedua tabel.
        $topupStatusMap = ['lunas' => 'berhasil'];
        $topupFilterStatus = $topupStatusMap[$filter_status] ?? $filter_status;

        $topups = \App\Models\Topup::with(['wallet.owner'])
            ->whereNotNull('proof_image')
            ->when($topupFilterStatus !== 'semua', function ($query) use ($topupFilterStatus) {
                $query->where('status', $topupFilterStatus);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', '%' . $search . '%')
                      ->orWhereHas('wallet', function ($qw) use ($search) {
                          $qw->whereHasMorph('owner', [\App\Models\Customer::class, \App\Models\Jastiper::class], function ($qo) use ($search) {
                              $qo->where('name', 'like', '%' . $search . '%')
                                 ->orWhere('phone_number', 'like', '%' . $search . '%');
                          });
                      });
                });
            })
            ->latest()
            ->get();

        $selected_topup = $selected_topup_id
            ? \App\Models\Topup::with(['wallet.owner'])->find($selected_topup_id)
            : null;
    @endphp

    <!-- Konten Dashboard Admin -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-sm">

        <!-- Header -->
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Antrian Verifikasi Pembayaran</h3>
            <p class="text-xs text-slate-400 mt-1">Review bukti transfer manual customer — baik untuk pesanan (order) maupun top up saldo e-wallet. Cross-check status Midtrans otomatis dijalankan sebelum approve (kalau customer sempat mencoba jalur VA/QRIS).</p>
        </div>

        <!-- Alerts -->
        @if ($success_message)
            <div class="mb-6 bg-emerald-50 border border-emerald-100 p-4 text-emerald-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $success_message }}</span>
            </div>
        @endif

        @if ($error_message)
            <div class="mb-6 bg-rose-50 border border-rose-100 p-4 text-rose-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ $error_message }}</span>
            </div>
        @endif

        <!-- Filter & Search Panel -->
        <div class="flex flex-col xl:flex-row gap-4 items-stretch xl:items-center justify-between mb-6">
            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2">
                <button
                    wire:click="$set('filter_status', 'menunggu')"
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'menunggu' ? 'background-color: #f59e0b; border-color: #f59e0b; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    Menunggu Review
                </button>
                <button
                    wire:click="$set('filter_status', 'lunas')"
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'lunas' ? 'background-color: #10b981; border-color: #10b981; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    Lunas
                </button>
                <button
                    wire:click="$set('filter_status', 'gagal')"
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'gagal' ? 'background-color: #e11d48; border-color: #e11d48; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    Ditolak
                </button>
                <button
                    wire:click="$set('filter_status', 'semua')"
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'semua' ? 'background-color: #0f172a; border-color: #0f172a; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    Semua
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative flex-grow max-w-md">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari berdasarkan #order, nama, atau no HP customer..."
                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 pl-10 pr-4 py-2 rounded-full text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150"
                >
                <div class="absolute left-3.5 top-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Tabel Antrian Pembayaran Order -->
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Pembayaran Order</p>
        <div class="overflow-x-auto border border-slate-200 rounded-2xl shadow-sm">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Order & Customer</th>
                        <th class="px-5 py-4">Nominal</th>
                        <th class="px-5 py-4">Metode Pembayaran</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($payments as $p)
                        <tr class="hover:bg-slate-50 transition duration-150">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-mono text-[11px] text-slate-500">{{ $p->created_at->format('d M Y') }}</span><br>
                                <span class="text-[10px] text-slate-400">{{ $p->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-sm text-slate-800">Order #{{ $p->order_id }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $p->order->customer->name ?? 'N/A' }} ({{ $p->order->customer->phone_number ?? '-' }})</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-700">Rp{{ number_format($p->amount, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-700">{{ strtoupper($p->method) }}{{ $p->channel ? ' · ' . strtoupper($p->channel) : '' }}</div>
                                @if($p->gateway_transaction_id)
                                    <div class="text-[10px] text-slate-400 mt-0.5">Gateway: Ada</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($p->status === 'menunggu')
                                    <span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Menunggu</span>
                                @elseif($p->status === 'lunas')
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Lunas</span>
                                @elseif($p->status === 'gagal')
                                    <span class="bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Gagal</span>
                                @elseif($p->status === 'kedaluwarsa')
                                    <span class="bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Kedaluwarsa</span>
                                @endif
                                
                                @if($p->status !== 'menunggu' && $p->verifiedByAdmin)
                                    <div class="text-[10px] text-slate-400 mt-2">
                                        Oleh: {{ $p->verifiedByAdmin->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button 
                                    type="button" 
                                    wire:click="selectPayment({{ $p->id }})" 
                                    class="bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2 rounded-lg text-[10px] uppercase transition shadow-sm"
                                >
                                    Review
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="w-12 h-12 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h4 class="font-bold text-sm text-slate-700">Tidak Ada Data</h4>
                                <p class="text-xs text-slate-500 mt-1">Belum ada bukti transfer manual dengan filter "{{ $filter_status }}".</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- BUGFIX: Tabel Antrian Verifikasi Top Up E-Wallet — sebelumnya tidak ada sama sekali di halaman ini -->
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2 mt-8">Top Up E-Wallet</p>
        <div class="overflow-x-auto border border-slate-200 rounded-2xl shadow-sm">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Topup & Customer</th>
                        <th class="px-5 py-4">Nominal</th>
                        <th class="px-5 py-4">Metode Pembayaran</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($topups as $t)
                        <tr class="hover:bg-slate-50 transition duration-150">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-mono text-[11px] text-slate-500">{{ $t->created_at->format('d M Y') }}</span><br>
                                <span class="text-[10px] text-slate-400">{{ $t->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-sm text-slate-800">Topup #{{ $t->id }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $t->wallet->owner->name ?? 'N/A' }} ({{ $t->wallet->owner->phone_number ?? '-' }})</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-700">Rp{{ number_format($t->amount, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-700">{{ strtoupper($t->method) }}{{ $t->channel ? ' · ' . strtoupper($t->channel) : '' }}</div>
                                @if($t->gateway_transaction_id)
                                    <div class="text-[10px] text-slate-400 mt-0.5">Gateway: Ada</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($t->status === 'menunggu')
                                    <span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Menunggu</span>
                                @elseif($t->status === 'berhasil')
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Berhasil</span>
                                @elseif($t->status === 'gagal')
                                    <span class="bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Gagal</span>
                                @endif

                                @if($t->status !== 'menunggu' && $t->verifiedByAdmin)
                                    <div class="text-[10px] text-slate-400 mt-2">
                                        Oleh: {{ $t->verifiedByAdmin->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    wire:click="selectTopup({{ $t->id }})"
                                    class="bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2 rounded-lg text-[10px] uppercase transition shadow-sm"
                                >
                                    Review
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="w-12 h-12 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h4 class="font-bold text-sm text-slate-700">Tidak Ada Data</h4>
                                <p class="text-xs text-slate-500 mt-1">Belum ada bukti transfer top up dengan filter "{{ $filter_status }}".</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- BUGFIX: MODAL DETAIL / AKSI VERIFIKASI TOP UP -->
    @if ($selected_topup)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-3xl w-full p-6 sm:p-8 space-y-6 relative shadow-lg animate-fade-in">

                <!-- Close Button -->
                <button
                    type="button"
                    wire:click="closeDetailTopup"
                    class="absolute top-5 right-5 text-slate-400 hover:text-slate-750 transition font-extrabold text-sm"
                >
                    ✕
                </button>

                <!-- Modal Header -->
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Review Bukti Transfer Top Up</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Topup #{{ $selected_topup->id }} —
                        <b>{{ $selected_topup->wallet->owner->name ?? 'N/A' }}</b>
                        ({{ $selected_topup->wallet->owner->phone_number ?? '-' }})
                    </p>
                </div>

                <!-- Detail Pembayaran -->
                <div class="space-y-2 text-xs">
                    <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider">Detail Pembayaran</label>
                    <div class="bg-[#F3F4F6] border border-slate-100 p-3.5 rounded-2xl space-y-1.5 text-slate-600">
                        <div>💰 Nominal: <b>Rp{{ number_format($selected_topup->amount, 0, ',', '.') }}</b></div>
                        <div>💳 Metode: <b>{{ strtoupper($selected_topup->method) }}{{ $selected_topup->channel ? ' · ' . strtoupper($selected_topup->channel) : '' }}</b></div>
                        @if($selected_topup->va_number)
                            <div>🏦 No. VA: <b>{{ $selected_topup->va_number }}</b></div>
                        @endif
                        @if($selected_topup->gateway_transaction_id)
                            <div>🔗 ID Transaksi Gateway: <b>{{ $selected_topup->gateway_transaction_id }}</b> — status akan di-cross-check otomatis ke Midtrans saat approve.</div>
                        @endif
                    </div>
                </div>

                <!-- Gambar Bukti Transfer -->
                <div class="space-y-2 text-center">
                    <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider text-left">Bukti Transfer</label>
                    <div class="border border-slate-200 rounded-3xl overflow-hidden p-2.5 bg-slate-50 shadow-inner">
                        @if(str_ends_with(strtolower($selected_topup->proof_image ?? ''), '.pdf'))
                            <a href="{{ asset('storage/' . $selected_topup->proof_image) }}" target="_blank" class="inline-flex items-center gap-2 text-rose-600 font-bold text-xs py-6">
                                📄 Buka Berkas PDF Bukti Transfer ↗
                            </a>
                        @else
                            <a href="{{ asset('storage/' . $selected_topup->proof_image) }}" target="_blank">
                                <img
                                    src="{{ asset('storage/' . $selected_topup->proof_image) }}"
                                    class="max-h-80 mx-auto object-contain hover:scale-105 transition duration-150 rounded-2xl"
                                    alt="Bukti transfer"
                                >
                            </a>
                        @endif
                    </div>
                    <span class="text-[10px] text-slate-400 block mt-1">Klik gambar untuk melihat resolusi penuh ↗</span>
                </div>

                <!-- Panel Aksi Evaluasi -->
                <div class="bg-slate-50 border border-slate-200 p-6 rounded-3xl space-y-4">
                    <h4 class="font-bold text-xs text-slate-705 uppercase tracking-wide">Evaluasi Admin JastipKuy</h4>

                    @if($selected_topup->status === 'menunggu')
                        @if(!$showRejectConfirmTopup)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    wire:click="approveTopup({{ $selected_topup->id }})"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center py-2.5 rounded-full transition shadow-md shadow-emerald-600/10 uppercase tracking-wider text-xs border border-emerald-500"
                                >
                                    SETUJUI & TAMBAH SALDO
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmRejectTopup"
                                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-2.5 rounded-full transition shadow-md shadow-rose-600/10 uppercase tracking-wider text-xs border border-rose-500"
                                >
                                    TOLAK BUKTI TRANSFER
                                </button>
                            </div>
                        @else
                            <div class="space-y-3">
                                <p class="text-xs text-rose-700 font-semibold bg-rose-50 border border-rose-100 p-3 rounded-2xl">
                                    ⚠️ Yakin tolak bukti transfer top up ini? Saldo customer TIDAK akan ditambahkan — customer masih bisa retry metode lain atau upload ulang bukti sebelum batas waktu pembayaran habis.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <button
                                        type="button"
                                        wire:click="rejectTopup({{ $selected_topup->id }})"
                                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-2.5 rounded-full transition uppercase tracking-wider text-xs border border-rose-500"
                                    >
                                        Ya, Tolak Bukti Ini
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="cancelRejectTopup"
                                        class="w-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-center py-2.5 rounded-full transition text-xs"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- Tampilan Jika Sudah Direview -->
                        <div class="flex justify-between items-center text-xs">
                            <div>
                                Status Saat Ini:
                                <span class="font-bold uppercase {{ $selected_topup->status === 'berhasil' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $selected_topup->status }}
                                </span>
                            </div>
                            <button
                                type="button"
                                wire:click="closeDetailTopup"
                                class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs px-6 py-2.5 rounded-full transition"
                            >
                                Tutup Halaman Detail
                            </button>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endif

    <!-- MODAL DETAIL / AKSI VERIFIKASI (TAMPIL DI LAYER ATAS) -->
    @if ($selected_payment)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-3xl w-full p-6 sm:p-8 space-y-6 relative shadow-lg animate-fade-in">

                <!-- Close Button -->
                <button
                    type="button"
                    wire:click="closeDetail"
                    class="absolute top-5 right-5 text-slate-400 hover:text-slate-750 transition font-extrabold text-sm"
                >
                    ✕
                </button>

                <!-- Modal Header -->
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Review Bukti Transfer</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Order #{{ $selected_payment->order_id }} —
                        <b>{{ $selected_payment->order->customer->name ?? 'N/A' }}</b>
                        ({{ $selected_payment->order->customer->phone_number ?? '-' }})
                    </p>
                </div>

                <!-- Ringkasan Order & Pembayaran -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Detail Order -->
                    <div class="space-y-2 text-xs">
                        <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider">Detail Pesanan</label>
                        <div class="bg-[#F3F4F6] border border-slate-100 p-3.5 rounded-2xl space-y-1.5 text-slate-600">
                            <div>📦 {{ $selected_payment->order->description ?? '-' }}</div>
                            <div>📍 Tujuan: {{ $selected_payment->order->destination_address ?? '-' }}</div>
                            <div>🚚 Jastiper: {{ $selected_payment->order->jastiper->name ?? 'Belum ditugaskan' }}</div>
                        </div>
                    </div>

                    <!-- Detail Pembayaran -->
                    <div class="space-y-2 text-xs">
                        <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider">Detail Pembayaran</label>
                        <div class="bg-[#F3F4F6] border border-slate-100 p-3.5 rounded-2xl space-y-1.5 text-slate-600">
                            <div>💰 Nominal: <b>Rp{{ number_format($selected_payment->amount, 0, ',', '.') }}</b></div>
                            <div>💳 Metode: <b>{{ strtoupper($selected_payment->method) }}{{ $selected_payment->channel ? ' · ' . strtoupper($selected_payment->channel) : '' }}</b></div>
                            @if($selected_payment->va_number)
                                <div>🏦 No. VA: <b>{{ $selected_payment->va_number }}</b></div>
                            @endif
                            @if($selected_payment->gateway_transaction_id)
                                <div>🔗 ID Transaksi Gateway: <b>{{ $selected_payment->gateway_transaction_id }}</b> — status akan di-cross-check otomatis ke Midtrans saat approve.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Gambar Bukti Transfer -->
                <div class="space-y-2 text-center">
                    <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider text-left">Bukti Transfer</label>
                    <div class="border border-slate-200 rounded-3xl overflow-hidden p-2.5 bg-slate-50 shadow-inner">
                        @if(str_ends_with(strtolower($selected_payment->proof_image ?? ''), '.pdf'))
                            <a href="{{ asset('storage/' . $selected_payment->proof_image) }}" target="_blank" class="inline-flex items-center gap-2 text-rose-600 font-bold text-xs py-6">
                                📄 Buka Berkas PDF Bukti Transfer ↗
                            </a>
                        @else
                            <a href="{{ asset('storage/' . $selected_payment->proof_image) }}" target="_blank">
                                <img
                                    src="{{ asset('storage/' . $selected_payment->proof_image) }}"
                                    class="max-h-80 mx-auto object-contain hover:scale-105 transition duration-150 rounded-2xl"
                                    alt="Bukti transfer"
                                >
                            </a>
                        @endif
                    </div>
                    <span class="text-[10px] text-slate-400 block mt-1">Klik gambar untuk melihat resolusi penuh ↗</span>
                </div>

                <!-- Panel Aksi Evaluasi -->
                <div class="bg-slate-50 border border-slate-200 p-6 rounded-3xl space-y-4">
                    <h4 class="font-bold text-xs text-slate-705 uppercase tracking-wide">Evaluasi Admin JastipKuy</h4>

                    @if($selected_payment->status === 'menunggu')
                        @if(!$showRejectConfirm)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    wire:click="approvePayment({{ $selected_payment->id }})"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center py-2.5 rounded-full transition shadow-md shadow-emerald-600/10 uppercase tracking-wider text-xs border border-emerald-500"
                                >
                                    SETUJUI (LUNAS) & MULAI PROSES
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmReject"
                                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-2.5 rounded-full transition shadow-md shadow-rose-600/10 uppercase tracking-wider text-xs border border-rose-500"
                                >
                                    TOLAK BUKTI TRANSFER
                                </button>
                            </div>
                        @else
                            <div class="space-y-3">
                                <p class="text-xs text-rose-700 font-semibold bg-rose-50 border border-rose-100 p-3 rounded-2xl">
                                    ⚠️ Yakin tolak bukti transfer ini? Order tetap "Menunggu Pembayaran" — customer masih bisa retry metode lain atau upload ulang bukti sebelum batas waktu pembayaran habis.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <button
                                        type="button"
                                        wire:click="rejectPayment({{ $selected_payment->id }})"
                                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-2.5 rounded-full transition uppercase tracking-wider text-xs border border-rose-500"
                                    >
                                        Ya, Tolak Bukti Ini
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="cancelReject"
                                        class="w-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-center py-2.5 rounded-full transition text-xs"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- Tampilan Jika Sudah Direview -->
                        <div class="flex justify-between items-center text-xs">
                            <div>
                                Status Saat Ini:
                                <span class="font-bold uppercase {{ $selected_payment->status === 'lunas' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $selected_payment->status }}
                                </span>
                            </div>
                            <button
                                type="button"
                                wire:click="closeDetail"
                                class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs px-6 py-2.5 rounded-full transition"
                            >
                                Tutup Halaman Detail
                            </button>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endif
</div>