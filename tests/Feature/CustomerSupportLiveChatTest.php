<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\SupportMessage;
use App\Models\Wallet;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerSupportLiveChatTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Customer $customer;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first() ?: Admin::create([
            'name' => 'Admin Support Test',
            'email' => 'admin@jastipkuy.com',
            'password' => bcrypt('password123'),
        ]);

        $this->wilayah = Wilayah::first() ?: Wilayah::create([
            'name' => 'Malang',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'default_radius_km' => 10.0,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Dewi Pelanggan',
            'phone_number' => '081299998888',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);
        Wallet::create(['owner_role' => 'customer', 'owner_id' => $this->customer->id, 'balance' => 50000]);
    }

    public function test_customer_can_access_support_chat_page()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.support.chat'));

        $response->assertStatus(200);
        $response->assertSee('Customer Support');
        $response->assertSee('Online • Siap Membantu');
        $response->assertSee('Pusat Layanan');
    }

    public function test_guest_cannot_access_support_chat()
    {
        $response = $this->get(route('customer.support.chat'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_can_send_message_to_admin()
    {
        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('bukti_kendala.png', 400, 400);

        $response = $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.support.send'), [
                'message' => 'Halo Admin, pesanan saya kenapa belum diantar ya?',
                'attachment' => $fakeImage,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => [
                'sender_type' => 'customer',
                'message' => 'Halo Admin, pesanan saya kenapa belum diantar ya?',
                'is_mine' => true,
            ]
        ]);

        $this->assertDatabaseHas('support_messages', [
            'customer_id' => $this->customer->id,
            'sender_type' => 'customer',
            'message' => 'Halo Admin, pesanan saya kenapa belum diantar ya?',
            'is_read' => false,
        ]);
    }

    public function test_customer_can_fetch_messages_realtime()
    {
        SupportMessage::create([
            'customer_id' => $this->customer->id,
            'sender_type' => 'customer',
            'message' => 'Pertanyaan saya nomor satu.',
            'is_read' => true,
        ]);

        SupportMessage::create([
            'customer_id' => $this->customer->id,
            'admin_id' => $this->admin->id,
            'sender_type' => 'admin',
            'message' => 'Halo kak, siap kami bantu cek segera.',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->getJson(route('customer.support.messages'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'messages' => [
                '*' => ['id', 'sender_type', 'sender_name', 'message', 'time', 'is_mine']
            ]
        ]);

        // Pesan dari admin harus ditandai telah dibaca (is_read = true)
        $this->assertEquals(0, SupportMessage::unreadForCustomer($this->customer->id)->count());
    }

    public function test_admin_can_access_support_chat_dashboard()
    {
        SupportMessage::create([
            'customer_id' => $this->customer->id,
            'sender_type' => 'customer',
            'message' => 'Butuh bantuan segera min!',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.support.index'));

        $response->assertStatus(200);
        $response->assertSee('Live Support Desk');
        $response->assertSee('Dewi Pelanggan');
        $response->assertSee('Butuh bantuan segera min!');
    }

    public function test_admin_can_send_reply_to_customer()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.support.send', $this->customer->id), [
                'message' => 'Baik kak Dewi, masalah Anda sedang kami tindaklanjuti ya.',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => [
                'sender_type' => 'admin',
                'sender_name' => $this->admin->name,
                'is_admin' => true,
            ]
        ]);

        $this->assertDatabaseHas('support_messages', [
            'customer_id' => $this->customer->id,
            'admin_id' => $this->admin->id,
            'sender_type' => 'admin',
            'message' => 'Baik kak Dewi, masalah Anda sedang kami tindaklanjuti ya.',
            'is_read' => false,
        ]);
    }

    public function test_admin_fetching_messages_marks_customer_messages_as_read()
    {
        $msg = SupportMessage::create([
            'customer_id' => $this->customer->id,
            'sender_type' => 'customer',
            'message' => 'Pesan belum dibaca oleh admin',
            'is_read' => false,
        ]);

        $this->assertEquals(1, SupportMessage::unreadForAdmin()->count());

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.support.messages', $this->customer->id));

        $response->assertStatus(200);
        $this->assertTrue($msg->fresh()->is_read);
        $this->assertEquals(0, SupportMessage::unreadForAdmin()->count());
    }

    public function test_customer_can_send_iphone_heic_attachment_in_support_chat()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $tmpFile = tempnam(sys_get_temp_dir(), 'heic_chat') . '.heic';
        file_put_contents($tmpFile, 'fake-iphone-chat-heic-content');
        $heicImage = new \Illuminate\Http\UploadedFile($tmpFile, 'screenshot_iphone.HEIC', 'image/heic', null, true);

        $response = $this->actingAs($this->customer, 'customer')
            ->postJson(route('customer.support.send'), [
                'message' => 'Ini bukti tangkapan layar dari HP iPhone.',
                'attachment' => $heicImage,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $lastMessage = SupportMessage::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($lastMessage);
        $this->assertNotNull($lastMessage->attachment_path);

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }
}

