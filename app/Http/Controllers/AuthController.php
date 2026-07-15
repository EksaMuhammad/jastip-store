<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login & register (gabungan).
     */
    public function showLogin()
    {
        // Jika sudah login sebagai customer atau jastiper, redirect langsung ke dashboard masing-masing
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        if (Auth::guard('jastiper')->check()) {
            return redirect()->route('jastiper.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Redirect untuk register ke halaman login (karena form disatukan).
     */
    public function showRegister()
    {
        return redirect()->route('login');
    }

    /**
     * Proses logout untuk semua guard user.
     */
    public function logout(Request $request)
    {
        if (Auth::guard('customer')->check()) {
            Auth::guard('customer')->logout();
        }

        if (Auth::guard('jastiper')->check()) {
            Auth::guard('jastiper')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
