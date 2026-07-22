<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'phone_number',
        'name',
        'password',
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'otp_code',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner', 'owner_role', 'owner_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'customer_id');
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

    public function favorites()
    {
        return $this->belongsToMany(Jastiper::class, 'customer_favorites', 'customer_id', 'jastiper_id');
    }
}
