<?php

namespace App\Services;

use App\Models\Payment;

/**
 * Kontrak untuk payment gateway. Implementasi default: MidtransGatewayService.
 * Kalau di masa depan pindah ke Xendit atau gateway lain, cukup buat kelas baru
 * yang implements interface ini dan ganti binding di AppServiceProvider —
 * PaymentService dan seluruh kode bisnis lain tidak perlu diubah.
 */
interface PaymentGatewayService
{
    /**
     * Buat Virtual Account untuk sebuah Payment di bank tertentu.
     *
     * @param Payment $payment
     * @param string $bank Kode bank, mis. 'bca', 'bni', 'bri', 'permata', 'mandiri'.
     * @return array{va_number: string, gateway_transaction_id: string, raw: array}
     */
    public function createVirtualAccount(Payment $payment, string $bank): array;

    /**
     * Buat QRIS untuk sebuah Payment.
     *
     * @param Payment $payment
     * @return array{qr_string: string, gateway_transaction_id: string, raw: array}
     */
    public function createQris(Payment $payment): array;

    /**
     * Cek status transaksi langsung ke gateway. Dipakai untuk cross-check saat
     * admin approve bukti transfer manual (brief §0 keputusan #3), supaya admin
     * tidak asal klik "lunas" untuk bukti transfer palsu.
     *
     * @param string $gatewayTransactionId
     * @return array Payload status mentah dari gateway (mis. transaction_status, dst).
     */
    public function getStatus(string $gatewayTransactionId): array;

    /**
     * Validasi signature payload webhook, supaya request ke endpoint webhook
     * publik (POST /webhooks/midtrans) tidak bisa dipalsukan sembarang pihak.
     *
     * @param array $payload Payload webhook mentah (sudah didecode dari JSON).
     * @return bool
     */
    public function verifyWebhookSignature(array $payload): bool;
}