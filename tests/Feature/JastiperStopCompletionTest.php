<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Order;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JastiperStopCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected Jastiper $jastiper;
    protected Customer $customer;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $now = now();
        $wilayah = Wilayah::firstOrCreate(
            ['name' => 'Sidoarjo & Malang'],
            ['default_radius_km' => 50.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
        );

        $this->jastiper = Jastiper::create([
            'name' => 'Siti Jastiper',
            'phone_number' => '081234567891',
            'password' => bcrypt('password123'),
            'verification_status' => 'approved',
            'work_status' => 'tersedia',
            'is_available' => true,
            'radius_km' => 15.0,
            'wilayah_id' => $wilayah->id,
        ]);

        $this->customer = Customer::create([
            'phone_number' => '081299998888',
            'name' => 'acis',
            'password' => bcrypt('password123'),
        ]);

        $this->order = Order::create([
            'customer_id' => $this->customer->id,
            'jastiper_id' => $this->jastiper->id,
            'wilayah_id' => $wilayah->id,
            'category' => 'multi-stop',
            'weight_category' => 'ringan',
            'status' => 'diproses',
            'description' => "McDonald's & Mie Gacoan - Sidoarjo Pahlawan",
            'estimated_fare' => 45000,
            'destination_address' => 'Suko, Sidoarjo',
            'destination_lat' => -7.4500,
            'destination_lng' => 112.7100,
            'recipient_name' => 'acis',
            'recipient_phone' => '081299998888',
            'multi_stops' => [
                [
                    'stop_number' => 1,
                    'name' => "McDonald's",
                    'status' => 'completed',
                    'photo' => 'order_stops/fake1.jpg',
                    'completed_at' => now()->toIso8601String(),
                ],
                [
                    'stop_number' => 2,
                    'name' => 'Mie Gacoan - Sidoarjo Pahlawan',
                    'status' => null,
                    'photo' => null,
                ]
            ]
        ]);
    }

    public function test_jastiper_can_complete_stop_with_json_ajax_request()
    {
        $this->actingAs($this->jastiper, 'jastiper');

        $fakePhoto = UploadedFile::fake()->image('gacoan_proof.jpg', 1200, 900)->size(1500); // 1.5MB

        $response = $this->postJson(route('jastiper.orders.stop.complete', [$this->order->id, 2]), [
            'photo' => $fakePhoto,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->order->refresh();

        // Stop 2 must be marked completed
        $this->assertEquals('completed', $this->order->multi_stops[1]['status']);
        $this->assertNotEmpty($this->order->multi_stops[1]['photo']);

        // Since both stops are completed, order status becomes barang_diambil and pickup_photo set
        $this->assertEquals('barang_diambil', $this->order->status);
        $this->assertNotNull($this->order->pickup_photo);
        $this->assertNotNull($this->order->pickup_time);
    }

    public function test_jastiper_complete_stop_fails_gracefully_with_validation_errors()
    {
        $this->actingAs($this->jastiper, 'jastiper');

        // Request without photo
        $response = $this->postJson(route('jastiper.orders.stop.complete', [$this->order->id, 2]), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photo']);
    }

    public function test_jastiper_update_order_status_supports_json_and_large_camera_photo()
    {
        $this->actingAs($this->jastiper, 'jastiper');

        // Simulated 15MB photo (larger than 10MB old limit)
        $largePhoto = UploadedFile::fake()->image('camera_high_res.jpg', 3000, 2000)->size(15360); // 15MB

        $response = $this->postJson(route('jastiper.orders.update-status', $this->order->id), [
            'status' => 'barang_diambil',
            'photo' => $largePhoto,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->order->refresh();
        $this->assertEquals('barang_diambil', $this->order->status);
        $this->assertNotNull($this->order->pickup_photo);
    }
}
