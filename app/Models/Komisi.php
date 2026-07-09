<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Komisi extends Model
{
    use SoftDeletes;

    protected $table = 'komisi';

    protected $fillable = [
        'order_id',
        'gross_amount',
        'commission_percentage',
        'commission_amount',
        'net_amount',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
