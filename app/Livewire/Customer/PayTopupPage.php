<?php

namespace App\Livewire\Customer;

use App\Models\Topup;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * BUGFIX: halaman "Selesaikan Pembayaran" topup (GET
 * /customer/wallet/pay-topup/{id}) sebelumnya cuma blade statis
 * (resources/views/customer/pay-topup.blade.php) tanpa Livewire component di
 * belakangnya — makanya tidak ada aksi/tombol "Kirim Bukti Transfer" sama
 * sekali di halaman itu, beda dengan halaman pembayaran order yang sudah
 * punya lewat App\Livewire\Customer\PaymentPage. Komponen ini menutup gap
 * itu, meniru pola yang sama (mount by id + kepemilikan wallet, wire:poll
 * status, upload bukti via WithFileUploads) supaya konsisten dengan
 * pembayaran order.
 */
class PayTopupPage extends Component
{
    use WithFileUploads;

    public int $topupId;

    public $proof = null;

    public ?string $flashError = null;

    public ?string $flashSuccess = null;

    public ?string $snapToken = null;

    public bool $showMockSnapModal = false;

    public function mount(int $topupId): void
    {
        $this->topupId = $topupId;

        $topup = $this->resolveOwnedTopup();
        abort_if(!$topup, 404, 'Topup tidak ditemukan atau bukan milik Anda.');

        if ($topup->status === 'menunggu_pembayaran') {
            $this->snapToken = app(WalletService::class)->getOrCreateSnapToken($topup);
            // Langsung tampilkan modal Snap seketika saat halaman dibuka
            if (!filled(config('services.midtrans.client_key')) || str_starts_with($this->snapToken, 'MOCK-')) {
                $this->showMockSnapModal = true;
            }
        }
    }

    /**
     * Buka pembayaran Midtrans Snap.
     * Jika ada client_key Midtrans, buka Snap popup resmi.
     * Jika mode mock / tanpa key, buka modal interaktif replika Midtrans.
     */
    public function openMidtransSnap(): void
    {
        $topup = $this->resolveOwnedTopup();
        if (!$topup || $topup->status !== 'menunggu_pembayaran') {
            $this->flashError = 'Tagihan topup tidak dalam status menunggu pembayaran atau sudah selesai.';
            return;
        }

        $this->snapToken = app(WalletService::class)->getOrCreateSnapToken($topup);

        if (filled(config('services.midtrans.client_key')) && !str_starts_with($this->snapToken, 'MOCK-')) {
            $this->dispatch('open-midtrans-snap', ['token' => $this->snapToken]);
        } else {
            $this->showMockSnapModal = true;
        }
    }

    public function closeMockSnapModal(): void
    {
        $this->showMockSnapModal = false;
    }

    /**
     * Otomatis selesaikan pembayaran (dipanggil dari Snap success callback atau tombol simulasi).
     */
    public function markTopupSuccess(?string $channel = 'midtrans_snap', ?string $transactionId = null): void
    {
        $topup = $this->resolveOwnedTopup();
        if (!$topup) {
            return;
        }

        // Jika transaksi memiliki BCA VA di mode Sandbox, simulasikan pelunasan ke Midtrans Simulator
        if (!empty($topup->va_number) && !config('services.midtrans.is_production')) {
            try {
                app(\App\Services\MidtransGatewayService::class)->simulateBcaVaPayment($topup->va_number);
            } catch (\Throwable $e) {
                // Jangan gagalkan alur jika simulator eksternal timeout
            }
        }

        app(WalletService::class)->settleTopupAutomatically($topup, $channel, $transactionId);

        $this->showMockSnapModal = false;
        $this->flashSuccess = 'Pembayaran berhasil dikonfirmasi! Saldo telah masuk ke E-Wallet Anda.';
        $this->dispatch('topup-berhasil');
    }

