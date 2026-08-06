<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pengajuan tarik saldo (withdraw) oleh Jastiper.
 * Brief Sprint 8 Bagian 3 §2.1.
 */
class WithdrawRequest extends Model
{
    use SoftDeletes;

    protected $table = 'withdraw_requests';

    protected $fillable = [
        'jastiper_id',
        'wallet_id',
        'amount',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'status',
        'admin_note',
        'processed_by_admin_id',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function jastiper()
    {
        return $this->belongsTo(Jastiper::class, 'jastiper_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function processedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }
}