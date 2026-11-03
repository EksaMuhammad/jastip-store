<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login & register (gabungan).
     * Selalu tampilkan halaman login agar pengguna bisa login peran lain di tab baru.
     */
    public function showLogin()
    {
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
     * Proses logout untuk guard user (tidak mematikan sesi role lain di tab berbeda).
     */
    public function logout(Request $request)
    {
        $role = $request->input('role') ?? $request->query('role');

        if (!$role) {
            $referer = $request->header('referer', '');
            if (str_contains($referer, '/jastiper')) {
                $role = 'jastiper';
            } elseif (str_contains($referer, '/customer')) {
                $role = 'customer';
            }
        }

        if ($role === 'customer') {
            Auth::guard('customer')->logout();
            return redirect('/')->with('info', 'Berhasil keluar dari akun Customer.');
        }

        if ($role === 'jastiper') {
            Auth::guard('jastiper')->logout();
            return redirect('/')->with('info', 'Berhasil keluar dari akun Jastiper.');
        }

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
