<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature test untuk POST /admin/payments/{id}/verify (Tahap 3, brief §1.3,
 * §2.4 adminVerifyManualProof(), dan §0 keputusan #3 — cross-check status ke
 * Midtrans sebelum admin approve).
 */
class PaymentManualVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
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

    private function makePaymentWithManualProof(Order $order): Payment
    {
        Storage::fake('public');

        $payment = app(\App\Services\PaymentService::class)->initiate($order);

        return app(\App\Services\PaymentService::class)->submitManualProof(
            $payment,
            UploadedFile::fake()->image('bukti-transfer.jpg')
        );
    }

    public function test_endpoint_verifikasi_wajib_login_admin()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePaymentWithManualProof($order);

        $this->postJson("/admin/payments/{$payment->id}/verify", ['action' => 'approve'])
            ->assertStatus(401);
    }

    public function test_admin_approve_bukti_manual_tanpa_riwayat_gateway_menandai_lunas_dan_start_processing()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePaymentWithManualProof($order);

        // Payment ini belum pernah mencoba jalur VA/QRIS (tidak ada gateway_transaction_id),
        // jadi cross-check ke Midtrans TIDAK dijalankan — approve murni penilaian admin.
        $this->assertNull($payment->gateway_transaction_id);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/payments/{$payment->id}/verify", ['action' => 'approve']);

        $response->assertOk()->assertJson(['success' => true]);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('lunas', $payment->status);
        $this->assertEquals($this->admin->id, $payment->verified_by_admin_id);
        $this->assertNotNull($payment->verified_at);
        $this->assertEquals('diproses', $order->status);
    }

    public function test_admin_approve_dengan_riwayat_gateway_melakukan_cross_check_status_midtrans()
    {
        config(['services.midtrans.server_key' => 'test-server-key-rahasia']);

        $order = $this->makeDealOrder();
        $payment = app(\App\Services\PaymentService::class)->initiate($order);

        Http::fake([
            '*/charge' => Http::response([
                'transaction_id' => 'trx-123-abc',
                'va_numbers' => [['bank' => 'bca', 'va_number' => '12345678901']],
            ], 200),
            '*/trx-123-abc/status' => Http::response([
                'transaction_id' => 'trx-123-abc',
                'transaction_status' => 'settlement',
            ], 200),
        ]);

        $payment = app(\App\Services\PaymentService::class)->payWithGatewayChannel($payment, 'bank_transfer_va', 'bca');
        $payment = app(\App\Services\PaymentService::class)->submitManualProof(
            $payment,
            UploadedFile::fake()->image('bukti-transfer.jpg')
        );

        $this->assertEquals('trx-123-abc', $payment->gateway_transaction_id);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/payments/{$payment->id}/verify", ['action' => 'approve']);

        $response->assertOk()->assertJson(['success' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/trx-123-abc/status');
        });

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('lunas', $payment->status);
        $this->assertEquals('diproses', $order->status);
    }

    public function test_admin_reject_bukti_manual_menandai_gagal_dan_tidak_mengubah_status_order()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePaymentWithManualProof($order);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/payments/{$payment->id}/verify", ['action' => 'reject']);

        $response->assertOk()->assertJson(['success' => true]);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('gagal', $payment->status);
        $this->assertEquals($this->admin->id, $payment->verified_by_admin_id);
        $this->assertEquals('menunggu_pembayaran', $order->status);
    }
}