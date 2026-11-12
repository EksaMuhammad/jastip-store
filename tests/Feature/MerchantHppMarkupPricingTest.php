<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\Wilayah;
use App\Services\KomisiService;
use App\Services\TariffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantHppMarkupPricingTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Merchant $merchant;
    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
        $this->wilayah = Wilayah::first();

        $this->merchant = Merchant::create([
            'name' => 'Resto Padang Enak',
            'email' => 'padang@merchant.test',
            'password' => bcrypt('password123'),
            'phone_number' => '08129998881',
            'type' => 'food',
            'address' => 'Jl. Soekarno Hatta No. 1',
            'latitude' => -7.9421,
            'longitude' => 112.6178,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Budi Customer',
        ]);

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

        // Siapkan wallet
        Wallet::create([
            'customer_id' => $this->customer->id,
            'owner_type' => 'customer',
            'owner_id' => $this->customer->id,
            'owner_role' => 'customer',
            'balance' => 500000,
        ]);
        Wallet::create([
            'jastiper_id' => $this->jastiper->id,
            'owner_type' => 'jastiper',
            'owner_id' => $this->jastiper->id,
            'owner_role' => 'jastiper',
            'balance' => 100000,
        ]);
    }

    public function test_admin_can_set_merchant_markup_percent_in_tariff_settings()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.settings.tariffs.update'), array_merge(
            TariffService::DEFAULTS,
            [
                'merchant_markup_percent' => 15,
            ]
        ));

        $response->assertRedirect(route('admin.settings.tariffs'))
            ->assertSessionHas('success');

        $this->assertEquals('15', Setting::get('merchant_markup_percent'));
        $this->assertEquals(15.0, app(TariffService::class)->getMerchantMarkupPercent());
    }

    public function test_merchant_can_create_product_with_hpp_and_auto_markup()
    {
        Setting::set('merchant_markup_percent', 10);
        $this->actingAs($this->merchant, 'merchant');

        $response = $this->post(route('merchant.products.store'), [
            'pricing_mode' => 'hpp',
            'hpp_price' => '20.000',
            'discount_hpp_price' => '15.000',
            'name' => 'Paket Ayam Krispi HPP',
            'category' => 'Makanan',
            'description' => 'Ayam krispi lezat',
            'is_available' => 1,
        ]);

        $response->assertRedirect(route('merchant.products.index'))
            ->assertSessionHas('success');

        $product = Product::where('name', 'Paket Ayam Krispi HPP')->first();
        $this->assertNotNull($product);
        $this->assertEquals(20000, $product->hpp_price);
        $this->assertEquals(15000, $product->discount_hpp_price);
        $this->assertTrue($product->is_auto_markup);

        // 20.000 + 10% = 22.000
        $this->assertEquals(22000, $product->estimated_price);
        // 15.000 + 10% = 16.500
        $this->assertEquals(16500, $product->discount_price);
        $this->assertEquals(15000, $product->effective_hpp_price);
        $this->assertEquals(16500, $product->effective_price);
    }

    public function test_merchant_can_create_product_with_manual_pricing_mode()
    {
        $this->actingAs($this->merchant, 'merchant');

        $response = $this->post(route('merchant.products.store'), [
            'pricing_mode' => 'manual',
            'estimated_price' => '25.000',
            'name' => 'Kopi Manual Price',
            'category' => 'Minuman',
            'description' => 'Kopi susu gula aren',
            'is_available' => 1,
        ]);

        $response->assertRedirect(route('merchant.products.index'));

        $product = Product::where('name', 'Kopi Manual Price')->first();
        $this->assertNotNull($product);
        $this->assertFalse($product->is_auto_markup);
        $this->assertEquals(25000, $product->estimated_price);
        $this->assertEquals(25000, $product->hpp_price); // fallback hpp
    }

    public function test_catalog_checkout_calculates_hpp_and_merchant_markup_profit()
    {
        Setting::set('merchant_markup_percent', 10);

        $product = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Menu Uji Markup',
            'category' => 'Makanan',
            'hpp_price' => 20000,
            'estimated_price' => 22000,
            'is_auto_markup' => true,
            'is_available' => true,
        ]);

        $this->actingAs($this->customer, 'customer');

        // Simulasi cart session
        session()->put('cart', [
            $product->id => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => 22000,
                'hpp_price' => 20000,
                'markup_per_item' => 2000,
                'quantity' => 2,
                'notes' => 'Pedas sedang',
                'merchant_id' => $this->merchant->id,
            ]
        ]);

        $cart = \App\Models\Cart::firstOrCreate(['customer_id' => $this->customer->id]);
        \App\Models\CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->post(route('customer.cart.checkout'), [
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'destination_address' => 'Jl. Merdeka No. 10',
            'destination_lat' => -7.9839,
            'destination_lng' => 112.6214,
            'delivery_fee' => 15000,
        ]);

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        // 2 items * Rp 2.000 = Rp 4.000 markup platform
        $this->assertEquals(4000, $order->merchant_markup_profit);

        $item = $order->items->first();
        $this->assertNotNull($item);
        $this->assertEquals(20000, $item->hpp_price);
        $this->assertEquals(4000, $item->markup_amount);
        $this->assertEquals(22000, $item->estimated_price);
    }

    public function test_order_completion_distributes_hpp_to_jastiper_and_keeps_markup_in_platform()
    {
        Setting::set('platform_commission_percent', 10);
        Setting::set('merchant_markup_percent', 10);

        // Order dengan ongkir Rp 20.000 dan belanja 2x Rp 22.000 (total DP Rp 44.000, HPP Rp 40.000, markup Rp 4.000)
        $order = Order::create([
            'order_number' => 'ORD-TEST-HPP-1',
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'toko-kirim',
            'weight_category' => 'ringan',
            'status' => 'diproses',
            'payment_method' => 'wallet',
            'estimated_fare' => 20000,
            'agreed_fare' => 20000,
            'downpayment_amount' => 64000,
            'merchant_markup_profit' => 4000,
            'destination_address' => 'Jl. Pahlawan No. 5',
            'delivery_address' => 'Jl. Pahlawan No. 5',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'name' => 'Item Test Markup',
            'quantity' => 2,
            'hpp_price' => 20000,
            'markup_amount' => 2000,
            'estimated_price' => 22000,
            'actual_price' => 22000,
        ]);

        $jastiperInitialBalance = $this->jastiper->wallet->fresh()->balance;

        // Potong komisi & pencairan selesai
        $komisiService = app(KomisiService::class);
        $komisi = $komisiService->potongKomisi($order);

        $this->assertNotNull($komisi);
        // Komisi ongkir 10% dari Rp 20.000 = Rp 2.000
        $this->assertEquals(2000, $komisi->commission_amount);
        // Markup barang tercatat Rp 4.000
        $this->assertEquals(4000, $komisi->item_markup_amount);

        // Jastiper mendapat:
        // 1. Bersih ongkir: Rp 20.000 - Rp 2.000 = Rp 18.000
        // 2. Reimbursement belanja modal toko (HPP): Rp 44.000 - Rp 4.000 markup = Rp 40.000
        // Total masuk ke wallet jastiper = Rp 58.000
        $jastiperFinalBalance = $this->jastiper->wallet->fresh()->balance;
        $this->assertEquals($jastiperInitialBalance + 58000, $jastiperFinalBalance);
    }
}
