<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderAddon extends Model
{
    use SoftDeletes;

    protected $table = 'order_addons';

    protected $fillable = [
        'order_id',
        'description',
        'additional_fare',
        'payment_status',
    ];

    protected $casts = [
        'additional_fare' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
