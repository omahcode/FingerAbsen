<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sekolah Fingerprint Management</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    <nav class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Brand Logo & Name -->
                <a href="{{ url('/') }}" class="flex items-center space-x-2.5 font-bold text-lg sm:text-xl tracking-tight hover:opacity-90 transition">
                    <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur flex items-center justify-center border border-white/20">
                        <i class="fas fa-fingerprint text-yellow-300 text-xl"></i>
                    </div>
                    <span class="truncate max-w-[200px] sm:max-w-none">Fingerprint Sekolah</span>
                </a>

                <!-- Desktop Navigation Links (Visible on Large Screens) -->
                <div class="hidden xl:flex items-center space-x-1 font-medium text-sm">
                    <a href="{{ route('devices.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('devices.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition">
                        <i class="fas fa-hdd mr-1 text-xs"></i> Mesin
                    </a>
                    <a href="{{ route('majors.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('majors.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition">
                        <i class="fas fa-graduation-cap mr-1 text-xs"></i> Jurusan
                    </a>
                    <a href="{{ route('classes.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('classes.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition">
                        <i class="fas fa-chalkboard mr-1 text-xs"></i> Kelas
                    </a>
                    <a href="{{ route('students.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('students.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition">
                        <i class="fas fa-user-graduate mr-1 text-xs"></i> Siswa
                    </a>
                    <a href="{{ route('attendance.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('attendance.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition">
                        <i class="fas fa-clipboard-check mr-1 text-xs"></i> Absensi
                    </a>
                    <a href="{{ route('reports.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('reports.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition">
                        <i class="fas fa-chart-bar mr-1 text-xs"></i> Rekap
                    </a>
                    <a href="{{ route('sync.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('sync.*') ? 'bg-yellow-400 text-gray-900 font-bold shadow-sm' : 'bg-yellow-400/20 text-yellow-200 hover:bg-yellow-400 hover:text-gray-900' }} transition">
                        <i class="fas fa-sync-alt mr-1 text-xs"></i> Sinkronisasi
                    </a>
                    <a href="{{ route('settings.index') }}" class="px-2.5 py-1.5 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition">
                        <i class="fas fa-cog mr-1 text-xs"></i> Pengaturan
                    </a>
                    <a href="{{ route('tv.index') }}" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500 text-emerald-200 hover:text-white border border-emerald-400/30 transition">
                        <i class="fas fa-tv mr-1 text-xs"></i> Mode TV
                    </a>

                    @auth
                    <!-- User Profile & Logout -->
                    <div class="flex items-center space-x-2 pl-3 ml-2 border-l border-blue-500/50">
                        <div class="flex items-center space-x-1.5 text-xs text-blue-100 font-medium">
                            <i class="fas fa-user-circle text-base text-yellow-300"></i>
                            <span class="max-w-[90px] truncate">{{ Auth::user()->name }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" data-confirm="Apakah Anda yakin ingin keluar dari sistem?" data-title="Konfirmasi Logout" data-danger="true" data-icon="question">
                            @csrf
                            <button type="submit" class="bg-red-500/20 hover:bg-red-500 text-red-100 hover:text-white text-xs px-2.5 py-1 rounded border border-red-400/40 transition">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="text-sm font-bold bg-white text-blue-800 px-3.5 py-1.5 rounded-full hover:bg-blue-50 transition shadow-sm ml-2">
                        Login
                    </a>
                    @endauth
                </div>

                <!-- Mobile Hamburger Toggle Button (Visible on Small Screens) -->
                <div class="flex items-center space-x-2 xl:hidden">
                    <a href="{{ route('tv.index') }}" target="_blank" class="text-xs font-semibold bg-emerald-500/30 text-emerald-200 px-2.5 py-1.5 rounded-lg border border-emerald-400/40 flex items-center gap-1">
                        <i class="fas fa-tv"></i> TV
                    </a>
                    <button type="button" id="mobile-menu-btn" class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white focus:outline-none transition" aria-label="Buka Menu">
                        <i class="fas fa-bars text-xl" id="menu-icon-bars"></i>
                        <i class="fas fa-times text-xl hidden" id="menu-icon-times"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Drawer / Dropdown -->
            <div id="mobile-menu" class="hidden xl:hidden py-3 border-t border-blue-600/60 transition-all duration-200">
                <div class="grid grid-cols-2 gap-2 text-sm font-medium pb-3">
                    <a href="{{ route('devices.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('devices.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10' }}">
                        <i class="fas fa-hdd w-5 text-center text-blue-300"></i> Mesin
                    </a>
                    <a href="{{ route('majors.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('majors.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10' }}">
                        <i class="fas fa-graduation-cap w-5 text-center text-indigo-300"></i> Jurusan
                    </a>
                    <a href="{{ route('classes.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('classes.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10' }}">
                        <i class="fas fa-chalkboard w-5 text-center text-purple-300"></i> Kelas
                    </a>
                    <a href="{{ route('students.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('students.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10' }}">
                        <i class="fas fa-user-graduate w-5 text-center text-emerald-300"></i> Siswa
                    </a>
                    <a href="{{ route('attendance.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('attendance.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10' }}">
                        <i class="fas fa-clipboard-check w-5 text-center text-green-300"></i> Absensi
                    </a>
                    <a href="{{ route('reports.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('reports.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10' }}">
                        <i class="fas fa-chart-bar w-5 text-center text-amber-300"></i> Rekap
                    </a>
                    <a href="{{ route('sync.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('sync.*') ? 'bg-yellow-400 text-gray-900 font-bold' : 'bg-yellow-400/20 text-yellow-200' }}">
                        <i class="fas fa-sync-alt w-5 text-center"></i> Sinkronisasi
                    </a>
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-2 p-2.5 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-white/20 text-white font-semibold' : 'text-blue-100 hover:bg-white/10' }}">
                        <i class="fas fa-cog w-5 text-center text-sky-300"></i> Pengaturan
                    </a>
                </div>

                @auth
                <div class="pt-3 border-t border-blue-600/60 flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-xs text-blue-100">
                        <i class="fas fa-user-circle text-lg text-yellow-300"></i>
                        <span>Login: <strong>{{ Auth::user()->name }}</strong></span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" data-confirm="Apakah Anda yakin ingin keluar dari sistem?" data-title="Konfirmasi Logout" data-danger="true" data-icon="question">
                        @csrf
                        <button type="submit" class="bg-red-500/30 hover:bg-red-500 text-red-100 text-xs px-3 py-1.5 rounded-lg border border-red-400/40 transition flex items-center gap-1.5">
                            <i class="fas fa-sign-out-alt"></i> Keluar
                        </button>
                    </form>
                </div>
                @else
                <div class="pt-3 border-t border-blue-600/60">
                    <a href="{{ route('login') }}" class="block text-center text-sm font-bold bg-white text-blue-800 py-2 rounded-lg hover:bg-blue-50 transition shadow-sm">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login Admin
                    </a>
                </div>
                @endauth
            </div>
        </div>
    </nav>
    
    <main class="container mx-auto mt-4 sm:mt-6 md:mt-8 px-3 sm:px-4 md:px-6 pb-16">
        @yield('content')
    </main>

    <script>
        // Toggle Mobile Menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const iconBars = document.getElementById('menu-icon-bars');
        const iconTimes = document.getElementById('menu-icon-times');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                const isHidden = mobileMenu.classList.contains('hidden');
                if (isHidden) {
                    mobileMenu.classList.remove('hidden');
                    iconBars.classList.add('hidden');
                    iconTimes.classList.remove('hidden');
                } else {
                    mobileMenu.classList.add('hidden');
                    iconBars.classList.remove('hidden');
                    iconTimes.classList.add('hidden');
                }
            });
        }
    </script>

    <!-- SweetAlert Global Handler -->
    <script>
        // Inisialisasi Toast SweetAlert
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        // Flash message dari Backend Session
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: {!! json_encode(session('success')) !!},
                confirmButtonColor: '#4f46e5',
                timer: 4000,
                timerProgressBar: true
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: {!! json_encode(session('error')) !!},
                confirmButtonColor: '#ef4444'
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: {!! json_encode(session('warning')) !!},
                confirmButtonColor: '#f59e0b'
            });
        @endif

        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal!',
                html: '<ul class="text-left text-sm text-red-600 space-y-1">' +
                      @json(collect($errors->all())->map(fn($e) => "<li>• $e</li>")->implode('')) +
                      '</ul>',
                confirmButtonColor: '#ef4444'
            });
        @endif

        // Global Helper untuk Konfirmasi SweetAlert
        function confirmAction(options) {
            const {
                title = 'Apakah Anda yakin?',
                text = 'Tindakan ini tidak dapat dibatalkan.',
                icon = 'warning',
                confirmButtonText = 'Ya, Lanjutkan',
                cancelButtonText = 'Batal',
                confirmButtonColor = '#4f46e5',
                onConfirm = () => {}
            } = options;

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6b7280',
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    onConfirm();
                }
            });
        }

        // Auto-interceptor untuk form dengan attribute data-confirm
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form[data-confirm]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const msg = this.getAttribute('data-confirm');
                    const title = this.getAttribute('data-title') || 'Konfirmasi Tindakan';
                    const icon = this.getAttribute('data-icon') || 'warning';
                    const isDanger = this.getAttribute('data-danger') === 'true' || this.querySelector('button[type="submit"]')?.classList.contains('bg-red-600') || this.querySelector('button[type="submit"]')?.classList.contains('bg-red-500');

                    Swal.fire({
                        title: title,
                        text: msg,
                        icon: icon,
                        showCancelButton: true,
                        confirmButtonColor: isDanger ? '#dc2626' : '#4f46e5',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>