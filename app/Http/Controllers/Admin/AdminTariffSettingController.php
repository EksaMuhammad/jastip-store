<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TariffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTariffSettingController extends Controller
{
    public function __construct(private TariffService $tariffService)
    {
    }

    /**
     * Tampilkan formulir pengaturan tarif dan ketentuan jastip.
     */
    public function index(): View
    {
        $tariffs = $this->tariffService->getAll();

        return view('admin.settings.tariffs', compact('tariffs'));
    }

    /**
     * Simpan pembaruan tarif ke database.
     */
    public function update(Request $request): RedirectResponse
    {
        // Bersihkan tanda titik pemisah ribuan dari field-field nominal Rupiah
        $nominalFields = [
            'rate_per_km',
            'min_fare',
            'extra_stop_fare',
            'multi_store_km_rate',
            'multi_store_extra_store_fare',
            'category_beli_antar',
            'category_ambil_antar',
            'category_toko_kirim',
            'category_dokumen',
            'category_multi_stop',
            'category_kirim_pihak_ketiga',
            'weight_ringan',
            'weight_sedang',
            'weight_berat',
            'min_topup_amount',
            'insurance_min_order_threshold',
            'insurance_fee',
        ];

        $input = $request->all();
        $input['insurance_enabled'] = $request->has('insurance_enabled') ? 1 : 0;
        foreach ($nominalFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = str_replace('.', '', (string) $input[$field]);
            }
        }
        $request->merge($input);

        $validated = $request->validate([
            'rate_per_km' => 'required|numeric|min:0',
            'min_fare' => 'required|numeric|min:0',
            'extra_stop_fare' => 'required|numeric|min:0',
            'base_included_km' => 'required|numeric|min:0',
            'multi_store_km_rate' => 'required|numeric|min:0',
            'multi_store_extra_store_fare' => 'required|numeric|min:0',

            'category_beli_antar' => 'required|numeric|min:0',
            'category_ambil_antar' => 'required|numeric|min:0',
            'category_toko_kirim' => 'required|numeric|min:0',
            'category_dokumen' => 'required|numeric|min:0',
            'category_multi_stop' => 'required|numeric|min:0',
            'category_kirim_pihak_ketiga' => 'required|numeric|min:0',

            'weight_ringan' => 'required|numeric|min:0',
            'weight_sedang' => 'required|numeric|min:0',
            'weight_berat' => 'required|numeric|min:0',

            'platform_commission_percent' => 'required|numeric|min:0|max:100',
            'merchant_markup_percent' => 'nullable|numeric|min:0|max:100',
            'payment_deadline_minutes' => 'required|numeric|min:1',
            'min_topup_amount' => 'required|numeric|min:1000',

            'insurance_enabled' => 'nullable|in:0,1',
            'insurance_min_order_threshold' => 'nullable|numeric|min:0',
            'insurance_fee' => 'nullable|numeric|min:0',
        ]);

        if (!isset($validated['merchant_markup_percent']) || $validated['merchant_markup_percent'] === null) {
            $validated['merchant_markup_percent'] = (float) $this->tariffService->get('merchant_markup_percent', 10);
        }

        if (!isset($validated['insurance_min_order_threshold']) || $validated['insurance_min_order_threshold'] === null) {
            $validated['insurance_min_order_threshold'] = (int) $this->tariffService->get('insurance_min_order_threshold', 200000);
        }
        if (!isset($validated['insurance_fee']) || $validated['insurance_fee'] === null) {
            $validated['insurance_fee'] = (int) $this->tariffService->get('insurance_fee', 2000);
        }

        $this->tariffService->updateAll($validated);

        return redirect()->route('admin.settings.tariffs')
            ->with('success', 'Pengaturan tarif & ketentuan jastip berhasil disimpan dan langsung aktif di seluruh sistem!');
    }

    /**
     * Reset seluruh pengaturan tarif kembali ke default awal.
     */
    public function reset(): RedirectResponse
    {
        $this->tariffService->resetToDefaults();

        return redirect()->route('admin.settings.tariffs')
            ->with('success', 'Seluruh pengaturan tarif berhasil direset ke standar awal!');
    }
}
