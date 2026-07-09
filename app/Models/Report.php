<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $table = 'reports';

    protected $fillable = [
        'order_id',
        'reporter_role',
        'reporter_id',
        'category',
        'description',
        'evidence_image',
        'status',
        'admin_decision',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function reporter()
    {
        return $this->morphTo('reporter', 'reporter_role', 'reporter_id');
    }

    public function decider()
    {
        return $this->belongsTo(Admin::class, 'decided_by');
    }
}
