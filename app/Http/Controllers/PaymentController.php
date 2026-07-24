<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint controller untuk fitur pembayaran wajib (virtual escrow) — Tahap 3.
 * Dipisah dari DashboardController (pola sama seperti ChatController), khusus
 * menangani aksi pembayaran + webhook Midtrans + verifikasi manual admin.
 * Controller ini TIDAK menyentuh state machine Payment/Order secara langsung —
 * semua orkestrasi didelegasikan ke PaymentService (lihat BRIEF §2.4).
 *
 * Halaman utama pembayaran (GET /customer/orders/{id}/payment, method page()
 * di bawah) ditambahkan di Tahap 5 — cuma wrapper blade tipis yang merender
 * komponen Livewire App\Livewire\Customer\PaymentPage. Komponen Livewire itu
 * TIDAK memanggil endpoint-endpoint aksi di controller ini lewat HTTP — ia
 * memanggil App\Services\PaymentService secara langsung (lihat catatan desain
 * di PaymentPage). Endpoint aksi di bawah (selectMethod/payWithWallet/
 * uploadProof/cancel/webhook/adminVerify) tetap dipertahankan untuk klien lain
 * (non-JS/mobile app masa depan) dan tetap tercakup test Tahap 3.
 */
class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    /**
     * GET /customer/orders/{id}/payment
     * Halaman utama pembayaran (Tahap 5) — cuma wrapper blade tipis yang
     * merender komponen Livewire full-page App\Livewire\Customer\PaymentPage
     * (pola sama seperti DashboardController::customerCreateOrder() yang
     * merender resources/views/dashboard/customer/create_order.blade.php
     * berisi @livewire('customer.create-order-form')). Semua logic sub-state
     * pembayaran ada di komponen Livewire itu sendiri, bukan di sini.
     */
    public function page($id)
    {
        $order = $this->resolveOwnedOrder($id);
        abort_if(!$order, 404, 'Order tidak ditemukan atau bukan milik Anda.');

        return view('customer.payment', compact('order'));
    }

    /**
     * POST /customer/orders/{id}/payment/method
     * Customer memilih channel transfer: Virtual Account bank tertentu atau QRIS.
     * (Pilihan "Saldo Wallet" punya endpoint terpisah, lihat payWithWallet().)
     */
    public function selectMethod(Request $request, $id)
    {
        $order = $this->resolveOwnedOrder($id);
        if (!$order) {
            return $this->fail($request, 'Order tidak ditemukan atau bukan milik Anda.', 404);
        }

        $request->validate([
            'channel' => 'required|in:bank_transfer_va,qris',
            'bank' => 'required_if:channel,bank_transfer_va|nullable|in:bca,bni,bri,permata,mandiri',
        ]);

        $payment = $this->resolvePendingPayment($order);
        if (!$payment) {
            return $this->fail($request, 'Tidak ada pembayaran yang menunggu untuk order ini.', 409);
        }

        try {
            $payment = $this->paymentService->payWithGatewayChannel($payment, $request->input('channel'), $request->input('bank'));
        } catch (\Throwable $e) {
            Log::error('[PAYMENT] Gagal membuat channel pembayaran: ' . $e->getMessage());
            return $this->fail($request, 'Gagal membuat metode pembayaran, silakan coba lagi.', 502);
        }

        return $this->ok($request, ['payment' => $this->paymentPayload($payment)]);
    }

    /**
     * POST /customer/orders/{id}/payment/wallet-pay
     * Debit langsung dari saldo wallet customer, instan (tidak lewat gateway).
     */
    public function payWithWallet(Request $request, $id)
    {
        $order = $this->resolveOwnedOrder($id);
        if (!$order) {
            return $this->fail($request, 'Order tidak ditemukan atau bukan milik Anda.', 404);
        }

        $payment = $this->resolvePendingPayment($order);
        if (!$payment) {
            return $this->fail($request, 'Tidak ada pembayaran yang menunggu untuk order ini.', 409);
        }

        $customer = Auth::guard('customer')->user();

        try {
            $payment = $this->paymentService->payWithWallet($payment, $customer);
        } catch (InsufficientBalanceException $e) {
            return $this->fail($request, $e->getMessage(), 422);
        }

        return $this->ok(
            $request,
            ['payment' => $this->paymentPayload($payment), 'order_status' => $payment->order->status],
            'Pembayaran berhasil, pesanan Anda akan segera diproses.'
        );
    }

    /**
     * POST /customer/orders/{id}/payment/upload-proof
     * Fallback manual: customer upload bukti transfer, menunggu approve admin.
     */
    public function uploadProof(Request $request, $id)
    {
        $order = $this->resolveOwnedOrder($id);
        if (!$order) {
            return $this->fail($request, 'Order tidak ditemukan atau bukan milik Anda.', 404);
        }

        $request->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $payment = $this->resolvePendingPayment($order);
        if (!$payment) {
            return $this->fail($request, 'Tidak ada pembayaran yang menunggu untuk order ini.', 409);
        }

        $payment = $this->paymentService->submitManualProof($payment, $request->file('proof'));

        return $this->ok(
            $request,
            ['payment' => $this->paymentPayload($payment)],
            'Bukti transfer berhasil diunggah, menunggu verifikasi admin.'
        );
    }

    /**
     * POST /customer/orders/{id}/payment/cancel
     * Customer batalkan order-nya sendiri selagi masih 'menunggu_pembayaran'
     * (Tahap 5, beda dari auto-cancel scheduler Tahap 4). Endpoint ini murni
     * untuk klien non-Livewire (mis. mobile app di masa depan) — komponen
     * Livewire PaymentPage sendiri memanggil PaymentService::cancelByCustomer()
     * langsung (lihat catatan desain di App\Livewire\Customer\PaymentPage).
     */
    public function cancel(Request $request, $id)
    {
        $order = $this->resolveOwnedOrder($id);
        if (!$order) {
            return $this->fail($request, 'Order tidak ditemukan atau bukan milik Anda.', 404);
        }

        $customer = Auth::guard('customer')->user();

        try {
            $this->paymentService->cancelByCustomer($order, $customer);
        } catch (\RuntimeException $e) {
            return $this->fail($request, $e->getMessage(), 409);
        }

        return $this->ok($request, [], 'Pesanan berhasil dibatalkan.');
    }

    /**
     * GET /customer/orders/{id}/payment/status
     * Endpoint polling JSON (dipakai wire:poll di Tahap 5). Selalu JSON,
     * termasuk untuk kasus gagal, karena ini murni dipanggil lewat fetch/polling.
     */
    public function status($id): JsonResponse
    {
        $order = $this->resolveOwnedOrder($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan atau bukan milik Anda.'], 404);
        }

        $payment = Payment::where('order_id', $order->id)->latest('id')->first();

        return response()->json([
            'success' => true,
            'order_status' => $order->status,
            'payment' => $payment ? $this->paymentPayload($payment) : null,
        ]);
    }

    /**
     * POST /webhooks/midtrans
     * Webhook publik (TIDAK di bawah middleware auth:*, dikecualikan dari
     * validasi CSRF di bootstrap/app.php). Validitas payload divalidasi lewat
     * signature Midtrans di dalam PaymentService::handleWebhook(), bukan lewat
     * middleware auth Laravel.
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleWebhook($request->all());
        } catch (\RuntimeException $e) {
            // Signature tidak valid — sudah di-log di dalam service. Balas 403
            // supaya kalau ada retry dari sisi Midtrans, jelas ini ditolak.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /admin/payments
     * Halaman antrian verifikasi pembayaran manual untuk admin (Tahap 6) —
     * cuma wrapper blade tipis yang merender komponen Livewire single-file
     * App\Livewire (SFC) 'admin.payment-verification' (pola sama seperti
     * page() customer di atas, dan sama seperti
     * DashboardController::adminVerification() yang merender
     * admin/verification.blade.php berisi @livewire('admin.admin-verification')
     * untuk verifikasi jastiper). Diletakkan di sini (bukan DashboardController)
     * karena ini murni tentang pembayaran — konsisten dengan pemisahan yang
     * sudah dilakukan sejak Tahap 3 (lihat docblock class ini).
     */
    public function adminPage()
    {
        return view('admin.payments');
    }

    /**
     * POST /admin/payments/{id}/verify
     * Admin approve/reject bukti transfer manual. Cross-check ke status
     * Midtrans (kalau payment punya gateway_transaction_id) sudah ditangani
     * di dalam PaymentService::adminVerifyManualProof() — lihat brief §0 no. 3.
     */
    public function adminVerify(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $payment = Payment::findOrFail($id);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $approve = $request->input('action') === 'approve';

        $payment = $this->paymentService->adminVerifyManualProof($payment, $admin, $approve);

        $message = $approve
            ? 'Pembayaran disetujui, pesanan akan mulai diproses.'
            : 'Bukti transfer ditolak.';

        return $this->ok($request, ['payment' => $this->paymentPayload($payment)], $message);
    }

    /**
     * Ambil order milik customer yang sedang login. Null kalau tidak ditemukan
     * atau bukan miliknya — sengaja tidak dibedakan (404) supaya customer lain
     * tidak bisa menebak-nebak keberadaan order id orang lain.
     */
    private function resolveOwnedOrder($id): ?Order
    {
        $customer = Auth::guard('customer')->user();

        return Order::where('id', $id)->where('customer_id', $customer->id)->first();
    }

    /**
     * Payment paling baru untuk order ini yang masih berstatus 'menunggu'.
     * Dipakai untuk menjaga supaya aksi (pilih channel/wallet-pay/upload bukti)
     * tidak bisa dipanggil lagi begitu payment sudah lunas/gagal/kedaluwarsa.
     */
    private function resolvePendingPayment(Order $order): ?Payment
    {
        return Payment::where('order_id', $order->id)
            ->where('status', 'menunggu')
            ->latest('id')
            ->first();
    }

    private function paymentPayload(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status,
            'method' => $payment->method,
            'channel' => $payment->channel,
            'va_number' => $payment->va_number,
            'qr_string' => $payment->qr_string,
            'amount' => $payment->amount,
            'payment_deadline' => optional($payment->payment_deadline)->toIso8601String(),
        ];
    }

    private function fail(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->back()->with('error', $message);
    }

    private function ok(Request $request, array $data = [], ?string $message = null): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(array_merge(['success' => true], $message ? ['message' => $message] : [], $data));
        }

        return redirect()->back()->with('success', $message ?? 'Berhasil.');
    }
}