<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Order;

/**
 * Titik pusat untuk semua penulisan baris chat. Dipakai lintas fitur:
 *  - Tahap 4 (ChatController@send) untuk pesan manual dari customer/jastiper.
 *  - OrderDealService::formDeal() untuk pesan pembuka otomatis saat deal terbentuk.
 *  - Konversi notifikasi WhatsApp -> Chat di 4 titik (lihat Bagian 0 briefing).
 *  - Sprint 7 nanti untuk pesan bertombol (action_button).
 *
 * Kenapa disatukan di sini dan bukan ditulis Chat::create() langsung di
 * masing-masing pemanggil: supaya konvensi "pesan sistem pakai sender_id = 0"
 * dan aturan validasi/format lainnya hanya perlu benar di SATU tempat.
 */
class ChatService
{
    /**
     * Kirim pesan biasa (teks dan/atau foto) dari customer atau jastiper.
     *
     * Catatan desain: tabel `chats` tidak punya message_type khusus untuk foto —
     * enum message_type cuma 'text' / 'action_button' (lihat migration awal).
     * Foto dikirim dengan cara mengisi attachment_path sambil message_type tetap
     * 'text' (lihat Tahap 2). $message boleh string kosong kalau pesan cuma
     * berupa foto tanpa keterangan — kolom `message` NOT NULL tapi TIDAK
     * mensyaratkan non-empty, jadi string kosong valid.
     *
     * Validasi "message ATAU attachment wajib salah satu ada" adalah tanggung
     * jawab caller (ChatController@send, Tahap 4) — service ini tidak menolak
     * kombinasi kosong-kosong supaya tetap murni dan reusable.
     *
     * @param Order $order
     * @param string $senderRole 'customer' atau 'jastiper' (jangan pakai method ini untuk 'system', pakai sendSystemMessage()).
     * @param int $senderId ID customer/jastiper pengirim (wajib > 0 untuk pesan manusia).
     * @param string $message Isi teks pesan. Boleh string kosong kalau ada attachment.
     * @param string|null $attachmentPath Path relatif file foto (hasil store()), null kalau tidak ada lampiran.
     */
    public function sendMessage(
        Order $order,
        string $senderRole,
        int $senderId,
        string $message = '',
        ?string $attachmentPath = null
    ): Chat {
        return Chat::create([
            'order_id' => $order->id,
            'sender_role' => $senderRole,
            'sender_id' => $senderId,
            'message_type' => 'text',
            'message' => $message,
            'attachment_path' => $attachmentPath,
            'is_read' => false,
        ]);
    }

    /**
     * Shortcut untuk pesan otomatis dari sistem (bukan ditulis manual oleh
     * customer/jastiper) — dipakai untuk pesan pembuka deal (Tahap 3) dan hasil
     * konversi notifikasi WhatsApp -> Chat (Tahap 4).
     *
     * sender_id SELALU diisi 0 sebagai konvensi "system" (bukan NULL — lihat
     * keputusan desain di migration 2026_07_23_000002_...). Ini keputusan yang
     * didokumentasikan di briefing Tahap 2, jangan diubah tanpa update migration.
     */
    public function sendSystemMessage(Order $order, string $message): Chat
    {
        return Chat::create([
            'order_id' => $order->id,
            'sender_role' => 'system',
            'sender_id' => 0,
            'message_type' => 'text',
            'message' => $message,
            'attachment_path' => null,
            'is_read' => false,
        ]);
    }

    /**
     * Kirim pesan bertombol (action_button). DISIAPKAN STRUKTURNYA SAJA untuk
     * Sprint 7 — di Tahap 5 tombolnya dirender tapi onclick masih placeholder
     * (belum ada logic sungguhan yang dijalankan saat tombol ditekan).
     *
     * @param string $actionType Kode aksi yang nanti dibaca frontend/Sprint 7, contoh: 'confirm_received'.
     * @param string $label Teks yang tampil di tombol, contoh: 'Konfirmasi Barang Diterima'.
     */
    public function sendActionMessage(
        Order $order,
        string $senderRole,
        int $senderId,
        string $actionType,
        string $label
    ): Chat {
        return Chat::create([
            'order_id' => $order->id,
            'sender_role' => $senderRole,
            'sender_id' => $senderId,
            'message_type' => 'action_button',
            'message' => $label,
            'attachment_path' => null,
            'action_type' => $actionType,
            'is_read' => false,
        ]);
    }

    /**
     * Dipanggil dari OrderDealService::formDeal() begitu deal terbentuk
     * (baik jalur bidding maupun direct booking) — kirim SATU pesan sistem
     * pembuka ke room chat order tersebut.
     *
     * Room chat di sini berupa satu order_id yang sama dilihat dari 2 sisi
     * (customer & jastiper), bukan 2 pesan terpisah. Karena itu isi pesannya
     * sengaja menyebut nama KEDUA pihak (bukan "nama lawan bicara" tunggal
     * seperti draf awal di briefing) — supaya masuk akal dibaca dari sisi
     * manapun tanpa perlu 2 varian teks berbeda.
     *
     * Dipanggil SETELAH $order->jastiper_id ke-set oleh formDeal(), jadi relasi
     * jastiper() & customer() sudah bisa di-resolve langsung dari $order.
     */
    public function createDealRoomOpeningMessage(Order $order): Chat
    {
        $order->loadMissing(['customer', 'jastiper']);

        $namaCustomer = $order->customer->name ?? 'Customer';
        $namaJastiper = $order->jastiper->name ?? 'Jastiper';

        $message = "Deal terbentuk! {$namaCustomer} & {$namaJastiper} sekarang bisa koordinasi langsung di sini untuk pesanan \"{$order->description}\".";

        return $this->sendSystemMessage($order, $message);
    }
}