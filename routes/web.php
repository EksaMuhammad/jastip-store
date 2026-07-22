<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminAuthController;

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
});

Route::middleware('auth:jastiper')->group(function () {
    Route::get('/jastiper/dashboard', [DashboardController::class, 'jastiperDashboard'])->name('jastiper.dashboard');
    Route::get('/jastiper/verification', [DashboardController::class, 'jastiperVerification'])->name('jastiper.verification');
    Route::get('/jastiper/area', [DashboardController::class, 'jastiperArea'])->name('jastiper.area');
    Route::post('/jastiper/orders/{id}/accept', [DashboardController::class, 'jastiperAcceptOrder'])->name('jastiper.orders.accept');
    Route::post('/jastiper/checkin', [DashboardController::class, 'jastiperCheckin'])->name('jastiper.checkin');
    Route::post('/jastiper/orders/{id}/direct-accept', [DashboardController::class, 'jastiperDirectAccept'])->name('jastiper.orders.direct-accept');
    Route::post('/jastiper/orders/{id}/direct-reject', [DashboardController::class, 'jastiperDirectReject'])->name('jastiper.orders.direct-reject');
    Route::post('/jastiper/toggle-status', [DashboardController::class, 'jastiperToggleStatus'])->name('jastiper.toggle-status');
});

// Admin Dashboard Routes
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/verification', [DashboardController::class, 'adminVerification'])->name('admin.verification');
    // Admin endpoint for updating verification status (simulation/actual)
    Route::post('/admin/verification/{id}/update', [DashboardController::class, 'adminVerificationUpdate'])->name('admin.verification.update');
});
