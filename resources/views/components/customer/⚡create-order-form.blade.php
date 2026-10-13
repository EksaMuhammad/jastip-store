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
    public $reference_photo;
    
    public string $origin_address = '';
    public string $destination_address = '';
    public string $recipient_name = '';
    public string $recipient_phone = '';

    // Multi-Stop specific inputs
    public array $stops = [
        ['address' => '', 'notes' => '', 'lat' => null, 'lng' => null],
        ['address' => '', 'notes' => '', 'lat' => null, 'lng' => null],
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

    // Messages
    public string $success_message = '';
    public string $error_message = '';

    public function addStop()
    {
        if (count($this->stops) < 5) {
            $this->stops[] = ['address' => '', 'notes' => '', 'lat' => null, 'lng' => null];
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

    protected function rules()
    {
        $rules = [
            'category' => ['required', 'in:beli-antar,ambil-antar,toko-kirim,dokumen,multi-stop,kirim-pihak-ketiga'],
            'weight_category' => ['required', 'in:ringan,sedang,berat'],
            'description' => ['required', 'string', 'min:5'],
            'destination_address' => ['required', 'string', 'min:5'],
            'recipient_name' => ['required', 'string', 'min:2'],
            'recipient_phone' => ['required', 'string', 'min:9'],
            'reference_photo' => ['nullable', 'image', 'max:2048'], // Max 2MB
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
            'reference_photo.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }

    public function mount()
    {
        // Auto fill recipient details with customer's own details as default
        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $this->recipient_name = $customer->name;
            $this->recipient_phone = $customer->phone_number;
        }

        // Tangkap parameter query kategori
        $cat = request()->query('cat');
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

        // Calculate initial fare
        $this->calculateFare();
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

    /**
     * Hitung tarif estimasi jastip secara real-time
     */
    public function calculateFare()
    {
        // Tarif dasar per KM
        $ratePerKm = 5000.0;
        
        // Biaya tambahan kategori barang
        $categoryAdditions = [
            'beli-antar' => 5000.0,
            'ambil-antar' => 3000.0,
            'toko-kirim' => 2000.0,
            'dokumen' => 0.0,
            'multi-stop' => 15000.0,
            'kirim-pihak-ketiga' => 7000.0,
        ];

        // Biaya tambahan berat
        $weightAdditions = [
            'ringan' => 0.0,
            'sedang' => 10000.0,
            'berat' => 25000.0,
        ];

        $baseAddition = $categoryAdditions[$this->category] ?? 0.0;
        $weightAddition = $weightAdditions[$this->weight_category] ?? 0.0;

        // Tambahan per extra stop untuk multi-stop (titik ke-3 dst: +Rp 6.000 / titik)
        $extraStopsFare = 0.0;
        if ($this->category === 'multi-stop' && count($this->stops) > 2) {
            $extraStopsFare = (count($this->stops) - 2) * 6000.0;
        }

        // Formula: (Jarak * Tarif/KM) + Tambahan Kategori + Tambahan Berat + Extra Stops
        $fare = ($this->distance * $ratePerKm) + $baseAddition + $weightAddition + $extraStopsFare;
        
        // Minimal fare Rp 10.000
        $this->estimated_fare = max(10000.0, $fare);
    }

    /**
     * Simpan request order baru ke database
     */
    public function submitOrder()
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();
        
        // Gunakan wilayah operasional default pertama (Malang Kota)
        $wilayah = Wilayah::first();
        if (!$wilayah) {
            $this->error_message = 'Wilayah operasional tidak aktif. Hubungi Admin.';
            return;
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
            } elseif ($this->category === 'kirim-pihak-ketiga') {
                $originText = $this->origin_address ?: "Konter / Agen {$this->courier_agency}";
            }

            // Buat record order baru
            $order = Order::create([
                'customer_id' => $customer->id,
                'jastiper_id' => $this->jastiper_id, // Direct booking jika terisi
                'wilayah_id' => $wilayah->id,
                'category' => $this->category,
                'weight_category' => $this->weight_category,
                'description' => $this->description,
                'reference_photo' => $photoPath,
                'origin_address' => $originText ?: 'Lokasi Pin Peta Asal',
                'origin_lat' => $this->origin_lat ?? -7.9839,
                'origin_lng' => $this->origin_lng ?? 112.6214,
                'destination_address' => $this->destination_address,
                'destination_lat' => $this->destination_lat ?? -7.9839,
                'destination_lng' => $this->destination_lng ?? 112.6214,
                'recipient_name' => $this->recipient_name,
                'recipient_phone' => $this->recipient_phone,
                'estimated_fare' => $this->estimated_fare,
                'multi_stops' => $multiStopsPayload,
                'courier_agency' => $this->category === 'kirim-pihak-ketiga' ? $this->courier_agency : null,
                'status' => 'menunggu_tawaran',
            ]);

            // Kirim notifikasi WhatsApp simulasi ke customer
            $categoryNames = [
                'beli-antar' => 'Jastip Kuliner (Beli-Antar)',
                'ambil-antar' => 'Jastip Ambil Barang (Ambil-Antar)',
                'toko-kirim' => 'Jastip Toko',
                'dokumen' => 'Jastip Dokumen Kecil',
                'multi-stop' => 'Jastip Multi-Stop (' . count($this->stops) . ' Titik)',
                'kirim-pihak-ketiga' => 'Jastip Agen Ekspedisi (' . $this->courier_agency . ')',
            ];
            $catLabel = $categoryNames[$this->category] ?? 'Jastip';

            if ($this->jastiper_id) {
                $msg = "Halo *{$customer->name}*!\n\nBooking langsung Anda untuk Jastiper *{$this->direct_jastiper_name}* (*{$this->description}*) telah dikirim.\n\nSistem sedang meneruskan pesanan ini eksklusif ke Jastiper bersangkutan. Mohon tunggu konfirmasi! 🚀";
                session()->flash('success', "Booking langsung ke {$this->direct_jastiper_name} berhasil dikirim! Menunggu persetujuan Jastiper.");
            } else {
                $msg = "Halo *{$customer->name}*!\n\nPermintaan Jastip baru Anda telah berhasil dikirim ke sistem:\n\n📦 *Layanan*: {$catLabel}\n📝 *Deskripsi*: {$this->description}\n🎯 *Radius/Jarak*: {$this->distance} KM\n💰 *Estimasi Ongkir*: Rp " . number_format($this->estimated_fare, 0, ',', '.') . "\n\nSistem sedang mencarikan Jastiper terdekat di area Malang. Mohon tunggu tawaran masuk! 🚀";
                session()->flash('success', 'Request Jastip Baru Berhasil Dibuat! Mohon tunggu tawaran dari Jastiper.');
            }
            WhatsAppService::sendMessage($customer->phone_number, $msg);

            return redirect()->route('customer.dashboard');

        } catch (\Exception $e) {
            Log::error("Error Customer Submit Order: " . $e->getMessage());
            $this->error_message = 'Gagal menyimpan pesanan. Silakan coba lagi.';
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
        
        <!-- 1. PILIH KATEGORI (6 Kategori JastipKuy) -->
        <div class="space-y-3">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Layanan Jastip</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                
                <!-- Beli-Antar -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'beli-antar')"
                    class="p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 focus:outline-none"
                    style="{{ $category === 'beli-antar' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.05);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Beli-Antar</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5">Jastip Kuliner / Makanan</span>
                    </div>
                </button>

                <!-- Ambil-Antar -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'ambil-antar')"
                    class="p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 focus:outline-none"
                    style="{{ $category === 'ambil-antar' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.05);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Ambil & Antar</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5">Ambil barang / COD</span>
                    </div>
                </button>

                <!-- Toko Kirim -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'toko-kirim')"
                    class="p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 focus:outline-none"
                    style="{{ $category === 'toko-kirim' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.05);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Toko Kirim</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5">Belanja Minimarket/Pasar</span>
                    </div>
                </button>

                <!-- Dokumen -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'dokumen')"
                    class="p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 focus:outline-none"
                    style="{{ $category === 'dokumen' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.05);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Dokumen Kecil</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5">Kirim surat / dokumen</span>
                    </div>
                </button>

                <!-- Multi-stop -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'multi-stop')"
                    class="p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 focus:outline-none"
                    style="{{ $category === 'multi-stop' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.05);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Multi-Stop</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5">Banyak titik belanja/antar</span>
                    </div>
                </button>

                <!-- Pihak Ketiga -->
                <button 
                    type="button" 
                    wire:click="$set('category', 'kirim-pihak-ketiga')"
                    class="p-3.5 rounded-2xl border text-left transition duration-150 flex flex-col justify-between h-28 focus:outline-none"
                    style="{{ $category === 'kirim-pihak-ketiga' ? 'border-color: #e11d48; background-color: #fff1f2; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.05);' : 'border-color: #e2e8f0; background-color: white;' }}"
                >
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Pihak Ketiga</h5>
                        <span class="text-[9px] text-slate-400 block mt-0.5">Ekspedisi / Agen Kirim</span>
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
                                <div class="bg-white border border-purple-200/80 rounded-2xl p-3.5 shadow-2xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-full bg-purple-600 text-white font-black text-[10px] flex items-center justify-center">
                                                {{ $idx + 1 }}
                                            </span>
                                            <span class="text-xs font-bold text-slate-800">Stop {{ $idx + 1 }}: Toko / Titik Jemput</span>
                                        </div>
                                        @if(count($stops) > 2)
                                            <button type="button" wire:click="removeStop({{ $idx }})" class="text-[10px] font-bold text-rose-500 hover:text-rose-700 hover:underline">
                                                ✕ Hapus Stop
                                            </button>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                        <!-- Alamat / Nama Toko -->
                                        <div class="sm:col-span-7 flex gap-1.5">
                                            <input 
                                                type="text" 
                                                id="stop_address_{{ $idx }}"
                                                wire:model.live="stops.{{ $idx }}.address"
                                                placeholder="Nama toko / lokasi (mis: Kopi Kenangan Dinoyo)..."
                                                class="flex-1 min-w-0 bg-slate-50 border border-slate-200 text-slate-800 px-3 py-2 rounded-xl text-xs focus:bg-white focus:border-purple-500 outline-none"
                                            >
                                            <button 
                                                type="button"
                                                onclick="searchStopLocation({{ $idx }})"
                                                id="btn_search_stop_{{ $idx }}"
                                                class="shrink-0 bg-purple-600 hover:bg-purple-700 text-white text-[10px] font-bold px-2.5 rounded-xl transition whitespace-nowrap"
                                            >
                                                🔍 Cari
                                            </button>
                                        </div>
                                        <!-- Catatan Belanja di Stop ini -->
                                        <div class="sm:col-span-5">
                                            <input 
                                                type="text" 
                                                wire:model.live="stops.{{ $idx }}.notes"
                                                placeholder="Barang dibeli (mis: 1 Latte Ice)..."
                                                class="w-full bg-slate-50 border border-slate-200 text-slate-800 px-3 py-2 rounded-xl text-xs focus:bg-white focus:border-purple-500 outline-none"
                                            >
                                        </div>
                                    </div>
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
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="destination_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Alamat Tujuan Pengantaran Akhir</label>
                            <button 
                                type="button" 
                                id="btn-gps-dest-1"
                                onclick="detectCustomerCurrentLocation()"
                                class="text-[10px] font-bold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1"
                            >
                                📍 Pakai Lokasi Saya (GPS)
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                id="destination_address" 
                                wire:model.live="destination_address"
                                placeholder="Alamat rumah / kos Anda..."
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
                        @error('destination_address') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Origin (Untuk single store/pickup) -->
                        @if(in_array($category, ['beli-antar', 'ambil-antar', 'toko-kirim']))
                            <div class="space-y-1.5">
                                <label for="origin_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Alamat Asal / Toko Belanja</label>
                                <div class="flex gap-2">
                                    <input 
                                        type="text" 
                                        id="origin_address" 
                                        wire:model.live="origin_address"
                                        placeholder="Tulis nama warung / lokasi belanja..."
                                        class="flex-1 min-w-0 bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2.5 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-xs"
                                    >
                                    <button 
                                        type="button"
                                        onclick="searchOriginLocation()"
                                        id="btn-search-origin"
                                        class="shrink-0 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-3 rounded-2xl transition duration-150 whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        🔍 Cari Toko
                                    </button>
                                </div>
                                @error('origin_address') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
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
                                    placeholder="Cabang / agen spesifik (opsional, mis: JNE Soekarno Hatta)..."
                                    class="w-full mt-1.5 bg-[#F3F4F6] border border-slate-200 text-slate-750 px-4 py-2 rounded-2xl outline-none focus:bg-white focus:border-rose-500 transition duration-150 text-[11px]"
                                >
                            </div>
                        @endif

                        <!-- Destination -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="destination_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Alamat Tujuan Pengantaran</label>
                                <button 
                                    type="button" 
                                    id="btn-gps-dest-2"
                                    onclick="detectCustomerCurrentLocation()"
                                    class="text-[10px] font-bold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1"
                                >
                                    📍 Pakai Lokasi Saya
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <input 
                                    type="text" 
                                    id="destination_address" 
                                    wire:model.live="destination_address"
                                    placeholder="Alamat rumah / kos Anda..."
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
                        <option value="ringan">Ringan (Baju, Makanan, Dokumen) - +Rp0</option>
                        <option value="sedang">Sedang (Dus Sedang, Helm) - +Rp10.000</option>
                        <option value="berat">Berat (Kardus Besar, Galon) - +Rp25.000</option>
                    </select>
                </div>

                <!-- Foto Uploader -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Foto Referensi (Opsional)</label>
                    <div class="border border-dashed border-slate-200 hover:border-rose-500 rounded-2xl p-4 text-center cursor-pointer relative bg-slate-50/50 hover:bg-white transition duration-150 shadow-inner">
                        <input type="file" wire:model="reference_photo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        @if ($reference_photo)
                            <div class="flex flex-col items-center">
                                <img src="{{ $reference_photo->temporaryUrl() }}" class="max-h-20 object-contain rounded-xl border border-slate-200">
                                <span class="text-[9px] text-emerald-600 font-bold mt-1.5">✓ Terpilih</span>
                            </div>
                        @else
                            <div class="flex flex-col items-center py-2">
                                <span class="text-xs font-bold text-slate-700">Pilih Foto</span>
                                <span class="text-[8px] text-slate-400 mt-0.5">PNG, JPG (Maks 2MB)</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. INTERACTIVE MAP SECTION -->
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Tentukan Titik di Peta (Geser Pin)</label>
                <span class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-full">
                    Jarak: <span id="disp-distance">{{ number_format($distance, 2) }}</span> KM
                </span>
            </div>

            <!-- Leaflet Container -->
            <div 
                wire:ignore
                id="leaflet-order-map" 
                class="h-64 w-full border border-slate-200 rounded-2xl bg-slate-100 z-10 shadow-inner"
            ></div>
            <p class="text-[10px] text-slate-400 leading-normal">
                💡 Gunakan tombol <strong>🔍 Cari Toko</strong> / <strong>🔍 Cari Lokasi</strong> di atas untuk langsung menemukan titik di peta berdasarkan nama, atau geser manual **Pin Merah (Asal Belanja)** dan **Pin Biru (Tujuan Pengantaran)**. Tarif ongkir akan otomatis dikalkulasi berdasarkan jarak kedua titik.
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
                <span class="text-[9px] uppercase font-bold text-rose-500 tracking-wider block">Total Estimasi Ongkos Kirim</span>
                <span class="text-2xl font-black text-rose-600 font-display">Rp {{ number_format($estimated_fare, 0, ',', '.') }}</span>
                <span class="text-[9px] text-slate-400 block mt-0.5">*(Ongkir disesuaikan berdasarkan jarak {{ number_format($distance, 1) }} KM & kategori berat)</span>
            </div>

            <button 
                type="submit" 
                class="w-full sm:w-auto bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs px-8 py-3.5 rounded-full transition shadow-md shadow-rose-600/10 uppercase tracking-wider shrink-0"
            >
                Kirim Request Jastip
            </button>
        </div>

    </form>

