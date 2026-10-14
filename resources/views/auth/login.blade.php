<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sekolah Fingerprint System</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .bg-mesh {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.25) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(99, 102, 241, 0.25) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(30, 58, 138, 0.3) 0px, transparent 50%);
        }
    </style>
</head>
<body class="bg-mesh min-h-screen flex items-center justify-center p-4 antialiased text-slate-100">

    <div class="w-full max-w-md">
        <!-- Brand Logo Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 shadow-lg shadow-indigo-500/30 mb-4 transform hover:scale-105 transition-transform duration-300">
                <i class="fas fa-fingerprint text-3xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Sekolah Fingerprint System</h1>
            <p class="text-xs text-slate-400 mt-1">Masuk untuk mengakses portal manajemen presensi biometrik</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl shadow-2xl p-8 relative overflow-hidden">
            <!-- Glow Accent -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Lockout Countdown Alert (Jika terkena throttle) -->
            @if(session('lockout_seconds'))
                <div id="lockout-banner" class="mb-5 p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-start space-x-3 animate-pulse">
                    <i class="fas fa-shield-virus text-lg text-rose-400 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <div class="font-bold text-rose-200">Akses Terkunci Sementara</div>
                        <p class="mt-0.5">Terlalu banyak percobaan login gagal. Anda dapat mencoba kembali dalam <span id="countdown-timer" class="font-mono font-bold text-white text-sm bg-rose-950/70 px-2 py-0.5 rounded border border-rose-800/60">{{ session('lockout_seconds') }}</span> detik.</p>
                    </div>
                </div>
            @endif

            <form id="login-form" action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Input Email / Username -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Email Administrator / Guru</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-envelope text-sm"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus
                            autocomplete="username"
                            placeholder="nama@sekolah.sch.id"
                            class="w-full pl-10 pr-4 py-3 bg-slate-800/60 border @error('email') border-rose-500/80 @else border-slate-700/70 @enderror rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Input Password -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Kata Sandi</label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-lock text-sm"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-3 bg-slate-800/60 border @error('password') border-rose-500/80 @else border-slate-700/70 @enderror rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility()" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 transition"
                            title="Tampilkan / Sembunyikan Kata Sandi"
                        >
                            <i id="toggle-icon" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Anti-Bot Math Security Challenge (Aktif setelah percobaan gagal berulang) -->
                @if(!empty($requireCaptcha))
                    <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 space-y-2.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-amber-300">
                            <span class="flex items-center">
                                <i class="fas fa-shield-alt mr-1.5 text-amber-400"></i> Verifikasi Anti-Bot Keamanan
                            </span>
                            <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full border border-amber-500/30">Wajib Diisi</span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            Sistem mendeteksi beberapa percobaan login. Silakan selesaikan hitungan berikut:
                        </p>
                        <div class="flex items-center space-x-3">
                            <div class="px-3.5 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-sm font-mono font-bold text-amber-300 tracking-wider select-none shadow-inner">
                                {{ $captchaQuestion ?? 'Berapa 5 + 3?' }}
                            </div>
                            <div class="flex-1">
                                <input 
                                    type="number" 
                                    name="security_answer" 
                                    required 
                                    placeholder="Hasil angka..."
                                    class="w-full px-3.5 py-2.5 bg-slate-800/80 border @error('security_answer') border-rose-500 @else border-slate-700 @enderror rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 font-mono"
                                >
                            </div>
                        </div>
                        @error('security_answer')
                            <p class="text-xs text-rose-400 flex items-center mt-1">
                                <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                @endif

                <!-- Remember Me & Link TV -->
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500/50 focus:ring-offset-0">
                        <span>Ingat Sesi Saya</span>
                    </label>
                    <a href="{{ route('tv.index') }}" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition flex items-center">
                        <i class="fas fa-tv mr-1"></i> Mode TV Layar Absen
                    </a>
                </div>

                <!-- Submit Button with Anti-Double Submission -->
                <button 
                    type="submit" 
                    id="btn-submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transform active:scale-[0.99] transition duration-200 text-sm flex items-center justify-center space-x-2 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span id="btn-text">Masuk ke Dashboard</span>
                    <i id="btn-icon" class="fas fa-arrow-right text-xs"></i>
                </button>

                <!-- Security Trust Badge -->
                <div class="pt-2 border-t border-slate-800/60 flex items-center justify-center space-x-2 text-[11px] text-slate-500">
                    <i class="fas fa-lock text-emerald-400 text-xs"></i>
                    <span>Proteksi Anti-Brute Force & Verifikasi Sesi Aktif</span>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; {{ date('Y') }} Sekolah Fingerprint Cloud Management. All rights reserved.
        </p>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Anti-Double Submit Handler
        const loginForm = document.getElementById('login-form');
        const submitBtn = document.getElementById('btn-submit');
        const btnText = document.getElementById('btn-text');
        const btnIcon = document.getElementById('btn-icon');

        if (loginForm && submitBtn) {
            loginForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                btnText.innerText = 'Memverifikasi Kredensial...';
                btnIcon.className = 'fas fa-circle-notch fa-spin text-xs';
            });
        }

        // Lockout countdown timer jika sedang dibatasi
        @if(session('lockout_seconds'))
            let countdown = {{ (int) session('lockout_seconds') }};
            const timerEl = document.getElementById('countdown-timer');
            const bannerEl = document.getElementById('lockout-banner');

            if (timerEl && countdown > 0) {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    btnText.innerText = 'Diblokir Sementara (' + countdown + 's)';
                }

                const interval = setInterval(() => {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        timerEl.innerText = '0';
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            btnText.innerText = 'Coba Masuk Lagi';
                            btnIcon.className = 'fas fa-arrow-right text-xs';
                        }
                        if (bannerEl) {
                            bannerEl.classList.remove('animate-pulse');
                            bannerEl.classList.add('bg-emerald-500/15', 'border-emerald-500/30', 'text-emerald-300');
                            bannerEl.innerHTML = '<i class="fas fa-check-circle text-lg text-emerald-400 mt-0.5"></i><div><div class="font-bold text-emerald-200">Blokir Sementara Telah Berakhir</div><p class="mt-0.5">Silakan kirim form kembali dengan kredensial yang valid.</p></div>';
                        }
                    } else {
                        timerEl.innerText = countdown;
                        if (submitBtn) {
                            btnText.innerText = 'Diblokir Sementara (' + countdown + 's)';
                        }
                    }
                }, 1000);
            }
        @endif

        // SweetAlert untuk notifikasi error atau status
        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: {!! json_encode($errors->first()) !!},
                confirmButtonColor: '#4f46e5',
                background: '#1e293b',
                color: '#f8fafc'
            });
        @endif

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Informasi',
                text: {!! json_encode(session('success')) !!},
                confirmButtonColor: '#4f46e5',
                background: '#1e293b',
                color: '#f8fafc'
            });
        @endif
    </script>
</body>
</html>
