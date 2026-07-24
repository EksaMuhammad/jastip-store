<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

/**
 * Tahap 4 — Scheduler auto-cancel (brief §1.3 & §3 Tahap 4).
 *
 * Dijalankan tiap menit oleh scheduler (lihat bootstrap/app.php ->withSchedule()).
 * Dibungkus jadi command command (bukan langsung $schedule->call(closure))
 * supaya: (1) bisa dites langsung lewat `php artisan payments:expire-overdue`
 * tanpa perlu memanggil `schedule:run`, dan (2) dapat exit code + output yang
 * jelas kalau dijalankan manual/di-cron log.
 */
class ExpireOverduePayments extends Command
{
    protected $signature = 'payments:expire-overdue';

    protected $description = 'Batalkan order yang menunggu_pembayaran dan sudah lewat payment_deadline (auto-expire).';

    public function handle(PaymentService $paymentService): int
    {
        $count = $paymentService->expireOverdue();

        $this->info("Selesai. {$count} order dibatalkan karena pembayaran kedaluwarsa.");

        return self::SUCCESS;
    }
}