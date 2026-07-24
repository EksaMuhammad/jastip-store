<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JastipBiddingTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Jastiper $jastiperA;
    protected Jastiper $jastiperB;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->wilayah = Wilayah::first();

        $this->customer = Customer::create([
            'phone_number' => '081111111111',
            'name' => 'Budi Utomo',
            'password' => bcrypt('password123'),
        ]);

        $this->jastiperA = Jastiper::create([
            'phone_number' => '082222222222',
            'name' => 'Joko Driver',
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);

        $this->jastiperB = Jastiper::create([
            'phone_number' => '082333333333',
            'name' => 'Slamet Driver',
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);
    }

    /**
     * Helper: buat order feed umum (belum ada jastiper_id) yang siap ditawar.
     */
    protected function createOpenOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'customer_id' => $this->customer->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip Martabak Keju',
            'origin_address' => 'Martabak 88',
            'destination_address' => 'Kos A',
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 15000.00,
            'status' => 'menunggu_tawaran',
        ], $overrides));
    }

    /** 1. Jastiper terverifikasi bisa mengirim penawaran harga. */
    public function test_jastiper_can_submit_offer()
    {
        $order = $this->createOpenOrder();

        $this->actingAs($this->jastiperA, 'jastiper')
            ->postJson(route('jastiper.orders.offer', $order->id), ['offered_price' => 12000])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('offers', [
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperA->id,
            'offered_price' => 12000.00,
            'status' => 'pending',
        ]);

        $order->refresh();
        $this->assertEquals('ada_tawaran', $order->status);
        // jastiper_id order TIDAK boleh terisi hanya karena mengirim tawaran
        $this->assertNull($order->jastiper_id);
    }

    /** Edge-case tambahan: tawaran di bawah minimum harus ditolak validasi. */
    public function test_jastiper_cannot_submit_offer_below_minimum_price()
    {
        $order = $this->createOpenOrder();

        $this->actingAs($this->jastiperA, 'jastiper')
            ->postJson(route('jastiper.orders.offer', $order->id), ['offered_price' => 1000])
            ->assertStatus(422);

        $this->assertDatabaseMissing('offers', [
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperA->id,
        ]);
    }

    /** 2. Tawaran masuk muncul di daftar order customer (endpoint polling). */
    public function test_customer_can_see_incoming_offers()
    {
        $order = $this->createOpenOrder(['status' => 'ada_tawaran']);

        Offer::create([
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperA->id,
            'offered_price' => 12000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer, 'customer')
            ->getJson(route('customer.orders.active-feed'))
            ->assertStatus(200)
            ->assertJsonFragment([
                'jastiper_name' => 'Joko Driver',
                'offered_price_formatted' => 'Rp 12.000',
            ]);
    }

    /** 3. Kalau tawaran diterima, status order -> deal, jastiper_id & agreed_fare terkunci. */
    public function test_customer_can_accept_offer_locks_deal()
    {
        $order = $this->createOpenOrder(['status' => 'ada_tawaran']);

        $offer = Offer::create([
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperA->id,
            'offered_price' => 12000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.offers.accept', $offer->id))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $offer->refresh();

        $this->assertEquals('menunggu_pembayaran', $order->status);
        $this->assertEquals($this->jastiperA->id, $order->jastiper_id);
        $this->assertEquals(12000.00, (float) $order->agreed_fare);
        $this->assertEquals('accepted', $offer->status);

        // Simulasi pembayaran agar masuk ke tahap diproses
        \App\Models\Wallet::create([
            'owner_role' => 'customer',
            'owner_id' => $this->customer->id,
            'balance' => 100000,
        ]);
        $payment = \App\Models\Payment::where('order_id', $order->id)->first();
        app(\App\Services\PaymentService::class)->payWithWallet($payment, $this->customer);

        $order->refresh();
        $this->assertEquals('diproses', $order->status);
    }

    /** 4. Tawaran jastiper lain otomatis berstatus rejected saat salah satu dipilih. */
    public function test_unselected_jastipers_are_notified_and_rejected()
    {
        $order = $this->createOpenOrder(['status' => 'ada_tawaran']);

        $offerA = Offer::create([
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperA->id,
            'offered_price' => 12000,
            'status' => 'pending',
        ]);

        $offerB = Offer::create([
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperB->id,
            'offered_price' => 15000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.offers.accept', $offerA->id))
            ->assertStatus(200);

        $offerA->refresh();
        $offerB->refresh();

        $this->assertEquals('accepted', $offerA->status);
        $this->assertEquals('rejected', $offerB->status);
    }

    /** 5. Customer bisa membatalkan orderan jika tidak ada tawaran yang cocok. */
    public function test_customer_can_cancel_order_during_timeout()
    {
        $order = $this->createOpenOrder(['status' => 'ada_tawaran']);

        Offer::create([
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperA->id,
            'offered_price' => 12000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.orders.cancel', $order->id))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals('dibatalkan', $order->status);
        $this->assertEquals('customer', $order->cancelled_by_role);
        $this->assertEquals($this->customer->id, $order->cancelled_by_id);

        // Tawaran pending untuk order ini harus ikut dibersihkan (jadi rejected)
        $this->assertDatabaseHas('offers', [
            'order_id' => $order->id,
            'jastiper_id' => $this->jastiperA->id,
            'status' => 'rejected',
        ]);
    }

    /** Bonus: expand-radius cukup menyentuh timestamp order, tidak error. */
    public function test_customer_can_expand_radius()
    {
        $order = $this->createOpenOrder();
        $originalUpdatedAt = $order->updated_at;

        sleep(1);

        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.orders.expand-radius', $order->id))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertTrue($order->updated_at->gt($originalUpdatedAt));
    }

    /** Bonus: bulk offer (multi-select) mengisi harga otomatis = estimated_fare tiap order. */
    public function test_jastiper_can_submit_bulk_offers_at_estimated_fare()
    {
        $orderX = $this->createOpenOrder(['description' => 'Titip A', 'estimated_fare' => 10000]);
        $orderY = $this->createOpenOrder(['description' => 'Titip B', 'estimated_fare' => 20000]);

        $this->actingAs($this->jastiperA, 'jastiper')
            ->postJson(route('jastiper.orders.multi-offer'), ['order_ids' => [$orderX->id, $orderY->id]])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'submitted_count' => 2]);

        $this->assertDatabaseHas('offers', [
            'order_id' => $orderX->id,
            'jastiper_id' => $this->jastiperA->id,
            'offered_price' => 10000.00,
        ]);
        $this->assertDatabaseHas('offers', [
            'order_id' => $orderY->id,
            'jastiper_id' => $this->jastiperA->id,
            'offered_price' => 20000.00,
        ]);
    }

    /** Jastiper cannot start processing manually anymore, returns 409 */
    public function test_jastiper_cannot_start_processing_order_manually()
    {
        $order = $this->createOpenOrder([
            'jastiper_id' => $this->jastiperA->id,
            'status' => 'menunggu_pembayaran',
            'agreed_fare' => 12000.00,
        ]);

        $this->actingAs($this->jastiperA, 'jastiper')
            ->postJson(route('jastiper.orders.start-process', $order->id))
            ->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    /** Jastiper can complete their assigned order (status -> selesai) */
    public function test_jastiper_can_complete_order()
    {
        $order = $this->createOpenOrder([
            'jastiper_id' => $this->jastiperA->id,
            'status' => 'diproses',
            'agreed_fare' => 12000.00,
        ]);

        $this->actingAs($this->jastiperA, 'jastiper')
            ->postJson(route('jastiper.orders.complete', $order->id))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
    }

    /** Polling customer feed should include active/processing orders */
    public function test_customer_feed_includes_in_progress_orders()
    {
        $order = $this->createOpenOrder([
            'jastiper_id' => $this->jastiperA->id,
            'status' => 'diproses',
            'agreed_fare' => 12000.00,
        ]);

        $this->actingAs($this->customer, 'customer')
            ->getJson(route('customer.orders.active-feed'))
            ->assertStatus(200)
            ->assertJsonCount(1, 'orders')
            ->assertJsonFragment([
                'id' => $order->id,
                'status' => 'diproses',
            ]);
    }
}