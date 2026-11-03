<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnifiedCartTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Merchant $merchant;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Budi Santoso',
            'password' => bcrypt('password123'),
        ]);

        $category = Category::first() ?? Category::create(['name' => 'Minimarket', 'type' => 'mart']);

        $this->merchant = Merchant::create([
            'name' => 'Indomaret Wonoayu',
            'address' => 'Jl. Raya Wonoayu No. 12, Sidoarjo',
            'type' => 'mart',
            'latitude' => -7.4500,
            'longitude' => 112.6000,
        ]);

        $this->product = Product::create([
            'merchant_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Roti Tawar Sari Roti',
            'estimated_price' => 15000,
            'is_available' => true,
        ]);
    }

    public function test_can_add_custom_buy_item_to_cart_and_calculate_route()
    {
        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);

        $cartItem = $cart->cartItems()->create([
            'item_type' => 'custom_buy',
            'category' => 'beli-antar',
            'name' => 'Nasi Goreng Pak Man',
            'description' => 'Pedas sedang, tambah telur dadar',
            'estimated_price' => 25000,
            'origin_address' => 'Jl. Soekarno Hatta No. 88, Malang',
            'origin_lat' => -7.9445,
            'origin_lng' => 112.6180,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'item_type' => 'custom_buy',
            'name' => 'Nasi Goreng Pak Man',
        ]);

        // Hitung rute via AJAX ke Alun-Alun Malang
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.cart.calculate-route'), [
                'destination_lat' => -7.9829,
                'destination_lng' => 112.6308,
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('total_distance_km', $data);
        $this->assertEquals(50000, $data['total_items_price']); // 2 x 25.000
        $this->assertGreaterThan(0, $data['estimated_fare']);
    }

    public function test_can_add_delivery_service_item_to_cart()
    {
        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);

        $cartItem = $cart->cartItems()->create([
            'item_type' => 'delivery_service',
            'category' => 'dokumen',
            'name' => 'Antar Berkas Ijazah Asli',
            'description' => 'Jangan dilipat, masukkan map plastik',
            'origin_address' => 'Jl. Ijen No. 10, Malang',
            'origin_lat' => -7.9700,
            'origin_lng' => 112.6200,
            'destination_address' => 'Kantor Pos Klojen, Malang',
            'destination_lat' => -7.9800,
            'destination_lng' => 112.6300,
            'recipient_name' => 'Ahmad R',
            'recipient_phone' => '08555555555',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'item_type' => 'delivery_service',
            'name' => 'Antar Berkas Ijazah Asli',
        ]);

        $res = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.cart.index'));

        $res->assertStatus(200);
        $res->assertSee('Antar Berkas Ijazah Asli');
        $res->assertSee('Jasa Pengantaran / Kurir Jastip');
        $res->assertSee('Ahmad R');
    }

    public function test_mixed_cart_combining_catalog_custom_buy_and_delivery_service()
    {
        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);

        // 1. Produk Katalog
        $cart->cartItems()->create([
            'product_id' => $this->product->id,
            'item_type' => 'catalog',
            'category' => 'toko-kirim',
            'name' => $this->product->name,
            'estimated_price' => $this->product->final_price,
            'quantity' => 2,
        ]);

        // 2. Titip Beli Kustom
        $cart->cartItems()->create([
            'item_type' => 'custom_buy',
            'category' => 'beli-antar',
            'name' => 'Sate Ayam Ponorogo',
            'description' => 'Bumbu kacang dipisah',
            'estimated_price' => 30000,
            'origin_address' => 'Jl. Semeru No. 4, Malang',
            'origin_lat' => -7.9750,
            'origin_lng' => 112.6250,
            'quantity' => 1,
        ]);

        // 3. Jasa Antar Barang
        $cart->cartItems()->create([
            'item_type' => 'delivery_service',
            'category' => 'ambil-antar',
            'name' => 'Ambil Kunci Kos Ketinggalan',
            'description' => 'Ambil di pos satpam',
            'origin_address' => 'Jl. Sigura-gura No. 2, Malang',
            'origin_lat' => -7.9550,
            'origin_lng' => 112.6100,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.cart.index'));

        $response->assertStatus(200);

        // Memastikan ketiga bagian dirender
        $response->assertSee('Produk Katalog Toko');
        $response->assertSee('Indomaret Wonoayu');
        $response->assertSee('Roti Tawar Sari Roti');

        $response->assertSee('Titip Beli Kustom (Non-Katalog)');
        $response->assertSee('Sate Ayam Ponorogo');

        $response->assertSee('Jasa Pengantaran / Kurir Jastip');
        $response->assertSee('Ambil Kunci Kos Ketinggalan');

        // Memastikan rute berantai menampilkan 3 titik stop
        $response->assertSee('Rute Pengantaran Berantai');
        $response->assertSee('3 Titik Penjemputan / Belanja');
    }

    public function test_checkout_mixed_cart_creates_unified_order_and_clears_cart()
    {
        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);

        // 1. Produk Katalog: Roti (2x 15.000 = 30.000)
        $cart->cartItems()->create([
            'product_id' => $this->product->id,
            'item_type' => 'catalog',
            'category' => 'toko-kirim',
            'name' => $this->product->name,
            'estimated_price' => $this->product->final_price,
            'quantity' => 2,
        ]);

        // 2. Titip Beli Kustom: Sate (1x 30.000)
        $cart->cartItems()->create([
            'item_type' => 'custom_buy',
            'category' => 'beli-antar',
            'name' => 'Sate Ayam',
            'description' => 'Bumbu kacang',
            'estimated_price' => 30000,
            'origin_address' => 'Jl. Semeru No. 4, Malang',
            'origin_lat' => -7.9750,
            'origin_lng' => 112.6250,
            'quantity' => 1,
        ]);

        // 3. Jasa Antar: Kunci (biaya barang 0)
        $cart->cartItems()->create([
            'item_type' => 'delivery_service',
            'category' => 'ambil-antar',
            'name' => 'Ambil Kunci',
            'description' => 'Di pos satpam',
            'origin_address' => 'Jl. Sigura-gura No. 2, Malang',
            'origin_lat' => -7.9550,
            'origin_lng' => 112.6100,
            'quantity' => 1,
        ]);

        $checkoutResponse = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.cart.checkout'), [
                'destination_address' => 'Jl. Alun-Alun No. 1, Malang',
                'destination_lat' => -7.9829,
                'destination_lng' => 112.6308,
                'recipient_name' => 'Budi Santoso',
                'recipient_phone' => '081234567890',
            ]);

        $checkoutResponse->assertRedirect(route('customer.dashboard'));
        $checkoutResponse->assertSessionHas('success');

        // Pastikan hanya 1 Order yang dibuat
        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('multi-stop', $order->category);
        $this->assertTrue($order->is_catalog_order);
        $this->assertEquals('menunggu_tawaran', $order->status);

        // Verifikasi multi_stops berantai
        $this->assertIsArray($order->multi_stops);
        $this->assertCount(3, $order->multi_stops);

        // Verifikasi OrderItem tersimpan dengan benar
        $orderItems = $order->orderItems;
        $this->assertCount(3, $orderItems);

        $catalogOrderItem = $orderItems->firstWhere('product_id', $this->product->id);
        $this->assertNotNull($catalogOrderItem);
        $this->assertEquals(2, $catalogOrderItem->quantity);

        $customOrderItem = $orderItems->first(fn($it) => str_contains($it->name, '[Titip Beli]'));
        $this->assertNotNull($customOrderItem);
        $this->assertEquals(30000, (int) $customOrderItem->estimated_price);

        $serviceOrderItem = $orderItems->first(fn($it) => str_contains($it->name, '[AMBIL ANTAR]'));
        $this->assertNotNull($serviceOrderItem);
        $this->assertEquals(0, (int) $serviceOrderItem->estimated_price);

        // Verifikasi perhitungan DP: 50% barang (30.000 + 30.000 = 60.000 -> 30.000) + full ongkir
        $expectedDpBarang = 60000 * 0.5;
        $this->assertEquals($expectedDpBarang + $order->estimated_fare, (float) $order->downpayment_amount);

        // Pastikan Cart telah dikosongkan
        $this->assertCount(0, $cart->fresh()->cartItems);
    }

    public function test_customer_can_clear_entire_cart()
    {
        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);
        $cart->cartItems()->create([
            'item_type' => 'custom_buy',
            'name' => 'Item 1',
            'estimated_price' => 10000,
        ]);
        $cart->cartItems()->create([
            'item_type' => 'delivery_service',
            'name' => 'Item 2',
        ]);

        $this->assertCount(2, $cart->cartItems);

        $response = $this->actingAs($this->customer, 'customer')
            ->delete(route('customer.cart.clear'));

        $response->assertRedirect(route('customer.cart.index'));
        $this->assertCount(0, $cart->fresh()->cartItems);
    }

    public function test_create_order_form_submits_to_cart_instead_of_direct_order()
    {
        $this->actingAs($this->customer, 'customer');

        // Test Livewire component form submission
        Livewire::test('customer.create-order-form')
            ->set('category', 'ambil-antar')
            ->set('weight_category', 'ringan')
            ->set('description', 'Tolong ambilkan paket baju di laundry')
            ->set('origin_address', 'Jl. Gajayana No. 10, Malang')
            ->set('origin_lat', -7.9500)
            ->set('origin_lng', 112.6100)
            ->set('destination_address', 'Jl. Ijen No. 5, Malang')
            ->set('destination_lat', -7.9700)
            ->set('destination_lng', 112.6200)
            ->set('recipient_name', 'Budi Santoso')
            ->set('recipient_phone', '081234567890')
            ->call('submitOrder')
            ->assertRedirect(route('customer.cart.index'));

        // Tidak boleh ada Order langsung yang terbentuk
        $this->assertDatabaseMissing('orders', [
            'customer_id' => $this->customer->id,
        ]);

        // Harus masuk ke CartItem
        $this->assertDatabaseHas('cart_items', [
            'item_type' => 'delivery_service',
            'category' => 'ambil-antar',
            'origin_address' => 'Jl. Gajayana No. 10, Malang',
            'recipient_name' => 'Budi Santoso',
        ]);
    }

    public function test_custom_buy_form_auto_creates_merchant_in_database_and_links_to_cart_item()
    {
        $this->actingAs($this->customer, 'customer');

        // Pastikan warung belum ada di database
        $this->assertDatabaseMissing('merchants', [
            'name' => 'Warung Nasgor Cak Slamet',
        ]);

        // Submit form titip beli kustom dengan nama warung
        Livewire::test('customer.create-order-form')
            ->set('category', 'beli-antar')
            ->set('weight_category', 'ringan')
            ->set('store_name', 'Warung Nasgor Cak Slamet')
            ->set('description', 'Beli 2 porsi nasi goreng gila pedas manis')
            ->set('estimated_item_price', 40000)
            ->set('origin_address', 'Jl. Sigura-gura No. 15, Malang')
            ->set('origin_lat', -7.9550)
            ->set('origin_lng', 112.6100)
            ->set('destination_address', 'Jl. Ijen No. 5, Malang')
            ->set('destination_lat', -7.9700)
            ->set('destination_lng', 112.6200)
            ->set('recipient_name', 'Budi Santoso')
            ->set('recipient_phone', '081234567890')
            ->call('submitOrder')
            ->assertRedirect(route('customer.cart.index'));

        // 1. Toko/Warung harus otomatis dibuat di tabel merchants
        $merchant = Merchant::where('name', 'Warung Nasgor Cak Slamet')->first();
        $this->assertNotNull($merchant);
        $this->assertEquals('food', $merchant->type);
        $this->assertEquals('Jl. Sigura-gura No. 15, Malang', $merchant->address);
        $this->assertEquals(-7.9550, $merchant->latitude);
        $this->assertEquals(112.6100, $merchant->longitude);
        $this->assertTrue((bool) $merchant->is_active);

        // 2. CartItem harus terhubung ke merchant_id tersebut
        $cartItem = CartItem::where('item_type', 'custom_buy')->latest()->first();
        $this->assertNotNull($cartItem);
        $this->assertEquals($merchant->id, $cartItem->merchant_id);
        $this->assertEquals('Warung Nasgor Cak Slamet', $cartItem->merchant->name);

        // 3. Checkout pesanan keranjang
        $checkoutResponse = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.cart.checkout'), [
                'destination_address' => 'Jl. Ijen No. 5, Malang',
                'destination_lat' => -7.9700,
                'destination_lng' => 112.6200,
                'recipient_name' => 'Budi Santoso',
                'recipient_phone' => '081234567890',
            ]);

        $checkoutResponse->assertRedirect(route('customer.dashboard'));

        // 4. Order dan OrderItem harus memuat informasi toko baru tersebut
        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals($merchant->id, $order->merchant_id);

        $orderItem = OrderItem::where('order_id', $order->id)->first();
        $this->assertNotNull($orderItem);
        $this->assertStringContainsString('Warung Nasgor Cak Slamet', $orderItem->name);
    }
}
