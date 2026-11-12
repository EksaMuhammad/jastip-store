<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerService;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\Wilayah;
use App\Services\InsuranceService;
use App\Services\TariffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceProtectionFundTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Customer $customer;
    protected Jastiper $jastiper;
    protected CustomerService $cs;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
        $this->wilayah = Wilayah::first();

        $this->customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Budi Customer',
        ]);
        $this->customer->wallet()->create(['balance' => 100000, 'owner_role' => 'customer']);

        $this->jastiper = Jastiper::create([
            'phone_number' => '089876543210',
            'name' => 'Doni Jastiper',
            'is_available' => true,
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 10,
            'current_lat' => -7.9839,
            'current_lng' => 112.6214,
        ]);
        $this->jastiper->wallet()->create(['balance' => 50000, 'owner_role' => 'jastiper']);

        $this->cs = CustomerService::create([
            'name' => 'Sari CS',
            'email' => 'cs.sari@jastipkuy.com',
            'password_hash' => bcrypt('password123'),
            'phone_number' => '081122334455',
            'is_active' => true,
            'is_online' => true,
            'work_status' => 'tersedia',
        ]);
    }

    protected function createOrder(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Pesanan Test Berasuransi',
            'origin_address' => 'Jl. Ijen No. 10, Malang',
            'destination_address' => 'Jl. Veteran No. 5, Malang',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 15000,
            'agreed_fare' => 500000,
            'downpayment_amount' => 265000,
            'cash_amount' => 0,
            'status' => 'diproses',
            'has_insurance' => true,
            'insurance_fee' => 2000,
        ], $attributes));
    }

    public function test_admin_can_update_insurance_tariff_settings()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.settings.tariffs.update'), [
            'rate_per_km' => 5000,
            'min_fare' => 10000,
            'extra_stop_fare' => 5000,
            'base_included_km' => 2.0,
            'multi_store_km_rate' => 3000,
            'multi_store_extra_store_fare' => 5000,

            'category_beli_antar' => 5000,
            'category_ambil_antar' => 3000,
            'category_toko_kirim' => 2000,
            'category_dokumen' => 1000,
            'category_multi_stop' => 10000,
            'category_kirim_pihak_ketiga' => 5000,

            'weight_ringan' => 0,
            'weight_sedang' => 5000,
            'weight_berat' => 10000,

            'platform_commission_percent' => 10,
            'payment_deadline_minutes' => 30,
            'min_topup_amount' => 10000,

            'insurance_enabled' => 1,
            'insurance_min_order_threshold' => 250000,
            'insurance_fee' => 3000,
        ]);

        $response->assertRedirect(route('admin.settings.tariffs'));

        $tariffService = app(TariffService::class);
        $this->assertEquals(1, $tariffService->get('insurance_enabled'));
        $this->assertEquals(250000, $tariffService->get('insurance_min_order_threshold'));
        $this->assertEquals(3000, $tariffService->get('insurance_fee'));
    }

    public function test_insurance_premium_is_recorded_when_order_has_insurance()
    {
        $order = $this->createOrder([
            'description' => 'Order Makanan Seafood Mewah 500k',
        ]);

        $insuranceService = app(InsuranceService::class);
        $tx = $insuranceService->recordPremium($order, 2000);

        $this->assertDatabaseHas('insurance_transactions', [
            'order_id' => $order->id,
            'type' => 'premi_masuk',
            'amount' => 2000,
        ]);

        $this->assertTrue($order->fresh()->has_insurance);
        $this->assertEquals(2000, $order->fresh()->insurance_fee);
    }

    public function test_dispute_resolution_with_insurance_claims_bailout_and_debits_jastiper_into_negative()
    {
        $order = $this->createOrder([
            'description' => 'Order 500k Jatuh di Jalan',
            'agreed_fare' => 500000,
            'status' => 'bermasalah',
        ]);

        // Simpan pembayaran lunas 500.000
        Payment::create([
            'order_id' => $order->id,
            'method' => 'wallet',
            'channel' => 'saldo_dompet',
            'amount' => 500000,
            'status' => 'lunas',
            'verified_at' => now(),
        ]);

        // Catat premi masuk
        $insuranceService = app(InsuranceService::class);
        $insuranceService->recordPremium($order, 2000);

        // Buat tiket komplain pengaduan
        $report = Report::create([
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'reporter_role' => 'customer',
            'reporter_id' => $this->customer->id,
            'category' => 'barang_rusak',
            'description' => 'Makanan 500rb jatuh dan hancur di jalan karena jastiper ngebut.',
            'status' => 'menunggu',
            'urgency' => 'tinggi',
        ]);

        // Jastiper saldo awal: 50.000
        $this->assertEquals(50000, $this->jastiper->wallet->balance);

        // CS mengeksekusi putusan resolusi refund 100% via klaim dana asuransi
        $this->actingAs($this->cs, 'cs');

        $response = $this->post(route('cs.reports.resolve', $report->id), [
            'action' => 'refund_full',
            'use_insurance' => 1,
            'admin_decision' => 'Pengaduan disetujui. Makanan hancur total di jalan akibat kelalaian jastiper. Dana diganti 100% dari kas asuransi platform.',
        ]);

        $response->assertRedirect(route('cs.reports.show', $report->id));

        // 1. Customer dompet bertambah 500.000 (100.000 + 500.000 = 600.000)
        $this->assertEquals(600000, $this->customer->wallet->fresh()->balance);

        // 2. Jastiper saldo didebit 500.000 menjadi MINUS (50.000 - 500.000 = -450.000)
        $this->assertEquals(-450000, $this->jastiper->wallet->fresh()->balance);

        // 3. Catatan mutasi dompet jastiper tersimpan dengan type debit dan allow negative
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->jastiper->wallet->id,
            'type' => 'debit',
            'source' => 'beban_ganti_rugi',
            'amount' => 500000,
        ]);

        // 4. Catatan transaksi kas asuransi tersimpan (klaim_keluar)
        $this->assertDatabaseHas('insurance_transactions', [
            'order_id' => $order->id,
            'report_id' => $report->id,
            'type' => 'klaim_keluar',
            'amount' => 500000,
        ]);

        // 5. Data report & order ditandai klaim asuransi
        $this->assertTrue((bool) $report->fresh()->is_insurance_claim);
        $this->assertEquals(500000, $report->fresh()->insurance_payout);
        $this->assertEquals(500000, $report->fresh()->jastiper_charged_amount);

        // 6. Ringkasan Pool Kas Asuransi menunjukkan defisit / platform sedang nomboki
        $pool = $insuranceService->getPoolSummary();
        $this->assertEquals(2000, $pool['total_premi_all_time']);
        $this->assertEquals(500000, $pool['total_klaim_all_time']);
        $this->assertEquals(-498000, $pool['net_pool_all_time']);
        $this->assertTrue($pool['is_deficit']);
        $this->assertEquals(450000, $pool['total_jastiper_debt']); // Piutang jastiper minus
        $this->assertEquals(1, $pool['debtor_count']);
    }

    public function test_admin_mutations_and_dashboard_render_insurance_pool_correctly()
    {
        $order = $this->createOrder([
            'description' => 'Order Terproteksi Asuransi',
            'status' => 'selesai',
        ]);

        app(InsuranceService::class)->recordPremium($order, 2000);

        $this->actingAs($this->admin, 'admin');

        // Buka Master Mutasi
        $mutationResponse = $this->get(route('admin.mutations.index'));
        $mutationResponse->assertOk();
        $mutationResponse->assertSee('Pool Dana Asuransi Barang');
        $mutationResponse->assertSee('SURPLUS KAS');
        $mutationResponse->assertSee('Total Premi Masuk');

        // Buka Admin Dashboard
        $dashboardResponse = $this->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Pool Dana Asuransi Barang (Talangan Platform)');
    }

    public function test_cart_checkout_with_insurance_checkbox_creates_order_and_records_premium()
    {
        $category = \App\Models\Category::first() ?? \App\Models\Category::create(['name' => 'Minimarket', 'type' => 'mart']);
        $merchant = \App\Models\Merchant::create([
            'name' => 'Toko Elektronik & Kuliner Mewah',
            'address' => 'Jl. Ijen No. 12, Malang',
            'type' => 'food',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
        ]);

        $product = \App\Models\Product::create([
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Hampers Premium',
            'estimated_price' => 250000,
            'is_available' => true,
        ]);

        $cart = \App\Models\Cart::firstOrCreate(['customer_id' => $this->customer->id]);
        $cart->cartItems()->create([
            'product_id' => $product->id,
            'item_type' => 'catalog',
            'category' => 'beli-antar',
            'name' => $product->name,
            'quantity' => 1,
            'estimated_price' => 250000,
        ]);

        $this->actingAs($this->customer, 'customer');

        // 1. Visit cart page - verify insurance badge & checkbox is rendered because subtotal 250k >= threshold 200k
        $cartViewResponse = $this->get(route('customer.cart.index'));
        $cartViewResponse->assertOk();
        $cartViewResponse->assertSee('Proteksi Asuransi Barang');
        $cartViewResponse->assertSee('+Rp 2.000');

        // 2. Checkout with has_insurance = 1
        $checkoutResponse = $this->post(route('customer.cart.checkout'), [
            'destination_address' => 'Jl. Sigura-gura No. 1, Malang',
            'destination_lat' => -7.9550,
            'destination_lng' => 112.6100,
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'has_insurance' => 1,
        ]);

        $checkoutResponse->assertRedirect(route('customer.dashboard'));

        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertTrue((bool) $order->has_insurance);
        $this->assertEquals(2000, $order->insurance_fee);

        // Verify premium transaction is recorded
        $this->assertDatabaseHas('insurance_transactions', [
            'order_id' => $order->id,
            'type' => 'premi_masuk',
            'amount' => 2000,
        ]);
    }
}
