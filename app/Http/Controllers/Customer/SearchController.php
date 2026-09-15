<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        
        // If empty query, show the search discovery page
        if (empty($query)) {
            return view('customer.search');
        }

        // Search Merchants
        $merchants = Merchant::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->take(5)
            ->get();
            
        // Search Products
        $products = Product::with('merchant')
            ->where('name', 'like', "%{$query}%")
            ->take(10)
            ->get();

        return view('customer.search', compact('merchants', 'products', 'query'));
    }
}
