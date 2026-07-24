<?php

use Illuminate\Console\Scheduling\Schedule;
use App\Services\PaymentService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // Tahap 4 — brief §1.3 & §3: cek tiap menit payment yang 'menunggu' dan
        // sudah lewat payment_deadline, lalu auto-cancel order terkait (lepas
        $schedule->call(function (PaymentService $paymentService) {
            $paymentService->expireOverdue();
        })
            ->everyMinute()
            ->name('payments:expire-overdue')
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            return route('login');
        });

        // Webhook Midtrans dipanggil server-to-server (bukan dari browser session
        // kita), jadi tidak akan pernah bawa CSRF token. Validitas payload divalidasi
        // sendiri via signature di dalam PaymentService::handleWebhook().
        $middleware->validateCsrfTokens(except: [
            'webhooks/midtrans',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();