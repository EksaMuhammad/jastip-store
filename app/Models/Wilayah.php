<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wilayah extends Model
{
    use SoftDeletes;

    protected $table = 'wilayah';

    protected $fillable = [
        'name',
        'default_radius_km',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_radius_km' => 'decimal:2',
    ];

    /**
     * Titik pusat koordinat (Center Latitude & Longitude) bawaan tiap wilayah operasional
     */
    public static array $knownCenters = [
        11 => ['name' => 'Malang Kota', 'lat' => -7.9839, 'lng' => 112.6214],
        15 => ['name' => 'Surabaya Kota', 'lat' => -7.2575, 'lng' => 112.7521],
        16 => ['name' => 'Sidoarjo', 'lat' => -7.4478, 'lng' => 112.7183],
        17 => ['name' => 'Gresik', 'lat' => -7.1566, 'lng' => 112.6555],
        18 => ['name' => 'Jawa Timur (Antar Kota)', 'lat' => -7.5360, 'lng' => 112.2384],
        19 => ['name' => 'Nasional / Seluruh Indonesia', 'lat' => -6.2088, 'lng' => 106.8456],
    ];

    /**
     * Accessor untuk mendapatkan koordinat pusat wilayah
     */
    public function getCenterCoordinatesAttribute(): array
    {
        $byName = [
            'Malang Kota' => ['lat' => -7.9839, 'lng' => 112.6214],
            'Surabaya Kota' => ['lat' => -7.2575, 'lng' => 112.7521],
            'Sidoarjo' => ['lat' => -7.4478, 'lng' => 112.7183],
            'Gresik' => ['lat' => -7.1566, 'lng' => 112.6555],
            'Jawa Timur (Antar Kota)' => ['lat' => -7.5360, 'lng' => 112.2384],
            'Nasional / Seluruh Indonesia' => ['lat' => -6.2088, 'lng' => 106.8456],
        ];

        return $byName[$this->name] ?? self::$knownCenters[$this->id] ?? ['lat' => -7.9839, 'lng' => 112.6214];
    }

    /**
     * Hitung jarak Haversine antara dua koordinat (dalam satuan KM)
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Cari Wilayah operasional terdekat dari koordinat Latitude & Longitude
     */
    public static function findNearest(float $lat, float $lng, ?float $maxKm = null): ?self
    {
        $activeWilayahs = self::where('is_active', true)
            ->whereNotIn('id', [13, 14])
            ->get();

        if ($activeWilayahs->isEmpty()) {
            return null;
        }

        $closest = null;
        $minDistance = INF;

        foreach ($activeWilayahs as $wilayah) {
            // Abaikan kategori regional / nasional dari perhitungan titik terdekat
            if (in_array($wilayah->name, ['Jawa Timur (Antar Kota)', 'Nasional / Seluruh Indonesia'])) {
                continue;
            }

            $coords = $wilayah->center_coordinates;
            $dist = self::calculateDistance($lat, $lng, $coords['lat'], $coords['lng']);

            if ($dist < $minDistance) {
                $minDistance = $dist;
                $closest = $wilayah;
            }
        }

        if ($maxKm !== null && $minDistance > $maxKm) {
            $jatim = $activeWilayahs->firstWhere('name', 'Jawa Timur (Antar Kota)');
            return $jatim ?: $closest;
        }

        return $closest;
    }

    public function jastipers()
    {
        return $this->hasMany(Jastiper::class, 'wilayah_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'wilayah_id');
    }
}
