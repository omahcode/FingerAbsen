<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Wali Murid - Sekolah Fingerprint System</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <!-- Brand Logo Header (Identik dengan Desain Login Sistem) -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 shadow-lg shadow-indigo-500/30 mb-4 transform hover:scale-105 transition-transform duration-300">
                <i class="fas fa-fingerprint text-3xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Portal Wali Murid</h1>
            <p class="text-xs text-slate-400 mt-1">Verifikasi Akses Orang Tua / Wali Siswa</p>
        </div>

        <!-- Verification Card -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl shadow-2xl p-8 relative overflow-hidden">
            <!-- Glow Accent -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs flex items-start space-x-3">
                    <i class="fas fa-check-circle text-lg text-emerald-400 mt-0.5 shrink-0"></i>
                    <div>
                        <div class="font-bold text-emerald-200">Berhasil</div>
                        <p class="mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Flash Error Message -->
            @if($errors->any())
                <div class="mb-5 p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs">
                    <div class="flex items-center gap-2 font-bold mb-1 text-rose-200">
                        <i class="fas fa-exclamation-circle text-rose-400"></i>
                        <span>Verifikasi Belum Berhasil</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-rose-300/90 ml-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="parent-login-form" action="{{ route('parent.select') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Input 1: Nomor Induk Siswa (NIS) -->
                <div>
                    <label for="nis" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Nomor Induk Siswa (NIS) <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-id-card text-sm"></i>
                        </div>
                        <input 
                            type="text" 
                            id="nis" 
                            name="nis" 
                            value="{{ old('nis') }}" 
                            required 
                            autofocus
                            placeholder="Contoh: 20241011 atau ID Siswa"
                            class="w-full pl-10 pr-4 py-3 bg-slate-800/60 border @error('nis') border-rose-500/80 @else border-slate-700/70 @enderror rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition"
                        >
                    </div>
                    @error('nis')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Input 2: Nama Lengkap Siswa -->
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Nama Lengkap Siswa <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="{{ old('name') }}" 
                            required 
                            placeholder="Masukkan nama lengkap ananda"
                            class="w-full pl-10 pr-4 py-3 bg-slate-800/60 border @error('name') border-rose-500/80 @else border-slate-700/70 @enderror rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition"
                        >
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Pencocokan nama bersifat bebas huruf besar/kecil (case-insensitive).</p>
                    @error('name')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Input 3: Pilihan Kelas -->
                <div>
                    <label for="school_class_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Pilih Kelas Siswa <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-school text-sm"></i>
                        </div>
                        <select 
                            id="school_class_id" 
                            name="school_class_id" 
                            required 
                            class="w-full pl-10 pr-4 py-3 bg-slate-800/60 border @error('school_class_id') border-rose-500/80 @else border-slate-700/70 @enderror rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition cursor-pointer"
                        >
                            <option value="" disabled {{ old('school_class_id') ? '' : 'selected' }} class="text-slate-400">-- Pilih Kelas Aktif --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }} class="bg-slate-800 text-white">
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('school_class_id')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Submit Button matching Login Style -->
                <button 
                    type="submit" 
                    id="btn-submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transform active:scale-[0.99] transition duration-200 text-sm flex items-center justify-center space-x-2 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span id="btn-text">Verifikasi & Masuk Portal</span>
                    <i id="btn-icon" class="fas fa-arrow-right text-xs"></i>
                </button>

                <!-- Footer Links & Security Badge -->
                <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-slate-400">
                    <span class="flex items-center">
                        <i class="fas fa-shield-alt text-emerald-400 mr-1.5"></i> Sesi Resmi Wali Murid
                    </span>
                    <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300 transition">
                        Login Staf / Guru &rarr;
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; {{ date('Y') }} Sekolah Fingerprint Cloud Management. All rights reserved.
        </p>
    </div>

    <script>
        // Anti-Double Submit Handler
        const form = document.getElementById('parent-login-form');
        const submitBtn = document.getElementById('btn-submit');
        const btnText = document.getElementById('btn-text');
        const btnIcon = document.getElementById('btn-icon');

        if (form && submitBtn) {
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                btnText.innerText = 'Memverifikasi Data Siswa...';
                btnIcon.className = 'fas fa-circle-notch fa-spin text-xs';
            });
        }
    </script>
</body>
</html>
