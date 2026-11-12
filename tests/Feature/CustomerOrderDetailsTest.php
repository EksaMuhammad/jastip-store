<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Wilayah $wilayah;
    protected Merchant $merchant;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wilayah = Wilayah::create([
            'name' => 'Malang',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'default_radius_km' => 10.0,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Budi Customer',
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Burger Bangor - Tlogomas',
            'email' => 'bangor@example.com',
            'password' => bcrypt('secret123'),
            'phone_number' => '081987654321',
            'type' => 'food',
            'address' => 'Jl. Raya Tlogomas No. 45, Malang',
            'latitude' => -7.9250,
            'longitude' => 112.5980,
            'description' => 'Burger lezat harga ramah kantong',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'French Fries',
            'description' => 'Kentang goreng renyah gurih',
            'estimated_price' => 20000,
            'discount_price' => 20000,
            'is_available' => true,
            'is_flash_sale' => false,
        ]);
    }

    public function test_customer_can_view_order_details_receipt_page()
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Pesanan French Fries Bangor',
            'origin_address' => 'Jl. Raya Tlogomas No. 45, Malang',
            'destination_address' => 'Jl. MT Haryono No. 10, Malang',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 8000,
            'agreed_fare' => 8000,
            'status' => 'deal',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'name' => 'French Fries',
            'quantity' => 2,
            'estimated_price' => 20000,
            'actual_price' => 20000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => 'transfer',
            'channel' => 'qris',
            'amount' => 48000,
            'status' => 'lunas',
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('Rincian Pesananmu');
        $response->assertSee('Rincian Pesanan');
        $response->assertSee('Informasi Pesanan');
        $response->assertSee('French Fries');
        $response->assertSee('Subtotal Pesanan');
        $response->assertSee('Biaya Pengiriman');
        $response->assertSee('Paid');
        $response->assertSee('SALIN');
    }

    public function test_customer_cannot_view_other_customers_order_detail()
    {
        $otherCustomer = Customer::create([
            'name' => 'Siti Customer',
            'phone_number' => '081299998888',
            'password' => bcrypt('password'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $order = Order::create([
            'customer_id' => $otherCustomer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Pesanan Rahasia Siti',
            'destination_address' => 'Jl. Ijen, Malang',
            'recipient_name' => 'Siti Customer',
            'recipient_phone' => '081299998888',
            'estimated_fare' => 10000,
            'status' => 'menunggu_tawaran',
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders.show', $order->id));

        $response->assertStatus(404);
    }

    public function test_customer_food_index_renders_original_layout_with_quick_filters()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.food'));

        $response->assertStatus(200);
        $response->assertSee('Kuliner (GoFood)');
        $response->assertSee('Resto Terdekat');
        $response->assertSee('Resto Terpopuler');
        $response->assertSee('Burger Bangor - Tlogomas');
    }

    public function test_customer_food_nearby_sekitarmu_renders_tabs_and_merchants()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.food.nearby', ['tab' => 'terdekat']));

        $response->assertStatus(200);
        $response->assertSee('Sekitarmu');
        $response->assertSee('Terkait');
        $response->assertSee('Terdekat');
        $response->assertSee('Terlaris');
        $response->assertSee('Rating');
        $response->assertSee('Burger Bangor - Tlogomas');
        $response->assertSee('Jarak Resto');
    }

    public function test_nearby_calculates_real_distance_when_coordinates_provided()
    {
        // Merchant Bangor berada di -7.9250, 112.5980
        // Customer berada 500 meter di dekatnya
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.food.nearby', [
                'tab' => 'terdekat',
                'lat' => -7.9260,
                'lng' => 112.5980,
            ]));

        $response->assertStatus(200);
        $response->assertSee('m'); // Real distance in meters
        $response->assertSee('GPS Terhubung');
    }

    public function test_customer_order_details_has_nota_preview_modal_and_share_view()
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Pesanan French Fries Bangor',
            'origin_address' => 'Jl. Raya Tlogomas No. 45, Malang',
            'destination_address' => 'Jl. MT Haryono No. 10, Malang',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 8000,
            'agreed_fare' => 8000,
            'status' => 'deal',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'name' => 'French Fries',
            'quantity' => 2,
            'estimated_price' => 20000,
            'actual_price' => 20000,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('showInvoiceModal: false', false);
        $response->assertSee('Pratinjau Nota Transaksi');
        $response->assertSee('Bagikan');
        $response->assertSee('Cetak / PDF');
        $response->assertSee('Tutup');
        $response->assertSee('shareInvoiceLink()', false);
    }

    public function test_guest_can_view_public_receipt_with_valid_token()
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Pesanan French Fries Bangor',
            'origin_address' => 'Jl. Raya Tlogomas No. 45, Malang',
            'destination_address' => 'Jl. MT Haryono No. 10, Malang',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 8000,
            'agreed_fare' => 8000,
            'status' => 'deal',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'name' => 'French Fries',
            'quantity' => 2,
            'estimated_price' => 20000,
            'actual_price' => 20000,
        ]);

        // Akses sebagai tamu (guest / tanpa login)
        $publicUrl = $order->public_receipt_url;
        $response = $this->get($publicUrl);

        $response->assertStatus(200);
        $response->assertSee('FAKTUR / NOTA TRANSAKSI');
        $response->assertSee('PT JASTIPKUY SOLUSI INDONESIA');
        $response->assertSee('French Fries');
        $response->assertSee('Bagikan');
        $response->assertSee('Cetak / PDF');
        $response->assertSee('Bukti Transaksi Resmi');
    }

    public function test_guest_cannot_view_public_receipt_with_invalid_or_missing_token()
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Pesanan French Fries Bangor',
            'origin_address' => 'Jl. Raya Tlogomas No. 45, Malang',
            'destination_address' => 'Jl. MT Haryono No. 10, Malang',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 8000,
            'agreed_fare' => 8000,
            'status' => 'deal',
        ]);

        // Tanpa token
        $responseNoToken = $this->get(route('orders.public_receipt', ['order' => $order->id]));
        $responseNoToken->assertStatus(403);

        // Token salah
        $responseWrongToken = $this->get(route('orders.public_receipt', ['order' => $order->id, 'token' => 'invalid-token-12345']));
        $responseWrongToken->assertStatus(403);
    }

    public function test_reference_photo_url_attribute_and_jastiper_feed_inclusion()
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip Bolu Raja Coklat Keju',
            'reference_photo' => 'orders/references/bolu_sample.jpg',
            'origin_address' => 'Jl. Raya Tlogomas No. 45, Malang',
            'origin_lat' => -7.9839,
            'origin_lng' => 112.6214,
            'destination_address' => 'Jl. MT Haryono No. 10, Malang',
            'destination_lat' => -7.9850,
            'destination_lng' => 112.6220,
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 12000,
            'status' => 'menunggu_tawaran',
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'name' => '[Titip Beli] Bolu Raja Coklat Keju',
            'quantity' => 2,
            'estimated_price' => 35000,
        ]);

        // Model Accessor assertions
        $this->assertNotNull($order->reference_photo_url);
        $this->assertStringContainsString('bolu_sample.jpg', $order->reference_photo_url);
        $this->assertNotNull($item->image_url);
        $this->assertStringContainsString('bolu_sample.jpg', $item->image_url);

        // Jastiper Feed assertion
        $jastiper = \App\Models\Jastiper::create([
            'name' => 'Jastiper Test',
            'phone_number' => '089999888777',
            'password' => bcrypt('secret123'),
            'wilayah_id' => $this->wilayah->id,
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'current_lat' => -7.9839,
            'current_lng' => 112.6214,
            'radius_km' => 20,
        ]);

        $feedResponse = $this->actingAs($jastiper, 'jastiper')->getJson(route('jastiper.orders.feed'));
        $feedResponse->assertStatus(200);
        $feedResponse->assertJsonFragment([
            'reference_photo_url' => $order->reference_photo_url,
        ]);

        // Customer Order Details view assertions
        $showResponse = $this->actingAs($this->customer, 'customer')->get(route('customer.orders.show', $order->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Foto Referensi Barang');
        $showResponse->assertSee('min-h-0');
        $showResponse->assertSee('Pratinjau Nota Transaksi');
    }
}
