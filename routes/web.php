<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

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

// Dashboard Routes (protected by role-based auth middleware)
Route::middleware('auth:customer')->group(function () {
    Route::get('/customer/dashboard', [DashboardController::class, 'customerDashboard'])->name('customer.dashboard');
});

Route::middleware('auth:jastiper')->group(function () {
    Route::get('/jastiper/dashboard', [DashboardController::class, 'jastiperDashboard'])->name('jastiper.dashboard');
    Route::get('/jastiper/verification', [DashboardController::class, 'jastiperVerification'])->name('jastiper.verification');
});

// Admin endpoint for updating verification status (simulation/actual)
Route::post('/admin/verification/{id}/update', [DashboardController::class, 'adminVerificationUpdate'])->name('admin.verification.update');


