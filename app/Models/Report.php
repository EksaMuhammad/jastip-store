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
        'refund_amount',
        'status',
        'resolution_type',
        'admin_decision',
        'investigation_notes',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
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
