<?php

namespace App\Services;

use App\Models\Order;
use App\Services\ChatService;
use Illuminate\Support\Facades\DB;

/**
 * Titik pusat orkestrasi setiap kali order berpindah ke status 'selesai'.
 * Brief Sprint 8 Bagian 3 §2.3 & Overview §2 — menyatukan 2 jalur yang tadinya
 * terpisah (jastiperCompleteOrder & customerConfirmDelivery di
 * DashboardController), sama seperti pola sentralisasi OrderDealService
 * sebelumnya di project ini.
 *
 * Tanggung jawab method completeOrder():
 * 1. Set status 'selesai' (idempotent — dijaga tidak diproses dua kali)
 * 2. Potong komisi & kredit wallet jastiper (KomisiService)
 * 3. TODO(Bagian 2 - Badge/Trust Level): panggil BadgeService::recalculate()
 *    untuk update total_completed_orders. Belum dikerjakan di sprint ini —
 *    di-guard dengan class_exists() supaya begitu Bagian 2 selesai, otomatis
 *    kepanggil tanpa perlu edit ulang file ini.
 * 4. Kirim notifikasi (pesan beda tergantung siapa yang menyelesaikan)
 */
class OrderCompletionService
{
    public function __construct(private KomisiService $komisiService)
    {
    }

    /**
     * @param  string  $completedByRole  'jastiper' atau 'customer' — dipakai untuk
     *                                   menentukan pesan notifikasi yang dikirim.
     */
    public function completeOrder(Order $order, string $completedByRole): Order
    {
        return DB::transaction(function () use ($order, $completedByRole) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            // Idempotency guard: kalau order sudah 'selesai' (misal ter-trigger
            // dobel dari dua request hampir bersamaan), jangan proses ulang
            // komisi/notifikasi lagi — cukup kembalikan order apa adanya.
            if ($order->status === 'selesai') {
                return $order;
            }

            $order->update(['status' => 'selesai']);

            // 2. Potong komisi & kredit wallet jastiper.
            $this->komisiService->potongKomisi($order);

            // 3. Badge recalculation (Bagian 2) — belum dikerjakan, di-guard.
            if (class_exists(\App\Services\BadgeService::class)) {
                app(\App\Services\BadgeService::class)->recalculate($order->jastiper_id);
            }

            // 4. Notifikasi — pesan berbeda tergantung siapa yang menyelesaikan,
            // mengikuti pesan yang sudah dipakai di masing-masing controller
            // sebelum disentralisasi ke sini.
            $this->notify($order, $completedByRole);

            return $order->fresh();
        });
    }

    private function notify(Order $order, string $completedByRole): void
    {
        $jastiper = $order->jastiper;
        $customer = $order->customer;

        if ($completedByRole === 'jastiper') {
            app(ChatService::class)->sendSystemMessage(
                $order,
                "Belanjaan Anda \"{$order->description}\" telah selesai dibelanjakan dan diantarkan oleh Jastiper " . ($jastiper->name ?? '') . "! Terima kasih telah menggunakan layanan JastipKuy. 🙏"
            );
        } else {
            if ($jastiper) {
                \App\Services\WhatsAppService::sendMessage(
                    $jastiper->phone_number,
                    "Customer telah mengkonfirmasi penerimaan pesanan \"{$order->description}\". Pesanan selesai!"
                );
            }
        }
    }
}