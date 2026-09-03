<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_customer_profile_requires_authentication()
    {
        $response = $this->get('/customer/profile');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_customer_can_access_profile_page()
    {
        $customer = Customer::create([
            'phone_number' => '081234567890',
            'name' => 'Budi Customer',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->actingAs($customer, 'customer')->get('/customer/profile');

        $response->assertStatus(200);
        $response->assertSee('Budi Customer');
        $response->assertSee('Profil Saya');
        $response->assertSee('Keluar dari Akun');
    }
}
