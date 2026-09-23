<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Http\Request;

class AdminPromoController extends Controller
{
    public function index()
    {
        $promos = Promo::latest()->get();
        return view('admin.promos.index', compact('promos'));
    }

    public function create()
    {
        return view('admin.promos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:flyer,ads',
            'badge_1' => 'nullable|string|max:255',
            'badge_2' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'terms' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('promos', 'public');
        }

        Promo::create($data);

        return redirect()->route('admin.promos.index')->with('success', 'Promo berhasil ditambahkan');
    }

    public function edit(Promo $promo)
    {
        return view('admin.promos.edit', compact('promo'));
    }

    public function update(Request $request, Promo $promo)
    {
        $data = $request->validate([
            'type' => 'required|in:flyer,ads',
            'badge_1' => 'nullable|string|max:255',
            'badge_2' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'terms' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('promos', 'public');
        }

        $promo->update($data);

        return redirect()->route('admin.promos.index')->with('success', 'Promo berhasil diperbarui');
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();
        return redirect()->route('admin.promos.index')->with('success', 'Promo berhasil dihapus');
    }
}
