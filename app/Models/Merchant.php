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
