<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Support\Facades\DB;

class RatingService
{
    /**
     * Simpan rating & review dari Customer untuk Jastiper pada sebuah order.
     *
     * Aturan bisnis:
     * - Order harus milik Customer yang bersangkutan.
     * - Order harus sudah berstatus 'selesai'.
     * - Setiap order hanya boleh dirating SATU KALI (dijaga di sini + unique
     *   constraint (order_id, deleted_at) di tabel ratings sebagai lapisan kedua).
     *
     * Setelah rating tersimpan, badge/trust level Jastiper terkait perlu
     * dihitung ulang (avg_rating berubah). Pemanggilan BadgeService::recalculate()
     * SENGAJA dibungkus try-catch supaya kegagalan di sisi badge (fitur
     * terpisah, mungkin belum ada / sedang dikerjakan paralel) tidak sampai
     * menggagalkan penyimpanan rating itu sendiri.
     *
     * @throws \RuntimeException kalau order bukan milik customer, belum selesai,
     *                           atau sudah pernah dirating sebelumnya.
     */
    public function submitRating(Order $order, Customer $customer, int $ratingValue, ?string $reviewText): Rating
    {
        if ((int) $order->customer_id !== (int) $customer->id) {
            throw new \RuntimeException('Order ini bukan milik Anda.');
        }

        if ($order->status !== 'selesai') {
            throw new \RuntimeException('Order belum selesai, belum bisa diberi rating.');
        }

        if (!$order->jastiper_id) {
            throw new \RuntimeException('Order ini tidak memiliki Jastiper untuk dirating.');
        }

        if ($ratingValue < 1 || $ratingValue > 5) {
            throw new \RuntimeException('Nilai rating harus antara 1 sampai 5.');
        }

        // Cek order sudah pernah dirating (relasi hasOne Order::rating()).
        $existing = Rating::where('order_id', $order->id)->first();
        if ($existing) {
            throw new \RuntimeException('Order ini sudah pernah Anda beri rating.');
        }

        $rating = DB::transaction(function () use ($order, $customer, $ratingValue, $reviewText) {
            return Rating::create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'jastiper_id' => $order->jastiper_id,
                'rating' => $ratingValue,
                'review_text' => $reviewText,
            ]);
        });

        // Trigger rekalkulasi badge/trust level Jastiper (Sprint 8 Bagian 2).
        // Dibungkus try-catch: BadgeService bisa saja belum ada kalau Bagian 2
        // belum dikerjakan di codebase ini — jangan sampai rating gagal tersimpan
        // hanya karena badge recalculation error.
        if (class_exists(\App\Services\BadgeService::class)) {
            try {
                app(\App\Services\BadgeService::class)->recalculate((int) $order->jastiper_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $rating;
    }
}