<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderApiController;
use App\Http\Controllers\AuthApiController;

// Authentication APIs
Route::post('/auth/check-phone', [AuthApiController::class, 'checkPhone']);
Route::post('/auth/login-password', [AuthApiController::class, 'loginPassword']);
Route::post('/auth/register', [AuthApiController::class, 'register']);
Route::post('/auth/send-otp', [AuthApiController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthApiController::class, 'verifyOtp']);

Route::post('/orders', [OrderApiController::class, 'store']);
Route::get('/orders', [OrderApiController::class, 'index']);

// User / Profile endpoints
Route::get('/customers', [OrderApiController::class, 'getCustomers']);
Route::get('/jastipers', [OrderApiController::class, 'getJastipers']);
Route::get('/wilayah', [OrderApiController::class, 'getWilayahList']);

// Jastiper endpoints
Route::get('/jastiper/feed', [OrderApiController::class, 'jastiperFeed']);
Route::post('/jastiper/bid', [OrderApiController::class, 'jastiperBid']);
Route::get('/jastiper/earnings', [AuthApiController::class, 'jastiperEarnings']);
Route::post('/jastiper/withdraw', [AuthApiController::class, 'jastiperWithdraw']);
Route::get('/jastiper/checkin', [OrderApiController::class, 'getCheckinJastipers']);
Route::post('/customer/accept-offer', [OrderApiController::class, 'acceptOffer']);
Route::post('/customer/pay-wallet', [OrderApiController::class, 'payOrderWithWallet']);
Route::post('/customer/confirm-delivery', [OrderApiController::class, 'confirmDelivery']);
Route::post('/jastiper/update-status', [OrderApiController::class, 'updateOrderStatus']);
Route::post('/jastiper/direct-accept', [OrderApiController::class, 'acceptDirectBooking']);
Route::post('/jastiper/direct-reject', [OrderApiController::class, 'rejectDirectBooking']);
Route::get('/orders/{id}/chat', [OrderApiController::class, 'getChatHistory']);
Route::post('/orders/{id}/chat', [OrderApiController::class, 'sendChatMessage']);
