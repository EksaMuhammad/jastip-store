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
            'payment_deadline_minutes' => 'required|numeric|min:1',
            'min_topup_amount' => 'required|numeric|min:1000',
        ]);

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
