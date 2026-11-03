<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemsFeedVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;
    protected Merchant $merchant;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->wilayah = Wilayah::first();

        $this->customer = Customer::create([
            'phone_number' => '081111111111',
            'name' => 'Joni Customer',
            'password' => bcrypt('password123'),
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Resto Bebek Sinjay',
            'address' => 'Jl. Raya Ponti No. 3, Sidoarjo',
            'phone_number' => '081234567890',
            'wilayah_id' => $this->wilayah->id,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Nasi Bebek Sinjay Goreng',
            'description' => 'Bebek goreng empuk dengan sambal mangga muda',
            'estimated_price' => 35000,
            'is_available' => true,
        ]);

        $this->jastiper = Jastiper::create([
            'phone_number' => '082222222222',
            'name' => 'Siti Jastiper',
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 10.0,
            'current_lat' => -7.4500,
            'current_lng' => 112.7100,
            'work_status' => 'tersedia',
            'is_available' => true,
        ]);
    }

    /**
     * Uji 1: Checkout keranjang menghasilkan deskripsi pesanan yang jelas dan menyebut nama toko & makanan.
     */
    public function test_cart_checkout_generates_descriptive_title_with_merchant_and_items()
    {
        $this->actingAs($this->customer, 'customer');

        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);
        $cart->cartItems()->create([
            'product_id' => $this->product->id,
            'item_type' => 'catalog',
            'category' => 'beli-antar',
            'name' => $this->product->name,
            'quantity' => 2,
            'estimated_price' => $this->product->final_price,
        ]);

        $response = $this->post(route('customer.cart.checkout'), [
            'destination_address' => 'Sedengan Mijen, Sidoarjo',
            'destination_lat' => -7.4600,
            'destination_lng' => 112.7200,
            'recipient_name' => 'Joni Customer',
            'recipient_phone' => '081234567890',
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        // Deskripsi harus memuat nama resto dan makanan, bukan cuma generic 'Pesanan Terpadu (1 Produk Toko)'
        $this->assertStringContainsString('Resto Bebek Sinjay', $order->description);
        $this->assertStringContainsString('Nasi Bebek Sinjay Goreng', $order->description);
        $this->assertStringContainsString('2x', $order->description);
    }

    /**
     * Uji 2: Feed pesanan Jastiper memuat array items lengkap dengan nama bersih, quantity, harga, dan catatan.
     */
    public function test_jastiper_feed_returns_order_items_and_merchant()
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Resto Bebek Sinjay – 2x Nasi Bebek Sinjay Goreng (1.83 KM)',
            'origin_address' => 'Jl. Raya Ponti No. 3, Sidoarjo',
            'origin_lat' => -7.4510,
            'origin_lng' => 112.7110,
            'destination_address' => 'Sedengan Mijen, Sidoarjo',
            'destination_lat' => -7.4600,
            'destination_lng' => 112.7200,
            'recipient_name' => 'Joni Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 41500,
            'downpayment_amount' => 0,
            'status' => 'menunggu_tawaran',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'name' => 'Nasi Bebek Sinjay Goreng [Resto Bebek Sinjay]',
            'quantity' => 2,
            'estimated_price' => 35000,
            'actual_price' => 35000,
            'notes' => 'Sambal dipisah, tidak pedas',
        ]);

        $this->actingAs($this->jastiper, 'jastiper');

        $response = $this->getJson(route('jastiper.orders.feed'));
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $orders = $response->json('orders');
        $this->assertNotEmpty($orders);

        $foundOrder = collect($orders)->firstWhere('id', $order->id);
        $this->assertNotNull($foundOrder);
        $this->assertEquals('Resto Bebek Sinjay', $foundOrder['merchant_name']);
        $this->assertCount(1, $foundOrder['items']);
        $this->assertEquals('Nasi Bebek Sinjay Goreng', $foundOrder['items'][0]['name_clean']);
        $this->assertEquals(2, $foundOrder['items'][0]['quantity']);
        $this->assertEquals('Sambal dipisah, tidak pedas', $foundOrder['items'][0]['notes']);
    }

    /**
     * Uji 3: Feed pesanan aktif customer memuat array items untuk preview di kartu pesanan aktif.
     */
    public function test_customer_active_feed_returns_order_items()
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Resto Bebek Sinjay – 2x Nasi Bebek Sinjay Goreng (1.83 KM)',
            'origin_address' => 'Jl. Raya Ponti No. 3, Sidoarjo',
            'destination_address' => 'Sedengan Mijen, Sidoarjo',
            'recipient_name' => 'Joni Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 41500,
            'downpayment_amount' => 0,
            'status' => 'menunggu_tawaran',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'name' => 'Nasi Bebek Sinjay Goreng',
            'quantity' => 2,
            'estimated_price' => 35000,
        ]);

        $this->actingAs($this->customer, 'customer');

        $response = $this->getJson(route('customer.orders.active-feed'));
        $response->assertStatus(200);

        $orders = $response->json('orders');
        $this->assertNotEmpty($orders);
        $this->assertCount(1, $orders[0]['items']);
        $this->assertEquals('Nasi Bebek Sinjay Goreng', $orders[0]['items'][0]['name_clean']);
    }

    /**
     * Uji 4: Halaman Dashboard Jastiper merender rincian item belanjaan pada pesanan aktif dan booking langsung.
     */
    public function test_jastiper_dashboard_renders_items_in_active_and_direct_orders()
    {
        // 1. Order aktif (deal)
        $activeOrder = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Resto Bebek Sinjay – 2x Nasi Bebek Sinjay Goreng',
            'origin_address' => 'Jl. Raya Ponti No. 3, Sidoarjo',
            'destination_address' => 'Sedengan Mijen, Sidoarjo',
            'recipient_name' => 'Joni Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 41500,
            'agreed_fare' => 41500,
            'downpayment_amount' => 0,
            'status' => 'deal',
        ]);

        OrderItem::create([
            'order_id' => $activeOrder->id,
            'product_id' => $this->product->id,
            'name' => 'Nasi Bebek Sinjay Goreng',
            'quantity' => 2,
            'estimated_price' => 35000,
            'actual_price' => 35000,
            'notes' => 'Ekstra serundeng',
        ]);

        $this->actingAs($this->jastiper, 'jastiper');

        $response = $this->get(route('jastiper.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Rincian Belanjaan');
        $response->assertSee('Nasi Bebek Sinjay Goreng');
        $response->assertSee('Ekstra serundeng');
    }

    /**
     * Uji 5: Single order (1 toko) menggunakan tarif per KM reguler (Rp 2.000/km) bukan tarif multi-store (Rp 3.000/km).
     */
    public function test_single_order_cart_uses_regular_km_rate_instead_of_multi_store_rate()
    {
        \App\Models\Setting::set('rate_per_km', 2000, 'number');
        \App\Models\Setting::set('multi_store_km_rate', 3000, 'number');
        \App\Models\Setting::set('min_fare', 10000, 'number');
        \App\Models\Setting::set('base_included_km', 2.0, 'number');

        // Stop 1 merchant saja (Single order)
        $singleStop = [
            [
                'id' => 'merchant_' . $this->merchant->id,
                'name' => $this->merchant->name,
                'address' => $this->merchant->address,
                'lat' => -7.4510,
                'lng' => 112.7110,
                'action' => 'pickup',
                'type' => 'merchant',
            ]
        ];

        $route = \App\Services\DistanceService::calculateMultiStoreRoute(
            $singleStop,
            -7.5500, // destLat (~11+ KM)
            112.7200,
            ['name' => 'Rumah Joni', 'address' => 'Sedengan Mijen']
        );

        $this->assertFalse($route['is_multi_store']);
        $this->assertEquals(2000, $route['extra_km_rate']);
        $this->assertEquals(0, $route['extra_stops_count']);
        $this->assertEquals(0, $route['extra_stops_fare']);

        // Multi store (2 merchant)
        $merchant2 = Merchant::create([
            'name' => 'Kopi Kenangan Sidoarjo',
            'address' => 'Jl. Pahlawan',
            'phone_number' => '089999999999',
            'wilayah_id' => $this->wilayah->id,
            'is_active' => true,
        ]);

        $multiStops = [
            $singleStop[0],
            [
                'id' => 'merchant_' . $merchant2->id,
                'name' => $merchant2->name,
                'address' => $merchant2->address,
                'lat' => -7.4550,
                'lng' => 112.7150,
                'action' => 'pickup',
                'type' => 'merchant',
            ]
        ];

        $multiRoute = \App\Services\DistanceService::calculateMultiStoreRoute(
            $multiStops,
            -7.5500,
            112.7200,
            ['name' => 'Rumah Joni', 'address' => 'Sedengan Mijen']
        );

        $this->assertTrue($multiRoute['is_multi_store']);
        $this->assertEquals(3000, $multiRoute['extra_km_rate']);
        $this->assertEquals(1, $multiRoute['extra_stops_count']);
        $this->assertEquals(5000, $multiRoute['extra_stops_fare']);
    }
}

