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
    public int $step = 1; // 1 = Input (Phone / Register / Login Password), 2 = OTP verification
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
            $this->success_message = 'Nomor HP belum terdaftar. Silakan lengkapi data Anda untuk mendaftar.';
        } else {
            // User terdaftar -> Tampilkan form password untuk login instan
            $this->is_new_user = false;
            $this->show_password_field = true;
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

        // Cari user
        if ($this->role === 'customer') {
            $user = Customer::where('phone_number', $this->phone_number)->first();
        } else {
            $user = Jastiper::where('phone_number', $this->phone_number)->first();
        }

        if (!$user) {
            $this->error_message = 'User tidak ditemukan.';
            return;
        }

        // Cek password
        if (!$user->password || !Hash::check($this->login_password, $user->password)) {
            $this->error_message = 'Kata sandi salah. Jika lupa, Anda bisa login menggunakan OTP.';
            return;
        }

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

        if ($this->role === 'customer') {
            $user = Customer::where('phone_number', $this->phone_number)->first();
        } else {
            $user = Jastiper::where('phone_number', $this->phone_number)->first();
        }

        if (!$user) {
            $this->error_message = 'User tidak ditemukan.';
            return;
        }

        if ($user->otp_code !== $this->otp_input) {
            $this->error_message = 'Kode OTP salah.';
            return;
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            $this->error_message = 'Kode OTP telah kedaluwarsa. Kirim ulang OTP.';
            return;
        }

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
        $this->step = 1;
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

<div class="w-full max-w-md mx-auto" x-data="{ countdown: @entangle('countdown'), timer: null }" x-init="
    timer = setInterval(() => {
        if (countdown > 0) {
            countdown--;
        }
    }, 1000);
    $watch('step', value => {
        if (value === 2) {
            countdown = 60;
            clearInterval(timer);
            timer = setInterval(() => {
                if (countdown > 0) {
                    countdown--;
                }
            }, 1000);
        }
    });
">
    <div class="bg-white border-2 border-slate-900 shadow-[8px_8px_0px_0px_rgba(15,23,42,1)] rounded-sm p-6 sm:p-8">
        
        <!-- Header Brand -->
        <div class="flex flex-col items-center mb-6">
            <div class="w-12 h-12 bg-slate-900 rounded-sm flex items-center justify-center border border-slate-800 shadow-sm mb-3">
                <span class="font-display font-black text-white text-base tracking-tighter">JK</span>
            </div>
            <h2 class="font-display font-extrabold text-2xl text-slate-900">
                Jastip<span class="text-rose-600">Kuy</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1 text-center">
                @if ($step === 2)
                    Masukkan kode verifikasi OTP yang kami kirim ke WhatsApp Anda
                @elseif ($show_password_field)
                    Masukkan kata sandi akun Anda untuk masuk
                @else
                    Masuk atau daftar menggunakan nomor HP Anda
                @endif
            </p>
        </div>

        <!-- Alert Notification -->
        @if ($error_message)
            <div class="mb-4 bg-rose-50 border-2 border-rose-200 p-3 text-rose-700 text-xs font-semibold rounded-sm flex items-start gap-2 animate-pulse">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ $error_message }}</span>
            </div>
        @endif

        @if ($success_message)
            <div class="mb-4 bg-emerald-50 border-2 border-emerald-200 p-3 text-emerald-700 text-xs font-semibold rounded-sm flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $success_message }}</span>
            </div>
        @endif

        <!-- STEP 1: Phone / Register / Login Password -->
        @if ($step === 1)
            <form wire:submit.prevent="{{ $is_new_user ? 'registerAndSendOtp' : ($show_password_field ? 'loginWithPassword' : 'startOtpProcess') }}" class="space-y-5">
                
                <!-- Role Selector (Hidden if typing password to keep focus) -->
                @if (!$show_password_field)
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Peran Anda</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative border-2 {{ $role === 'customer' ? 'border-rose-600 bg-rose-50/30' : 'border-slate-200 bg-white hover:border-slate-300' }} p-3.5 rounded-sm flex flex-col items-center justify-center cursor-pointer transition">
                                <input type="radio" wire:model.live="role" value="customer" class="sr-only">
                                <div class="w-7 h-7 rounded-sm flex items-center justify-center mb-1.5 {{ $role === 'customer' ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                </div>
                                <span class="text-xs font-bold text-slate-900">Customer</span>
                                <span class="text-[9px] text-slate-400 mt-0.5">Penitip Belanjaan</span>
                            </label>

                            <label class="relative border-2 {{ $role === 'jastiper' ? 'border-rose-600 bg-rose-50/30' : 'border-slate-200 bg-white hover:border-slate-300' }} p-3.5 rounded-sm flex flex-col items-center justify-center cursor-pointer transition">
                                <input type="radio" wire:model.live="role" value="jastiper" class="sr-only">
                                <div class="w-7 h-7 rounded-sm flex items-center justify-center mb-1.5 {{ $role === 'jastiper' ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <span class="text-xs font-bold text-slate-900">Jastiper</span>
                                <span class="text-[9px] text-slate-400 mt-0.5">Kurir / Pembeli</span>
                            </label>
                        </div>
                    </div>
                @endif

                <!-- Phone Number Input -->
                <div>
                    <label for="phone_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Handphone (Aktif)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-slate-400 font-bold text-sm">+62</span>
                        <input type="text" id="phone_number" wire:model="phone_number" placeholder="81234567890" {{ ($is_new_user || $show_password_field) ? 'readonly' : '' }} class="w-full bg-slate-50 border-2 border-slate-200 focus:border-slate-900 text-slate-800 text-sm font-bold pl-12 pr-3 py-2.5 rounded-sm outline-none transition {{ ($is_new_user || $show_password_field) ? 'opacity-70 cursor-not-allowed' : '' }}">
                    </div>
                    @error('phone_number') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <!-- LOGIN WITH PASSWORD FIELDS -->
                @if ($show_password_field)
                    <div class="space-y-4 border-t-2 border-dashed border-slate-100 pt-4 animate-fade-in">
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label for="login_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Kata Sandi</label>
                                <span class="text-[10px] text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-sm font-bold uppercase">Akun Terdaftar</span>
                            </div>
                            <input type="password" id="login_password" wire:model="login_password" placeholder="Masukkan kata sandi Anda" class="w-full bg-slate-50 border-2 border-slate-200 focus:border-slate-900 text-slate-800 text-sm font-semibold px-3 py-2.5 rounded-sm outline-none transition">
                            @error('login_password') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-2 space-y-2">
                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-center py-3 rounded-sm shadow-[2px_2px_0px_0px_rgba(244,63,94,1)] border-2 border-slate-900 transition duration-150 uppercase tracking-wide text-xs">
                            Masuk Ke Akun
                        </button>
                        
                        <button type="button" wire:click="loginWithOtpDirect" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-3 rounded-sm shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] border-2 border-slate-900 transition duration-150 uppercase tracking-wide text-xs">
                            Login dengan Kode OTP WA
                        </button>

                        <button type="button" wire:click="goBack" class="w-full bg-white hover:bg-slate-50 text-slate-700 font-semibold text-center py-2.5 mt-2 rounded-sm border-2 border-slate-200 text-xs transition">
                            Ganti Nomor HP / Kembali
                        </button>
                    </div>
                @endif

                <!-- NEW REGISTRATION FIELDS -->
                @if ($is_new_user)
                    <div class="space-y-4 border-t-2 border-dashed border-slate-100 pt-4 animate-fade-in">
                        
                        <!-- Name Input -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                            <input type="text" id="name" wire:model="name" placeholder="Masukkan nama lengkap Anda" class="w-full bg-slate-50 border-2 border-slate-200 focus:border-slate-900 text-slate-800 text-sm font-semibold px-3 py-2.5 rounded-sm outline-none transition">
                            @error('name') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <!-- Wilayah Selection (Jastiper only) -->
                        @if ($role === 'jastiper')
                            <div wire:key="wilayah-select-container">
                                <label for="wilayah_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Wilayah Operasional</label>
                                <select id="wilayah_id" wire:model="wilayah_id" wire:key="wilayah-select-element" class="w-full bg-slate-50 border-2 border-slate-200 focus:border-slate-900 text-slate-800 text-sm font-semibold p-2.5 rounded-sm outline-none transition">
                                    <option value="" wire:key="wilayah-opt-default">-- Pilih Wilayah --</option>
                                    @foreach ($this->getWilayahList() as $wilayah)
                                        <option value="{{ $wilayah->id }}" wire:key="wilayah-opt-{{ $wilayah->id }}">{{ $wilayah->name }}</option>
                                    @endforeach
                                </select>
                                @error('wilayah_id') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <!-- Password Input -->
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi Baru</label>
                            <input type="password" id="password" wire:model="password" placeholder="Minimal 6 karakter" class="w-full bg-slate-50 border-2 border-slate-200 focus:border-slate-900 text-slate-800 text-sm font-semibold px-3 py-2.5 rounded-sm outline-none transition">
                            @error('password') <span class="text-rose-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                    </div>
                @endif

                <!-- Action Button for first check / registration -->
                @if (!$show_password_field)
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-3 rounded-sm shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] border-2 border-slate-900 transition duration-150 uppercase tracking-wide text-xs">
                            {{ $is_new_user ? 'Daftar & Kirim OTP WA' : 'Lanjutkan' }}
                        </button>
                        
                        @if ($is_new_user)
                            <button type="button" wire:click="goBack" class="w-full bg-white hover:bg-slate-50 text-slate-700 font-semibold text-center py-2.5 mt-2 rounded-sm border-2 border-slate-200 text-xs transition">
                                Ganti Nomor HP / Batal
                            </button>
                        @endif
                    </div>
                @endif

            </form>
        @endif

        <!-- STEP 2: Verify OTP Code -->
        @if ($step === 2)
            <form wire:submit.prevent="verifyOtp" class="space-y-5">
                
                <!-- OTP Digits Container -->
                <div>
                    <label for="otp_input" class="block text-xs font-bold text-slate-700 uppercase tracking-wider text-center mb-4">
                        Masukkan 6-Digit OTP yang dikirim ke WhatsApp <br/>
                        <span class="text-rose-600 font-extrabold">+62 {{ $phone_number }}</span>
                    </label>
                    
                    <div class="flex justify-center">
                        <input type="text" id="otp_input" wire:model="otp_input" maxlength="6" placeholder="******" class="w-40 text-center tracking-[1em] text-2xl font-black bg-slate-50 border-2 border-slate-900 p-2.5 rounded-sm outline-none focus:ring-2 focus:ring-rose-500 transition">
                    </div>
                    @error('otp_input') <span class="text-rose-600 text-xs mt-2 block text-center font-medium">{{ $message }}</span> @enderror
                </div>

                <!-- Submit verification -->
                <div class="pt-2">
                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-3 rounded-sm shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] border-2 border-slate-900 transition uppercase tracking-wide text-xs">
                        Verifikasi & Masuk
                    </button>
                </div>

                <!-- Resend & Countdown Info -->
                <div class="text-center text-xs text-slate-500 pt-1">
                    <div x-show="countdown > 0">
                        Kirim ulang OTP tersedia dalam <span class="font-bold text-rose-600" x-text="countdown + ' detik'"></span>
                    </div>
                    <div x-show="countdown === 0">
                        Tidak menerima kode? 
                        <button type="button" wire:click="resendOtp" class="font-bold text-rose-600 hover:underline">
                            Kirim Ulang OTP
                        </button>
                    </div>
                </div>

                <!-- Back to Step 1 link -->
                <div class="text-center pt-2">
                    <button type="button" wire:click="goBack" class="text-slate-400 hover:text-slate-900 text-xs font-semibold underline">
                        Ganti Nomor HP / Kembali
                    </button>
                </div>

            </form>
        @endif

    </div>

    <!-- Debug OTP Panel (Only visible for local testing) -->
    @if ($debug_otp && app()->environment('local', 'testing'))
        <div class="mt-6 bg-slate-900 border-2 border-slate-800 text-slate-300 p-4 rounded-sm shadow-sm font-mono text-xs animate-fade-in">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-2">
                <span class="font-bold text-rose-500 flex items-center gap-1.5">
                    <span class="w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
                    DEBUG OTP PANEL
                </span>
                <span class="text-[10px] text-slate-500">MOCK SMS/WA GATEWAY</span>
            </div>
            <div class="space-y-1">
                <div>Phone: <span class="text-white font-bold">+62 {{ $phone_number }}</span></div>
                <div>Role: <span class="text-white font-bold">{{ strtoupper($role) }}</span></div>
                <div class="bg-slate-950 p-2 border border-slate-800 text-center text-lg font-black text-emerald-400 tracking-widest mt-2 rounded-sm select-all cursor-pointer" title="Klik untuk menyalin">
                    {{ $debug_otp }}
                </div>
                
                @if(env('WHATSAPP_API_KEY') === 'Isi_Token_Fonnte_Anda_Disini' || empty(env('WHATSAPP_API_KEY')))
                    <div class="text-[9px] text-slate-400 mt-2 bg-slate-950/60 p-2 border border-dashed border-slate-700 leading-normal rounded-sm">
                        💡 <b>WA Asli Belum Aktif:</b> Karena <code>WHATSAPP_API_KEY</code> di file <code>.env</code> masih berisi default. Gunakan kode debug di atas untuk masuk.
                    </div>
                @else
                    <div class="text-[9px] text-emerald-400 mt-2 bg-emerald-950/30 p-2 border border-emerald-900/60 leading-normal rounded-sm">
                        ✅ <b>WA Asli Aktif:</b> Token <code>WHATSAPP_API_KEY</code> terdeteksi. Pesan OTP harusnya juga sudah masuk ke WhatsApp nomor Anda.
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>