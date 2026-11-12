<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JastiperHistoryAndChatReadTest extends TestCase
{
    use RefreshDatabase;

    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wilayah = Wilayah::create([
            'name' => 'Sidoarjo',
            'latitude' => -7.4478,
            'longitude' => 112.7183,
            'default_radius_km' => 10.0,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_jastiper_history()
    {
        $response = $this->get(route('jastiper.history'));
        $response->assertRedirect('/login');
    }

    public function test_jastiper_can_access_history_page()
    {
        $customer = Customer::create([
            'name' => 'Budi Customer',
            'phone_number' => '081234567890',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $jastiper = Jastiper::create([
            'name' => 'Joko Driver',
            'phone_number' => '082222222222',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'jastiper_id' => $jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip beli cemilan enak',
            'origin_address' => 'Jl. Jati No. 1, Sidoarjo',
            'destination_address' => 'Jl. Pahlawan No. 2, Sidoarjo',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 35000,
            'agreed_fare' => 35000,
            'status' => 'selesai',
            'distance_km' => 3.5,
            'total_amount' => 50000,
        ]);

        $response = $this->actingAs($jastiper, 'jastiper')->get(route('jastiper.history'));
        $response->assertStatus(200);
        $response->assertSee('Riwayat Pesanan');
        $response->assertSee('#Order ' . $order->id);
        $response->assertSee('35.000');
    }

    public function test_customer_can_mark_all_chats_as_read()
    {
        $customer = Customer::create([
            'name' => 'Budi Customer',
            'phone_number' => '081234567890',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $jastiper = Jastiper::create([
            'name' => 'Joko Driver',
            'phone_number' => '082222222222',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'jastiper_id' => $jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip beli cemilan enak',
            'origin_address' => 'Jl. Jati No. 1, Sidoarjo',
            'destination_address' => 'Jl. Pahlawan No. 2, Sidoarjo',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 35000,
            'agreed_fare' => 35000,
            'status' => 'selesai',
            'distance_km' => 3.5,
            'total_amount' => 50000,
        ]);

        Chat::create([
            'order_id' => $order->id,
            'sender_role' => 'jastiper',
            'sender_id' => $jastiper->id,
            'message' => 'Halo kak',
            'message_type' => 'text',
            'is_read' => false,
        ]);

        Chat::create([
            'order_id' => $order->id,
            'sender_role' => 'system',
            'sender_id' => 0,
            'message' => 'Pesanan telah selesai',
            'message_type' => 'text',
            'is_read' => false,
        ]);

        $this->assertEquals(2, Chat::where('order_id', $order->id)->where('is_read', false)->count());

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('customer.chats.mark-all-read'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(0, Chat::where('order_id', $order->id)->where('is_read', false)->count());
    }
}
