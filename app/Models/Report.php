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
        'cs_claim_status',
        'is_insurance_claim',
        'insurance_payout',
        'jastiper_charged_amount',
        'assigned_cs_id',
        'resolution_type',
        'admin_decision',
        'investigation_notes',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'is_insurance_claim' => 'boolean',
        'insurance_payout' => 'decimal:2',
        'jastiper_charged_amount' => 'decimal:2',
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

    public function assignedCs()
    {
        return $this->belongsTo(CustomerService::class, 'assigned_cs_id');
    }
}
