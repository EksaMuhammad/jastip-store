<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function food(Request $request)
    {
        $categories = Category::where('type', 'food')->get();
        $selectedCategoryId = $request->query('category_id');
        $selectedCategory = $selectedCategoryId ? Category::find($selectedCategoryId) : null;

        $flashSales = Product::where('is_flash_sale', true)
            ->whereHas('merchant', function($q) { $q->where('type', 'food'); })
            ->with('merchant')
            ->get();
            
        $merchantsQuery = Merchant::where('type', 'food');
        $categoryProducts = collect();

        if ($selectedCategory) {
            // Ambil produk yang berkategori ini atau berhubungan
            $categoryProducts = Product::where('category_id', $selectedCategory->id)
                ->where('is_available', true)
                ->whereHas('merchant', fn($q) => $q->where('type', 'food'))
                ->with('merchant')
                ->take(12)
                ->get();

            // Fallback: cari berdasarkan kata kunci nama kategori jika relasi belum terhubung
            if ($categoryProducts->isEmpty()) {
                $categoryProducts = Product::where(function($q) use ($selectedCategory) {
                    $q->where('name', 'like', "%{$selectedCategory->name}%")
                      ->orWhere('description', 'like', "%{$selectedCategory->name}%");
                })
                ->where('is_available', true)
                ->whereHas('merchant', fn($q) => $q->where('type', 'food'))
                ->with('merchant')
                ->take(12)
                ->get();
            }

            // Filter toko yang punya produk terkait atau nama toko cocok
            $merchantsQuery->where(function($q) use ($selectedCategory) {
                $q->whereHas('products', function($pq) use ($selectedCategory) {
                    $pq->where('category_id', $selectedCategory->id)
                      ->orWhere('name', 'like', "%{$selectedCategory->name}%")
                      ->orWhere('description', 'like', "%{$selectedCategory->name}%");
                })
                ->orWhere('name', 'like', "%{$selectedCategory->name}%")
                ->orWhere('description', 'like', "%{$selectedCategory->name}%");
            });
        }

        $merchants = $merchantsQuery->paginate(10)->withQueryString();
        
        return view('customer.food.index', compact('categories', 'flashSales', 'merchants', 'selectedCategory', 'categoryProducts'));
    }

    public function nearby(Request $request)
    {
        $categories = Category::where('type', 'food')->get();
        
        // Ambil koordinat GPS customer jika ada dari input atau session
        if ($request->filled('lat') && $request->filled('lng')) {
            session([
                'customer_lat' => (float) $request->lat,
                'customer_lng' => (float) $request->lng,
            ]);
        }
        $custLat = $request->filled('lat') ? (float) $request->lat : session('customer_lat');
        $custLng = $request->filled('lng') ? (float) $request->lng : session('customer_lng');

        // Fallback otomatis ke koordinat pengantaran pesanan terakhir jika ada
        if (empty($custLat) || empty($custLng)) {
            $customer = auth('customer')->user();
            if ($customer) {
                $lastOrder = \App\Models\Order::where('customer_id', $customer->id)
                    ->whereNotNull('destination_lat')
                    ->whereNotNull('destination_lng')
                    ->latest()
                    ->first();
                if ($lastOrder) {
                    $custLat = (float) $lastOrder->destination_lat;
                    $custLng = (float) $lastOrder->destination_lng;
                }
            }
        }

        $query = Merchant::where('type', 'food')
            ->with(['products' => function($q) {
                $q->where('is_available', true)->take(2);
            }]);

        if ($request->filled('q')) {
            $searchTerm = '%' . $request->input('q') . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('address', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('products', function($q) use ($request) {
                $q->where('category_id', $request->input('category_id'));
            });
        }

        $allMerchants = $query->get();

        // Hitung jarak HANYA jika ada koordinat maps customer dan koordinat resto yang valid
        $hasCustomerLocation = !empty($custLat) && !empty($custLng);

        $merchantsWithDistance = $allMerchants->map(function($merchant) use ($custLat, $custLng, $hasCustomerLocation) {
            $coords = $merchant->getCoordinates();
            $hasCoords = !empty($coords['lat']) && !empty($coords['lng']);

            // Deteksi kota merchant dari alamat/nama
            $addr = strtolower(($merchant->address ?? '') . ' ' . ($merchant->name ?? ''));
            if (str_contains($addr, 'sidoarjo')) {
                $merchant->city_name = 'Sidoarjo';
            } elseif (str_contains($addr, 'surabaya')) {
                $merchant->city_name = 'Surabaya';
            } elseif (str_contains($addr, 'malang')) {
                $merchant->city_name = 'Malang';
            } else {
                $merchant->city_name = null;
            }

            if ($hasCustomerLocation && $hasCoords) {
                $distKm = \App\Services\DistanceService::haversine($custLat, $custLng, (float) $coords['lat'], (float) $coords['lng']);
                $merchant->distance_km = $distKm;
                $merchant->has_real_distance = true;
                if ($distKm < 1.0) {
                    $merchant->distance_formatted = round($distKm * 1000) . ' m';
                } else {
                    $merchant->distance_formatted = number_format($distKm, 1) . ' km';
                }
            } else {
                $merchant->distance_km = null;
                $merchant->has_real_distance = false;
                $merchant->distance_formatted = null;
            }

            return $merchant;
        });

        // Deteksi nama area/kota customer berdasarkan koordinat
        $detectedArea = null;
        if ($hasCustomerLocation) {
            if ($custLat >= -7.60 && $custLat <= -7.36 && $custLng >= 112.50 && $custLng <= 112.85) {
                $detectedArea = 'Sidoarjo';
            } elseif ($custLat >= -7.36 && $custLat <= -7.15 && $custLng >= 112.60 && $custLng <= 112.85) {
                $detectedArea = 'Surabaya';
            } elseif ($custLat >= -8.15 && $custLat <= -7.85 && $custLng >= 112.50 && $custLng <= 112.75) {
                $detectedArea = 'Malang';
            } else {
                $detectedArea = 'Jawa Timur';
            }
        }

        $activeTab = $request->query('tab', 'terdekat');

        // Filter radius jarak:
        // Untuk tab 'terdekat', gunakan batas maksimal radius 20 km (atau filter lebih kecil jika dipilih)
        // Pedagang di luar 20 km tidak dimasukkan ke dalam daftar 'terdekat', tetap diurutkan dari yang paling dekat
        if ($hasCustomerLocation) {
            $maxRadius = 20.0;
            if ($request->filled('max_dist')) {
                $maxRadius = min($maxRadius, (float) $request->input('max_dist'));
            }

            if ($activeTab === 'terdekat' || $request->filled('max_dist')) {
                $merchantsWithDistance = $merchantsWithDistance->filter(function($m) use ($maxRadius) {
                    return $m->distance_km !== null && $m->distance_km <= $maxRadius;
                });
            }
        }

        if ($activeTab === 'terdekat') {
            // Urutkan berdasarkan jarak nyata dari terdekat jika ada koordinat
            if ($hasCustomerLocation) {
                $merchantsWithDistance = $merchantsWithDistance->sortBy(function($m) {
                    return $m->distance_km ?? 999999;
                });
            } else {
                $merchantsWithDistance = $merchantsWithDistance->sortByDesc('latitude');
            }
        } elseif ($activeTab === 'terlaris') {
            $merchantsWithDistance = $merchantsWithDistance->sortByDesc(fn($m) => $m->products->count());
        } elseif ($activeTab === 'rating') {
            $merchantsWithDistance = $merchantsWithDistance->sortBy('id');
        } else { // terkait
            if ($hasCustomerLocation) {
                $merchantsWithDistance = $merchantsWithDistance->sortBy(function($m) {
                    return $m->distance_km ?? 999999;
                });
            }
        }

        // Paginate manual collection
        $page = (int) $request->input('page', 1);
        $perPage = 12;
        $items = $merchantsWithDistance->forPage($page, $perPage)->values();
        $merchants = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $merchantsWithDistance->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('customer.food.nearby', compact('categories', 'merchants', 'activeTab', 'hasCustomerLocation', 'custLat', 'custLng', 'detectedArea'));
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
