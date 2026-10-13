<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class MerchantProductController extends Controller
{
    public function index(Request $request)
    {
        $merchant = Auth::guard('merchant')->user();

        $query = $merchant->products()->with('category')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'ready') {
                $query->where('is_available', true);
            } elseif ($request->status === 'empty') {
                $query->where('is_available', false);
            }
        }

        $categories = Category::where('type', $merchant->type)
            ->where(function ($q) use ($merchant) {
                $q->whereNull('merchant_id')->orWhere('merchant_id', $merchant->id);
            })
            ->orderBy('name')
            ->get();

        $products = $query->paginate(12)->withQueryString();

        return view('merchant.products.index', compact('merchant', 'products', 'categories'));
    }

    public function create()
    {
        $merchant = Auth::guard('merchant')->user();
        $categories = Category::where('type', $merchant->type)
            ->where(function ($q) use ($merchant) {
                $q->whereNull('merchant_id')->orWhere('merchant_id', $merchant->id);
            })
            ->orderBy('name')
            ->get();

        return view('merchant.products.create', compact('merchant', 'categories'));
    }

    public function store(Request $request)
    {
        $merchant = Auth::guard('merchant')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable',
            'new_category_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'estimated_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'is_available' => 'nullable|boolean',
            'is_flash_sale' => 'nullable|boolean',
        ], [
            'estimated_price.required' => 'Harga normal / asli wajib diisi.',
            'discount_price.numeric' => 'Harga diskon harus berupa angka.',
            'discount_price.min' => 'Harga diskon minimal 0.',
        ]);

        if (!empty($validated['discount_price']) && (float)$validated['discount_price'] >= (float)$validated['estimated_price']) {
            return back()->withInput()->withErrors([
                'discount_price' => 'Harga diskon harus lebih rendah dari harga normal / asli.',
            ]);
        }

        // Handle on-the-fly category creation by merchant
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

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        Product::create($validated);

        return redirect()->route('merchant.products.index')->with('success', 'Produk berhasil ditambahkan dan langsung aktif di katalog toko customer!');
    }

    public function edit(Product $product)
    {
        $merchant = Auth::guard('merchant')->user();

        if ($product->merchant_id !== $merchant->id) {
            abort(403, 'Akses tidak sah ke produk ini.');
        }

        $categories = Category::where('type', $merchant->type)
            ->where(function ($q) use ($merchant) {
                $q->whereNull('merchant_id')->orWhere('merchant_id', $merchant->id);
            })
            ->orderBy('name')
            ->get();

        return view('merchant.products.edit', compact('merchant', 'product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $merchant = Auth::guard('merchant')->user();

        if ($product->merchant_id !== $merchant->id) {
            abort(403, 'Akses tidak sah ke produk ini.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable',
            'new_category_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'estimated_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ], [
            'estimated_price.required' => 'Harga normal / asli wajib diisi.',
            'discount_price.numeric' => 'Harga diskon harus berupa angka.',
            'discount_price.min' => 'Harga diskon minimal 0.',
        ]);

        if (!empty($validated['discount_price']) && (float)$validated['discount_price'] >= (float)$validated['estimated_price']) {
            return back()->withInput()->withErrors([
                'discount_price' => 'Harga diskon harus lebih rendah dari harga normal / asli.',
            ]);
        }

        // Handle on-the-fly category creation by merchant
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

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $product->update($validated);

        return redirect()->route('merchant.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function toggleStatus(Product $product)
    {
        $merchant = Auth::guard('merchant')->user();

        if ($product->merchant_id !== $merchant->id) {
            abort(403, 'Akses tidak sah ke produk ini.');
        }

        $product->is_available = !$product->is_available;
        $product->save();

        $statusText = $product->is_available ? 'Ready (Tersedia)' : 'Habis (Kosong)';
        return back()->with('success', "Status produk '{$product->name}' diubah menjadi {$statusText}.");
    }

    public function destroy(Product $product)
    {
        $merchant = Auth::guard('merchant')->user();

        if ($product->merchant_id !== $merchant->id) {
            abort(403, 'Akses tidak sah ke produk ini.');
        }

        $product->delete();

        return redirect()->route('merchant.products.index')->with('success', 'Produk berhasil dihapus dari katalog toko Anda.');
    }
}
