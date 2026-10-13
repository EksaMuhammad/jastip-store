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
        $products = $merchant->products()->with('category')->latest()->paginate(15);
        $categories = Category::where('type', $merchant->type)
            ->where(function ($q) use ($merchant) {
                $q->whereNull('merchant_id')->orWhere('merchant_id', $merchant->id);
            })
            ->orderBy('name')
            ->get();
        return view('admin.products.index', compact('merchant', 'products', 'categories'));
    }

    public function store(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable',
            'new_category_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'estimated_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|url',
        ]);

        if ($request->filled('new_category_name')) {
            $catName = trim($request->new_category_name);
            $newCat = Category::firstOrCreate(
                ['name' => $catName, 'type' => $merchant->type, 'merchant_id' => $merchant->id]
            );
            $validated['category_id'] = $newCat->id;
        } elseif ($request->filled('category_id')) {
            $validated['category_id'] = $request->category_id;
        } else {
            $validated['category_id'] = null;
        }

        $validated['discount_price'] = !empty($validated['discount_price']) ? $validated['discount_price'] : null;
        $validated['merchant_id'] = $merchant->id;
        $validated['is_available'] = $request->has('is_available');
        $validated['is_flash_sale'] = $request->has('is_flash_sale');

        Product::create($validated);

        return redirect()->route('admin.products.index', $merchant->id)->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        $merchant = $product->merchant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable',
            'new_category_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'estimated_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|url',
        ]);

        if ($request->filled('new_category_name')) {
            $catName = trim($request->new_category_name);
            $newCat = Category::firstOrCreate(
                ['name' => $catName, 'type' => $merchant->type, 'merchant_id' => $merchant->id]
            );
            $validated['category_id'] = $newCat->id;
        } elseif ($request->filled('category_id')) {
            $validated['category_id'] = $request->category_id;
        } else {
            $validated['category_id'] = null;
        }

        $validated['discount_price'] = !empty($validated['discount_price']) ? $validated['discount_price'] : null;
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
