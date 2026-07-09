<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badge extends Model
{
    use SoftDeletes;

    protected $table = 'badges';

    protected $fillable = [
        'jastiper_id',
        'avg_rating',
        'avg_response_time_minutes',
        'total_completed_orders',
        'badge_level',
    ];

    protected $casts = [
        'avg_rating' => 'decimal:2',
        'avg_response_time_minutes' => 'integer',
        'total_completed_orders' => 'integer',
    ];

    public function jastiper()
    {
        return $this->belongsTo(Jastiper::class, 'jastiper_id');
    }
}
