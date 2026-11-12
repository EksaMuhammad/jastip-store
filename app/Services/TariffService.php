<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class TariffService
{
    public const CACHE_KEY = 'jastip_tariff_settings';

    public const DEFAULTS = [
        // Jarak & Pengantaran
        'rate_per_km' => 5000,
        'min_fare' => 10000,
        'extra_stop_fare' => 6000,
        'base_included_km' => 2.0,
        'multi_store_km_rate' => 3000,
        'multi_store_extra_store_fare' => 5000,

        // Kategori Layanan
        'category_beli_antar' => 5000,
        'category_ambil_antar' => 3000,
        'category_toko_kirim' => 2000,
        'category_dokumen' => 0,
        'category_multi_stop' => 15000,
        'category_kirim_pihak_ketiga' => 7000,

        // Kategori Berat
        'weight_ringan' => 0,
        'weight_sedang' => 10000,
        'weight_berat' => 25000,

        // Platform & Transaksi
        'platform_commission_percent' => 10,
        'merchant_markup_percent' => 10,
        'payment_deadline_minutes' => 60,
        'min_topup_amount' => 10000,

        // Proteksi Asuransi Barang
        'insurance_enabled' => 1,
        'insurance_min_order_threshold' => 200000,
        'insurance_fee' => 2000,
    ];

    /**
     * Dapatkan semua pengaturan tarif dan ketentuan jastip.
     */
    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $settings = [];
            foreach (self::DEFAULTS as $key => $default) {
                $val = Setting::get($key, $default);
                $settings[$key] = is_numeric($val) ? (float) $val : $val;
            }
            return $settings;
        });
    }

    /**
     * Ambil 1 nilai tarif tertentu.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();
        return $all[$key] ?? ($default ?? (self::DEFAULTS[$key] ?? null));
    }

    /**
     * Simpan pembaruan tarif dari form admin.
     */
    public function updateAll(array $data): void
    {
        foreach (self::DEFAULTS as $key => $default) {
            if (array_key_exists($key, $data)) {
                $val = (float) $data[$key];
                $group = $this->resolveGroup($key);
                Setting::set($key, $val, $group);
            }
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Reset semua tarif kembali ke nilai default pabrikan.
     */
    public function resetToDefaults(): void
    {
        foreach (self::DEFAULTS as $key => $default) {
            $group = $this->resolveGroup($key);
            Setting::set($key, $default, $group);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Hitung ongkir estimasi pesanan reguler / custom jastip.
     */
    public function calculateFare(float $distanceKm, string $category, string $weightCategory, int $stopsCount = 2): float
    {
        $all = $this->getAll();

        $ratePerKm = (float) ($all['rate_per_km'] ?? 5000);
        $minFare = (float) ($all['min_fare'] ?? 10000);

        $catKey = 'category_' . str_replace('-', '_', $category);
        $categoryAddition = (float) ($all[$catKey] ?? 0);

        $weightKey = 'weight_' . $weightCategory;
        $weightAddition = (float) ($all[$weightKey] ?? 0);

        $extraStopFare = (float) ($all['extra_stop_fare'] ?? 6000);
        $extraStops = max(0, $stopsCount - 2);
        $extraStopsFare = ($category === 'multi-stop') ? ($extraStops * $extraStopFare) : 0.0;

        $fare = ($distanceKm * $ratePerKm) + $categoryAddition + $weightAddition + $extraStopsFare;

        $finalFare = max($minFare, $fare);
        return (float) (ceil($finalFare / 500) * 500);
    }

    /**
     * Hitung ongkir pesanan belanja berantai multi-merchant (multi-stop catalog).
     */
    public function calculateMultiStoreFare(float $totalDistanceKm, int $storesCount): array
    {
        $all = $this->getAll();

        $baseFare = (int) ($all['min_fare'] ?? 10000);
        $baseKm = (float) ($all['base_included_km'] ?? 2.0);
        $isMultiStore = ($storesCount > 1);
        $extraKmRate = $isMultiStore
            ? (int) ($all['multi_store_km_rate'] ?? 3000)
            : (int) ($all['rate_per_km'] ?? 2000);
        $extraStoreRate = (int) ($all['multi_store_extra_store_fare'] ?? 5000);

        $extraKmDistance = max(0.0, $totalDistanceKm - $baseKm);
        $kmFare = (int) ceil($extraKmDistance * $extraKmRate);

        $extraStopsCount = max(0, $storesCount - 1);
        $extraStopsFare = (int) ($extraStopsCount * $extraStoreRate);

        $totalFare = (int) ($baseFare + $kmFare + $extraStopsFare);
        $totalFare = (int) (ceil($totalFare / 500) * 500);

        return [
            'base_fare' => $baseFare,
            'km_fare' => $kmFare,
            'extra_km_rate' => $extraKmRate,
            'is_multi_store' => $isMultiStore,
            'extra_stops_count' => $extraStopsCount,
            'extra_stops_fare' => $extraStopsFare,
            'estimated_fare' => $totalFare,
        ];
    }

    public function getMerchantMarkupPercent(): float
    {
        return (float) $this->get('merchant_markup_percent', 10);
    }

    private function resolveGroup(string $key): string
    {
        if (str_starts_with($key, 'category_')) {
            return 'layanan';
        }
        if (str_starts_with($key, 'weight_')) {
            return 'berat';
        }
        if (in_array($key, ['platform_commission_percent', 'merchant_markup_percent', 'payment_deadline_minutes', 'min_topup_amount'])) {
            return 'platform';
        }
        return 'tarif';
    }
}
