<?php

use Livewire\Component;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use App\Services\WhatsAppService;

new class extends Component
{
    // Form inputs
    public string $phone_number = '';
    public string $role = 'customer'; // customer or jastiper
    public string $name = '';
    public $wilayah_id = '';
    public string $password = ''; // For registration
    public string $login_password = ''; // For password-based login
    public string $otp_input = '';

    // State management
    public int $step = 0; // 0 = Welcome/Pembuka, 1 = Input (Phone / Register / Login Password), 2 = OTP verification
    public bool $is_new_user = false; // true if phone number not registered
    public bool $show_password_field = false; // true if registered and we prompt for password
    public int $countdown = 60;
    public string $debug_otp = ''; // For debugging purposes

    // Feedback messages
    public string $error_message = '';
    public string $success_message = '';

    /**
     * Dapatkan daftar wilayah aktif untuk dropdown Jastiper.
     * Auto-seed jika kosong agar dropdown tidak macet.
     */
    public function getWilayahList()
    {
        $list = Wilayah::where('is_active', true)->get();
        if ($list->isEmpty()) {
            Wilayah::create([
                'name' => 'Malang Kota',
                'default_radius_km' => 5.00,
                'is_active' => true,
            ]);
            $list = Wilayah::where('is_active', true)->get();
        }
        return $list;
    }

    /**
     * Normalisasi format nomor HP ke standar 08xxxx
     */
    public function normalizePhone(string $phone): string
    {
        // Hapus karakter non-digit
        $cleaned = preg_replace('/\D/', '', $phone);
        // Jika berawalan +62 atau 62, ubah ke 0
        if (str_starts_with($cleaned, '62')) {
            $cleaned = '0' . substr($cleaned, 2);
        }
        // Jika berawalan 8, ubah ke 08
        if (str_starts_with($cleaned, '8') && !str_starts_with($cleaned, '08')) {
            $cleaned = '0' . $cleaned;
        }
        return $cleaned;
    }

    /**
     * Navigasi ke Step 1 (Input Nomor HP) dari Pembuka
     */
    public function goToPhoneStep()
    {
        $this->step = 1;
        $this->error_message = '';
        $this->success_message = '';
    }

    /**
     * Navigasi ke Step 0 (Pembuka)
     */
    public function goToWelcomeStep()
    {
        $this->step = 0;
        $this->error_message = '';
        $this->success_message = '';
    }

    /**
     * Memulai proses login/register: cek nomor HP
     */
    public function startOtpProcess()
    {
        $this->resetErrorBag();
        $this->error_message = '';
        $this->success_message = '';

        // Validasi awal
        $this->validate([
            'phone_number' => ['required', 'string', 'min:9', 'max:15'],
            'role' => ['required', 'in:customer,jastiper'],
        ], [
            'phone_number.required' => 'Nomor HP wajib diisi.',
            'phone_number.min' => 'Nomor HP minimal 9 karakter.',
            'phone_number.max' => 'Nomor HP maksimal 15 karakter.',
        ]);

        // Normalisasi nomor HP
        $this->phone_number = $this->normalizePhone($this->phone_number);

        // Cek apakah terdaftar
        if ($this->role === 'customer') {
            $userExists = Customer::where('phone_number', $this->phone_number)->exists();
        } else {
            $userExists = Jastiper::where('phone_number', $this->phone_number)->exists();
        }

        if (!$userExists) {
            // User baru -> Tampilkan formulir registrasi
            $this->is_new_user = true;
            $this->show_password_field = false;
            $this->step = 1;
            $this->success_message = 'Nomor HP belum terdaftar. Silakan lengkapi data Anda untuk mendaftar.';
        } else {
            // User terdaftar -> Tampilkan form password untuk login instan
            $this->is_new_user = false;
            $this->show_password_field = true;
            $this->step = 1;
            $this->login_password = '';
        }
    }

    /**
     * Login instan menggunakan password (tanpa OTP)
     */
    public function loginWithPassword()
    {
        $this->resetErrorBag();
        $this->error_message = '';

        $this->validate([
            'login_password' => ['required', 'string'],
        ], [
            'login_password.required' => 'Kata sandi wajib diisi.',
        ]);

        $rateLimitKey = 'login-password:' . $this->role . ':' . $this->phone_number;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->error_message = "Terlalu banyak percobaan salah. Silakan coba lagi dalam {$seconds} detik, atau login menggunakan OTP.";
            return;
        }

        // Cari user
        if ($this->role === 'customer') {
            $user = Customer::where('phone_number', $this->phone_number)->first();
        } else {
            $user = Jastiper::where('phone_number', $this->phone_number)->first();
        }

        if (!$user) {
            RateLimiter::hit($rateLimitKey, 300);
            $this->error_message = 'User tidak ditemukan.';
            return;
        }

        // Cek password
        if (!$user->password || !Hash::check($this->login_password, $user->password)) {
            RateLimiter::hit($rateLimitKey, 300);
            $this->error_message = 'Kata sandi salah. Jika lupa, Anda bisa login menggunakan OTP.';
            return;
        }

        RateLimiter::clear($rateLimitKey);

        // Login
        Auth::guard($this->role)->login($user);
        session()->regenerate();

        // Redirect
        if ($this->role === 'customer') {
            return redirect()->route('customer.dashboard');
        } else {
            return redirect()->route('jastiper.dashboard');
        }
    }

    /**
     * Mengabaikan password dan login dengan OTP (untuk user terdaftar)
     */
    public function loginWithOtpDirect()
    {
        $this->sendOtp(false);
    }

    /**
     * Mendaftarkan user baru dan mengirimkan OTP verifikasi nomor HP
     */
    public function registerAndSendOtp()
    {
        $this->resetErrorBag();
        $this->error_message = '';
        $this->success_message = '';

        // Normalisasi nomor HP sebelum validasi/save
        $this->phone_number = $this->normalizePhone($this->phone_number);

        // Validasi pendaftaran
        $rules = [
            'phone_number' => ['required', 'string', 'min:9', 'max:15'],
            'role' => ['required', 'in:customer,jastiper'],
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
        ];

        $messages = [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama lengkap minimal 3 karakter.',
            'name.max' => 'Nama lengkap maksimal 50 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ];

        if ($this->role === 'jastiper') {
            $rules['wilayah_id'] = ['required', 'exists:wilayah,id'];
            $messages['wilayah_id.required'] = 'Wilayah operasional wajib dipilih.';
        }

        $this->validate($rules, $messages);

        // Kirim OTP dan buat record database
        $this->sendOtp(true);
    }

    /**
     * Kirim kode OTP
     */
    public function sendOtp($isRegistering = false)
    {
        $rateLimitKey = 'send-otp:' . $this->role . ':' . $this->phone_number;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->error_message = "Terlalu banyak permintaan OTP. Silakan coba lagi dalam {$seconds} detik.";
            return;
        }

        RateLimiter::hit($rateLimitKey, 120);

        // Generate 6 digit OTP
        $otpCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = now()->addMinutes(5);

        if ($isRegistering) {
            $hashedPassword = Hash::make($this->password);
            
            if ($this->role === 'customer') {
                Customer::create([
                    'phone_number' => $this->phone_number,
                    'name' => $this->name,
                    'password' => $hashedPassword,
                    'otp_code' => $otpCode,
                    'otp_expires_at' => $otpExpiresAt,
                ]);
            } else {
                Jastiper::create([
                    'phone_number' => $this->phone_number,
                    'name' => $this->name,
                    'password' => $hashedPassword,
                    'otp_code' => $otpCode,
                    'otp_expires_at' => $otpExpiresAt,
                    'wilayah_id' => $this->wilayah_id,
                    'radius_km' => 5.00,
                    'is_available' => true,
                    'verification_status' => 'belum',
                ]);
            }
        } else {
            // Update user terdaftar
            if ($this->role === 'customer') {
                Customer::where('phone_number', $this->phone_number)->update([
                    'otp_code' => $otpCode,
                    'otp_expires_at' => $otpExpiresAt,
                ]);
            } else {
                Jastiper::where('phone_number', $this->phone_number)->update([
                    'otp_code' => $otpCode,
                    'otp_expires_at' => $otpExpiresAt,
                ]);
            }
        }

        // Simpan ke debug properti
        $this->debug_otp = $otpCode;

        // Kirim via WhatsApp API (Fonnte)
        WhatsAppService::sendOtp($this->phone_number, $otpCode);

        // Log
        Log::info("OTP dikirim ke {$this->phone_number} ({$this->role}): {$otpCode}");

        $this->step = 2;
        $this->countdown = 60;
        $this->otp_input = '';
        $this->success_message = 'Kode OTP verifikasi berhasil dikirim!';
    }

    /**
     * Verifikasi kode OTP untuk menyelesaikan login/registrasi
     */
    public function verifyOtp()
    {
        $this->resetErrorBag();
        $this->error_message = '';

        $this->validate([
            'otp_input' => ['required', 'string', 'size:6'],
        ], [
            'otp_input.required' => 'Kode OTP wajib diisi.',
            'otp_input.size' => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        $rateLimitKey = 'verify-otp:' . $this->role . ':' . $this->phone_number;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->error_message = "Terlalu banyak percobaan salah. Silakan kirim ulang OTP dalam {$seconds} detik.";
            return;
        }

        if ($this->role === 'customer') {
            $user = Customer::where('phone_number', $this->phone_number)->first();
        } else {
            $user = Jastiper::where('phone_number', $this->phone_number)->first();
        }

        if (!$user) {
            RateLimiter::hit($rateLimitKey, 300);
            $this->error_message = 'User tidak ditemukan.';
            return;
        }

        if (!hash_equals((string) $user->otp_code, $this->otp_input)) {
            RateLimiter::hit($rateLimitKey, 300);
            $this->error_message = 'Kode OTP salah.';
            return;
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            $this->error_message = 'Kode OTP telah kedaluwarsa. Kirim ulang OTP.';
            return;
        }

        RateLimiter::clear($rateLimitKey);

        // Sukses verifikasi
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        Auth::guard($this->role)->login($user);
        session()->regenerate();

        if ($this->role === 'customer') {
            return redirect()->route('customer.dashboard');
        } else {
            return redirect()->route('jastiper.dashboard');
        }
    }

    /**
     * Kirim ulang OTP dari step 2
     */
    public function resendOtp()
    {
        $this->error_message = '';
        $this->success_message = '';
        $this->sendOtp(false);
    }

    /**
     * Reset / Kembali ke form HP awal
     */
    public function goBack()
    {
        if ($this->step === 2) {
            $this->step = 1;
        } else {
            $this->step = 0;
        }
        $this->is_new_user = false;
        $this->show_password_field = false;
        $this->otp_input = '';
        $this->password = '';
        $this->login_password = '';
        $this->debug_otp = '';
        $this->error_message = '';
        $this->success_message = '';
    }
};
?>

