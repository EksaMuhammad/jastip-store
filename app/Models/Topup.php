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
        'snap_token',
        'method',
        'channel',
        'va_number',
        'qr_string',
        'raw_response',
        'raw_webhook_payload',
        'payment_deadline',
        'proof_image',
        'rejection_note',
        'approved_at',
        'approved_by_admin_id',
        'verified_at',
        'verified_by_admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_response' => 'array',
        'raw_webhook_payload' => 'array',
        'payment_deadline' => 'datetime',
        'approved_at' => 'datetime',
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

    public function approvedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    /**
     * Cek apakah topup diselesaikan via Midtrans Callback (otomatis tanpa ACC 2 admin manual).
     */
    public function isSettledByGateway(): bool
    {
        return $this->status === 'berhasil' && is_null($this->verified_by_admin_id);
    }

    /**
     * Keterangan siapa yang menyelesaikan verifikasi Tahap 2.
     */
    public function getStage2VerifierLabel(): string
    {
        if ($this->verifiedByAdmin) {
            return $this->verifiedByAdmin->name . ' (Admin Manual)';
        }

        if ($this->isSettledByGateway()) {
            return 'Midtrans Callback (Otomatis)';
        }

        return 'Belum diverifikasi';
    }
}