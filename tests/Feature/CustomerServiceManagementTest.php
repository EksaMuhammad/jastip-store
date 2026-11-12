<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerService;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Report;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\Wallet;
use App\Models\Wilayah;
use App\Services\CustomerSupportRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected CustomerService $cs1;
    protected CustomerService $cs2;
    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first() ?: Admin::create([
            'name' => 'Admin JastipKuy',
            'email' => 'admin@jastipkuy.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $this->wilayah = Wilayah::first() ?: Wilayah::create([
            'name' => 'Malang Kota',
            'default_radius_km' => 5.0,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Budi Customer',
            'phone_number' => '081234567890',
            'wilayah_id' => $this->wilayah->id,
        ]);
        Wallet::firstOrCreate(['owner_id' => $this->customer->id, 'owner_role' => 'customer'], ['balance' => 200000]);

        $this->jastiper = Jastiper::create([
            'name' => 'Siti Jastiper',
            'phone_number' => '081234567891',
            'wilayah_id' => $this->wilayah->id,
            'verification_status' => 'approved',
            'is_available' => true,
            'radius_km' => 5.0,
        ]);
        Wallet::firstOrCreate(['owner_id' => $this->jastiper->id, 'owner_role' => 'jastiper'], ['balance' => 100000]);

        $this->cs1 = CustomerService::where('email', 'cs1@jastipkuy.com')->first() ?: CustomerService::create([
            'name' => 'Budi CS 1',
            'email' => 'cs1@jastipkuy.com',
            'password_hash' => Hash::make('password123'),
            'phone_number' => '081234567895',
            'is_active' => true,
            'is_online' => true,
            'work_status' => 'tersedia',
            'last_active_at' => now(),
        ]);

        $this->cs2 = CustomerService::where('email', 'cs2@jastipkuy.com')->first() ?: CustomerService::create([
            'name' => 'Dewi CS 2',
            'email' => 'cs2@jastipkuy.com',
            'password_hash' => Hash::make('password123'),
            'phone_number' => '081234567896',
            'is_active' => true,
            'is_online' => true,
            'work_status' => 'tersedia',
            'last_active_at' => now(),
        ]);
    }

    public function test_admin_can_view_cs_officers_and_metrics()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.cs.index'));

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee('Dewi Lestari');
        $response->assertSee('Total Staf CS');
    }

    public function test_admin_can_create_and_update_cs_officer()
    {
        // 1. Create
        $createResponse = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.cs.store'), [
                'name' => 'Officer Baru',
                'email' => 'baru@jastipkuy.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'phone_number' => '081211112222',
            ]);

        $createResponse->assertRedirect(route('admin.cs.index'));
        $this->assertDatabaseHas('customer_services', [
            'email' => 'baru@jastipkuy.com',
            'name' => 'Officer Baru',
        ]);

        $officer = CustomerService::where('email', 'baru@jastipkuy.com')->first();

        // 2. Update
        $updateResponse = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.cs.update', $officer->id), [
                'name' => 'Officer Baru Updated',
                'email' => 'baru@jastipkuy.com',
                'phone_number' => '081299990000',
            ]);

        $updateResponse->assertRedirect(route('admin.cs.index'));
        $this->assertDatabaseHas('customer_services', [
            'id' => $officer->id,
            'name' => 'Officer Baru Updated',
            'phone_number' => '081299990000',
        ]);
    }

    public function test_admin_can_toggle_cs_officer_status()
    {
        $this->assertTrue($this->cs1->is_active);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.cs.toggle-status', $this->cs1->id));

        $response->assertRedirect(route('admin.cs.index'));
        $this->assertFalse($this->cs1->fresh()->is_active);
    }

    public function test_admin_monitoring_api_returns_live_metrics()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.cs.monitoring'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'timestamp',
            'metrics' => [
                'total_cs',
                'online_cs',
                'free_cs',
                'busy_cs',
                'offline_cs',
                'unassigned_chats',
                'unassigned_reports',
            ],
            'officers',
        ]);
    }

    public function test_cs_can_login_and_logout()
    {
        // 1. Show login
        $this->get(route('cs.login'))->assertStatus(200);

        // 2. Login
        $loginResponse = $this->post(route('cs.login.submit'), [
            'email' => 'cs1@jastipkuy.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect(route('cs.dashboard'));
        $this->assertAuthenticatedAs($this->cs1, 'cs');
        $this->assertTrue($this->cs1->fresh()->is_online);

        // 3. Logout
        $logoutResponse = $this->actingAs($this->cs1, 'cs')
            ->post(route('cs.logout'));

        $logoutResponse->assertRedirect(route('cs.login'));
        $this->assertFalse($this->cs1->fresh()->is_online);
    }

    public function test_cs_can_access_live_chat_page()
    {
        // Buat conversation dan pesan chat pelanggan
        SupportConversation::create([
            'customer_id' => $this->customer->id,
            'assigned_cs_id' => $this->cs1->id,
            'status' => 'aktif',
            'last_message_at' => now(),
        ]);

        SupportMessage::create([
            'customer_id' => $this->customer->id,
            'sender_type' => 'customer',
            'message' => 'Halo CS, mau tanya pesanan saya',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->cs1, 'cs')
            ->get(route('cs.chats.index'));

        $response->assertStatus(200);
        $response->assertSee('Live Support Desk CS');
        $response->assertSee('alpinejs');

        // Test with selected customer (like clicking "Buka Chat" on dashboard)
        $responseWithCustomer = $this->actingAs($this->cs1, 'cs')
            ->get(route('cs.chats.index', ['customer_id' => $this->customer->id]));

        $responseWithCustomer->assertStatus(200);
        $responseWithCustomer->assertSee($this->customer->name);
        $responseWithCustomer->assertSee('Halo CS, mau tanya pesanan saya');

        // Test fetchMessages API endpoint
        $apiResponse = $this->actingAs($this->cs1, 'cs')
            ->get(route('cs.chats.messages', $this->customer->id));

        $apiResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'customer' => [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                ],
            ]);
    }

    public function test_routing_service_auto_routes_chat_to_available_cs()
    {
        $routingService = app(CustomerSupportRoutingService::class);

        $customer2 = Customer::create([
            'name' => 'Customer Lain',
            'phone_number' => '081299990001',
            'wilayah_id' => $this->wilayah->id,
        ]);

        // CS 1 has 0 active chats, CS 2 has 1 active chat
        SupportConversation::create([
            'customer_id' => $customer2->id,
            'assigned_cs_id' => $this->cs2->id,
            'status' => 'aktif',
            'last_message_at' => now(),
        ]);

        // Customer initiates chat
        $conversation = $routingService->routeCustomerChat($this->customer);

        // Should be assigned to cs1 (least loaded)
        $this->assertEquals($this->cs1->id, $conversation->assigned_cs_id);
        $this->assertEquals('aktif', $conversation->status);
    }

    public function test_routing_service_queues_chat_when_all_cs_offline()
    {
        // Set all CS offline
        CustomerService::query()->update(['is_online' => false]);

        $routingService = app(CustomerSupportRoutingService::class);
        $conversation = $routingService->routeCustomerChat($this->customer);

        // Customer is not lost; placed in queue
        $this->assertNull($conversation->assigned_cs_id);
        $this->assertEquals('menunggu', $conversation->status);
    }

    public function test_cs_can_claim_and_reply_in_live_chat()
    {
        // Queue conversation
        $conversation = SupportConversation::create([
            'customer_id' => $this->customer->id,
            'assigned_cs_id' => null,
            'status' => 'menunggu',
            'last_message_at' => now(),
        ]);

        // Customer sent initial message
        SupportMessage::create([
            'customer_id' => $this->customer->id,
            'sender_type' => 'customer',
            'message' => 'Halo CS, pesanan saya ada kendala nih.',
            'is_read' => false,
        ]);

        // CS 1 claims chat
        $claimResponse = $this->actingAs($this->cs1, 'cs')
            ->post(route('cs.chats.claim', $this->customer->id));

        $claimResponse->assertRedirect(route('cs.chats.index', ['customer_id' => $this->customer->id]));
        $this->assertEquals($this->cs1->id, $conversation->fresh()->assigned_cs_id);
        $this->assertEquals('aktif', $conversation->fresh()->status);

        // CS 1 sends message
        $sendResponse = $this->actingAs($this->cs1, 'cs')
            ->post(route('cs.chats.send', $this->customer->id), [
                'message' => 'Halo Kak Budi, ada yang bisa saya bantu terkait pesanannya?',
            ]);

        $sendResponse->assertStatus(200);
        $sendResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('support_messages', [
            'customer_id' => $this->customer->id,
            'cs_id' => $this->cs1->id,
            'message' => 'Halo Kak Budi, ada yang bisa saya bantu terkait pesanannya?',
        ]);

        // CS closes chat
        $closeResponse = $this->actingAs($this->cs1, 'cs')
            ->post(route('cs.chats.close', $this->customer->id));

        $closeResponse->assertRedirect(route('cs.chats.index'));
        $this->assertEquals('selesai', $conversation->fresh()->status);
    }

    public function test_cs_can_claim_and_resolve_order_dispute()
    {
        // Create an active order and payment
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Beli Donat Kentang 2 Box',
            'destination_address' => 'Jl. Ijen No. 10, Malang',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 50000,
            'agreed_fare' => 50000,
            'status' => 'diproses',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 50000,
            'method' => 'transfer',
            'status' => 'lunas',
        ]);

        $initialWalletBalance = $this->customer->wallet->fresh()->balance;

        // Customer creates report
        $report = Report::create([
            'order_id' => $order->id,
            'reporter_role' => 'customer',
            'reporter_id' => $this->customer->id,
            'category' => 'barang_rusak',
            'description' => 'Donat kemasan hancur dan penyok parah saat sampai.',
            'status' => 'menunggu',
            'cs_claim_status' => 'unassigned',
        ]);

        // CS claims report
        $this->actingAs($this->cs1, 'cs')
            ->post(route('cs.reports.claim', $report->id))
            ->assertRedirect(route('cs.reports.show', $report->id));

        $this->assertEquals($this->cs1->id, $report->fresh()->assigned_cs_id);

        // CS resolves with 100% full refund
        $resolveResponse = $this->actingAs($this->cs1, 'cs')
            ->post(route('cs.reports.resolve', $report->id), [
                'action' => 'refund_full',
                'admin_decision' => 'Komplain valid setelah melihat bukti kemasan hancur. Dilakukan pengembalian dana penuh ke dompet customer.',
                'investigation_notes' => 'Telah dikonfirmasi ke Jastiper terkait kecelakaan di jalan.',
            ]);

        $resolveResponse->assertRedirect(route('cs.reports.show', $report->id));

        $freshReport = $report->fresh();
        $this->assertEquals('selesai', $freshReport->status);
        $this->assertEquals('refund_full', $freshReport->resolution_type);
        $this->assertEquals(50000, (float) $freshReport->refund_amount);

        // Verify customer wallet credited
        $this->assertEquals($initialWalletBalance + 50000, (float) $this->customer->wallet->fresh()->balance);
    }
}
