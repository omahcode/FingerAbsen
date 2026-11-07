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

                <!-- Input: Nomor Induk Siswa (NIS) -->
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
                            autocomplete="off"
                            placeholder="Ketik NIS ananda (Contoh: 12530955)"
                            class="w-full pl-10 pr-10 py-3 bg-slate-800/60 border @error('nis') border-rose-500/80 @else border-slate-700/70 @enderror rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition"
                        >
                        <!-- Loading Spinner saat pengecekan NIS -->
                        <div id="nis-spinner" class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none hidden">
                            <i class="fas fa-circle-notch fa-spin text-indigo-400 text-sm"></i>
                        </div>
                        <!-- Centang Hijau saat Siswa Ditemukan -->
                        <div id="nis-check-icon" class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none hidden">
                            <i class="fas fa-check-circle text-emerald-400 text-sm"></i>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">Ketik NIS siswa untuk menampilkan data nama dan kelas secara otomatis.</p>
                    @error('nis')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Preview Info Siswa (Muncul Otomatis saat NIS Ditemukan) -->
                <div id="student-preview-card" class="hidden transition-all duration-300">
                    <div class="p-3.5 rounded-xl bg-slate-800/90 border border-emerald-500/40 shadow-lg shadow-emerald-500/5 flex items-center space-x-3.5">
                        <!-- Avatar / Foto Siswa -->
                        <div class="relative shrink-0">
                            <div id="student-avatar-fallback" class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 flex items-center justify-center text-white text-lg shadow-md">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <img id="student-photo" src="" alt="Foto Siswa" class="w-12 h-12 rounded-xl object-cover border border-slate-700 hidden shadow-md">
                            <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-slate-900 flex items-center justify-center">
                                <i class="fas fa-check text-[8px] text-white"></i>
                            </span>
                        </div>

                        <!-- Data Nama & Kelas Siswa -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="text-[10px] font-semibold text-emerald-400 uppercase tracking-wider bg-emerald-500/10 px-1.5 py-0.5 rounded flex items-center gap-1">
                                    <i class="fas fa-id-badge text-[9px]"></i> Siswa Teridentifikasi
                                </span>
                            </div>
                            <h4 id="student-name" class="text-sm font-bold text-white truncate leading-tight">Nama Siswa</h4>
                            <p id="student-class" class="text-xs text-indigo-300 font-medium truncate mt-0.5">Kelas & Jurusan</p>
                        </div>
                    </div>
                </div>

                <!-- Pesan Siswa Tidak Ditemukan -->
                <div id="student-not-found" class="hidden text-xs text-rose-400 flex items-center gap-2 bg-rose-500/10 border border-rose-500/20 px-3 py-2.5 rounded-xl">
                    <i class="fas fa-exclamation-circle text-rose-400 shrink-0"></i>
                    <span id="not-found-text">Siswa dengan NIS tersebut tidak ditemukan.</span>
                </div>

                <!-- Submit Button matching Login Style -->
                <button 
                    type="submit" 
                    id="btn-submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transform active:scale-[0.99] transition duration-200 text-sm flex items-center justify-center space-x-2 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span id="btn-text">Masuk Portal</span>
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
        // Live NIS Student Lookup
        const nisInput = document.getElementById('nis');
        const nisSpinner = document.getElementById('nis-spinner');
        const nisCheckIcon = document.getElementById('nis-check-icon');
        const previewCard = document.getElementById('student-preview-card');
        const avatarFallback = document.getElementById('student-avatar-fallback');
        const studentPhoto = document.getElementById('student-photo');
        const studentName = document.getElementById('student-name');
        const studentClass = document.getElementById('student-class');
        const notFoundCard = document.getElementById('student-not-found');
        const notFoundText = document.getElementById('not-found-text');
        const checkNisUrl = @json(route('parent.check-nis'));

        let debounceTimer = null;
        let activeAbort = null;

        function checkStudentNis(val) {
            const query = (val || '').trim();

            if (activeAbort) {
                activeAbort.abort();
            }

            if (!query || query.length < 2) {
                nisSpinner.classList.add('hidden');
                nisCheckIcon.classList.add('hidden');
                previewCard.classList.add('hidden');
                notFoundCard.classList.add('hidden');
                return;
            }

            nisSpinner.classList.remove('hidden');
            nisCheckIcon.classList.add('hidden');
            previewCard.classList.add('hidden');
            notFoundCard.classList.add('hidden');

            activeAbort = new AbortController();

            fetch(`${checkNisUrl}?nis=${encodeURIComponent(query)}`, {
                signal: activeAbort.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                nisSpinner.classList.add('hidden');

                if (data && data.found && data.student) {
                    nisCheckIcon.classList.remove('hidden');
                    notFoundCard.classList.add('hidden');

                    studentName.textContent = data.student.name;
                    studentClass.textContent = data.student.class_name + (data.student.major_name ? ' • ' + data.student.major_name : '');

                    if (data.student.photo_url) {
                        studentPhoto.src = data.student.photo_url;
                        studentPhoto.classList.remove('hidden');
                        avatarFallback.classList.add('hidden');
                    } else {
                        studentPhoto.classList.add('hidden');
                        avatarFallback.classList.remove('hidden');
                    }

                    previewCard.classList.remove('hidden');
                } else {
                    nisCheckIcon.classList.add('hidden');
                    previewCard.classList.add('hidden');
                    notFoundText.textContent = (data && data.message) ? data.message : 'Siswa dengan NIS tersebut tidak ditemukan.';
                    notFoundCard.classList.remove('hidden');
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    nisSpinner.classList.add('hidden');
                }
            });
        }

        if (nisInput) {
            nisInput.addEventListener('input', function(e) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    checkStudentNis(e.target.value);
                }, 300);
            });

            nisInput.addEventListener('change', function(e) {
                clearTimeout(debounceTimer);
                checkStudentNis(e.target.value);
            });

            // Otomatis cek jika input sudah terisi (misal saat reload dengan old input)
            if (nisInput.value.trim().length >= 2) {
                checkStudentNis(nisInput.value);
            }
        }

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
