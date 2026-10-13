<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AdminMidtransSettingController extends Controller
{
    /**
     * Tampilkan formulir pengaturan Midtrans di portal Admin.
     */
    public function index(): View
    {
        $serverKey = Setting::get('midtrans_server_key', config('services.midtrans.server_key', ''));
        $clientKey = Setting::get('midtrans_client_key', config('services.midtrans.client_key', ''));
        $merchantId = Setting::get('midtrans_merchant_id', config('services.midtrans.merchant_id', ''));
        $isProduction = (bool) Setting::get('midtrans_is_production', config('services.midtrans.is_production', false));
        $isActive = (bool) Setting::get('midtrans_is_active', true);
        
        $enabledPaymentsRaw = Setting::get('midtrans_enabled_payments', 'qris,gopay,shopeepay,bca_va,bni_va,bri_va,echannel');
        $enabledPayments = explode(',', $enabledPaymentsRaw);

        $webhookUrl = url('/webhooks/midtrans');
        $finishUrl = url('/customer/dompet');

        return view('admin.settings.midtrans', compact(
            'serverKey',
            'clientKey',
            'merchantId',
            'isProduction',
            'isActive',
            'enabledPayments',
            'webhookUrl',
            'finishUrl'
        ));
    }

    /**
     * Simpan pembaruan pengaturan Midtrans ke database.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'server_key' => 'nullable|string|max:255',
            'client_key' => 'nullable|string|max:255',
            'merchant_id' => 'nullable|string|max:255',
            'is_production' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'enabled_payments' => 'nullable|array',
            'enabled_payments.*' => 'string',
        ]);

        $serverKey = trim((string) ($validated['server_key'] ?? ''));
        $clientKey = trim((string) ($validated['client_key'] ?? ''));
        $merchantId = trim((string) ($validated['merchant_id'] ?? ''));
        $isProduction = $request->boolean('is_production');
        $isActive = $request->boolean('is_active');
        $enabledPayments = $request->input('enabled_payments', []);

        Setting::set('midtrans_server_key', $serverKey, 'midtrans');
        Setting::set('midtrans_client_key', $clientKey, 'midtrans');
        Setting::set('midtrans_merchant_id', $merchantId, 'midtrans');
        Setting::set('midtrans_is_production', $isProduction ? '1' : '0', 'midtrans');
        Setting::set('midtrans_is_active', $isActive ? '1' : '0', 'midtrans');
        Setting::set('midtrans_enabled_payments', implode(',', $enabledPayments), 'midtrans');

        // Perbarui config runtime agar langsung berlaku di request saat ini
        config([
            'services.midtrans.server_key' => $serverKey,
            'services.midtrans.client_key' => $clientKey,
            'services.midtrans.merchant_id' => $merchantId,
            'services.midtrans.is_production' => $isProduction,
            'services.midtrans.is_active' => $isActive,
        ]);

        return redirect()->route('admin.settings.midtrans')
            ->with('success', 'Pengaturan Payment Gateway Midtrans berhasil disimpan!');
    }

    /**
     * Uji koneksi kredensial ke server API Midtrans secara live.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $serverKey = trim((string) $request->input('server_key'))
            ?: (Setting::get('midtrans_server_key') ?: config('services.midtrans.server_key'));

        $isProduction = $request->has('is_production')
            ? $request->boolean('is_production')
            : (bool) Setting::get('midtrans_is_production', config('services.midtrans.is_production', false));

        if (empty($serverKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Server Key belum diisi. Masukkan Server Key terlebih dahulu untuk melakukan uji koneksi.',
            ], 422);
        }

        $baseUrl = $isProduction ? 'https://api.midtrans.com/v2' : 'https://api.sandbox.midtrans.com/v2';

        try {
            // Lakukan ping status ke Midtrans API menggunakan Basic Auth
            $response = Http::withBasicAuth($serverKey, '')
                ->timeout(8)
                ->get($baseUrl . '/dummy-connection-test-check');

            if ($response->status() === 401) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koneksi Ditolak (401 Unauthorized): Server Key tidak valid. Pastikan Server Key cocok dengan mode ' . ($isProduction ? 'Production' : 'Sandbox') . ' dari Dashboard Midtrans Anda.',
                ]);
            }

            // Jika status response bukan 401 (misal 404 karena order dummy tidak ada), Basic Auth terbukti sah diterima server Midtrans
            return response()->json([
                'success' => true,
                'mode' => $isProduction ? 'Production (Live)' : 'Sandbox (Uji Coba)',
                'message' => 'Koneksi Berhasil! Akun Midtrans Anda (' . ($isProduction ? 'Production' : 'Sandbox') . ') terhubung dan siap digunakan.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server Midtrans: ' . $e->getMessage(),
            ], 500);
        }
    }
}
