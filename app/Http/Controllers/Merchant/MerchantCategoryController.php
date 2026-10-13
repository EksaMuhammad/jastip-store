<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class MerchantCategoryController extends Controller
{
    /**
     * Tampilkan daftar kategori yang tersedia untuk merchant (kategori bawaan + custom toko).
     */
    public function index()
    {
        $merchant = Auth::guard('merchant')->user();

        $categories = Category::where('type', $merchant->type)
            ->where(function ($q) use ($merchant) {
                $q->whereNull('merchant_id')
                  ->orWhere('merchant_id', $merchant->id);
            })
            ->withCount(['products' => function ($query) use ($merchant) {
                $query->where('merchant_id', $merchant->id);
            }])
            ->orderByRaw('CASE WHEN merchant_id IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('name', 'asc')
            ->get();

        return view('merchant.categories.index', compact('merchant', 'categories'));
    }

    /**
     * Simpan kategori baru untuk merchant ini.
     */
    public function store(Request $request)
    {
        $merchant = Auth::guard('merchant')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 50 karakter.',
        ]);

        $name = trim($validated['name']);

        // Periksa apakah kategori dengan nama serupa sudah ada untuk merchant ini atau global
        $existing = Category::where('type', $merchant->type)
            ->where(function ($q) use ($merchant) {
                $q->whereNull('merchant_id')
                  ->orWhere('merchant_id', $merchant->id);
            })
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            return back()->with('error', "Kategori '{$existing->name}' sudah tersedia dalam daftar.");
        }

        Category::create([
            'name' => $name,
            'type' => $merchant->type,
            'merchant_id' => $merchant->id,
        ]);

        return back()->with('success', "Kategori '{$name}' berhasil ditambahkan! Sekarang bisa langsung dipilih saat menambah/mengedit produk.");
    }

    /**
     * Perbarui nama kategori milik merchant.
     */
    public function update(Request $request, Category $category)
    {
        $merchant = Auth::guard('merchant')->user();

        // Validasi kepemilikan kategori
        if ($category->merchant_id !== $merchant->id) {
            return back()->with('error', 'Kategori sistem bawaan tidak dapat diubah.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 50 karakter.',
        ]);

        $name = trim($validated['name']);
        $category->update(['name' => $name]);

        return back()->with('success', "Kategori berhasil diperbarui menjadi '{$name}'.");
    }

    /**
     * Hapus kategori milik merchant.
     */
    public function destroy(Category $category)
    {
        $merchant = Auth::guard('merchant')->user();

        // Validasi kepemilikan kategori
        if ($category->merchant_id !== $merchant->id) {
            return back()->with('error', 'Kategori sistem bawaan tidak dapat dihapus.');
        }

        // Lepaskan relasi kategori dari produk toko ini sebelum dihapus
        Product::where('merchant_id', $merchant->id)
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $name = $category->name;
        $category->delete();

        return back()->with('success', "Kategori '{$name}' berhasil dihapus.");
    }
}
