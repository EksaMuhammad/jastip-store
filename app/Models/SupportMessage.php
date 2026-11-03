<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportMessage extends Model
{
    use SoftDeletes;

    protected $table = 'support_messages';

    protected $fillable = [
        'customer_id',
        'admin_id',
        'sender_type',
        'message',
        'attachment_path',
        'is_read',
        'order_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function scopeBetween($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeUnreadForAdmin($query)
    {
        return $query->where('sender_type', 'customer')->where('is_read', false);
    }

    public function scopeUnreadForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId)
                     ->where('sender_type', 'admin')
                     ->where('is_read', false);
    }
}
