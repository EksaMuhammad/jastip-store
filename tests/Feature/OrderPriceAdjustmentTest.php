<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPriceAdjustment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPriceAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;
    protected Merchant $merchant;
    protected Order $order;
    protected OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wilayah = Wilayah::create([
            'name' => 'Malang',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'default_radius_km' => 10.0,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Budi Customer',
            'phone_number' => '081234567890',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->jastiper = Jastiper::create([
            'name' => 'Joko Driver',
            'phone_number' => '082222222222',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);

        $this->merchant = Merchant::create([
            'name' => 'Toko Oleh-Oleh Khas Malang',
            'email' => 'toko@example.com',
            'password' => bcrypt('secret123'),
            'phone_number' => '081987654321',
            'type' => 'food',
            'address' => 'Jl. Ijen No. 10, Malang',
            'latitude' => -7.9700,
            'longitude' => 112.6200,
            'is_active' => true,
        ]);

        // Buat order non-katalog / custom item dengan estimasi awal Rp 25.000
        $this->order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'merchant_id' => $this->merchant->id,
            'wilayah_id' => $this->wilayah->id,
            'is_catalog_order' => false,
            'category' => 'beli-antar',
            'weight_category' => 'ringan',
            'description' => 'Titip beli oleh-oleh khas Malang',
            'status' => 'diproses',
            'origin_address' => 'Jl. Ijen No. 10, Malang',
            'destination_address' => 'Jl. Veteran No. 5, Malang',
            'recipient_name' => 'Budi Customer',
            'recipient_phone' => '081234567890',
            'estimated_fare' => 8000,
            'agreed_fare' => 8000,
            'downpayment_amount' => 33000,
            'cash_amount' => 0,
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'merchant_id' => $this->merchant->id,
            'product_id' => null, // Non-katalog
            'name' => 'Keripik Apel Batu Premium 250g',
            'quantity' => 1,
            'estimated_price' => 25000,
            'actual_price' => null,
            'notes' => 'Tolong belikan yang kemasan warna merah',
        ]);

        // Buat pembayaran DP lunas
        Payment::create([
            'order_id' => $this->order->id,
            'method' => 'transfer',
            'channel' => 'qris',
            'amount' => 33000,
            'status' => 'lunas',
            'verified_at' => now(),
        ]);
    }

    public function test_jastiper_can_fetch_eligible_items_for_adjustment()
    {
        $response = $this->actingAs($this->jastiper, 'jastiper')
            ->getJson("/jastiper/orders/{$this->order->id}/adjustment-items");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->orderItem->id,
                'name' => 'Keripik Apel Batu Premium 250g',
                'current_price' => 25000,
                'has_pending' => false,
            ]);
    }

    public function test_jastiper_can_propose_price_adjustment_with_photo()
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('struk_toko.jpg', 600, 600);

        $response = $this->actingAs($this->jastiper, 'jastiper')
            ->postJson("/jastiper/orders/{$this->order->id}/price-adjustments", [
                'order_item_id' => $this->orderItem->id,
                'proposed_price' => 30000, // Harga naik Rp 5.000
                'reason' => 'Harga di kasir toko naik menjadi Rp 30.000',
                'proof_image' => $photo,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pengajuan penyesuaian harga berhasil dikirim ke Chat.',
            ]);

        // Pastikan tabel order_price_adjustments terisi
        $this->assertDatabaseHas('order_price_adjustments', [
            'order_id' => $this->order->id,
            'order_item_id' => $this->orderItem->id,
            'jastiper_id' => $this->jastiper->id,
            'original_price' => 25000,
            'proposed_price' => 30000,
            'difference' => 5000,
            'status' => 'pending',
        ]);

        // Pastikan pesan interaktif masuk ke tabel chats
        $this->assertDatabaseHas('chats', [
            'order_id' => $this->order->id,
            'message_type' => 'action_button',
        ]);
    }

    public function test_customer_can_approve_price_adjustment_and_receipt_is_updated()
    {
        // Jastiper propose penyesuaian harga
        $adjustment = OrderPriceAdjustment::create([
            'order_id' => $this->order->id,
            'order_item_id' => $this->orderItem->id,
            'jastiper_id' => $this->jastiper->id,
            'item_name' => $this->orderItem->name,
            'original_price' => 25000,
            'proposed_price' => 30000,
            'difference' => 5000,
            'reason' => 'Harga toko naik',
            'status' => 'pending',
        ]);

        // Customer menyetujui
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson("/customer/price-adjustments/{$adjustment->id}/respond", [
                'action' => 'approve',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'approved',
            ]);

        // Verifikasi status adjustment disetujui
        $this->assertDatabaseHas('order_price_adjustments', [
            'id' => $adjustment->id,
            'status' => 'approved',
        ]);

        // Verifikasi actual_price di order_items terupdate
        $this->assertDatabaseHas('order_items', [
            'id' => $this->orderItem->id,
            'actual_price' => 30000,
        ]);

        // Verifikasi cash_amount di order bertambah Rp 5.000 untuk selisih bayar
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'cash_amount' => 5000,
        ]);

        // Verifikasi halaman rincian pesanan customer menampilkan penyesuaian
        $showResponse = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders.show', $this->order->id));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('Harga Toko: Rp30.000');
        $showResponse->assertSee('Penyesuaian Harga Asli Toko');
        $showResponse->assertSee('Rp40.000');
    }

    public function test_customer_can_reject_price_adjustment()
    {
        $adjustment = OrderPriceAdjustment::create([
            'order_id' => $this->order->id,
            'order_item_id' => $this->orderItem->id,
            'jastiper_id' => $this->jastiper->id,
            'item_name' => $this->orderItem->name,
            'original_price' => 25000,
            'proposed_price' => 30000,
            'difference' => 5000,
            'reason' => 'Harga toko naik',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->postJson("/customer/price-adjustments/{$adjustment->id}/respond", [
                'action' => 'reject',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'rejected',
            ]);

        $this->assertDatabaseHas('order_price_adjustments', [
            'id' => $adjustment->id,
            'status' => 'rejected',
        ]);

        // actual_price tetap null
        $this->assertDatabaseHas('order_items', [
            'id' => $this->orderItem->id,
            'actual_price' => null,
        ]);
    }

    public function test_customer_can_cancel_order_via_price_adjustment()
    {
        $adjustment = OrderPriceAdjustment::create([
            'order_id' => $this->order->id,
            'order_item_id' => $this->orderItem->id,
            'jastiper_id' => $this->jastiper->id,
            'item_name' => $this->orderItem->name,
            'original_price' => 25000,
            'proposed_price' => 30000,
            'difference' => 5000,
            'reason' => 'Harga toko naik',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->postJson("/customer/price-adjustments/{$adjustment->id}/respond", [
                'action' => 'cancel_order',
                'cancel_reason' => 'Harga di luar estimasi',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'cancelled',
            ]);

        $this->assertDatabaseHas('order_price_adjustments', [
            'id' => $adjustment->id,
            'status' => 'cancelled',
        ]);

        // Pesanan terbatalkan
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'dibatalkan',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'kredit',
            'source' => 'refund_pembatalan',
            'reference_order_id' => $this->order->id,
        ]);

        // Verifikasi halaman rincian pesanan menampilkan banner alert pembatalan kenaikan harga toko
        $showResponse = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders.show', $this->order->id));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('Pesanan Dibatalkan');
        $showResponse->assertSee('Kenaikan Harga Toko');
        $showResponse->assertSee('Saldo Dompet');
    }

    /**
     * Uji 6: Riwayat chat menyertakan adjustment_status dan halaman show menampilkan tombol aksi langsung.
     */
    public function test_chat_history_includes_adjustment_status_and_show_renders_direct_action_buttons()
    {
        $adjustment = OrderPriceAdjustment::create([
            'order_id' => $this->order->id,
            'order_item_id' => $this->orderItem->id,
            'jastiper_id' => $this->jastiper->id,
            'item_name' => $this->orderItem->name,
            'original_price' => 25000,
            'proposed_price' => 28000,
            'difference' => 3000,
            'status' => 'pending',
        ]);

        \App\Models\Chat::create([
            'order_id' => $this->order->id,
            'sender_type' => 'system',
            'sender_id' => 0,
            'sender_role' => 'system',
            'message_type' => 'action_button',
            'message' => "🏷️ KONFIRMASI HARGA ASLI TOKO\nBarang: Keripik Apel\nEstimasi Awal: Rp 25.000\nHarga Asli Toko: Rp 28.000\nSelisih: +Rp 3.000",
            'action_type' => 'price_adjustment:' . $adjustment->id,
            'is_read' => false,
        ]);

        $this->actingAs($this->customer, 'customer');

        // 1. Verifikasi chat history mengembalikan adjustment_status
        $chatRes = $this->getJson(route('customer.orders.chat.history', $this->order->id));
        $chatRes->assertStatus(200);
        $messages = $chatRes->json('messages');
        $adjMsg = collect($messages)->firstWhere('action_type', 'price_adjustment:' . $adjustment->id);
        $this->assertNotNull($adjMsg);
        $this->assertEquals('pending', $adjMsg['adjustment_status']);
        $this->assertEquals('action_button', $adjMsg['message_type']);

        // 2. Verifikasi halaman customer order detail menampilkan tombol langsung Setujui & Tolak
        $pageRes = $this->get(route('customer.orders.show', $this->order->id));
        $pageRes->assertStatus(200);
        $pageRes->assertSee('Perlu Konfirmasi');
        $pageRes->assertSee('Ada Perubahan Harga di Toko!');
        $pageRes->assertSee('Setujui (+Rp3.000)');
        $pageRes->assertSee('Tolak');

        // 3. Verifikasi submit form langsung menyetujui penyesuaian harga
        $submitRes = $this->post(route('customer.price-adjustments.respond', $adjustment->id), [
            'action' => 'approve',
        ]);
        $submitRes->assertRedirect();
        $this->assertDatabaseHas('order_price_adjustments', [
            'id' => $adjustment->id,
            'status' => 'approved',
        ]);
    }
}

