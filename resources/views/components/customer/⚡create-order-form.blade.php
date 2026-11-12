<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Order;
use App\Models\Wilayah;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    use WithFileUploads;

    // Form inputs
    public string $category = 'beli-antar'; // beli-antar, ambil-antar, toko-kirim, dokumen, multi-stop, kirim-pihak-ketiga
    public string $weight_category = 'ringan'; // ringan, sedang, berat
    public string $description = '';
    public string $store_name = ''; // Nama warung / toko yang di-request customer
    public float $estimated_item_price = 0.0; // Estimasi harga barang untuk beli-antar/toko-kirim
    public $reference_photo;
    
    public string $origin_address = '';
    public string $destination_address = '';
    public string $recipient_name = '';
    public string $recipient_phone = '';

    // Toko rujukan dari keranjang saat ini (misal Indomaret jika ada belanjaan katalog)
    public ?int $cart_reference_merchant_id = null;
    public ?string $cart_reference_merchant_name = null;
    public ?string $cart_reference_merchant_address = null;
    public ?float $cart_reference_merchant_lat = null;
    public ?float $cart_reference_merchant_lng = null;

    // Alamat tujuan dari item pertama di keranjang (Single Drop-off Terpadu)
    public bool $has_cart_destination = false;
    public ?string $cart_first_item_name = null;

    // Multi-Stop specific inputs
    // Multi-Stop specific inputs (mendukung multi-pickup dan multi-dropoff ke tempat berbeda)
    public array $stops = [
        ['action' => 'pickup', 'address' => '', 'notes' => '', 'lat' => null, 'lng' => null, 'recipient_name' => '', 'recipient_phone' => ''],
        ['action' => 'pickup', 'address' => '', 'notes' => '', 'lat' => null, 'lng' => null, 'recipient_name' => '', 'recipient_phone' => ''],
    ];

    // Ekspedisi Pihak Ketiga specific inputs
    public string $courier_agency = 'JNE'; // JNE, J&T, SiCepat, Wahana, POS Indonesia, dll

    // Koordinat pin peta (diisi otomatis oleh JS Leaflet/Nominatim, jangan diisi manual lewat form)
    public ?float $origin_lat = null;
    public ?float $origin_lng = null;
    public ?float $destination_lat = null;
    public ?float $destination_lng = null;

    // Calculation states
    public float $distance = 1.0; // in KM
    public float $estimated_fare = 0.0;

    // Direct booking states
    public ?int $jastiper_id = null;
    public ?string $direct_jastiper_name = null;

    // Customer saved addresses
    public array $saved_addresses = [];
    public ?string $selected_saved_address_id = null;

    // Messages
    public string $success_message = '';
    public string $error_message = '';

    public function addStop()
    {
        if (count($this->stops) < 5) {
            $this->stops[] = ['action' => 'pickup', 'address' => '', 'notes' => '', 'lat' => null, 'lng' => null, 'recipient_name' => '', 'recipient_phone' => ''];
            $this->calculateFare();
        }
    }

    public function removeStop(int $index)
    {
        if (count($this->stops) > 2) {
            unset($this->stops[$index]);
            $this->stops = array_values($this->stops);
            $this->calculateFare();
        }
    }

    public function setStopAction(int $index, string $action)
    {
        if (isset($this->stops[$index])) {
            $this->stops[$index]['action'] = in_array($action, ['pickup', 'dropoff']) ? $action : 'pickup';
            $this->calculateFare();
        }
    }

    protected function rules()
    {
        $rules = [
            'category' => ['required', 'in:beli-antar,ambil-antar,toko-kirim,dokumen,multi-stop,kirim-pihak-ketiga'],
            'weight_category' => ['required', 'in:ringan,sedang,berat'],
            'description' => ['required', 'string', 'min:5'],
            'estimated_item_price' => ['nullable', 'numeric', 'min:0'],
            'destination_address' => ['required', 'string', 'min:5'],
            'recipient_name' => ['required', 'string', 'min:2'],
            'recipient_phone' => ['required', 'string', 'min:9'],
            'reference_photo' => ['nullable', 'image', 'max:25600'], // Max 25MB untuk kamera HP modern
        ];

        if (in_array($this->category, ['beli-antar', 'ambil-antar', 'toko-kirim'])) {
            $rules['origin_address'] = ['required', 'string', 'min:3'];
        } elseif ($this->category === 'multi-stop') {
            $rules['stops.*.address'] = ['required', 'string', 'min:3'];
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'description.required' => 'Deskripsi pesanan wajib diisi.',
            'description.min' => 'Deskripsi minimal berisi 5 karakter.',
            'origin_address.required' => 'Alamat asal/toko wajib diisi.',
            'stops.*.address.required' => 'Semua titik alamat pemberhentian multi-stop wajib diisi.',
            'destination_address.required' => 'Alamat tujuan pengantaran wajib diisi.',
            'destination_address.min' => 'Alamat tujuan minimal berisi 5 karakter.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'recipient_phone.required' => 'No HP penerima wajib diisi.',
            'reference_photo.max' => 'Ukuran foto maksimal 25MB.',
        ];
    }

    public function mount($jastiper_id = null, $direct_jastiper_name = null)
    {
        if ($jastiper_id) {
            $this->jastiper_id = (int) $jastiper_id;
            $this->direct_jastiper_name = $direct_jastiper_name ?? '';
        }

        // Auto fill recipient details with customer's own details as default
        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $this->recipient_name = $customer->name;
            $this->recipient_phone = $customer->phone_number;
            $this->saved_addresses = \App\Http\Controllers\DashboardController::getCustomerSavedAddresses($customer);
        } else {
            $this->saved_addresses = session()->get('customer_saved_addresses', []);
        }

        // Cek jika ada request query saved_address_id
        $requestedSavedId = request()->query('saved_address_id');
        if ($requestedSavedId) {
            $this->selectSavedAddress($requestedSavedId);
        }

        // Tangkap parameter query kategori
        $cat = request()->query('cat');
        if ($cat === 'dokumen') {
            $cat = 'ambil-antar';
        }
        if ($cat && in_array($cat, ['beli-antar', 'ambil-antar', 'toko-kirim', 'dokumen', 'multi-stop', 'kirim-pihak-ketiga'])) {
            $this->category = $cat;
        }

        // Tangkap parameter query alamat asal & koordinat dari pencarian dashboard
        $originAddress = request()->query('origin_address');
        if ($originAddress) {
            $this->origin_address = urldecode($originAddress);
        }
        $originLat = request()->query('origin_lat');
        if ($originLat) {
            $this->origin_lat = (float) $originLat;
        }
        $originLng = request()->query('origin_lng');
        if ($originLng) {
            $this->origin_lng = (float) $originLng;
        }

        // Tangkap parameter query booking langsung
        $jastiperId = request()->query('jastiper_id');
        if ($jastiperId) {
            $jastiper = \App\Models\Jastiper::find($jastiperId);
            if ($jastiper) {
                $this->jastiper_id = $jastiper->id;
                $this->direct_jastiper_name = $jastiper->name;

                // Pre-fill lokasi belanja jika dirujuk dari check-in
                $loc = request()->query('location');
                if ($loc) {
                    $this->origin_address = urldecode($loc);
                }
            }
        }

        // Cek apakah customer sudah memiliki belanjaan di keranjang belanja
        if ($customer) {
            $cart = \App\Models\Cart::with(['cartItems.product.merchant'])->where('customer_id', $customer->id)->first();
            if ($cart && $cart->cartItems->isNotEmpty()) {
                // 1. Ambil rujukan toko katalog jika ada
                $catalogItem = $cart->catalogItems->first();
                if ($catalogItem && $catalogItem->product && $catalogItem->product->merchant) {
                    $m = $catalogItem->product->merchant;
                    $this->cart_reference_merchant_id = $m->id;
                    $this->cart_reference_merchant_name = $m->name;
                    $this->cart_reference_merchant_address = $m->address;
                    $coords = $m->getCoordinates();
                    $this->cart_reference_merchant_lat = (float) $coords['lat'];
                    $this->cart_reference_merchant_lng = (float) $coords['lng'];
                }

                // 2. OTOMATIS: Ambil alamat tujuan dari item pertama di keranjang (Single Drop-off)
                $firstWithDest = $cart->cartItems->first(fn($it) => !empty($it->destination_address));
                if ($firstWithDest) {
                    $this->destination_address = $firstWithDest->destination_address;
                    $this->destination_lat = (float) $firstWithDest->destination_lat;
                    $this->destination_lng = (float) $firstWithDest->destination_lng;
                    if (!empty($firstWithDest->recipient_name)) {
                        $this->recipient_name = $firstWithDest->recipient_name;
                    }
                    if (!empty($firstWithDest->recipient_phone)) {
                        $this->recipient_phone = $firstWithDest->recipient_phone;
                    }
                    $this->has_cart_destination = true;
                    $this->cart_first_item_name = $firstWithDest->name;
                }
            }
        }

        // Calculate initial fare
        $this->calculateFare();
    }

    public function useNearCartStore()
    {
        if ($this->cart_reference_merchant_name) {
            $this->origin_address = "Area sekitar {$this->cart_reference_merchant_name}, {$this->cart_reference_merchant_address}";
            $this->origin_lat = $this->cart_reference_merchant_lat;
            $this->origin_lng = $this->cart_reference_merchant_lng;
            $this->calculateFare();
        }
    }

    /**
     * Pilih alamat pengantaran dari daftar alamat tersimpan customer
     */
    public function selectSavedAddress($addressId)
    {
        $this->selected_saved_address_id = (string) $addressId;
        $found = collect($this->saved_addresses)->first(fn($a) => (string)($a['id'] ?? '') === (string)$addressId);
        if ($found) {
            $this->destination_address = $found['address'];
            if (!empty($found['latitude']) && !empty($found['longitude'])) {
                $this->destination_lat = (float) $found['latitude'];
                $this->destination_lng = (float) $found['longitude'];
            }
            $this->calculateFare();
            $this->dispatch('saved-address-selected', [
                'address' => $found['address'],
                'lat' => $this->destination_lat,
                'lng' => $this->destination_lng,
                'label' => $found['label'] ?? '',
            ]);
        }
    }

    public function updated($propertyName)
    {
        $this->calculateFare();
    }

    /**
     * Dipanggil dari JavaScript (Leaflet drag-end / hasil pencarian Nominatim) setiap kali
     * posisi pin Asal atau Tujuan berubah. Menyimpan koordinat ke state Livewire supaya
     * ikut tersimpan ke database saat submitOrder(), dan bisa dipakai untuk filter radius
     * di sisi jastiper nantinya.
     *
     * @param string $point 'origin' atau 'destination'
     * @param float $lat
     * @param float $lng
     */
    public function updateCoordinates(string $point, float $lat, float $lng)
    {
        if ($point === 'origin') {
            $this->origin_lat = $lat;
            $this->origin_lng = $lng;
        } elseif ($point === 'destination') {
            $this->destination_lat = $lat;
            $this->destination_lng = $lng;
        }
    }

    public function updateStopCoordinates(int $index, float $lat, float $lng)
    {
        if (isset($this->stops[$index])) {
            $this->stops[$index]['lat'] = $lat;
            $this->stops[$index]['lng'] = $lng;
            if ($index === 0) {
                $this->origin_lat = $lat;
                $this->origin_lng = $lng;
            }
        }
    }

    /**
     * Hitung tarif estimasi jastip secara real-time berdasarkan pengaturan dinamis Admin
     */
    public function calculateFare()
    {
        $this->estimated_fare = app(\App\Services\TariffService::class)->calculateFare(
            (float) $this->distance,
            (string) $this->category,
            (string) $this->weight_category,
            is_array($this->stops) ? count($this->stops) : 2
        );
    }

    /**
     * Simpan request order / layanan baru ke dalam keranjang belanja
     */
    public function submitOrder()
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login');
        }

        try {
            $photoPath = null;
            if ($this->reference_photo) {
                $photoPath = $this->reference_photo->store('orders/references', 'public');
            }

            // Tentukan origin address
            $originText = $this->origin_address;
            $multiStopsPayload = null;

            if ($this->category === 'multi-stop') {
                $multiStopsPayload = $this->stops;
                // Buat ringkasan titik di origin_address
                $stopTitles = array_map(function($s, $idx) {
                    return "Titik " . ($idx + 1) . ": " . ($s['address'] ?: 'Lokasi');
                }, $this->stops, array_keys($this->stops));
                $originText = implode(' ➔ ', $stopTitles);
                if (!empty($this->stops[0]['lat'])) {
                    $this->origin_lat = (float) $this->stops[0]['lat'];
                    $this->origin_lng = (float) $this->stops[0]['lng'];
                }
            } elseif ($this->category === 'kirim-pihak-ketiga') {
                $originText = $this->origin_address ?: "Konter / Agen {$this->courier_agency}";
            }

            // Jika ini adalah Direct Booking khusus ke satu Jastiper tertentu (dari halaman booking)
            if ($this->jastiper_id) {
                $destStr = strtolower(($this->destination_address ?? '') . ' ' . ($originText ?? ''));
                $targetWilayah = null;
                if (str_contains($destStr, 'surabaya')) {
                    $targetWilayah = Wilayah::where('name', 'like', '%Surabaya%')->first();
                } elseif (str_contains($destStr, 'sidoarjo')) {
                    $targetWilayah = Wilayah::where('name', 'like', '%Sidoarjo%')->first();
                } elseif (str_contains($destStr, 'gresik')) {
                    $targetWilayah = Wilayah::where('name', 'like', '%Gresik%')->first();
                } elseif (str_contains($destStr, 'malang') || str_contains($destStr, 'batu')) {
                    $targetWilayah = Wilayah::where('name', 'like', '%Malang%')->first();
                } else {
                    $targetWilayah = Wilayah::where('name', 'like', '%Jawa Timur%')->first()
                        ?? Wilayah::where('name', 'like', '%Malang%')->first()
                        ?? Wilayah::where('is_active', true)->first();
                }
                $wilayahId = $targetWilayah?->id ?? 11;

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'jastiper_id' => $this->jastiper_id,
                    'wilayah_id' => $wilayahId,
                    'category' => $this->category,
                    'weight_category' => $this->weight_category,
                    'description' => $this->description,
                    'reference_photo' => $photoPath,
                    'origin_address' => $originText,
                    'origin_lat' => $this->origin_lat,
                    'origin_lng' => $this->origin_lng,
                    'destination_address' => $this->destination_address,
                    'destination_lat' => $this->destination_lat,
                    'destination_lng' => $this->destination_lng,
                    'recipient_name' => $this->recipient_name,
                    'recipient_phone' => $this->recipient_phone,
                    'estimated_fare' => $this->estimated_fare,
                    'status' => 'menunggu_tawaran',
                    'courier_agency' => $this->category === 'kirim-pihak-ketiga' ? $this->courier_agency : null,
                    'multi_stops' => $multiStopsPayload,
                ]);

                $targetJastiper = \App\Models\Jastiper::find($this->jastiper_id);
                if ($targetJastiper && $targetJastiper->phone_number) {
                    WhatsAppService::sendMessage(
                        $targetJastiper->phone_number,
                        "Halo {$targetJastiper->name}, ada pesanan Direct Booking baru (#{$order->id}) dari {$customer->name}!"
                    );
                }

                session()->flash('success', "Pesanan berhasil dibuat khusus untuk Jastiper {$this->direct_jastiper_name}! Menunggu konfirmasi.");
                return redirect()->route('customer.dashboard');
            }

            // Dapatkan atau buat Cart aktif untuk customer (Unified Multi-Service Cart)
            $cart = \App\Models\Cart::firstOrCreate(
                ['customer_id' => $customer->id]
            );

            $itemType = in_array($this->category, ['beli-antar', 'toko-kirim']) ? 'custom_buy' : 'delivery_service';

            $categoryTitles = [
                'beli-antar' => 'Titip Beli: ' . \Illuminate\Support\Str::limit($this->description, 35),
                'ambil-antar' => 'Ambil & Antar: ' . \Illuminate\Support\Str::limit($this->description, 35),
                'toko-kirim' => 'Belanja Retail: ' . ($this->origin_address ?: \Illuminate\Support\Str::limit($this->description, 30)),
                'dokumen' => 'Antar Dokumen: ' . \Illuminate\Support\Str::limit($this->description, 35),
                'multi-stop' => 'Multi-Stop: ' . count($this->stops) . ' Titik',
                'kirim-pihak-ketiga' => 'Ekspedisi ' . $this->courier_agency . ': ' . \Illuminate\Support\Str::limit($this->description, 30),
            ];
            $titleName = $categoryTitles[$this->category] ?? 'Layanan Jastip';

            // Auto-register toko/warung baru ke tabel merchants jika belum ada di database
            $createdMerchantId = null;
            $createdStoreName = null;
            $createdProductId = null;

            if (in_array($this->category, ['beli-antar', 'toko-kirim'])) {
                $rawStoreName = trim($this->store_name);
                if (empty($rawStoreName) && !empty($this->origin_address)) {
                    $parts = explode(',', $this->origin_address);
                    $firstPart = trim($parts[0]);
                    if (!str_starts_with(strtolower($firstPart), 'area sekitar')) {
                        $rawStoreName = $firstPart;
                    }
                }

                if (!empty($rawStoreName) && strlen($rawStoreName) >= 2) {
                    $mType = ($this->category === 'beli-antar') ? 'food' : 'mart';
                    $storeAddr = trim($this->origin_address) ?: (str_contains(strtolower($this->origin_address), 'surabaya') ? 'Kota Surabaya' : 'Area Jawa Timur');

                    $matchedMerchant = \App\Models\Merchant::where('name', $rawStoreName)->first();
                    if (!$matchedMerchant) {
                        $defaultLat = str_contains(strtolower($storeAddr), 'surabaya') ? -7.2575 : -7.9839;
                        $defaultLng = str_contains(strtolower($storeAddr), 'surabaya') ? 112.7521 : 112.6214;
                        $matchedMerchant = \App\Models\Merchant::create([
                            'name' => $rawStoreName,
                            'address' => $storeAddr,
                            'type' => $mType,
                            'latitude' => $this->origin_lat ?: $defaultLat,
                            'longitude' => $this->origin_lng ?: $defaultLng,
                            'description' => 'Warung/Toko ditambahkan otomatis dari permintaan belanja pelanggan',
                            'is_active' => true,
                        ]);
                    } elseif ($this->origin_lat && $this->origin_lng && (!$matchedMerchant->latitude || !$matchedMerchant->longitude)) {
                        $matchedMerchant->update([
                            'latitude' => $this->origin_lat,
                            'longitude' => $this->origin_lng,
                        ]);
                    }
                    $createdMerchantId = $matchedMerchant->id;
                    $createdStoreName = $matchedMerchant->name;

                    // Auto-register produk baru dengan harga transaksi sebagai acuan katalog toko
                    $productPrice = (float) ($this->estimated_item_price ?? 0.0);
                    $rawProductName = trim(strtok($this->description, "\n"));
                    if (empty($rawProductName)) {
                        $rawProductName = 'Menu Titip ' . $matchedMerchant->name;
                    } elseif (strlen($rawProductName) > 90) {
                        $rawProductName = \Illuminate\Support\Str::limit($rawProductName, 85);
                    }

                    $matchedProduct = \App\Models\Product::where('merchant_id', $matchedMerchant->id)
                        ->where('name', $rawProductName)
                        ->first();

                    if (!$matchedProduct) {
                        $matchedProduct = \App\Models\Product::create([
                            'merchant_id' => $matchedMerchant->id,
                            'category_id' => null,
                            'name' => $rawProductName,
                            'description' => $this->description,
                            'hpp_price' => $productPrice,
                            'estimated_price' => $productPrice,
                            'image' => $photoPath,
                            'is_available' => true,
                            'is_auto_markup' => false,
                        ]);
                    } elseif ($productPrice > 0 && ($matchedProduct->estimated_price <= 0 || !$matchedProduct->hpp_price)) {
                        $matchedProduct->update([
                            'hpp_price' => $productPrice,
                            'estimated_price' => $productPrice,
                        ]);
                    }

                    if ($matchedProduct) {
                        $createdProductId = $matchedProduct->id;
                    }
                }
            }

            \App\Models\CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $createdProductId,
                'merchant_id' => $createdMerchantId,
                'item_type' => $itemType,
                'category' => $this->category,
                'name' => $titleName,
                'description' => $this->description,
                'estimated_price' => $this->estimated_item_price ?? 0.0,
                'origin_address' => $originText ?: 'Lokasi Jemput / Belanja',
                'origin_lat' => $this->origin_lat,
                'origin_lng' => $this->origin_lng,
                'destination_address' => $this->destination_address,
                'destination_lat' => $this->destination_lat,
                'destination_lng' => $this->destination_lng,
                'recipient_name' => $this->recipient_name,
                'recipient_phone' => $this->recipient_phone,
                'reference_photo' => $photoPath,
                'weight_category' => $this->weight_category,
                'courier_agency' => $this->category === 'kirim-pihak-ketiga' ? $this->courier_agency : null,
                'stops_data' => $multiStopsPayload,
                'quantity' => 1,
            ]);

            // Isi alamat tujuan hanya pada item yang belum memiliki alamat pengantaran/drop-off khusus
            $cart->cartItems()->where(function($q) {
                $q->whereNull('destination_address')->orWhere('destination_address', '');
            })->update([
                'destination_address' => $this->destination_address,
                'destination_lat' => $this->destination_lat,
                'destination_lng' => $this->destination_lng,
                'recipient_name' => $this->recipient_name,
                'recipient_phone' => $this->recipient_phone,
            ]);

            $storeMsg = $createdStoreName ? " Toko '{$createdStoreName}' juga otomatis tersimpan ke direktori toko sistem!" : "";
            session()->flash('success', "Layanan '{$titleName}' berhasil dimasukkan ke keranjang belanja!{$storeMsg}");
            return redirect()->route('customer.cart.index');

        } catch (\Exception $e) {
            Log::error("Error Customer Add Service to Cart: " . $e->getMessage());
            $this->error_message = 'Gagal menyimpan ke keranjang. Silakan coba lagi.';
        }
    }
};
?>

