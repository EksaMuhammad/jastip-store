<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Wilayah;
use App\Services\DistanceService;
use App\Services\TariffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTariffSettingTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Customer $customer;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
        $this->wilayah = Wilayah::first();

        $this->customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Siti Aminah',
        ]);
    }

    public function test_admin_tariffs_page_requires_admin_auth()
    {
        $this->get(route('admin.settings.tariffs'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_tariffs_settings_page()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.tariffs'))
            ->assertOk()
            ->assertSee('Master Pengaturan Tarif', false)
            ->assertSee('Simulasi Tarif Live')
            ->assertSee('rate_per_km')
            ->assertSee('min_fare');
    }

    public function test_admin_can_update_tariffs_and_it_reflects_in_database()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.settings.tariffs.update'), [
            'rate_per_km' => 8000,
            'min_fare' => 15000,
            'extra_stop_fare' => 7500,
            'base_included_km' => 3.0,
            'multi_store_km_rate' => 4000,
            'multi_store_extra_store_fare' => 6000,

            'category_beli_antar' => 10000,
            'category_ambil_antar' => 4000,
            'category_toko_kirim' => 3000,
            'category_dokumen' => 1000,
            'category_multi_stop' => 20000,
            'category_kirim_pihak_ketiga' => 8000,

            'weight_ringan' => 0,
            'weight_sedang' => 12000,
            'weight_berat' => 30000,

            'platform_commission_percent' => 15,
            'payment_deadline_minutes' => 45,
            'min_topup_amount' => 20000,
        ]);

        $response->assertRedirect(route('admin.settings.tariffs'))
            ->assertSessionHas('success');

        $this->assertEquals('8000', Setting::get('rate_per_km'));
        $this->assertEquals('15000', Setting::get('min_fare'));
        $this->assertEquals('15', Setting::get('platform_commission_percent'));
        $this->assertEquals('20000', Setting::get('min_topup_amount'));
    }

    public function test_customer_fare_calculation_uses_updated_dynamic_tariffs()
    {
        $tariffService = app(TariffService::class);

        // Sebelum diubah: rate 5000, beli_antar 5000, ringan 0 -> 4 KM * 5000 + 5000 = 25000
        $initialFare = $tariffService->calculateFare(4.0, 'beli-antar', 'ringan');
        $this->assertEquals(25000, $initialFare);

        // Admin mengubah tarif: rate 8000, beli_antar 7000
        $this->actingAs($this->admin, 'admin')->post(route('admin.settings.tariffs.update'), array_merge(
            TariffService::DEFAULTS,
            [
                'rate_per_km' => 8000,
                'category_beli_antar' => 7000,
            ]
        ));

        // Setelah diubah: 4 KM * 8000 + 7000 = 39000
        $updatedFare = $tariffService->calculateFare(4.0, 'beli-antar', 'ringan');
        $this->assertEquals(39000, $updatedFare);
    }

    public function test_multi_store_fare_uses_updated_dynamic_tariffs()
    {
        $merchants = [
            ['id' => 1, 'name' => 'Toko A', 'lat' => -6.2000, 'lng' => 106.8166],
            ['id' => 2, 'name' => 'Toko B', 'lat' => -6.2200, 'lng' => 106.8300],
        ];

        // Ubah setting tarif multi store
        $this->actingAs($this->admin, 'admin')->post(route('admin.settings.tariffs.update'), array_merge(
            TariffService::DEFAULTS,
            [
                'min_fare' => 20000,
                'multi_store_extra_store_fare' => 8000,
            ]
        ));

        $route = DistanceService::calculateMultiStoreRoute($merchants, -6.2400, 106.8500);

        $this->assertGreaterThanOrEqual(20000, $route['estimated_fare']);
        $this->assertEquals(8000, $route['extra_stops_fare']);
    }

    public function test_admin_can_reset_tariffs_to_defaults()
    {
        // Ubah dulu ke nilai aneh
        Setting::set('rate_per_km', 99999);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.tariffs.reset'))
            ->assertRedirect(route('admin.settings.tariffs'))
            ->assertSessionHas('success');

        $this->assertEquals('5000', Setting::get('rate_per_km'));
    }

    public function test_admin_can_update_tariffs_with_formatted_dots()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.settings.tariffs.update'), [
            'rate_per_km' => '8.500',
            'min_fare' => '12.000',
            'extra_stop_fare' => '7.000',
            'base_included_km' => 2.0,
            'multi_store_km_rate' => '3.500',
            'multi_store_extra_store_fare' => '6.000',

            'category_beli_antar' => '6.500',
            'category_ambil_antar' => '3.500',
            'category_toko_kirim' => '2.500',
            'category_dokumen' => '1.000',
            'category_multi_stop' => '17.500',
            'category_kirim_pihak_ketiga' => '8.500',

            'weight_ringan' => '0',
            'weight_sedang' => '15.000',
            'weight_berat' => '35.000',

            'platform_commission_percent' => 12.5,
            'payment_deadline_minutes' => 60,
            'min_topup_amount' => '25.000',
        ]);

        $response->assertRedirect(route('admin.settings.tariffs'))
            ->assertSessionHas('success');

        $this->assertEquals('8500', Setting::get('rate_per_km'));
        $this->assertEquals('12000', Setting::get('min_fare'));
        $this->assertEquals('17500', Setting::get('category_multi_stop'));
        $this->assertEquals('35000', Setting::get('weight_berat'));
        $this->assertEquals('25000', Setting::get('min_topup_amount'));
    }
}
