<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'destination_address',
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
    ];

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