<div class="w-full" x-data="{ 
    countdown: @entangle('countdown'), 
    timer: null,
    slide: 0,
    otpDigits: ['', '', '', '', '', ''],
    initOtpBoxes() {
        this.otpDigits = ['', '', '', '', '', ''];
        if ($wire.otp_input && $wire.otp_input.length === 6) {
            this.otpDigits = $wire.otp_input.split('');
        }
    },
    syncOtpToLivewire() {
        $wire.otp_input = this.otpDigits.join('');
    },
    handleDigitInput(e, index) {
        const val = e.target.value.replace(/\D/g, '');
        if (val.length > 0) {
            this.otpDigits[index] = val[val.length - 1];
            if (index < 5) {
                this.$refs['otpBox' + (index + 1)].focus();
            }
        } else {
            this.otpDigits[index] = '';
        }
        this.syncOtpToLivewire();
    },
    handleDigitKeydown(e, index) {
        if (e.key === 'Backspace' && !this.otpDigits[index] && index > 0) {
            this.$refs['otpBox' + (index - 1)].focus();
        }
    },
    handleOtpPaste(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
        if (pasted) {
            for (let i = 0; i < 6; i++) {
                this.otpDigits[i] = pasted[i] || '';
            }
            this.syncOtpToLivewire();
            const focusIndex = Math.min(pasted.length, 5);
            if (this.$refs['otpBox' + focusIndex]) {
                this.$refs['otpBox' + focusIndex].focus();
            }
        }
    }
}" x-init="
    timer = setInterval(() => {
        if (countdown > 0) {
            countdown--;
        }
    }, 1000);
    $watch('step', value => {
        if (value === 2) {
            countdown = 60;
            initOtpBoxes();
            $nextTick(() => {
                if ($refs.otpBox0) $refs.otpBox0.focus();
            });
            clearInterval(timer);
            timer = setInterval(() => {
                if (countdown > 0) {
                    countdown--;
                }
            }, 1000);
        }
    });
