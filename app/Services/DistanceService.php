<?php

namespace App\Services;

class DistanceService
{
    /**
     * Hitung jarak garis lurus (great-circle distance) antara 2 koordinat GPS menggunakan formula Haversine.
     * Mengembalikan jarak dalam satuan Kilometer (KM).
     */
    public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if (abs($lat1 - $lat2) < 0.00001 && abs($lon1 - $lon2) < 0.00001) {
            return 0.0;
        }

        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }

    /**
     * Hitung rute berantai multi-toko hingga ke alamat tujuan customer:
     * Toko 1 ➔ Toko 2 ➔ ... ➔ Toko N ➔ Alamat Tujuan.
     *
     * @param array $merchantLocations Array of ['id' => .., 'name' => .., 'lat' => .., 'lng' => .., 'address' => ..]
     * @param float $destLat
     * @param float $destLng
     * @return array
     */
    public static function calculateMultiStoreRoute(array $merchantLocations, float $destLat, float $destLng): array
    {
        if (empty($merchantLocations)) {
            return [
                'legs' => [],
                'total_distance_km' => 0.0,
                'extra_stops_count' => 0,
                'extra_stops_fare' => 0,
                'estimated_fare' => 10000,
            ];
        }

        $legs = [];
        $totalDistance = 0.0;
        $count = count($merchantLocations);

        // 1. Hitung jarak antar toko berturut-turut: Toko 1 -> Toko 2 -> Toko 3...
        for ($i = 0; $i < $count - 1; $i++) {
            $current = $merchantLocations[$i];
            $next = $merchantLocations[$i + 1];

            $legDistance = self::haversine(
                (float) $current['lat'],
                (float) $current['lng'],
                (float) $next['lat'],
                (float) $next['lng']
            );

            // Minimal jarak antar toko terpisah di kota adalah 0.5 KM
            $legDistance = max(0.5, $legDistance);
            $totalDistance += $legDistance;

            $legs[] = [
                'from_type' => 'merchant',
                'from_name' => $current['name'],
                'from_address' => $current['address'] ?? '',
                'to_type' => 'merchant',
                'to_name' => $next['name'],
                'to_address' => $next['address'] ?? '',
                'distance_km' => round($legDistance, 2),
            ];
        }

        // 2. Hitung jarak dari toko terakhir ke alamat tujuan customer: Toko N -> Tujuan
        $lastMerchant = $merchantLocations[$count - 1];
        $finalLegDistance = self::haversine(
            (float) $lastMerchant['lat'],
            (float) $lastMerchant['lng'],
            $destLat,
            $destLng
        );

        $finalLegDistance = max(0.8, $finalLegDistance);
        $totalDistance += $finalLegDistance;

        $legs[] = [
            'from_type' => 'merchant',
            'from_name' => $lastMerchant['name'],
            'from_address' => $lastMerchant['address'] ?? '',
            'to_type' => 'destination',
            'to_name' => 'Alamat Tujuan Pengiriman',
            'to_address' => 'Lokasi Penerima',
            'distance_km' => round($finalLegDistance, 2),
        ];

        $totalDistance = round($totalDistance, 2);

        // 3. Kalkulasi Tarif / Ongkir Jastip:
        // - Tarif dasar: Rp 10.000 untuk 2 KM pertama
        // - Tambahan per KM berikutnya: Rp 3.000 / KM
        // - Tambahan toko ekstra: Rp 5.000 per toko ekstra (karena jastiper harus belanja di beberapa toko terpisah)
        $baseFare = 10000;
        $extraKmDistance = max(0.0, $totalDistance - 2.0);
        $kmFare = (int) ceil($extraKmDistance * 3000);
        $extraStopsCount = max(0, $count - 1);
        $extraStopsFare = $extraStopsCount * 5000;

        $totalFare = (int) ($baseFare + $kmFare + $extraStopsFare);
        // Bulatkan ke kelipatan 500 terdekat
        $totalFare = (int) (ceil($totalFare / 500) * 500);

        return [
            'legs' => $legs,
            'total_distance_km' => $totalDistance,
            'extra_stops_count' => $extraStopsCount,
            'extra_stops_fare' => $extraStopsFare,
            'estimated_fare' => $totalFare,
        ];
    }
}
