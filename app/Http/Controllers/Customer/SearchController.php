<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Definisi 4 Layanan Belanja Jastip Utama
     */
    public static array $services = [
        [
            'id' => 'beli-antar',
            'name' => 'Beli-Antar',
            'subtitle' => 'Jastip Kuliner',
            'description' => 'Titip beli aneka makanan & minuman favorit dari restoran, warung, atau kafe pilihan.',
            'url' => '/customer/food',
            'route_name' => 'customer.food',
            'image' => 'images/services/beli-antar.png',
            'badge' => 'Kuliner & Resto',
            'badge_color' => 'bg-rose-50 text-rose-600 border-rose-200',
            'keywords' => ['beli-antar', 'beli antar', 'kuliner', 'makanan', 'food', 'gofood', 'resto', 'restoran', 'warung', 'cafe', 'kopi', 'minuman', 'jajan', 'snack', 'nasi', 'mie', 'bakso', 'ayam', 'bebek', 'sarapan', 'makan siang', 'makan malam', 'cemilan', 'minum'],
        ],
        [
            'id' => 'toko-kirim',
            'name' => 'Belanja Retail',
            'subtitle' => 'Minimarket/Pasar',
            'description' => 'Belanja kebutuhan harian, sembako, dan belanjaan pasar tradisional/supermarket.',
            'url' => '/customer/mart',
            'route_name' => 'customer.mart',
            'image' => 'images/services/toko-kirim.png',
            'badge' => 'Minimarket / Pasar',
            'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200',
            'keywords' => ['belanja retail', 'retail', 'toko kirim', 'toko-kirim', 'toko', 'mart', 'gomart', 'minimarket', 'indomaret', 'alfamart', 'pasar', 'sembako', 'sayur', 'buah', 'daging', 'ikan', 'supermarket', 'belanja bulanan', 'kebutuhan harian', 'kelontong', 'hypermart', 'superindo', 'belanjaan'],
        ],
        [
            'id' => 'ambil-antar',
            'name' => 'Ambil & Antar',
            'subtitle' => 'Barang & Dokumen',
            'description' => 'Jasa jemput dan antar barang titipan, dokumen penting, atau barang belanjaan antar titik.',
            'url' => '/customer/orders/create?cat=ambil-antar',
            'route_name' => 'customer.orders.create',
            'route_params' => ['cat' => 'ambil-antar'],
            'image' => 'images/services/ambil-antar.png',
            'badge' => 'Barang & Dokumen',
            'badge_color' => 'bg-sky-50 text-sky-700 border-sky-200',
            'keywords' => ['ambil & antar', 'ambil dan antar', 'ambil antar', 'ambil-antar', 'barang', 'dokumen', 'paket', 'titip jemput', 'antar barang', 'jemput barang', 'kirim barang', 'kurir instan', 'laundry', 'titip antar', 'surat', 'berkas', 'kunci', 'baju', 'titipan', 'titip kirim'],
        ],
        [
            'id' => 'kirim-pihak-ketiga',
            'name' => 'Pihak Ketiga',
            'subtitle' => 'Ekspedisi/Agen',
            'description' => 'Bantuan antar paket titipan ke agen ekspedisi resmi, bus, travel, atau kargo.',
            'url' => '/customer/orders/create?cat=kirim-pihak-ketiga',
            'route_name' => 'customer.orders.create',
            'route_params' => ['cat' => 'kirim-pihak-ketiga'],
            'image' => 'images/services/kirim-pihak-ketiga.png',
            'badge' => 'Ekspedisi & Kargo',
            'badge_color' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'keywords' => ['pihak ketiga', 'kirim pihak ketiga', 'pihak-ketiga', 'ekspedisi', 'agen', 'jne', 'jnt', 'j&t', 'sicepat', 'pos indonesia', 'pos', 'shopee express', 'shopee xpress', 'kargo', 'cargo', 'travel', 'bus', 'terminal', 'stasiun', 'titip paket', 'agen paket'],
        ],
    ];

    public function index(Request $request)
    {
        $query = trim($request->input('q', ''));
        $allServices = collect(self::$services);
        
        // If empty query, show the search discovery page with all 4 services
        if (empty($query)) {
            return view('customer.search', compact('allServices'));
        }

        $qLower = strtolower($query);

        // Search Matching Services (4 Layanan Utama)
        $matchedServices = $allServices->filter(function ($svc) use ($qLower) {
            if (str_contains(strtolower($svc['name']), $qLower)) return true;
            if (str_contains(strtolower($svc['subtitle']), $qLower)) return true;
            if (str_contains(strtolower($svc['description']), $qLower)) return true;
            foreach ($svc['keywords'] as $kw) {
                if (str_contains(strtolower($kw), $qLower) || str_contains($qLower, strtolower($kw))) {
                    return true;
                }
            }
            return false;
        })->values();

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

        return view('customer.search', compact('merchants', 'products', 'matchedServices', 'allServices', 'query'));
    }

    /**
     * Endpoint API Live Autocomplete Search untuk dropdown instan (Layanan, Toko, Produk)
     */
    public function liveSearch(Request $request)
    {
        $query = trim($request->input('q', ''));
        if (mb_strlen($query) < 1) {
            return response()->json([
                'query' => '',
                'services' => [],
                'merchants' => [],
                'products' => [],
                'total' => 0,
            ]);
        }

        $qLower = strtolower($query);

        // 1. Cocokkan 4 Layanan Belanja Jastip Utama (Beli-Antar, Belanja Retail, Ambil-Antar, Ekspedisi)
        $matchedServices = collect(self::$services)->filter(function ($svc) use ($qLower) {
            if (str_contains(strtolower($svc['name']), $qLower)) return true;
            if (str_contains(strtolower($svc['subtitle']), $qLower)) return true;
            if (str_contains(strtolower($svc['description']), $qLower)) return true;
            foreach ($svc['keywords'] as $kw) {
                if (str_contains(strtolower($kw), $qLower) || str_contains($qLower, strtolower($kw))) {
                    return true;
                }
            }
            return false;
        })->map(function ($svc) {
            return [
                'id' => $svc['id'],
                'name' => $svc['name'],
                'subtitle' => $svc['subtitle'],
                'description' => $svc['description'],
                'badge' => $svc['badge'],
                'badge_color' => $svc['badge_color'],
                'url' => url($svc['url']),
                'image' => asset($svc['image']),
            ];
        })->values()->take(4);

        // 2. Cocokkan Mitra Toko / Merchant (Kuliner & Mart)
        $merchants = Merchant::where('is_active', true)
            ->where(function ($sub) use ($query) {
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            })
            ->take(3)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'type' => $m->type ?? 'food',
                    'type_label' => ($m->type === 'mart' ? 'Minimarket / Retail' : 'Restoran / Kuliner'),
                    'address' => $m->address ?: 'Area Malang Raya & Sekitarnya',
                    'initials' => strtoupper(substr($m->name, 0, 2)),
                    'url' => route('customer.merchant.show', $m->id),
                ];
            });

        // 3. Cocokkan Produk / Menu Makanan
        $products = Product::where('is_available', true)
            ->where(function ($sub) use ($query) {
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('merchant')
            ->take(4)
            ->get()
            ->map(function ($p) {
                $finalPrice = (float) ($p->final_price ?? $p->estimated_price);
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'merchant_name' => $p->merchant ? $p->merchant->name : 'Mitra Toko',
                    'price' => $finalPrice,
                    'formatted_price' => 'Rp ' . number_format($finalPrice, 0, ',', '.'),
                    'image' => $p->image_url ?? $p->image ?? null,
                    'initials' => strtoupper(substr($p->name, 0, 2)),
                    'url' => $p->merchant_id ? route('customer.merchant.show', $p->merchant_id) : route('customer.search', ['q' => $p->name]),
                ];
            });

        $total = $matchedServices->count() + $merchants->count() + $products->count();

        return response()->json([
            'query' => $query,
            'services' => $matchedServices,
            'merchants' => $merchants,
            'products' => $products,
            'search_all_url' => route('customer.search', ['q' => $query]),
            'total' => $total,
        ]);
    }

    public function searchPlaces(Request $request)
    {
        $query = trim($request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Normalisasi alias populer & typo umum bahasa Indonesia
        $aliases = [
            'starbuks' => 'starbucks',
            'starbuk' => 'starbucks',
            'indomart' => 'indomaret',
            'indomrt' => 'indomaret',
            'alfa mart' => 'alfamart',
            'alfamrt' => 'alfamart',
            'mekdi' => 'mcdonalds',
            'mc donald' => 'mcdonalds',
            'mcd' => 'mcdonalds',
            'kfc' => 'kfc',
            'hokben' => 'hoka hoka bento',
        ];

        $normalizedQuery = $query;
        foreach ($aliases as $k => $v) {
            $normalizedQuery = preg_replace('/\b' . preg_quote($k, '/') . '\b/i', $v, $normalizedQuery);
        }

        $results = [];

        // 0. Cek kecocokan dengan 4 Layanan Utama JastipKuy
        $qLower = strtolower($query);
        foreach (self::$services as $svc) {
            $isMatch = false;
            if (str_contains(strtolower($svc['name']), $qLower) || str_contains(strtolower($svc['subtitle']), $qLower)) {
                $isMatch = true;
            } else {
                foreach ($svc['keywords'] as $kw) {
                    if (str_contains(strtolower($kw), $qLower)) {
                        $isMatch = true;
                        break;
                    }
                }
            }

            if ($isMatch) {
                $results[] = [
                    'source' => 'service',
                    'id' => $svc['id'],
                    'name' => 'Layanan ' . $svc['name'] . ' (' . $svc['subtitle'] . ')',
                    'display_name' => '🚀 ' . $svc['name'] . ' - ' . $svc['description'],
                    'address' => $svc['description'],
                    'url' => $svc['url'],
                    'type_label' => 'Layanan Utama',
                    'badge' => '🚀 Layanan Jastip',
                    'icon' => '🚀',
                ];
            }
        }

        // 1. Cari Merchant Internal JastipKuy (prioritas utama)
        $merchants = Merchant::where('is_active', true)
            ->where(function ($sub) use ($query, $normalizedQuery) {
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$normalizedQuery}%")
                    ->orWhere('address', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$normalizedQuery}%");
            })
            ->take(4)
            ->get();

        foreach ($merchants as $m) {
            $coords = $m->getCoordinates();
            $results[] = [
                'source' => 'merchant',
                'id' => $m->id,
                'name' => $m->name,
                'display_name' => $m->name . ' - ' . ($m->address ?: 'Jawa Timur'),
                'address' => $m->address ?: ($m->name . ', Jawa Timur'),
                'lat' => (float) $coords['lat'],
                'lng' => (float) $coords['lng'],
                'type_label' => 'Mitra ' . ucfirst($m->type ?: 'Toko'),
                'badge' => '🏪 Mitra JastipKuy',
                'icon' => ($m->type === 'food') ? '🍔' : '🏪',
            ];
        }

        // 2. Cari dari OpenStreetMap Nominatim (dengan caching 2 jam)
        $cacheKey = 'places_search_osm_' . md5(strtolower($normalizedQuery));
        $osmPlaces = \Illuminate\Support\Facades\Cache::remember($cacheKey, 7200, function () use ($normalizedQuery) {
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(2.5)
                    ->withHeaders(['User-Agent' => 'JastipKuyApp/1.0 (info@jastipkuy.com)'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $normalizedQuery,
                        'format' => 'json',
                        'limit' => 5,
                        'countrycodes' => 'id',
                        'addressdetails' => 1,
                    ]);

                if ($res->successful()) {
                    $data = $res->json();
                    if (!empty($data)) {
                        return $data;
                    }
                }

                // Fallback: jika tidak ada hasil dan belum ada nama wilayah, coba tambahkan 'Jawa Timur'
                if (!preg_match('/(surabaya|sidoarjo|malang|gresik|jawa|pasuruan)/i', $normalizedQuery)) {
                    $resFallback = \Illuminate\Support\Facades\Http::timeout(2.5)
                        ->withHeaders(['User-Agent' => 'JastipKuyApp/1.0 (info@jastipkuy.com)'])
                        ->get('https://nominatim.openstreetmap.org/search', [
                            'q' => $normalizedQuery . ' Jawa Timur',
                            'format' => 'json',
                            'limit' => 4,
                            'countrycodes' => 'id',
                            'addressdetails' => 1,
                        ]);
                    if ($resFallback->successful()) {
                        return $resFallback->json();
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Nominatim search failed: ' . $e->getMessage());
            }

            return [];
        });

        if (is_array($osmPlaces)) {
            foreach ($osmPlaces as $p) {
                $addr = $p['address'] ?? [];
                
                // Buat ringkasan alamat yang manusiawi & rapi
                $road = $addr['road'] ?? $addr['pedestrian'] ?? '';
                $houseNumber = $addr['house_number'] ?? '';
                $village = $addr['village'] ?? $addr['suburb'] ?? $addr['neighbourhood'] ?? '';
                $city = $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['county'] ?? '';
                $state = $addr['state'] ?? 'Jawa Timur';

                $shortAddrParts = array_filter([
                    trim($road . ' ' . $houseNumber),
                    $village,
                    $city,
                    $state,
                ]);
                $cleanAddress = !empty($shortAddrParts) ? implode(', ', $shortAddrParts) : ($p['display_name'] ?? '');

                // Nama tempat utama
                $placeName = $p['name'] ?: ($addr['amenity'] ?? $addr['shop'] ?? $addr['building'] ?? strtok($cleanAddress, ','));

                // Tentukan icon berdasarkan amenity/shop
                $type = $p['type'] ?? '';
                $icon = '📍';
                if (in_array($type, ['cafe', 'coffee_shop', 'restaurant', 'fast_food'])) {
                    $icon = '☕';
                } elseif (in_array($type, ['supermarket', 'convenience', 'grocery', 'mall'])) {
                    $icon = '🛒';
                }

                $results[] = [
                    'source' => 'osm',
                    'id' => $p['place_id'] ?? null,
                    'name' => $placeName,
                    'display_name' => $placeName . ' (' . ($city ?: $village ?: 'Maps') . ')',
                    'address' => $cleanAddress,
                    'lat' => (float) $p['lat'],
                    'lng' => (float) $p['lon'],
                    'type_label' => ucfirst($p['type'] ?? 'Lokasi'),
                    'badge' => '📍 Lokasi Maps',
                    'icon' => $icon,
                ];
            }
        }

        return response()->json($results);
    }
}
