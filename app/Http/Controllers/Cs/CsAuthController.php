<?php

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CsAuthController extends Controller
{
    /**
     * Tampilkan formulir login portal Customer Service.
     */
    public function showLogin()
    {
        if (Auth::guard('cs')->check()) {
            return redirect()->route('cs.dashboard');
        }

        return view('cs.auth.login');
    }

    /**
     * Proses autentikasi CS & tandai status online.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email CS wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        $cs = CustomerService::where('email', $request->email)->first();

        if (!$cs || !$cs->is_active) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun CS tidak ditemukan atau dinonaktifkan oleh Administrator.']);
        }

        if (Auth::guard('cs')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            /** @var CustomerService $user */
            $user = Auth::guard('cs')->user();
            $user->update([
                'is_online' => true,
                'work_status' => 'tersedia',
                'last_active_at' => now(),
            ]);

            return redirect()->intended(route('cs.dashboard'))
                ->with('success', "Selamat datang kembali, {$user->name}! Status Anda kini aktif dan siap menerima tiket.");
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['password' => 'Password yang Anda masukkan salah.']);
    }

    /**
     * Logout CS & tandai status offline.
     */
    public function logout(Request $request)
    {
        /** @var CustomerService|null $user */
        $user = Auth::guard('cs')->user();
        if ($user) {
            $user->update([
                'is_online' => false,
                'last_active_at' => now(),
            ]);
        }

        Auth::guard('cs')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('cs.login')
            ->with('success', 'Anda telah berhasil logout. Sampai jumpa kembali!');
    }
}

