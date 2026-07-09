<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'status',
        'payment_deadline',
        'gateway_reference',
        'proof_image',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_deadline' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
