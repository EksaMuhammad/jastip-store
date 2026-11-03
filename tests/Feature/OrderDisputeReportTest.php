<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Report;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderDisputeReportTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Customer $customer;
    protected Customer $otherCustomer;
    protected Jastiper $jastiper;
    protected Wilayah $wilayah;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->admin = Admin::where('email', 'admin@jastipkuy.com')->first() ?: Admin::create([
            'name' => 'Admin Test',
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
            'name' => 'Siti Pelanggan',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);
        Wallet::create(['owner_role' => 'customer', 'owner_id' => $this->customer->id, 'balance' => 0]);

        $this->otherCustomer = Customer::create([
            'name' => 'Budi Lain',
            'phone_number' => '081234567892',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'wilayah_id' => $this->wilayah->id,
        ]);
        Wallet::create(['owner_role' => 'customer', 'owner_id' => $this->otherCustomer->id, 'balance' => 0]);

        $this->jastiper = Jastiper::create([
            'name' => 'Rahmat Jastiper',
            'phone_number' => '082222222223',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'wilayah_id' => $this->wilayah->id,
            'radius_km' => 5.0,
            'is_available' => true,
        ]);
        Wallet::create(['owner_role' => 'jastiper', 'owner_id' => $this->jastiper->id, 'balance' => 0]);

        $this->order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'kuliner',
            'weight_category' => 'ringan',
            'description' => 'Beli Bakpao Telo Khas Malang 2 Box',
            'origin_address' => 'Jl. Ijen No. 10, Malang',
            'destination_address' => 'Jl. Veteran No. 5, Malang',
            'recipient_name' => 'Siti Pelanggan',
            'recipient_phone' => '081234567891',
            'estimated_fare' => 50000,
            'agreed_fare' => 50000,
            'downpayment_amount' => 50000,
            'cash_amount' => 0,
            'status' => 'tiba_tujuan',
        ]);

        \App\Models\Payment::create([
            'order_id' => $this->order->id,
            'method' => 'wallet',
            'channel' => 'saldo_dompet',
            'amount' => 50000,
            'status' => 'lunas',
            'verified_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'name' => 'Bakpao Telo Coklat',
            'quantity' => 2,
            'estimated_price' => 20000,
            'actual_price' => 20000,
        ]);
    }

    public function test_customer_can_render_complaint_form_for_own_order()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders.report', $this->order->id));

        $response->assertStatus(200);
        $response->assertSee('Pengaduan Pesanan #' . $this->order->id);
        $response->assertSee('Barang Tidak Sesuai');
        $response->assertSee('Barang Rusak / Basi');
    }

    public function test_customer_cannot_access_complaint_form_for_other_customer_order()
    {
        $response = $this->actingAs($this->otherCustomer, 'customer')
            ->get(route('customer.orders.report', $this->order->id));

        $response->assertStatus(403);
    }

    public function test_customer_can_submit_valid_complaint_and_order_becomes_bermasalah()
    {
        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('bukti_rusak.jpg', 600, 600);

        $response = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.orders.report.store', $this->order->id), [
                'category' => 'barang_rusak',
                'description' => 'Kemasan bakpao sobek dan isinya rusak saat diserahkan.',
                'evidence_image' => $fakeImage,
            ]);

        $response->assertRedirect(route('customer.orders.report', $this->order->id));
        $response->assertSessionHas('success');

        // Pastikan laporan tersimpan di tabel reports
        $this->assertDatabaseHas('reports', [
            'order_id' => $this->order->id,
            'reporter_role' => 'customer',
            'reporter_id' => $this->customer->id,
            'category' => 'barang_rusak',
            'status' => 'menunggu',
        ]);

        // Status pesanan menjadi bermasalah
        $this->assertEquals('bermasalah', $this->order->fresh()->status);

        // Pesan sistem tercatat di tabel chats
        $this->assertDatabaseHas('chats', [
            'order_id' => $this->order->id,
            'sender_role' => 'system',
        ]);
    }

    public function test_customer_can_submit_complaint_with_iphone_heic_image()
    {
        Storage::fake('public');

        $tmpFile = tempnam(sys_get_temp_dir(), 'heic_test') . '.heic';
        file_put_contents($tmpFile, 'fake-iphone-heic-content');
        $heicImage = new UploadedFile($tmpFile, 'IMG_4829.HEIC', 'image/heic', null, true);

        $order2 = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $this->wilayah->id,
            'category' => 'kuliner',
            'weight_category' => 'ringan',
            'description' => 'Order for HEIC test',
            'origin_address' => 'Jl. Ijen No. 10, Malang',
            'destination_address' => 'Jl. Pengaduan HEIC No. 2',
            'recipient_name' => 'Siti Pelanggan',
            'recipient_phone' => '081234567891',
            'status' => 'selesai',
            'estimated_fare' => 20000,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.orders.report.store', $order2->id), [
                'category' => 'barang_tidak_sesuai',
                'description' => 'Foto bukti diambil langsung dari kamera iPhone dalam format HEIC.',
                'evidence_image' => $heicImage,
            ]);

        $response->assertRedirect(route('customer.orders.report', $order2->id));
        $response->assertSessionHas('success');

        $report = Report::where('order_id', $order2->id)->first();
        $this->assertNotNull($report);
        $this->assertNotNull($report->evidence_image);

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }

    public function test_admin_can_view_reports_index_and_detail()
    {
        $report = Report::create([
            'order_id' => $this->order->id,
            'reporter_role' => 'customer',
            'reporter_id' => $this->customer->id,
            'category' => 'barang_tidak_sesuai',
            'description' => 'Varian yang dibeli salah, pesan keju dapat coklat.',
            'status' => 'menunggu',
        ]);
        $this->order->update(['status' => 'bermasalah']);

        // Index page
        $resIndex = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Pengaduan & Komplain Pesanan');
        $resIndex->assertSee('#REP-' . $report->id);

        // Show detail page
        $resShow = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.show', $report->id));
        $resShow->assertStatus(200);
        $resShow->assertSee('Investigasi Pengaduan #REP-' . $report->id);
        $resShow->assertSee('Refund Penuh 100%');
        $resShow->assertSee('Tolak Komplain');
    }

    public function test_admin_can_resolve_complaint_with_full_refund()
    {
        $report = Report::create([
            'order_id' => $this->order->id,
            'reporter_role' => 'customer',
            'reporter_id' => $this->customer->id,
            'category' => 'barang_rusak',
            'description' => 'Barang pecah di perjalanan.',
            'status' => 'menunggu',
        ]);
        $this->order->update(['status' => 'bermasalah']);

        $customerWallet = $this->customer->wallet;
        $this->assertEquals(0, $customerWallet->fresh()->balance);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.reports.resolve', $report->id), [
                'action' => 'refund_full',
                'admin_decision' => 'Bukti foto valid, dilakukan pengembalian dana 100% ke customer.',
                'investigation_notes' => 'Kerusakan fisik terbukti jelas.',
            ]);

        $response->assertRedirect(route('admin.reports.show', $report->id));
        $response->assertSessionHas('success');

        // Pastikan saldo customer bertambah 50000
        $this->assertEquals(50000, $customerWallet->fresh()->balance);

        // Pastikan transaksi mutasi tercatat
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $customerWallet->id,
            'type' => 'kredit',
            'amount' => 50000,
            'source' => 'refund_dispute',
        ]);

        // Status report & order
        $freshReport = $report->fresh();
        $this->assertEquals('selesai', $freshReport->status);
        $this->assertEquals('refund_full', $freshReport->resolution_type);
        $this->assertEquals(50000, (float) $freshReport->refund_amount);
        $this->assertEquals($this->admin->id, $freshReport->decided_by);

        $this->assertEquals('dibatalkan', $this->order->fresh()->status);
    }

    public function test_admin_can_resolve_complaint_with_partial_refund()
    {
        $report = Report::create([
            'order_id' => $this->order->id,
            'reporter_role' => 'customer',
            'reporter_id' => $this->customer->id,
            'category' => 'barang_kurang',
            'description' => 'Hanya 1 box yang diantar dari total 2 box.',
            'status' => 'menunggu',
        ]);
        $this->order->update(['status' => 'bermasalah']);

        $customerWallet = $this->customer->wallet;
        $this->assertEquals(0, $customerWallet->fresh()->balance);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.reports.resolve', $report->id), [
                'action' => 'refund_partial',
                'refund_amount' => 20000,
                'admin_decision' => 'Pengembalian sebagian untuk 1 box yang tidak terbeli.',
            ]);

        $response->assertRedirect(route('admin.reports.show', $report->id));
        $response->assertSessionHas('success');

        // Saldo customer bertambah 20000
        $this->assertEquals(20000, $customerWallet->fresh()->balance);

        // Report status
        $freshReport = $report->fresh();
        $this->assertEquals('selesai', $freshReport->status);
        $this->assertEquals('refund_partial', $freshReport->resolution_type);
        $this->assertEquals(20000, (float) $freshReport->refund_amount);

        // Order selesai dan jastiper dibayar
        $this->assertEquals('selesai', $this->order->fresh()->status);
        $this->assertGreaterThan(0, $this->jastiper->wallet->fresh()->balance);
    }

    public function test_admin_can_reject_complaint_and_complete_order_to_jastiper()
    {
        $report = Report::create([
            'order_id' => $this->order->id,
            'reporter_role' => 'customer',
            'reporter_id' => $this->customer->id,
            'category' => 'barang_tidak_sesuai',
            'description' => 'Customer klaim salah padahal varian sudah benar sesuai foto chat.',
            'status' => 'menunggu',
        ]);
        $this->order->update(['status' => 'bermasalah']);

        $jastiperWallet = $this->jastiper->wallet;
        $this->assertEquals(0, $jastiperWallet->fresh()->balance);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.reports.resolve', $report->id), [
                'action' => 'reject_complaint',
                'admin_decision' => 'Setelah cross-check chat, jastiper sudah konfirmasi varian dan disetujui customer. Komplain ditolak.',
            ]);

        $response->assertRedirect(route('admin.reports.show', $report->id));
        $response->assertSessionHas('success');

        // Customer wallet tidak bertambah
        $this->assertEquals(0, $this->customer->wallet->fresh()->balance);

        // Report status ditolak
        $freshReport = $report->fresh();
        $this->assertEquals('selesai', $freshReport->status);
        $this->assertEquals('reject_complaint', $freshReport->resolution_type);
        $this->assertEquals(0, (float) $freshReport->refund_amount);

        // Order selesai dan komisi dipotong serta jastiper mendapatkan hak saldo
        $this->assertEquals('selesai', $this->order->fresh()->status);
        $this->assertGreaterThan(0, $jastiperWallet->fresh()->balance);
    }
}
