<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'jastiper_id',
        'wilayah_id',
        'category',
        'weight_category',
        'description',
        'reference_photo',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'recipient_name',
        'recipient_phone',
        'estimated_fare',
        'agreed_fare',
        'status',
        'cancelled_by_role',
        'cancelled_by_id',
        'cancellation_reason',
    ];

    protected $casts = [
        'estimated_fare' => 'decimal:2',
        'agreed_fare' => 'decimal:2',
        'origin_lat' => 'decimal:8',
        'origin_lng' => 'decimal:8',
        'destination_lat' => 'decimal:8',
        'destination_lng' => 'decimal:8',
    ];

    /**
     * Scope: filter order yang titik asalnya (origin_lat/origin_lng) berada dalam
     * radius tertentu (KM) dari sebuah koordinat pusat, menggunakan formula Haversine.
     * Order yang belum punya koordinat (origin_lat/origin_lng null) otomatis dikecualikan.
     *
     * Juga menambahkan kolom virtual "distance_km" pada hasil query yang bisa dipakai
     * untuk sorting/ditampilkan di UI ("2.3 KM dari lokasi Anda").
     */
    public function scopeNearby($query, float $centerLat, float $centerLng, float $radiusKm)
    {
        // Formula Haversine dalam raw SQL (kompatibel MySQL 8.0)
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(origin_lat)) * cos(radians(origin_lng) - radians(?)) + sin(radians(?)) * sin(radians(origin_lat))))";

        $query->whereNotNull('origin_lat')
            ->whereNotNull('origin_lng');

        if (DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            // SQLite does not support virtual columns in HAVING without GROUP BY, so we put the formula in WHERE
            return $query
                ->selectRaw("orders.*, {$haversine} AS distance_km", [$centerLat, $centerLng, $centerLat])
                ->whereRaw("{$haversine} <= ?", [$centerLat, $centerLng, $centerLat, $radiusKm])
                ->orderBy('distance_km', 'asc');
        }

        return $query
            ->selectRaw("orders.*, {$haversine} AS distance_km", [$centerLat, $centerLng, $centerLat])
            ->havingRaw("distance_km <= ?", [$radiusKm])
            ->orderBy('distance_km', 'asc');
    }

    /**
     * Scope: filter berdasarkan kategori, hanya diterapkan kalau $category tidak kosong/null.
     * Memudahkan pemanggilan: Order::query()->categoryIs($request->category)->get();
     */
    public function scopeCategoryIs($query, ?string $category)
    {
        if (empty($category) || $category === 'semua') {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function jastiper()
    {
        return $this->belongsTo(Jastiper::class, 'jastiper_id');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id');
    }

    public function addons()
    {
        return $this->hasMany(OrderAddon::class, 'order_id');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function rating()
    {
        return $this->hasOne(Rating::class, 'order_id');
    }

    public function komisi()
    {
        return $this->hasOne(Komisi::class, 'order_id');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'order_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'order_id');
    }

    public function cancelledBy()
    {
        return $this->morphTo('cancelledBy', 'cancelled_by_role', 'cancelled_by_id');
    }
}