<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'method',
        'channel',
        'amount',
        'status',
        'payment_deadline',
        'gateway_reference',
        'gateway_transaction_id',
        'va_number',
        'qr_string',
        'raw_response',
        'raw_webhook_payload',
        'proof_image',
        'verified_at',
        'verified_by_admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_deadline' => 'datetime',
        'verified_at' => 'datetime',
        'raw_response' => 'array',
        'raw_webhook_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function verifiedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }
}