<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wilayah;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature test untuk halaman admin verifikasi pembayaran (Tahap 6, brief §3
 * Tahap 6). Mirror dari tests/Feature/AdminVerificationDashboardTest.php
 * (verifikasi jastiper) yang sudah ada, disesuaikan untuk komponen Livewire
 * single-file 'admin.payment-verification' dan model Payment.
 *
 * Tidak menguji ulang cross-check status Midtrans / idempotency — itu sudah
 * dites lengkap di PaymentManualVerificationTest (Tahap 3) lewat endpoint
 * POST /admin/payments/{id}/verify. Test di sini fokus ke: halaman admin bisa
 * diakses, komponen Livewire ter-render, dan tombol approve/reject di
 * komponen benar-benar mendelegasikan ke PaymentService::adminVerifyManualProof()
 * (sama seperti PaymentController::adminVerify() memanggilnya, lihat catatan
 * desain di PaymentPage soal komponen Livewire memanggil Service langsung).
 */
class PaymentAdminVerificationTest extends TestCase
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

        $payment = app(PaymentService::class)->initiate($order);

        return app(PaymentService::class)->submitManualProof(
            $payment,
            UploadedFile::fake()->image('bukti-transfer.jpg')
        );
    }

    public function test_admin_payments_dashboard_requires_admin_auth()
    {
        $this->get(route('admin.payments'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_payments_dashboard_renders_successfully()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.payments'))
            ->assertStatus(200)
            ->assertSeeLivewire('admin.payment-verification');
    }

    public function test_dashboard_hanya_menampilkan_payment_yang_punya_bukti_manual()
    {
        $order = $this->makeDealOrder();
        $paymentWithProof = $this->makePaymentWithManualProof($order);

        // Payment lain yang belum upload bukti sama sekali (mis. baru pilih VA,
        // belum bayar) TIDAK boleh muncul di antrian verifikasi manual ini.
        $order2 = $this->makeDealOrder(50000);
        $paymentWithoutProof = app(PaymentService::class)->initiate($order2);

        $this->actingAs($this->admin, 'admin');

        Livewire::test('admin.payment-verification')
            ->assertSee('Order #' . $paymentWithProof->order_id)
            ->assertDontSee('Order #' . $paymentWithoutProof->order_id);
    }

    public function test_admin_dapat_approve_pembayaran_via_komponen()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePaymentWithManualProof($order);

        $this->actingAs($this->admin, 'admin');

        Livewire::test('admin.payment-verification')
            ->call('selectPayment', $payment->id)
            ->call('approvePayment', $payment->id)
            ->assertHasNoErrors();

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('lunas', $payment->status);
        $this->assertEquals($this->admin->id, $payment->verified_by_admin_id);
        $this->assertNotNull($payment->verified_at);
        $this->assertEquals('diproses', $order->status);
    }

    public function test_admin_dapat_reject_pembayaran_via_komponen()
    {
        $order = $this->makeDealOrder();
        $payment = $this->makePaymentWithManualProof($order);

        $this->actingAs($this->admin, 'admin');

        Livewire::test('admin.payment-verification')
            ->call('selectPayment', $payment->id)
            ->call('confirmReject')
            ->call('rejectPayment', $payment->id)
            ->assertHasNoErrors();

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('gagal', $payment->status);
        $this->assertEquals($this->admin->id, $payment->verified_by_admin_id);
        // Sesuai keputusan Tahap 3 (brief catatan implementasi §Tahap 3): reject
        // manual TIDAK membatalkan order, tetap 'menunggu_pembayaran' supaya
        // customer masih bisa retry sebelum payment_deadline lewat.
        $this->assertEquals('menunggu_pembayaran', $order->status);
    }

    public function test_approve_dengan_riwayat_gateway_melakukan_cross_check_status_midtrans()
    {
        config(['services.midtrans.server_key' => 'test-server-key-rahasia']);

        $order = $this->makeDealOrder();
        $payment = app(PaymentService::class)->initiate($order);

        Http::fake([
            '*/charge' => Http::response([
                'transaction_id' => 'trx-admin-dash-1',
                'va_numbers' => [['bank' => 'bca', 'va_number' => '12345678901']],
            ], 200),
            '*/trx-admin-dash-1/status' => Http::response([
                'transaction_id' => 'trx-admin-dash-1',
                'transaction_status' => 'settlement',
            ], 200),
        ]);

        $payment = app(PaymentService::class)->payWithGatewayChannel($payment, 'bank_transfer_va', 'bca');

        Storage::fake('public');
        $payment = app(PaymentService::class)->submitManualProof(
            $payment,
            UploadedFile::fake()->image('bukti-transfer.jpg')
        );

        $this->actingAs($this->admin, 'admin');

        Livewire::test('admin.payment-verification')
            ->call('approvePayment', $payment->id)
            ->assertHasNoErrors();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/trx-admin-dash-1/status');
        });

        $payment->refresh();
        $this->assertEquals('lunas', $payment->status);
    }
}