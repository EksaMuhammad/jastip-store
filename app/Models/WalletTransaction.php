<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'source',
        'reference_order_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'reference_order_id');
    }
}
