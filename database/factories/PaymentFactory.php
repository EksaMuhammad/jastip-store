<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model Payment — Tahap 1 (brief §Tahap 1).
 *
 * ⚠️ Riwayat bug (lihat BRIEF Progress Log Tahap 5 & Tahap 6): file ini pernah
 * ke-timpa jadi salinan persis file lain sebanyak DUA KALI di sesi-sesi
 * sebelumnya — pertama jadi salinan config/services.php (ditemukan & diperbaiki
 * di Tahap 5), lalu (ditemukan di sesi Tahap 6 ini) balik lagi jadi salinan
 * persis app/Http/Controllers/PaymentController.php (namespace & isinya class
 * PaymentController, bukan factory sama sekali). Ini BUKAN cuma "kurang bagus"
 * — kalau dibiarkan, `class PaymentController` di sini bentrok deklarasi dengan
 * `App\Http\Controllers\PaymentController` yang asli begitu Composer classmap
 * mengindeksnya (mis. lewat `composer dump-autoload -o` atau optimasi produksi),
 * dan menyebabkan fatal error "Cannot redeclare class" di SELURUH aplikasi,
 * bukan cuma di fitur pembayaran. Ditulis ulang di sini sesuai deskripsi yang
 * SUDAH BENAR di Progress Log Tahap 1 sejak awal (isinya belum pernah berubah,
 * cuma implementasi filenya yang berulang kali salah tertimpa).
 *
 * Project ini sebelumnya tidak punya pola factory sama sekali (Order/Customer/
 * Wilayah tidak ada factory-nya), jadi dependency minimal dibuat manual lewat
 * firstOrCreate()/create() di bawah, bukan lewat Model::factory() berantai.
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            // Diisi di configure()->afterMaking(): Order (juga Customer/Wilayah)
            // di project ini TIDAK pakai trait HasFactory, jadi Order::factory()
            // tidak bisa dipanggil di sini — dependency dibuat manual di bawah.
            'order_id' => null,
            'method' => 'transfer',
            'channel' => 'bank_transfer_va',
            'amount' => $this->faker->randomFloat(2, 20000, 500000),
            'status' => 'menunggu',
            'payment_deadline' => now()->addMinutes(15),
            'gateway_reference' => 'ORDER-' . $this->faker->unique()->numerify('######') . '-' . now()->format('YmdHis'),
            'va_number' => null,
            'qr_string' => null,
            'gateway_transaction_id' => null,
            'raw_response' => null,
            'raw_webhook_payload' => null,
            'proof_image' => null,
            'verified_at' => null,
            'verified_by_admin_id' => null,
        ];
    }

    /**
     * Order/Customer/Wilayah project ini tidak punya factory sendiri, jadi
     * dependency minimal dibuat manual di sini (bukan lewat Order::factory()
     * yang tidak ada) — konsisten dengan cara test lain (mis.
     * PaymentServiceTest, PaymentManualVerificationTest) bikin data manual.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Payment $payment) {
            if ($payment->order_id) {
                return;
            }

            $wilayah = Wilayah::firstOrCreate(
                ['name' => 'Wilayah Uji Coba'],
                ['default_radius_km' => 5, 'is_active' => true]
            );

            $customer = Customer::firstOrCreate(
                ['phone_number' => '089999999999'],
                ['name' => 'Customer Uji Coba']
            );

            $order = Order::create([
                'customer_id' => $customer->id,
                'wilayah_id' => $wilayah->id,
                'category' => 'beli-antar',
                'weight_category' => 'ringan',
                'description' => 'Order uji coba (factory)',
                'destination_address' => 'Jl. Uji Coba No. 1',
                'recipient_name' => $customer->name,
                'recipient_phone' => $customer->phone_number,
                'estimated_fare' => 50000,
                'agreed_fare' => 50000,
                'status' => 'menunggu_pembayaran',
            ]);

            $payment->order_id = $order->id;
        });
    }

    /**
     * State: payment sudah lunas (via gateway VA), sudah ada gateway_transaction_id.
     */
    public function lunas(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lunas',
            'gateway_transaction_id' => 'trx-' . $this->faker->unique()->numerify('###########'),
            'va_number' => '8808' . $this->faker->numerify('#######'),
            'verified_at' => now(),
        ]);
    }

    /**
     * State: dibayar via saldo wallet (tidak lewat gateway sama sekali).
     */
    public function wallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'wallet',
            'channel' => null,
            'status' => 'lunas',
            'va_number' => null,
            'qr_string' => null,
            'verified_at' => now(),
        ]);
    }

    /**
     * State: sudah lewat payment_deadline, otomatis dibatalkan scheduler.
     */
    public function kedaluwarsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'kedaluwarsa',
            'payment_deadline' => now()->subMinutes(30),
        ]);
    }
}