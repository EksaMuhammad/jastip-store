<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim OTP via WhatsApp (Menggunakan mock log jika API Key tidak ada,
     * dan menggunakan API Fonnte jika API Key terkonfigurasi).
     */
    public static function sendOtp(string $phoneNumber, string $otpCode)
    {
        $message = "Halo! Kode OTP JastipKuy Anda adalah: *{$otpCode}*.\n\nKode ini berlaku selama 5 menit. Harap JANGAN membagikan kode ini kepada siapapun demi keamanan akun Anda.";

        // Format nomor HP Indonesia (misal 0812345678 -> 62812345678)
        $formattedPhone = preg_replace('/^0/', '62', $phoneNumber);
        if (!str_starts_with($formattedPhone, '62') && !str_starts_with($formattedPhone, '+')) {
            $formattedPhone = '62' . $formattedPhone;
        }

        $apiKey = env('WHATSAPP_API_KEY');

        if (!$apiKey) {
            // Mocking log jika API Key belum dipasang
            Log::info("[MOCK WHATSAPP OTP] Mengirim ke +{$formattedPhone}: {$message}");
            return true;
        }

        try {
            // Contoh menggunakan gateway Fonnte (salah satu API WA terpopuler di Indonesia)
            $response = Http::withHeaders([
                'Authorization' => $apiKey
            ])->post('https://api.fonnte.com/send', [
                'target' => $formattedPhone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                Log::info("[WHATSAPP OTP SUCCESS] Berhasil mengirim OTP ke +{$formattedPhone}");
                return true;
            }

            Log::error("[WHATSAPP OTP ERROR] Gagal mengirim OTP: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("[WHATSAPP OTP EXCEPTION] Gagal memproses kirim WA: " . $e->getMessage());
            return false;
        }
    }
}
