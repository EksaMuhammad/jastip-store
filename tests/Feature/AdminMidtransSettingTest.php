<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminMidtransSettingTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');
        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first();
    }

    public function test_midtrans_settings_page_requires_admin_auth()
    {
        $this->get(route('admin.settings.midtrans'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_midtrans_settings_page()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.midtrans'))
            ->assertStatus(200)
            ->assertSee('Pengaturan Midtrans Payment Gateway')
            ->assertSee('Server Key')
            ->assertSee('Client Key')
            ->assertSee('URL Callback Midtrans')
            ->assertSee('Uji Koneksi Server Key');
    }

    public function test_admin_can_save_midtrans_settings()
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.settings.midtrans.update'), [
                'server_key' => 'SB-Mid-server-TEST-KEY-12345',
                'client_key' => 'SB-Mid-client-TEST-KEY-12345',
                'merchant_id' => 'G987654321',
                'is_production' => 0,
                'is_active' => 1,
                'enabled_payments' => ['qris', 'gopay', 'bca_va'],
            ])
            ->assertRedirect(route('admin.settings.midtrans'))
            ->assertSessionHas('success');

        $this->assertEquals('SB-Mid-server-TEST-KEY-12345', Setting::get('midtrans_server_key'));
        $this->assertEquals('SB-Mid-client-TEST-KEY-12345', Setting::get('midtrans_client_key'));
        $this->assertEquals('G987654321', Setting::get('midtrans_merchant_id'));
        $this->assertEquals('0', Setting::get('midtrans_is_production'));
        $this->assertEquals('1', Setting::get('midtrans_is_active'));
        $this->assertEquals('qris,gopay,bca_va', Setting::get('midtrans_enabled_payments'));

        // Pastikan config runtime juga terupdate
        $this->assertEquals('SB-Mid-server-TEST-KEY-12345', config('services.midtrans.server_key'));
        $this->assertEquals('SB-Mid-client-TEST-KEY-12345', config('services.midtrans.client_key'));
    }

    public function test_admin_test_connection_requires_server_key()
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.settings.midtrans.test'), [
                'server_key' => '',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_admin_test_connection_success_with_valid_credentials()
    {
        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*' => Http::response(['status_code' => '404', 'status_message' => 'Transaction not found'], 404),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.settings.midtrans.test'), [
                'server_key' => 'SB-Mid-server-VALID-KEY',
                'is_production' => 0,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_admin_test_connection_fails_with_unauthorized_key()
    {
        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*' => Http::response(['status_code' => '401', 'status_message' => 'Access denied'], 401),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.settings.midtrans.test'), [
                'server_key' => 'SB-Mid-server-WRONG-KEY',
                'is_production' => 0,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => false,
            ]);
    }
}
