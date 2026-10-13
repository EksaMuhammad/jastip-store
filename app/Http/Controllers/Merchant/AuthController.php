<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('merchant')->check()) {
            return redirect()->route('merchant.dashboard');
        }

        return view('merchant.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->has('remember');

        if (Auth::guard('merchant')->attempt($credentials, $remember)) {
            $merchant = Auth::guard('merchant')->user();

            if (!$merchant->is_active) {
                Auth::guard('merchant')->logout();
                return back()->withErrors([
                    'email' => 'Akun merchant Anda sedang dinonaktifkan oleh administrator. Silakan hubungi admin JastipKuy.',
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();
            return redirect()->intended(route('merchant.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::guard('merchant')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('merchant.login')->with('success', 'Anda telah keluar dari Portal Mitra Merchant.');
    }
}
