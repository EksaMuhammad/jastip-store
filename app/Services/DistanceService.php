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
     * Hitung rute berantai multi-stop & multi-dropoff dengan algoritma Smart Freshness Priority:
     * Toko 1 ➔ Toko 2 ➔ ... ➔ Alamat Penerima 1 ➔ ... ➔ Alamat Penerima Akhir.
     *
     * @param array $stops Array of stops dengan tipe 'pickup' / 'dropoff', lat, lng, name, address, dll.
     * @param float $destLat
     * @param float $destLng
     * @param array $destInfo
     * @return array
     */
    public static function calculateMultiStoreRoute(array $stops, float $destLat, float $destLng, array $destInfo = []): array
    {
        if (empty($stops)) {
            return [
                'legs' => [],
                'ordered_stops' => [],
                'total_distance_km' => 0.0,
                'extra_stops_count' => 0,
                'extra_stops_fare' => 0,
                'extra_dropoffs_count' => 0,
                'extra_dropoffs_fare' => 0,
                'total_extra_fare' => 0,
                'has_multi_dropoff' => false,
                'has_freshness_optimization' => false,
                'estimated_fare' => 10000,
            ];
        }

        // 1. Normalisasi setiap stop
        $normalizedStops = [];
        foreach ($stops as $idx => $st) {
            $isDropoff = ($st['action'] ?? null) === 'dropoff';
            $isPerishable = !empty($st['is_perishable'])
                || (isset($st['type']) && $st['type'] === 'merchant' && ($st['merchant_type'] ?? '') === 'food')
                || (isset($st['category']) && in_array($st['category'], ['beli-antar', 'food']));

            $normalizedStops[] = [
                'id' => $st['id'] ?? "stop_{$idx}",
                'name' => $st['name'] ?? "Titik Singgah " . ($idx + 1),
                'address' => $st['address'] ?? 'Alamat Lokasi',
                'lat' => (float) ($st['lat'] ?? $destLat),
                'lng' => (float) ($st['lng'] ?? $destLng),
                'action' => $isDropoff ? 'dropoff' : 'pickup',
                'type' => $st['type'] ?? ($isDropoff ? 'destination' : 'merchant'),
                'is_perishable' => $isPerishable,
                'recipient_name' => $st['recipient_name'] ?? null,
                'recipient_phone' => $st['recipient_phone'] ?? null,
                'notes' => $st['notes'] ?? null,
                'badge' => $st['badge'] ?? ($isDropoff ? '📍 Titik Antar / Penerima' : '🛒 Titik Jemput / Belanja'),
            ];
        }

        // 2. Evaluasi Smart Freshness Optimization (Urutan Cerdas: Dokumen/Non-makanan diselesaikan dulu, Makanan/Es Kopi dibeli terakhir)
        $hasPerishable = false;
        $hasNonPerishableErrand = false;

        foreach ($normalizedStops as $nst) {
            if ($nst['is_perishable']) {
                $hasPerishable = true;
            } elseif ($nst['action'] === 'pickup' || $nst['action'] === 'dropoff') {
                $hasNonPerishableErrand = true;
            }
        }

        $hasFreshnessOptimization = ($hasPerishable && $hasNonPerishableErrand);
        $orderedStops = [];

        if ($hasFreshnessOptimization) {
            // Pisahkan urutan tugas agar es kopi / makanan tidak basi/mencair saat kurir mengantar barang lain:
            // Urutan:
            // A. Non-perishable pickups (Jemput dokumen/paket/laundry)
            $nonPerishablePickups = array_filter($normalizedStops, fn($s) => !$s['is_perishable'] && $s['action'] === 'pickup');
            // B. Non-perishable dropoffs (Antar dokumen/paket ke penerima kantor)
            $nonPerishableDropoffs = array_filter($normalizedStops, fn($s) => !$s['is_perishable'] && $s['action'] === 'dropoff');
            // C. Perishable pickups (Beli es kopi / makanan siap saji)
            $perishablePickups = array_filter($normalizedStops, fn($s) => $s['is_perishable'] && $s['action'] === 'pickup');
            // D. Perishable dropoffs (Antar makanan ke customer)
            $perishableDropoffs = array_filter($normalizedStops, fn($s) => $s['is_perishable'] && $s['action'] === 'dropoff');

            $orderedStops = array_merge(
                array_values($nonPerishablePickups),
                array_values($nonPerishableDropoffs),
                array_values($perishablePickups),
                array_values($perishableDropoffs)
            );
        } else {
            // Pertahankan urutan yang ditentukan customer / urutan input
            $orderedStops = array_values($normalizedStops);
        }

        // 3. Tambahkan Tujuan Akhir jika belum ada titik drop-off yang identik dengan tujuan utama
        $hasFinalDropoff = false;
        if (!empty($orderedStops)) {
            $last = end($orderedStops);
            $sameCoords = (abs($last['lat'] - $destLat) < 0.0005 && abs($last['lng'] - $destLng) < 0.0005);
            $sameAddress = (!empty($last['address']) && !empty($destInfo['address']) && strtolower(trim($last['address'])) === strtolower(trim($destInfo['address'])));
            if ($last['action'] === 'dropoff' && ($sameCoords || $sameAddress)) {
                $hasFinalDropoff = true;
            }
        }

        if (!$hasFinalDropoff) {
            $orderedStops[] = [
                'id' => 'final_destination',
                'name' => $destInfo['name'] ?? 'Alamat Tujuan Pengantaran',
                'address' => $destInfo['address'] ?? 'Lokasi Penerima Akhir',
                'lat' => $destLat,
                'lng' => $destLng,
                'action' => 'dropoff',
                'type' => 'destination',
                'is_perishable' => false,
                'recipient_name' => $destInfo['recipient_name'] ?? null,
                'recipient_phone' => $destInfo['recipient_phone'] ?? null,
                'notes' => 'Tujuan akhir penyerahan barang belanjaan',
                'badge' => '🏁 Tujuan Akhir',
            ];
        }

        // 4. Hitung rute jarak berantai antar seluruh titik (Stop 1 ➔ Stop 2 ➔ ... ➔ Stop N)
        $legs = [];
        $totalDistance = 0.0;
        $totalStopsCount = count($orderedStops);

        for ($i = 0; $i < $totalStopsCount - 1; $i++) {
            $from = $orderedStops[$i];
            $to = $orderedStops[$i + 1];

            $legDistance = self::haversine($from['lat'], $from['lng'], $to['lat'], $to['lng']);
            // Jarak minimal realistis di dalam kota adalah 0.5 KM (atau 0.8 KM untuk leg pengantaran akhir)
            $minLegDist = ($i === $totalStopsCount - 2) ? 0.8 : 0.5;
            $legDistance = max($minLegDist, $legDistance);
            $totalDistance += $legDistance;

            $toRecipient = !empty($to['recipient_name'])
                ? "{$to['recipient_name']}" . (!empty($to['recipient_phone']) ? " ({$to['recipient_phone']})" : "")
                : null;

            $legs[] = [
                'from_name' => $from['name'],
                'from_address' => $from['address'] ?? '',
                'from_action' => $from['action'],
                'from_type' => $from['type'] ?? ($from['action'] === 'dropoff' ? 'destination' : 'merchant'),
                'to_name' => $to['name'],
                'to_address' => $to['address'] ?? '',
                'to_action' => $to['action'],
                'to_type' => $to['type'] ?? ($to['action'] === 'dropoff' ? 'destination' : 'merchant'),
                'to_recipient' => $toRecipient,
                'distance_km' => round($legDistance, 2),
            ];
        }

        $totalDistance = round($totalDistance, 2);

        // 5. Kalkulasi Tarif Dinamis dari TariffService (termasuk biaya ekstra jemput & ekstra antar/dropoff)
        $tariff = app(TariffService::class);
        $allTariff = $tariff->getAll();

        $baseFare = (int) ($allTariff['min_fare'] ?? 10000);
        $baseKm = (float) ($allTariff['base_included_km'] ?? 2.0);

        // Hitung jumlah titik jemput dan titik antar
        $pickupsCount = count(array_filter($orderedStops, fn($s) => $s['action'] === 'pickup'));
        $dropoffsCount = count(array_filter($orderedStops, fn($s) => $s['action'] === 'dropoff'));

        $extraPickupsCount = max(0, $pickupsCount - 1);
        $extraDropoffsCount = max(0, $dropoffsCount - 1);

        $isMultiStore = ($extraPickupsCount > 0 || $extraDropoffsCount > 0);

        // Single order (1 toko & 1 tujuan) gunakan tarif per KM reguler (rate_per_km, e.g. Rp 2.000/KM).
        // Multi-toko / multi-dropoff gunakan tarif multi store per KM (multi_store_km_rate, e.g. Rp 3.000/KM).
        $extraKmRate = $isMultiStore
            ? (int) ($allTariff['multi_store_km_rate'] ?? 3000)
            : (int) ($allTariff['rate_per_km'] ?? 2000);

        $extraStoreRate = (int) ($allTariff['multi_store_extra_store_fare'] ?? 5000);
        $extraDropoffRate = (int) ($allTariff['extra_stop_fare'] ?? 6000);

        $extraKmDistance = max(0.0, $totalDistance - $baseKm);
        $kmFare = (int) ceil($extraKmDistance * $extraKmRate);

        $extraPickupsFare = (int) ($extraPickupsCount * $extraStoreRate);
        $extraDropoffsFare = (int) ($extraDropoffsCount * $extraDropoffRate);

        $totalFare = (int) ($baseFare + $kmFare + $extraPickupsFare + $extraDropoffsFare);
        $totalFare = (int) (ceil($totalFare / 500) * 500);

        return [
            'legs' => $legs,
            'ordered_stops' => $orderedStops,
            'total_distance_km' => $totalDistance,
            'base_fare' => $baseFare,
            'base_km' => $baseKm,
            'extra_km_distance' => round($extraKmDistance, 2),
            'extra_km_rate' => $extraKmRate,
            'is_multi_store' => $isMultiStore,
            'km_fare' => $kmFare,
            'extra_store_rate' => $extraStoreRate,
            'extra_dropoff_rate' => $extraDropoffRate,
            'extra_stops_count' => $extraPickupsCount,
            'extra_stops_fare' => $extraPickupsFare,
            'extra_dropoffs_count' => $extraDropoffsCount,
            'extra_dropoffs_fare' => $extraDropoffsFare,
            'total_extra_fare' => $extraPickupsFare + $extraDropoffsFare,
            'has_multi_dropoff' => $extraDropoffsCount > 0,
            'has_freshness_optimization' => $hasFreshnessOptimization,
            'estimated_fare' => $totalFare,
        ];
    }
}
