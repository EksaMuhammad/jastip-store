<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model
{
    protected $fillable = [
        'wallet_id',
        'amount',
        'status',
        'gateway_reference',
        'gateway_transaction_id',
        'method',
        'channel',
        'va_number',
        'qr_string',
        'raw_response',
        'raw_webhook_payload',
        'payment_deadline',
        'proof_image',
        'verified_at',
        'verified_by_admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_response' => 'array',
        'raw_webhook_payload' => 'array',
        'payment_deadline' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function verifiedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }
}