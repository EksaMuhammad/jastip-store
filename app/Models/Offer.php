<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    protected $table = 'offers';

    protected $fillable = [
        'order_id',
        'jastiper_id',
        'offered_price',
        'status',
    ];

    protected $casts = [
        'offered_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function jastiper()
    {
        return $this->belongsTo(Jastiper::class, 'jastiper_id');
    }
}
