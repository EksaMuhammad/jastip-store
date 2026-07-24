<?php

namespace App\Livewire\Customer;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Halaman utama pembayaran (Tahap 5) — BRIEF §3 Tahap 5.
 *
 * Full-page Livewire component untuk GET /customer/orders/{id}/payment.
 * Menyatukan seluruh sub-state UI pembayaran: pilih metode -> (kalau transfer)
 * pilih bank/QRIS -> tampilkan VA number/QR -> countdown deadline -> polling
 * status -> auto-redirect begitu Order.status = diproses. Juga menyediakan
 * retry upload bukti manual dan batalkan order manual (customer-initiated,
 * beda dari auto-cancel scheduler Tahap 4).
 *
 * KEPUTUSAN DESAIN (dicatat di BRIEF, lihat progress log Tahap 5): komponen
 * ini memanggil App\Services\PaymentService LANGSUNG (bukan lewat fetch/HTTP
 * ke App\Http\Controllers\PaymentController seperti disebut brief §3 secara
 * literal). Alasannya: Livewire component sudah berjalan di server yang sama
 * dalam satu request/response lifecycle Livewire sendiri — membuatnya
 * memanggil endpoint HTTP dirinya sendiri lewat fetch() cuma menambah round
 * trip tanpa manfaat (tidak ada proses lain di antaranya, tidak ada
 * kebutuhan cross-origin). PaymentController (Tahap 3) TETAP dipertahankan
 * apa adanya untuk kebutuhan lain (klien non-JS / integrasi luar / mobile
 * app di masa depan) dan tetap tercakup oleh PaymentWebhookTest &
 * PaymentManualVerificationTest yang sudah ada. Otorisasi & resolusi
 * "payment menunggu terbaru" di komponen ini sengaja meniru logic privat
 * yang sama di PaymentController (resolveOwnedOrder/resolvePendingPayment)
 * supaya perilakunya identik.
 */
class PaymentPage extends Component
{
    use WithFileUploads;

    public int $orderId;

    public ?int $paymentId = null;

    /** @var string 'pilih_metode' | 'pilih_bank' | 'tampil_va' | 'tampil_qris' | 'upload_bukti' */
    public string $step = 'pilih_metode';

    public ?string $selectedBank = null;

    public $proof = null;

    public ?string $flashError = null;

    public ?string $flashSuccess = null;

    public bool $showCancelConfirm = false;

    private const SUPPORTED_BANKS = ['bca', 'bni', 'bri', 'permata', 'mandiri'];

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;

        $order = $this->resolveOwnedOrder();
        abort_if(!$order, 404, 'Order tidak ditemukan atau bukan milik Anda.');

        $payment = Payment::where('order_id', $order->id)->latest('id')->first();
        $this->paymentId = $payment?->id;

