<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JastipWorkflowTest extends TestCase
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
            'verification_status' => 'approved', // Lolos verifikasi
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);
    }

    public function test_customer_create_order_page_requires_auth()
    {
        $this->get(route('customer.orders.create'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_can_render_create_order_form()
    {
        $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders.create'))
            ->assertStatus(200)
            ->assertSeeLivewire('customer.create-order-form');
    }

    public function test_customer_can_submit_valid_jastip_request()
    {
        $this->actingAs($this->customer, 'customer');

        Livewire::test('customer.create-order-form')
            ->set('category', 'beli-antar')
            ->set('weight_category', 'ringan')
            ->set('description', 'Titip Nasi Goreng Spesial 1 Porsi Pedas')
            ->set('origin_address', 'Warung Bu Sri')
            ->set('destination_address', 'Jl. Lowokwaru No. 12')
            ->set('recipient_name', 'Budi Penerima')
            ->set('recipient_phone', '081234567890')
            ->set('distance', 3.5)
            ->call('submitOrder')
            ->assertRedirect(route('customer.dashboard'));

        // Cek apakah order tersimpan di DB
        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'category' => 'beli-antar',
            'status' => 'menunggu_tawaran',
            'description' => 'Titip Nasi Goreng Spesial 1 Porsi Pedas',
            'origin_address' => 'Warung Bu Sri',
            'destination_address' => 'Jl. Lowokwaru No. 12',
        ]);
    }

    public function test_jastiper_can_see_order_in_dashboard_feed()
    {
        // Set koordinat jastiper agar tidak null
        $this->jastiper->update([
            'current_lat' => -7.9839,
            'current_lng' => 112.6214,
        ]);

        // Buat order aktif di wilayah Malang dengan koordinat dekat jastiper
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip Martabak Keju',
            'origin_address' => 'Martabak 88',
            'origin_lat' => -7.9839,
            'origin_lng' => 112.6214,
            'destination_address' => 'Kos A',
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 15000.00,
            'status' => 'menunggu_tawaran',
        ]);

        // Karena feed di-load secara async via JSON, kita hit JSON feed endpoint
        $this->actingAs($this->jastiper, 'jastiper')
            ->get(route('jastiper.orders.feed'))
            ->assertStatus(200)
            ->assertJsonFragment([
                'description' => 'Titip Martabak Keju',
                'estimated_fare_formatted' => 'Rp 15.000',
            ]);
    }

    public function test_jastiper_can_accept_order()
    {
        // Buat order aktif di wilayah Malang
        $order = Order::create([
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
        ]);

        $this->actingAs($this->jastiper, 'jastiper')
            ->post(route('jastiper.orders.accept', $order->id))
            ->assertRedirect(route('jastiper.dashboard'));

        // Pastikan status order berubah di DB
        $order->refresh();
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals($this->jastiper->id, $order->jastiper_id);
    }

    public function test_unverified_jastiper_cannot_accept_order()
    {
        // Ubah status verifikasi jastiper ke 'belum'
        $this->jastiper->update(['verification_status' => 'belum']);

        // Buat order aktif
        $order = Order::create([
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
        ]);

        $this->actingAs($this->jastiper, 'jastiper')
            ->post(route('jastiper.orders.accept', $order->id))
            ->assertSessionHas('error');

        // Pastikan status order tidak berubah
        $order->refresh();
        $this->assertEquals('menunggu_tawaran', $order->status);
        $this->assertNull($order->jastiper_id);
    }
}
