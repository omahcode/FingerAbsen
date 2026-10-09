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
    <nav class="bg-gradient-to-r from-blue-700 to-indigo-800 p-4 text-white shadow-md">
        <div class="container mx-auto font-bold text-xl flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-fingerprint text-yellow-300 text-2xl"></i>
                <span>Sekolah Fingerprint System</span>
            </div>
            <div class="flex space-x-4 items-center">
                <a href="{{ route('devices.index') }}" class="text-sm font-medium hover:text-blue-200 transition">Mesin</a>
                <a href="{{ route('majors.index') }}" class="text-sm font-medium hover:text-blue-200 transition">Jurusan</a>
                <a href="{{ route('classes.index') }}" class="text-sm font-medium hover:text-blue-200 transition">Kelas</a>
                <a href="{{ route('students.index') }}" class="text-sm font-medium hover:text-blue-200 transition">Siswa</a>
                <a href="{{ route('attendance.index') }}" class="text-sm font-medium hover:text-blue-200 transition">Absensi</a>
                <a href="{{ route('reports.index') }}" class="text-sm font-medium hover:text-blue-200 transition">Rekap Siswa</a>
                <a href="{{ route('sync.index') }}" class="text-sm font-bold bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-3 py-1 rounded-full shadow-sm transition">
                    <i class="fas fa-sync-alt mr-1"></i> Sinkronisasi
                </a>
                <a href="{{ route('settings.index') }}" class="text-sm font-medium hover:text-blue-200 transition">
                    <i class="fas fa-cog mr-0.5"></i> Pengaturan
                </a>

                @auth
                <!-- User Profile & Logout -->
                <div class="flex items-center space-x-3 pl-3 border-l border-blue-500/50">
                    <div class="flex items-center space-x-1.5 text-xs text-blue-100 font-medium">
                        <i class="fas fa-user-circle text-base text-yellow-300"></i>
                        <span>{{ Auth::user()->name }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" data-confirm="Apakah Anda yakin ingin keluar dari sistem?" data-title="Konfirmasi Logout" data-danger="true" data-icon="question">
                        @csrf
                        <button type="submit" class="bg-red-500/20 hover:bg-red-500/40 text-red-100 text-xs px-2.5 py-1 rounded border border-red-400/40 transition">
                            <i class="fas fa-sign-out-alt mr-0.5"></i> Keluar
                        </button>
                    </form>
                </div>
                @else
                <a href="{{ route('login') }}" class="text-sm font-bold bg-white text-blue-800 px-3 py-1 rounded-full hover:bg-blue-50 transition">
                    Login
                </a>
                @endauth
            </div>
        </div>
    </nav>
    
    <main class="container mx-auto mt-8 px-4 pb-16">
        @yield('content')
    </main>

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