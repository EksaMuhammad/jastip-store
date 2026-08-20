<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderApiController;

Route::post('/orders', [OrderApiController::class, 'store']);
Route::get('/orders', [OrderApiController::class, 'index']);

// Jastiper endpoints
Route::get('/jastiper/feed', [OrderApiController::class, 'jastiperFeed']);
Route::post('/jastiper/bid', [OrderApiController::class, 'jastiperBid']);
