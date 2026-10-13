<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantDashboardController extends Controller
{
    public function index()
    {
        $merchant = Auth::guard('merchant')->user();
        $products = $merchant->products()->latest()->take(5)->get();
        $totalProducts = $merchant->products()->count();
        $availableProducts = $merchant->products()->where('is_available', true)->count();
        $emptyProducts = $merchant->products()->where('is_available', false)->count();

        return view('merchant.dashboard', compact('merchant', 'products', 'totalProducts', 'availableProducts', 'emptyProducts'));
    }

    public function profile()
    {
        $merchant = Auth::guard('merchant')->user();
        return view('merchant.profile', compact('merchant'));
    }

    public function updateProfile(Request $request)
    {
        $merchant = Auth::guard('merchant')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone_number' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('merchants', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $merchant->update($validated);

        return back()->with('success', 'Profil toko berhasil diperbarui!');
    }
}