    /**
     * Cek status pembayaran secara langsung ke server API Midtrans.
     * Sangat berguna saat di localhost karena webhook Midtrans cloud tidak bisa tembus ke localhost tanpa tunnel.
     */
    public function checkMidtransStatus(bool $isManual = true): void
    {
        $topup = $this->resolveOwnedTopup();
        if (!$topup || $topup->status === 'berhasil') {
            return;
        }

        if (!filled(config('services.midtrans.server_key'))) {
            if ($isManual) {
                $this->flashError = 'Server Key Midtrans belum dikonfigurasi.';
            }
            return;
        }

        $identifier = $topup->gateway_reference ?: $topup->gateway_transaction_id;
        if (!$identifier) {
            if ($isManual) {
                $this->flashError = 'ID Transaksi Midtrans belum terbit untuk top-up ini.';
            }
            return;
        }

        try {
            $status = app(\App\Services\MidtransGatewayService::class)->getStatus($identifier);
            $txStatus = $status['transaction_status'] ?? 'pending';

            if (in_array($txStatus, ['settlement', 'capture'], true)) {
                app(WalletService::class)->settleTopupAutomatically(
                    $topup,
                    $status['payment_type'] ?? 'midtrans',
                    $status['transaction_id'] ?? null
                );

                $this->flashSuccess = 'Pembayaran terverifikasi lunas dari Midtrans! Saldo telah masuk ke E-Wallet Anda.';
                $this->dispatch('topup-berhasil');
            } elseif ($isManual) {
                $channel = strtoupper($status['payment_type'] ?? 'PEMBAYARAN');
                $this->flashError = "Status di Midtrans saat ini: [{$txStatus}] ({$channel}). Silakan selesaikan pembayaran di simulator atau aplikasi e-wallet Anda terlebih dahulu.";
            }
        } catch (\Throwable $e) {
            if ($isManual) {
                $this->flashError = 'Gagal memeriksa status ke Midtrans: ' . $e->getMessage();
            }
        }
    }

    /**
     * Dipanggil tiap 3 detik lewat wire:poll.3s, sama seperti PaymentPage,
     * supaya customer otomatis diarahkan balik ke wallet begitu topup
     * berhasil (baik lewat webhook otomatis maupun disetujui admin).
     */
    public function refreshStatus(): void
    {
        $topup = $this->resolveOwnedTopup();

        if (!$topup) {
            return;
        }

        if ($topup->status === 'berhasil') {
            $this->dispatch('topup-berhasil');
            return;
        }

        // Jika topup masih menunggu dan Server Key aktif, coba sinkronisasi otomatis ke Midtrans
        if (in_array($topup->status, ['menunggu', 'menunggu_pembayaran'], true) && filled(config('services.midtrans.server_key'))) {
            $this->checkMidtransStatus(false);
        }
    }

    public function uploadProof(): void
    {
        $this->resetFlash();

        $this->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [], ['proof' => 'bukti transfer']);

        $topup = $this->resolveOwnedTopup();

        if (!$topup || !in_array($topup->status, ['menunggu', 'menunggu_pembayaran', 'menunggu_verifikasi_bukti'])) {
            $this->flashError = 'Tidak ada topup yang siap menerima pembayaran — mungkin belum di-approve admin, sudah berhasil, atau kedaluwarsa.';
            return;
        }

        app(WalletService::class)->submitManualProof($topup, $this->proof);

        $this->proof = null;
        $this->flashSuccess = 'Bukti transfer berhasil diunggah, menunggu verifikasi admin. Saldo akan otomatis masuk begitu disetujui.';
    }

    public function render()
    {
        $topup = $this->resolveOwnedTopup();
        abort_if(!$topup, 404, 'Topup tidak ditemukan atau bukan milik Anda.');

        return view('livewire.customer.pay-topup-page', [
            'topup' => $topup,
        ]);
    }

    private function resolveOwnedTopup(): ?Topup
    {
        $customer = Auth::guard('customer')->user();

        return Topup::where('id', $this->topupId)
            ->whereHas('wallet', function ($q) use ($customer) {
                $q->where('owner_role', 'customer')->where('owner_id', $customer->id);
            })
            ->first();
    }

    private function resetFlash(): void
    {
        $this->flashError = null;
        $this->flashSuccess = null;
    }
}