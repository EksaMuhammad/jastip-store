<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test untuk POST /webhooks/midtrans (Tahap 3, brief §1.3 & §2.4).
 *
 * Karena MIDTRANS_SERVER_KEY tidak diset di lingkungan test (lihat phpunit.xml),
 * MidtransGatewayService jalan di "mock mode" secara default —
 * verifyWebhookSignature() otomatis return true. Beberapa test di sini secara
 * eksplisit mengisi services.midtrans.server_key supaya jalur signature-check
 * yang sesungguhnya (SHA-512) ikut teruji.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->wilayah = Wilayah::first();

        $this->customer = Customer::create([
            'phone_number' => '081111111111',
            'name' => 'Budi Utomo',
        ]);

        $this->jastiper = Jastiper::create([
            'phone_number' => '082222222222',
            'name' => 'Joko Driver',
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);
    }

    private function makeDealOrder(float $agreedFare = 75000): Order
    {
        return Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Beli kopi kekinian',
            'destination_address' => 'Jl. Mawar No. 10',
            'recipient_name' => 'Budi Utomo',
            'recipient_phone' => '081111111111',
            'estimated_fare' => $agreedFare,
            'agreed_fare' => $agreedFare,
            'status' => 'deal',
        ]);
    }

    private function makePendingVaPayment(Order $order): Payment
    {
        $payment = app(\App\Services\PaymentService::class)->initiate($order);

        return app(\App\Services\PaymentService::class)->payWithGatewayChannel($payment, 'bank_transfer_va', 'bca');
    }

    public function test_webhook_settlement_menandai_lunas_dan_memicu_start_processing()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePendingVaPayment($order);

        $response = $this->postJson('/webhooks/midtrans', [
            'order_id' => $payment->gateway_reference,
            'transaction_id' => $payment->gateway_transaction_id,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => number_format((float) $payment->amount, 2, '.', ''),
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('lunas', $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals($this->jastiper->id, $order->jastiper_id);
    }

    public function test_webhook_duplikat_tidak_memicu_start_processing_dua_kali_dan_jastiper_tetap_terkunci()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePendingVaPayment($order);

        $payload = [
            'order_id' => $payment->gateway_reference,
            'transaction_id' => $payment->gateway_transaction_id,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => number_format((float) $payment->amount, 2, '.', ''),
        ];

        $this->postJson('/webhooks/midtrans', $payload)->assertOk();

        $order->refresh();
        $this->assertEquals('diproses', $order->status);
        $firstJastiperId = $order->jastiper_id;

        // Webhook kedua (duplikat) untuk transaksi yang sama.
        $response = $this->postJson('/webhooks/midtrans', $payload);
        $response->assertOk()->assertJson(['success' => true]);

        $payment->refresh();
        $order->refresh();

        // Status tidak berubah lagi, jastiper_id tetap sama (tidak ke-reset/berubah).
        $this->assertEquals('lunas', $payment->status);
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals($firstJastiperId, $order->jastiper_id);
    }

    public function test_webhook_status_gagal_menandai_payment_gagal_tanpa_mengubah_status_order()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePendingVaPayment($order);

        $response = $this->postJson('/webhooks/midtrans', [
            'order_id' => $payment->gateway_reference,
            'transaction_id' => $payment->gateway_transaction_id,
            'transaction_status' => 'expire',
            'status_code' => '200',
            'gross_amount' => number_format((float) $payment->amount, 2, '.', ''),
        ]);

        $response->assertOk();

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('gagal', $payment->status);
        $this->assertEquals('menunggu_pembayaran', $order->status);
    }

    public function test_webhook_dengan_signature_tidak_valid_ditolak_403()
    {
        $order = $this->makeDealOrder();
        // Payment+VA sengaja dibuat dulu SEBELUM server_key diisi, supaya
        // createVirtualAccount() tetap lewat mock mode (tidak ada request HTTP
        // asli ke Midtrans). Signature-check di webhook tidak bergantung pada
        // bagaimana VA dibuat, jadi ini aman dilakukan terpisah.
        $payment = $this->makePendingVaPayment($order);

        // Baru sekarang aktifkan jalur signature-check asli (bukan mock mode).
        config(['services.midtrans.server_key' => 'test-server-key-rahasia']);

        $response = $this->postJson('/webhooks/midtrans', [
            'order_id' => $payment->gateway_reference,
            'transaction_id' => $payment->gateway_transaction_id,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => number_format((float) $payment->amount, 2, '.', ''),
            'signature_key' => 'signature-palsu-asal-asalan',
        ]);

        $response->assertStatus(403)->assertJson(['success' => false]);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('menunggu', $payment->status);
        $this->assertEquals('menunggu_pembayaran', $order->status);
    }

    public function test_webhook_dengan_signature_valid_diterima()
    {
        $order = $this->makeDealOrder();
        // Sama seperti test di atas: buat payment+VA dulu di mock mode, baru
        // aktifkan server_key untuk menguji jalur signature-check asli.
        $payment = $this->makePendingVaPayment($order);

        config(['services.midtrans.server_key' => 'test-server-key-rahasia']);

        $statusCode = '200';
        $grossAmount = number_format((float) $payment->amount, 2, '.', '');
        $signature = hash('sha512', $payment->gateway_reference . $statusCode . $grossAmount . 'test-server-key-rahasia');

        $response = $this->postJson('/webhooks/midtrans', [
            'order_id' => $payment->gateway_reference,
            'transaction_id' => $payment->gateway_transaction_id,
            'transaction_status' => 'settlement',
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $payment->refresh();
        $this->assertEquals('lunas', $payment->status);
    }
}