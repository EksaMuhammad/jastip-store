<?php

use Illuminate\Console\Scheduling\Schedule;
use App\Services\PaymentService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
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
        // Percayai header dari reverse proxy (cloudflared tunnel) supaya Laravel
        // tahu request aslinya HTTPS dan generate URL asset dengan skema yang benar.
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            if ($request->is('merchant') || $request->is('merchant/*')) {
                return route('merchant.login');
            }
            if ($request->is('cs') || $request->is('cs/*')) {
                return route('cs.login');
            }
            return route('login');
        });

        // Webhook Midtrans dipanggil server-to-server (bukan dari browser session
        // kita), jadi tidak akan pernah bawa CSRF token. Validitas payload divalidasi
        // sendiri via signature di dalam PaymentService::handleWebhook().
        $middleware->validateCsrfTokens(except: [
            'webhooks/midtrans',
            'api/*',
            'livewire/*',
            'livewire-*/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::warning('CSRF Token Mismatch Details', [
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'host' => $request->getHost(),
                'header_csrf' => $request->header('X-CSRF-TOKEN'),
                'input_token' => $request->input('_token'),
                'session_token' => $request->hasSession() ? $request->session()->token() : 'NO_SESSION',
                'session_id' => $request->hasSession() ? $request->session()->getId() : 'NO_SESSION_ID',
                'cookies' => $request->cookies->all(),
            ]);
        });
    })->create();