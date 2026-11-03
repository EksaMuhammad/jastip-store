<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'user_name',
        'order_id',
        'category',
        'action',
        'title',
        'description',
        'properties',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Dapatkan model user yang melakukan aksi (jika ada).
     */
    public function getActorAttribute()
    {
        return match ($this->user_type) {
            'customer' => Customer::find($this->user_id),
            'jastiper' => Jastiper::find($this->user_id),
            'admin' => Admin::find($this->user_id),
            default => null,
        };
    }

    /**
     * Label peran yang rapi dan mudah dibaca.
     */
    public function getRoleBadgeAttribute(): array
    {
        return match ($this->user_type) {
            'customer' => ['label' => 'Customer', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500'],
            'jastiper' => ['label' => 'Mitra Jastiper', 'bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'dot' => 'bg-blue-500'],
            'admin' => ['label' => 'Admin Support', 'bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'dot' => 'bg-purple-500'],
            'system' => ['label' => 'Sistem Otomatis', 'bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'dot' => 'bg-amber-500'],
            default => ['label' => 'Umum', 'bg' => 'bg-slate-50 text-slate-700 border-slate-200', 'dot' => 'bg-slate-400'],
        };
    }

    /**
     * Label dan styling kategori aktivitas (clean enterprise badge tanpa emoji slop).
     */
    public function getCategoryMetaAttribute(): array
    {
        return match ($this->category) {
            'pesanan' => ['label' => 'Pesanan', 'color' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
            'pengantaran' => ['label' => 'Pengantaran', 'color' => 'text-blue-700 bg-blue-50 border-blue-200'],
            'harga_toko' => ['label' => 'Harga Toko', 'color' => 'text-amber-700 bg-amber-50 border-amber-200'],
            'komplain_tiket' => ['label' => 'Komplain', 'color' => 'text-rose-700 bg-rose-50 border-rose-200'],
            'chat_cs' => ['label' => 'Bantuan CS', 'color' => 'text-teal-700 bg-teal-50 border-teal-200'],
            'keuangan' => ['label' => 'Keuangan', 'color' => 'text-indigo-700 bg-indigo-50 border-indigo-200'],
            default => ['label' => 'Umum', 'color' => 'text-slate-700 bg-slate-50 border-slate-200'],
        };
    }
}