<div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-sm space-y-8">
    
    <div class="border-b border-slate-100 pb-4 mb-6">
        <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Form Request Jastip Baru</h3>
        <p class="text-xs text-slate-400 mt-1">Silakan pilih kategori jastip, isi deskripsi kebutuhan titipan, serta tentukan rute pengantaran.</p>
    </div>

    <!-- Error Alert -->
    @if ($error_message)
        <div class="bg-rose-50 border border-rose-100 p-4 text-rose-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
            <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>{{ $error_message }}</span>
        </div>
    @endif

    <!-- Direct Request Info Banner -->
    @if ($jastiper_id)
        <div class="bg-rose-50 border border-rose-100 p-4 rounded-2xl flex items-center justify-between text-xs shadow-sm mb-6">
            <div class="flex items-center gap-3">
                <div class="p-1.5 bg-rose-100 text-rose-600 rounded-xl shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[8px] text-rose-500 uppercase tracking-widest font-black block">Mode Booking Langsung</span>
                    <p class="text-slate-800 font-bold mt-0.5">Mengirim pesanan langsung ke: <span class="text-rose-600 font-black">{{ $direct_jastiper_name }}</span></p>
                </div>
            </div>
            <button type="button" wire:click="$set('jastiper_id', null)" class="text-[10px] text-slate-400 hover:text-slate-600 font-bold uppercase tracking-wider focus:outline-none flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                <span>Batal & Kirim ke Umum</span>
            </button>
        </div>
    @endif

    <form wire:submit.prevent="submitOrder" class="space-y-6">
        
        <!-- 1. PILIH KATEGORI (4 Layanan Utama JastipKuy) -->
        <div class="space-y-3">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Layanan Jastip</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                
                <!-- Beli-Antar -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'beli-antar')"
                    class="group p-3 sm:p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 sm:h-32 focus:outline-none relative overflow-hidden"
                    style="{{ $category === 'beli-antar' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.08);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="flex items-center justify-start">
                        <img src="{{ asset('images/services/beli-antar.png') }}" alt="Beli-Antar" class="w-12 h-10 sm:w-14 sm:h-12 object-contain group-hover:scale-105 transition-transform drop-shadow-xs">
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Beli-Antar</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5 leading-tight">Jastip Kuliner / Makanan</span>
                    </div>
                </button>

                <!-- Ambil & Antar -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'ambil-antar')"
                    class="group p-3 sm:p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 sm:h-32 focus:outline-none relative overflow-hidden"
                    style="{{ $category === 'ambil-antar' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.08);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="flex items-center justify-start">
                        <img src="{{ asset('images/services/ambil-antar.png') }}" alt="Ambil & Antar" class="w-12 h-10 sm:w-14 sm:h-12 object-contain group-hover:scale-105 transition-transform drop-shadow-xs">
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Ambil & Antar</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5 leading-tight">Barang & Dokumen / COD</span>
                    </div>
                </button>

                <!-- Belanja Retail (Minimarket / Pasar) -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'toko-kirim')"
                    class="group p-3 sm:p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 sm:h-32 focus:outline-none relative overflow-hidden"
                    style="{{ $category === 'toko-kirim' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.08);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="flex items-center justify-start">
                        <img src="{{ asset('images/services/toko-kirim.png') }}" alt="Belanja Retail" class="w-12 h-10 sm:w-14 sm:h-12 object-contain group-hover:scale-105 transition-transform drop-shadow-xs">
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Belanja Retail</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5 leading-tight">Belanja Minimarket / Pasar</span>
                    </div>
                </button>

                <!-- Pihak Ketiga -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'kirim-pihak-ketiga')"
                    class="group p-3 sm:p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 sm:h-32 focus:outline-none relative overflow-hidden"
                    style="{{ $category === 'kirim-pihak-ketiga' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.08);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="flex items-center justify-start">
                        <img src="{{ asset('images/services/kirim-pihak-ketiga.png') }}" alt="Pihak Ketiga" class="w-12 h-10 sm:w-14 sm:h-12 object-contain group-hover:scale-105 transition-transform drop-shadow-xs">
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Pihak Ketiga</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5 leading-tight">Ekspedisi / Agen Kirim</span>
                    </div>
                </button>

            </div>
        </div>

        <!-- 2. DETAIL PESANAN & UPLOADER -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            
            <div class="md:col-span-8 space-y-4">
                <!-- Deskripsi -->
                <div class="space-y-1.5">
                    <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Detail Belanjaan / Deskripsi Barang</label>
                    <textarea 
                        id="description" 
                        wire:model.live="description"
                        rows="3"
                        placeholder="Contoh: Titip Nasi Goreng Gila 1 porsi pedas sedang, es teh manis 1..."
                        class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-3 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs"
                    ></textarea>
                    @error('description') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                </div>

                @if(in_array($category, ['beli-antar', 'toko-kirim']))
                    <div class="space-y-1.5 bg-amber-50/60 border border-amber-200/60 p-3.5 rounded-2xl"
                         x-data="{
                             priceVal: @entangle('estimated_item_price'),
                             displayVal: '',
                             formatRupiah(val) {
                                 if (!val || val == 0) return '';
                                 return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                             },
                             init() {
                                 if (this.priceVal && this.priceVal > 0) {
                                     this.displayVal = this.formatRupiah(this.priceVal);
                                 }
                             },
                             onInput(e) {
                                 let raw = e.target.value.replace(/[^0-9]/g, '');
                                 let num = raw ? parseInt(raw, 10) : 0;
                                 this.priceVal = num;
                                 this.displayVal = num > 0 ? this.formatRupiah(num) : '';
                             }
                         }">
                        <label for="estimated_item_price_input" class="block text-xs font-bold text-amber-900 uppercase tracking-wider">
                            Perkiraan Total Harga Barang Belanjaan (Rp, Opsional)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-xs font-bold text-amber-700">Rp</span>
                            <input 
                                type="text"
                                inputmode="numeric"
                                id="estimated_item_price_input" 
                                x-model="displayVal"
                                @input="onInput($event)"
                                placeholder="35.000 (tulis kisaran total harga barang jika sudah tahu)"
                                class="w-full bg-white border border-amber-200 text-slate-800 pl-10 pr-4 py-2.5 rounded-xl outline-none focus:border-amber-500 transition duration-150 text-xs font-bold"
                            >
                            <span x-show="priceVal > 0" x-cloak class="text-[10px] font-bold text-amber-800 block mt-1">
                                Estimasi Barang: Rp <span x-text="displayVal"></span>
                            </span>
                        </div>
                        <p class="text-[10px] text-amber-700/80 mt-0.5">Estimasi ini akan dihitung pada subtotal belanjaan di keranjang Anda dan dapat disesuaikan bukti nota nanti.</p>
                    </div>
                @endif

                <!-- Alamat Origin & Destination / Multi-Stop -->
                @if($category === 'multi-stop')
                    <!-- KHUSUS MULTI-STOP: Titik Pemberhentian Dinamis -->
                    <div class="bg-purple-50/50 border border-purple-100 rounded-3xl p-4 sm:p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-black text-purple-900 uppercase tracking-wide flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                    Titik Pemberhentian & Belanja Multi-Stop (Maks 5 Titik)
                                </h4>
                                <p class="text-[10px] text-purple-600 mt-0.5">Jastiper akan mampir berurutan ke toko-toko ini sebelum mengantar ke tujuan akhir Anda.</p>
                            </div>
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700">
                                {{ count($stops) }} Titik Singgah
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach($stops as $idx => $stop)
                            @php
                                $isDropoff = ($stop['action'] ?? 'pickup') === 'dropoff';
                            @endphp
                                <div class="bg-white border {{ $isDropoff ? 'border-indigo-200/90 bg-indigo-50/20' : 'border-purple-200/80' }} rounded-2xl p-3.5 shadow-2xs space-y-2.5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-full {{ $isDropoff ? 'bg-indigo-600' : 'bg-purple-600' }} text-white font-black text-[10px] flex items-center justify-center">
                                                {{ $idx + 1 }}
                                            </span>
                                            <span class="text-xs font-bold text-slate-800">
                                                Stop {{ $idx + 1 }}: {{ $isDropoff ? 'Drop-off / Antar Penerima' : 'Toko / Titik Jemput' }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <!-- Action Selector: Pickup vs Dropoff -->
                                            <div class="inline-flex p-0.5 bg-slate-100 rounded-lg border border-slate-200/60">
                                                <button 
                                                    type="button" 
                                                    wire:click="setStopAction({{ $idx }}, 'pickup')" 
                                                    class="px-2 py-1 rounded-md text-[10px] font-bold transition flex items-center gap-1 {{ !$isDropoff ? 'bg-white text-emerald-700 shadow-2xs border border-slate-200/80' : 'text-slate-500 hover:text-slate-800' }}"
                                                >
                                                    <span>🟢 Jemput / Beli</span>
                                                </button>
                                                <button 
                                                    type="button" 
                                                    wire:click="setStopAction({{ $idx }}, 'dropoff')" 
                                                    class="px-2 py-1 rounded-md text-[10px] font-bold transition flex items-center gap-1 {{ $isDropoff ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}"
                                                >
                                                    <span>🔵 Antar Penerima</span>
                                                </button>
                                            </div>

                                            @if(count($stops) > 2)
                                                <button type="button" wire:click="removeStop({{ $idx }})" class="text-[10px] font-bold text-rose-500 hover:text-rose-700 hover:underline">
                                                    ✕ Hapus Stop
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                        <!-- Alamat / Nama Toko atau Alamat Drop-off -->
                                        <div class="sm:col-span-7 flex gap-1.5">
                                            <input 
                                                type="text" 
                                                id="stop_address_{{ $idx }}"
                                                wire:model.live="stops.{{ $idx }}.address"
                                                placeholder="{{ $isDropoff ? 'Alamat tujuan drop-off (mis: Kantor BCA Jl. Basuki Rahmat, Rumah Teman)...' : 'Nama toko / lokasi jemput (mis: Jokopi Surabaya, Kopi Kenangan Dinoyo)...' }}"
                                                class="flex-1 min-w-0 bg-slate-50 border {{ $isDropoff ? 'border-indigo-200 focus:border-indigo-500' : 'border-slate-200 focus:border-purple-500' }} text-slate-800 px-3 py-2 rounded-xl text-xs focus:bg-white outline-none"
                                            >
                                            <button 
                                                type="button"
                                                onclick="searchStopLocation({{ $idx }})"
                                                id="btn_search_stop_{{ $idx }}"
                                                class="shrink-0 {{ $isDropoff ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-purple-600 hover:bg-purple-700' }} text-white text-[10px] font-bold px-2.5 rounded-xl transition whitespace-nowrap"
                                            >
                                                🔍 Cari
                                            </button>
                                        </div>
                                        <!-- Catatan Belanja atau Catatan Antar di Stop ini -->
                                        <div class="sm:col-span-5">
                                            <input 
                                                type="text" 
                                                wire:model.live="stops.{{ $idx }}.notes"
                                                placeholder="{{ $isDropoff ? 'Catatan antar (mis: titip di pos satpam / lobi)...' : 'Barang dibeli/dijemput (mis: 1 Latte Ice)...' }}"
                                                class="w-full bg-slate-50 border {{ $isDropoff ? 'border-indigo-200 focus:border-indigo-500' : 'border-slate-200 focus:border-purple-500' }} text-slate-800 px-3 py-2 rounded-xl text-xs focus:bg-white outline-none"
                                            >
                                        </div>
                                    </div>

                                    @if($isDropoff)
                                    <!-- Informasi Penerima khusus untuk titik Drop-off -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-2.5 bg-indigo-50/70 border border-indigo-200/70 rounded-xl">
                                        <div>
                                            <label class="block text-[10px] font-bold text-indigo-900 mb-0.5">Nama Penerima di Titik Ini</label>
                                            <input 
                                                type="text" 
                                                wire:model.live="stops.{{ $idx }}.recipient_name"
                                                placeholder="Contoh: Pak Budi / Resepsionis"
                                                class="w-full bg-white border border-indigo-200 text-slate-800 px-2.5 py-1.5 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 outline-none"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-indigo-900 mb-0.5">No. WhatsApp Penerima</label>
                                            <input 
                                                type="text" 
                                                wire:model.live="stops.{{ $idx }}.recipient_phone"
                                                placeholder="Contoh: 08123456789"
                                                class="w-full bg-white border border-indigo-200 text-slate-800 px-2.5 py-1.5 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 outline-none"
                                            >
                                        </div>
                                    </div>
                                    @endif

                                    @error('stops.'.$idx.'.address') <span class="text-rose-600 text-[9px] font-semibold block">{{ $message }}</span> @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            @if(count($stops) < 5)
                                <button type="button" wire:click="addStop" class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-700 hover:text-purple-900 bg-white border border-purple-200 px-4 py-2 rounded-xl shadow-2xs hover:bg-purple-50 transition">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    + Tambah Titik Pemberhentian (+Rp 6.000)
                                </button>
                            @else
                                <span class="text-[10px] font-bold text-slate-400 italic">Maksimal 5 titik singgah telah tercapai.</span>
                            @endif

                            <span class="text-[10px] text-purple-700 font-medium">
                                Biaya dasar multi-stop + tambahan titik ke-3 dst.
                            </span>
                        </div>
                    </div>

                    <!-- Destination -->
                    <div class="space-y-1.5" x-data="{ openSavedMulti: false }">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <label for="destination_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Alamat Tujuan Pengantaran Akhir</label>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button 
                                    type="button" 
                                    id="btn-gps-dest-1"
                                    onclick="detectCustomerCurrentLocation()"
                                    class="text-[11px] font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200 transition flex items-center gap-1 cursor-pointer"
                                    title="Gunakan lokasi GPS saya saat ini"
                                >
                                    📍 Lokasi Saya (GPS)
                                </button>

                                @if(!empty($saved_addresses) && count($saved_addresses) > 0)
                                    <div class="relative">
                                        <button 
                                            type="button"
                                            @click="openSavedMulti = !openSavedMulti"
                                            @click.outside="openSavedMulti = false"
                                            class="text-[11px] font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-300 px-2 py-0.5 rounded-lg transition flex items-center gap-1 cursor-pointer"
                                        >
                                            <span>📋 Alamat Tersimpan</span>
                                            <span class="bg-rose-500 text-white text-[9px] font-extrabold px-1 rounded-full">{{ count($saved_addresses) }}</span>
                                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>

                                        <!-- Dropdown List Alamat Tersimpan -->
                                        <div x-show="openSavedMulti" 
                                             x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             class="absolute right-0 top-full mt-1.5 w-72 sm:w-80 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-50 divide-y divide-slate-100">
                                            <div class="px-3 py-2 text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center justify-between">
                                                <span>Pilih Alamat Tersimpan</span>
                                                <a href="{{ route('customer.address') }}" class="text-rose-600 hover:underline capitalize font-bold">+ Kelola</a>
                                            </div>
                                            <div class="max-h-56 overflow-y-auto divide-y divide-slate-50">
                                                @foreach($saved_addresses as $sAddr)
                                                    <button type="button" 
                                                            wire:click="selectSavedAddress('{{ $sAddr['id'] ?? '' }}')"
                                                            @click="openSavedMulti = false"
                                                            class="w-full text-left px-3 py-2 hover:bg-rose-50/70 transition flex items-start gap-2.5 group cursor-pointer">
                                                        <span class="text-sm shrink-0 pt-0.5">
                                                            @if(stripos($sAddr['label'] ?? '', 'kantor') !== false) 🏢
                                                            @elseif(stripos($sAddr['label'] ?? '', 'kos') !== false) 🏠
                                                            @elseif(stripos($sAddr['label'] ?? '', 'toko') !== false) 🏪
                                                            @else 📍 @endif
                                                        </span>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center justify-between gap-1">
                                                                <span class="font-bold text-xs text-slate-800 group-hover:text-rose-600 truncate">{{ $sAddr['label'] }}</span>
                                                                @if(!empty($selected_saved_address_id) && (string)$selected_saved_address_id === (string)($sAddr['id'] ?? ''))
                                                                    <span class="text-[9px] text-emerald-600 font-extrabold bg-emerald-50 px-1 rounded">✓ Dipilih</span>
                                                                @endif
                                                            </div>
                                                            <p class="text-[11px] text-slate-500 line-clamp-1 leading-tight mt-0.5">{{ $sAddr['address'] }}</p>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                            <div class="p-2 bg-slate-50 text-center">
                                                <a href="{{ route('customer.address') }}" class="text-[11px] font-bold text-rose-600 hover:text-rose-700 block">
                                                    + Tambah Alamat Baru
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ route('customer.address') }}" 
                                       class="text-[11px] font-bold text-slate-500 hover:text-rose-600 hover:bg-rose-50 px-2 py-0.5 rounded-lg border border-dashed border-slate-300 transition flex items-center gap-1"
                                       title="Simpan alamat baru untuk pesanan berikutnya">
                                        + Tambah Alamat
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                id="destination_address" 
                                wire:model.live="destination_address"
                                placeholder="Alamat rumah / kos Anda se-Jawa/Indonesia (mis: Rungkut Surabaya, Jl. Ijen Malang)..."
                                class="flex-1 min-w-0 bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs"
                            >
                            <button 
                                type="button"
                                onclick="searchDestinationLocation()"
                                id="btn-search-destination"
                                class="shrink-0 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-3 rounded-2xl transition duration-150 whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                🔍 Cari Lokasi
                            </button>
                        </div>

                        @if(!empty($saved_addresses) && count($saved_addresses) > 0)
                            <!-- Quick Chips Alamat Tersimpan -->
                            <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Pilih Cepat:</span>
                                @foreach(array_slice($saved_addresses, 0, 3) as $sAddr)
                                    <button type="button" 
                                            wire:click="selectSavedAddress('{{ $sAddr['id'] ?? '' }}')"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-lg border transition cursor-pointer {{ (!empty($selected_saved_address_id) && (string)$selected_saved_address_id === (string)($sAddr['id'] ?? '')) ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' }}">
                                        <span>{{ stripos($sAddr['label'] ?? '', 'kantor') !== false ? '🏢' : '🏠' }}</span>
                                        <span>{{ $sAddr['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @error('destination_address') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Origin (Untuk single store/pickup) -->
                        @if(in_array($category, ['beli-antar', 'ambil-antar', 'toko-kirim']))
                            <div class="space-y-3">
                                @if(in_array($category, ['beli-antar', 'toko-kirim']))
                                    <div class="space-y-1 relative" id="store-name-wrapper">
                                        <div class="flex items-center justify-between">
                                            <label for="store_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Warung / Toko</label>
                                            <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">✨ Auto Masuk Data Toko Baru</span>
                                        </div>
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                id="store_name" 
                                                wire:model="store_name"
                                                placeholder="Contoh: Starbucks Surabaya, Indomaret, Hokben, Warung Nasgor..."
                                                autocomplete="off"
                                                oninput="handlePlaceSearch(this.value, 'store_name')"
                                                onfocus="if(this.value && this.value.length >= 2 && (!window.lastSelectedPlaces || window.lastSelectedPlaces['store_name'] !== this.value.trim())) handlePlaceSearch(this.value, 'store_name')"
                                                class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-800 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs font-medium pr-10"
                                            >
                                            <div id="store-search-spinner" class="hidden absolute right-3.5 top-3 pointer-events-none">
                                                <svg class="animate-spin h-3.5 w-3.5 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                            </div>
                                        </div>
                                        <!-- Live Autocomplete Suggestions Dropdown -->
                                        <div wire:ignore id="dropdown-store_name" class="hidden absolute left-0 right-0 top-full mt-1.5 z-50 bg-white rounded-2xl shadow-2xl border border-slate-200 max-h-72 overflow-y-auto divide-y divide-slate-100"></div>
                                    </div>

                                    @if($cart_reference_merchant_name)
                                        <div class="bg-blue-50/80 border border-blue-200/80 rounded-2xl p-2.5 flex items-center justify-between gap-2 shadow-2xs">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="text-base shrink-0">🏪</span>
                                                <div class="min-w-0">
                                                    <span class="text-[9px] text-blue-600 font-bold uppercase tracking-wider block">Ada Belanjaan di Toko:</span>
                                                    <span class="text-xs font-extrabold text-blue-900 truncate block">{{ $cart_reference_merchant_name }}</span>
                                                </div>
                                            </div>
                                            <button 
                                                type="button" 
                                                wire:click="useNearCartStore"
                                                class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-xl shadow-xs transition"
                                                title="Gunakan lokasi dekat toko ini untuk rute yang searah"
                                            >
                                                📍 Beli Dekat Sini
                                            </button>
                                        </div>
                                    @endif
                                @endif

                                <div class="space-y-1.5 relative" id="origin-address-wrapper">
                                    <label for="origin_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        {{ in_array($category, ['beli-antar', 'toko-kirim']) ? 'Alamat / Patokan Warung' : 'Alamat Asal / Penjemputan' }}
                                    </label>
                                    <div class="flex gap-2 relative">
                                        <div class="flex-1 relative">
                                            <input 
                                                type="text" 
                                                id="origin_address" 
                                                wire:model="origin_address"
                                                placeholder="{{ in_array($category, ['beli-antar', 'toko-kirim']) ? 'Nama/patokan warung (mis: Starbucks Surabaya, Jokopi, Indomaret)...' : 'Alamat asal/penjemputan se-Indonesia...' }}"
                                                autocomplete="off"
                                                oninput="handlePlaceSearch(this.value, 'origin_address')"
                                                onfocus="if(this.value && this.value.length >= 2 && (!window.lastSelectedPlaces || window.lastSelectedPlaces['origin_address'] !== this.value.trim())) handlePlaceSearch(this.value, 'origin_address')"
                                                class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs pr-10"
                                            >
                                            <div id="origin-search-spinner" class="hidden absolute right-3.5 top-3 pointer-events-none">
                                                <svg class="animate-spin h-3.5 w-3.5 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                            </div>
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="searchOriginLocation()"
                                            id="btn-search-origin"
                                            class="shrink-0 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-3 rounded-2xl transition duration-150 whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed shadow-xs"
                                        >
                                            🔍 Cari Toko
                                        </button>
                                    </div>

                                    <!-- Live Autocomplete Suggestions Dropdown -->
                                    <div wire:ignore id="dropdown-origin_address" class="hidden absolute left-0 right-0 top-full mt-1.5 z-50 bg-white rounded-2xl shadow-2xl border border-slate-200 max-h-72 overflow-y-auto divide-y divide-slate-100"></div>

                                    @error('origin_address') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @elseif($category === 'kirim-pihak-ketiga')
                            <div class="space-y-1.5">
                                <label for="courier_agency" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Ekspedisi / Kurir Tujuan</label>
                                <select 
                                    id="courier_agency" 
                                    wire:model.live="courier_agency"
                                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs font-semibold"
                                >
                                    <option value="JNE">JNE Express</option>
                                    <option value="J&T">J&T Express</option>
                                    <option value="SiCepat">SiCepat Ekspres</option>
                                    <option value="Wahana">Wahana Express</option>
                                    <option value="POS Indonesia">Kantor Pos Indonesia</option>
                                    <option value="TIKI">TIKI</option>
                                    <option value="Lion Parcel">Lion Parcel</option>
                                    <option value="Anteraja">Anteraja</option>
                                </select>
                                <input 
                                    type="text" 
                                    wire:model.live="origin_address"
                                    placeholder="Cabang / agen spesifik (opsional, mis: JNE Diponegoro Surabaya)..."
                                    class="w-full mt-1.5 bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-[11px]"
                                >
                            </div>
                        @endif

                        <!-- Destination -->
                        <div class="space-y-1.5 relative" id="destination-address-wrapper">
                            @if($has_cart_destination)
                                <div class="bg-amber-50/90 border border-amber-200/90 rounded-2xl p-2.5 flex items-center justify-between gap-2 shadow-2xs mb-1">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-base shrink-0">🎯</span>
                                        <div class="min-w-0">
                                            <span class="text-[9px] text-amber-700 font-bold uppercase tracking-wider block">Tujuan Pengantaran Terpadu:</span>
                                            <span class="text-xs font-extrabold text-amber-950 truncate block">{{ $destination_address }}</span>
                                            <span class="text-[9px] text-amber-700 block">Otomatis mengikuti pesanan pertama di keranjang ({{ $cart_first_item_name }})</span>
                                        </div>
                                    </div>
                                    <span class="shrink-0 bg-amber-200/80 text-amber-900 text-[9px] font-extrabold px-2 py-1 rounded-lg">
                                        ✓ Single Drop-off
                                    </span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between gap-2 flex-wrap" x-data="{ openSavedSingle: false }">
                                <label for="destination_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    {{ $has_cart_destination ? 'Alamat Tujuan Pengantaran (Terpadu)' : 'Alamat Tujuan Pengantaran' }}
                                </label>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <button 
                                        type="button" 
                                        id="btn-gps-dest-2"
                                        onclick="detectCustomerCurrentLocation()"
                                        class="text-[11px] font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200 transition flex items-center gap-1 cursor-pointer"
                                        title="Gunakan lokasi GPS saya saat ini"
                                    >
                                        📍 Lokasi Saya (GPS)
                                    </button>

                                    @if(!empty($saved_addresses) && count($saved_addresses) > 0)
                                        <div class="relative">
                                            <button 
                                                type="button"
                                                @click="openSavedSingle = !openSavedSingle"
                                                @click.outside="openSavedSingle = false"
                                                class="text-[11px] font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-300 px-2 py-0.5 rounded-lg transition flex items-center gap-1 cursor-pointer"
                                            >
                                                <span>📋 Alamat Tersimpan</span>
                                                <span class="bg-rose-500 text-white text-[9px] font-extrabold px-1 rounded-full">{{ count($saved_addresses) }}</span>
                                                <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </button>

                                            <!-- Dropdown List Alamat Tersimpan -->
                                            <div x-show="openSavedSingle" 
                                                 x-cloak
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 class="absolute right-0 top-full mt-1.5 w-72 sm:w-80 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-50 divide-y divide-slate-100">
                                                <div class="px-3 py-2 text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center justify-between">
                                                    <span>Pilih Alamat Tersimpan</span>
                                                    <a href="{{ route('customer.address') }}" class="text-rose-600 hover:underline capitalize font-bold">+ Kelola</a>
                                                </div>
                                                <div class="max-h-56 overflow-y-auto divide-y divide-slate-50">
                                                    @foreach($saved_addresses as $sAddr)
                                                        <button type="button" 
                                                                wire:click="selectSavedAddress('{{ $sAddr['id'] ?? '' }}')"
                                                                @click="openSavedSingle = false"
                                                                class="w-full text-left px-3 py-2 hover:bg-rose-50/70 transition flex items-start gap-2.5 group cursor-pointer">
                                                            <span class="text-sm shrink-0 pt-0.5">
                                                                @if(stripos($sAddr['label'] ?? '', 'kantor') !== false) 🏢
                                                                @elseif(stripos($sAddr['label'] ?? '', 'kos') !== false) 🏠
                                                                @elseif(stripos($sAddr['label'] ?? '', 'toko') !== false) 🏪
                                                                @else 📍 @endif
                                                            </span>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-center justify-between gap-1">
                                                                    <span class="font-bold text-xs text-slate-800 group-hover:text-rose-600 truncate">{{ $sAddr['label'] }}</span>
                                                                    @if(!empty($selected_saved_address_id) && (string)$selected_saved_address_id === (string)($sAddr['id'] ?? ''))
                                                                        <span class="text-[9px] text-emerald-600 font-extrabold bg-emerald-50 px-1 rounded">✓ Dipilih</span>
                                                                    @endif
                                                                </div>
                                                                <p class="text-[11px] text-slate-500 line-clamp-1 leading-tight mt-0.5">{{ $sAddr['address'] }}</p>
                                                            </div>
                                                        </button>
                                                    @endforeach
                                                </div>
                                                <div class="p-2 bg-slate-50 text-center">
                                                    <a href="{{ route('customer.address') }}" class="text-[11px] font-bold text-rose-600 hover:text-rose-700 block">
                                                        + Tambah Alamat Baru
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <a href="{{ route('customer.address') }}" 
                                           class="text-[11px] font-bold text-slate-500 hover:text-rose-600 hover:bg-rose-50 px-2 py-0.5 rounded-lg border border-dashed border-slate-300 transition flex items-center gap-1"
                                           title="Simpan alamat baru untuk pesanan berikutnya">
                                            + Tambah Alamat
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2 relative">
                                <div class="flex-1 relative">
                                    <input 
                                        type="text" 
                                        id="destination_address" 
                                        wire:model="destination_address"
                                        placeholder="Alamat rumah / kos Anda se-Jawa/Indonesia..."
                                        autocomplete="off"
                                        oninput="handlePlaceSearch(this.value, 'destination_address')"
                                        onfocus="if(this.value && this.value.length >= 2 && (!window.lastSelectedPlaces || window.lastSelectedPlaces['destination_address'] !== this.value.trim())) handlePlaceSearch(this.value, 'destination_address')"
                                        class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs pr-10"
                                    >
                                    <div id="dest-search-spinner" class="hidden absolute right-3.5 top-3 pointer-events-none">
                                        <svg class="animate-spin h-3.5 w-3.5 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    </div>
                                </div>
                                <button 
                                    type="button"
                                    onclick="searchDestinationLocation()"
                                    id="btn-search-destination"
                                    class="shrink-0 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-3 rounded-2xl transition duration-150 whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed shadow-xs"
                                >
                                    🔍 Cari Lokasi
                                </button>
                            </div>

                            <!-- Live Autocomplete Suggestions Dropdown -->
                            <div wire:ignore id="dropdown-destination_address" class="hidden absolute left-0 right-0 top-full mt-1.5 z-50 bg-white rounded-2xl shadow-2xl border border-slate-200 max-h-72 overflow-y-auto divide-y divide-slate-100"></div>

                            @if(!empty($saved_addresses) && count($saved_addresses) > 0)
                                <!-- Quick Chips Alamat Tersimpan -->
                                <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Pilih Cepat:</span>
                                    @foreach(array_slice($saved_addresses, 0, 3) as $sAddr)
                                        <button type="button" 
                                                wire:click="selectSavedAddress('{{ $sAddr['id'] ?? '' }}')"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-lg border transition cursor-pointer {{ (!empty($selected_saved_address_id) && (string)$selected_saved_address_id === (string)($sAddr['id'] ?? '')) ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' }}">
                                            <span>{{ stripos($sAddr['label'] ?? '', 'kantor') !== false ? '🏢' : '🏠' }}</span>
                                            <span>{{ $sAddr['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @error('destination_address') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif
            </div>

            <!-- Upload Box & Weight Category -->
            <div class="md:col-span-4 space-y-4">
                <!-- Berat Category -->
                <div class="space-y-1.5">
                    <label for="weight_category" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Estimasi Berat Barang</label>
                    <select 
                        id="weight_category" 
                        wire:model.live="weight_category"
                        class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-3.5 py-3 rounded-2xl text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150"
                    >
                        <option value="ringan">Ringan (Baju, Makanan, Dokumen) - +Rp 0</option>
                        <option value="sedang">Sedang (Dus Sedang, Helm) - +Rp 10.000</option>
                        <option value="berat">Berat (Kardus Besar, Galon) - +Rp 25.000</option>
                    </select>
                </div>

                <!-- Foto Uploader (Pratinjau Instan + Feedback Upload) -->
                <div class="space-y-1.5"
                     x-data="{
                         localPreview: null,
                         fileName: '',
                         fileSize: '',
                         isUploading: false,
                         progress: 0,
                         handleFile(event) {
                             const file = event.target.files && event.target.files[0];
                             if (file) {
                                 this.fileName = file.name;
                                 this.fileSize = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
                                 this.localPreview = URL.createObjectURL(file);
                             }
                         },
                         removePhoto() {
                             this.localPreview = null;
                             this.fileName = '';
                             this.fileSize = '';
                             $wire.set('reference_photo', null);
                             const input = $refs.photoInput;
                             if (input) input.value = '';
                         }
                     }"
                     x-on:livewire-upload-start="isUploading = true"
                     x-on:livewire-upload-finish="isUploading = false"
                     x-on:livewire-upload-error="isUploading = false"
                     x-on:livewire-upload-progress="progress = $event.detail.progress">
                    
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Foto Referensi (Opsional)</label>
                        <button type="button" 
                                x-show="localPreview || @js(!empty($reference_photo))" 
                                @click="removePhoto()" 
                                class="text-[10px] text-rose-600 hover:text-rose-800 font-extrabold cursor-pointer">
                            ✕ Hapus Foto
                        </button>
                    </div>

                    <div class="border border-dashed border-slate-200 hover:border-rose-500 rounded-2xl p-3 text-center cursor-pointer relative bg-slate-50/50 hover:bg-white transition duration-150 shadow-inner overflow-hidden">
                        <input type="file" 
                               x-ref="photoInput"
                               wire:model.live="reference_photo" 
                               accept="image/*"
                               @change="handleFile($event)"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                        <!-- Preview jika ada file terpilih (Instan Client Side atau Server Fallback) -->
                        <div x-show="localPreview || @js(!empty($reference_photo))" class="flex items-center gap-3 text-left">
                            <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                                <template x-if="localPreview">
                                    <img :src="localPreview" alt="Pratinjau Foto" class="w-full h-full object-cover">
                                </template>
                                @if ($reference_photo)
                                    <template x-if="!localPreview">
                                        <img src="{{ $reference_photo->temporaryUrl() }}" alt="Pratinjau Foto" class="w-full h-full object-cover">
                                    </template>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                        <span>Foto Terpilih</span>
                                    </span>
                                    <span class="text-[9px] text-slate-400 font-bold" x-text="fileSize"></span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-800 truncate mt-1" x-text="fileName || 'Foto Referensi'"></p>
                                
                                <!-- Progress upload Livewire -->
                                <div x-show="isUploading" class="mt-1 space-y-1">
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-rose-600 h-1.5 rounded-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                                    </div>
                                    <span class="text-[8.5px] text-slate-500 font-bold" x-text="'Mengunggah: ' + progress + '%'"></span>
                                </div>
                                <span x-show="!isUploading" class="text-[8.5px] text-slate-400 block mt-0.5">Ketuk kotak untuk ganti foto</span>
                            </div>
                        </div>

                        <!-- Placeholder jika belum ada foto -->
                        <div x-show="!localPreview && @js(empty($reference_photo))" class="flex flex-col items-center py-2">
                            <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-700">Pilih Foto Referensi / Kamera</span>
                            <span class="text-[8px] text-slate-400 mt-0.5">PNG, JPG (Maks 25MB)</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. INTERACTIVE MAP SECTION -->
        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                        Peta Rute Pengantaran (Jawa & Indonesia)
                    </label>
                    <p class="text-[10px] text-slate-400 mt-0.5">Mendukung rute dalam kota maupun antar-kota (Surabaya, Sidoarjo, Malang, dll).</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span id="disp-route-type" class="text-[9px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                        Rute Lokal
                    </span>
                    <span class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full shadow-2xs">
                        Jarak: <span id="disp-distance">{{ number_format($distance, 2) }}</span> KM
                    </span>
                </div>
            </div>

            <!-- Quick Jump Area Chips -->
            <div class="flex flex-wrap items-center gap-1.5 p-2 bg-slate-50 border border-slate-200/80 rounded-2xl">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mr-1">Fokus Cepat:</span>
                <button type="button" onclick="jumpToArea('surabaya')" class="text-[10px] font-bold py-1 px-2.5 rounded-xl bg-white hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200 transition shadow-2xs">
                    🏙️ Surabaya
                </button>
                <button type="button" onclick="jumpToArea('sidoarjo')" class="text-[10px] font-bold py-1 px-2.5 rounded-xl bg-white hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200 transition shadow-2xs">
                    ✈️ Sidoarjo
                </button>
                <button type="button" onclick="jumpToArea('malang')" class="text-[10px] font-bold py-1 px-2.5 rounded-xl bg-white hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200 transition shadow-2xs">
                    🏛️ Malang
                </button>
                <button type="button" onclick="jumpToArea('pasuruan')" class="text-[10px] font-bold py-1 px-2.5 rounded-xl bg-white hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200 transition shadow-2xs">
                    🏭 Pasuruan
                </button>
                <button type="button" onclick="jumpToArea('jawa_timur')" class="text-[10px] font-bold py-1 px-2.5 rounded-xl bg-white hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 border border-slate-200 transition shadow-2xs">
                    🗺️ Jawa Timur
                </button>
                <button type="button" onclick="fitFullRoute()" class="text-[10px] font-bold py-1 px-2.5 rounded-xl bg-rose-600 text-white hover:bg-rose-700 transition ml-auto shadow-2xs">
                    🔍 Rute Lengkap
                </button>
            </div>

            <!-- Leaflet Container -->
            <div 
                wire:ignore
                id="leaflet-order-map" 
                class="h-72 w-full border border-slate-200 rounded-2xl bg-slate-100 z-10 shadow-inner"
            ></div>
            <p class="text-[10px] text-slate-400 leading-normal">
                💡 Cari nama toko / alamat se-Indonesia (misal: "Jokopi Surabaya", "Grand Indonesia Jakarta", "Bakso Malang"), atau geser pin langsung di peta. Titik lokasi dan estimasi jarak otomatis terupdate.
            </p>
        </div>

        <!-- 4. RECIPIENT DATA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
            <div class="space-y-1.5">
                <label for="recipient_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Penerima Paket / Titipan</label>
                <input 
                    type="text" 
                    id="recipient_name" 
                    wire:model.live="recipient_name"
                    placeholder="Nama lengkap penerima..."
                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs"
                >
                @error('recipient_name') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-1.5">
                <label for="recipient_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Nomor HP Penerima</label>
                <input 
                    type="text" 
                    id="recipient_phone" 
                    wire:model.live="recipient_phone"
                    placeholder="Contoh: 08123456789..."
                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs"
                >
                @error('recipient_phone') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- 5. FARE ESTIMATION BOX & SUBMIT -->
        <div class="bg-rose-50 border border-rose-100 p-5 rounded-3xl flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 shadow-sm">
            <div>
                <span class="text-[9px] uppercase font-bold text-rose-500 tracking-wider block">Estimasi Ongkir Jasa</span>
                <span class="text-2xl font-black text-rose-600 font-display">Rp {{ number_format($estimated_fare, 0, ',', '.') }}</span>
                <span class="text-[9px] text-slate-400 block mt-0.5">*(Akan digabungkan di keranjang bersama rute belanja lainnya)</span>
            </div>

            <button 
                type="submit" 
                class="w-full sm:w-auto bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs px-8 py-3.5 rounded-full transition shadow-md shadow-rose-600/20 uppercase tracking-wider shrink-0 flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>+ Masukkan ke Keranjang</span>
            </button>
        </div>

    </form>

</div>

@script
<script>
    let map;
    let markerOrigin = null;
    let markerDest = null;
    let stopMarkers = [];
    let polyline = null;
    let updateDistanceAndRoute;
    let fitFullRoute;

    // Helper functions for copying coordinates
    window.copyCoordinatesText = function (text, btn) {
        if (!navigator.clipboard) {
            prompt('Salin koordinat:', text);
            return;
        }
        navigator.clipboard.writeText(text).then(() => {
            const original = btn ? btn.innerText : '';
            if (btn) btn.innerText = '✓ Tersalin!';
            setTimeout(() => { if (btn) btn.innerText = original || 'Salin'; }, 1500);
        }).catch(() => {
            prompt('Salin koordinat:', text);
        });
    };

    window.copyOriginCoords = function () {
        const lat = $wire.origin_lat;
        const lng = $wire.origin_lng;
        if (lat && lng) {
            window.copyCoordinatesText(`${lat}, ${lng}`, event?.target);
        } else {
            alert('Koordinat asal belum ditentukan.');
        }
    };

    window.copyDestCoords = function () {
        const lat = $wire.destination_lat;
        const lng = $wire.destination_lng;
        if (lat && lng) {
            window.copyCoordinatesText(`${lat}, ${lng}`, event?.target);
        } else {
            alert('Koordinat tujuan belum ditentukan.');
        }
    };

    window.copyStopCoords = function (idx) {
        const stops = $wire.stops || [];
        const stop = stops[idx];
        if (stop && stop.lat && stop.lng) {
            window.copyCoordinatesText(`${stop.lat}, ${stop.lng}`, event?.target);
        } else {
            alert(`Koordinat Stop ${idx + 1} belum ditentukan.`);
        }
    };

    window.jumpToArea = function (area) {
        if (!map) return;
        const areas = {
            'surabaya': [-7.2575, 112.7521, 13],
            'sidoarjo': [-7.4478, 112.7183, 13],
            'malang': [-7.9839, 112.6214, 13],
            'pasuruan': [-7.6453, 112.9075, 13],
            'jawa_timur': [-7.5360, 112.6000, 9]
        };
        const target = areas[area];
        if (target) {
            map.flyTo([target[0], target[1]], target[2], { duration: 1.2 });
        }
    };

    function initOrderMap() {
        if (typeof L === 'undefined') return;

        let container = L.DomUtil.get('leaflet-order-map');
        if (container !== null && container._leaflet_id !== undefined && container._leaflet_id !== null) {
            return;
        }

        // Tentukan initial center berdasarkan koordinat yang ada atau Jawa Timur
        let initialLat = $wire.origin_lat || -7.3260; // Surabaya default jika kosong
        let initialLng = $wire.origin_lng || 112.7808;
        let initialZoom = ($wire.origin_lat) ? 13 : 11;

        map = L.map('leaflet-order-map', {
            zoomControl: true,
            attributionControl: false
        }).setView([initialLat, initialLng], initialZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        const redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const blueIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        function createNumberIcon(number) {
            return L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color:#7e22ce; color:white; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:12px; border:2px solid white; box-shadow:0 3px 6px rgba(0,0,0,0.3);">${number}</div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
                popupAnchor: [0, -14]
            });
        }

        // Render markers berdasarkan kategori saat ini
        const isMultiStop = ($wire.category === 'multi-stop');

        // Destination Marker (selalu ada)
        let destLat = $wire.destination_lat || ($wire.origin_lat ? $wire.origin_lat - 0.02 : -7.9829);
        let destLng = $wire.destination_lng || ($wire.origin_lng ? $wire.origin_lng + 0.02 : 112.6308);

        markerDest = L.marker([destLat, destLng], {
            icon: blueIcon,
            draggable: true
        }).bindTooltip("Tujuan Pengantaran (Geser Pin)", { permanent: true, direction: "top" }).addTo(map);

        if (isMultiStop) {
            const stopsData = $wire.stops || [];
            stopMarkers = [];

            stopsData.forEach((st, idx) => {
                let sLat = st.lat || (initialLat + (idx * 0.01));
                let sLng = st.lng || (initialLng - (idx * 0.01));

                const sm = L.marker([sLat, sLng], {
                    icon: createNumberIcon(idx + 1),
                    draggable: true
                }).bindTooltip(`Stop ${idx + 1} (Geser Pin)`, { permanent: true, direction: "top" }).addTo(map);

                sm.on('dragend', function() {
                    const pos = sm.getLatLng();
                    $wire.updateStopCoordinates(idx, pos.lat, pos.lng);
                    updateDistanceAndRoute();
                });

                stopMarkers.push(sm);
            });
        } else {
            let origLat = $wire.origin_lat || -7.3260;
            let origLng = $wire.origin_lng || 112.7808;

            markerOrigin = L.marker([origLat, origLng], {
                icon: redIcon,
                draggable: true
            }).bindTooltip("Toko Belanja / Asal (Geser Pin)", { permanent: true, direction: "top" }).addTo(map);

            markerOrigin.on('dragend', function() {
                const pos = markerOrigin.getLatLng();
                $wire.updateCoordinates('origin', pos.lat, pos.lng);
                updateDistanceAndRoute();
            });
        }

        markerDest.on('dragend', function() {
            const pos = markerDest.getLatLng();
            $wire.updateCoordinates('destination', pos.lat, pos.lng);
            updateDistanceAndRoute();
        });

        polyline = L.polyline([], {
            color: isMultiStop ? '#9333ea' : '#e11d48',
            weight: 3.5,
            dashArray: '6, 6'
        }).addTo(map);

        updateDistanceAndRoute = function() {
            const isMulti = ($wire.category === 'multi-stop');
            let latlngs = [];
            let totalMeters = 0;

            if (isMulti && stopMarkers.length > 0) {
                for (let i = 0; i < stopMarkers.length; i++) {
                    const curPos = stopMarkers[i].getLatLng();
                    latlngs.push(curPos);

                    // Update DOM text untuk Stop
                    const stopText = document.getElementById(`disp-stop-coords-${i}`);
                    if (stopText) {
                        stopText.innerText = `Lat: ${curPos.lat.toFixed(5)}, Lng: ${curPos.lng.toFixed(5)}`;
                    }

                    if (i > 0) {
                        totalMeters += latlngs[i - 1].distanceTo(curPos);
                    }
                }
                const destPos = markerDest.getLatLng();
                latlngs.push(destPos);
                totalMeters += latlngs[latlngs.length - 2].distanceTo(destPos);

            } else if (markerOrigin && markerDest) {
                const oPos = markerOrigin.getLatLng();
                const dPos = markerDest.getLatLng();
                latlngs = [oPos, dPos];
                totalMeters = oPos.distanceTo(dPos);

                const originText = document.getElementById('disp-origin-coords');
                if (originText) originText.innerText = `Lat: ${oPos.lat.toFixed(5)}, Lng: ${oPos.lng.toFixed(5)}`;
            }

            const destPos = markerDest.getLatLng();
            const destText1 = document.getElementById('disp-dest-coords-1');
            const destText2 = document.getElementById('disp-dest-coords-2');
            if (destText1) destText1.innerText = `Lat: ${destPos.lat.toFixed(5)}, Lng: ${destPos.lng.toFixed(5)}`;
            if (destText2) destText2.innerText = `Lat: ${destPos.lat.toFixed(5)}, Lng: ${destPos.lng.toFixed(5)}`;

            polyline.setLatLngs(latlngs);

            const distanceKM = Math.max(0.5, parseFloat((totalMeters / 1000).toFixed(2)));
            const dispDist = document.getElementById('disp-distance');
            if (dispDist) dispDist.innerText = distanceKM.toFixed(2);

            const routeType = document.getElementById('disp-route-type');
            if (routeType) {
                if (distanceKM > 30) {
                    routeType.innerText = '🚀 Antar-Kota (Jawa Timur)';
                    routeType.className = 'text-[9px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200';
                } else {
                    routeType.innerText = '📍 Rute Lokal';
                    routeType.className = 'text-[9px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200';
                }
            }

            $wire.set('distance', distanceKM);
        };

        fitFullRoute = function() {
            if (!map || !polyline) return;
            const pts = polyline.getLatLngs();
            if (pts && pts.length > 0) {
                map.fitBounds(L.latLngBounds(pts), { padding: [50, 50], maxZoom: 16 });
            }
        };

        window.fitFullRoute = fitFullRoute;

        updateDistanceAndRoute();
        fitFullRoute();

        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 300);
    }

    let initAttempts = 0;
    function tryInitMap() {
        if (typeof L !== 'undefined') {
            initOrderMap();
        } else if (initAttempts < 50) {
            initAttempts++;
            setTimeout(tryInitMap, 100);
        }
    }

    tryInitMap();

    // ================= NOMINATIM GEOCODING (SE-JAWA & INDONESIA) =================
    let lastGeocodeRequestAt = 0;
    const GEOCODE_MIN_INTERVAL_MS = 1000;

    async function geocodeAndMove(query, targetType, btnId, stopIdx = null) {
        const btn = document.getElementById(btnId);
        const originalText = btn ? btn.innerText : '';

        if (!query || query.trim().length < 3) {
            alert('Ketik nama toko / jalan minimal 3 karakter ya.');
            return;
        }

        if (!map) {
            alert('Peta sedang dimuat, tunggu sebentar lalu ulangi.');
            return;
        }

        const now = Date.now();
        const waitTime = Math.max(0, GEOCODE_MIN_INTERVAL_MS - (now - lastGeocodeRequestAt));
        lastGeocodeRequestAt = now + waitTime;

        if (btn) {
            btn.innerText = '⏳ Mencari...';
            btn.disabled = true;
        }

        try {
            if (waitTime > 0) {
                await new Promise(resolve => setTimeout(resolve, waitTime));
            }

            const cleanQuery = query.trim();
            // Pencarian langsung se-Indonesia dengan countrycodes=id
            let url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(cleanQuery)}&format=json&limit=5&countrycodes=id`;
            let response = await fetch(url, { headers: { 'Accept-Language': 'id-ID,id;q=0.9' } });
            
            if (!response.ok) throw new Error('HTTP ' + response.status);
            let results = await response.json();

            // Fallback: jika tidak ketemu dan tidak menyebut provinsi, coba query + Jawa Timur
            if (!results || results.length === 0) {
                if (!cleanQuery.toLowerCase().includes('jawa') && !cleanQuery.toLowerCase().includes('indonesia')) {
                    const fallbackUrl = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(cleanQuery + ' Jawa Timur')}&format=json&limit=3&countrycodes=id`;
                    const fallbackRes = await fetch(fallbackUrl, { headers: { 'Accept-Language': 'id-ID,id;q=0.9' } });
                    if (fallbackRes.ok) {
                        results = await fallbackRes.json();
                    }
                }
            }

            if (!results || results.length === 0) {
                alert(`Lokasi "${query}" tidak ditemukan di peta. Coba tambahkan nama kota/jalan (misal: "${query} Surabaya" atau "${query} Jawa Timur"), atau geser pin langsung pada peta.`);
                return;
            }

            const foundLat = parseFloat(results[0].lat);
            const foundLng = parseFloat(results[0].lon);

            if (targetType === 'stop' && stopIdx !== null && stopMarkers[stopIdx]) {
                stopMarkers[stopIdx].setLatLng([foundLat, foundLng]);
                $wire.updateStopCoordinates(stopIdx, foundLat, foundLng);
            } else if (targetType === 'origin' && markerOrigin) {
                markerOrigin.setLatLng([foundLat, foundLng]);
                $wire.updateCoordinates('origin', foundLat, foundLng);
            } else if (targetType === 'dest' && markerDest) {
                markerDest.setLatLng([foundLat, foundLng]);
                $wire.updateCoordinates('destination', foundLat, foundLng);
            }

            if (typeof updateDistanceAndRoute === 'function') {
                updateDistanceAndRoute();
            }

            if (typeof fitFullRoute === 'function') {
                fitFullRoute();
            }

        } catch (error) {
            console.error('Geocoding error:', error);
            alert('Gagal menghubungi layanan pencarian lokasi. Coba lagi sebentar atau posisikan pin manual.');
        } finally {
            if (btn) {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    }

    window.searchOriginLocation = function () {
        const input = document.getElementById('origin_address');
        geocodeAndMove(input ? input.value : '', 'origin', 'btn-search-origin');
    };

    window.searchDestinationLocation = function () {
        const input = document.getElementById('destination_address');
        geocodeAndMove(input ? input.value : '', 'dest', 'btn-search-destination');
    };

    window.searchStopLocation = function (idx) {
        const input = document.getElementById('stop_address_' + idx);
        geocodeAndMove(input ? input.value : '', 'stop', 'btn_search_stop_' + idx, idx);
    };

    window.detectCustomerCurrentLocation = function (isAutomatic = false) {
        if (!navigator.geolocation) {
            if (!isAutomatic) alert('Browser Anda tidak mendukung deteksi GPS.');
            return;
        }

        const btns = [
            document.getElementById('btn-gps-dest-1'),
            document.getElementById('btn-gps-dest-2')
        ].filter(Boolean);

        btns.forEach(b => {
            b.innerHTML = '⏳ Menyesuaikan GPS...';
            b.disabled = true;
        });

        navigator.geolocation.getCurrentPosition(
            async function (pos) {
                const userLat = pos.coords.latitude;
                const userLng = pos.coords.longitude;

                if (markerDest && map) {
                    markerDest.setLatLng([userLat, userLng]);
                    $wire.updateCoordinates('destination', userLat, userLng);
                    if (typeof updateDistanceAndRoute === 'function') {
                        updateDistanceAndRoute();
                    }
                    if (typeof fitFullRoute === 'function') {
                        fitFullRoute();
                    }
                }

                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${userLat}&lon=${userLng}&zoom=18&addressdetails=1`, {
                        headers: { 'Accept-Language': 'id' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        const addr = data.address || {};
                        let cleanAddress = [
                            addr.road || addr.amenity || addr.building || '',
                            addr.neighbourhood || addr.suburb || addr.village || '',
                            addr.city || addr.town || addr.county || 'Surabaya'
                        ].filter(Boolean).join(', ');
                        if (!cleanAddress) cleanAddress = data.display_name;

                        window.isSelectingPlace = true;
                        clearTimeout(searchPlaceTimer);
                        if (activePlaceController) activePlaceController.abort();
                        window.closeAllPlaceDropdowns();

                        window.lastSelectedPlaces = window.lastSelectedPlaces || {};
                        window.lastSelectedPlaces['destination_address'] = cleanAddress.trim();

                        const destInputs = document.querySelectorAll('#destination_address');
                        destInputs.forEach(inp => { inp.value = cleanAddress; });

                        $wire.set('destination_address', cleanAddress);

                        setTimeout(() => {
                            window.isSelectingPlace = false;
                        }, 500);
                    }
                } catch (e) {
                    console.warn('Reverse geocode error:', e);
                } finally {
                    btns.forEach(b => {
                        b.innerHTML = '✓ Lokasi Saya Terpasang';
                        b.disabled = false;
                    });
                    window.closeAllPlaceDropdowns();
                }
            },
            function (err) {
                btns.forEach(b => {
                    b.innerHTML = '📍 Pakai Lokasi Saya (GPS)';
                    b.disabled = false;
                });
                if (!isAutomatic && err.code === err.PERMISSION_DENIED) {
                    alert('Izin akses lokasi ditolak di browser. Aktifkan izin lokasi untuk menggunakan fitur ini.');
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
    };

    // ================= LIVE AUTOCOMPLETE DROPDOWN UNTUK CARI TOKO & LOKASI =================
    let searchPlaceTimer = null;
    let activePlaceController = null;
    window.isSelectingPlace = false;
    window.lastSelectedPlaces = window.lastSelectedPlaces || {};
    window.currentSearchPlaces = window.currentSearchPlaces || {};

    window.handlePlaceSearch = function (query, fieldType) {
        if (window.isSelectingPlace) return;

        // Jangan cari ulang jika nilai input sama persis dengan yang baru saja dipilih
        if (window.lastSelectedPlaces && window.lastSelectedPlaces[fieldType] && window.lastSelectedPlaces[fieldType] === (query || '').trim()) {
            return;
        }

        clearTimeout(searchPlaceTimer);

        const dropdown = document.getElementById('dropdown-' + fieldType);
        const spinner = getSpinnerForField(fieldType);

        if (!query || query.trim().length < 2) {
            if (dropdown) dropdown.classList.add('hidden');
            if (spinner) spinner.classList.add('hidden');
            return;
        }

        if (spinner) spinner.classList.remove('hidden');

        searchPlaceTimer = setTimeout(async () => {
            if (window.isSelectingPlace) return;

            if (activePlaceController) {
                activePlaceController.abort();
            }
            activePlaceController = new AbortController();

            try {
                const res = await fetch(`/api/search-places?q=${encodeURIComponent(query.trim())}`, {
                    signal: activePlaceController.signal,
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Search failed');
                const places = await res.json();
                if (window.isSelectingPlace) return;
                window.currentSearchPlaces[fieldType] = places;
                renderPlaceDropdown(places, fieldType, query);
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Place search error:', err);
                }
            } finally {
                if (spinner) spinner.classList.add('hidden');
            }
        }, 250);
    };

    function getSpinnerForField(fieldType) {
        if (fieldType === 'store_name') return document.getElementById('store-search-spinner');
        if (fieldType === 'origin_address') return document.getElementById('origin-search-spinner');
        if (fieldType === 'destination_address') return document.getElementById('dest-search-spinner');
        return null;
    }

    window.renderPlaceDropdown = function (places, fieldType, query) {
        const dropdown = document.getElementById('dropdown-' + fieldType);
        if (!dropdown) return;

        if (!places || places.length === 0) {
            dropdown.innerHTML = `
                <div class="p-4 text-center text-slate-500 text-xs">
                    <span class="block text-xl mb-1">🔍</span>
                    <span class="font-bold text-slate-700">Tidak ada lokasi untuk "${escapeHtml(query)}"</span>
                    <span class="block text-[10px] text-slate-400 mt-1">Coba kata kunci lain atau gunakan tombol "Cari Toko"</span>
                </div>
            `;
            dropdown.classList.remove('hidden');
            return;
        }

        let html = `
            <div class="px-3.5 py-2 bg-slate-50/90 border-b border-slate-100 flex items-center justify-between text-[10px] font-bold text-slate-500 uppercase tracking-wider sticky top-0 z-10 backdrop-blur-xs">
                <span>Pilihan Toko & Lokasi (${places.length})</span>
                <span class="text-rose-600 font-semibold">Klik untuk pasang pin 📍</span>
            </div>
            <div class="py-1">
        `;

        places.forEach((p, index) => {
            const isMerchant = p.source === 'merchant';
            const badgeClass = isMerchant 
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                : 'bg-blue-50 text-blue-700 border-blue-200';

            html += `
                <div 
                    data-index="${index}"
                    onmousedown="event.preventDefault(); window.selectPlaceByIndex(${index}, '${fieldType}');"
                    class="px-3.5 py-2.5 hover:bg-rose-50/80 transition duration-150 cursor-pointer flex items-start gap-2.5 group select-none border-b border-slate-50 last:border-b-0"
                >
                    <div class="w-8 h-8 rounded-xl ${isMerchant ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'} flex items-center justify-center shrink-0 text-sm mt-0.5 shadow-2xs group-hover:scale-105 transition-transform">
                        ${p.icon || '📍'}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-extrabold text-xs text-slate-800 group-hover:text-rose-600 truncate transition-colors">${escapeHtml(p.name)}</span>
                            <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded-full border ${badgeClass} shrink-0">${p.badge}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 truncate mt-0.5 leading-tight">${escapeHtml(p.address)}</p>
                        <div class="flex items-center gap-2 mt-1 text-[10px] text-slate-400 font-mono">
                            <span>Lat: ${Number(p.lat).toFixed(4)}, Lng: ${Number(p.lng).toFixed(4)}</span>
                            <span class="text-rose-600 font-sans font-bold group-hover:translate-x-0.5 transition-transform">➔ Pasang Lokasi</span>
                        </div>
                    </div>
                </div>
            `;
        });

        html += `</div>`;
        dropdown.innerHTML = html;
        dropdown.classList.remove('hidden');
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    window.selectPlaceByIndex = function (index, fieldType) {
        const list = window.currentSearchPlaces ? window.currentSearchPlaces[fieldType] : null;
        if (!list || !list[index]) return;
        const place = list[index];
        window.selectPlaceSuggestion(place, fieldType);
    };

    window.selectPlaceSuggestion = function (place, fieldType) {
        if (!place) return;

        window.isSelectingPlace = true;
        clearTimeout(searchPlaceTimer);
        if (activePlaceController) {
            activePlaceController.abort();
        }
        window.closeAllPlaceDropdowns();

        window.lastSelectedPlaces = window.lastSelectedPlaces || {};

        if (fieldType === 'store_name' || fieldType === 'origin_address') {
            window.lastSelectedPlaces['store_name'] = (place.name || '').trim();
            window.lastSelectedPlaces['origin_address'] = (place.address || place.display_name || '').trim();

            const storeInput = document.getElementById('store_name');
            if (storeInput) {
                storeInput.value = place.name;
            }

            const addrInput = document.getElementById('origin_address');
            if (addrInput) {
                addrInput.value = place.address || place.display_name;
            }

            // Sync to Livewire
            try {
                $wire.set('store_name', place.name, false);
                $wire.set('origin_address', place.address || place.display_name, false);
                $wire.updateCoordinates('origin', parseFloat(place.lat), parseFloat(place.lng));
            } catch (e) {
                console.warn('Livewire sync notice:', e);
            }

            if (markerOrigin) {
                markerOrigin.setLatLng([parseFloat(place.lat), parseFloat(place.lng)]);
            }
            if (map) {
                map.flyTo([parseFloat(place.lat), parseFloat(place.lng)], 15, { duration: 0.8 });
            }

            const dispCoords = document.getElementById('disp-origin-coords');
            if (dispCoords) {
                dispCoords.innerHTML = `<span class="text-emerald-700 font-extrabold">✓ Lat: ${Number(place.lat).toFixed(5)}, Lng: ${Number(place.lng).toFixed(5)}</span>`;
            }

        } else if (fieldType === 'destination_address') {
            window.lastSelectedPlaces['destination_address'] = (place.address || place.display_name || '').trim();

            const destInputs = document.querySelectorAll('#destination_address');
            destInputs.forEach(inp => {
                inp.value = place.address || place.display_name;
            });

            try {
                $wire.set('destination_address', place.address || place.display_name, false);
                $wire.updateCoordinates('destination', parseFloat(place.lat), parseFloat(place.lng));
            } catch (e) {
                console.warn('Livewire sync notice:', e);
            }

            if (markerDest) {
                markerDest.setLatLng([parseFloat(place.lat), parseFloat(place.lng)]);
            }
            if (map) {
                map.flyTo([parseFloat(place.lat), parseFloat(place.lng)], 15, { duration: 0.8 });
            }

            const dispDest1 = document.getElementById('disp-dest-coords-1');
            const dispDest2 = document.getElementById('disp-dest-coords-2');
            if (dispDest1) dispDest1.innerHTML = `<span class="text-emerald-700 font-extrabold">✓ Lat: ${Number(place.lat).toFixed(5)}, Lng: ${Number(place.lng).toFixed(5)}</span>`;
            if (dispDest2) dispDest2.innerHTML = `<span class="text-emerald-700 font-extrabold">✓ Lat: ${Number(place.lat).toFixed(5)}, Lng: ${Number(place.lng).toFixed(5)}</span>`;
        }

        if (typeof updateDistanceAndRoute === 'function') {
            updateDistanceAndRoute();
        }
        if (typeof fitFullRoute === 'function') {
            setTimeout(fitFullRoute, 500);
        }

        window.closeAllPlaceDropdowns();

        setTimeout(() => {
            window.isSelectingPlace = false;
        }, 500);
    };

    window.closeAllPlaceDropdowns = function () {
        clearTimeout(searchPlaceTimer);
        ['store_name', 'origin_address', 'destination_address'].forEach(id => {
            const dropdown = document.getElementById('dropdown-' + id);
            if (dropdown) dropdown.classList.add('hidden');
            const spinner = getSpinnerForField(id);
            if (spinner) spinner.classList.add('hidden');
        });
    };

    document.addEventListener('pointerdown', function (e) {
        if (!e.target.closest('#store-name-wrapper') && !e.target.closest('#origin-address-wrapper') && !e.target.closest('#destination-address-wrapper')) {
            closeAllPlaceDropdowns();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllPlaceDropdowns();
        }
    });

    $wire.on('saved-address-selected', (event) => {
        const data = Array.isArray(event) ? event[0] : event;
        const destInputs = document.querySelectorAll('#destination_address');
        destInputs.forEach(inp => {
            inp.value = data.address || '';
        });

        window.lastSelectedPlaces = window.lastSelectedPlaces || {};
        window.lastSelectedPlaces['destination_address'] = (data.address || '').trim();

        if (data.lat && data.lng) {
            if (markerDest) {
                markerDest.setLatLng([parseFloat(data.lat), parseFloat(data.lng)]);
            }
            if (map) {
                map.flyTo([parseFloat(data.lat), parseFloat(data.lng)], 15, { duration: 0.8 });
            }
        }

        if (typeof updateDistanceAndRoute === 'function') {
            updateDistanceAndRoute();
        }
        if (typeof fitFullRoute === 'function') {
            setTimeout(fitFullRoute, 500);
        }
    });
</script>
@endscript