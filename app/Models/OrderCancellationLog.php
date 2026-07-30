<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCancellationLog extends Model
{
    protected $table = 'order_cancellation_logs';

    protected $fillable = [
        'order_id',
        'cancelled_by_role',
        'cancelled_by_id',
        'stage',
        'reason',
        'total_refund',
        'jastiper_compensation',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
