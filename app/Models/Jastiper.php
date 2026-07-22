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
