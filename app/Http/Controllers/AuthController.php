<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Tampilkan Halaman Form Login
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return Auth::user()->isGuru()
                ? redirect()->route('guru.dashboard')
                : redirect()->route('devices.index');
        }

        return view('auth.login');
    }

    /**
     * Proses Autentikasi Pengguna
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        // Coba autentikasi
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            $targetRoute = $user->isGuru() ? route('guru.dashboard') : route('devices.index');

            return redirect()->intended($targetRoute)->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Proses Logout Pengguna
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
