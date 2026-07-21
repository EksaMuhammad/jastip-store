<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminAuthController extends Controller
{
    /**
     * Tampilkan halaman login Admin.
     */
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.verification');
        }

        return view('admin.login');
    }

    /**
     * Proses autentikasi Login Admin.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Attempt login via 'admin' guard
        // Note: Admin model uses getAuthPassword() returning 'password_hash', Laravel handles check automatically.
        if (Auth::guard('admin')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password']
        ], $request->has('remember'))) {
            
            $request->session()->regenerate();
            return redirect()->route('admin.verification');
        }

        return back()->withErrors([
            'email' => 'Email atau Password admin salah.',
        ])->onlyInput('email');
    }

    /**
     * Proses logout Admin.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
