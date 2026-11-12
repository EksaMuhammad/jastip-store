<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Topup;
use App\Models\Wallet;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MajorRevisionsFivePointsTest extends TestCase
{
    use RefreshDatabase;

    protected Wilayah $wilayah;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        Storage::fake('public');

        $this->wilayah = Wilayah::first() ?: Wilayah::create([
            'name' => 'Malang',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'default_radius_km' => 10.0,
            'is_active' => true,
        ]);

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
    }

    /**
     * Test Point 1: Customer Order Invoice & Topup Receipt for Reimbursement
     */
    public function test_customer_can_view_official_order_invoice_and_reimbursement_receipt()
    {
        $customer = Customer::create([
            'name' => 'Budi Reimburse',
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $jastiper = Jastiper::create([
            'name' => 'Driver Jastip',
            'phone_number' => '081298765432',
            'password' => bcrypt('password'),
            'vehicle_plate' => 'N 1234 AB',
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'radius_km' => 10.0,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'jastiper_id' => $jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip Kopi & Roti Kantor',
            'status' => 'selesai',
            'estimated_fare' => 15000,
            'agreed_fare' => 15000,
            'destination_address' => 'Gedung Wisma BRI Lt 5, Malang',
            'recipient_name' => 'Budi Reimburse',
            'recipient_phone' => '081234567890',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'name' => 'Kopi Kenangan Mantan',
            'quantity' => 2,
            'estimated_price' => 18000,
            'actual_price' => 18000,
        ]);

        // Access as owner
        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.orders.invoice', $order->id));

        $response->assertStatus(200);
        $response->assertSee('PT JASTIPKUY SOLUSI INDONESIA');
        $response->assertSee('FAKTUR / NOTA TRANSAKSI');
        $response->assertSee('Titip Kopi', false);
        $response->assertSee('Kopi Kenangan Mantan');
        $response->assertSee('Budi Reimburse');
        $response->assertSee('LUNAS / TRANSAKSI VALID');

        // Other customer cannot view
        $otherCustomer = Customer::create([
            'name' => 'Other User',
            'phone_number' => '081299990000',
            'password' => bcrypt('password'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $responseOther = $this->actingAs($otherCustomer, 'customer')
            ->get(route('customer.orders.invoice', $order->id));
        $responseOther->assertStatus(404);
    }

    public function test_customer_can_view_official_topup_reimbursement_receipt()
    {
        $customer = Customer::create([
            'name' => 'Siti Reimburse',
            'phone_number' => '081288887777',
            'password' => bcrypt('password'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $wallet = Wallet::create([
            'owner_role' => 'customer',
            'owner_id' => $customer->id,
            'balance' => 250000,
        ]);

        $topup = Topup::create([
            'wallet_id' => $wallet->id,
            'amount' => 250000,
            'status' => 'berhasil',
            'channel' => 'BCA Virtual Account',
            'va_number' => '80777081288887777',
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.wallet.topup.invoice', $topup->id));

        $response->assertStatus(200);
        $response->assertSee('PT JASTIPKUY SOLUSI INDONESIA');
        $response->assertSee('BUKTI PENERIMAAN DANA (TOP-UP)');
        $response->assertSee('Siti Reimburse');
        $response->assertSee('250.000');
        $response->assertSee('BCA Virtual Account');
    }

    /**
     * Test Point 2: Admin Corporate Reports Print / Download (Mutasi, Customers, Jastipers)
     */
    public function test_admin_can_view_corporate_print_reports()
    {
        // 1. Mutations & Laba Rugi report
        $resMut = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.mutations.print', ['days' => '30']));
        $resMut->assertStatus(200);
        $resMut->assertSee('PT JASTIPKUY SOLUSI INDONESIA');
        $resMut->assertSee('LAPORAN KEUANGAN & MUTASI KAS', false);

        // 2. Customers report
        $resCust = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.print'));
        $resCust->assertStatus(200);
        $resCust->assertSee('LAPORAN DATA PELANGGAN (CUSTOMER)');

        // 3. Jastipers report
        $resJastip = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.jastipers.print'));
        $resJastip->assertStatus(200);
        $resJastip->assertSee('LAPORAN DATA MITRA JASTIPER');
    }

    /**
     * Test Point 3: Customer Notification Bell Aggregation
     */
    public function test_customer_notification_bell_aggregates_all_lifecycle_activities()
    {
        $customer = Customer::create([
            'name' => 'Notification Tester',
            'phone_number' => '081233334444',
            'password' => bcrypt('password'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $wallet = Wallet::create([
            'owner_role' => 'customer',
            'owner_id' => $customer->id,
            'balance' => 100000,
        ]);

        // Active order in flight
        $activeOrder = Order::create([
            'customer_id' => $customer->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Beli Martabak Manis',
            'status' => 'barang_diambil',
            'estimated_fare' => 12000,
            'destination_address' => 'Jl. Soekarno Hatta No 1',
            'recipient_name' => 'Notification Tester',
            'recipient_phone' => '081233334444',
        ]);

        // Successful topup
        Topup::create([
            'wallet_id' => $wallet->id,
            'amount' => 50000,
            'status' => 'berhasil',
            'channel' => 'Mandiri VA',
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Notifikasi & Aktivitas', false);
        $response->assertSee('Beli Martabak Manis');
        $response->assertSee('Jastiper telah selesai mengambil barang di toko');
        $response->assertSee('Top-Up E-Wallet Berhasil');
    }

    /**
     * Test Point 4: Food Category Filter
     */
    public function test_customer_food_category_filter_matches_products_and_merchants()
    {
        $customer = Customer::create([
            'name' => 'Foodie Customer',
            'phone_number' => '081211112222',
            'password' => bcrypt('password'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $catSweet = Category::firstOrCreate(['name' => 'Sweets', 'type' => 'food']);

        $merchantSweet = Merchant::create([
            'name' => 'Toko Roti Manis Legendaris',
            'email' => 'sweet@bakery.com',
            'password' => bcrypt('password'),
            'type' => 'food',
            'address' => 'Jl. Merbabu No 10, Malang',
            'description' => 'Pusat aneka donat & sweet cakes',
        ]);

        $sweetProduct = Product::create([
            'merchant_id' => $merchantSweet->id,
            'category_id' => $catSweet->id,
            'name' => 'Donat Cokelat Lumer',
            'estimated_price' => 15000,
            'hpp_price' => 12000,
            'is_available' => true,
        ]);

        // Filter by Sweets category with auth:customer
        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.food.index', ['category_id' => $catSweet->id]));

        $response->assertStatus(200);
        $response->assertSee('Filter Aktif: Sweets');
        $response->assertSee('Donat Cokelat Lumer');
        $response->assertSee('Toko Roti Manis Legendaris');
        $response->assertSee('Hapus Filter');
    }

    /**
     * Test Point 5: Multi-Order Step-by-Step Progress & Photo Proof Updates
     */
    public function test_jastiper_can_complete_multi_stops_one_by_one_with_proof_photos()
    {
        $customer = Customer::create([
            'name' => 'Multi Order Customer',
            'phone_number' => '081277778888',
            'password' => bcrypt('password'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $jastiper = Jastiper::create([
            'name' => 'Mitra Multi Stop',
            'phone_number' => '081266665555',
            'password' => bcrypt('password'),
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'radius_km' => 10.0,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $multiStops = [
            [
                'stop_number' => 1,
                'name' => 'Toko Pertama: HokBen MT Haryono',
                'address' => 'Jl. MT Haryono No 20',
                'status' => 'pending',
                'photo' => null,
            ],
            [
                'stop_number' => 2,
                'name' => 'Toko Kedua: Chatime Suhat',
                'address' => 'Jl. Soekarno Hatta No 45',
                'status' => 'pending',
                'photo' => null,
            ]
        ];

        $order = Order::create([
            'customer_id' => $customer->id,
            'jastiper_id' => $jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'description' => 'Multi Titip HokBen & Chatime',
            'category' => 'multi-stop',
            'weight_category' => 'ringan',
            'status' => 'diproses',
            'multi_stops' => $multiStops,
            'estimated_fare' => 25000,
            'agreed_fare' => 25000,
            'destination_address' => 'Perum Griya Shanta Blok E, Malang',
            'recipient_name' => 'Multi Order Customer',
            'recipient_phone' => '081277778888',
        ]);

        // Step 1: Complete Stop 1 with Photo
        $file1 = UploadedFile::fake()->image('stop1_proof.jpg');
        $responseStop1 = $this->actingAs($jastiper, 'jastiper')
            ->post(route('jastiper.orders.stop.complete', [$order->id, 1]), [
                'photo' => $file1,
                'notes' => 'HokBen selesai dibeli, struk terlampir.',
            ]);

        $responseStop1->assertRedirect();
        $responseStop1->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('diproses', $order->status); // Status remains diproses because Stop 2 is pending
        $this->assertEquals('completed', $order->multi_stops[0]['status']);
        $this->assertNotNull($order->multi_stops[0]['photo']);
        $this->assertEquals('pending', $order->multi_stops[1]['status']);

        // Check Customer Details page shows Stop 1 completed and Stop 2 active
        $custView = $this->actingAs($customer, 'customer')
            ->get(route('customer.orders.show', $order->id));
        $custView->assertStatus(200);
        $custView->assertSee('Progres Belanja Multi-Toko');
        $custView->assertSee('1 dari 2 Selesai');
        $custView->assertSee('Toko Pertama: HokBen MT Haryono');
        $custView->assertSee('Toko Kedua: Chatime Suhat');
        $custView->assertSee('Bukti Foto Barang Stop #1');

        // Step 2: Complete Stop 2 with Photo
        $file2 = UploadedFile::fake()->image('stop2_proof.jpg');
        $responseStop2 = $this->actingAs($jastiper, 'jastiper')
            ->post(route('jastiper.orders.stop.complete', [$order->id, 2]), [
                'photo' => $file2,
                'notes' => 'Chatime selesai diambil.',
            ]);

        $responseStop2->assertRedirect();
        $order->refresh();

        // Now all stops are completed! Order status transitions to barang_diambil
        $this->assertEquals('barang_diambil', $order->status);
        $this->assertEquals('completed', $order->multi_stops[1]['status']);
        $this->assertNotNull($order->multi_stops[1]['photo']);
        $this->assertNotNull($order->pickup_photo);

        // Check Customer Details page shows 2 dari 2 Selesai
        $custView2 = $this->actingAs($customer, 'customer')
            ->get(route('customer.orders.show', $order->id));
        $custView2->assertStatus(200);
        $custView2->assertSee('2 dari 2 Selesai');
        $custView2->assertSee('Bukti Foto Barang Stop #2');
    }
}
