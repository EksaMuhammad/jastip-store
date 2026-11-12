<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CustomerService extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'customer_services';

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'phone_number',
        'avatar',
        'is_active',
        'is_online',
        'work_status',
        'last_active_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function assignedConversations()
    {
        return $this->hasMany(SupportConversation::class, 'assigned_cs_id');
    }

    public function activeConversations()
    {
        return $this->hasMany(SupportConversation::class, 'assigned_cs_id')
            ->where('status', 'aktif');
    }

    public function assignedReports()
    {
        return $this->hasMany(Report::class, 'assigned_cs_id');
    }

    public function activeReports()
    {
        return $this->hasMany(Report::class, 'assigned_cs_id')
            ->where('status', 'menunggu');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'cs_id');
    }

    /**
     * Cek apakah CS benar-benar aktif/online (heartbeat dalam 5 menit terakhir)
     */
    public function isReallyOnline(): bool
    {
        if (!$this->is_online || !$this->is_active) {
            return false;
        }

        if (!$this->last_active_at) {
            return false;
        }

        return $this->last_active_at->diffInMinutes(now()) <= 5;
    }

    /**
     * Hitung total beban kerja aktif (chat aktif + tiket komplain yang sedang ditangani)
     */
    public function getActiveWorkloadCountAttribute(): int
    {
        return $this->activeConversations()->count() + $this->activeReports()->count();
    }
}

