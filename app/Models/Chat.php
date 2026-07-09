<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use SoftDeletes;

    protected $table = 'chats';

    protected $fillable = [
        'order_id',
        'sender_role',
        'sender_id',
        'message_type',
        'message',
        'action_type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function sender()
    {
        return $this->morphTo('sender', 'sender_role', 'sender_id');
    }
}
