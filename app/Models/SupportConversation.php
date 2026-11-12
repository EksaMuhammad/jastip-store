<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportConversation extends Model
{
    use HasFactory;

    protected $table = 'support_conversations';

    protected $fillable = [
        'customer_id',
        'assigned_cs_id',
        'status',
        'priority',
        'last_message_at',
        'assigned_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function assignedCs()
    {
        return $this->belongsTo(CustomerService::class, 'assigned_cs_id');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'customer_id', 'customer_id');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}

