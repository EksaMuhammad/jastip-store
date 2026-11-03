<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Merchant extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'type',
        'address',
        'latitude',
        'longitude',
        'description',
        'image',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Dapatkan titik koordinat toko (dengan default Malang yang realistis jika belum diset).
     */
    public function getCoordinates(): array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ];
        }

        $offsetMap = [
            1 => ['lat' => -7.9483, 'lng' => 112.6138], // MT Haryono (McDonalds)
            2 => ['lat' => -7.9436, 'lng' => 112.6174], // Soekarno Hatta (Belikopi)
            3 => ['lat' => -7.9558, 'lng' => 112.6152], // Veteran (Indomaret)
        ];

        if (isset($offsetMap[$this->id])) {
            return $offsetMap[$this->id];
        }

        // Cek apakah nama atau alamat toko mengandung kata kunci wilayah
        $text = strtolower(($this->address ?? '') . ' ' . ($this->name ?? ''));
        if (str_contains($text, 'wonoayu')) {
            return ['lat' => -7.4345, 'lng' => 112.6178];
        }
        if (str_contains($text, 'sedengan') || str_contains($text, 'krian')) {
            return ['lat' => -7.4120, 'lng' => 112.5830];
        }
        if (str_contains($text, 'waru')) {
            return ['lat' => -7.3540, 'lng' => 112.7260];
        }
        if (str_contains($text, 'sidoarjo')) {
            return ['lat' => -7.4478, 'lng' => 112.7183];
        }
        if (str_contains($text, 'rungkut')) {
            return ['lat' => -7.3180, 'lng' => 112.7820];
        }
        if (str_contains($text, 'dinoyo')) {
            return ['lat' => -7.2842, 'lng' => 112.7459];
        }
        if (str_contains($text, 'surabaya')) {
            return ['lat' => -7.2575, 'lng' => 112.7521];
        }
        if (str_contains($text, 'gresik')) {
            return ['lat' => -7.1566, 'lng' => 112.6555];
        }
        if (str_contains($text, 'batu')) {
            return ['lat' => -7.8712, 'lng' => 112.5270];
        }

        $defaultLat = -7.9839;
        $defaultLng = 112.6214;
        $latOffset = (($this->id % 5) - 2) * 0.008;
        $lngOffset = ((($this->id * 3) % 5) - 2) * 0.008;

        return [
            'lat' => (float) ($defaultLat + $latOffset),
            'lng' => (float) ($defaultLng + $lngOffset),
        ];
    }
}
