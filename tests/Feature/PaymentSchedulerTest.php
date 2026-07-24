<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wilayah;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature test untuk Tahap 4 — Scheduler auto-cancel (brief §3 Tahap 4).
 *
 * Menguji 2 lapisan:
 * 1. Command `payments:expire-overdue` itu sendiri (wrapper tipis di atas
 *    PaymentService::expireOverdue(), sudah dites lengkap di PaymentServiceTest
 *    Tahap 2 — di sini cukup pastikan command-nya benar-benar memanggil service).
 * 2. Bahwa command itu benar-benar TERDAFTAR di scheduler dan jalan saat
 *    `artisan schedule:run` dieksekusi — ini yang jadi acceptance criteria utama
 *    Tahap 4, karena PaymentService::expireOverdue() sendiri sudah lolos test
 *    sejak Tahap 2.
 */
class PaymentSchedulerTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

    public function test_command_payments_expire_overdue_membatalkan_order_yang_lewat_deadline()
    {
        $order = $this->makeDealOrder();
        $payment = app(PaymentService::class)->initiate($order);
        $payment->update(['payment_deadline' => now()->subMinutes(1)]);

        $this->artisan('payments:expire-overdue')
            ->expectsOutputToContain('1 order dibatalkan')
            ->assertExitCode(0);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('kedaluwarsa', $payment->status);
        $this->assertEquals('dibatalkan', $order->status);
        $this->assertEquals('system', $order->cancelled_by_role);
        $this->assertNull($order->jastiper_id);
    }

    public function test_scheduler_menjalankan_payments_expire_overdue_lewat_schedule_run()
    {
        $order = $this->makeDealOrder();
        $payment = app(PaymentService::class)->initiate($order);

        // Majukan waktu simulasi melewati payment_deadline (default 15 menit
        // dari config/jastip.php), lalu jalankan scheduler seperti cron asli.
        $this->travel(20)->minutes();

        $this->artisan('schedule:run')->assertExitCode(0);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('kedaluwarsa', $payment->status);
        $this->assertEquals('dibatalkan', $order->status);
        $this->assertEquals('system', $order->cancelled_by_role);
        $this->assertNull($order->jastiper_id);
    }

    public function test_scheduler_tidak_menyentuh_payment_yang_belum_lewat_deadline()
    {
        $order = $this->makeDealOrder();
        app(PaymentService::class)->initiate($order);

        // Waktu belum maju sama sekali — payment_deadline (default 15 menit) belum lewat.
        $this->artisan('schedule:run')->assertExitCode(0);

        $order->refresh();
        $this->assertEquals('menunggu_pembayaran', $order->status);
        $this->assertEquals($this->jastiper->id, $order->jastiper_id);
    }

    public function test_command_expire_overdue_terdaftar_di_scheduler()
    {
        // Pastikan command benar-benar terdaftar sebagai scheduled event (bukan
        // cuma command mandiri yang tidak pernah dipanggil cron), supaya kalau
        // suatu saat withSchedule() di bootstrap/app.php ke-hapus tidak sengaja,
        // test ini yang bakal merah duluan.
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $commands = collect($schedule->events())->map(fn ($event) => $event->description ?? '');

        $this->assertTrue(
            $commands->contains(fn ($command) => str_contains($command, 'payments:expire-overdue')),
            'Command payments:expire-overdue tidak ditemukan di scheduler.'
        );
    }
}