<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderCancellationController;
use App\Http\Controllers\OrderAddonController;
use App\Http\Controllers\EarningsController;
use App\Http\Controllers\AdminWithdrawController;
use App\Http\Controllers\RatingController;

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
    Route::get('/customer/activity', [DashboardController::class, 'customerActivity'])->name('customer.activity');
    Route::get('/customer/chats', [DashboardController::class, 'customerChats'])->name('customer.chats');

    // ===== Bidding & Deal (Halaman Tawaran & Deal) =====
    Route::get('/customer/orders/active-feed', [DashboardController::class, 'customerActiveOrdersFeed'])->name('customer.orders.active-feed');
    Route::post('/customer/offers/{id}/accept', [DashboardController::class, 'customerAcceptOffer'])->name('customer.offers.accept');
    Route::post('/customer/orders/{id}/expand-radius', [DashboardController::class, 'customerExpandOrderRadius'])->name('customer.orders.expand-radius');
    Route::post('/customer/orders/{id}/cancel', [OrderCancellationController::class, 'cancelOrder'])->name('customer.orders.cancel');
    Route::post('/customer/orders/{id}/confirm', [DashboardController::class, 'customerConfirmDelivery'])->name('customer.orders.confirmation');

    // ===== Chat Personal per Order =====
    Route::post('/customer/orders/{id}/chat', [ChatController::class, 'send'])->name('customer.orders.chat.send');
    Route::get('/customer/orders/{id}/chat', [ChatController::class, 'history'])->name('customer.orders.chat.history');
    Route::post('/customer/orders/{id}/addon', [OrderAddonController::class, 'requestAddon'])->name('customer.orders.addon.request');
    Route::post('/customer/orders/{id}/addon/{addon_id}/pay', [OrderAddonController::class, 'payAddon'])->name('customer.orders.addon.pay');

    // ===== Pembayaran Wajib (Virtual Escrow) — Tahap 3 & 5 =====
    Route::get('/customer/orders/{id}/payment', [PaymentController::class, 'page'])->name('customer.orders.payment.page');
    Route::post('/customer/orders/{id}/payment/method', [PaymentController::class, 'selectMethod'])->name('customer.orders.payment.method');
    Route::post('/customer/orders/{id}/payment/wallet-pay', [PaymentController::class, 'payWithWallet'])->name('customer.orders.payment.wallet-pay');
    Route::post('/customer/orders/{id}/payment/upload-proof', [PaymentController::class, 'uploadProof'])->name('customer.orders.payment.upload-proof');
    Route::post('/customer/orders/{id}/payment/cancel', [PaymentController::class, 'cancel'])->name('customer.orders.payment.cancel');
    Route::get('/customer/orders/{id}/payment/status', [PaymentController::class, 'status'])->name('customer.orders.payment.status');

    // ===== E-Wallet =====
    Route::get('/customer/wallet', function() {
        return view('customer.wallet');
    })->name('customer.wallet');
    Route::get('/customer/wallet/pay-topup/{id}', function($id) {
        $topup = \App\Models\Topup::findOrFail($id);
        if ($topup->wallet->owner_id !== auth()->guard('customer')->id()) {
            abort(403);
        }
        return view('customer.pay-topup', compact('topup'));
    })->name('customer.wallet.pay_topup');

    // ===== Rating & Review (Sprint 8 Bagian 1) =====
    Route::get('/customer/orders/{id}/rating', [RatingController::class, 'showForm'])->name('customer.orders.rating.form');
    Route::post('/customer/orders/{id}/rating', [RatingController::class, 'store'])->name('customer.orders.rating.store');
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
    Route::post('/jastiper/orders/{id}/update-status', [DashboardController::class, 'jastiperUpdateOrderStatus'])->name('jastiper.orders.update-status');
    Route::post('/jastiper/orders/{id}/complete', [DashboardController::class, 'jastiperCompleteOrder'])->name('jastiper.orders.complete');

    // ===== Chat Personal per Order =====
    Route::post('/jastiper/orders/{id}/chat', [ChatController::class, 'send'])->name('jastiper.orders.chat.send');
    Route::get('/jastiper/orders/{id}/chat', [ChatController::class, 'history'])->name('jastiper.orders.chat.history');
    Route::post('/jastiper/addons/{id}/respond', [OrderAddonController::class, 'respondAddon'])->name('jastiper.addons.respond');

    // ===== Rekap Pendapatan & Withdraw (Sprint 8 Bagian 3) =====
    Route::get('/jastiper/earnings', [EarningsController::class, 'recap'])->name('jastiper.earnings');
    Route::get('/jastiper/earnings/data', [EarningsController::class, 'recapData'])->name('jastiper.earnings.data');
    Route::post('/jastiper/earnings/withdraw', [EarningsController::class, 'requestWithdraw'])->name('jastiper.earnings.withdraw');

    // ===== Profil Jastiper =====
    Route::get('/jastiper/profile', [DashboardController::class, 'jastiperProfile'])->name('jastiper.profile');
});

// Admin Dashboard Routes
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/verification', [DashboardController::class, 'adminVerification'])->name('admin.verification');
    // Admin endpoint for updating verification status (simulation/actual)
    Route::post('/admin/verification/{id}/update', [DashboardController::class, 'adminVerificationUpdate'])->name('admin.verification.update');

    // ===== Pembayaran Wajib (Virtual Escrow) — verifikasi manual bukti transfer =====
    Route::get('/admin/payments', [PaymentController::class, 'adminPage'])->name('admin.payments');
    Route::post('/admin/payments/{id}/verify', [PaymentController::class, 'adminVerify'])->name('admin.payments.verify');

    // ===== Withdraw Jastiper — approval admin (Sprint 8 Bagian 3) =====
    Route::get('/admin/withdraws', [AdminWithdrawController::class, 'index'])->name('admin.withdraws');
    Route::post('/admin/withdraws/{id}/approve', [AdminWithdrawController::class, 'approve'])->name('admin.withdraws.approve');
    Route::post('/admin/withdraws/{id}/reject', [AdminWithdrawController::class, 'reject'])->name('admin.withdraws.reject');
});

// Webhook Midtrans — publik, TANPA middleware auth:*. Validitas payload
// divalidasi via signature di dalam PaymentService::handleWebhook(), dan
// route ini dikecualikan dari validasi CSRF di bootstrap/app.php.
Route::post('/webhooks/midtrans', [PaymentController::class, 'webhook'])->name('webhooks.midtrans');