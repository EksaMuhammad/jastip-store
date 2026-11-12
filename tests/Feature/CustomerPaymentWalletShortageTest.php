<?php

namespace Tests\Feature;

use App\Livewire\Customer\PaymentPage;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Topup;
use App\Models\Wallet;
use App\Models\Wilayah;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPaymentWalletShortageTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;
    protected Order $order;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->wilayah = Wilayah::first() ?: Wilayah::create([
            'name' => 'Malang',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'default_radius_km' => 10,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Eksa Test',
            'phone_number' => '082139678388',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);

        // Buat wallet dengan saldo 500.000
        $this->customer->wallet()->create([
            'owner_role' => 'customer',
            'balance' => 500000,
        ]);

        $this->jastiper = Jastiper::create([
            'name' => 'Mitra Jastiper',
            'phone_number' => '082222222221',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 10,
            'is_available' => true,
        ]);

        // Order dengan nilai total / tagihan 600.000
        $this->order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'sedang',
            'description' => 'Belanja Oleh-Oleh Malang',
            'origin_address' => 'Toko Oleh-Oleh Malang',
            'destination_address' => 'Jl. Ijen Malang',
            'recipient_name' => 'Eksa Test',
            'recipient_phone' => '082139678388',
            'estimated_fare' => 50000,
            'agreed_fare' => 600000,
            'downpayment_amount' => 600000,
            'status' => 'menunggu_pembayaran',
        ]);

        $this->payment = app(PaymentService::class)->initiate($this->order);
    }

    /**
     * Uji 1: Halaman pembayaran menampilkan saldo saat ini dan peringatan kekurangan saldo.
     */
    public function test_payment_page_shows_balance_shortage()
    {
        Livewire::actingAs($this->customer, 'customer')
            ->test(PaymentPage::class, ['orderId' => $this->order->id])
            ->assertSet('currentBalance', 500000.0)
            ->assertSet('requiredAmount', 600000.0)
            ->assertSet('shortageAmount', 100000.0)
            ->assertSee('Kurang Rp100.000')
            ->assertSee('Top-Up Rp100.000');
    }

    /**
     * Uji 2: Klik bayar dengan saldo saat saldo kurang memunculkan modal peringatan & anjuran top-up.
     */
    public function test_paying_with_insufficient_wallet_triggers_topup_modal()
    {
        Livewire::actingAs($this->customer, 'customer')
            ->test(PaymentPage::class, ['orderId' => $this->order->id])
            ->call('payWithWallet')
            ->assertSet('showInsufficientBalanceModal', true)
            ->assertSet('shortageAmount', 100000.0)
            ->assertSee('Saldo E-Wallet Kurang')
            ->assertSee('Top-Up Saldo Rp100.000 Sekarang');
    }

    /**
     * Uji 3: Klik proceedToTopup membuat tiket topup sejumlah kekurangan (100.000) dan redirect ke halaman bayar topup.
     */
    public function test_proceed_to_topup_creates_topup_ticket_and_redirects()
    {
        Livewire::actingAs($this->customer, 'customer')
            ->test(PaymentPage::class, ['orderId' => $this->order->id])
            ->call('proceedToTopup')
            ->assertSessionHas('return_order_payment_id', $this->order->id);

        $topup = Topup::latest('id')->first();
        $this->assertNotNull($topup);
        $this->assertEquals(100000, (float) $topup->amount);
        $this->assertEquals('menunggu_pembayaran', $topup->status);
    }

    /**
     * Uji 4: Setelah top-up 100.000 sukses, saldo menjadi 600.000 dan pembayaran dengan wallet berhasil.
     */
    public function test_successful_topup_enables_wallet_payment()
    {
        // Top-up selesai
        $wallet = $this->customer->wallet;
        app(WalletService::class)->credit($wallet, 100000, 'topup', null, 'Topup kekurangan order');
        $this->assertEquals(600000, (float) $wallet->fresh()->balance);

        Livewire::actingAs($this->customer, 'customer')
            ->test(PaymentPage::class, ['orderId' => $this->order->id])
            ->assertSee('Saldo Cukup')
            ->call('payWithWallet')
            ->assertDispatched('order-diproses');

        $this->assertEquals(0, (float) $wallet->fresh()->balance);
        $this->assertEquals('diproses', $this->order->fresh()->status);
    }

    /**
     * Uji 5: Pilihan Midtrans ada di daftar bawah; opsi suksesinnya hanya muncul setelah dipilih.
     */
    public function test_midtrans_method_selection_and_auto_success_option()
    {
        $test = Livewire::actingAs($this->customer, 'customer')
            ->test(PaymentPage::class, ['orderId' => $this->order->id]);

        // Awalnya: Pilihan Midtrans ada di daftar, tapi opsi aksi/suksesinnya belum muncul
        $test->assertSee('Bayar via Midtrans Snap')
            ->assertDontSee('Buka Pop-up Midtrans');

        // Saat dipilih: Opsi aksi dan "⚡ Otomatis Lunas" (opsi suksesinnya) baru muncul
        $test->call('selectMethod', 'midtrans')
            ->assertSet('selectedMethod', 'midtrans')
            ->assertSee('Buka Pop-up Midtrans')
            ->assertSee('⚡ Otomatis Lunas')
            ->assertSee('🔄 Cek Status');

        // Klik "⚡ Otomatis Lunas" sukses menyelesaikan pembayaran
        $test->call('markPaymentSuccess', 'auto_instant')
            ->assertDispatched('order-diproses');

        $this->assertEquals('diproses', $this->order->fresh()->status);
    }
}

