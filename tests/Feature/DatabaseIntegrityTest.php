<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Wallet;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_composite_unique_key_allows_new_insert_after_soft_delete()
    {
        // 1. Create a customer
        $customer1 = Customer::create([
            'phone_number' => '08123456789',
            'name' => 'John Doe',
        ]);

        $this->assertDatabaseHas('customers', [
            'phone_number' => '08123456789',
            'name' => 'John Doe',
        ]);

        // 2. Soft delete the first customer
        $customer1->delete();
        $this->assertSoftDeleted($customer1);

        // 3. Create another customer with the same phone number (which should succeed now)
        $customer2 = Customer::create([
            'phone_number' => '08123456789',
            'name' => 'Jane Doe',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer2->id,
            'phone_number' => '08123456789',
            'name' => 'Jane Doe',
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function test_wallet_polymorphic_relationships()
    {
        // Create a customer
        $customer = Customer::create([
            'phone_number' => '08111111111',
            'name' => 'Alice',
        ]);

        // Create customer wallet
        $customerWallet = Wallet::create([
            'owner_role' => 'customer',
            'owner_id' => $customer->id,
            'balance' => 50000.00,
        ]);

        // Check relationship
        $this->assertNotNull($customer->wallet);
        $this->assertEquals(50000.00, $customer->wallet->balance);
        $this->assertInstanceOf(Customer::class, $customerWallet->owner);
        $this->assertEquals($customer->id, $customerWallet->owner->id);

        // Create a wilayah
        $wilayah = Wilayah::create([
            'name' => 'Bandung',
            'default_radius_km' => 10.00,
            'is_active' => true,
        ]);

        // Create a jastiper
        $jastiper = Jastiper::create([
            'phone_number' => '08222222222',
            'name' => 'Bob',
            'verification_status' => 'approved',
            'wilayah_id' => $wilayah->id,
            'radius_km' => 5.00,
            'is_available' => true,
        ]);

        // Create jastiper wallet
        $jastiperWallet = Wallet::create([
            'owner_role' => 'jastiper',
            'owner_id' => $jastiper->id,
            'balance' => 100000.00,
        ]);

        // Check relationship
        $this->assertNotNull($jastiper->wallet);
        $this->assertEquals(100000.00, $jastiper->wallet->balance);
        $this->assertInstanceOf(Jastiper::class, $jastiperWallet->owner);
        $this->assertEquals($jastiper->id, $jastiperWallet->owner->id);
    }
}
