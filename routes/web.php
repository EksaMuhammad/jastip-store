<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/faq', 'faq')->name('faq');
Route::view('/syarat-ketentuan', 'terms')->name('terms');
Route::view('/kebijakan-privasi', 'privacy')->name('privacy');
Route::view('/tentang-kami', 'about')->name('about');