">
    <div class="lg:grid lg:grid-cols-12 lg:gap-8 xl:gap-12 items-center">
        
        <!-- DESKTOP HERO SHOWCASE (Shown on lg+ screens) -->
        <div class="hidden lg:block lg:col-span-6 xl:col-span-7">
            <div class="relative bg-gradient-to-br from-rose-600 via-rose-700 to-slate-900 rounded-3xl p-8 xl:p-12 text-white overflow-hidden shadow-2xl border border-rose-500/20">
                <!-- Background Glowing Orbs -->
                <div class="absolute -top-24 -left-24 w-80 h-80 bg-rose-400/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-amber-400/20 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Brand Header -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 bg-white text-rose-600 rounded-2xl flex items-center justify-center font-black text-xl shadow-lg">
                        JK
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight">Jastip<span class="text-amber-400">Kuy</span></h1>
                        <p class="text-xs text-rose-100/80 font-medium">Titip Belanja & Delivery On-Demand</p>
                    </div>
                </div>

                <!-- Main Hero Illustration Display -->
                <div class="relative my-6 flex justify-center items-center">
                    <div class="relative z-10 w-full max-w-md bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/15 shadow-xl text-center">
                        <img src="{{ asset('images/belanjaan.png') }}" alt="JastipKuy Hero Illustration" class="w-64 h-auto mx-auto drop-shadow-2xl transition duration-500 hover:scale-105" />
                        
                        <div class="mt-6">
                            <h2 class="text-xl font-black text-white">Solusi Jastip Praktis #1</h2>
                            <p class="text-xs text-rose-100/90 mt-2 leading-relaxed">
                                Titip belanjaan toko impian, makanan khas daerah, atau barang antar kota. Pengantaran fleksibel & transaksi 100% aman via Escrow!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature Badges List -->
                <div class="grid grid-cols-3 gap-3 mt-8 pt-6 border-t border-white/10">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 text-center border border-white/10">
                        <div class="w-8 h-8 mx-auto mb-1 bg-amber-400/20 rounded-lg flex items-center justify-center text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <div class="text-[11px] font-bold">Multi Store</div>
                        <div class="text-[9px] text-rose-100/70">Bebas pilih lokasi</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 text-center border border-white/10">
                        <div class="w-8 h-8 mx-auto mb-1 bg-amber-400/20 rounded-lg flex items-center justify-center text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div class="text-[11px] font-bold">Live Tracking</div>
                        <div class="text-[9px] text-rose-100/70">Pantau proses jastip</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 text-center border border-white/10">
                        <div class="w-8 h-8 mx-auto mb-1 bg-amber-400/20 rounded-lg flex items-center justify-center text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <div class="text-[11px] font-bold">Rekber Safe</div>
                        <div class="text-[9px] text-rose-100/70">Dana aman di escrow</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MOBILE APP CONTAINER FRAME (Gojek Design Reference) -->
        <div class="lg:col-span-6 xl:col-span-5 max-w-md mx-auto w-full">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden min-h-[620px] flex flex-col justify-between relative">
                
                <!-- TOP APPLICATION BAR -->
                <div class="px-5 pt-5 pb-3 flex items-center justify-between border-b border-slate-100">
                    @if ($step > 0)
                        <button type="button" wire:click="goBack" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                    @else
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-rose-600 text-white rounded-xl flex items-center justify-center font-black text-xs shadow-md">
                                JK
                            </div>
                            <span class="font-black text-slate-900 text-sm tracking-tight">Jastip<span class="text-rose-600">Kuy</span></span>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <!-- Help Icon Button -->
                        <button type="button" onclick="showMaintenanceToast(event)" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold hover:bg-slate-200 transition" title="Bantuan">
                            ?
                        </button>
                        
                        <!-- Language Pill -->
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 cursor-pointer transition">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.6 9h16.8M3.6 15h16.8"></path></svg>
                            <span>Bahasa Indonesia</span>
                        </div>
                    </div>
                </div>

                <!-- MAIN CONTENT AREA BASED ON STEP -->
                <div class="p-6 flex-1 flex flex-col justify-between">
                    
                    <!-- ALERT NOTIFICATIONS -->
                    @if ($error_message)
                        <div class="mb-4 bg-rose-50 border border-rose-200 p-3.5 rounded-2xl text-rose-700 text-xs font-semibold flex items-start gap-2.5 shadow-sm animate-fade-in">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>{{ $error_message }}</span>
                        </div>
                    @endif

                    @if ($success_message)
                        <div class="mb-4 bg-emerald-50 border border-emerald-200 p-3.5 rounded-2xl text-emerald-700 text-xs font-semibold flex items-start gap-2.5 shadow-sm animate-fade-in">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ $success_message }}</span>
                        </div>
                    @endif

                    <!-- STEP 0: GOJEK-STYLE WELCOME / PEMBUKA SCREEN -->
                    @if ($step === 0)
                        <div class="flex-1 flex flex-col justify-between py-2">
                            
                            <!-- Carousel Illustration & Greetings -->
                            <div class="text-center my-auto space-y-4">
                                <div class="relative w-full max-w-[260px] mx-auto aspect-square rounded-3xl bg-gradient-to-b from-rose-50 to-rose-100/50 p-4 flex items-center justify-center border border-rose-100/80 shadow-inner overflow-hidden">
                                    <div class="absolute inset-0 bg-white/40 backdrop-blur-3xl"></div>
                                    
                                    <!-- Dynamic Slide Images -->
                                    <div x-show="slide === 0" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative z-10">
                                        <img src="{{ asset('images/belanjaan.png') }}" alt="Belanja Jastip" class="h-44 w-auto object-contain mx-auto drop-shadow-xl" />
                                    </div>
                                    <div x-show="slide === 1" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative z-10" style="display: none;">
                                        <img src="{{ asset('images/pengantaran.png') }}" alt="Delivery Jastip" class="h-44 w-auto object-contain mx-auto drop-shadow-xl" />
                                    </div>
                                    <div x-show="slide === 2" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative z-10" style="display: none;">
                                        <img src="{{ asset('images/serah-terima.png') }}" alt="Escrow Jastip" class="h-44 w-auto object-contain mx-auto drop-shadow-xl" />
                                    </div>
                                </div>

                                <!-- Text Headlines -->
                                <div class="px-2">
                                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                                        Selamat datang di JastipKuy!
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                                        Aplikasi yang buat belanja & pengantaran barangmu makin gampang. Siap bantu kebutuhanmu, kapan pun, di mana pun.
                                    </p>
                                </div>

                                <!-- Carousel Pagination Dots -->
                                <div class="flex justify-center items-center gap-1.5 pt-2">
                                    <button type="button" @click="slide = 0" class="h-2 rounded-full transition-all duration-300" :class="slide === 0 ? 'w-6 bg-rose-600' : 'w-2 bg-slate-200'"></button>
                                    <button type="button" @click="slide = 1" class="h-2 rounded-full transition-all duration-300" :class="slide === 1 ? 'w-6 bg-rose-600' : 'w-2 bg-slate-200'"></button>
                                    <button type="button" @click="slide = 2" class="h-2 rounded-full transition-all duration-300" :class="slide === 2 ? 'w-6 bg-rose-600' : 'w-2 bg-slate-200'"></button>
                                </div>
                            </div>

                            <!-- Terms & Actions -->
                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <p class="text-[11px] text-slate-400 text-center leading-normal">
                                    Dengan melanjutkan, saya setuju data pribadi saya diproses sesuai 
                                    <a href="{{ route('terms') }}" class="text-rose-600 font-semibold hover:underline" target="_blank">Ketentuan Layanan</a> & 
                                    <a href="{{ route('privacy') }}" class="text-rose-600 font-semibold hover:underline" target="_blank">Kebijakan Privasi JastipKuy</a>.
                                </p>

                                <button type="button" wire:click="goToPhoneStep" class="w-full bg-rose-600 hover:bg-rose-700 active:scale-[0.99] text-white font-extrabold text-sm py-3.5 px-4 rounded-2xl shadow-lg shadow-rose-600/25 transition duration-200 flex items-center justify-center gap-2">
                                    <span>Lanjut dengan Nomor HP</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- STEP 1: INPUT NOMOR HP (GOJEK-INSPIRED) -->
                    @if ($step === 1)
                        <div class="flex-1 flex flex-col justify-between py-1">
                            
                            <form wire:submit.prevent="{{ $is_new_user ? 'registerAndSendOtp' : ($show_password_field ? 'loginWithPassword' : 'startOtpProcess') }}" class="space-y-5">
                                
                                <div>
                                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                                        Masukkan nomor HP
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Nomor HP kamu akan dipakai untuk proses verifikasi dan masuk ke akun JastipKuy
                                    </p>
                                </div>

                                <!-- Role Selection Tabs (Customer vs Jastiper) -->
                                @if (!$show_password_field)
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Pilih Tipe Akun</label>
                                        <div class="grid grid-cols-2 gap-2.5 p-1 bg-slate-100 rounded-2xl">
                                            <button type="button" wire:click="$set('role', 'customer')" class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 {{ $role === 'customer' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                                <span>Customer</span>
                                            </button>
                                            <button type="button" wire:click="$set('role', 'jastiper')" class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 {{ $role === 'jastiper' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                <span>Jastiper</span>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <!-- Phone Input Field (Gojek Style with Country Code Badge) -->
                                <div>
                                    <label for="phone_number" class="block text-xs font-bold text-slate-700 mb-2">Nomor HP<span class="text-rose-600">*</span></label>
                                    
                                    <div class="flex items-center gap-2.5">
                                        <!-- Country Code Pill with SVG Flag -->
                                        <div class="flex items-center gap-2 bg-slate-100 border border-slate-200 rounded-2xl px-3.5 py-3 text-slate-800 text-sm font-bold shrink-0">
                                            <svg class="w-4 h-3 rounded-xs shadow-xs border border-slate-300" viewBox="0 0 640 480">
                                                <g fill-rule="evenodd">
                                                    <path fill="#e70011" d="M0 0h640v240H0z"/>
                                                    <path fill="#ffffff" d="M0 240h640v240H0z"/>
                                                </g>
                                            </svg>
                                            <span>+62</span>
                                        </div>
                                        
                                        <!-- Clean Phone Input -->
                                        <input type="text" id="phone_number" wire:model="phone_number" placeholder="81234567890" {{ ($is_new_user || $show_password_field) ? 'readonly' : '' }} class="w-full bg-slate-50 border border-slate-200 focus:border-rose-600 focus:bg-white text-slate-900 text-lg font-bold px-4 py-2.5 rounded-2xl outline-none focus:ring-4 focus:ring-rose-500/10 transition {{ ($is_new_user || $show_password_field) ? 'opacity-75 cursor-not-allowed bg-slate-100' : '' }}" autofocus>
                                    </div>
                                    @error('phone_number') <span class="text-rose-600 text-xs mt-1.5 block font-semibold">{{ $message }}</span> @enderror

                                    <div class="mt-2 text-right">
                                        <button type="button" onclick="showMaintenanceToast(event)" class="text-xs font-bold text-rose-600 hover:underline">
                                            Ada kendala atau lupa nomor?
                                        </button>
                                    </div>
                                </div>

                                <!-- LOGIN WITH PASSWORD FIELDS -->
                                @if ($show_password_field)
                                    <div class="space-y-4 border-t border-slate-100 pt-4 animate-fade-in">
                                        <div>
                                            <div class="flex justify-between items-center mb-1.5">
                                                <label for="login_password" class="block text-xs font-bold text-slate-700">Kata Sandi</label>
                                                <span class="text-[10px] text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-bold">Akun Terdaftar</span>
                                            </div>
                                            <input type="password" id="login_password" wire:model="login_password" placeholder="Masukkan kata sandi akun Anda" class="w-full bg-slate-50 border border-slate-200 focus:border-rose-600 text-slate-800 text-sm font-semibold px-4 py-3 rounded-2xl outline-none focus:ring-4 focus:ring-rose-500/10 transition">
                                            @error('login_password') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- NEW USER REGISTRATION FIELDS -->
                                @if ($is_new_user)
                                    <div class="space-y-4 border-t border-slate-100 pt-4 animate-fade-in">
                                        <div>
                                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                                            <input type="text" id="name" wire:model="name" placeholder="Masukkan nama sesuai KTP / ID" class="w-full bg-slate-50 border border-slate-200 focus:border-rose-600 text-slate-800 text-sm font-semibold px-4 py-3 rounded-2xl outline-none focus:ring-4 focus:ring-rose-500/10 transition">
                                            @error('name') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>

                                        @if ($role === 'jastiper')
                                            <div wire:key="wilayah-select-container">
                                                <label for="wilayah_id" class="block text-xs font-bold text-slate-700 mb-1">Wilayah Operasional</label>
                                                <select id="wilayah_id" wire:model="wilayah_id" wire:key="wilayah-select-element" class="w-full bg-slate-50 border border-slate-200 focus:border-rose-600 text-slate-800 text-sm font-semibold p-3 rounded-2xl outline-none focus:ring-4 focus:ring-rose-500/10 transition">
                                                    <option value="" wire:key="wilayah-opt-default">-- Pilih Wilayah --</option>
                                                    @foreach ($this->getWilayahList() as $wilayah)
                                                        <option value="{{ $wilayah->id }}" wire:key="wilayah-opt-{{ $wilayah->id }}">{{ $wilayah->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('wilayah_id') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                            </div>
                                        @endif

                                        <div>
                                            <label for="password" class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi Baru</label>
                                            <input type="password" id="password" wire:model="password" placeholder="Minimal 6 karakter" class="w-full bg-slate-50 border border-slate-200 focus:border-rose-600 text-slate-800 text-sm font-semibold px-4 py-3 rounded-2xl outline-none focus:ring-4 focus:ring-rose-500/10 transition">
                                            @error('password') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- Submit Button -->
                                <div class="pt-2">
                                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 active:scale-[0.99] text-white font-extrabold text-sm py-3.5 px-4 rounded-2xl shadow-lg shadow-rose-600/25 transition duration-150">
                                        @if ($show_password_field)
                                            Masuk ke Akun
                                        @elseif ($is_new_user)
                                            Daftar & Kirim OTP WA
                                        @else
                                            Lanjut
                                        @endif
                                    </button>
                                </div>

                                <!-- Password login fallback option -->
                                @if ($show_password_field)
                                    <button type="button" wire:click="loginWithOtpDirect" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs py-3 px-4 rounded-2xl transition">
                                        Masuk dengan Kode OTP WhatsApp
                                    </button>
                                @endif

                            </form>

                            <!-- Social Login Divider (Gojek Reference style) -->
                            <div class="mt-6 space-y-4">
                                <div class="relative flex items-center justify-center">
                                    <div class="border-t border-slate-200 w-full"></div>
                                    <span class="bg-white px-3 text-xs text-slate-400 font-semibold absolute">atau</span>
                                </div>

                                <div class="space-y-2.5">
                                    <button type="button" onclick="showMaintenanceToast(event)" class="w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs py-3 px-4 rounded-2xl flex items-center justify-center gap-2.5 shadow-sm transition">
                                        <svg class="w-4 h-4 text-slate-900" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 4.32c.62-.76 1.04-1.81.93-2.86-.9.04-1.99.6-2.63 1.36-.57.66-1.07 1.73-.93 2.76 1.01.08 2.01-.5 2.63-1.26z"/></svg>
                                        <span>Lanjut dengan Apple</span>
                                    </button>

                                    <button type="button" onclick="showMaintenanceToast(event)" class="w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs py-3 px-4 rounded-2xl flex items-center justify-center gap-2.5 shadow-sm transition">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                                        <span>Lanjut dengan Google</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- STEP 2: VERIFIKASI OTP (FIXED 6-DIGIT BOX INPUT) -->
                    @if ($step === 2)
                        <div class="flex-1 flex flex-col justify-between py-1">
                            
                            <form wire:submit.prevent="verifyOtp" class="space-y-6">
                                
                                <div>
                                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                                        Masukkan kode OTP
                                    </h2>
                                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                                        Kode verifikasi 6 digit telah dikirim melalui WhatsApp ke <span class="font-extrabold text-slate-900">+62 {{ $phone_number }}</span>
                                    </p>
                                </div>

                                <!-- 6-DIGIT INDIVIDUAL OTP BOX GRID (NO TEXT CLIPPING) -->
                                <div class="py-2">
                                    <label class="block text-xs font-bold text-slate-700 text-center uppercase tracking-wider mb-4">
                                        Kode Verifikasi (6 Digit)
                                    </label>

                                    <!-- 6 Square Box Input Grid -->
                                    <div class="grid grid-cols-6 gap-2 sm:gap-2.5 max-w-sm mx-auto">
                                        <template x-for="(digit, index) in otpDigits" :key="index">
                                            <input 
                                                type="text" 
                                                inputmode="numeric" 
                                                maxlength="1" 
                                                :x-ref="'otpBox' + index" 
                                                :value="digit"
                                                @input="handleDigitInput($event, index)"
                                                @keydown="handleDigitKeydown($event, index)"
                                                @paste="handleOtpPaste($event)"
                                                class="w-full h-14 text-center font-black text-2xl text-slate-900 bg-slate-50 border-2 border-slate-300 rounded-2xl focus:border-rose-600 focus:bg-white focus:ring-4 focus:ring-rose-500/20 outline-none transition shadow-sm"
                                            />
                                        </template>
                                    </div>

                                    @error('otp_input') 
                                        <span class="text-rose-600 text-xs mt-3 block text-center font-semibold animate-pulse">
                                            {{ $message }}
                                        </span> 
                                    @enderror
                                </div>

                                <!-- Submit Verification Button -->
                                <div>
                                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 active:scale-[0.99] text-white font-extrabold text-sm py-3.5 px-4 rounded-2xl shadow-lg shadow-rose-600/25 transition duration-150">
                                        Verifikasi & Masuk
                                    </button>
                                </div>

                                <!-- Resend OTP & Timer Info -->
                                <div class="text-center text-xs text-slate-500 space-y-2">
                                    <div x-show="countdown > 0" class="font-medium">
                                        Kirim ulang kode tersedia dalam <span class="font-extrabold text-rose-600" x-text="countdown + ' detik'"></span>
                                    </div>
                                    <div x-show="countdown === 0">
                                        Belum menerima kode? 
                                        <button type="button" wire:click="resendOtp" class="font-extrabold text-rose-600 hover:underline">
                                            Kirim Ulang OTP WA
                                        </button>
                                    </div>
                                </div>

                            </form>

                            <!-- Navigation Links -->
                            <div class="pt-4 border-t border-slate-100 text-center">
                                <button type="button" wire:click="goBack" class="text-xs font-bold text-slate-400 hover:text-slate-800 transition">
                                    Ganti Nomor HP / Kembali
                                </button>
                            </div>

                        </div>
                    @endif

                </div>

                <!-- BRAND FOOTER (from JastipKuy) -->
                <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 text-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center justify-center gap-1">
                        from <span class="text-slate-800 font-extrabold">JastipKuy</span>
                    </span>
                </div>

            </div>

            <!-- DEBUG OTP PANEL FOR TESTING ENVIRONMENT -->
            @if ($debug_otp && app()->environment('local', 'testing'))
                <div class="mt-4 bg-slate-900 text-slate-300 p-4 rounded-2xl shadow-lg font-mono text-xs animate-fade-in border border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-2">
                        <span class="font-bold text-rose-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
                            DEBUG OTP SIMULATOR
                        </span>
                        <span class="text-[9px] text-slate-500">DEVELOPMENT MODE</span>
                    </div>
                    <div class="space-y-1">
                        <div>Target HP: <span class="text-white font-bold">+62 {{ $phone_number }}</span></div>
                        <div>Role: <span class="text-white font-bold">{{ strtoupper($role) }}</span></div>
                        <div class="bg-slate-950 p-2.5 border border-slate-800 text-center text-xl font-black text-emerald-400 tracking-widest mt-2 rounded-xl select-all cursor-pointer" title="Klik untuk menyalin">
                            {{ $debug_otp }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>
</div>