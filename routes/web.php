<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/faq', 'faq')->name('faq');
Route::view('/syarat-ketentuan', 'terms')->name('terms');
Route::view('/kebijakan-privasi', 'privacy')->name('privacy');
Route::view('/tentang-kami', 'about')->name('about');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Auth Routes
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Dashboard Routes (protected by role-based auth middleware)
Route::middleware('auth:customer')->group(function () {
    Route::get('/customer/dashboard', [DashboardController::class, 'customerDashboard'])->name('customer.dashboard');
    Route::get('/customer/orders/create', [DashboardController::class, 'customerCreateOrder'])->name('customer.orders.create');
    Route::get('/customer/booking', [DashboardController::class, 'customerBookingView'])->name('customer.booking');
    Route::post('/customer/jastiper/{id}/favorite', [DashboardController::class, 'customerToggleFavorite'])->name('customer.jastiper.favorite');
    Route::get('/customer/jastiper/{id}/availability', [DashboardController::class, 'customerJastiperAvailability'])->name('customer.jastiper.availability');

    // ===== Bidding & Deal (Halaman Tawaran & Deal) =====
    Route::get('/customer/orders/active-feed', [DashboardController::class, 'customerActiveOrdersFeed'])->name('customer.orders.active-feed');
    Route::post('/customer/offers/{id}/accept', [DashboardController::class, 'customerAcceptOffer'])->name('customer.offers.accept');
    Route::post('/customer/orders/{id}/expand-radius', [DashboardController::class, 'customerExpandOrderRadius'])->name('customer.orders.expand-radius');
    Route::post('/customer/orders/{id}/cancel', [DashboardController::class, 'customerCancelOrder'])->name('customer.orders.cancel');

    // ===== Chat Personal per Order =====
    Route::post('/customer/orders/{id}/chat', [ChatController::class, 'send'])->name('customer.orders.chat.send');
    Route::get('/customer/orders/{id}/chat', [ChatController::class, 'history'])->name('customer.orders.chat.history');

    // ===== Pembayaran Wajib (Virtual Escrow) — Tahap 3 =====
    // Catatan: GET /customer/orders/{id}/payment (halaman utama, Livewire full-page)
    // sengaja belum didaftarkan di sini — itu scope Tahap 5.
    Route::post('/customer/orders/{id}/payment/method', [PaymentController::class, 'selectMethod'])->name('customer.orders.payment.method');
    Route::post('/customer/orders/{id}/payment/wallet-pay', [PaymentController::class, 'payWithWallet'])->name('customer.orders.payment.wallet-pay');
    Route::post('/customer/orders/{id}/payment/upload-proof', [PaymentController::class, 'uploadProof'])->name('customer.orders.payment.upload-proof');
    Route::get('/customer/orders/{id}/payment/status', [PaymentController::class, 'status'])->name('customer.orders.payment.status');
});

Route::middleware('auth:jastiper')->group(function () {
    Route::get('/jastiper/dashboard', [DashboardController::class, 'jastiperDashboard'])->name('jastiper.dashboard');
    Route::get('/jastiper/verification', [DashboardController::class, 'jastiperVerification'])->name('jastiper.verification');
    Route::get('/jastiper/area', [DashboardController::class, 'jastiperArea'])->name('jastiper.area');
    Route::post('/jastiper/checkin', [DashboardController::class, 'jastiperCheckin'])->name('jastiper.checkin');
    Route::post('/jastiper/orders/{id}/direct-accept', [DashboardController::class, 'jastiperDirectAccept'])->name('jastiper.orders.direct-accept');
    Route::post('/jastiper/orders/{id}/direct-reject', [DashboardController::class, 'jastiperDirectReject'])->name('jastiper.orders.direct-reject');
    Route::post('/jastiper/toggle-status', [DashboardController::class, 'jastiperToggleStatus'])->name('jastiper.toggle-status');

    // ===== Bagian 3: Halaman Feed Request (radius + kategori + multi-order) =====
    Route::post('/jastiper/work-status', [DashboardController::class, 'jastiperUpdateWorkStatus'])->name('jastiper.work-status.update');
    Route::get('/jastiper/orders/feed', [DashboardController::class, 'jastiperOrderFeed'])->name('jastiper.orders.feed');

    // ===== Bidding & Deal (Halaman Tawaran & Deal) =====
    Route::post('/jastiper/orders/{id}/offer', [DashboardController::class, 'jastiperSubmitOffer'])->name('jastiper.orders.offer');
    Route::post('/jastiper/orders/multi-offer', [DashboardController::class, 'jastiperMultiSubmitOffer'])->name('jastiper.orders.multi-offer');
    Route::post('/jastiper/orders/{id}/start-process', [DashboardController::class, 'jastiperStartProcessOrder'])->name('jastiper.orders.start-process');
    Route::post('/jastiper/orders/{id}/complete', [DashboardController::class, 'jastiperCompleteOrder'])->name('jastiper.orders.complete');

    // ===== Chat Personal per Order =====
    Route::post('/jastiper/orders/{id}/chat', [ChatController::class, 'send'])->name('jastiper.orders.chat.send');
    Route::get('/jastiper/orders/{id}/chat', [ChatController::class, 'history'])->name('jastiper.orders.chat.history');
});

// Admin Dashboard Routes
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/verification', [DashboardController::class, 'adminVerification'])->name('admin.verification');
    // Admin endpoint for updating verification status (simulation/actual)
    Route::post('/admin/verification/{id}/update', [DashboardController::class, 'adminVerificationUpdate'])->name('admin.verification.update');

    // ===== Pembayaran Wajib (Virtual Escrow) — verifikasi manual bukti transfer =====
    Route::post('/admin/payments/{id}/verify', [PaymentController::class, 'adminVerify'])->name('admin.payments.verify');
});

// Webhook Midtrans — publik, TANPA middleware auth:*. Validitas payload
// divalidasi via signature di dalam PaymentService::handleWebhook(), dan
// route ini dikecualikan dari validasi CSRF di bootstrap/app.php.
Route::post('/webhooks/midtrans', [PaymentController::class, 'webhook'])->name('webhooks.midtrans');