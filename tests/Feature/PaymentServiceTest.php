<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Wilayah;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;
    protected PaymentService $paymentService;

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

        $this->paymentService = app(PaymentService::class);
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

    public function test_initiate_membuat_payment_dan_mengubah_status_order()
    {
        $order = $this->makeDealOrder(75000);

        $payment = $this->paymentService->initiate($order);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
            'status' => 'menunggu',
        ]);

        $this->assertEquals('75000.00', $payment->amount);
        $this->assertNotNull($payment->payment_deadline);
        $this->assertNotNull($payment->gateway_reference);

        $order->refresh();
        $this->assertEquals('menunggu_pembayaran', $order->status);
    }

    public function test_initiate_idempotent_tidak_bikin_row_dobel_kalau_dipanggil_ulang()
    {
        $order = $this->makeDealOrder();

        $first = $this->paymentService->initiate($order);
        $second = $this->paymentService->initiate($order->fresh());

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_pay_with_wallet_sukses_debit_saldo_dan_mulai_proses_order()
    {
        $order = $this->makeDealOrder(50000);
        $payment = $this->paymentService->initiate($order);

        $wallet = Wallet::create([
            'owner_role' => 'customer',
            'owner_id' => $this->customer->id,
            'balance' => 100000,
        ]);

        $result = $this->paymentService->payWithWallet($payment, $this->customer);

        $this->assertEquals('lunas', $result->status);
        $this->assertEquals('wallet', $result->method);
        $this->assertNotNull($result->verified_at);

        $wallet->refresh();
        $this->assertEquals('50000.00', $wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => '50000.00',
            'source' => 'pembayaran_order',
            'reference_order_id' => $order->id,
        ]);

        $order->refresh();
        $this->assertEquals('diproses', $order->status);
    }

    public function test_pay_with_wallet_gagal_kalau_saldo_kurang()
    {
        $order = $this->makeDealOrder(50000);
        $payment = $this->paymentService->initiate($order);

        Wallet::create([
            'owner_role' => 'customer',
            'owner_id' => $this->customer->id,
            'balance' => 10000,
        ]);

        $this->expectException(InsufficientBalanceException::class);

        $this->paymentService->payWithWallet($payment, $this->customer);

        $payment->refresh();
        $this->assertEquals('menunggu', $payment->status);
    }

    public function test_expire_overdue_membatalkan_order_dan_melepas_jastiper()
    {
        $order = $this->makeDealOrder();
        $payment = $this->paymentService->initiate($order);

        // Majukan waktu supaya payment_deadline sudah lewat.
        $payment->update(['payment_deadline' => now()->subMinutes(1)]);

        $cancelledCount = $this->paymentService->expireOverdue();

        $this->assertEquals(1, $cancelledCount);

        $payment->refresh();
        $this->assertEquals('kedaluwarsa', $payment->status);

        $order->refresh();
        $this->assertEquals('dibatalkan', $order->status);
        $this->assertEquals('system', $order->cancelled_by_role);
        $this->assertNull($order->jastiper_id);
    }

    public function test_expire_overdue_tidak_menyentuh_payment_yang_belum_lewat_deadline()
    {
        $order = $this->makeDealOrder();
        $this->paymentService->initiate($order);

        $cancelledCount = $this->paymentService->expireOverdue();

        $this->assertEquals(0, $cancelledCount);

        $order->refresh();
        $this->assertEquals('menunggu_pembayaran', $order->status);
    }
}