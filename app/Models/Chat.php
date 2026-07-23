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
        'attachment_path',
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

    /**
     * PERHATIAN: sender_role bisa bernilai 'system' (lihat migration
     * 2026_07_23_000002_add_attachment_and_system_sender_to_chats.php), tapi
     * 'system' TIDAK didaftarkan di Relation::morphMap() (app/Providers/AppServiceProvider.php)
     * — cuma ada 'customer', 'jastiper', 'admin'. Artinya memanggil ->sender
     * pada baris chat dengan sender_role = 'system' akan melempar error saat
     * Eloquent coba resolve class 'system'. Jangan panggil relasi ini tanpa
     * cek scopeSystemMessages()/sender_role dulu di kode yang membaca chat (dipakai
     * mulai Tahap 5 saat render bubble chat).
     */
    public function sender()
    {
        return $this->morphTo('sender', 'sender_role', 'sender_id');
    }

    /**
     * Scope: filter chat untuk satu order tertentu.
     * Tidak menentukan urutan — caller yang atur orderBy sesuai kebutuhan
     * (riwayat chat biasanya ->orderBy('created_at')).
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope: pesan yang belum dibaca DARI SUDUT PANDANG role tertentu.
     * "Belum dibaca oleh jastiper" = pesan yang BUKAN dikirim oleh jastiper
     * (datang dari customer atau system) dan is_read masih false. Dipakai
     * baik untuk badge jumlah pesan belum dibaca (Tahap 5) maupun untuk
     * menandai pesan lawan bicara sebagai read saat endpoint history diakses
     * (Tahap 4).
     *
     * @param string $viewerRole Role yang sedang membuka/melihat chat ('customer' atau 'jastiper').
     */
    public function scopeUnreadFor($query, string $viewerRole)
    {
        return $query->where('sender_role', '!=', $viewerRole)->where('is_read', false);
    }

    /**
     * Scope: pesan sistem (notifikasi otomatis, bukan ditulis manual oleh user).
     * Dipakai di sisi render (Tahap 5) untuk styling beda (center-aligned,
     * italic) dan untuk menghindari pemanggilan ->sender pada baris ini.
     */
    public function scopeSystemMessages($query)
    {
        return $query->where('sender_role', 'system');
    }
}