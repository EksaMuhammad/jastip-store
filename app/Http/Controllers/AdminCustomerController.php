<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    /**
     * Menampilkan daftar data pelanggan (Customer).
     */
    public function index(Request $request)
    {
        $search = trim($request->query('search', ''));
        $hasOrders = $request->query('has_orders', 'all');

        $query = Customer::with(['wallet'])
            ->withCount(['orders', 'ratings'])
            ->withSum(['orders as total_spent' => function ($q) {
                $q->where('status', 'selesai');
            }], 'agreed_fare')
            ->latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($hasOrders === 'yes') {
            $query->has('orders');
        } elseif ($hasOrders === 'no') {
            $query->doesntHave('orders');
        }

        $customers = $query->paginate(15)->withQueryString();

        $totalCustomersCount = Customer::count();
        $activeCustomersCount = Customer::has('orders')->count();

        return view('admin.customers.index', compact(
            'customers', 
            'search', 
            'hasOrders',
            'totalCustomersCount',
            'activeCustomersCount'
        ));
    }
}
