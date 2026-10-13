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
    public string $topup_reject_reason = '';

    public function selectTopup($id)
    {
        $this->selected_topup_id = $id;
        $this->success_message = '';
        $this->error_message = '';
        $this->showRejectConfirmTopup = false;
        $this->topup_reject_reason = '';
    }

    public function closeDetailTopup()
    {
        $this->selected_topup_id = null;
        $this->showRejectConfirmTopup = false;
        $this->topup_reject_reason = '';
    }

    public function confirmRejectTopup()
    {
        $this->showRejectConfirmTopup = true;
    }

    public function cancelRejectTopup()
    {
        $this->showRejectConfirmTopup = false;
        $this->topup_reject_reason = '';
    }

    /**
     * TAHAP 1: ACC Pengajuan Top-Up (Buka kode pembayaran / QRIS untuk customer)
     */
    public function approveTopupRequest($id)
    {
        $topup = Topup::findOrFail($id);
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            app(WalletService::class)->approveTopupRequest($topup, $admin);
            $this->success_message = "Pengajuan Topup #{$topup->id} disetujui! Kode pembayaran QRIS/VA telah dibuka untuk customer.";
        } catch (\Throwable $e) {
            Log::error('[ADMIN TOPUP APPROVE REQUEST] ' . $e->getMessage());
            $this->error_message = 'Gagal memproses persetujuan pengajuan topup: ' . $e->getMessage();
        }

        $this->selected_topup_id = null;
        $this->showRejectConfirmTopup = false;
    }

    /**
     * TAHAP 1: Tolak Pengajuan Top-Up
     */
    public function rejectTopupRequest($id)
    {
        $topup = Topup::findOrFail($id);
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            app(WalletService::class)->rejectTopupRequest($topup, $admin, $this->topup_reject_reason);
            $this->success_message = "Pengajuan Topup #{$topup->id} telah ditolak.";
        } catch (\Throwable $e) {
            Log::error('[ADMIN TOPUP REJECT REQUEST] ' . $e->getMessage());
            $this->error_message = 'Gagal menolak pengajuan topup: ' . $e->getMessage();
        }

        $this->selected_topup_id = null;
        $this->showRejectConfirmTopup = false;
        $this->topup_reject_reason = '';
    }

    /**
     * TAHAP 2: ACC Pembayaran / Bukti Transfer (Menambah saldo ke customer)
     */
    public function approveTopup($id)
    {
        $this->verifyTopup($id, true);
    }

    /**
     * TAHAP 2: Tolak Bukti Pembayaran
     */
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
                ? "Pembayaran Topup #{$topup->id} disetujui (ACC ke-2)! Saldo Rp" . number_format($topup->amount, 0, ',', '.') . " berhasil ditambahkan ke wallet customer."
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
        // Antrian verifikasi topup e-wallet 2 Tahap:
        // Tahap 1: Pengajuan baru (menunggu_approval) -> ACC Admin 1
        // Tahap 2: Bukti transfer diunggah / menunggu dicek (menunggu_verifikasi_bukti / menunggu) -> ACC Admin 2
        $topups = \App\Models\Topup::with(['wallet.owner', 'approvedByAdmin', 'verifiedByAdmin'])
            ->when($filter_status === 'menunggu', function ($query) {
                $query->whereIn('status', ['menunggu_approval', 'menunggu_pembayaran', 'menunggu_verifikasi_bukti', 'menunggu']);
            })
            ->when($filter_status === 'lunas', function ($query) {
                $query->where('status', 'berhasil');
            })
            ->when($filter_status === 'gagal', function ($query) {
                $query->whereIn('status', ['gagal', 'ditolak_pengajuan', 'kedaluwarsa']);
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
            ? \App\Models\Topup::with(['wallet.owner', 'approvedByAdmin', 'verifiedByAdmin'])->find($selected_topup_id)
            : null;
    @endphp

    <!-- Konten Dashboard Admin -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-sm">

        <!-- Header -->
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Antrian Verifikasi Pembayaran & Top Up</h3>
            <p class="text-xs text-slate-400 mt-1">Sistem Verifikasi 2 Tahap: Review pengajuan awal top up customer (ACC Buka Pembayaran), dilanjutkan pengecekan bukti transfer manual / Midtrans (ACC Tambah Saldo).</p>
        </div>

        <!-- Alerts -->
        @if ($success_message)
            <div class="mb-6 bg-emerald-50 border border-emerald-100 p-4 text-emerald-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $success_message }}</span>
            </div>
        @endif

        @if ($error_message)
            <div class="mb-6 bg-rose-50 border border-rose-100 p-4 text-rose-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
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
                    Lunas / Berhasil
                </button>
                <button
                    wire:click="$set('filter_status', 'gagal')"
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'gagal' ? 'background-color: #e11d48; border-color: #e11d48; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    Ditolak / Gagal
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

        <!-- Tabel Antrian Verifikasi Top Up E-Wallet (2 Tahap) -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-black text-rose-600 uppercase tracking-wider">Top Up E-Wallet Customer (Alur 2 Tahap: Pengajuan & Pembayaran)</p>
                <span class="text-[10px] text-slate-400 font-semibold">{{ $topups->count() }} Permintaan</span>
            </div>
            <div class="overflow-x-auto border border-slate-200 rounded-2xl shadow-sm">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-4">Tanggal</th>
                            <th class="px-5 py-4">Topup & Customer</th>
                            <th class="px-5 py-4">Nominal</th>
                            <th class="px-5 py-4">Metode & Bukti</th>
                            <th class="px-5 py-4">Status & Tahapan</th>
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
                                    @if($t->proof_image)
                                        <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">✓ Bukti Transfer Ada</div>
                                    @elseif($t->status === 'berhasil' && !$t->verified_by_admin_id)
                                        <div class="text-[10px] text-sky-600 font-bold mt-0.5 flex items-center gap-1">
                                            <span>⚡</span> Instant Gateway Midtrans
                                        </div>
                                    @else
                                        <div class="text-[10px] text-slate-400 mt-0.5">Belum upload bukti</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($t->status === 'menunggu_approval')
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">
                                            Tahap 1: Menunggu ACC Pengajuan
                                        </span>
                                    @elseif($t->status === 'menunggu_pembayaran')
                                        <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                                            Menunggu Customer Bayar
                                        </span>
                                    @elseif($t->status === 'menunggu_verifikasi_bukti' || $t->status === 'menunggu')
                                        <span class="bg-purple-100 text-purple-800 border border-purple-300 text-[10px] font-black px-2.5 py-1 rounded-full uppercase">
                                            Tahap 2: Menunggu ACC Pembayaran
                                        </span>
                                    @elseif($t->status === 'berhasil')
                                        @if($t->verified_by_admin_id)
                                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase inline-flex items-center gap-1">
                                                ✓ Lunas (ACC Manual Admin)
                                            </span>
                                        @else
                                            <span class="bg-sky-50 text-sky-700 border border-sky-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase inline-flex items-center gap-1 shadow-xs">
                                                <span class="text-xs">⚡</span> Lunas (Midtrans Callback)
                                            </span>
                                        @endif
                                    @elseif($t->status === 'ditolak_pengajuan')
                                        <span class="bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                                            Pengajuan Ditolak
                                        </span>
                                    @elseif($t->status === 'gagal')
                                        <span class="bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                                            Gagal / Ditolak
                                        </span>
                                    @else
                                        <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">
                                            {{ strtoupper($t->status) }}
                                        </span>
                                    @endif

                                    @if($t->approvedByAdmin)
                                        <div class="text-[10px] text-slate-500 mt-1 flex items-center gap-1">
                                            <span class="text-slate-400 font-medium">ACC 1:</span>
                                            <span class="font-semibold text-slate-700">{{ $t->approvedByAdmin->name }}</span>
                                        </div>
                                    @endif

                                    @if($t->verifiedByAdmin)
                                        <div class="text-[10px] text-slate-500 mt-0.5 flex items-center gap-1">
                                            <span class="text-slate-400 font-medium">ACC 2:</span>
                                            <span class="font-semibold text-slate-700">{{ $t->verifiedByAdmin->name }} (Manual)</span>
                                        </div>
                                    @elseif($t->status === 'berhasil')
                                        <div class="text-[10px] text-sky-700 font-bold mt-1 inline-flex items-center gap-1 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-md">
                                            <span>⚡</span>
                                            <span>ACC 2: Callback Midtrans (Otomatis)</span>
                                        </div>
                                        @if($t->gateway_transaction_id)
                                            <div class="text-[9px] font-mono text-slate-400 mt-0.5 truncate max-w-[210px]" title="{{ $t->gateway_transaction_id }}">
                                                ID: {{ \Illuminate\Support\Str::limit($t->gateway_transaction_id, 20) }}
                                            </div>
                                        @endif
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
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-xs text-slate-700">Tidak Ada Permintaan Top Up</h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Belum ada data top up dengan status filter "{{ $filter_status }}".</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Antrian Pembayaran Order -->
        <p class="text-[11px] font-black text-slate-400 uppercase tracking-wider mb-2">Pembayaran Pesanan / Jastip Order</p>
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
                                @elseif($p->status === 'lunas' && !$p->verified_by_admin_id)
                                    <div class="text-[10px] text-sky-600 font-bold mt-2 flex items-center gap-1">
                                        <span>⚡</span> Midtrans Callback (Otomatis)
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
                                <p class="text-xs text-slate-500 mt-1">Belum ada bukti transfer manual pesanan dengan filter "{{ $filter_status }}".</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- MODAL DETAIL / AKSI VERIFIKASI TOP UP (2 TAHAP) -->
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
                    @if($selected_topup->status === 'menunggu_approval')
                        <div class="inline-block bg-amber-100 text-amber-800 text-[10px] font-black uppercase px-3 py-1 rounded-full mb-2">
                            Tahap 1: Verifikasi Permintaan Top Up
                        </div>
                        <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Persetujuan Pengajuan Top Up</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Sebelum customer melakukan pembayaran / mendapatkan kode bayar QRIS, Admin menyetujui atau menolak permohonan ini.
                        </p>
                    @elseif($selected_topup->status === 'berhasil')
                        @if($selected_topup->verified_by_admin_id)
                            <div class="inline-block bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase px-3 py-1 rounded-full mb-2">
                                Status: Berhasil (ACC Manual Admin)
                            </div>
                            <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Top Up Berhasil Disetujui</h3>
                        @else
                            <div class="inline-flex items-center gap-1.5 bg-sky-100 text-sky-800 text-[10px] font-black uppercase px-3 py-1 rounded-full mb-2">
                                <span>⚡</span> ACC 2 Selesai Otomatis: Midtrans Callback
                            </div>
                            <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Top Up Lunas via Callback Midtrans</h3>
                        @endif
                        <p class="text-xs text-slate-400 mt-1">
                            Rincian status verifikasi 2 tahap dan mutasi saldo dompet customer.
                        </p>
                    @else
                        <div class="inline-block bg-purple-100 text-purple-800 text-[10px] font-black uppercase px-3 py-1 rounded-full mb-2">
                            Tahap 2: Verifikasi Pembayaran Top Up
                        </div>
                        <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Review Pembayaran Top Up Saldo</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Periksa bukti transfer atau status pembayaran. Saat disetujui, saldo e-wallet customer otomatis bertambah.
                        </p>
                    @endif
                </div>

                <!-- Info Customer & Topup -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="bg-[#F8FAFC] border border-slate-100 p-4 rounded-2xl space-y-2">
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider">Pemilik Dompet</label>
                        <div class="font-bold text-slate-800 text-sm">{{ $selected_topup->wallet->owner->name ?? 'N/A' }}</div>
                        <div class="text-slate-500">No. HP: <b>{{ $selected_topup->wallet->owner->phone_number ?? '-' }}</b></div>
                        <div class="text-slate-500">Saldo Saat Ini: <b>Rp{{ number_format($selected_topup->wallet->balance ?? 0, 0, ',', '.') }}</b></div>
                    </div>

                    <div class="bg-[#F8FAFC] border border-slate-100 p-4 rounded-2xl space-y-2">
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider">Rincian Topup #{{ $selected_topup->id }}</label>
                        <div>💰 Nominal Top Up: <b class="text-rose-600 text-sm">Rp{{ number_format($selected_topup->amount, 0, ',', '.') }}</b></div>
                        <div>💳 Jalur Pembayaran: <b>{{ strtoupper($selected_topup->method) }}{{ $selected_topup->channel ? ' · ' . strtoupper($selected_topup->channel) : '' }}</b></div>
                        <div>⏱️ Dibuat: <b>{{ $selected_topup->created_at->format('d M Y, H:i') }}</b></div>
                        @if($selected_topup->gateway_reference)
                            <div class="text-[11px] text-slate-500 font-mono">Ref Order: <b>{{ $selected_topup->gateway_reference }}</b></div>
                        @endif
                        @if($selected_topup->gateway_transaction_id)
                            <div class="text-[11px] text-slate-500 font-mono">Trx Midtrans: <b>{{ $selected_topup->gateway_transaction_id }}</b></div>
                        @endif

                        {{-- Riwayat ACC Tahap 1 --}}
                        @if($selected_topup->approvedByAdmin)
                            <div class="text-emerald-700 bg-emerald-50 p-2 rounded-xl border border-emerald-100 text-[11px]">
                                <span class="font-bold">✓ ACC Pengajuan (Tahap 1):</span><br>
                                Disetujui oleh: <b>{{ $selected_topup->approvedByAdmin->name }}</b>
                                <span class="text-slate-500">({{ $selected_topup->approved_at ? $selected_topup->approved_at->format('d/m/Y H:i') : '-' }})</span>
                            </div>
                        @endif

                        {{-- Riwayat ACC Tahap 2 --}}
                        @if($selected_topup->verifiedByAdmin)
                            <div class="text-emerald-700 bg-emerald-50 p-2 rounded-xl border border-emerald-100 text-[11px]">
                                <span class="font-bold">✓ ACC Pembayaran (Tahap 2):</span><br>
                                Diverifikasi oleh: <b>{{ $selected_topup->verifiedByAdmin->name }}</b> (Manual)
                                <span class="text-slate-500">({{ $selected_topup->verified_at ? $selected_topup->verified_at->format('d/m/Y H:i') : '-' }})</span>
                            </div>
                        @elseif($selected_topup->status === 'berhasil')
                            <div class="text-sky-800 bg-sky-50 p-2.5 rounded-xl border border-sky-200 text-[11px] space-y-1">
                                <div class="font-extrabold flex items-center gap-1.5 text-sky-900">
                                    <span class="text-sm">⚡</span>
                                    <span>ACC Pembayaran (Tahap 2): Selesai Otomatis</span>
                                </div>
                                <div class="text-sky-700 text-[10px]">
                                    Diselesaikan oleh: <b>Callback Payment Gateway Midtrans</b>
                                </div>
                                <div class="text-sky-700 text-[10px]">
                                    Waktu Callback: <b>{{ $selected_topup->verified_at ? $selected_topup->verified_at->format('d M Y, H:i:s') : '-' }}</b>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Gambar Bukti Transfer (Jika Tahap 2) -->
                @if($selected_topup->proof_image)
                    <div class="space-y-2 text-center">
                        <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider text-left">Bukti Pembayaran / Transfer Customer</label>
                        <div class="border border-slate-200 rounded-3xl overflow-hidden p-2.5 bg-slate-50 shadow-inner">
                            @if(str_ends_with(strtolower($selected_topup->proof_image ?? ''), '.pdf'))
                                <a href="{{ asset('storage/' . $selected_topup->proof_image) }}" target="_blank" class="inline-flex items-center gap-2 text-rose-600 font-bold text-xs py-6">
                                    📄 Buka Berkas PDF Bukti Transfer ↗
                                </a>
                            @else
                                <a href="{{ asset('storage/' . $selected_topup->proof_image) }}" target="_blank">
                                    <img
                                        src="{{ asset('storage/' . $selected_topup->proof_image) }}"
                                        class="max-h-72 mx-auto object-contain hover:scale-105 transition duration-150 rounded-2xl"
                                        alt="Bukti transfer"
                                    >
                                </a>
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-1">Klik gambar untuk membuka tampilan penuh ↗</span>
                    </div>
                @elseif($selected_topup->status === 'berhasil' && !$selected_topup->verified_by_admin_id)
                    <div class="bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 rounded-2xl p-4 text-center space-y-1.5">
                        <div class="w-8 h-8 rounded-full bg-sky-500 text-white flex items-center justify-center mx-auto text-sm shadow-xs font-bold">
                            ⚡
                        </div>
                        <div class="font-bold text-xs text-sky-950 uppercase tracking-wide">
                            Pembayaran Otomatis Midtrans Snap
                        </div>
                        <p class="text-[11px] text-slate-600 max-w-md mx-auto leading-relaxed">
                            Customer melunasi tagihan secara instan via <b>Midtrans Snap</b> (QRIS / GoPay / VA). Sistem Midtrans telah memvalidasi settlement dan mengeksekusi callback otomatis (ACC Tahap 2) tanpa perlu upload struk transfer manual.
                        </p>
                    </div>
                @elseif($selected_topup->status === 'menunggu_approval')
                    <div class="bg-amber-50/70 border border-amber-200 p-4 rounded-2xl text-xs text-amber-800 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <span>ℹ️</span> Tahap 1: Pengajuan Belum Melakukan Pembayaran
                        </div>
                        <p class="text-amber-700">
                            Customer baru mengajukan permohonan isi saldo sebesar <b>Rp{{ number_format($selected_topup->amount, 0, ',', '.') }}</b>.
                            Jika disetujui, sistem akan membuka invoice, mengenerate QRIS/VA atau rekening transfer, dan customer diarahkan untuk membayar.
                        </p>
                    </div>
                @endif

                <!-- Panel Aksi Evaluasi Berdasarkan Tahapan -->
                <div class="bg-slate-50 border border-slate-200 p-6 rounded-3xl space-y-4">
                    <h4 class="font-bold text-xs text-slate-700 uppercase tracking-wide">Aksi Keputusan Admin</h4>

                    {{-- TAHAP 1: AKSI PENGAJUAN AWAL --}}
                    @if($selected_topup->status === 'menunggu_approval')
                        @if(!$showRejectConfirmTopup)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    wire:click="approveTopupRequest({{ $selected_topup->id }})"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center py-3 rounded-full transition shadow-md shadow-emerald-600/10 uppercase tracking-wider text-xs border border-emerald-500"
                                >
                                    ✓ SETUJUI PENGAJUAN (BUKA PEMBAYARAN)
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmRejectTopup"
                                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-3 rounded-full transition shadow-md shadow-rose-600/10 uppercase tracking-wider text-xs border border-rose-500"
                                >
                                    ✕ TOLAK PENGAJUAN TOP UP
                                </button>
                            </div>
                        @else
                            <div class="space-y-3">
                                <p class="text-xs text-rose-700 font-semibold bg-rose-50 border border-rose-100 p-3 rounded-2xl">
                                    ⚠️ Masukkan alasan penolakan pengajuan top up ini (akan diberitahukan ke customer):
                                </p>
                                <textarea
                                    wire:model="topup_reject_reason"
                                    rows="2"
                                    placeholder="Contoh: Nominal tidak sesuai ketentuan, akun sedang dibatasi, dll..."
                                    class="w-full text-xs p-3 rounded-xl border border-rose-200 focus:outline-none focus:border-rose-500 bg-white"
                                ></textarea>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <button
                                        type="button"
                                        wire:click="rejectTopupRequest({{ $selected_topup->id }})"
                                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-2.5 rounded-full transition uppercase tracking-wider text-xs border border-rose-500"
                                    >
                                        Konfirmasi Tolak Pengajuan
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

                    {{-- TAHAP 2: AKSI SETELAH CUSTOMER MEMBAYAR / MENUNGGU VERIFIKASI BUKTI --}}
                    @elseif(in_array($selected_topup->status, ['menunggu_verifikasi_bukti', 'menunggu_pembayaran', 'menunggu']))
                        @if(!$showRejectConfirmTopup)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    wire:click="approveTopup({{ $selected_topup->id }})"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center py-3 rounded-full transition shadow-md shadow-emerald-600/10 uppercase tracking-wider text-xs border border-emerald-500"
                                >
                                    ✓ SETUJUI & TAMBAH SALDO (ACC TAHAP 2)
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmRejectTopup"
                                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-3 rounded-full transition shadow-md shadow-rose-600/10 uppercase tracking-wider text-xs border border-rose-500"
                                >
                                    ✕ TOLAK BUKTI PEMBAYARAN
                                </button>
                            </div>
                        @else
                            <div class="space-y-3">
                                <p class="text-xs text-rose-700 font-semibold bg-rose-50 border border-rose-100 p-3 rounded-2xl">
                                    ⚠️ Yakin tolak bukti pembayaran top up ini? Saldo customer TIDAK akan ditambahkan.
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

                    {{-- STATUS SUDAH FINAL: BERHASIL --}}
                    @elseif($selected_topup->status === 'berhasil')
                        <div class="space-y-4">
                            @if(!$selected_topup->verified_by_admin_id)
                                <div class="bg-sky-50 border border-sky-200 p-4 rounded-2xl space-y-2">
                                    <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                                        <svg class="w-5 h-5 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>KETERANGAN ADMIN: TAHAP 2 SELESAI OTOMATIS (MIDTRANS CALLBACK)</span>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed">
                                        Top up ini <b>tidak membutuhkan persetujuan manual (ACC 2) dari Admin</b> karena pembayaran telah dikonfirmasi sah secara otomatis oleh Payment Gateway Midtrans. Saldo <b>Rp{{ number_format($selected_topup->amount, 0, ',', '.') }}</b> sudah langsung masuk ke E-Wallet customer.
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-sky-100 text-[11px] font-mono text-slate-600">
                                        <div>ID Transaksi: <b class="text-slate-800">{{ $selected_topup->gateway_transaction_id ?? '-' }}</b></div>
                                        <div>Saluran: <b class="text-slate-800">{{ strtoupper($selected_topup->channel ?? 'MIDTRANS SNAP') }}</b></div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl text-xs text-emerald-800 space-y-1">
                                    <div class="font-bold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Top Up Disetujui Secara Manual oleh Admin</span>
                                    </div>
                                    <p class="text-emerald-700">
                                        Disetujui oleh: <b>{{ $selected_topup->verifiedByAdmin->name }}</b> pada {{ $selected_topup->verified_at ? $selected_topup->verified_at->format('d M Y, H:i') : '-' }}. Saldo telah ditambahkan ke dompet customer.
                                    </p>
                                </div>
                            @endif

                            <div class="flex justify-between items-center text-xs pt-1">
                                <span class="text-slate-400 font-semibold">Status Akhir: <b class="text-emerald-600 uppercase">BERHASIL / LUNAS</b></span>
                                <button
                                    type="button"
                                    wire:click="closeDetailTopup"
                                    class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs px-6 py-2.5 rounded-full transition shadow-sm"
                                >
                                    Tutup Halaman Detail
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-center">
                                <div>
                                    Status Topup:
                                    <span class="font-black uppercase text-rose-600">
                                        {{ $selected_topup->status }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    wire:click="closeDetailTopup"
                                    class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs px-6 py-2.5 rounded-full transition"
                                >
                                    Tutup
                                </button>
                            </div>
                            @if($selected_topup->rejection_note)
                                <div class="bg-rose-50 border border-rose-100 text-rose-700 p-3 rounded-xl mt-2">
                                    <b>Catatan Penolakan:</b> {{ $selected_topup->rejection_note }}
                                </div>
                            @endif
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