</div>

@script
<script>
        let map;
        let markerOrigin;
        let markerDest;
        let polyline;
        let updateDistanceAndRoute; // di-hoist ke scope luar agar bisa dipanggil dari fungsi geocoding

        // Default: Malang Kota center
        let lat = -7.9839;
        let lng = 112.6214;

        function initOrderMap() {
            if (typeof L === 'undefined') return;

            let container = L.DomUtil.get('leaflet-order-map');
            if (container !== null && container._leaflet_id !== undefined && container._leaflet_id !== null) {
                return;
            }

            map = L.map('leaflet-order-map', {
                zoomControl: true,
                attributionControl: false
            }).setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18
            }).addTo(map);

            // Merah untuk Asal Belanja
            const redIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            // Biru untuk Tujuan Antar
            const blueIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            // Tambahkan markers
            markerOrigin = L.marker([lat + 0.005, lng - 0.005], {
                icon: redIcon,
                draggable: true
            }).bindTooltip("Toko Belanja (Asal)", {permanent: true, direction: "top"}).addTo(map);

            markerDest = L.marker([lat - 0.005, lng + 0.005], {
                icon: blueIcon,
                draggable: true
            }).bindTooltip("Alamat Pengantaran (Tujuan)", {permanent: true, direction: "top"}).addTo(map);

            // Tambahkan garis pembantu
            polyline = L.polyline([markerOrigin.getLatLng(), markerDest.getLatLng()], {
                color: '#e11d48',
                dashArray: '5, 5',
                weight: 3
            }).addTo(map);

            updateDistanceAndRoute = function() {
                const originLatLng = markerOrigin.getLatLng();
                const destLatLng = markerDest.getLatLng();
                
                polyline.setLatLngs([originLatLng, destLatLng]);

                const distanceMeters = originLatLng.distanceTo(destLatLng);
                const distanceKM = Math.max(0.5, parseFloat((distanceMeters / 1000).toFixed(2)));

                // Update UI display
                document.getElementById('disp-distance').innerText = distanceKM.toFixed(2);

                // Kirim jarak ke Livewire component
                $wire.set('distance', distanceKM);

                // Kirim koordinat pin Asal & Tujuan ke Livewire supaya tersimpan ke database
                // (dipakai nanti untuk filter radius di sisi jastiper)
                $wire.updateCoordinates('origin', originLatLng.lat, originLatLng.lng);
                $wire.updateCoordinates('destination', destLatLng.lat, destLatLng.lng);
            };

            // Bind drag events
            markerOrigin.on('dragend', updateDistanceAndRoute);
            markerDest.on('dragend', updateDistanceAndRoute);

            // Update distance initial
            updateDistanceAndRoute();

            setTimeout(() => {
                if (map) {
                    map.invalidateSize();
                }
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

        // ================= NOMINATIM GEOCODING (Cari Toko / Cari Lokasi) =================

        // Sederhana throttle biar tidak spam request Nominatim (max ~1 req/detik)
        let lastGeocodeRequestAt = 0;
        const GEOCODE_MIN_INTERVAL_MS = 1100;

        async function geocodeAndMove(query, marker, btnId) {
            const btn = document.getElementById(btnId);
            const originalText = btn ? btn.innerText : '';

            if (!query || query.trim().length < 3) {
                alert('Ketik nama toko / alamat minimal 3 karakter dulu ya.');
                return;
            }

            if (!map || !marker) {
                alert('Peta belum siap, coba tunggu sebentar lalu ulangi.');
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

                // Filter area Malang otomatis ditambahkan ke query secara terprogram
                const searchQuery = `${query.trim()} Malang`;
                const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(searchQuery)}&format=json&limit=1&countrycodes=id`;

                const response = await fetch(url, {
                    headers: {
                        'Accept-Language': 'id'
                    }
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const results = await response.json();

                if (!results || results.length === 0) {
                    alert('Lokasi "' + query + '" tidak ditemukan di area Malang. Coba nama lain, atau geser pin secara manual di peta.');
                    return;
                }

                const foundLat = parseFloat(results[0].lat);
                const foundLng = parseFloat(results[0].lon);

                // Pindahkan marker bersangkutan ke koordinat baru
                marker.setLatLng([foundLat, foundLng]);

                // Geser titik tengah peta ke lokasi tersebut
                map.setView([foundLat, foundLng], 16);

                // Hitung kembali jarak, polyline, dan estimasi fare via Livewire
                if (typeof updateDistanceAndRoute === 'function') {
                    updateDistanceAndRoute();
                }

            } catch (error) {
                console.error('Geocoding error:', error);
                alert('Gagal menghubungi layanan pencarian lokasi (Nominatim). Coba lagi sebentar.');
            } finally {
                if (btn) {
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            }
        }

        // Expose ke window supaya bisa dipanggil dari atribut onclick di HTML
        window.searchOriginLocation = function () {
            const input = document.getElementById('origin_address');
            geocodeAndMove(input ? input.value : '', markerOrigin, 'btn-search-origin');
        };

        window.searchDestinationLocation = function () {
            const input = document.getElementById('destination_address');
            geocodeAndMove(input ? input.value : '', markerDest, 'btn-search-destination');
        };

        window.searchStopLocation = function (idx) {
            const input = document.getElementById('stop_address_' + idx);
            geocodeAndMove(input ? input.value : '', markerOrigin, 'btn_search_stop_' + idx);
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
                        map.setView([userLat, userLng], 15);
                        if (typeof updateDistanceAndRoute === 'function') {
                            updateDistanceAndRoute();
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
                                addr.city || addr.town || addr.county || 'Malang'
                            ].filter(Boolean).join(', ');
                            if (!cleanAddress) cleanAddress = data.display_name;

                            // Update input and Livewire
                            $wire.set('destination_address', cleanAddress);
                        }
                    } catch (e) {
                        console.warn('Reverse geocode error:', e);
                    } finally {
                        btns.forEach(b => {
                            b.innerHTML = '✓ Lokasi Saya Terpasang';
                            b.disabled = false;
                        });
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
</script>
@endscript