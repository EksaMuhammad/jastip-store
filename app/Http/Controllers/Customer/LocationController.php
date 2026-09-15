<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'location' => 'required|string|max:255',
        ]);

        session(['customer_location' => $request->location]);

        return redirect()->back()->with('success', 'Lokasi berhasil diperbarui.');
    }
}
