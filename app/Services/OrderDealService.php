<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Jastiper;

/**
 * Titik pusat untuk semua transisi status order yang berkaitan dengan
 * "pembentukan deal" antara Customer dan Jastiper.
 *
 * Sebelumnya logic ini duplikat di 2 tempat berbeda (DashboardController@customerAcceptOffer
 * untuk jalur Tawaran/Bidding, dan DashboardController@jastiperDirectAccept untuk jalur
 * Booking Langsung). Disatukan di sini supaya:
 *
 *  1. Ada SATU sumber kebenaran untuk apa yang terjadi saat deal terbentuk
 *     (jastiper_id terkunci, agreed_fare terkunci, status jadi 'deal').
 *  2. Fitur Chat (Sprint berikutnya) cukup nge-hook di SATU titik (formDeal())
 *     untuk otomatis membuat pesan pembuka chat room, tidak perlu ditulis
 *     ulang di setiap jalur yang bisa membentuk deal.
 *
 * PENTING: Service ini murni transisi status data (state machine). Pengiriman
 * notifikasi (WhatsApp / Chat) SENGAJA tidak ditaruh di sini — itu tanggung
 * jawab controller pemanggil, karena arah & isi notifikasi berbeda tergantung
 * siapa yang memicu aksi (customer memilih tawaran vs jastiper menerima booking
 * langsung). Guard/validasi status awal (misal cek order masih 'menunggu_tawaran')
 * juga tetap jadi tanggung jawab controller, karena pesan error yang ditampilkan
 * ke user berbeda-beda tergantung konteks pemanggilan.
 */
class OrderDealService
{
    /**
     * Kunci order ke satu Jastiper dengan harga yang disepakati, dan pindahkan
     * status order ke 'deal'. Dipanggil baik dari jalur Bidding (customer memilih
     * salah satu tawaran) maupun jalur Booking Langsung (jastiper menerima booking).
     *
     * Method ini TIDAK melakukan validasi/guard status awal order — pastikan
     * caller sudah memvalidasi order dalam status yang tepat SEBELUM memanggil
     * ini (dan idealnya di dalam DB transaction dengan row lock, seperti pola
     * yang sudah dipakai di customerAcceptOffer/jastiperDirectAccept).
     *
     * @param Order $order Order yang akan di-deal-kan (idealnya hasil lockForUpdate()).
     * @param Jastiper $jastiper Jastiper yang menjadi pemenang/penerima order ini.
     * @param float $agreedFare Harga final yang disepakati.
     * @param string $via Sumber pembentukan deal, untuk keperluan logging/analitik
     *                     di masa depan. Nilai yang dipakai saat ini: 'bidding' | 'direct'.
     * @return Order Order yang sudah di-refresh dari database setelah update.
     */
    public function formDeal(Order $order, Jastiper $jastiper, float $agreedFare, string $via = 'bidding'): Order
    {
        $order->update([
            'jastiper_id' => $jastiper->id,
            'agreed_fare' => $agreedFare,
            'status' => 'deal',
        ]);

        // TODO (Tahap 3-4 — Fitur Chat Personal): setelah ChatService dibuat,
        // panggil di sini untuk otomatis membuat pesan pembuka chat room, contoh:
        //
        //   app(\App\Services\ChatService::class)->createDealRoomOpeningMessage($order);
        //
        // Sengaja belum dipanggil sekarang karena ChatService belum ada (baru
        // dibangun di Tahap 3). Menaruh TODO di sini supaya titik hook-nya jelas
        // dan tidak perlu cari-cari lagi lintas file nanti.

        return $order->fresh();
    }

    /**
     * Pindahkan order yang sudah berstatus 'deal' ke status 'diproses', menandakan
     * jastiper resmi mulai membelanjakan/mengerjakan pesanan.
     *
     * Dipakai baik lewat aksi eksplisit jastiper (tombol "Mulai Proses" pada jalur
     * Bidding, lihat DashboardController@jastiperStartProcessOrder) MAUPUN otomatis
     * langsung setelah formDeal() pada jalur Booking Langsung (jastiper yang menerima
     * booking langsung dianggap otomatis langsung mulai memproses, tidak perlu tap
     * tombol terpisah — ini mempertahankan perilaku lama yang sudah ada sebelum
     * refactor ini).
     *
     * Method ini TIDAK memvalidasi bahwa order memang berstatus 'deal' sebelum
     * dipanggil — pastikan caller sudah memvalidasi (lihat catatan formDeal() di atas).
     *
     * @param Order $order Order yang berstatus 'deal', akan dipindah ke 'diproses'.
     * @return Order Order yang sudah di-refresh dari database setelah update.
     */
    public function startProcessing(Order $order): Order
    {
        $order->update(['status' => 'diproses']);

        // TODO (Tahap 4 — Fitur Chat Personal): ganti notifikasi WhatsApp "mulai
        // diproses" di DashboardController@jastiperStartProcessOrder menjadi pesan
        // sistem lewat ChatService::sendSystemMessage($order, ...) di titik ini
        // atau tetap di controller — didiskusikan lagi saat Tahap 4 dikerjakan.

        return $order->fresh();
    }
}