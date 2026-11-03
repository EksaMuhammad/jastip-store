<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Services\DistanceService;
use App\Services\TariffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiDropoffFreshnessTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Merchant $foodMerchant;
    protected Product $coffeeProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Aditya Pratama',
            'password' => bcrypt('password123'),
        ]);

        $foodCat = Category::where('type', 'food')->first() ?? Category::create(['name' => 'Cafe & Minuman', 'type' => 'food']);

        $this->foodMerchant = Merchant::create([
            'name' => 'Kopi Kenangan Dinoyo',
            'address' => 'Jl. MT Haryono No. 100, Malang',
            'type' => 'food',
            'latitude' => -7.9500,
            'longitude' => 112.6100,
        ]);

        $this->coffeeProduct = Product::create([
            'merchant_id' => $this->foodMerchant->id,
            'category_id' => $foodCat->id,
            'name' => 'Es Kopi Kenangan Mantan (Dingin)',
            'estimated_price' => 18000,
            'is_available' => true,
        ]);
    }

    /**
     * Uji algoritma Smart Freshness Routing pada DistanceService:
     * Penjemputan & drop-off dokumen non-perishable harus didahulukan,
     * kemudian baru membeli es kopi dingin di titik akhir sebelum diantar ke customer.
     */
    public function test_distance_service_prioritizes_non_perishable_errand_before_perishable_food_pickup()
    {
        $stops = [
            // Perishable item input pertama kali
            [
                'id' => 'coffee_stop',
                'name' => 'Beli Es Kopi Kenangan Mantan',
                'address' => 'Kopi Kenangan Dinoyo',
                'lat' => -7.9500,
                'lng' => 112.6100,
                'action' => 'pickup',
                'is_perishable' => true,
            ],
            // Non-perishable errand pickup
            [
                'id' => 'doc_pickup',
                'name' => 'Jemput Berkas Notaris',
                'address' => 'Kantor Notaris Jl. Bandung',
                'lat' => -7.9600,
                'lng' => 112.6200,
                'action' => 'pickup',
                'is_perishable' => false,
            ],
            // Non-perishable errand dropoff ke client lain
            [
                'id' => 'doc_dropoff',
                'name' => 'Antar Berkas ke Bank Mandiri',
                'address' => 'Bank Mandiri Jl. Basuki Rahmat',
                'lat' => -7.9700,
                'lng' => 112.6300,
                'action' => 'dropoff',
                'recipient_name' => 'Pak Budi Hendra',
                'recipient_phone' => '081987654321',
                'is_perishable' => false,
            ],
        ];

        // Customer's own destination (Rumah)
        $destLat = -7.9800;
        $destLng = 112.6400;

        $result = DistanceService::calculateMultiStoreRoute($stops, $destLat, $destLng, [
            'name' => 'Rumah Customer',
            'address' => 'Jl. Ijen No. 45, Malang',
        ]);

        $this->assertTrue($result['has_freshness_optimization'], 'Freshness optimization flag must be true');
        $this->assertTrue($result['has_multi_dropoff'], 'Multi dropoff flag must be true');
        $this->assertEquals(1, $result['extra_dropoffs_count'], 'Should have 1 extra drop-off point');
        $this->assertGreaterThan(0, $result['extra_dropoffs_fare'], 'Should have extra drop-off fare');

        $ordered = $result['ordered_stops'];
        $this->assertCount(4, $ordered, 'Should have 3 stops + final customer destination');

        // Verifikasi urutan eksekusi:
        // 1. Notaris pickup (non-perishable pickup)
        $this->assertEquals('doc_pickup', $ordered[0]['id']);
        $this->assertEquals('pickup', $ordered[0]['action']);
        $this->assertFalse($ordered[0]['is_perishable']);

        // 2. Bank Mandiri dropoff (non-perishable dropoff)
        $this->assertEquals('doc_dropoff', $ordered[1]['id']);
        $this->assertEquals('dropoff', $ordered[1]['action']);
        $this->assertEquals('Pak Budi Hendra', $ordered[1]['recipient_name']);

        // 3. Kopi Kenangan pickup (perishable pickup - dibeli TERAKHIR sebelum ke customer!)
        $this->assertEquals('coffee_stop', $ordered[2]['id']);
        $this->assertEquals('pickup', $ordered[2]['action']);
        $this->assertTrue($ordered[2]['is_perishable']);

        // 4. Rumah Customer (final delivery)
        $this->assertEquals('final_destination', $ordered[3]['id']);
        $this->assertEquals('dropoff', $ordered[3]['action']);
    }

    /**
     * Uji tarif ekstra drop-off:
     * Menambah drop-off ke alamat penerima kedua dikenakan biaya tambahan (+Rp 6.000).
     */
    public function test_distance_service_calculates_extra_dropoff_fee()
    {
        $tariff = app(TariffService::class);
        $expectedExtraDropoffFee = (int) ($tariff->getAll()['extra_stop_fare'] ?? 6000);

        // Hanya 1 titik jemput dan 1 dropoff utama
        $singleDropoff = DistanceService::calculateMultiStoreRoute([
            ['name' => 'Toko A', 'lat' => -7.95, 'lng' => 112.61, 'action' => 'pickup', 'is_perishable' => false],
        ], -7.98, 112.64);

        $this->assertEquals(0, $singleDropoff['extra_dropoffs_count']);
        $this->assertEquals(0, $singleDropoff['extra_dropoffs_fare']);
        $this->assertFalse($singleDropoff['has_multi_dropoff']);

        // 1 titik jemput dan 2 dropoff (1 intermediate dropoff + 1 final destination)
        $twoDropoffs = DistanceService::calculateMultiStoreRoute([
            ['name' => 'Toko A', 'lat' => -7.95, 'lng' => 112.61, 'action' => 'pickup', 'is_perishable' => false],
            ['name' => 'Kantor Teman', 'lat' => -7.96, 'lng' => 112.62, 'action' => 'dropoff', 'is_perishable' => false],
        ], -7.98, 112.64);

        $this->assertEquals(1, $twoDropoffs['extra_dropoffs_count']);
        $this->assertEquals($expectedExtraDropoffFee, $twoDropoffs['extra_dropoffs_fare']);
        $this->assertTrue($twoDropoffs['has_multi_dropoff']);
        $this->assertGreaterThan($singleDropoff['estimated_fare'], $twoDropoffs['estimated_fare']);
    }

    /**
     * Uji bahwa CartController::collectStops menandai makanan/minuman sebagai perishable
     * dan menyusun titik multi-dropoff dengan benar.
     */
    public function test_cart_collects_stops_with_actions_and_detects_perishable_items()
    {
        $this->actingAs($this->customer, 'customer');

        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);

        // 1. Tambahkan produk katalog es kopi
        $cart->cartItems()->create([
            'product_id' => $this->coffeeProduct->id,
            'item_type' => 'catalog',
            'category' => 'beli-antar',
            'name' => $this->coffeeProduct->name,
            'quantity' => 2,
            'estimated_price' => $this->coffeeProduct->final_price,
        ]);

        // 2. Tambahkan jasa antar dokumen dengan penerima lain
        $cart->cartItems()->create([
            'item_type' => 'delivery_service',
            'category' => 'dokumen',
            'name' => 'Kirim Berkas Sertifikat',
            'description' => 'Tolong antar ke kantor BCA',
            'origin_address' => 'Jl. Dieng No. 10, Malang',
            'origin_lat' => -7.9700,
            'origin_lng' => 112.6200,
            'destination_address' => 'Kantor BCA Malang',
            'destination_lat' => -7.9800,
            'destination_lng' => 112.6300,
            'recipient_name' => 'Bapak Joko',
            'recipient_phone' => '081333444555',
            'quantity' => 1,
        ]);

        $response = $this->get(route('customer.cart.index'));
        $response->assertStatus(200);
        $response->assertSee('Es Kopi Kenangan Mantan');
        $response->assertSee('Kirim Berkas Sertifikat');
        $response->assertSee('Kantor BCA Malang');
        $response->assertSee('Bapak Joko');
        // Pastikan banner freshness routing muncul
        $response->assertSee('Smart Freshness Priority');
    }

    /**
     * Uji checkout pesanan terpadu dengan Multi-Dropoff:
     * Menyimpan kategori 'multi-stop', array multi_stops dengan action & recipient,
     * serta mencatat rincian penerima di OrderItem.
     */
    public function test_checkout_multi_dropoff_creates_multi_stop_order_with_ordered_stops()
    {
        $this->actingAs($this->customer, 'customer');

        $cart = Cart::firstOrCreate(['customer_id' => $this->customer->id]);

        // Produk es kopi
        $cart->cartItems()->create([
            'product_id' => $this->coffeeProduct->id,
            'item_type' => 'catalog',
            'category' => 'beli-antar',
            'name' => $this->coffeeProduct->name,
            'quantity' => 1,
            'estimated_price' => $this->coffeeProduct->final_price,
        ]);

        // Jasa antar berkas ke kantor
        $cart->cartItems()->create([
            'item_type' => 'delivery_service',
            'category' => 'dokumen',
            'name' => 'Kirim Berkas Notaris',
            'origin_address' => 'Kantor Notaris',
            'origin_lat' => -7.9600,
            'origin_lng' => 112.6200,
            'destination_address' => 'Kantor Klien',
            'destination_lat' => -7.9700,
            'destination_lng' => 112.6300,
            'recipient_name' => 'Ibu Rina',
            'recipient_phone' => '081222333444',
            'quantity' => 1,
        ]);

        $response = $this->post(route('customer.cart.checkout'), [
            'destination_address' => 'Rumah Customer Jl. Ijen 45',
            'destination_lat' => -7.9800,
            'destination_lng' => 112.6400,
            'recipient_name' => 'Aditya Pratama',
            'recipient_phone' => '081234567890',
        ]);

        $response->assertRedirect(route('customer.dashboard'));

        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('multi-stop', $order->category);
        $this->assertIsArray($order->multi_stops);
        $this->assertGreaterThanOrEqual(3, count($order->multi_stops));

        // Verifikasi bahwa titik multi_stops memiliki action dan data penerima
        $actions = array_column($order->multi_stops, 'action');
        $this->assertContains('pickup', $actions);
        $this->assertContains('dropoff', $actions);

        $dropoffStops = array_filter($order->multi_stops, fn($s) => ($s['action'] ?? '') === 'dropoff');
        $this->assertGreaterThanOrEqual(1, count($dropoffStops), 'Order should contain intermediate dropoff');
        $this->assertEquals('Rumah Customer Jl. Ijen 45', $order->destination_address);

        // Verifikasi order items
        $this->assertEquals(2, $order->orderItems()->count());
        $deliveryItem = $order->orderItems()->where('name', 'like', '%[DOKUMEN]%')->first();
        $this->assertNotNull($deliveryItem);
        $this->assertStringContainsString('Ibu Rina', $deliveryItem->notes);
        $this->assertStringContainsString('Kantor Klien', $deliveryItem->notes);
    }

    /**
     * Uji Livewire CreateOrderForm dengan opsi multi-stop dan toggle drop-off
     */
    public function test_create_order_form_supports_multi_stop_with_pickup_and_dropoff_actions()
    {
        $this->actingAs($this->customer, 'customer');

        $component = Livewire::test('customer.create-order-form')
            ->set('category', 'multi-stop')
            ->set('description', 'Titip jemput dokumen lalu antar ke kantor teman')
            ->set('destination_address', 'Rumah Saya Jl. Suhat')
            ->set('destination_lat', -7.9400)
            ->set('destination_lng', 112.6150)
            ->set('stops.0.action', 'pickup')
            ->set('stops.0.address', 'Kantor Law Firm Jl. Semeru')
            ->set('stops.0.notes', 'Ambil map merah')
            ->set('stops.0.lat', -7.9700)
            ->set('stops.0.lng', 112.6200)
            ->set('stops.1.action', 'dropoff')
            ->set('stops.1.address', 'Kantor Teman Jl. Kawi')
            ->set('stops.1.recipient_name', 'Doni Setiawan')
            ->set('stops.1.recipient_phone', '081234432100')
            ->set('stops.1.notes', 'Serahkan ke resepsionis')
            ->set('stops.1.lat', -7.9750)
            ->set('stops.1.lng', 112.6250)
            ->call('submitOrder');

        $component->assertRedirect(route('customer.cart.index'));

        $cart = Cart::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($cart);
        $cartItem = $cart->cartItems()->where('category', 'multi-stop')->first();
        $this->assertNotNull($cartItem);
        $this->assertIsArray($cartItem->stops_data);
        $this->assertEquals('pickup', $cartItem->stops_data[0]['action']);
        $this->assertEquals('dropoff', $cartItem->stops_data[1]['action']);
        $this->assertEquals('Doni Setiawan', $cartItem->stops_data[1]['recipient_name']);
        $this->assertEquals('081234432100', $cartItem->stops_data[1]['recipient_phone']);
    }
}
