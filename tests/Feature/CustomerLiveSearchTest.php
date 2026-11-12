<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLiveSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'phone_number' => '081299887766',
            'name' => 'Budi Testing',
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_live_search_requires_customer_authentication(): void
    {
        $response = $this->getJson(route('customer.search.live', ['q' => 'beli']));
        $response->assertStatus(401);
    }

    public function test_live_search_empty_query_returns_empty_structure(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')
            ->getJson(route('customer.search.live', ['q' => '']));

        $response->assertStatus(200);
        $response->assertJson([
            'query' => '',
            'services' => [],
            'merchants' => [],
            'products' => [],
            'total' => 0,
        ]);
    }

    public function test_live_search_returns_matching_services_merchants_and_products(): void
    {
        $customer = $this->createCustomer();

        $merchant = Merchant::create([
            'name' => 'Warung Beli-Beli Barokah',
            'email' => 'warungbelibeli@example.com',
            'phone_number' => '081233445566',
            'type' => 'food',
            'address' => 'Jl. Soekarno Hatta No 12, Malang',
            'is_active' => true,
        ]);

        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Paket Beli Kenyang Ayam Bakar',
            'description' => 'Ayam bakar lezat khas Malang',
            'estimated_price' => 25000,
            'is_available' => true,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->getJson(route('customer.search.live', ['q' => 'Beli']));

        $response->assertStatus(200);
        $data = $response->json();

        // 1. Matched Services (Beli-Antar & Belanja Retail)
        $this->assertNotEmpty($data['services']);
        $serviceNames = collect($data['services'])->pluck('name')->toArray();
        $this->assertTrue(in_array('Beli-Antar', $serviceNames) || in_array('Belanja Retail', $serviceNames));

        // 2. Matched Merchants
        $this->assertNotEmpty($data['merchants']);
        $this->assertEquals('Warung Beli-Beli Barokah', $data['merchants'][0]['name']);

        // 3. Matched Products
        $this->assertNotEmpty($data['products']);
        $this->assertEquals('Paket Beli Kenyang Ayam Bakar', $data['products'][0]['name']);
        $this->assertEquals('Rp 25.000', $data['products'][0]['formatted_price']);

        // 4. Overall total count and search URL
        $this->assertGreaterThanOrEqual(3, $data['total']);
        $this->assertStringContainsString('customer/search?q=Beli', $data['search_all_url']);
    }

    public function test_customer_dashboard_renders_chat_button_and_profil_nav(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        // Header contains Chat button linking to customer.chats
        $response->assertSee(route('customer.chats'));
        // Bottom nav contains Profil button linking to customer.profile
        $response->assertSee(route('customer.profile'));
        $response->assertSee('searchAutocomplete');
    }
}
