<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * Hitung ulang metrik dan perbarui Badge Level untuk Jastiper.
     * 
     * @param int $jastiperId
     * @return Badge
     */
    public function recalculate(int $jastiperId): Badge
    {
        // 1. Hitung rata-rata rating
        $avgRating = (float) Rating::where('jastiper_id', $jastiperId)->avg('rating');
        if (!$avgRating) {
            $avgRating = 0.0;
        }

        // 2. Hitung rata-rata response time (menit) dari penawaran ke order
        $offers = Offer::join('orders', 'offers.order_id', '=', 'orders.id')
            ->where('offers.jastiper_id', $jastiperId)
            ->select('offers.created_at as offer_created_at', 'orders.created_at as order_created_at')
            ->get();

        $totalResponseMinutes = 0;
        $responseCount = 0;
        foreach ($offers as $offer) {
            if ($offer->offer_created_at && $offer->order_created_at) {
                // Selisih dalam menit
                $offerTime = \Carbon\Carbon::parse($offer->offer_created_at);
                $orderTime = \Carbon\Carbon::parse($offer->order_created_at);
                $diffInMinutes = $offerTime->diffInMinutes($orderTime);
                $totalResponseMinutes += $diffInMinutes;
                $responseCount++;
            }
        }
        $avgResponseTime = $responseCount > 0 ? (int) round($totalResponseMinutes / $responseCount) : 0;

        // 3. Hitung total order selesai
        $totalCompleted = Order::where('jastiper_id', $jastiperId)
            ->where('status', 'selesai')
            ->count();

        // 4. Tentukan Badge Level berdasarkan threshold config
        $badgeLevel = $this->determineBadgeLevel($totalCompleted, $avgRating, $avgResponseTime);

        // 5. Simpan/Update Badge
        return Badge::updateOrCreate(
            ['jastiper_id' => $jastiperId],
            [
                'avg_rating' => $avgRating,
                'avg_response_time_minutes' => $avgResponseTime,
                'total_completed_orders' => $totalCompleted,
                'badge_level' => $badgeLevel,
            ]
        );
    }

    /**
     * Menentukan level badge berdasarkan syarat.
     */
    private function determineBadgeLevel(int $completedOrders, float $avgRating, int $avgResponseTimeMinutes): string
    {
        $thresholds = config('jastip.badge.thresholds');

        // Check Platinum
        if (
            $completedOrders >= $thresholds['platinum']['total_completed_orders'] &&
            $avgRating >= $thresholds['platinum']['avg_rating'] &&
            $avgResponseTimeMinutes <= $thresholds['platinum']['avg_response_time_minutes']
        ) {
            return 'platinum';
        }

        // Check Gold
        if (
            $completedOrders >= $thresholds['gold']['total_completed_orders'] &&
            $avgRating >= $thresholds['gold']['avg_rating'] &&
            $avgResponseTimeMinutes <= $thresholds['gold']['avg_response_time_minutes']
        ) {
            return 'gold';
        }

        // Check Silver
        if (
            $completedOrders >= $thresholds['silver']['total_completed_orders'] &&
            $avgRating >= $thresholds['silver']['avg_rating']
        ) {
            return 'silver';
        }

        return 'bronze';
    }
}
