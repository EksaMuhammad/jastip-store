<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    |
    | Berapa lama customer punya waktu untuk menyelesaikan pembayaran sebelum
    | order otomatis kedaluwarsa (dibatalkan oleh scheduler). Brief §0 no. 5
    | dan §4 no. 1: default 15 menit, dibuat configurable di sini.
    |
    */
    'payment' => [
        'deadline_minutes' => env('JASTIP_PAYMENT_DEADLINE_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Komisi
    |--------------------------------------------------------------------------
    |
    | Persentase komisi platform yang dipotong dari agreed_fare saat order
    | selesai/diterima, sebelum sisanya dikreditkan ke wallet jastiper.
    | Brief §2.5: hardcode dulu tapi gampang diubah lewat config/env.
    |
    */
    'komisi' => [
        'percentage' => env('JASTIP_KOMISI_PERCENTAGE', 10),
    ],

];