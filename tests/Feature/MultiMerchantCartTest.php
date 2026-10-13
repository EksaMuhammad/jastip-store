<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Services\DistanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiMerchantCartTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Merchant $merchantA;
    protected Merchant $merchantB;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->customer = Customer::create([
            'phone_number' => '089999999999',
            'name' => 'Dewi Lestari',
            'password' => bcrypt('rahasia123'),
        ]);

        $category = Category::first() ?? Category::create(['name' => 'Makanan', 'type' => 'food']);

        // Toko 1: McD Dinoyo (-7.9483, 112.6138)
        $this->merchantA = Merchant::create([
            'name' => 'McDonalds Dinoyo',
            'address' => 'Jl. MT Haryono No. 1, Malang',
            'type' => 'food',
            'latitude' => -7.9483,
            'longitude' => 112.6138,
        ]);

        // Toko 2: Kopi Suhat (-7.9445, 112.6180) - jarak ~0.62 KM dari Toko 1
        $this->merchantB = Merchant::create([
            'name' => 'Kopi Suhat',
            'address' => 'Jl. Soekarno Hatta No. 15, Malang',
            'type' => 'food',
            'latitude' => -7.9445,
            'longitude' => 112.6180,
        ]);

        $this->productA = Product::create([
            'merchant_id' => $this->merchantA->id,
            'category_id' => $category->id,
            'name' => 'Burger McSpicy',
            'estimated_price' => 45000,
        ]);

        $this->productB = Product::create([
            'merchant_id' => $this->merchantB->id,
            'category_id' => $category->id,
            'name' => 'Es Kopi Susu Aren',
            'estimated_price' => 20000,
        ]);
    }

    public function test_customer_can_add_products_from_different_merchants_to_same_cart()
    {
        // 1. Tambah produk dari Toko A
        $resA = $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productA->id));

        $resA->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cart_count' => 1,
            ]);

        // 2. Tambah produk dari Toko B (Toko yang berbeda)
        $resB = $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productB->id));

        $resB->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cart_count' => 2,
            ]);

        // Verifikasi database cart
        $cart = $this->customer->cart;
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart->cartItems);
        $this->assertEquals(2, $cart->cartItems()->pluck('product_id')->count());
    }

    public function test_cart_index_displays_grouped_merchants_and_chained_route()
    {
        // Masukkan kedua produk ke cart
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productA->id));
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productB->id));

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.cart.index'));

        $response->assertStatus(200);
        $response->assertSee('McDonalds Dinoyo');
        $response->assertSee('Kopi Suhat');
        $response->assertSee('Burger McSpicy');
        $response->assertSee('Es Kopi Susu Aren');
        $response->assertSee('Rute Pengantaran Berantai');
        $response->assertSee('Total');
    }

    public function test_ajax_calculate_route_endpoint_computes_multi_store_chain()
    {
        // Masukkan kedua produk ke cart
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productA->id));
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productB->id));

        // Koordinat tujuan: Alun-Alun Malang Kota (-7.9829, 112.6308)
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.calculate-route'), [
                'destination_lat' => -7.9829,
                'destination_lng' => 112.6308,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Harus ada 2 legs: Leg 1 (Toko A -> Toko B), Leg 2 (Toko B -> Tujuan)
        $this->assertArrayHasKey('legs', $data);
        $this->assertCount(2, $data['legs']);
        $this->assertEquals('McDonalds Dinoyo', $data['legs'][0]['from_name']);
        $this->assertEquals('Kopi Suhat', $data['legs'][0]['to_name']);
        $this->assertEquals('Kopi Suhat', $data['legs'][1]['from_name']);
        $this->assertEquals('destination', $data['legs'][1]['to_type']);

        $this->assertGreaterThan(0, $data['total_distance_km']);
        $this->assertEquals(1, $data['extra_stops_count']);
        $this->assertEquals(5000, $data['extra_stops_fare']);
        $this->assertGreaterThan(10000, $data['estimated_fare']);
    }

    public function test_checkout_multi_merchant_creates_multi_stop_order_with_proper_stops()
    {
        // Masukkan kedua produk ke cart
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productA->id));
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productB->id));

        $checkoutResponse = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.cart.checkout'), [
                'delivery_location' => 'Jl. Alun-Alun No. 5, Malang',
                'destination_lat' => -7.9829,
                'destination_lng' => 112.6308,
            ]);

        $checkoutResponse->assertRedirect(route('customer.dashboard'));
        $checkoutResponse->assertSessionHas('success');

        // Pastikan Order dibuat
        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('multi-stop', $order->category);
        $this->assertEquals('menunggu_tawaran', $order->status);
        $this->assertTrue($order->is_catalog_order);

        // Pastikan multi_stops terisi lengkap untuk kedua merchant
        $this->assertIsArray($order->multi_stops);
        $this->assertCount(2, $order->multi_stops);
        $this->assertEquals('McDonalds Dinoyo', $order->multi_stops[0]['name']);
        $this->assertEquals('Kopi Suhat', $order->multi_stops[1]['name']);

        // Pastikan kedua item tersimpan di order_items
        $this->assertCount(2, $order->orderItems);

        // Pastikan keranjang dikosongkan setelah checkout
        $cart = $this->customer->cart->fresh();
        $this->assertCount(0, $cart->cartItems);
    }

    public function test_customer_can_update_quantity_in_cart()
    {
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productA->id));

        $cartItem = $this->customer->cart->cartItems()->first();

        $response = $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.update-qty', $cartItem->id), [
                'quantity' => 3,
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(3, $cartItem->fresh()->quantity);
    }

    public function test_customer_can_remove_item_from_cart()
    {
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productA->id));

        $cartItem = $this->customer->cart->cartItems()->first();

        $response = $this->actingAs($this->customer, 'customer')
            ->delete(route('customer.cart.remove', $cartItem->id));

        $response->assertRedirect(route('customer.cart.index'));
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_single_merchant_cart_maintains_standard_checkout()
    {
        // Hanya tambahkan 1 produk dari 1 toko
        $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.add', $this->productA->id));

        $checkoutResponse = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.cart.checkout'), [
                'delivery_location' => 'Jl. Ijen No. 10, Malang',
                'destination_lat' => -7.9700,
                'destination_lng' => 112.6200,
            ]);

        $checkoutResponse->assertRedirect(route('customer.dashboard'));

        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        // Kategori bukan multi-stop jika hanya 1 toko
        $this->assertNotEquals('multi-stop', $order->category);
        $this->assertEquals($this->merchantA->id, $order->merchant_id);
        $this->assertNull($order->multi_stops);
    }
}
