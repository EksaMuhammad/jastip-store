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

    public function mount(int $topupId): void
    {
        $this->topupId = $topupId;

        $topup = $this->resolveOwnedTopup();
        abort_if(!$topup, 404, 'Topup tidak ditemukan atau bukan milik Anda.');
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
        }
    }

    public function uploadProof(): void
    {
        $this->resetFlash();

        $this->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [], ['proof' => 'bukti transfer']);

        $topup = $this->resolveOwnedTopup();

        if (!$topup || $topup->status !== 'menunggu') {
            $this->flashError = 'Tidak ada topup yang menunggu pembayaran — mungkin sudah berhasil, gagal, atau kedaluwarsa.';
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