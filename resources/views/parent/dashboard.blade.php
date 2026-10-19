<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Presensi - {{ $student->name }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 flex flex-col justify-between antialiased selection:bg-blue-600 selection:text-white">

    <!-- Top Navigation Bar (Mobile-Optimized) -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-4xl mx-auto px-4 h-14 sm:h-16 flex items-center justify-between">
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs">
                    <i class="fas fa-fingerprint text-base sm:text-lg"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-sm sm:text-base tracking-tight text-slate-900 leading-tight">Portal Orang Tua</h1>
                    <span class="text-[11px] sm:text-xs text-slate-500 font-medium block truncate max-w-[180px] sm:max-w-none">{{ $schoolName }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Tombol Keluar Sesi -->
                <form action="{{ route('parent.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 px-3 py-1.5 rounded-full text-xs font-semibold transition border border-slate-200 hover:border-rose-200" title="Akhiri Sesi Portal">
                        <i class="fas fa-arrow-right-from-bracket text-xs"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-3.5 sm:px-4 py-4 sm:py-6 w-full flex-1 space-y-3.5 sm:space-y-4">

        <!-- Flash Message Alerts -->
        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm flex items-start gap-2.5 shadow-xs">
            <i class="fas fa-check-circle mt-0.5 text-emerald-600 text-sm shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm flex items-start gap-2.5 shadow-xs">
            <i class="fas fa-circle-exclamation mt-0.5 text-rose-600 text-sm shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        <!-- Identity Banner Siswa (Mobile-Friendly Hero Card) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    @if($student->photo)
                    <img src="{{ $student->photo_url }}" alt="{{ $student->name }}" class="w-13 h-13 sm:w-16 sm:h-16 rounded-xl object-cover border border-slate-200 shadow-xs shrink-0">
                    @else
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 text-lg sm:text-xl font-bold shrink-0">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                    @endif
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider mb-1">
                            <i class="fas fa-check-circle text-[9px]"></i> Siswa Aktif
                        </div>
                        <h2 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight truncate" id="student-name-display">{{ $student->name }}</h2>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-600 mt-0.5">
                            <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-md">Kelas: {{ $student->schoolClass?->name ?? '-' }}</span>
                            <span class="text-slate-500 font-mono text-[11px]">NIS: <strong>{{ $student->nis ?? $student->device_user_id }}</strong></span>
                        </div>
                        @if($student->schoolClass?->major)
                        <p class="text-[11px] text-slate-500 mt-1 truncate">{{ $student->schoolClass->major->name }}</p>
                        @endif
                    </div>
                </div>

                <!-- Streak Kehadiran Chip -->
                <div class="shrink-0 text-right">
                    <div class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-200 text-amber-800 px-2.5 py-1 rounded-xl font-bold text-xs shadow-2xs">
                        <i class="fas fa-fire text-amber-500 text-sm"></i>
                        <span id="streak-count-display">{{ $streak }} Hari Berturut-turut</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner Keterangan Resmi Disetujui (Jika Izin / Sakit Aktif Hari Ini) -->
        @if($todayPermission)
        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 shadow-xs flex items-start gap-3 text-purple-950" id="permission-banner">
            <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-base shrink-0 border border-purple-200">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="bg-purple-100 text-purple-800 border border-purple-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                        Keterangan Resmi Disetujui
                    </span>
                    <span class="text-[11px] text-purple-600">{{ \Carbon\Carbon::parse($todayPermission->date)->translatedFormat('d M Y') }}</span>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-purple-950 mt-1">Ananda Tercatat Berstatus {{ $todayPermission->type }}</h3>
                <p class="text-xs text-purple-800 mt-0.5 leading-relaxed">
                    {{ $todayPermission->notes ?: 'Telah disampaikan oleh orang tua dan diakui secara resmi dalam sistem presensi sekolah.' }}
                </p>
            </div>
        </div>
        @endif

        <!-- Kartu Status Presensi (2-Kolom Rapi di Mobile & Desktop) -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <!-- Kartu 1: Presensi Datang -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-3.5 sm:p-4 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs sm:text-sm border border-blue-100">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-700">Presensi Masuk</span>
                        </div>
                    </div>

                    <div class="my-2">
                        <div class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight" id="time-kedatangan">
                            {{ $statusKedatanganTime }}
                        </div>
                        <div class="mt-1">
                            <span id="badge-kedatangan" class="inline-block px-2 py-0.5 rounded-md text-[11px] font-bold border 
                                @if($statusKedatangan === 'ontime') bg-emerald-50 text-emerald-700 border-emerald-200
                                @elseif($statusKedatangan === 'late') bg-amber-50 text-amber-700 border-amber-200
                                @elseif($statusKedatangan === 'permission') bg-purple-50 text-purple-700 border-purple-200
                                @elseif($statusKedatangan === 'holiday') bg-slate-100 text-slate-700 border-slate-200
                                @else bg-rose-50 text-rose-700 border-rose-200 @endif">
                                {{ $statusKedatanganLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 pt-2 border-t border-slate-100 flex items-center justify-between">
                    <span>Batas Tepat Waktu</span>
                    <span class="font-semibold text-slate-700">{{ $checkinEnd }} WIB</span>
                </div>
            </div>

            <!-- Kartu 2: Presensi Pulang -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-3.5 sm:p-4 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs sm:text-sm border border-emerald-100">
                                <i class="fas fa-person-walking-arrow-right"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-700">Presensi Pulang</span>
                        </div>
                    </div>

                    <div class="my-2">
                        <div class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight" id="time-kepulangan">
                            {{ $statusKepulanganTime }}
                        </div>
                        <div class="mt-1">
                            <span id="badge-kepulangan" class="inline-block px-2 py-0.5 rounded-md text-[11px] font-bold border 
                                @if($statusKepulangan === 'departed') bg-emerald-50 text-emerald-700 border-emerald-200
                                @elseif($statusKepulangan === 'in_school') bg-blue-50 text-blue-700 border-blue-200
                                @else bg-slate-100 text-slate-600 border-slate-200 @endif">
                                {{ $statusKepulanganLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 pt-2 border-t border-slate-100">
                    <div class="truncate">
                        <span>Durasi: </span>
                        <span class="font-bold text-slate-800" id="study-duration">{{ $studyDuration }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Layanan Utama (Thumb-Friendly di Mobile) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                    <i class="fas fa-bolt text-amber-500"></i> Layanan Presensi Siswa
                </span>
                <span class="text-[11px] text-slate-500">Notifikasi & Permohonan Izin</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <!-- Tombol 1: Ajukan Izin / Sakit -->
                <button 
                    type="button" 
                    onclick="openPermissionModal()" 
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2.5 px-4 rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 transition active:scale-[0.98] shadow-xs"
                >
                    <i class="fas fa-envelope-open-text text-sm"></i>
                    <span>Kirim Izin / Sakit</span>
                </button>

                <!-- Tombol 2: Aktifkan Push Notification -->
                <button 
                    type="button" 
                    id="btn-subscribe-push" 
                    onclick="subscribeWebPush()" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 transition active:scale-[0.98] shadow-xs"
                >
                    <i class="fas fa-bell text-sm"></i>
                    <span id="push-btn-text">Aktifkan Notifikasi di HP</span>
                </button>
            </div>

            <!-- Link Tes Push Notifikasi -->
            <div class="text-center pt-1">
                <button 
                    type="button" 
                    id="btn-test-push" 
                    onclick="triggerTestPush()" 
                    class="text-xs text-slate-500 hover:text-blue-600 inline-flex items-center gap-1.5 transition font-medium"
                >
                    <i class="fas fa-paper-plane text-[11px]"></i>
                    <span>Kirim Notifikasi Uji Coba ke Layar HP Ini</span>
                </button>
            </div>
        </div>

        <!-- Alur Aktivitas Presensi Hari Ini (Horizontal Visual Tracker) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3.5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-route"></i>
                    </div>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900">Alur Presensi Hari Ini</h3>
                </div>
                <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</span>
                </div>
            </div>

            <!-- Horizontal Stepper Progress -->
            <div class="relative py-2 px-2 sm:px-6">
                <!-- Background Line -->
                <div class="absolute top-4 left-8 right-8 h-1 bg-slate-100 -z-0">
                    <div class="h-full bg-blue-600 transition-all duration-500 rounded-full"
                         style="width: {{ $statusKepulangan === 'departed' ? '100%' : ($todayCheckin ? '50%' : '0%') }}"></div>
                </div>

                <div class="flex items-center justify-between relative z-10">
                    <!-- Step 1: Datang -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs
                            @if($todayPermission) bg-purple-100 text-purple-700 border-2 border-purple-500
                            @elseif($todayCheckin) bg-emerald-600 text-white
                            @else bg-white text-slate-400 border-2 border-slate-300 @endif">
                            @if($todayCheckin) <i class="fas fa-check text-[10px]"></i>
                            @elseif($todayPermission) <i class="fas fa-file-medical text-[10px]"></i>
                            @else 1 @endif
                        </div>
                        <span class="text-xs font-bold text-slate-900 mt-2">1. Datang</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">
                            @if($todayPermission) Izin Resmi
                            @elseif($todayCheckin) {{ \Carbon\Carbon::parse($todayCheckin->timestamp)->format('H:i') }} WIB
                            @else Batas {{ $checkinEnd }} @endif
                        </span>
                    </div>

                    <!-- Step 2: KBM Kelas -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs
                            @if($todayPermission) bg-slate-100 text-slate-400 border-2 border-slate-300
                            @elseif($todayCheckout) bg-emerald-600 text-white
                            @elseif($todayCheckin) bg-blue-600 text-white ring-4 ring-blue-100
                            @else bg-white text-slate-400 border-2 border-slate-300 @endif">
                            @if($todayCheckout) <i class="fas fa-check text-[10px]"></i>
                            @elseif($todayCheckin && !$todayCheckout) <i class="fas fa-book-open text-[10px] animate-pulse"></i>
                            @else 2 @endif
                        </div>
                        <span class="text-xs font-bold text-slate-900 mt-2">2. KBM Kelas</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">
                            @if($todayCheckout) Selesai
                            @elseif($todayCheckin) Sedang Belajar
                            @elseif($todayPermission) Dispensasi
                            @else Menunggu @endif
                        </span>
                    </div>

                    <!-- Step 3: Pulang -->
                    <div class="flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs
                            @if($todayCheckout) bg-emerald-600 text-white
                            @else bg-white text-slate-400 border-2 border-slate-300 @endif">
                            @if($todayCheckout) <i class="fas fa-check text-[10px]"></i>
                            @else 3 @endif
                        </div>
                        <span class="text-xs font-bold text-slate-900 mt-2">3. Pulang</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">
                            @if($todayCheckout) {{ \Carbon\Carbon::parse($todayCheckout->timestamp)->format('H:i') }} WIB
                            @else Mulai {{ $checkoutStart }} @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rekap Presensi Bulanan (1 Bulan Penuh) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <!-- Header dengan Navigator Bulan -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-calendar-days text-blue-600"></i>
                        <span>Rekap Presensi Bulanan</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Pantau ringkasan dan rincian kehadiran ananda per bulan</p>
                </div>

                <!-- Kontrol Navigasi Bulan -->
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <a href="{{ route('parent.index', ['month' => $prevMonth]) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 flex items-center justify-center transition shadow-2xs" title="Bulan Sebelumnya">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                    <span class="px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-xs font-bold text-slate-800 tracking-wide">
                        {{ $monthName }}
                    </span>
                    <a href="{{ route('parent.index', ['month' => $nextMonth]) }}" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 flex items-center justify-center transition shadow-2xs" title="Bulan Berikutnya">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                </div>
            </div>

            <!-- Ringkasan Statistik 4 Kategori (Hadir, Terlambat, Izin, Alpha) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <div class="bg-emerald-50/70 border border-emerald-100 rounded-xl p-2.5 text-center">
                    <span class="text-[11px] text-emerald-700 font-semibold block">Tepat Waktu</span>
                    <strong class="text-lg font-black text-emerald-800">{{ $statsHadir }}</strong>
                    <span class="text-[10px] text-emerald-600 block">Hari</span>
                </div>
                <div class="bg-amber-50/70 border border-amber-100 rounded-xl p-2.5 text-center">
                    <span class="text-[11px] text-amber-700 font-semibold block">Terlambat</span>
                    <strong class="text-lg font-black text-amber-800">{{ $statsTerlambat }}</strong>
                    <span class="text-[10px] text-amber-600 block">Hari</span>
                </div>
                <div class="bg-purple-50/70 border border-purple-100 rounded-xl p-2.5 text-center">
                    <span class="text-[11px] text-purple-700 font-semibold block">Izin / Sakit</span>
                    <strong class="text-lg font-black text-purple-800">{{ $statsIzin }}</strong>
                    <span class="text-[10px] text-purple-600 block">Hari</span>
                </div>
                <div class="bg-rose-50/70 border border-rose-100 rounded-xl p-2.5 text-center">
                    <span class="text-[11px] text-rose-700 font-semibold block">Alpha / Belum</span>
                    <strong class="text-lg font-black text-rose-800">{{ $statsAlpha }}</strong>
                    <span class="text-[10px] text-rose-600 block">Hari</span>
                </div>
            </div>

            <!-- Kalender Mini Visual 1 Bulan -->
            <div class="bg-slate-50/80 rounded-2xl p-3 sm:p-4 border border-slate-150">
                <div class="text-xs font-bold text-slate-700 mb-2.5 flex items-center justify-between">
                    <span>Kalender Kehadiran {{ $monthName }}</span>
                    <div class="flex items-center gap-3 text-[10px] font-medium text-slate-500">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Hadir</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Terlambat</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Izin</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Alpha</span>
                    </div>
                </div>

                <!-- 7 Kolom Hari (Sen - Min) -->
                <div class="grid grid-cols-7 gap-1.5 sm:gap-2 text-center text-[10px] sm:text-xs">
                    <span class="font-bold text-slate-400 py-1">Sen</span>
                    <span class="font-bold text-slate-400 py-1">Sel</span>
                    <span class="font-bold text-slate-400 py-1">Rab</span>
                    <span class="font-bold text-slate-400 py-1">Kam</span>
                    <span class="font-bold text-slate-400 py-1">Jum</span>
                    <span class="font-bold text-slate-400 py-1">Sab</span>
                    <span class="font-bold text-rose-400 py-1">Min</span>

                    <!-- Kotak Kosong Sebelum Hari Pertama Bulan Ini -->
                    @for($i = 1; $i < $startDayOfWeek; $i++)
                        <div class="h-10 sm:h-12 rounded-lg bg-transparent"></div>
                    @endfor

                    <!-- Daftar Hari 1 .. 31 -->
                    @foreach($monthlyDays as $d)
                        <div class="h-10 sm:h-12 rounded-lg p-1 flex flex-col items-center justify-between border transition
                            @if($d['is_today']) ring-2 ring-blue-500 bg-white border-blue-200
                            @elseif($d['is_holiday']) bg-slate-100/70 border-slate-200/50 text-slate-400
                            @elseif($d['status'] === 'ontime') bg-emerald-50 border-emerald-200 text-emerald-900
                            @elseif($d['status'] === 'late') bg-amber-50 border-amber-200 text-amber-900
                            @elseif($d['status'] === 'permission') bg-purple-50 border-purple-200 text-purple-900
                            @elseif($d['status'] === 'absent') bg-rose-50 border-rose-200 text-rose-900
                            @else bg-white border-slate-200/60 text-slate-400 @endif"
                            title="{{ $d['date_formatted'] }}: {{ $d['label'] }} ({{ $d['checkin_time'] }})">
                            <span class="font-bold text-[11px] sm:text-xs leading-none {{ $d['day_of_week'] == 7 ? 'text-rose-500' : '' }}">{{ $d['day'] }}</span>
                            
                            @if($d['status'] === 'ontime')
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            @elseif($d['status'] === 'late')
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @elseif($d['status'] === 'permission')
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            @elseif($d['status'] === 'absent')
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            @elseif($d['is_holiday'])
                                <span class="text-[9px] text-slate-400 font-semibold">Libur</span>
                            @else
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Tabel Daftar Rincian Harian Bulan Ini -->
            <div class="space-y-2 pt-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">Daftar Rincian Presensi Harian</span>
                    <span class="text-[11px] text-slate-500">{{ count(array_filter($monthlyDays, fn($i) => !$i['is_future'])) }} Hari Terdata</span>
                </div>

                <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-slate-600 text-[11px] uppercase tracking-wider">
                                <th class="py-2.5 px-3 rounded-l-lg">Tanggal</th>
                                <th class="py-2.5 px-3">Status</th>
                                <th class="py-2.5 px-3">Masuk</th>
                                <th class="py-2.5 px-3">Pulang</th>
                                <th class="py-2.5 px-3">Durasi</th>
                                <th class="py-2.5 px-3 rounded-r-lg">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse(array_reverse($monthlyDays) as $item)
                            @if(!$item['is_future'])
                            <tr class="hover:bg-slate-50 transition {{ $item['is_today'] ? 'bg-blue-50/30' : '' }}">
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        @if($item['is_today'])
                                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                        @endif
                                        <div>
                                            <span class="font-bold text-slate-900 block leading-tight">{{ $item['day_name'] }}</span>
                                            <span class="text-[11px] text-slate-500">{{ $item['date_formatted'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    @if($item['status'] === 'ontime')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check text-[9px]"></i> Tepat Waktu
                                    </span>
                                    @elseif($item['status'] === 'late')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-clock text-[9px]"></i> Terlambat
                                    </span>
                                    @elseif($item['status'] === 'permission')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        <i class="fas fa-file-lines text-[9px]"></i> {{ $item['label'] }}
                                    </span>
                                    @elseif($item['status'] === 'holiday')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        Libur Akhir Pekan
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-xmark text-[9px]"></i> Belum Hadir / Alpha
                                    </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-900 whitespace-nowrap">
                                    {{ $item['checkin_time'] }}
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-900 whitespace-nowrap">
                                    {{ $item['checkout_time'] }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap">
                                    {{ $item['duration'] }}
                                </td>
                                <td class="py-2.5 px-3 text-[11px] text-slate-500">
                                    {{ $item['notes'] ?: '-' }}
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-slate-400">
                                    Belum ada data presensi untuk bulan ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- Modal Form Pengajuan Izin / Sakit Mandiri -->
    <div id="permission-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-3xl sm:rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl relative animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-base border border-purple-100">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">Formulir Keterangan Izin / Sakit</h3>
                        <p class="text-[11px] text-slate-500">Kirim pemberitahuan resmi untuk ananda {{ $student->name }}</p>
                    </div>
                </div>
                <button type="button" onclick="closePermissionModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('parent.permission.store') }}" method="POST" id="form-permission" class="space-y-3.5">
                @csrf

                <!-- Tanggal Izin / Sakit -->
                <div>
                    <label for="perm-date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Tanggal Berhalangan Hadir <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="date" 
                        id="perm-date" 
                        value="{{ $today }}" 
                        required 
                        class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500 transition"
                    >
                </div>

                <!-- Jenis Keterangan -->
                <div>
                    <label for="perm-type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Jenis Keterangan <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="type" 
                        id="perm-type" 
                        required 
                        class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500 transition cursor-pointer"
                    >
                        <option value="Sakit" selected>Sakit</option>
                        <option value="Izin Ada Keperluan Keluarga">Izin Ada Keperluan Keluarga</option>
                        <option value="Izin Lainnya">Izin Lainnya</option>
                    </select>
                </div>

                <!-- Catatan / Keterangan Tambahan -->
                <div>
                    <label for="perm-notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Alasan / Keterangan Dokter (Opsional)
                    </label>
                    <textarea 
                        name="notes" 
                        id="perm-notes" 
                        rows="2.5" 
                        placeholder="Contoh: Mengalami demam dan disarankan istirahat dokter..." 
                        class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 transition"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button 
                        type="button" 
                        onclick="closePermissionModal()" 
                        class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 transition"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-4 py-2 rounded-xl text-xs sm:text-sm shadow-xs transition active:scale-[0.98] flex items-center gap-1.5"
                    >
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Kirimkan Keterangan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full py-4 text-center text-xs text-slate-400 border-t border-slate-200/80 bg-white">
        <p>&copy; {{ date('Y') }} Sistem Presensi Sekolah. Seluruh hak cipta dilindungi.</p>
    </footer>

    <!-- Script Web Push W3C & Realtime Polling -->
    <script>
        const VAPID_PUBLIC_KEY = "{{ $vapidPublicKey }}";
        const CSRF_TOKEN = "{{ csrf_token() }}";

        function openPermissionModal() {
            document.getElementById('permission-modal').classList.remove('hidden');
        }

        function closePermissionModal() {
            document.getElementById('permission-modal').classList.add('hidden');
        }

        // Helper Konversi base64 URL-safe ke Uint8Array
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        // Daftarkan Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(function(reg) {
                    console.log('Service Worker terdaftar:', reg);
                })
                .catch(function(err) {
                    console.error('Service Worker gagal didaftarkan:', err);
                });
        }

        // Aktifkan Notifikasi Web Push pada Perangkat Ini
        async function subscribeWebPush() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Didukung',
                    text: 'Browser ini belum mendukung fitur Web Push Notifications.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            const btn = document.getElementById('btn-subscribe-push');
            const btnText = document.getElementById('push-btn-text');
            btnText.innerText = 'Mengaktifkan..';
            btn.disabled = true;

            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    throw new Error('Izin notifikasi belum diizinkan oleh pengguna pada peramban ini.');
                }

                const reg = await navigator.serviceWorker.ready;
                let subscription = await reg.pushManager.getSubscription();

                if (!subscription) {
                    if (!VAPID_PUBLIC_KEY) {
                        throw new Error('VAPID Public Key belum terkonfigurasi di server.');
                    }
                    const convertedVapidKey = urlBase64ToUint8Array(VAPID_PUBLIC_KEY);
                    subscription = await reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: convertedVapidKey
                    });
                }

                const res = await fetch("{{ route('parent.push.subscribe') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(subscription.toJSON())
                });

                const data = await res.json();

                if (res.ok) {
                    btnText.innerText = 'Notifikasi Aktif';
                    btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                    Swal.fire({
                        icon: 'success',
                        title: 'Notifikasi Aktif!',
                        text: 'Layar HP / peramban Anda akan menerima notifikasi setiap kali ananda melakukan presensi sidik jari.',
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    throw new Error(data.message || 'Gagal menyimpan langganan notifikasi.');
                }
            } catch (err) {
                btnText.innerText = 'Aktifkan Notifikasi di HP';
                btn.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengaktifkan Notifikasi',
                    text: err.message,
                    confirmButtonColor: '#2563eb'
                });
            }
        }

        // Trigger Uji Coba Notifikasi Layar
        async function triggerTestPush() {
            const btn = document.getElementById('btn-test-push');
            btn.disabled = true;

            try {
                const res = await fetch("{{ route('parent.push.test') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();

                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terkirim!',
                        text: data.message,
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Perhatian',
                        text: data.message || 'Silakan klik tombol "Aktifkan Notifikasi di HP" terlebih dahulu.',
                        confirmButtonColor: '#2563eb'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: err.message,
                    confirmButtonColor: '#2563eb'
                });
            } finally {
                btn.disabled = false;
            }
        }

        // Live Polling Sinkronisasi Otomatis Setiap 20 Detik
        setInterval(async () => {
            try {
                const res = await fetch("{{ route('parent.data') }}", {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;

                const data = await res.json();
                if (data.status === 'success') {
                    // Update Streak
                    if (data.student && data.student.streak !== undefined) {
                        const streakEl = document.getElementById('streak-count-display');
                        if (streakEl) streakEl.innerText = data.student.streak + ' Hari Berturut-turut';
                    }

                    // Update Checkin Info
                    if (data.attendance && data.attendance.checkin) {
                        const timeKedatangan = document.getElementById('time-kedatangan');
                        if (timeKedatangan && timeKedatangan.innerText === '-') {
                            timeKedatangan.innerText = data.attendance.checkin.time + ' WIB';
                            const badge = document.getElementById('badge-kedatangan');
                            if (badge) {
                                badge.innerText = data.attendance.checkin.is_ontime ? 'Hadir Tepat Waktu' : 'Hadir Terlambat';
                                badge.className = 'inline-block px-2 py-0.5 rounded-md text-[11px] font-bold border ' + 
                                    (data.attendance.checkin.is_ontime ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200');
                            }
                        }
                    }

                    // Update Checkout Info
                    if (data.attendance && data.attendance.checkout) {
                        const timeKepulangan = document.getElementById('time-kepulangan');
                        if (timeKepulangan && timeKepulangan.innerText === '-') {
                            timeKepulangan.innerText = data.attendance.checkout.time + ' WIB';
                            const badge = document.getElementById('badge-kepulangan');
                            if (badge) {
                                badge.innerText = 'Sudah Pulang Sekolah';
                                badge.className = 'inline-block px-2 py-0.5 rounded-md text-[11px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200';
                            }
                        }
                    }
                }
            } catch (e) {
                // Ignore polling background fetch errors
            }
        }, 20000);
    </script>
</body>
</html>
