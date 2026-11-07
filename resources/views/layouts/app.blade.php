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
    
    <script>
        // Cek preferensi sidebar minimized dari localStorage sebelum DOM di-render agar tidak ada kedipan
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-init');
        }
    </script>

    <style>
        /* Custom Scrollbar untuk Sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.4);
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Responsive Sidebar Width & Smooth Transitions */
        #main-sidebar {
            width: 17rem;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #main-wrapper {
            padding-left: 17rem;
            transition: padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (max-width: 767px) {
            #main-wrapper {
                padding-left: 0 !important;
            }
            #main-sidebar {
                width: 17rem !important;
            }
        }

        /* ================================================= */
        /* COLLAPSED / MINIMIZED SIDEBAR STATE (DESKTOP)     */
        /* ================================================= */
        html.sidebar-collapsed-init body #main-sidebar,
        body.sidebar-collapsed #main-sidebar {
            width: 5rem !important; /* 80px */
        }

        html.sidebar-collapsed-init body #main-wrapper,
        body.sidebar-collapsed #main-wrapper {
            padding-left: 5rem !important;
        }

        body.sidebar-collapsed .sidebar-text,
        body.sidebar-collapsed .sidebar-section-title,
        body.sidebar-collapsed .sidebar-badge,
        body.sidebar-collapsed .sidebar-logo-text,
        body.sidebar-collapsed .sidebar-user-info {
            display: none !important;
        }

        body.sidebar-collapsed .sidebar-link {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

        body.sidebar-collapsed .sidebar-link i {
            margin: 0 !important;
            font-size: 1.15rem !important;
        }

        body.sidebar-collapsed .sidebar-brand-box {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        body.sidebar-collapsed .sidebar-user-card {
            justify-content: center !important;
            padding: 0.4rem !important;
        }

        body.sidebar-collapsed #sidebar-collapse-icon {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-40 hidden md:hidden transition-opacity duration-300"></div>

    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- ============================================== -->
        <!-- SIDEBAR NAVIGATION                             -->
        <!-- ============================================== -->
        <aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 bg-slate-950 border-r border-slate-800/80 text-slate-300 flex flex-col transition-all duration-300 -translate-x-full md:translate-x-0 shadow-2xl md:shadow-none">
            
            <!-- Brand Logo & Minimize Button Header -->
            <div class="h-16 px-4 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/90 shrink-0 sidebar-brand-box">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group overflow-hidden">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20 border border-blue-400/30 group-hover:scale-105 transition shrink-0">
                        <i class="fas fa-fingerprint text-yellow-300 text-xl"></i>
                    </div>
                    <div class="sidebar-logo-text whitespace-nowrap overflow-hidden transition-all duration-300">
                        <div class="font-extrabold text-white text-base tracking-tight leading-none group-hover:text-blue-300 transition">FingerAbsen</div>
                        <div class="text-[11px] text-slate-400 font-medium tracking-wide mt-1">Presensi Biometrik</div>
                    </div>
                </a>

                <!-- Desktop Minimize Button in Sidebar Header -->
                <button type="button" id="sidebar-collapse-btn" class="hidden md:flex text-slate-400 hover:text-white hover:bg-slate-900 p-2 rounded-xl transition focus:outline-none" title="Minimize / Perluas Sidebar">
                    <i id="sidebar-collapse-icon" class="fas fa-chevron-left text-xs transition-transform duration-300"></i>
                </button>

                <!-- Mobile Close Button -->
                <button type="button" id="sidebar-close-btn" class="md:hidden text-slate-400 hover:text-white p-2 rounded-lg focus:outline-none">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Scrollable Navigation Menu -->
            <div class="flex-1 overflow-y-auto sidebar-scroll px-3 py-4 space-y-6">
                
                @auth
                @if(Auth::user()->isKepalaSekolah())
                <!-- KHUSUS KEPALA SEKOLAH: HANYA PRESENSI UPACARA GURU & DATA GURU -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sidebar-section-title">
                        Menu Kepala Sekolah
                    </div>
                    <nav class="space-y-1.5">
                        <a href="{{ route('teachers.ceremony') }}" class="sidebar-link flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('teachers.ceremony*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Presensi Guru (Upacara & Acara)">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i class="fas fa-flag w-5 text-center shrink-0 {{ request()->routeIs('teachers.ceremony*') ? 'text-white' : 'text-rose-400' }}"></i>
                                <span class="sidebar-text truncate">Presensi Upacara Guru</span>
                            </div>
                            <span class="sidebar-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 shrink-0">
                                Guru
                            </span>
                        </a>

                        <a href="{{ route('teachers.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('teachers.index*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Data Guru (User Mesin)">
                            <i class="fas fa-chalkboard-teacher w-5 text-center shrink-0 {{ request()->routeIs('teachers.index*') ? 'text-white' : 'text-emerald-400' }}"></i>
                            <span class="sidebar-text truncate">Data Guru</span>
                        </a>
                    </nav>
                </div>
                @else
                <!-- SECTION 1: MONITORING & PRESENSI (ADMIN & GURU) -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sidebar-section-title">
                        Monitoring & Presensi
                    </div>
                    <nav class="space-y-1">
                        <a href="{{ route('guru.dashboard') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('guru.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Monitoring Kelas">
                            <i class="fas fa-chalkboard-teacher w-5 text-center shrink-0 {{ request()->routeIs('guru.*') ? 'text-white' : 'text-indigo-400' }}"></i>
                            <span class="sidebar-text truncate">Monitoring Kelas</span>
                        </a>

                        <a href="{{ route('monitoring.jurusan') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('monitoring.jurusan*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Monitoring Jurusan">
                            <i class="fas fa-layer-group w-5 text-center shrink-0 {{ request()->routeIs('monitoring.jurusan*') ? 'text-white' : 'text-violet-400' }}"></i>
                            <span class="sidebar-text truncate">Monitoring Jurusan</span>
                        </a>

                        <a href="{{ route('monitoring.kepulangan') }}" class="sidebar-link flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('monitoring.kepulangan*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Monitoring Kepulangan Siswa">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i class="fas fa-door-open w-5 text-center shrink-0 {{ request()->routeIs('monitoring.kepulangan*') ? 'text-white' : 'text-amber-400' }}"></i>
                                <span class="sidebar-text truncate">Monitoring Kepulangan</span>
                            </div>
                            <span class="sidebar-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 shrink-0">
                                Live
                            </span>
                        </a>

                        @if(Auth::user()->isAdmin())
                        <a href="{{ route('attendance.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('attendance.*') && !request()->routeIs('attendance.simulate') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Log & Rekap Absensi">
                            <i class="fas fa-clipboard-check w-5 text-center shrink-0 {{ request()->routeIs('attendance.index') ? 'text-white' : 'text-emerald-400' }}"></i>
                            <span class="sidebar-text truncate">Log & Rekap Absensi</span>
                        </a>

                        <a href="{{ route('teachers.ceremony') }}" class="sidebar-link flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('teachers.ceremony*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Presensi Guru (Upacara & Acara)">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i class="fas fa-flag w-5 text-center shrink-0 {{ request()->routeIs('teachers.ceremony*') ? 'text-white' : 'text-rose-400' }}"></i>
                                <span class="sidebar-text truncate">Presensi Upacara Guru</span>
                            </div>
                            <span class="sidebar-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 shrink-0">
                                Guru
                            </span>
                        </a>
                        @endif
                    </nav>
                </div>

                <!-- SECTION 2: PENGUMUMAN & LAYAR TV -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sidebar-section-title">
                        Display & Informasi
                    </div>
                    <nav class="space-y-1">
                        <a href="{{ route('announcements.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('announcements.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Pengumuman TV">
                            <i class="fas fa-bullhorn w-5 text-center shrink-0 {{ request()->routeIs('announcements.*') ? 'text-white' : 'text-amber-400' }}"></i>
                            <span class="sidebar-text truncate">Pengumuman TV</span>
                        </a>

                        @if(Auth::user()->isAdmin())
                        @php $pendingBadge = \App\Models\TvAnnouncement::where('status', 'pending')->count(); @endphp
                        <a href="{{ route('admin.announcements.index') }}" class="sidebar-link flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.announcements.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Persetujuan Pengumuman TV">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i class="fas fa-check-double w-5 text-center shrink-0 {{ request()->routeIs('admin.announcements.*') ? 'text-white' : 'text-sky-400' }}"></i>
                                <span class="sidebar-text truncate">Approval TV</span>
                            </div>
                            @if($pendingBadge > 0)
                            <span class="sidebar-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white animate-pulse shrink-0">
                                {{ $pendingBadge }}
                            </span>
                            @endif
                        </a>
                        @endif

                        <a href="{{ route('tv.index') }}" target="_blank" class="sidebar-link flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-emerald-400 hover:bg-emerald-500/15 hover:text-emerald-300 transition border border-emerald-500/20 bg-emerald-500/5" title="Buka Layar Mode TV">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i class="fas fa-tv w-5 text-center shrink-0"></i>
                                <span class="sidebar-text truncate">Buka Mode TV</span>
                            </div>
                            <i class="fas fa-external-link-alt text-[10px] opacity-75 sidebar-text shrink-0"></i>
                        </a>

                        <a href="{{ route('parent.index') }}" target="_blank" class="sidebar-link flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-sky-400 hover:bg-sky-500/15 hover:text-sky-300 transition border border-sky-500/20 bg-sky-500/5" title="Buka Portal Orang Tua">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i class="fas fa-users w-5 text-center shrink-0"></i>
                                <span class="sidebar-text truncate">Portal Orang Tua</span>
                            </div>
                            <i class="fas fa-external-link-alt text-[10px] opacity-75 sidebar-text shrink-0"></i>
                        </a>
                    </nav>
                </div>

                <!-- SECTION 3: MASTER DATA & PERANGKAT (ADMIN ONLY) -->
                @if(Auth::user()->isAdmin())
                <div>
                    <div class="px-3 mb-2 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sidebar-section-title">
                        Master Data & Mesin
                    </div>
                    <nav class="space-y-1">
                        <a href="{{ route('devices.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('devices.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Mesin Fingerprint">
                            <i class="fas fa-hdd w-5 text-center shrink-0 {{ request()->routeIs('devices.*') ? 'text-white' : 'text-blue-400' }}"></i>
                            <span class="sidebar-text truncate">Mesin Fingerprint</span>
                        </a>

                        <a href="{{ route('majors.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('majors.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Data Jurusan">
                            <i class="fas fa-graduation-cap w-5 text-center shrink-0 {{ request()->routeIs('majors.*') ? 'text-white' : 'text-indigo-400' }}"></i>
                            <span class="sidebar-text truncate">Data Jurusan</span>
                        </a>

                        <a href="{{ route('classes.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('classes.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Data Kelas">
                            <i class="fas fa-chalkboard w-5 text-center shrink-0 {{ request()->routeIs('classes.*') ? 'text-white' : 'text-purple-400' }}"></i>
                            <span class="sidebar-text truncate">Data Kelas</span>
                        </a>

                        <a href="{{ route('students.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('students.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Data Siswa">
                            <i class="fas fa-user-graduate w-5 text-center shrink-0 {{ request()->routeIs('students.*') ? 'text-white' : 'text-teal-400' }}"></i>
                            <span class="sidebar-text truncate">Data Siswa</span>
                        </a>

                        <a href="{{ route('teachers.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('teachers.index*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Data Guru (User Mesin)">
                            <i class="fas fa-chalkboard-teacher w-5 text-center shrink-0 {{ request()->routeIs('teachers.index*') ? 'text-white' : 'text-emerald-400' }}"></i>
                            <span class="sidebar-text truncate">Data Guru</span>
                        </a>

                        <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.users.*') || request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Manajemen Pengguna (Guru, Kepsek, Admin)">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <i class="fas fa-users-cog w-5 text-center shrink-0 {{ request()->routeIs('admin.users.*') || request()->routeIs('users.*') ? 'text-white' : 'text-amber-400' }}"></i>
                                <span class="sidebar-text truncate">Manajemen Users</span>
                            </div>
                            <span class="sidebar-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 shrink-0">
                                Baru
                            </span>
                        </a>
                    </nav>
                </div>

                <!-- SECTION 4: SISTEM & LAPORAN -->
                <div>
                    <div class="px-3 mb-2 text-[10px] font-extrabold text-slate-500 uppercase tracking-wider sidebar-section-title">
                        Sistem & Laporan
                    </div>
                    <nav class="space-y-1">
                        <a href="{{ route('reports.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Rekap & Ekspor PDF">
                            <i class="fas fa-chart-bar w-5 text-center shrink-0 {{ request()->routeIs('reports.*') ? 'text-white' : 'text-amber-400' }}"></i>
                            <span class="sidebar-text truncate">Rekap & PDF</span>
                        </a>

                        <a href="{{ route('sync.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('sync.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Sinkronisasi ADMS Mesin">
                            <i class="fas fa-sync-alt w-5 text-center shrink-0 {{ request()->routeIs('sync.*') ? 'text-white' : 'text-yellow-400' }}"></i>
                            <span class="sidebar-text truncate">Sinkronisasi ADMS</span>
                        </a>

                        <a href="{{ route('settings.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('settings.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Pengaturan Sistem">
                            <i class="fas fa-cog w-5 text-center shrink-0 {{ request()->routeIs('settings.*') ? 'text-white' : 'text-slate-400' }}"></i>
                            <span class="sidebar-text truncate">Pengaturan Sistem</span>
                        </a>

                        <a href="{{ route('attendance.simulate') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('attendance.simulate') ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'text-purple-300 hover:bg-slate-900 hover:text-purple-200' }}" title="Simulasi Uji Coba Absensi">
                            <i class="fas fa-flask w-5 text-center shrink-0 text-purple-400"></i>
                            <span class="sidebar-text truncate">Simulasi Absensi</span>
                        </a>
                    </nav>
                </div>
                @endif
                @endif
                @endauth

            </div>

            <!-- Sidebar User Profile Footer -->
            @auth
            <div class="p-3 border-t border-slate-800/80 bg-slate-950 shrink-0">
                <div class="sidebar-user-card flex items-center justify-between p-2 rounded-xl bg-slate-900/90 border border-slate-800">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg {{ Auth::user()->isKepalaSekolah() ? 'bg-amber-600/30 text-amber-300 border border-amber-500/40' : (Auth::user()->isGuru() ? 'bg-emerald-600/30 text-emerald-300 border border-emerald-500/40' : 'bg-indigo-600/30 text-indigo-400 border border-indigo-500/40') }} font-bold flex items-center justify-center shrink-0 text-xs">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="sidebar-user-info overflow-hidden">
                            <div class="font-bold text-white text-xs truncate max-w-[100px] xl:max-w-[120px]">{{ Auth::user()->name }}</div>
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded {{ Auth::user()->isGuru() ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : (Auth::user()->isKepalaSekolah() ? 'bg-amber-950 text-amber-300 border border-amber-600/40' : 'bg-blue-950 text-blue-400 border border-blue-800/40') }}">
                                {{ Auth::user()->isGuru() ? 'Wali Kelas' : (Auth::user()->isKepalaSekolah() ? 'Kepala Sekolah' : 'Admin') }}
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" data-confirm="Apakah Anda yakin ingin keluar dari sistem?" data-title="Konfirmasi Logout" data-danger="true" data-icon="question" class="sidebar-user-info">
                        @csrf
                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition" title="Keluar">
                            <i class="fas fa-sign-out-alt text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="p-4 border-t border-slate-800/80 bg-slate-950">
                <a href="{{ route('login') }}" class="block text-center py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition">
                    <i class="fas fa-sign-in-alt mr-1"></i> <span class="sidebar-text">Login Akun</span>
                </a>
            </div>
            @endauth

        </aside>

        <!-- ============================================== -->
        <!-- MAIN CONTENT AREA & TOPBAR                     -->
        <!-- ============================================== -->
        <div id="main-wrapper" class="flex-1 flex flex-col min-w-0 transition-all duration-300">
            
            <!-- Sticky Dashboard Topbar -->
            <header class="h-16 bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 px-4 sm:px-6 flex items-center justify-between shadow-sm">
                
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger Button -->
                    <button type="button" id="sidebar-open-btn" class="md:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition focus:outline-none" aria-label="Buka Menu">
                        <i class="fas fa-bars text-lg"></i>
                    </button>

                    <!-- Desktop Minimize Toggle Button -->
                    <button type="button" id="desktop-toggle-btn" class="hidden md:flex p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition focus:outline-none" title="Minimize / Perluas Sidebar">
                        <i class="fas fa-bars-staggered text-base"></i>
                    </button>

                    <!-- Breadcrumb / Context Title -->
                    <div class="flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-700">
                        <span class="text-slate-400 hidden sm:inline">FingerAbsen</span>
                        <span class="text-slate-300 hidden sm:inline">/</span>
                        <span class="text-slate-900 font-bold">
                            @if(request()->routeIs('guru.dashboard'))
                                Monitoring Kelas
                            @elseif(request()->routeIs('monitoring.jurusan*'))
                                Monitoring Jurusan
                            @elseif(request()->routeIs('announcements.*'))
                                Pengumuman TV
                            @elseif(request()->routeIs('admin.announcements.*'))
                                Persetujuan Pengumuman TV
                            @elseif(request()->routeIs('admin.teachers.*'))
                                Kelola Akun Guru
                            @elseif(request()->routeIs('devices.*'))
                                Mesin Fingerprint
                            @elseif(request()->routeIs('majors.*'))
                                Master Data Jurusan
                            @elseif(request()->routeIs('classes.*'))
                                Master Data Kelas
                            @elseif(request()->routeIs('students.*'))
                                Data Siswa
                            @elseif(request()->routeIs('attendance.*'))
                                Log Presensi
                            @elseif(request()->routeIs('reports.*'))
                                Laporan & Rekap PDF
                            @elseif(request()->routeIs('sync.*'))
                                Sinkronisasi Perangkat
                            @elseif(request()->routeIs('settings.*'))
                                Pengaturan Sistem
                            @else
                                Dashboard
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Right Quick Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Mode TV Quick Button -->
                    @if(!Auth::user() || !Auth::user()->isKepalaSekolah())
                    <a href="{{ route('tv.index') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-xs border border-emerald-200 shadow-sm transition">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <i class="fas fa-tv text-xs"></i>
                        <span class="hidden sm:inline">Layar TV</span>
                    </a>
                    @endif

                    @auth
                    <!-- User Avatar Pill -->
                    <div class="flex items-center gap-2 pl-2 sm:pl-3 border-l border-slate-200">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="text-xs font-semibold text-slate-800 hidden md:inline max-w-[120px] truncate">
                            {{ Auth::user()->name }}
                        </span>
                    </div>
                    @endauth
                </div>

            </header>

            <!-- Main Content Container -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
                @yield('content')
            </main>

            <!-- Dashboard Footer -->
            <footer class="py-4 px-6 text-center text-xs text-slate-400 border-t border-slate-200 bg-white/60">
                &copy; {{ date('Y') }} Sistem Informasi Presensi Biometrik Fingerprint & Mode TV Interaktif.
            </footer>
        </div>
    </div>

    <!-- Sidebar Collapse & Mobile Drawer Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('main-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const openBtn = document.getElementById('sidebar-open-btn');
            const closeBtn = document.getElementById('sidebar-close-btn');
            const desktopToggleBtn = document.getElementById('desktop-toggle-btn');
            const sidebarCollapseBtn = document.getElementById('sidebar-collapse-btn');

            // 1. Mobile Drawer Functions
            function openMobileSidebar() {
                sidebar?.classList.remove('-translate-x-full');
                backdrop?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'md:overflow-auto');
            }

            function closeMobileSidebar() {
                sidebar?.classList.add('-translate-x-full');
                backdrop?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'md:overflow-auto');
            }

            openBtn?.addEventListener('click', openMobileSidebar);
            closeBtn?.addEventListener('click', closeMobileSidebar);
            backdrop?.addEventListener('click', closeMobileSidebar);

            // 2. Desktop Collapse / Minimize Functions with LocalStorage Persistence
            function initDesktopCollapseState() {
                const savedState = localStorage.getItem('sidebar_collapsed');
                if (savedState === 'true') {
                    document.body.classList.add('sidebar-collapsed');
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                }
                // Hapus class inisialisasi sementara
                document.documentElement.classList.remove('sidebar-collapsed-init');
            }

            function toggleDesktopSidebar() {
                document.body.classList.toggle('sidebar-collapsed');
                const isNowCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar_collapsed', isNowCollapsed ? 'true' : 'false');
            }

            desktopToggleBtn?.addEventListener('click', toggleDesktopSidebar);
            sidebarCollapseBtn?.addEventListener('click', toggleDesktopSidebar);

            // Jalankan inisialisasi status
            initDesktopCollapseState();

            // Close on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeMobileSidebar();
            });
        });
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

        @if(isset($errors) && $errors->any())
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

        // ========================================================
        // MODAL CEK KETERSEDIAAN SIDIK JARI DI MESIN (GLOBAL)
        // ========================================================
        window.showFingerprintDeviceModal = async function(pin = '', fallbackName = '', fallbackRole = '', id = '') {
            const modal = document.getElementById('modal-device-fingerprints');
            if (!modal) return;

            modal.style.display = 'flex';
            modal.classList.remove('hidden');

            const loadingBox = document.getElementById('mdf-loading');
            const contentBox = document.getElementById('mdf-content');
            const titleName = document.getElementById('mdf-user-name');
            const titlePin = document.getElementById('mdf-user-pin');
            const subtitleRole = document.getElementById('mdf-user-role');
            const dbCountBadge = document.getElementById('mdf-db-count');
            const deviceListEl = document.getElementById('mdf-device-list');
            const btnSyncAllMissing = document.getElementById('mdf-btn-sync-all');

            loadingBox.classList.remove('hidden');
            contentBox.classList.add('hidden');
            titleName.innerText = fallbackName || 'Memuat...';
            titlePin.innerText = pin ? `(PIN: ${pin})` : '';
            subtitleRole.innerText = fallbackRole || '';

            try {
                const params = new URLSearchParams({
                    pin: pin || '',
                    id: id || '',
                    role: fallbackRole || ''
                });
                const res = await fetch(`/sync/user-devices-status?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                let data;
                try {
                    data = await res.json();
                } catch (e) {
                    throw new Error('Gagal memproses data server (Format tidak sesuai). Pastikan migrasi database sudah dijalankan.');
                }

                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || `Gagal mengambil data mesin (Kode: ${res.status}).`);
                }

                const user = data.user;
                const devices = data.devices;

                titleName.innerText = user.name;
                titlePin.innerText = user.pin ? `(PIN: ${user.pin})` : '';
                subtitleRole.innerText = `${user.role} • ${user.class_name}`;

                if (user.db_count > 0) {
                    dbCountBadge.innerHTML = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <i class="fas fa-check-circle text-emerald-600"></i> Tersimpan di Database Server (${user.db_count} Jari)
                    </span>`;
                } else {
                    dbCountBadge.innerHTML = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500 border border-gray-300">
                        <i class="fas fa-times-circle text-gray-400"></i> Belum Ada Rekaman Sidik Jari di Database Server
                    </span>`;
                }

                // Render Daftar Mesin
                if (!devices || devices.length === 0) {
                    deviceListEl.innerHTML = `<div class="p-6 text-center text-gray-400 text-xs">Belum ada mesin fingerprint yang terdaftar di database.</div>`;
                    btnSyncAllMissing.classList.add('hidden');
                } else {
                    let html = '';
                    let hasMissing = false;

                    devices.forEach(dev => {
                        const hasTemplate = dev.has_template;
                        if (!hasTemplate && user.db_count > 0) hasMissing = true;

                        html += `
                        <div class="p-3.5 rounded-2xl border ${hasTemplate ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/30'} flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl ${hasTemplate ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-rose-100 text-rose-600 border border-rose-200'} flex items-center justify-center text-base shrink-0">
                                    <i class="fas ${hasTemplate ? 'fa-check' : 'fa-times'}"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-gray-900 text-sm leading-tight flex items-center gap-1.5">
                                        <span>${dev.name}</span>
                                        <span class="text-[10px] font-normal px-2 py-0.2 rounded-md bg-gray-100 text-gray-600 border border-gray-200">${dev.purpose_label}</span>
                                    </h5>
                                    <p class="text-[11px] text-gray-500 font-mono mt-0.5">IP: ${dev.ip_address}:${dev.port} • SN: ${dev.serial_number}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                ${hasTemplate ? `
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs">
                                        <i class="fas fa-fingerprint text-emerald-600"></i>
                                        <span>Ada di Mesin Ini (${dev.template_count} Jari)</span>
                                    </span>
                                ` : (user.db_count > 0 ? `
                                    <button type="button" onclick="sendFingerprintToSingleDevice('${user.pin}', ${dev.id}, '${dev.name}')" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-xs py-1.5 px-3.5 rounded-xl shadow-xs transition transform hover:scale-105 active:scale-95">
                                        <i class="fas fa-paper-plane text-[10px]"></i>
                                        <span>Kirim ke Mesin Ini</span>
                                    </button>
                                ` : `
                                    <span class="text-xs text-gray-400 font-semibold px-2 py-1">Belum Ada FP di Web</span>
                                `)}
                            </div>
                        </div>
                        `;
                    });

                    deviceListEl.innerHTML = html;

                    if (hasMissing && user.db_count > 0) {
                        btnSyncAllMissing.classList.remove('hidden');
                        btnSyncAllMissing.onclick = () => sendFingerprintToAllMissing(user.pin);
                    } else {
                        btnSyncAllMissing.classList.add('hidden');
                    }
                }

                loadingBox.classList.add('hidden');
                contentBox.classList.remove('hidden');
            } catch (err) {
                loadingBox.innerHTML = `<div class="p-6 text-center text-rose-500 font-semibold text-xs">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2 text-rose-400"></i><br>
                    ${err.message || 'Terjadi kesalahan saat memuat data.'}
                </div>`;
            }
        };

        window.closeFingerprintDeviceModal = function() {
            const modal = document.getElementById('modal-device-fingerprints');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        };

        window.sendFingerprintToSingleDevice = async function(pin, deviceId, deviceName) {
            Swal.fire({
                title: 'Kirim ke Mesin',
                text: `Kirim sidik jari PIN ${pin} ke mesin ${deviceName}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim Sekarang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    try {
                        const res = await fetch("{{ route('sync.syncMissing') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                pins: [pin],
                                target_device_id: deviceId
                            })
                        });
                        const data = await res.json();
                        return data;
                    } catch (e) {
                        Swal.showValidationMessage('Gagal mengirim ke antrean mesin.');
                    }
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dijadwalkan!',
                        text: `Sidik jari berhasil dijadwalkan ke antrean mesin ${deviceName}. Mesin akan otomatis mengunduh saat kontak ADMS.`,
                        confirmButtonColor: '#4f46e5'
                    });
                    // Refresh modal
                    showFingerprintDeviceModal(pin);
                }
            });
        };

        window.sendFingerprintToAllMissing = async function(pin) {
            Swal.fire({
                title: 'Sebar ke Semua Mesin',
                text: `Sebarkan sidik jari PIN ${pin} ke seluruh mesin yang belum memilikinya?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Sebar Sekarang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#7c3aed',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    try {
                        const res = await fetch("{{ route('sync.syncMissing') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                pins: [pin]
                            })
                        });
                        const data = await res.json();
                        return data;
                    } catch (e) {
                        Swal.showValidationMessage('Gagal mengirim ke antrean mesin.');
                    }
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dijadwalkan!',
                        text: `Sidik jari berhasil disebarkan ke antrean seluruh mesin yang belum memilikinya.`,
                        confirmButtonColor: '#7c3aed'
                    });
                    showFingerprintDeviceModal(pin);
                }
            });
        };

        // Delegasi event klik untuk tombol cek sidik jari & backdrop
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modal-device-fingerprints');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeFingerprintDeviceModal();
                    }
                });
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeFingerprintDeviceModal();
                }
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-check-fingerprint');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const pin = btn.getAttribute('data-pin') || '';
                    const name = btn.getAttribute('data-name') || '';
                    const role = btn.getAttribute('data-role') || '';
                    const id = btn.getAttribute('data-id') || '';
                    showFingerprintDeviceModal(pin, name, role, id);
                }
            });
        });
    </script>

    <!-- Modal Cek Ketersediaan Mesin (Global) -->
    <div id="modal-device-fingerprints" style="display: none;" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in duration-150">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 p-5 text-white flex items-center justify-between border-b border-indigo-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/20 text-purple-300 border border-purple-400/30 flex items-center justify-center text-lg shrink-0">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-base text-white flex items-center gap-2">
                            <span id="mdf-user-name">Nama User</span>
                            <span class="text-xs font-mono text-purple-200" id="mdf-user-pin">(PIN: -)</span>
                        </h4>
                        <p class="text-xs text-purple-200/80 mt-0.5" id="mdf-user-role">Siswa • Kelas -</p>
                    </div>
                </div>

                <button type="button" onclick="closeFingerprintDeviceModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <!-- Loading State -->
                <div id="mdf-loading" class="py-12 text-center text-gray-400">
                    <div class="inline-block animate-spin w-8 h-8 border-3 border-purple-600 border-t-transparent rounded-full mb-3"></div>
                    <p class="text-xs font-semibold text-gray-500">Memeriksa ketersediaan sidik jari di seluruh mesin...</p>
                </div>

                <!-- Content State -->
                <div id="mdf-content" class="hidden space-y-4">
                    <!-- Status Database Server -->
                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-gray-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">
                            Status Database Server:
                        </span>
                        <div id="mdf-db-count">
                            <!-- Injected by JS -->
                        </div>
                    </div>

                    <!-- Judul Ketersediaan Mesin -->
                    <div>
                        <h5 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-2.5 flex items-center gap-1.5">
                            <i class="fas fa-server text-indigo-500"></i> Ketersediaan di Mesin-Mesin Fingerprint:
                        </h5>
                        <div id="mdf-device-list" class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                            <!-- Injected by JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50/80 px-6 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <button type="button" id="mdf-btn-sync-all" class="hidden inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold py-2 px-4 rounded-xl text-xs shadow-sm transition">
                    <i class="fas fa-share-alt"></i>
                    <span>Sebar ke Seluruh Mesin yang Belum Memiliki</span>
                </button>

                <button type="button" onclick="closeFingerprintDeviceModal()" class="ml-auto bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-xl text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</body>
</html>