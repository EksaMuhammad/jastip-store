<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMutationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Customer $customer;
    protected Jastiper $jastiper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
        $this->customer = Customer::first() ?: Customer::create([
            'name' => 'Budi Customer',
            'phone_number' => '081234567890',
            'password' => bcrypt('password123'),
        ]);

        $wilayah = Wilayah::first();
        $this->jastiper = Jastiper::first() ?: Jastiper::create([
            'name' => 'Joko Jastip',
            'phone_number' => '089876543210',
            'password' => bcrypt('password123'),
            'wilayah_id' => $wilayah ? $wilayah->id : 1,
            'radius_km' => 10,
            'verification_status' => 'approved',
        ]);
    }

    public function test_guest_cannot_access_mutations_page()
    {
        $response = $this->get('/admin/mutations');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_access_mutations_page()
    {
        $response = $this->actingAs($this->admin, 'admin')->get('/admin/mutations');
        $response->assertStatus(200);
        $response->assertSee('Master Data Mutasi Keseluruhan');
        $response->assertSee('Pilih Rentang Hari Mutasi');
    }

    public function test_mutations_page_displays_and_filters_by_days()
    {
        $customerWallet = $this->customer->wallet()->firstOrCreate([], ['balance' => 100000]);

        // Transaction 2 days ago
        $txRecent = WalletTransaction::create([
            'wallet_id' => $customerWallet->id,
            'type' => 'kredit',
            'amount' => 50000,
            'source' => 'topup',
            'description' => 'Topup Saldo Baru Dua Hari Lalu',
        ]);
        $txRecent->forceFill(['created_at' => now()->subDays(2)])->save();

        // Transaction 20 days ago
        $txOlder = WalletTransaction::create([
            'wallet_id' => $customerWallet->id,
            'type' => 'kredit',
            'amount' => 75000,
            'source' => 'topup',
            'description' => 'Topup Saldo Lama Dua Puluh Hari Lalu',
        ]);
        $txOlder->forceFill(['created_at' => now()->subDays(20)])->save();

        // When filtered by 7 days: should see recent, but NOT older
        $response7Days = $this->actingAs($this->admin, 'admin')->get('/admin/mutations?days=7');
        $response7Days->assertStatus(200);
        $response7Days->assertSee('Topup Saldo Baru Dua Hari Lalu');
        $response7Days->assertDontSee('Topup Saldo Lama Dua Puluh Hari Lalu');

        // When filtered by 30 days: should see BOTH
        $response30Days = $this->actingAs($this->admin, 'admin')->get('/admin/mutations?days=30');
        $response30Days->assertStatus(200);
        $response30Days->assertSee('Topup Saldo Baru Dua Hari Lalu');
        $response30Days->assertSee('Topup Saldo Lama Dua Puluh Hari Lalu');
    }

    public function test_mutations_filter_by_type_and_search()
    {
        $customerWallet = $this->customer->wallet()->firstOrCreate([], ['balance' => 200000]);

        WalletTransaction::create([
            'wallet_id' => $customerWallet->id,
            'type' => 'kredit',
            'amount' => 100000,
            'source' => 'topup',
            'description' => 'Topup via QRIS Sukses',
            'created_at' => now()->subHour(),
        ]);

        WalletTransaction::create([
            'wallet_id' => $customerWallet->id,
            'type' => 'debit',
            'amount' => 45000,
            'source' => 'pembayaran_order',
            'description' => 'Pembayaran Order Belanja Mart',
            'created_at' => now()->subHours(2),
        ]);

        // Test filter kredit
        $responseKredit = $this->actingAs($this->admin, 'admin')->get('/admin/mutations?type=kredit');
        $responseKredit->assertStatus(200);
        $responseKredit->assertSee('Topup via QRIS Sukses');
        $responseKredit->assertDontSee('Pembayaran Order Belanja Mart');

        // Test filter debit
        $responseDebit = $this->actingAs($this->admin, 'admin')->get('/admin/mutations?type=debit');
        $responseDebit->assertStatus(200);
        $responseDebit->assertSee('Pembayaran Order Belanja Mart');
        $responseDebit->assertDontSee('Topup via QRIS Sukses');

        // Test search
        $responseSearch = $this->actingAs($this->admin, 'admin')->get('/admin/mutations?search=QRIS');
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Topup via QRIS Sukses');
        $responseSearch->assertDontSee('Pembayaran Order Belanja Mart');
    }

    public function test_admin_dashboard_includes_master_mutasi_section()
    {
        $customerWallet = $this->customer->wallet()->firstOrCreate([], ['balance' => 500000]);

        WalletTransaction::create([
            'wallet_id' => $customerWallet->id,
            'type' => 'kredit',
            'amount' => 88000,
            'source' => 'topup',
            'description' => 'Deposit Customer Dashboard Mutasi Test',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get('/admin/mutations?days=7');
        $response->assertStatus(200);
        $response->assertSee('Master Data Mutasi');
        $response->assertSee('Deposit Customer Dashboard Mutasi Test');
        $response->assertSee('Rp 88.000');
    }

    public function test_mutations_filter_by_platform_commission_source()
    {
        $jastiperWallet = $this->jastiper->wallet()->firstOrCreate([], ['balance' => 0]);

        // Simulasikan order dan komisi yang dipotong
        $order = \App\Models\Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->jastiper->wilayah_id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip Kopi Kenangan',
            'origin_address' => 'Mall Olympic Garden',
            'destination_address' => 'Jl. Ijen',
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 20000.00,
            'agreed_fare' => 20000.00,
            'status' => 'diproses',
        ]);

        app(\App\Services\KomisiService::class)->potongKomisi($order);

        // 1. Pastikan kedua mutasi (gross order_completion dan potongan komisi) tercatat di wallet_transactions
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $jastiperWallet->id,
            'type' => 'kredit',
            'amount' => 20000.00,
            'source' => 'order_completion',
            'reference_order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $jastiperWallet->id,
            'type' => 'debit',
            'amount' => 2000.00, // 10% dari 20.000
            'source' => 'komisi',
            'reference_order_id' => $order->id,
        ]);

        // 2. Filter Mutasi khusus Potongan Komisi Platform
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/mutations?source=komisi&days=7');

        $response->assertStatus(200);
        $response->assertSee('Potongan Komisi Platform');
        $response->assertSee('2.000');
        $response->assertSee('Potongan komisi platform (10%) order #' . $order->id);
    }
}

