<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Implementasi PaymentGatewayService untuk Midtrans Core API (BUKAN Snap —
 * lihat brief §0 keputusan #2).
 *
 * Mengikuti pola App\Services\WhatsAppService: kalau MIDTRANS_SERVER_KEY belum
 * dipasang di .env, service ini jalan di "mock mode" — tidak benar-benar
 * memanggil API Midtrans, cuma nge-log dan mengembalikan data VA/QRIS palsu
 * yang tetap valid secara bentuk (format), supaya development & test tetap
 * bisa jalan tanpa akun sandbox Midtrans asli.
 */
class MidtransGatewayService implements PaymentGatewayService
{
    public function createVirtualAccount(Payment $payment, string $bank): array
    {
        if (!$this->hasCredentials()) {
            $vaNumber = $this->mockVaNumber($bank);
            $transactionId = (string) Str::uuid();

            Log::info("[MOCK MIDTRANS] createVirtualAccount order_id={$payment->gateway_reference} bank={$bank} va_number={$vaNumber}");

            return [
                'va_number' => $vaNumber,
                'gateway_transaction_id' => $transactionId,
                'raw' => [
                    'mock' => true,
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->gateway_reference,
                    'transaction_status' => 'pending',
                    'va_numbers' => [['bank' => $bank, 'va_number' => $vaNumber]],
                ],
            ];
        }

        $response = $this->client()->post($this->baseUrl() . '/charge', [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => $payment->gateway_reference,
                'gross_amount' => (int) round((float) $payment->amount),
            ],
            'bank_transfer' => [
                'bank' => $bank,
            ],
        ]);

        $body = $response->json() ?? [];

        if (!$response->successful()) {
            Log::error('[MIDTRANS ERROR] createVirtualAccount gagal: ' . $response->body());
            throw new \RuntimeException('Gagal membuat Virtual Account di Midtrans: ' . ($body['status_message'] ?? $response->body()));
        }

        return [
            'va_number' => $body['va_numbers'][0]['va_number'] ?? null,
            'gateway_transaction_id' => $body['transaction_id'] ?? null,
            'raw' => $body,
        ];
    }

    public function createQris(Payment $payment): array
    {
        if (!$this->hasCredentials()) {
            $qrString = 'MOCKQRIS-' . strtoupper(Str::random(20));
            $transactionId = (string) Str::uuid();

            Log::info("[MOCK MIDTRANS] createQris order_id={$payment->gateway_reference} qr_string={$qrString}");

            return [
                'qr_string' => $qrString,
                'gateway_transaction_id' => $transactionId,
                'raw' => [
                    'mock' => true,
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->gateway_reference,
                    'transaction_status' => 'pending',
                ],
            ];
        }

        $response = $this->client()->post($this->baseUrl() . '/charge', [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $payment->gateway_reference,
                'gross_amount' => (int) round((float) $payment->amount),
            ],
            'qris' => [
                'acquirer' => 'gopay',
            ],
        ]);

        $body = $response->json() ?? [];

        if (!$response->successful()) {
            Log::error('[MIDTRANS ERROR] createQris gagal: ' . $response->body());
            throw new \RuntimeException('Gagal membuat QRIS di Midtrans: ' . ($body['status_message'] ?? $response->body()));
        }

        $qrString = $body['qr_string']
            ?? collect($body['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url']
            ?? null;

        return [
            'qr_string' => $qrString,
            'gateway_transaction_id' => $body['transaction_id'] ?? null,
            'raw' => $body,
        ];
    }

    public function getStatus(string $gatewayTransactionId): array
    {
        if (!$this->hasCredentials()) {
            Log::info("[MOCK MIDTRANS] getStatus transaction_id={$gatewayTransactionId}");

            // Mock mode netral: bukan 'settlement' supaya admin tetap wajib pakai
            // penilaian sendiri (bukti transfer manual) selama belum ada kredensial asli.
            return [
                'mock' => true,
                'transaction_id' => $gatewayTransactionId,
                'transaction_status' => 'pending',
            ];
        }

        $response = $this->client()->get($this->baseUrl() . "/{$gatewayTransactionId}/status");

        if (!$response->successful()) {
            Log::error('[MIDTRANS ERROR] getStatus gagal: ' . $response->body());
            throw new \RuntimeException('Gagal cek status transaksi ke Midtrans: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        if (!$this->hasCredentials()) {
            // Mock mode: tidak ada server_key asli buat hitung signature SHA512,
            // jadi mock mode menganggap payload valid (dev/test only). Di production
            // MIDTRANS_SERVER_KEY selalu wajib diisi, jadi jalur ini tidak akan
            // pernah dipakai untuk lalu lintas nyata.
            Log::info('[MOCK MIDTRANS] verifyWebhookSignature (mock mode, auto-valid)');
            return true;
        }

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';
        $serverKey = config('services.midtrans.server_key');

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expected, (string) $signatureKey);
    }

    private function hasCredentials(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    private function baseUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    private function client()
    {
        // Midtrans Core API pakai HTTP Basic Auth: username = server_key, password kosong.
        return Http::withBasicAuth(config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->asJson();
    }

    private function mockVaNumber(string $bank): string
    {
        $prefix = match ($bank) {
            'bca' => '12345',
            'bni' => '8808',
            'bri' => '77777',
            'permata' => '8529',
            'mandiri' => '89999',
            default => '00000',
        };

        return $prefix . random_int(100000000, 999999999);
    }
}