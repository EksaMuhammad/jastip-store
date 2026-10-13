<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Merchant;
use Illuminate\Support\Facades\Hash;

class AdminMerchantController extends Controller
{
    public function index()
    {
        $merchants = Merchant::withCount('products')->latest()->paginate(10);
        return view('admin.merchants.index', compact('merchants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:food,mart',
            'address' => 'required|string',
            'email' => 'required|email|unique:merchants,email',
            'password' => 'required|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active');

        Merchant::create($validated);

        return redirect()->route('admin.merchants.index')->with('success', 'Mitra Merchant dan akun login berhasil didaftarkan.');
    }

    public function update(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:food,mart',
            'address' => 'required|string',
            'email' => 'required|email|unique:merchants,email,' . $merchant->id,
            'password' => 'nullable|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active');

        $merchant->update($validated);

        return redirect()->route('admin.merchants.index')->with('success', 'Data Mitra Merchant berhasil diperbarui.');
    }

    public function destroy(Merchant $merchant)
    {
        // Delete all products associated
        $merchant->products()->delete();
        $merchant->delete();

        return redirect()->route('admin.merchants.index')->with('success', 'Merchant dan semua produk di dalamnya berhasil dihapus.');
    }
}
