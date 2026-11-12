<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Wilayah;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();

        $this->wilayah = Wilayah::first() ?: Wilayah::create([
            'name' => 'Malang',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'default_radius_km' => 10,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Budi Customer',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->jastiper = Jastiper::create([
            'name' => 'Joko Jastiper',
            'phone_number' => '082222222221',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 10,
            'is_available' => true,
        ]);

        $this->order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip beli bakso bakar Malang',
            'origin_address' => 'Jl. Soekarno Hatta',
            'destination_address' => 'Jl. Veteran',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567891',
            'estimated_fare' => 15000,
            'agreed_fare' => 15000,
            'status' => 'diproses',
        ]);
    }

    /**
     * Uji 1: Tamu (Guest) tidak bisa mengakses halaman audit activity logs admin.
     */
    public function test_guest_cannot_access_activity_logs()
    {
        $response = $this->get(route('admin.activities.index'));
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * Uji 2: Admin dapat mengakses halaman monitoring log aktivitas.
     */
    public function test_admin_can_access_activity_logs_page()
    {
        ActivityLogger::log([
            'user_type' => 'customer',
            'user_id' => $this->customer->id,
            'user_name' => $this->customer->name,
            'order_id' => $this->order->id,
            'category' => 'pesanan',
            'action' => 'order_created',
            'title' => "Pesanan #{$this->order->id} Dibuat",
            'description' => "Customer membuat pesanan baru",
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.activities.index'));

        $response->assertStatus(200);
        $response->assertSee('Monitoring');
        $response->assertSee('Log Aktivitas Pengguna');
        $response->assertSee("Pesanan #{$this->order->id} Dibuat");
        $response->assertSee('Budi Customer');
    }

    /**
     * Uji 3: Filter berdasarkan kategori dan peran berfungsi akurat.
     */
    public function test_activity_logs_can_be_filtered()
    {
        // Log Pesanan Customer
        ActivityLogger::log([
            'user_type' => 'customer',
            'user_id' => $this->customer->id,
            'user_name' => $this->customer->name,
            'order_id' => $this->order->id,
            'category' => 'pesanan',
            'action' => 'order_created',
            'title' => "Pesanan Baru Dibuat",
            'description' => "Order dibuat oleh customer",
        ]);

        // Log Harga Toko Jastiper
        ActivityLogger::log([
            'user_type' => 'jastiper',
            'user_id' => $this->jastiper->id,
            'user_name' => $this->jastiper->name,
            'order_id' => $this->order->id,
            'category' => 'harga_toko',
            'action' => 'price_adjustment_proposed',
            'title' => "Pengajuan Penyesuaian Harga Toko",
            'description' => "Jastiper lapor harga naik",
        ]);

        // 1. Filter Kategori harga_toko
        $responseCategory = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.activities.index', ['category' => 'harga_toko']));

        $responseCategory->assertStatus(200);
        $responseCategory->assertSee('Pengajuan Penyesuaian Harga Toko');
        $responseCategory->assertDontSee('Order dibuat oleh customer');

        // 2. Filter Peran customer
        $responseRole = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.activities.index', ['user_type' => 'customer']));

        $responseRole->assertStatus(200);
        $responseRole->assertSee('Pesanan Baru Dibuat');
        $responseRole->assertDontSee('Jastiper lapor harga naik');
    }

    /**
     * Uji 4: Endpoint timeline kronologis pesanan mengembalikan riwayat urut per order.
     */
    public function test_order_timeline_endpoint_returns_chronological_events()
    {
        // Event 1: Dibuat
        ActivityLog::create([
            'user_type' => 'customer',
            'user_id' => $this->customer->id,
            'user_name' => $this->customer->name,
            'order_id' => $this->order->id,
            'category' => 'pesanan',
            'action' => 'order_created',
            'title' => "Pesanan Dibuat",
            'description' => "Langkah 1: Customer membuat pesanan",
            'created_at' => now()->subMinutes(10),
        ]);

        // Event 2: Kurir Mengambil Barang
        ActivityLog::create([
            'user_type' => 'jastiper',
            'user_id' => $this->jastiper->id,
            'user_name' => $this->jastiper->name,
            'order_id' => $this->order->id,
            'category' => 'pengantaran',
            'action' => 'status_barang_diambil',
            'title' => "Jastiper: Barang Diambil",
            'description' => "Langkah 2: Barang diambil di warung bakso",
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.activities.order-timeline', $this->order->id));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order' => [
                    'id' => $this->order->id,
                    'customer_name' => $this->customer->name,
                    'jastiper_name' => $this->jastiper->name,
                ],
            ]);

        $data = $response->json();
        $this->assertCount(2, $data['timeline']);
        $this->assertEquals('Pesanan Dibuat', $data['timeline'][0]['title']);
        $this->assertEquals('Jastiper: Barang Diambil', $data['timeline'][1]['title']);
    }

    /**
     * Uji 5: Backfill data historis otomatis mengisi log dari rekaman database sebelumnya.
     */
    public function test_backfill_creates_logs_from_existing_database_records()
    {
        ActivityLog::truncate();
        $this->assertEquals(0, ActivityLog::count());

        $backfilled = ActivityLogger::backfillExistingHistory();

        $this->assertGreaterThan(0, $backfilled);
        $this->assertDatabaseHas('activity_logs', [
            'order_id' => $this->order->id,
            'action' => 'order_created',
        ]);
    }

    /**
     * Uji 6: Top-up saldo dicatat ke kategori keuangan dan dapat difilter di log aktivitas.
     */
    public function test_topup_activity_is_logged_and_filterable()
    {
        $wallet = $this->customer->wallet ?: \App\Models\Wallet::create([
            'owner_role' => 'customer',
            'owner_id' => $this->customer->id,
            'balance' => 0,
        ]);

        $topup = \App\Models\Topup::create([
            'wallet_id' => $wallet->id,
            'amount' => 150000,
            'status' => 'berhasil',
            'method' => 'transfer',
            'channel' => 'qris',
            'gateway_reference' => 'TOPUP-TEST-1234',
        ]);

        ActivityLogger::logTopup(
            $topup,
            'topup_berhasil',
            'Top-Up Saldo Berhasil (+Rp 150.000)',
            "Customer {$this->customer->name} berhasil melakukan top-up",
            $this->customer
        );

        $this->assertDatabaseHas('activity_logs', [
            'category' => 'keuangan',
            'action' => 'topup_berhasil',
            'title' => 'Top-Up Saldo Berhasil (+Rp 150.000)',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.activities.index', ['category' => 'keuangan']));

        $response->assertStatus(200);
        $response->assertSee('Top-Up Saldo Berhasil (+Rp 150.000)');
        $response->assertSee('TOPUP-TEST-1234');
    }
}
