<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JastiperVerification extends Model
{
    use SoftDeletes;

    protected $table = 'jastiper_verifications';

    protected $fillable = [
        'jastiper_id',
        'ktp_image',
        'selfie_image',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function jastiper()
    {
        return $this->belongsTo(Jastiper::class, 'jastiper_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
