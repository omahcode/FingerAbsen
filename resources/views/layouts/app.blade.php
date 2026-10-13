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
                <!-- SECTION 1: MONITORING & PRESENSI -->
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

                        <a href="{{ route('admin.teachers.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.teachers.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}" title="Kelola Akun Guru">
                            <i class="fas fa-user-tie w-5 text-center shrink-0 {{ request()->routeIs('admin.teachers.*') ? 'text-white' : 'text-cyan-400' }}"></i>
                            <span class="sidebar-text truncate">Kelola Akun Guru</span>
                        </a>
                    </nav>
                </div>

                <!-- SECTION 4: SISTEM & LAPORAN (ADMIN ONLY) -->
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
                @endauth

            </div>

            <!-- Sidebar User Profile Footer -->
            @auth
            <div class="p-3 border-t border-slate-800/80 bg-slate-950 shrink-0">
                <div class="sidebar-user-card flex items-center justify-between p-2 rounded-xl bg-slate-900/90 border border-slate-800">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/30 text-indigo-400 border border-indigo-500/40 font-bold flex items-center justify-center shrink-0 text-xs">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="sidebar-user-info overflow-hidden">
                            <div class="font-bold text-white text-xs truncate max-w-[100px] xl:max-w-[120px]">{{ Auth::user()->name }}</div>
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-1 py-0.2 rounded {{ Auth::user()->isGuru() ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-blue-950 text-blue-400 border border-blue-800/40' }}">
                                {{ Auth::user()->isGuru() ? 'Wali Kelas' : 'Admin' }}
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
                    <a href="{{ route('tv.index') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-xs border border-emerald-200 shadow-sm transition">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <i class="fas fa-tv text-xs"></i>
                        <span class="hidden sm:inline">Layar TV</span>
                    </a>

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
    </script>
</body>
</html>