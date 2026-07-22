<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jastiper extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'jastiper';

    protected $fillable = [
        'phone_number',
        'name',
        'password',
        'otp_code',
        'otp_expires_at',
        'verification_status',
        'wilayah_id',
        'radius_km',
        'current_lat',
        'current_lng',
        'is_available',
        'work_status',
        'checkin_location',
        'checked_in_at',
    ];

    protected $hidden = [
        'password',
        'otp_code',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'is_available' => 'boolean',
        'radius_km' => 'decimal:2',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
        'checked_in_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!isset($model->work_status)) {
                $model->work_status = ($model->is_available ?? false) ? 'tersedia' : 'offline';
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('is_available') && !$model->isDirty('work_status')) {
                $model->work_status = $model->is_available ? 'tersedia' : 'offline';
            }
        });
    }

    /**
     * Konstanta status kerja jastiper (3-state).
     */
    public const STATUS_TERSEDIA = 'tersedia';
    public const STATUS_STANDBY = 'standby';
    public const STATUS_OFFLINE = 'offline';

    public static function workStatusOptions(): array
    {
        return [
            self::STATUS_TERSEDIA => 'Tersedia',
            self::STATUS_STANDBY => 'Standby',
            self::STATUS_OFFLINE => 'Offline',
        ];
    }

    /**
     * Apakah jastiper sedang aktif menerima order baru di feed.
     */
    public function isReceivingNewOrders(): bool
    {
        return $this->work_status === self::STATUS_TERSEDIA;
    }

    /**
     * Apakah jastiper sedang online (tersedia ATAU standby), lawan dari offline total.
     */
    public function isOnline(): bool
    {
        return $this->work_status !== self::STATUS_OFFLINE;
    }

    /**
     * Set work_status sekaligus sinkronkan kolom is_available lama (backward compatibility
     * untuk query lain yang masih pakai is_available, misal cek booking favorit customer).
     */
    public function setWorkStatus(string $status): void
    {
        $this->work_status = $status;
        $this->is_available = $status !== self::STATUS_OFFLINE;

        // Kalau diset ke offline, otomatis check-out dari lokasi check-in
        if ($status === self::STATUS_OFFLINE) {
            $this->checkin_location = null;
            $this->checked_in_at = null;
        }

        $this->save();
    }

    /**
     * Scope: filter jastiper yang sedang tersedia menerima order baru.
     */
    public function scopeReceivingOrders($query)
    {
        return $query->where('work_status', self::STATUS_TERSEDIA);
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id');
    }

    public function verifications()
    {
        return $this->hasMany(JastiperVerification::class, 'jastiper_id');
    }

    public function latestVerification()
    {
        return $this->hasOne(JastiperVerification::class, 'jastiper_id')->latestOfMany();
    }

    public function badge()
    {
        return $this->hasOne(Badge::class, 'jastiper_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(Customer::class, 'customer_favorites', 'jastiper_id', 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'jastiper_id');
    }

    /**
     * Order aktif (masih diproses, belum selesai/batal) yang sedang dipegang jastiper ini.
     * Dipakai untuk cek berapa banyak order yang sedang berjalan bersamaan (multi-order).
     */
    public function activeOrders()
    {
        return $this->hasMany(Order::class, 'jastiper_id')
            ->whereNotIn('status', ['selesai', 'dibatalkan']);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'jastiper_id');
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner', 'owner_role', 'owner_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'jastiper_id');
    }

    public function chats()
    {
        return $this->morphMany(Chat::class, 'sender', 'sender_role', 'sender_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reporter', 'reporter_role', 'reporter_id');
    }

    public function cancelledOrders()
    {
        return $this->morphMany(Order::class, 'cancelledBy', 'cancelled_by_role', 'cancelled_by_id');
    }
}