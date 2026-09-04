<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Category; // In case we need it later

class AdminProductController extends Controller
{
    public function index(Merchant $merchant)
    {
        $products = $merchant->products()->latest()->paginate(15);
        return view('admin.products.index', compact('merchant', 'products'));
    }

    public function store(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_price' => 'required|numeric|min:0',
            'image' => 'nullable|url',
        ]);

        $validated['merchant_id'] = $merchant->id;
        $validated['is_available'] = $request->has('is_available');
        $validated['is_flash_sale'] = $request->has('is_flash_sale');

        Product::create($validated);

        return redirect()->route('admin.products.index', $merchant->id)->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_price' => 'required|numeric|min:0',
            'image' => 'nullable|url',
        ]);

        $validated['is_available'] = $request->has('is_available');
        $validated['is_flash_sale'] = $request->has('is_flash_sale');

        $product->update($validated);

        return redirect()->route('admin.products.index', $product->merchant_id)->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $merchantId = $product->merchant_id;
        $product->delete();

        return redirect()->route('admin.products.index', $merchantId)->with('success', 'Produk berhasil dihapus.');
    }
}
