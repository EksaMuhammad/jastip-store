<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPriceAdjustment extends Model
{
    use SoftDeletes;

    protected $table = 'order_price_adjustments';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'jastiper_id',
        'item_name',
        'original_price',
        'proposed_price',
        'difference',
        'reason',
        'proof_image',
        'status',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'proposed_price' => 'decimal:2',
        'difference' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function jastiper(): BelongsTo
    {
        return $this->belongsTo(Jastiper::class, 'jastiper_id');
    }
}
