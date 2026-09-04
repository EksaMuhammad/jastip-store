<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Merchant;

class AdminMerchantController extends Controller
{
    public function index()
    {
        $merchants = Merchant::latest()->paginate(10);
        return view('admin.merchants.index', compact('merchants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:food,mart',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|url', // Using URL for simplicity, can change to file upload later
        ]);

        $validated['is_active'] = $request->has('is_active');

        Merchant::create($validated);

        return redirect()->route('admin.merchants.index')->with('success', 'Merchant berhasil ditambahkan.');
    }

    public function update(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:food,mart',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|url',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $merchant->update($validated);

        return redirect()->route('admin.merchants.index')->with('success', 'Merchant berhasil diperbarui.');
    }

    public function destroy(Merchant $merchant)
    {
        // Delete all products associated
        $merchant->products()->delete();
        $merchant->delete();

        return redirect()->route('admin.merchants.index')->with('success', 'Merchant dan semua produk di dalamnya berhasil dihapus.');
    }
}
