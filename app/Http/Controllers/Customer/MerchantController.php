<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function food()
    {
        $categories = Category::where('type', 'food')->get();
        $flashSales = Product::where('is_flash_sale', true)
            ->whereHas('merchant', function($q) { $q->where('type', 'food'); })
            ->with('merchant')
            ->get();
            
        $merchants = Merchant::where('type', 'food')->paginate(10);
        
        return view('customer.food.index', compact('categories', 'flashSales', 'merchants'));
    }

    public function mart(Request $request)
    {
        $categories = Category::where('type', 'mart')->get();
        $query = Product::whereHas('merchant', function($q) { $q->where('type', 'mart'); })
            ->with(['merchant', 'category']);

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products = $query->paginate(12)->withQueryString();
            
        return view('customer.mart.index', compact('categories', 'products'));
    }

    public function show(Merchant $merchant)
    {
        $merchant->load(['products' => function($query) {
            $query->where('is_available', true)->with('category');
        }]);
        
        return view('customer.merchant.show', compact('merchant'));
    }
}
