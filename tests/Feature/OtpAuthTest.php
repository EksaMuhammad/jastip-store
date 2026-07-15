<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OtpAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default CancellationPolicies as required by models/events
        $this->artisan('db:seed');
    }

    public function test_login_page_renders_successfully()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSeeLivewire('auth.otp-auth');
    }

    public function test_new_user_shows_registration_fields()
    {
        Livewire::test('auth.otp-auth')
            ->set('phone_number', '089999999999')
            ->set('role', 'customer')
            ->call('startOtpProcess')
            ->assertSet('step', 1)
            ->assertSet('is_new_user', true);
    }

    public function test_can_register_new_customer_with_password()
    {
        Livewire::test('auth.otp-auth')
            ->set('phone_number', '089999999999')
            ->set('role', 'customer')
            ->call('startOtpProcess')
            ->assertSet('is_new_user', true)
            ->set('name', 'Andi Wijaya')
            ->set('password', 'secret123')
            ->call('registerAndSendOtp')
            ->assertSet('step', 2);

        $this->assertDatabaseHas('customers', [
            'phone_number' => '089999999999',
            'name' => 'Andi Wijaya'
        ]);

        $customer = Customer::where('phone_number', '089999999999')->first();
        $this->assertTrue(Hash::check('secret123', $customer->password));
    }

    public function test_can_register_new_jastiper()
    {
        $wilayah = Wilayah::first();

        Livewire::test('auth.otp-auth')
            ->set('phone_number', '089999999998')
            ->set('role', 'jastiper')
            ->call('startOtpProcess')
            ->assertSet('is_new_user', true)
            ->set('name', 'Bambang Jastip')
            ->set('password', 'secret123')
            ->set('wilayah_id', $wilayah->id)
            ->call('registerAndSendOtp')
            ->assertSet('step', 2);

        $this->assertDatabaseHas('jastiper', [
            'phone_number' => '089999999998',
            'name' => 'Bambang Jastip',
            'wilayah_id' => $wilayah->id,
        ]);
    }

    public function test_can_login_with_password_directly()
    {
        $customer = Customer::create([
            'phone_number' => '085555555555',
            'name' => 'Test Customer',
            'password' => Hash::make('secret123'),
        ]);

        // Access login page, phone number check should show password field
        Livewire::test('auth.otp-auth')
            ->set('phone_number', '085555555555')
            ->set('role', 'customer')
            ->call('startOtpProcess')
            ->assertSet('show_password_field', true)
            ->assertSet('is_new_user', false)
            ->set('login_password', 'secret123')
            ->call('loginWithPassword')
            ->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_can_verify_otp_and_login_customer()
    {
        $customer = Customer::create([
            'phone_number' => '085555555555',
            'name' => 'Test Customer',
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        Livewire::test('auth.otp-auth')
            ->set('phone_number', '085555555555')
            ->set('role', 'customer')
            ->set('step', 2)
            ->set('otp_input', '123456')
            ->call('verifyOtp')
            ->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_phone_number_normalization()
    {
        // 1. Inputs starting with 62 or +62
        $customer = Customer::create([
            'phone_number' => '081122334455',
            'name' => 'Budi Utomo',
            'password' => Hash::make('secret123'),
        ]);

        Livewire::test('auth.otp-auth')
            ->set('phone_number', '+6281122334455')
            ->set('role', 'customer')
            ->call('startOtpProcess')
            ->assertSet('phone_number', '081122334455') // Assert normalization to 08...
            ->assertSet('show_password_field', true)
            ->set('login_password', 'secret123')
            ->call('loginWithPassword')
            ->assertRedirect(route('customer.dashboard'));
    }
}
