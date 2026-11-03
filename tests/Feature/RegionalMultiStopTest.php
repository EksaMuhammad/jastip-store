<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Wilayah;
use App\Services\DistanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegionalMultiStopTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $now = now();
        Wilayah::firstOrCreate(
            ['name' => 'Surabaya Kota'],
            ['default_radius_km' => 15.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
        );
        Wilayah::firstOrCreate(
            ['name' => 'Jawa Timur (Antar Kota)'],
            ['default_radius_km' => 100.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
        );

        $this->customer = Customer::create([
            'phone_number' => '081299887766',
            'name' => 'Customer Surabaya',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_can_create_custom_buy_in_surabaya_and_link_to_cart()
    {
        $this->actingAs($this->customer, 'customer');

        // Form create order dengan toko Jokopi Surabaya
        Livewire::test('customer.create-order-form')
            ->set('category', 'beli-antar')
            ->set('description', 'Titip Beli 2 Es Kopi Susu Jokopi Surabaya')
            ->set('store_name', 'Jokopi Surabaya')
            ->set('origin_address', 'Jokopi, Jl. Dr. Ir. H. Soekarno, Rungkut, Surabaya')
            ->set('origin_lat', -7.326003)
            ->set('origin_lng', 112.780842)
            ->set('destination_address', 'Apartemen Gunawangsa Merr, Rungkut, Surabaya')
            ->set('destination_lat', -7.315000)
            ->set('destination_lng', 112.785000)
            ->set('estimated_item_price', 36000)
            ->call('submitOrder')
            ->assertRedirect(route('customer.cart.index'));

        // Pastikan merchant otomatis terbuat dengan alamat & koordinat Surabaya
        $merchant = Merchant::where('name', 'Jokopi Surabaya')->first();
        $this->assertNotNull($merchant);
        $this->assertEquals(-7.326003, (float) $merchant->latitude);
        $this->assertEquals(112.780842, (float) $merchant->longitude);

        // Pastikan CartItem terbuat di keranjang
        $cart = Cart::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($cart);
        $this->assertEquals(1, $cart->cartItems()->count());

        $item = $cart->cartItems()->first();
        $this->assertEquals('custom_buy', $item->item_type);
        $this->assertEquals(-7.326003, (float) $item->origin_lat);
        $this->assertEquals(112.780842, (float) $item->origin_lng);
    }

    public function test_multi_stop_with_surabaya_and_intercity_route_calculates_distance()
    {
        $this->actingAs($this->customer, 'customer');

        // Jokopi Surabaya (-7.3260, 112.7808) ke Alun-Alun Malang (-7.9829, 112.6308)
        $distance = DistanceService::haversine(-7.3260, 112.7808, -7.9829, 112.6308);
        $this->assertGreaterThan(70, $distance);
        $this->assertLessThan(95, $distance);

        // Simulasi keranjang dengan multi-stop service
        $cart = Cart::create(['customer_id' => $this->customer->id]);
        $stopsPayload = [
            ['address' => 'Jokopi Surabaya', 'notes' => '1 Kopi', 'lat' => -7.3260, 'lng' => 112.7808],
            ['address' => 'Pusat Oleh-Oleh Sidoarjo', 'notes' => 'Bandeng Asap', 'lat' => -7.4478, 'lng' => 112.7183],
        ];

        CartItem::create([
            'cart_id' => $cart->id,
            'item_type' => 'delivery_service',
            'category' => 'multi-stop',
            'name' => 'Multi-Stop: 2 Titik',
            'description' => 'Titip beli Jokopi lalu mampir ke Sidoarjo',
            'origin_address' => 'Titik 1: Jokopi Surabaya ➔ Titik 2: Sidoarjo',
            'origin_lat' => -7.3260,
            'origin_lng' => 112.7808,
            'destination_address' => 'Jl. Ijen No. 10, Kota Malang',
            'destination_lat' => -7.9829,
            'destination_lng' => 112.6308,
            'stops_data' => $stopsPayload,
            'quantity' => 1,
            'estimated_price' => 0,
        ]);

        // Checkout order
        $response = $this->post(route('customer.cart.checkout'), [
            'destination_address' => 'Jl. Ijen No. 10, Kota Malang',
            'destination_lat' => -7.9829,
            'destination_lng' => 112.6308,
            'recipient_name' => 'Budi Santoso',
            'recipient_phone' => '081234567890',
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('multi-stop', $order->category);
        $this->assertNotNull($order->multi_stops);
        $this->assertCount(2, $order->multi_stops);
        $this->assertStringContainsString('Jokopi Surabaya', $order->multi_stops[0]['name']);
        $this->assertEquals(-7.3260, (float) $order->multi_stops[0]['lat']);
    }

    public function test_multi_stop_order_is_broadcast_only_to_jastipers_covering_first_stop()
    {
        $this->actingAs($this->customer, 'customer');

        // Order multi-stop: Toko 1 di Surabaya, Tujuan di Sidoarjo
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'wilayah_id' => 1,
            'category' => 'multi-stop',
            'weight_category' => 'ringan',
            'description' => 'Titip Jokopi Surabaya ke Sidoarjo',
            'origin_address' => 'Stop 1: Jokopi Surabaya ➔ Stop 2: Alamat Tujuan',
            'origin_lat' => -7.3260, // Surabaya
            'origin_lng' => 112.7808,
            'destination_address' => 'Sedengan Mijen, Sidoarjo',
            'destination_lat' => -7.4316,
            'destination_lng' => 112.5958,
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 50000,
            'status' => 'menunggu_tawaran',
        ]);

        // Jastiper A: Berada di Surabaya dekat Toko 1 (radius 5 KM)
        $jastiperSurabaya = \App\Models\Jastiper::create([
            'name' => 'Jastiper Surabaya',
            'phone_number' => '081234567801',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'is_available' => true,
            'current_lat' => -7.3250, // Dekat Toko 1 (jarak < 1 KM)
            'current_lng' => 112.7800,
            'radius_km' => 5.0,
            'wilayah_id' => 1,
        ]);

        // Jastiper B: Berada di Malang (jauh dari Toko 1, > 70 KM)
        $jastiperMalang = \App\Models\Jastiper::create([
            'name' => 'Jastiper Malang',
            'phone_number' => '081234567802',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'is_available' => true,
            'current_lat' => -7.9829, // Malang
            'current_lng' => 112.6308,
            'radius_km' => 5.0,
            'wilayah_id' => 1,
        ]);

        // Jastiper Surabaya mengakses feed: order HARUS muncul
        $this->actingAs($jastiperSurabaya, 'jastiper');
        $responseSurabaya = $this->getJson(route('jastiper.orders.feed'));
        $responseSurabaya->assertStatus(200);
        $orderIdsSurabaya = collect($responseSurabaya->json('orders'))->pluck('id');
        $this->assertTrue($orderIdsSurabaya->contains($order->id));

        // Jastiper Malang mengakses feed: order TIDAK BOLEH muncul
        $this->actingAs($jastiperMalang, 'jastiper');
        $responseMalang = $this->getJson(route('jastiper.orders.feed'));
        $responseMalang->assertStatus(200);
        $orderIdsMalang = collect($responseMalang->json('orders'))->pluck('id');
        $this->assertFalse($orderIdsMalang->contains($order->id));
    }
}
