<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CancellationPolicy extends Model
{
    use SoftDeletes;

    protected $table = 'cancellation_policies';

    protected $fillable = [
        'stage',
        'refund_percentage',
        'jastiper_compensation_percentage',
        'description',
    ];

    protected $casts = [
        'refund_percentage' => 'decimal:2',
        'jastiper_compensation_percentage' => 'decimal:2',
    ];
}
