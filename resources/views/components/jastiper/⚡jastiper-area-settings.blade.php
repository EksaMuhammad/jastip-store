<?php

use Livewire\Component;
use App\Models\Wilayah;
use App\Models\Jastiper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    public $wilayah_list;
    public $wilayah_id;
    public $radius_km;
    public $current_lat;
    public $current_lng;
    
    public bool $is_simulating = false;
    
    public string $success_message = '';
    public string $error_message = '';

    protected function rules()
    {
        return [
            'wilayah_id' => ['required', 'exists:wilayah,id'],
            'radius_km' => ['required', 'numeric', 'min:1', 'max:15'],
            'current_lat' => ['required', 'numeric', 'between:-90,90'],
            'current_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    protected function messages()
    {
        return [
            'wilayah_id.required' => 'Wilayah operasional wajib dipilih.',
            'wilayah_id.exists' => 'Wilayah yang dipilih tidak valid.',
            'radius_km.required' => 'Radius jangkauan wajib ditentukan.',
            'radius_km.min' => 'Radius minimal adalah 1 km.',
            'radius_km.max' => 'Radius maksimal adalah 15 km.',
            'current_lat.required' => 'Koordinat Latitude wajib diisi.',
            'current_lng.required' => 'Koordinat Longitude wajib diisi.',
        ];
    }

    public function mount()
    {
        $jastiper = Auth::guard('jastiper')->user();
        
        $this->radius_km = floatval($jastiper->radius_km ?? 5.00);
        $this->current_lat = floatval($jastiper->current_lat ?? -7.9839);
        $this->current_lng = floatval($jastiper->current_lng ?? 112.6214);

        // Jika jastiper sudah memiliki koordinat, otomatis cari dan sinkronkan wilayah terdekat
        if ($jastiper->current_lat && $jastiper->current_lng) {
            $nearest = Wilayah::findNearest((float) $jastiper->current_lat, (float) $jastiper->current_lng);
            if ($nearest) {
                $this->wilayah_id = $nearest->id;
                if ($jastiper->wilayah_id !== $nearest->id) {
                    $jastiper->update(['wilayah_id' => $nearest->id]);
                }
            } else {
                $this->wilayah_id = $jastiper->wilayah_id;
            }
        } else {
            $this->wilayah_id = $jastiper->wilayah_id;
        }
        
        $this->wilayah_list = Wilayah::where('is_active', true)->get();
    }

    /**
     * Saat jastiper memilih wilayah di dropdown, otomatis pindahkan koordinat dan peta ke pusat kota wilayah tersebut
     */
    public function updatedWilayahId($val)
    {
        if ($val) {
            $w = Wilayah::find($val);
            if ($w) {
                $coords = $w->center_coordinates;
                $this->current_lat = $coords['lat'];
                $this->current_lng = $coords['lng'];
                
                $this->dispatch('pan-to-coordinates', [
                    'lat' => $this->current_lat,
                    'lng' => $this->current_lng,
                    'radius' => $this->radius_km,
                    'name' => $w->name,
                ]);
            }
        }
    }

    /**
     * Memperbarui lokasi Jastiper secara berkala / real-time dari peta atau GPS
     */
    public function updateLocation($lat, $lng)
    {
        $this->current_lat = floatval($lat);
        $this->current_lng = floatval($lng);

        // Deteksi otomatis wilayah terdekat dari koordinat baru
        $nearest = Wilayah::findNearest($this->current_lat, $this->current_lng);
        if ($nearest) {
            $this->wilayah_id = $nearest->id;
            $this->dispatch('wilayah-auto-detected', [
                'id' => $nearest->id,
                'name' => $nearest->name,
            ]);
        }

        // Jika mode simulasi GPS berjalan aktif, langsung simpan perubahan koordinat ke database
        if ($this->is_simulating) {
            $jastiper = Auth::guard('jastiper')->user();
            $jastiper->current_lat = $this->current_lat;
            $jastiper->current_lng = $this->current_lng;
            if ($nearest) {
                $jastiper->wilayah_id = $nearest->id;
            }
            $jastiper->save();
        }
    }

    /**
     * Mengubah status simulasi pergerakan lokasi GPS
     */
    public function toggleSimulation()
    {
        $this->is_simulating = !$this->is_simulating;
        
        if ($this->is_simulating) {
            // Langsung update posisi awal simulasi ke DB
            $jastiper = Auth::guard('jastiper')->user();
            $jastiper->current_lat = $this->current_lat;
            $jastiper->current_lng = $this->current_lng;
            $nearest = Wilayah::findNearest($this->current_lat, $this->current_lng);
            if ($nearest) {
                $jastiper->wilayah_id = $nearest->id;
            }
            $jastiper->save();
            
            $this->success_message = 'Simulasi GPS Berjalan diaktifkan! Lokasi Anda di DB akan diperbarui secara real-time.';
        } else {
            $this->success_message = 'Simulasi GPS Berjalan dinonaktifkan.';
        }
    }

    /**
     * Menyimpan pengaturan wilayah dan radius jangkauan
     */
    public function saveSettings()
    {
        $this->validate();

        try {
            $jastiper = Auth::guard('jastiper')->user();

            // Otomatis sinkronkan wilayah_id dengan titik koordinat aktual di peta
            $nearest = Wilayah::findNearest($this->current_lat, $this->current_lng);
            if ($nearest) {
                $this->wilayah_id = $nearest->id;
            }

            $jastiper->update([
                'wilayah_id' => $this->wilayah_id,
                'radius_km' => $this->radius_km,
                'current_lat' => $this->current_lat,
                'current_lng' => $this->current_lng,
            ]);

            $wName = $jastiper->fresh()->wilayah ? $jastiper->fresh()->wilayah->name : '';
            $this->success_message = "Pengaturan wilayah ({$wName}) & radius jangkauan ({$this->radius_km} KM) berhasil disimpan!";
            $this->error_message = '';
            
            // Dispatch browser event to notify Map component about coordinates / radius updates
            $this->dispatch('settings-saved', [
                'lat' => $this->current_lat,
                'lng' => $this->current_lng,
                'radius' => $this->radius_km,
                'name' => $wName,
            ]);
        } catch (\Exception $e) {
            Log::error("Error Jastiper Area Settings: " . $e->getMessage());
            $this->error_message = 'Gagal menyimpan pengaturan. Silakan coba lagi.';
            $this->success_message = '';
        }
    }
};
?>

<div class="space-y-6">
    <!-- Card Utama -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-sm">
        
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Wilayah & Radius Jangkauan</h3>
            <p class="text-xs text-slate-400 mt-1">
                Tentukan wilayah operasional utama Anda, batasan radius pengantaran belanjaan, serta simulasikan lokasi GPS Anda untuk mematchingkan pesanan terdekat.
            </p>
        </div>

        <!-- Alerts -->
        @if ($success_message)
            <div class="mb-6 bg-emerald-50 border border-emerald-100 p-4 text-emerald-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $success_message }}</span>
            </div>
        @endif

        @if ($error_message)
            <div class="mb-6 bg-rose-50 border border-rose-100 p-4 text-rose-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-5 h-5 mt-0.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ $error_message }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Kolom Peta (12/12 di Mobile, 7/12 di Desktop) -->
            <div class="lg:col-span-7 space-y-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Lokasi Aktif di Peta</label>
                
                <!-- Container Map Leaflet -->
                <div 
                    wire:ignore
                    id="leaflet-jastiper-map" 
                    class="h-96 w-full border border-slate-200 rounded-2xl bg-slate-100 z-10 shadow-sm"
                ></div>

                <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-slate-200 p-3 rounded-2xl text-xs shadow-sm">
                    <div class="font-mono text-[10px] text-slate-500">
                        📍 Lat: <span class="font-bold text-slate-800" id="disp-lat">{{ number_format($current_lat, 6) }}</span> 
                        | Lng: <span class="font-bold text-slate-800" id="disp-lng">{{ number_format($current_lng, 6) }}</span>
                    </div>
                    
                    <button 
                        type="button" 
                        onclick="geolocateMe()" 
                        class="bg-white hover:bg-slate-100 border border-slate-200 px-4 py-2 font-bold text-[10px] uppercase rounded-full transition duration-150 shadow-sm shrink-0 flex items-center gap-1.5"
                    >
                        🎯 Lacak GPS Saya
                    </button>
                </div>
            </div>

            <!-- Kolom Form Pengaturan (5/12 di Desktop) -->
            <div class="lg:col-span-5 flex flex-col justify-between">
                <form wire:submit.prevent="saveSettings" class="space-y-6">
                    
                    <!-- Pilih Wilayah Dropdown -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="wilayah_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Wilayah Kerja Utama</label>
                            <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Sinkron Otomatis Peta</span>
                            </span>
                        </div>
                        <select 
                            id="wilayah_id" 
                            wire:model.live="wilayah_id" 
                            class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-700 px-3.5 py-3 rounded-2xl text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150"
                        >
                            <option value="">-- Pilih Wilayah Operasional --</option>
                            @foreach($wilayah_list as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} (Bawaan: {{ number_format($w->default_radius_km, 1) }} km)</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400">
                            💡 Geser pin di peta untuk mendeteksi wilayah otomatis, atau pilih dari daftar untuk memindahkan peta.
                        </p>
                        @error('wilayah_id') <span class="text-rose-600 text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Slider Radius -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label for="radius_km" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Radius Jangkauan</label>
                            <span class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-0.5 rounded-full">
                                <span id="disp-radius">{{ number_format($radius_km, 1) }}</span> KM
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-4 py-2">
                            <span class="text-[10px] text-slate-400 font-bold">1 km</span>
                            <input 
                                type="range" 
                                id="radius_km"
                                wire:model.live="radius_km" 
                                min="1" 
                                max="15" 
                                step="0.5" 
                                class="flex-grow accent-rose-600 cursor-pointer h-2 bg-slate-200 rounded-lg appearance-none"
                                oninput="updateCircleRadius(this.value)"
                            >
                            <span class="text-[10px] text-slate-400 font-bold">15 km</span>
                        </div>
                        <p class="text-[10px] text-slate-400 leading-normal">
                            Pesanan belanja (order feed) di luar radius ini tidak akan masuk ke dashboard Anda.
                        </p>
                        @error('radius_km') <span class="text-rose-600 text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-slate-100">
                        <button 
                            type="submit" 
                            class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3.5 rounded-full transition duration-150 uppercase tracking-wider shadow-md shadow-rose-600/10"
                        >
                            💾 Simpan Pengaturan Wilayah
                        </button>
                    </div>
                </form>

                <!-- PANEL SIMULASI GPS REALTIME -->
                @if(app()->environment('local', 'testing'))
                <div class="mt-8 bg-slate-900 border border-slate-800 text-slate-300 p-5 rounded-3xl shadow-sm space-y-3.5">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                        <span class="font-bold text-rose-500 flex items-center gap-1.5 text-xs">
                            <span class="w-2.5 h-2.5 bg-rose-500 rounded-full {{ $is_simulating ? 'animate-ping' : '' }}"></span>
                            📡 SIMULATOR REAL-TIME GPS
                        </span>
                        <span class="text-[9px] text-slate-500">MOCK GPS SENSOR</span>
                    </div>

                    <p class="text-slate-400 leading-relaxed text-[10px]">
                        Mengaktifkan fitur ini akan membuat pergerakan GPS virtual. Sistem akan mengubah koordinat Latitude/Longitude secara acak halus (jitter) setiap 10 detik dan menyimpannya secara otomatis ke database untuk feed matching.
                    </p>

                    <div class="flex justify-between items-center gap-3">
                        <div>
                            <span class="text-[9px] text-slate-500 block">STATUS SIMULASI:</span>
                            <span class="font-bold {{ $is_simulating ? 'text-emerald-400' : 'text-slate-400' }}">
                                {{ $is_simulating ? '● AKTIF (BERGERAK)' : '○ NONAKTIF' }}
                            </span>
                        </div>

                        <button 
                            type="button" 
                            wire:click="toggleSimulation" 
                            class="font-bold text-[9px] px-4 py-2.5 rounded-full uppercase transition duration-150 shadow-sm"
                            style="{{ $is_simulating ? 'background-color: #e11d48; border-color: #e11d48; color: white;' : 'background-color: #1e293b; border-color: #334155; color: #cbd5e1;' }}"
                        >
                            {{ $is_simulating ? 'Hentikan Simulasi' : 'Mulai Simulasi GPS' }}
                        </button>
                    </div>
                </div>
                @endif


            </div>

        </div>

    </div>
</div>

@php
    $wilayahMap = [];
    foreach($wilayah_list as $w) {
        $coords = $w->center_coordinates;
        $wilayahMap[$w->id] = [
            'id' => $w->id,
            'name' => $w->name,
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
        ];
    }
@endphp

@script
<script>
    let map;
    let marker;
    let circle;
    
    let currentLat = parseFloat($wire.current_lat);
    let currentLng = parseFloat($wire.current_lng);
    let currentRadius = parseFloat($wire.radius_km);
    let isSimulating = $wire.is_simulating;
    let simulationInterval = null;
    const wilayahCoordsMap = @json($wilayahMap);

    function calcDistanceKm(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    function findClosestWilayah(lat, lng) {
        let best = null;
        let minDist = Infinity;
        for (const [id, w] of Object.entries(wilayahCoordsMap)) {
            if (!w.lat || !w.lng) continue;
            if (w.name.includes('Antar Kota') || w.name.includes('Nasional')) continue;
            const d = calcDistanceKm(lat, lng, w.lat, w.lng);
            if (d < minDist) {
                minDist = d;
                best = w;
            }
        }
        return best;
    }

    // Inisialisasi Peta Leaflet
    function initMap() {
        if (typeof L === 'undefined') {
            return;
        }

        // Cek apakah map sudah di-inisialisasi sebelumnya di container ini
        let container = L.DomUtil.get('leaflet-jastiper-map');
        if (container !== null && container._leaflet_id !== undefined && container._leaflet_id !== null) {
            return;
        }

        // Buat instance map ke div #leaflet-jastiper-map
        map = L.map('leaflet-jastiper-map').setView([currentLat, currentLng], 13);

        // Gunakan tiles OpenStreetMap gratis tanpa API key
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        // Tambahkan Custom Marker Jastiper yang bisa di-drag
        const jastiperIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/854/854878.png', // Icon Pin Bagus
            iconSize: [38, 38],
            iconAnchor: [19, 38],
            popupAnchor: [0, -38]
        });

        marker = L.marker([currentLat, currentLng], {
            draggable: true,
            icon: jastiperIcon
        }).addTo(map);

        marker.bindPopup("<div class='font-sans font-bold text-slate-800 text-center text-xs'>Suaikan Posisi Anda dengan Menarik Pin Ini!</div>").openPopup();

        // Gambar lingkaran radius jangkauan
        circle = L.circle([currentLat, currentLng], {
            color: '#e11d48', // rose-600
            fillColor: '#f43f5e',
            fillOpacity: 0.15,
            radius: currentRadius * 1000 // Leaflet radius dalam satuan meter
        }).addTo(map);

        // Invalidate size to guarantee rendering
        setTimeout(() => {
            if (map) {
                map.invalidateSize();
            }
        }, 250);

        // Event saat marker selesai di-drag
        marker.on('dragend', function (e) {
            const position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });

        // Event saat peta diklik (pindahkan marker)
        map.on('click', function (e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            marker.setLatLng([lat, lng]);
            updateCoordinates(lat, lng);
        });
    }

    // Jalankan inisialisasi peta dengan retry jika Leaflet belum ter-load dari CDN
    let initAttempts = 0;
    function tryInitMap() {
        if (typeof L !== 'undefined') {
            initMap();
        } else if (initAttempts < 50) {
            initAttempts++;
            setTimeout(tryInitMap, 100);
        } else {
            console.error("Leaflet.js failed to load after 5 seconds.");
        }
    }
    
    tryInitMap();

    // Update koordinat di variabel lokal dan memicu Livewire updateLocation
    function updateCoordinates(lat, lng) {
        currentLat = lat;
        currentLng = lng;
        
        // Perbarui visual lingkaran
        if (circle) {
            circle.setLatLng([lat, lng]);
        }
        
        // Perbarui teks UI instan
        document.getElementById('disp-lat').innerText = lat.toFixed(6);
        document.getElementById('disp-lng').innerText = lng.toFixed(6);

        // Deteksi otomatis wilayah terdekat di sisi browser secara instan
        const closest = findClosestWilayah(lat, lng);
        if (closest) {
            const selectEl = document.getElementById('wilayah_id');
            if (selectEl && selectEl.value != closest.id) {
                selectEl.value = closest.id;
            }
        }

        // Panggil fungsi Livewire
        $wire.updateLocation(lat, lng);
    }

    // Fungsi global untuk memperbarui radius lingkaran dari slider (oninput JS)
    window.updateCircleRadius = function(radius) {
        currentRadius = parseFloat(radius);
        document.getElementById('disp-radius').innerText = currentRadius.toFixed(1);
        if (circle) {
            circle.setRadius(currentRadius * 1000);
        }
    };

    // Fungsi global untuk lacak lokasi dari GPS browser
    window.geolocateMe = function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    if (map && marker) {
                        map.setView([lat, lng], 14);
                        marker.setLatLng([lat, lng]);
                        updateCoordinates(lat, lng);
                    }
                },
                (error) => {
                    alert("Gagal melacak lokasi GPS: " + error.message);
                }
            );
        } else {
            alert("Browser Anda tidak mendukung Geolocation.");
        }
    };

    // Responsif terhadap perpindahan wilayah lewat dropdown (pindah ke pusat kota wilayah tersebut)
    $wire.on('pan-to-coordinates', (event) => {
        const data = event[0];
        currentLat = data.lat;
        currentLng = data.lng;
        if (map && marker && circle) {
            map.flyTo([data.lat, data.lng], 13);
            marker.setLatLng([data.lat, data.lng]);
            circle.setLatLng([data.lat, data.lng]);
            circle.setRadius(data.radius * 1000);
            document.getElementById('disp-lat').innerText = data.lat.toFixed(6);
            document.getElementById('disp-lng').innerText = data.lng.toFixed(6);
        }
    });

    // Responsif terhadap event deteksi wilayah otomatis dari server
    $wire.on('wilayah-auto-detected', (event) => {
        const data = event[0];
        const selectEl = document.getElementById('wilayah_id');
        if (selectEl && selectEl.value != data.id) {
            selectEl.value = data.id;
        }
    });

    // Responsif terhadap event simpan pengaturan sukses untuk refresh maps state
    $wire.on('settings-saved', (event) => {
        const data = event[0];
        if (map && marker && circle) {
            map.setView([data.lat, data.lng]);
            marker.setLatLng([data.lat, data.lng]);
            circle.setLatLng([data.lat, data.lng]);
            circle.setRadius(data.radius * 1000);
        }
    });

    // Tangani efek simulasi GPS bergerak secara client-side
    function runSimulation() {
        if (simulationInterval) clearInterval(simulationInterval);

        if (isSimulating) {
            simulationInterval = setInterval(() => {
                // Variasikan latitude & longitude sedikit saja secara halus (sekitar +/- 0.0001 s.d 0.0003 derajat, simulasi berkendara santai)
                const latJitter = (Math.random() - 0.5) * 0.0005;
                const lngJitter = (Math.random() - 0.5) * 0.0005;
                
                const newLat = currentLat + latJitter;
                const newLng = currentLng + lngJitter;

                // Update marker & circle di peta
                if (marker) {
                    marker.setLatLng([newLat, newLng]);
                    updateCoordinates(newLat, newLng);
                }
            }, 10000); // Setiap 10 detik
        }
    }

    // Monitor perubahan is_simulating dari Livewire
    $wire.watch('is_simulating', (value) => {
        isSimulating = value;
        if (isSimulating) {
            runSimulation();
        } else {
            if (simulationInterval) {
                clearInterval(simulationInterval);
                simulationInterval = null;
            }
        }
    });

    // Jalankan simulasi jika diinisiasi sebagai aktif
    if (isSimulating) {
        runSimulation();
    }
</script>
@endscript
