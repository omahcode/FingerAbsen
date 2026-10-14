<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Tampilkan Halaman Form Login dengan Deteksi Proteksi
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return Auth::user()->isGuru()
                ? redirect()->route('guru.dashboard')
                : redirect()->route('devices.index');
        }

        // Cek apakah user perlu verifikasi anti-bot captcha (setelah 2x percobaan gagal)
        $attempts = session('login_attempts', 0);
        $requireCaptcha = $attempts >= 2;

        if ($requireCaptcha && ! session()->has('login_captcha_question')) {
            $n1 = rand(2, 9);
            $n2 = rand(2, 9);
            session([
                'login_captcha_answer' => $n1 + $n2,
                'login_captcha_question' => "Berapa hasil {$n1} + {$n2}?",
            ]);
        }

        return view('auth.login', [
            'requireCaptcha' => $requireCaptcha,
            'captchaQuestion' => session('login_captcha_question'),
        ]);
    }

    /**
     * Proses Autentikasi Pengguna dengan Proteksi Berlapis
     */
    public function login(Request $request)
    {
        // 1. Validasi Input Dasar
        $rules = [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:4|max:128',
        ];

        $attempts = session('login_attempts', 0);
        $requireCaptcha = $attempts >= 2;

        if ($requireCaptcha) {
            $rules['security_answer'] = 'required|numeric';
        }

        $credentials = $request->validate($rules, [
            'email.required' => 'Email administrator / guru wajib diisi.',
            'email.email' => 'Format email yang dimasukkan tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 4 karakter.',
            'security_answer.required' => 'Pertanyaan keamanan anti-bot wajib dijawab.',
            'security_answer.numeric' => 'Jawaban keamanan harus berupa angka.',
        ]);

        $normalizedEmail = strtolower(trim($credentials['email']));
        $ip = $request->ip();

        // 2. Throttle Key (Kombinasi IP + Email ternormalisasi)
        $throttleKey = Str::transliterate($normalizedEmail.'|'.$ip);

        // 3. Proteksi Rate Limiting (Maksimal 5 percobaan per menit)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning("Login diblokir sementara karena brute-force dari IP: {$ip}, Email: {$normalizedEmail}. Tersisa: {$seconds}s.");

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login yang gagal. Akses dibatasi sementara demi keamanan. Silakan coba lagi dalam {$seconds} detik.",
            ])->with('lockout_seconds', $seconds)->onlyInput('email');
        }

        // 4. Verifikasi Anti-Bot Captcha (Jika aktif)
        if ($requireCaptcha) {
            $expectedAnswer = session('login_captcha_answer');
            $userAnswer = (int) $request->input('security_answer');

            if ($expectedAnswer === null || $userAnswer !== (int) $expectedAnswer) {
                // Regenerate captcha baru
                $n1 = rand(2, 9);
                $n2 = rand(2, 9);
                session([
                    'login_captcha_answer' => $n1 + $n2,
                    'login_captcha_question' => "Berapa hasil {$n1} + {$n2}?",
                ]);

                RateLimiter::hit($throttleKey, 60);

                Log::warning("Jawaban verifikasi anti-bot salah dari IP: {$ip}, Email: {$normalizedEmail}");

                return back()->withErrors([
                    'security_answer' => 'Jawaban verifikasi keamanan anti-bot salah. Silakan coba lagi.',
                ])->onlyInput('email');
            }
        }

        $remember = $request->boolean('remember');

        // 5. Coba Autentikasi Kredensial
        if (Auth::attempt(['email' => $normalizedEmail, 'password' => $credentials['password']], $remember)) {
            // Berhasil login: bersihkan rate limiter dan session attempt
            RateLimiter::clear($throttleKey);
            session()->forget(['login_attempts', 'login_captcha_answer', 'login_captcha_question']);

            $request->session()->regenerate();
            $user = Auth::user();

            Log::info("Login berhasil untuk user: {$user->name} ({$user->email}), Role: {$user->role}, IP: {$ip}");

            $targetRoute = $user->isGuru() ? route('guru.dashboard') : route('devices.index');

            return redirect()->intended($targetRoute)->with('success', 'Selamat datang kembali, '.$user->name.'!');
        }

        // 6. Gagal Login: Catat rate limiter & naikkan counter attempt
        RateLimiter::hit($throttleKey, 60);
        $newAttempts = $attempts + 1;
        session(['login_attempts' => $newAttempts]);

        // Siapkan captcha untuk percobaan berikutnya jika sudah mencapai batas
        if ($newAttempts >= 2) {
            $n1 = rand(2, 9);
            $n2 = rand(2, 9);
            session([
                'login_captcha_answer' => $n1 + $n2,
                'login_captcha_question' => "Berapa hasil {$n1} + {$n2}?",
            ]);
        }

        $remaining = RateLimiter::retriesLeft($throttleKey, 5);

        Log::warning("Percobaan login gagal untuk Email: {$normalizedEmail} dari IP: {$ip}. Sisa kesempatan sebelum diblokir: {$remaining}");

        $errorMessage = 'Email atau kata sandi yang Anda masukkan tidak cocok.';
        if ($remaining > 0 && $remaining <= 2) {
            $errorMessage .= " Peringatan: Sisa kesempatan login tinggal {$remaining} kali sebelum akun diblokir sementara.";
        }

        return back()->withErrors([
            'email' => $errorMessage,
        ])->onlyInput('email');
    }

    /**
     * Proses Logout Pengguna
     */
    public function logout(Request $request)
    {
        $userName = Auth::user()?->name ?? 'Pengguna';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', "Anda telah berhasil keluar dari sistem. Sampai jumpa kembali, {$userName}.");
    }
}
