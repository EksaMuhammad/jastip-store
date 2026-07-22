<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JastipDirectBookingTest extends TestCase
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

        // Buat Customer
        $this->customer = Customer::create([
            'phone_number' => '081111111111',
            'name' => 'Budi Utomo',
            'password' => bcrypt('password123'),
        ]);

        // Buat Jastiper
        $this->jastiper = Jastiper::create([
            'phone_number' => '082222222222',
            'name' => 'Joko Driver',
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);
    }

    public function test_jastiper_can_checkin_and_checkout()
    {
        $this->actingAs($this->jastiper, 'jastiper');

        // Test Check-in
        $this->post(route('jastiper.checkin'), [
            'action' => 'checkin',
            'location_name' => 'Mie Gacoan Lowokwaru',
        ])->assertRedirect();

        $this->jastiper->refresh();
        $this->assertEquals('Mie Gacoan Lowokwaru', $this->jastiper->checkin_location);
        $this->assertNotNull($this->jastiper->checked_in_at);

        // Test Check-out
        $this->post(route('jastiper.checkin'), [
            'action' => 'checkout',
        ])->assertRedirect();

        $this->jastiper->refresh();
        $this->assertNull($this->jastiper->checkin_location);
        $this->assertNull($this->jastiper->checked_in_at);
    }

    public function test_customer_can_toggle_favorite_jastiper()
    {
        $this->actingAs($this->customer, 'customer');

        // Favorite
        $this->post(route('customer.jastiper.favorite', $this->jastiper->id))
            ->assertRedirect();

        $this->assertDatabaseHas('customer_favorites', [
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
        ]);

        // Unfavorite
        $this->post(route('customer.jastiper.favorite', $this->jastiper->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('customer_favorites', [
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
        ]);
    }

    public function test_realtime_availability_endpoint()
    {
        $this->jastiper->update([
            'checkin_location' => 'Starbucks Ijen',
            'checked_in_at' => now(),
            'is_available' => true,
        ]);

        $this->actingAs($this->customer, 'customer')
            ->get(route('customer.jastiper.availability', $this->jastiper->id))
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->jastiper->id,
                'is_available' => true,
                'checkin_location' => 'Starbucks Ijen',
            ]);
    }

    public function test_customer_can_submit_direct_request_booking()
    {
        $this->actingAs($this->customer, 'customer');

        // Simulate Livewire direct booking submission
        Livewire::test('customer.create-order-form', ['jastiper_id' => $this->jastiper->id, 'direct_jastiper_name' => $this->jastiper->name])
            ->set('category', 'beli-antar')
            ->set('weight_category', 'ringan')
            ->set('description', 'Titip Mie Gacoan Level 3')
            ->set('origin_address', 'Mie Gacoan Lowokwaru')
            ->set('destination_address', 'Jl. Sukarno Hatta No 5')
            ->set('recipient_name', 'Budi')
            ->set('recipient_phone', '081234567890')
            ->set('distance', 1.5)
            ->set('jastiper_id', $this->jastiper->id)
            ->call('submitOrder')
            ->assertRedirect(route('customer.dashboard'));

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'description' => 'Titip Mie Gacoan Level 3',
            'status' => 'menunggu_tawaran',
        ]);
    }

    public function test_jastiper_can_accept_direct_booking()
    {
        // Buat order direct booking
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip Kopi Kenangan',
            'destination_address' => 'Kos B',
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 12000.00,
            'status' => 'menunggu_tawaran',
        ]);

        $this->actingAs($this->jastiper, 'jastiper')
            ->post(route('jastiper.orders.direct-accept', $order->id))
            ->assertRedirect(route('jastiper.dashboard'));

        $order->refresh();
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals($this->jastiper->id, $order->jastiper_id);
    }

    public function test_jastiper_can_reject_direct_booking_and_release_to_general_pool()
    {
        // Buat order direct booking
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip Kopi Kenangan',
            'destination_address' => 'Kos B',
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 12000.00,
            'status' => 'menunggu_tawaran',
        ]);

        $this->actingAs($this->jastiper, 'jastiper')
            ->post(route('jastiper.orders.direct-reject', $order->id))
            ->assertRedirect(route('jastiper.dashboard'));

        // Orderan kembali ke umum: jastiper_id di-set NULL, status tetap menunggu_tawaran
        $order->refresh();
        $this->assertEquals('menunggu_tawaran', $order->status);
        $this->assertNull($order->jastiper_id);
    }
}
