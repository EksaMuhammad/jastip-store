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

    public ?string $snapToken = null;

    public bool $showMockSnapModal = false;

    public bool $showInsufficientBalanceModal = false;

    public float $shortageAmount = 0.0;

    public float $currentBalance = 0.0;

    public float $requiredAmount = 0.0;

    public ?string $selectedMethod = null;

    private const SUPPORTED_BANKS = ['bca', 'bni', 'bri', 'permata', 'mandiri'];

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;

        $order = $this->resolveOwnedOrder();
        abort_if(!$order, 404, 'Order tidak ditemukan atau bukan milik Anda.');

        if (session()->has('return_order_payment_id') && session('return_order_payment_id') == $this->orderId) {
            session()->forget('return_order_payment_id');
            $this->flashSuccess = 'Top-Up saldo berhasil dikonfirmasi! Saldo e-wallet Anda sekarang telah bertambah. Silakan selesaikan pembayaran pesanan.';
        }

        // Ambil payment terbaru — utamakan yang masih menunggu, fallback ke yang lunas
        $payment = Payment::where('order_id', $order->id)
            ->where('status', 'menunggu')
            ->latest('id')
            ->first()
            ?? Payment::where('order_id', $order->id)->latest('id')->first();
        $this->paymentId = $payment?->id;

        if ($payment && in_array($payment->status, ['menunggu'], true)) {
            $this->snapToken = app(PaymentService::class)->getOrCreateSnapToken($payment);

            if ($payment->channel) {
                $this->step = $payment->channel === 'qris' ? 'tampil_qris' : 'tampil_va';
                $this->selectedBank = $this->extractBankFromRaw($payment);
            }
        }
    }

    /**
     * Pilih / toggle metode pembayaran pada daftar pilihan.
     */
    public function selectMethod(string $method): void
    {
        $this->selectedMethod = $this->selectedMethod === $method ? null : $method;
    }

    /**
     * Buka pembayaran Midtrans Snap.
     */
    public function openMidtransSnap(): void
    {
        $this->selectedMethod = 'midtrans';

        $payment = $this->requirePendingPayment();
        if (!$payment) {
            return;
        }

        $this->snapToken = app(PaymentService::class)->getOrCreateSnapToken($payment);

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
     * Otomatis selesaikan pembayaran order (dipanggil dari Snap success callback atau simulasi).
     */
    public function markPaymentSuccess(?string $channel = 'midtrans_snap', ?string $transactionId = null): void
    {
        $payment = $this->requirePendingPayment();
        if (!$payment) {
            return;
        }

        // Jika transaksi memiliki BCA VA di mode Sandbox, simulasikan pelunasan ke Midtrans Simulator
        if (!empty($payment->va_number) && !config('services.midtrans.is_production')) {
            try {
                app(\App\Services\MidtransGatewayService::class)->simulateBcaVaPayment($payment->va_number);
            } catch (\Throwable $e) {
                // Jangan gagalkan alur jika simulator eksternal timeout
            }
        }

        app(PaymentService::class)->settlePaymentAutomatically($payment, $channel, $transactionId);

        $this->showMockSnapModal = false;
        $this->flashSuccess = 'Pembayaran berhasil dikonfirmasi! Pesanan Anda sedang diproses jastiper.';
        $this->dispatch('order-diproses');
    }

    /**
     * Cek status pembayaran pesanan secara langsung ke server API Midtrans.
     * Otomatis melunasi order & mencatat mutasi saat pembayaran disetujui di Midtrans.
     */
    public function checkMidtransStatus(bool $isManual = true): void
    {
        $payment = $this->requirePendingPayment();
        if (!$payment || $payment->status === 'lunas') {
            return;
        }

        if (!filled(config('services.midtrans.server_key'))) {
            if ($isManual) {
                $this->flashError = 'Server Key Midtrans belum dikonfigurasi.';
            }
            return;
        }

        $identifier = $payment->gateway_reference ?: $payment->gateway_transaction_id;
        if (!$identifier) {
            if ($isManual) {
                $this->flashError = 'ID Transaksi Midtrans belum terbit untuk pesanan ini.';
            }
            return;
        }

        try {
            $status = app(\App\Services\MidtransGatewayService::class)->getStatus($identifier);
            $txStatus = $status['transaction_status'] ?? 'pending';

            if (in_array($txStatus, ['settlement', 'capture'], true)) {
                app(PaymentService::class)->settlePaymentAutomatically(
                    $payment,
                    $status['payment_type'] ?? 'midtrans',
                    $status['transaction_id'] ?? null
                );

                $this->flashSuccess = 'Pembayaran terverifikasi lunas dari Midtrans! Pesanan Anda sedang diproses jastiper.';
                $this->dispatch('order-diproses');
            } elseif ($isManual) {
                $channel = strtoupper($status['payment_type'] ?? 'PEMBAYARAN');
                $this->flashError = "Status di Midtrans saat ini: [{$txStatus}] ({$channel}). Silakan selesaikan pembayaran di simulator atau aplikasi e-wallet / mobile banking Anda.";
            }
        } catch (\Throwable $e) {
            if ($isManual) {
                $this->flashError = 'Gagal memeriksa status ke Midtrans: ' . $e->getMessage();
            }
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

        // Order sudah final / tidak butuh pembayaran lagi — redirect sesuai kondisi
        if (in_array($order->status, ['diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan', 'selesai'], true)) {
            $this->dispatch('order-diproses');
            return;
        }

        if ($order->status === 'dibatalkan') {
            $this->dispatch('order-dibatalkan');
            return;
        }

        // Jika payment sudah lunas (misal settle via webhook setelah Snap), redirect juga
        $payment = $this->paymentId
            ? Payment::find($this->paymentId)
            : Payment::where('order_id', $order->id)->latest('id')->first();

        if ($payment && $payment->status === 'lunas') {
            $this->dispatch('order-diproses');
            return;
        }

        // Hanya cek ke Midtrans bila order masih menunggu_pembayaran DAN payment masih menunggu
        if (
            $order->status === 'menunggu_pembayaran'
            && $payment
            && $payment->status === 'menunggu'
            && filled(config('services.midtrans.server_key'))
        ) {
            $this->checkMidtransStatus(false);
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
        $wallet = $customer?->wallet()->first();
        $this->currentBalance = $wallet ? (float) $wallet->balance : 0.0;
        $this->requiredAmount = (float) $payment->amount;

        if ($this->currentBalance < $this->requiredAmount) {
            $this->shortageAmount = $this->requiredAmount - $this->currentBalance;
            $this->showInsufficientBalanceModal = true;
            return;
        }

        try {
            app(PaymentService::class)->payWithWallet($payment, $customer);
        } catch (InsufficientBalanceException $e) {
            $this->shortageAmount = max(0, $this->requiredAmount - $this->currentBalance);
            $this->showInsufficientBalanceModal = true;
            return;
        }

        $this->flashSuccess = 'Pembayaran berhasil, pesanan Anda akan segera diproses.';
        $this->dispatch('order-diproses');
    }

    public function closeInsufficientBalanceModal(): void
    {
        $this->showInsufficientBalanceModal = false;
    }

    /**
     * Aksi langsung jika saldo kurang: Buat tiket top-up otomatis sejumlah kekurangan dan arahkan customer untuk top-up.
     */
    public function proceedToTopup(): void
    {
        $payment = $this->requirePendingPayment();
        if (!$payment) {
            return;
        }

        $customer = Auth::guard('customer')->user();
        $wallet = $customer->wallet ?: $customer->wallet()->create([
            'owner_role' => 'customer',
            'balance' => 0,
        ]);

        $balance = (float) $wallet->balance;
        $required = (float) $payment->amount;
        $shortage = max(0, $required - $balance);

        $minTopup = (int) app(\App\Services\TariffService::class)->get('min_topup_amount', 10000);
        $amountToTopup = max($minTopup, ceil($shortage));

        // Buat tiket topup otomatis dengan nominal kekurangan
        $topup = app(\App\Services\WalletService::class)->initiateTopup($wallet, $amountToTopup, 'qris');

        session()->flash('info', "Silakan selesaikan pembayaran Top-Up sebesar Rp " . number_format($amountToTopup, 0, ',', '.') . " untuk melunasi pesanan #{$this->orderId}.");
        session()->put('return_order_payment_id', $this->orderId);

        $this->redirect(route('customer.wallet.pay_topup', ['id' => $topup->id]));
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

        $customer = Auth::guard('customer')->user();
        $wallet = $customer?->wallet()->first();
        $walletBalance = $wallet ? (float) $wallet->balance : 0.0;
        $requiredAmount = $payment ? (float) $payment->amount : (float) ($order->downpayment_amount ?? $order->agreed_fare ?? 0);
        $shortage = max(0, $requiredAmount - $walletBalance);

        $this->currentBalance = $walletBalance;
        $this->requiredAmount = $requiredAmount;
        $this->shortageAmount = $shortage;

        return view('livewire.customer.payment-page', [
            'order' => $order,
            'payment' => $payment,
            'supportedBanks' => self::SUPPORTED_BANKS,
            'walletBalance' => $walletBalance,
            'requiredAmount' => $requiredAmount,
            'shortage' => $shortage,
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