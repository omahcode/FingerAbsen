<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sekolah Fingerprint System</title>
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
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl shadow-2xl p-8">
            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Input Email / Username -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Email Administrator</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-envelope text-sm"></i>
                        </div>
                        <input 
                            type="text" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus
                            placeholder="admin@sekolah.sch.id"
                            class="w-full pl-10 pr-4 py-3 bg-slate-800/60 border border-slate-700/70 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition"
                        >
                    </div>
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
                            placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-3 bg-slate-800/60 border border-slate-700/70 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility()" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 transition"
                        >
                            <i id="toggle-icon" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500/50 focus:ring-offset-0">
                        <span>Ingat Sesi Saya</span>
                    </label>
                    <a href="{{ route('tv.index') }}" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition flex items-center">
                        <i class="fas fa-tv mr-1"></i> Mode TV Layar Absen
                    </a>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transform active:scale-[0.99] transition duration-200 text-sm flex items-center justify-center space-x-2"
                >
                    <span>Masuk ke Dashboard</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
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

        // SweetAlert untuk notifikasi error atau status
        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Gagal Masuk',
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