        if ($payment && in_array($payment->status, ['menunggu'], true) && $payment->channel) {
            $this->step = $payment->channel === 'qris' ? 'tampil_qris' : 'tampil_va';
            $this->selectedBank = $this->extractBankFromRaw($payment);
        }
    }

    /**
     * Dipanggil tiap 3 detik lewat wire:poll.3s (brief §0 keputusan #5: polling,
     * karena BROADCAST_CONNECTION=log, belum ada Pusher/Reverb). Server tetap
     * source of truth untuk countdown deadline (Alpine di view cuma menghitung
     * mundur secara visual di antara 2 polling).
     */
    public function refreshStatus(): void
    {
        $order = $this->resolveOwnedOrder();

        if (!$order) {
            return;
        }

        if ($order->status === 'diproses') {
            $this->dispatch('order-diproses');
            return;
        }

        if ($order->status === 'dibatalkan') {
            $this->dispatch('order-dibatalkan');
        }
    }

    public function selectChannel(string $channel): void
    {
        $this->resetFlash();

        if (!in_array($channel, ['bank_transfer_va', 'qris'], true)) {
            $this->flashError = 'Metode pembayaran tidak dikenal.';
            return;
        }

        if ($channel === 'qris') {
            $this->processQris();
            return;
        }

        $this->step = 'pilih_bank';
    }

    public function chooseBank(string $bank): void
    {
        $this->resetFlash();

        if (!in_array($bank, self::SUPPORTED_BANKS, true)) {
            $this->flashError = 'Bank tidak didukung.';
            return;
        }

        $payment = $this->requirePendingPayment();
        if (!$payment) {
            return;
        }

        try {
            $payment = app(PaymentService::class)->payWithGatewayChannel($payment, 'bank_transfer_va', $bank);
        } catch (\Throwable $e) {
            $this->flashError = 'Gagal membuat Virtual Account, silakan coba lagi.';
            return;
        }

        $this->paymentId = $payment->id;
        $this->selectedBank = $bank;
        $this->step = 'tampil_va';
    }

    public function backToMethod(): void
    {
        $this->resetFlash();
        $this->step = 'pilih_metode';
        $this->selectedBank = null;
    }

    public function payWithWallet(): void
    {
        $this->resetFlash();

        $payment = $this->requirePendingPayment();
        if (!$payment) {
            return;
        }

        $customer = Auth::guard('customer')->user();

        try {
            app(PaymentService::class)->payWithWallet($payment, $customer);
        } catch (InsufficientBalanceException $e) {
            $this->flashError = $e->getMessage();
            return;
        }

        $this->flashSuccess = 'Pembayaran berhasil, pesanan Anda akan segera diproses.';
        $this->dispatch('order-diproses');
    }

    public function uploadProof(): void
    {
        $this->resetFlash();

        $this->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [], ['proof' => 'bukti transfer']);

        $payment = $this->requirePendingPayment();
        if (!$payment) {
            return;
        }

        app(PaymentService::class)->submitManualProof($payment, $this->proof);

        $this->proof = null;
        $this->flashSuccess = 'Bukti transfer berhasil diunggah, menunggu verifikasi admin. Anda masih bisa mencoba metode lain sebelum batas waktu habis.';
    }

    public function confirmCancel(): void
    {
        $this->showCancelConfirm = true;
    }

    public function cancelCancel(): void
    {
        $this->showCancelConfirm = false;
    }

    public function cancelOrder(): void
    {
        $this->resetFlash();
        $this->showCancelConfirm = false;

        $order = $this->resolveOwnedOrder();
        if (!$order) {
            $this->flashError = 'Order tidak ditemukan.';
            return;
        }

        $customer = Auth::guard('customer')->user();

        try {
            app(PaymentService::class)->cancelByCustomer($order, $customer);
        } catch (\RuntimeException $e) {
            $this->flashError = $e->getMessage();
            return;
        }

        $this->dispatch('order-dibatalkan');
    }

    public function render()
    {
        $order = $this->resolveOwnedOrder();
        abort_if(!$order, 404, 'Order tidak ditemukan atau bukan milik Anda.');

        $payment = $this->paymentId
            ? Payment::find($this->paymentId)
            : Payment::where('order_id', $order->id)->latest('id')->first();

        return view('livewire.customer.payment-page', [
            'order' => $order,
            'payment' => $payment,
            'supportedBanks' => self::SUPPORTED_BANKS,
        ]);
    }

    private function resolveOwnedOrder(): ?Order
    {
        $customer = Auth::guard('customer')->user();

        return Order::where('id', $this->orderId)->where('customer_id', $customer->id)->first();
    }

    private function requirePendingPayment(): ?Payment
    {
        $order = $this->resolveOwnedOrder();

        $payment = $order
            ? Payment::where('order_id', $order->id)->where('status', 'menunggu')->latest('id')->first()
            : null;

        if (!$payment) {
            $this->flashError = 'Tidak ada pembayaran yang menunggu untuk order ini — mungkin sudah dibayar, gagal, atau kedaluwarsa.';
        }

        return $payment;
    }

    private function processQris(): void
    {
        $payment = $this->requirePendingPayment();
        if (!$payment) {
            return;
        }

        try {
            $payment = app(PaymentService::class)->payWithGatewayChannel($payment, 'qris', null);
        } catch (\Throwable $e) {
            $this->flashError = 'Gagal membuat kode QRIS, silakan coba lagi.';
            return;
        }

        $this->paymentId = $payment->id;
        $this->step = 'tampil_qris';
    }

    private function extractBankFromRaw(Payment $payment): ?string
    {
        $raw = $payment->raw_response ?? [];
        return $raw['va_numbers'][0]['bank'] ?? null;
    }

    private function resetFlash(): void
    {
        $this->flashError = null;
        $this->flashSuccess = null;
    }
}