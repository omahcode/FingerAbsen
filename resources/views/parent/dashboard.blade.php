<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Orang Tua - {{ $student->name }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .animate-pulse-ring {
            animation: pulse-ring 2s infinite;
        }
        .highlight-card-update {
            animation: card-highlight 1.5s ease-out;
        }
        @keyframes card-highlight {
            0% { transform: scale(0.98); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.5); }
            100% { transform: scale(1); box-shadow: none; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 flex flex-col justify-between antialiased selection:bg-blue-600 selection:text-white">

    <!-- Top Navigation Bar (Mobile-Optimized) -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs backdrop-blur-md bg-white/95">
        <div class="max-w-4xl mx-auto px-4 h-14 sm:h-16 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                    <i class="fas fa-fingerprint text-base sm:text-lg"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="font-extrabold text-sm sm:text-base tracking-tight text-slate-900 leading-tight truncate">Portal Orang Tua</h1>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="hidden sm:inline">Live Sync</span>
                        </span>
                    </div>
                    <span class="text-[11px] sm:text-xs text-slate-500 font-medium block truncate max-w-[170px] sm:max-w-none">{{ $schoolName }}</span>
                </div>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <!-- Toggle Suara Notifikasi -->
                <button 
                    type="button" 
                    id="btn-sound-toggle" 
                    onclick="toggleSoundPreference()" 
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-full text-xs font-semibold transition border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700" 
                    title="Atur Suara Notifikasi"
                >
                    <i id="sound-icon" class="fas fa-volume-high text-xs text-blue-600"></i>
                    <span id="sound-label" class="hidden sm:inline">Suara: Aktif</span>
                </button>

                <!-- Tombol Keluar Sesi -->
                <form action="{{ route('parent.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 px-3 py-1.5 rounded-full text-xs font-semibold transition border border-slate-200 hover:border-rose-200" title="Akhiri Sesi Portal">
                        <i class="fas fa-arrow-right-from-bracket text-xs"></i>
                        <span class="hidden sm:inline">Keluar</span>
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
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-6 -mt-6 w-28 h-28 rounded-full bg-blue-50/50 -z-0 pointer-events-none"></div>

            <div class="flex items-start justify-between gap-3 relative z-10">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    @if($student->photo)
                    <img src="{{ $student->photo_url }}" alt="{{ $student->name }}" class="w-13 h-13 sm:w-16 sm:h-16 rounded-xl object-cover border border-slate-200 shadow-xs shrink-0">
                    @else
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-blue-600 text-white flex items-center justify-center text-lg sm:text-xl font-black shadow-xs shrink-0">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                    @endif
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider">
                                <i class="fas fa-check-circle text-[9px]"></i> Siswa Aktif
                            </span>
                            <span class="text-[11px] text-slate-400 hidden sm:inline">•</span>
                            <span class="text-[11px] text-slate-500 font-medium hidden sm:inline">{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</span>
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
                        <span id="streak-count-display">{{ $streak }} Hari Berturut</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner Keterangan Resmi Disetujui (Jika Izin / Sakit Aktif Hari Ini) -->
        <div id="permission-banner-container" class="{{ $todayPermission ? '' : 'hidden' }}">
            @if($todayPermission)
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-purple-950" id="permission-banner">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg shrink-0 border border-purple-200">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="bg-purple-100 text-purple-800 border border-purple-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                Keterangan Resmi Disetujui
                            </span>
                            <span class="text-[11px] text-purple-600 font-medium" id="permission-date-display">
                                {{ \Carbon\Carbon::parse($todayPermission->date)->translatedFormat('d M Y') }}
                            </span>
                        </div>
                        <h3 class="text-sm sm:text-base font-extrabold text-purple-950 mt-1" id="permission-type-display">
                            Ananda Tercatat Berstatus {{ $todayPermission->type }}
                        </h3>
                        <p class="text-xs text-purple-800 mt-0.5 leading-relaxed" id="permission-notes-display">
                            {{ $todayPermission->notes ?: 'Telah disampaikan secara resmi oleh orang tua dan terverifikasi pada sistem presensi sekolah.' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-purple-200/60 w-full sm:w-auto justify-end">
                    @if($todayPermission->attachment)
                    <a href="{{ asset('storage/' . $todayPermission->attachment) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold transition shadow-2xs">
                        <i class="fas fa-paperclip text-xs"></i>
                        <span>Lihat Lampiran</span>
                    </a>
                    @endif
                    <button type="button" onclick="cancelCurrentPermission({{ $todayPermission->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 hover:border-rose-200 text-xs font-semibold transition shadow-2xs" title="Batalkan Keterangan Izin/Sakit Hari Ini">
                        <i class="fas fa-trash-can text-xs"></i>
                        <span>Batalkan</span>
                    </button>
                </div>
            </div>
            @endif
        </div>

        <!-- Kartu Status Presensi (2-Kolom Rapi di Mobile & Desktop) -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <!-- Kartu 1: Presensi Datang -->
            <div id="card-kedatangan" class="bg-white border border-slate-200/90 rounded-2xl p-3.5 sm:p-4 shadow-xs flex flex-col justify-between transition duration-300">
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
            <div id="card-kepulangan" class="bg-white border border-slate-200/90 rounded-2xl p-3.5 sm:p-4 shadow-xs flex flex-col justify-between transition duration-300">
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
                    <i class="fas fa-bolt text-amber-500"></i> Layanan Cepat Orang Tua
                </span>
                <span class="text-[11px] text-slate-500 font-medium">Izin, Suara & Notifikasi Layar</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
                <!-- Tombol 1: Ajukan Izin / Sakit -->
                <button 
                    type="button" 
                    onclick="openPermissionModal()" 
                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-2.5 px-3 rounded-xl text-xs flex flex-col sm:flex-row items-center justify-center gap-1.5 sm:gap-2 transition active:scale-[0.98] shadow-xs"
                >
                    <i class="fas fa-envelope-open-text text-sm"></i>
                    <span>Kirim Izin/Sakit</span>
                </button>

                <!-- Tombol 2: Aktifkan Push Notification HP -->
                <button 
                    type="button" 
                    id="btn-subscribe-push" 
                    onclick="subscribeWebPush()" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-3 rounded-xl text-xs flex flex-col sm:flex-row items-center justify-center gap-1.5 sm:gap-2 transition active:scale-[0.98] shadow-xs"
                >
                    <i class="fas fa-bell text-sm"></i>
                    <span id="push-btn-text">Notifikasi di HP</span>
                </button>

                <!-- Tombol 3: Tes Bunyi Bel & Notifikasi -->
                <button 
                    type="button" 
                    id="btn-test-push" 
                    onclick="testSoundAndNotification()" 
                    class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 px-3 rounded-xl text-xs flex flex-col sm:flex-row items-center justify-center gap-1.5 sm:gap-2 transition active:scale-[0.98] border border-slate-200"
                >
                    <i class="fas fa-music text-blue-600 text-sm"></i>
                    <span>Tes Bel & Notif</span>
                </button>

                <!-- Tombol 4: Bagikan Bukti ke WhatsApp -->
                <button 
                    type="button" 
                    onclick="shareToWhatsApp()" 
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-3 rounded-xl text-xs flex flex-col sm:flex-row items-center justify-center gap-1.5 sm:gap-2 transition active:scale-[0.98] shadow-xs"
                >
                    <i class="fab fa-whatsapp text-sm"></i>
                    <span>Kirim ke WA</span>
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
                    <div id="stepper-progress-bar" class="h-full bg-blue-600 transition-all duration-500 rounded-full"
                         style="width: {{ $statusKepulangan === 'departed' ? '100%' : ($todayCheckin ? '50%' : '0%') }}"></div>
                </div>

                <div class="flex items-center justify-between relative z-10">
                    <!-- Step 1: Datang -->
                    <div class="flex flex-col items-center text-center">
                        <div id="step-1-circle" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs
                            @if($todayPermission) bg-purple-100 text-purple-700 border-2 border-purple-500
                            @elseif($todayCheckin) bg-emerald-600 text-white
                            @else bg-white text-slate-400 border-2 border-slate-300 @endif">
                            @if($todayCheckin) <i class="fas fa-check text-[10px]"></i>
                            @elseif($todayPermission) <i class="fas fa-file-medical text-[10px]"></i>
                            @else 1 @endif
                        </div>
                        <span class="text-xs font-bold text-slate-900 mt-2">1. Datang</span>
                        <span class="text-[10px] text-slate-500 mt-0.5" id="step-1-sub">
                            @if($todayPermission) Izin Resmi
                            @elseif($todayCheckin) {{ \Carbon\Carbon::parse($todayCheckin->timestamp)->format('H:i') }} WIB
                            @else Batas {{ $checkinEnd }} @endif
                        </span>
                    </div>

                    <!-- Step 2: KBM Kelas -->
                    <div class="flex flex-col items-center text-center">
                        <div id="step-2-circle" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs
                            @if($todayPermission) bg-slate-100 text-slate-400 border-2 border-slate-300
                            @elseif($todayCheckout) bg-emerald-600 text-white
                            @elseif($todayCheckin) bg-blue-600 text-white ring-4 ring-blue-100
                            @else bg-white text-slate-400 border-2 border-slate-300 @endif">
                            @if($todayCheckout) <i class="fas fa-check text-[10px]"></i>
                            @elseif($todayCheckin && !$todayCheckout) <i class="fas fa-book-open text-[10px] animate-pulse"></i>
                            @else 2 @endif
                        </div>
                        <span class="text-xs font-bold text-slate-900 mt-2">2. KBM Kelas</span>
                        <span class="text-[10px] text-slate-500 mt-0.5" id="step-2-sub">
                            @if($todayCheckout) Selesai
                            @elseif($todayCheckin) Sedang Belajar
                            @elseif($todayPermission) Dispensasi
                            @else Menunggu @endif
                        </span>
                    </div>

                    <!-- Step 3: Pulang -->
                    <div class="flex flex-col items-center text-center">
                        <div id="step-3-circle" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs
                            @if($todayCheckout) bg-emerald-600 text-white
                            @else bg-white text-slate-400 border-2 border-slate-300 @endif">
                            @if($todayCheckout) <i class="fas fa-check text-[10px]"></i>
                            @else 3 @endif
                        </div>
                        <span class="text-xs font-bold text-slate-900 mt-2">3. Pulang</span>
                        <span class="text-[10px] text-slate-500 mt-0.5" id="step-3-sub">
                            @if($todayCheckout) {{ \Carbon\Carbon::parse($todayCheckout->timestamp)->format('H:i') }} WIB
                            @else Mulai {{ $checkoutStart }} @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linimasa & Pemberitahuan Aktivitas Hari Ini -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3.5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900">Linimasa & Pemberitahuan Hari Ini</h3>
                </div>
                <span class="text-[11px] text-slate-400 font-mono" id="live-clock">{{ $currentTimeWib }} WIB</span>
            </div>

            <div id="activities-container" class="space-y-2.5">
                @forelse($activitiesToday as $act)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50/80 border border-slate-100 transition hover:bg-slate-50">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0 
                        @if($act['type'] === 'checkin') bg-emerald-100 text-emerald-700
                        @elseif($act['type'] === 'checkout') bg-blue-100 text-blue-700
                        @else bg-purple-100 text-purple-700 @endif">
                        <i class="fas {{ $act['icon'] }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-xs font-bold text-slate-900 truncate">{{ $act['title'] }}</h4>
                            <span class="text-[10px] font-semibold text-slate-400 shrink-0">{{ $act['time'] }}</span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">{{ $act['description'] }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-5 text-slate-400 space-y-1">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 text-sm">
                        <i class="fas fa-hourglass-start"></i>
                    </div>
                    <p class="text-xs font-medium text-slate-600">Belum ada aktivitas presensi masuk hari ini</p>
                    <p class="text-[11px] text-slate-400">Notifikasi otomatis akan berbunyi dan muncul begitu ananda tap sidik jari di sekolah.</p>
                </div>
                @endforelse
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

    <!-- Modal Form Pengajuan Izin / Sakit Mandiri & Riwayat Izin -->
    <div id="permission-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-end sm:items-center justify-center p-0 sm:p-4 transition duration-200">
        <div class="bg-white rounded-t-3xl sm:rounded-2xl max-w-lg w-full max-h-[92vh] flex flex-col shadow-2xl relative animate-in fade-in zoom-in duration-150 overflow-hidden">
            
            <!-- Modal Header -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-base border border-purple-100">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">Keterangan Izin / Sakit Siswa</h3>
                        <p class="text-[11px] text-slate-500">Pemberitahuan resmi untuk ananda {{ $student->name }}</p>
                    </div>
                </div>
                <button type="button" onclick="closePermissionModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="flex border-b border-slate-150 px-4 sm:px-5 shrink-0 bg-slate-50/50">
                <button type="button" id="tab-btn-form" onclick="switchPermTab('form')" class="py-2.5 px-3 text-xs font-bold border-b-2 border-purple-600 text-purple-700 transition">
                    <i class="fas fa-edit mr-1"></i> Form Pengajuan
                </button>
                <button type="button" id="tab-btn-history" onclick="switchPermTab('history')" class="py-2.5 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition">
                    <i class="fas fa-history mr-1"></i> Riwayat Izin (<span id="history-count">{{ count($permissionHistory) }}</span>)
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-4 sm:p-5 overflow-y-auto flex-1">
                
                <!-- Tab 1: Form Pengajuan -->
                <div id="tab-content-form" class="space-y-4">
                    <!-- Quick Reason Chips -->
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Pilihan Cepat Alasan:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" onclick="setQuickReason('Sakit', 'Sakit demam/flu, istirahat di rumah.')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 transition">
                                🤒 Demam / Flu
                            </button>
                            <button type="button" onclick="setQuickReason('Sakit', 'Pemeriksaan dokter / rawat jalan ke rumah sakit.')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 transition">
                                🏥 Ke Dokter / RS
                            </button>
                            <button type="button" onclick="setQuickReason('Izin Ada Keperluan Keluarga', 'Menghadiri acara penting keluarga yang tidak dapat ditinggalkan.')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition">
                                👨‍👩‍👧 Acara Keluarga
                            </button>
                            <button type="button" onclick="setQuickReason('Izin Lainnya', 'Mengikuti agenda lomba / perwakilan kegiatan.')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition">
                                🏆 Dispensasi Lomba
                            </button>
                        </div>
                    </div>

                    <form id="form-permission" onsubmit="submitPermissionForm(event)" enctype="multipart/form-data" class="space-y-3.5">
                        @csrf

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

                        <!-- Tanggal Izin / Sakit -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="perm-date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Tanggal Mulai <span class="text-rose-500">*</span>
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
                            <div id="end-date-wrapper" class="hidden">
                                <label for="perm-end-date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Tanggal Selesai (Opsional)
                                </label>
                                <input 
                                    type="date" 
                                    name="end_date" 
                                    id="perm-end-date" 
                                    value="{{ $today }}" 
                                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500 transition"
                                >
                            </div>
                        </div>

                        <!-- Checkbox Multi-day -->
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="check-multi-day" onchange="toggleMultiDay(this)" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-slate-300">
                            <label for="check-multi-day" class="text-xs text-slate-700 font-medium cursor-pointer select-none">
                                Izin lebih dari 1 hari (rentang tanggal)
                            </label>
                        </div>

                        <!-- Catatan Alasan -->
                        <div>
                            <label for="perm-notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Alasan / Keterangan Tambahan
                            </label>
                            <textarea 
                                name="notes" 
                                id="perm-notes" 
                                rows="2.5" 
                                placeholder="Tuliskan keterangan detail sakit atau permohonan izin..." 
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 transition"
                            ></textarea>
                        </div>

                        <!-- Unggah Bukti Surat / Resep Dokter -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Foto Surat Dokter / Bukti Surat (Opsional)
                            </label>
                            <div class="border-2 border-dashed border-slate-200 hover:border-purple-300 rounded-xl p-3 text-center transition bg-slate-50/50">
                                <input 
                                    type="file" 
                                    name="attachment" 
                                    id="perm-attachment" 
                                    accept="image/*,application/pdf" 
                                    onchange="previewAttachment(this)" 
                                    class="hidden"
                                >
                                <label for="perm-attachment" class="cursor-pointer block">
                                    <div id="attachment-placeholder">
                                        <i class="fas fa-cloud-arrow-up text-slate-400 text-xl mb-1"></i>
                                        <p class="text-xs font-semibold text-slate-700">Pilih Foto Surat Dokter / PDF</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Format JPG, PNG, atau PDF (Maks 3MB)</p>
                                    </div>
                                    <div id="attachment-preview" class="hidden flex items-center justify-center gap-2 py-1">
                                        <i class="fas fa-file-check text-emerald-600 text-base"></i>
                                        <span id="attachment-filename" class="text-xs font-bold text-emerald-700 truncate max-w-[200px]"></span>
                                        <span class="text-[10px] text-slate-400">(Klik untuk ganti)</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                onclick="closePermissionModal()" 
                                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                id="btn-submit-perm" 
                                class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold px-4 py-2 rounded-xl text-xs sm:text-sm shadow-xs transition active:scale-[0.98] flex items-center gap-1.5"
                            >
                                <i class="fas fa-paper-plane text-xs"></i>
                                <span id="btn-submit-perm-text">Kirimkan Keterangan</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab 2: Riwayat Izin -->
                <div id="tab-content-history" class="hidden space-y-2.5">
                    <div id="history-list" class="space-y-2.5">
                        @forelse($permissionHistory as $item)
                        <div class="p-3 rounded-xl border border-slate-200/80 bg-slate-50/50 flex items-start justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="font-extrabold text-slate-900">{{ $item->type }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-800">
                                        {{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">{{ $item->notes ?: 'Tidak ada catatan.' }}</p>
                                @if($item->attachment)
                                <a href="{{ asset('storage/' . $item->attachment) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:underline mt-1.5">
                                    <i class="fas fa-paperclip"></i> Lihat Lampiran Surat
                                </a>
                                @endif
                            </div>
                            <button type="button" onclick="cancelCurrentPermission({{ $item->id }})" class="text-slate-400 hover:text-rose-600 p-1.5 transition rounded-lg hover:bg-rose-50" title="Batalkan Izin Ini">
                                <i class="fas fa-trash-can text-xs"></i>
                            </button>
                        </div>
                        @empty
                        <div class="text-center py-6 text-slate-400">
                            <i class="fas fa-inbox text-2xl mb-1 block"></i>
                            <p class="text-xs">Belum ada riwayat permohonan izin ananda.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full py-4 text-center text-xs text-slate-400 border-t border-slate-200/80 bg-white">
        <p>&copy; {{ date('Y') }} Sistem Presensi Sekolah. Seluruh hak cipta dilindungi.</p>
    </footer>

    <!-- Script Web Audio API, Web Push W3C, Sound Effects & Realtime Polling -->
    <script>
        const VAPID_PUBLIC_KEY = "{{ $vapidPublicKey }}";
        const CSRF_TOKEN = "{{ csrf_token() }}";
        const STUDENT_NAME = "{{ $student->name }}";
        const STUDENT_CLASS = "{{ $student->schoolClass?->name ?? '-' }}";
        const SCHOOL_NAME = "{{ $schoolName }}";

        let lastCheckinTime = "{{ $todayCheckin ? \Carbon\Carbon::parse($todayCheckin->timestamp)->format('H:i') : '' }}";
        let lastCheckoutTime = "{{ $todayCheckout ? \Carbon\Carbon::parse($todayCheckout->timestamp)->format('H:i') : '' }}";

        // ==========================================
        // 1. SISTEM AUDIO BEL & NOTIFIKASI WEB AUDIO
        // ==========================================
        let isSoundEnabled = localStorage.getItem('hadirin_parent_sound') !== 'muted';

        function updateSoundUI() {
            const label = document.getElementById('sound-label');
            const icon = document.getElementById('sound-icon');
            if (isSoundEnabled) {
                if (label) label.innerText = 'Suara: Aktif';
                if (icon) {
                    icon.className = 'fas fa-volume-high text-xs text-blue-600';
                }
            } else {
                if (label) label.innerText = 'Suara: Bisu';
                if (icon) {
                    icon.className = 'fas fa-volume-xmark text-xs text-slate-400';
                }
            }
        }
        updateSoundUI();

        function toggleSoundPreference() {
            isSoundEnabled = !isSoundEnabled;
            localStorage.setItem('hadirin_parent_sound', isSoundEnabled ? 'active' : 'muted');
            updateSoundUI();

            if (isSoundEnabled) {
                playChimeSound('success');
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({ icon: 'success', title: 'Suara notifikasi diaktifkan' });
            } else {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
                Toast.fire({ icon: 'info', title: 'Suara dinonaktifkan (hening)' });
            }
        }

        // Sintesis Suara Melodic Chime Alami via Web Audio API
        function playChimeSound(type = 'success') {
            if (!isSoundEnabled) return;
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const now = ctx.currentTime;

                if (type === 'checkin') {
                    // 3-Tone Happy Arpeggio (C5 - E5 - G5)
                    [523.25, 659.25, 783.99].forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, now + (i * 0.1));
                        gain.gain.setValueAtTime(0.18, now + (i * 0.1));
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + (i * 0.1) + 0.35);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + (i * 0.1));
                        osc.stop(now + (i * 0.1) + 0.35);
                    });
                } else if (type === 'checkout') {
                    // 2-Tone Warm Ding (G5 - C5)
                    [783.99, 523.25].forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(freq, now + (i * 0.14));
                        gain.gain.setValueAtTime(0.2, now + (i * 0.14));
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + (i * 0.14) + 0.4);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + (i * 0.14));
                        osc.stop(now + (i * 0.14) + 0.4);
                    });
                } else {
                    // Standard Crisp Melody
                    [659.25, 880.00].forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, now + (i * 0.12));
                        gain.gain.setValueAtTime(0.15, now + (i * 0.12));
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + (i * 0.12) + 0.3);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + (i * 0.12));
                        osc.stop(now + (i * 0.12) + 0.3);
                    });
                }
            } catch (e) {
                console.log('Web Audio sound not available:', e);
            }
        }

        // ==========================================
        // 2. MODAL FORM PERMISSION & RIWAYAT IZIN
        // ==========================================
        function openPermissionModal() {
            document.getElementById('permission-modal').classList.remove('hidden');
        }

        function closePermissionModal() {
            document.getElementById('permission-modal').classList.add('hidden');
        }

        function switchPermTab(tab) {
            const btnForm = document.getElementById('tab-btn-form');
            const btnHistory = document.getElementById('tab-btn-history');
            const contentForm = document.getElementById('tab-content-form');
            const contentHistory = document.getElementById('tab-content-history');

            if (tab === 'form') {
                btnForm.className = 'py-2.5 px-3 text-xs font-bold border-b-2 border-purple-600 text-purple-700 transition';
                btnHistory.className = 'py-2.5 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition';
                contentForm.classList.remove('hidden');
                contentHistory.classList.add('hidden');
            } else {
                btnHistory.className = 'py-2.5 px-3 text-xs font-bold border-b-2 border-purple-600 text-purple-700 transition';
                btnForm.className = 'py-2.5 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition';
                contentHistory.classList.remove('hidden');
                contentForm.classList.add('hidden');
            }
        }

        function toggleMultiDay(checkbox) {
            const wrapper = document.getElementById('end-date-wrapper');
            if (checkbox.checked) {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
                document.getElementById('perm-end-date').value = document.getElementById('perm-date').value;
            }
        }

        function setQuickReason(type, notes) {
            document.getElementById('perm-type').value = type;
            document.getElementById('perm-notes').value = notes;
            playChimeSound('success');
        }

        function previewAttachment(input) {
            const placeholder = document.getElementById('attachment-placeholder');
            const preview = document.getElementById('attachment-preview');
            const filenameEl = document.getElementById('attachment-filename');

            if (input.files && input.files[0]) {
                placeholder.classList.add('hidden');
                preview.classList.remove('hidden');
                filenameEl.innerText = input.files[0].name;
            } else {
                placeholder.classList.remove('hidden');
                preview.classList.add('hidden');
                filenameEl.innerText = '';
            }
        }

        async function submitPermissionForm(event) {
            event.preventDefault();
            const form = event.target;
            const btn = document.getElementById('btn-submit-perm');
            const btnText = document.getElementById('btn-submit-perm-text');

            btn.disabled = true;
            btnText.innerText = 'Mengirimkan...';

            const formData = new FormData(form);

            try {
                const res = await fetch("{{ route('parent.permission.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();

                if (res.ok) {
                    playChimeSound('success');
                    closePermissionModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Permohonan Berhasil!',
                        text: data.message || 'Keterangan izin resmi disetujui dan disinkronkan ke Wali Kelas.',
                        confirmButtonColor: '#9333ea'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Gagal mengirimkan permohonan.');
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: err.message,
                    confirmButtonColor: '#9333ea'
                });
            } finally {
                btn.disabled = false;
                btnText.innerText = 'Kirimkan Keterangan';
            }
        }

        async function cancelCurrentPermission(id) {
            const result = await Swal.fire({
                title: 'Batalkan Keterangan Izin?',
                text: 'Keterangan izin/sakit ananda akan dihapus dari sistem presensi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Kembali'
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`/orang-tua/izin/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();

                if (res.ok) {
                    playChimeSound('success');
                    Swal.fire({
                        icon: 'success',
                        title: 'Dibatalkan',
                        text: data.message,
                        confirmButtonColor: '#2563eb'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Gagal membatalkan izin.');
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: err.message,
                    confirmButtonColor: '#2563eb'
                });
            }
        }

        // ==========================================
        // 3. WHATSAPP REPORT SHARING
        // ==========================================
        function shareToWhatsApp() {
            const timeIn = document.getElementById('time-kedatangan').innerText.trim();
            const timeOut = document.getElementById('time-kepulangan').innerText.trim();
            const badgeIn = document.getElementById('badge-kedatangan').innerText.trim();
            const badgeOut = document.getElementById('badge-kepulangan').innerText.trim();
            const duration = document.getElementById('study-duration').innerText.trim();

            const text = `*Laporan Presensi Ananda - ${SCHOOL_NAME}*\n\n` +
                `👤 *Nama:* ${STUDENT_NAME}\n` +
                `🏫 *Kelas:* ${STUDENT_CLASS}\n` +
                `📅 *Tanggal:* {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}\n\n` +
                `🚪 *Presensi Masuk:* ${timeIn} (${badgeIn})\n` +
                `🚶 *Presensi Pulang:* ${timeOut} (${badgeOut})\n` +
                `⏱️ *Durasi:* ${duration}\n\n` +
                `_Status resmi tercatat melalui Sistem Fingerprint Biometrik Sekolah._`;

            const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
        }

        // ==========================================
        // 4. WEB PUSH NOTIFICATION & SOUND TEST
        // ==========================================
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(console.error);
        }

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

            const btnText = document.getElementById('push-btn-text');
            btnText.innerText = 'Mengaktifkan..';

            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    throw new Error('Izin notifikasi ditolak oleh peramban ini.');
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

                if (res.ok) {
                    btnText.innerText = 'Notifikasi Aktif';
                    playChimeSound('checkin');
                    Swal.fire({
                        icon: 'success',
                        title: 'Notifikasi Aktif!',
                        text: 'Layar HP Anda akan menerima notifikasi otomatis begitu ananda tap presensi masuk atau pulang.',
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    throw new Error('Gagal menyimpan langganan notifikasi.');
                }
            } catch (err) {
                btnText.innerText = 'Notifikasi di HP';
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: err.message,
                    confirmButtonColor: '#2563eb'
                });
            }
        }

        async function testSoundAndNotification() {
            playChimeSound('checkin');

            // Cek permission browser notification lokal
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    new Notification(`Presensi Masuk: ${STUDENT_NAME}`, {
                        body: `Ananda ${STUDENT_NAME} terdeteksi hadir tepat waktu di sekolah.`,
                        icon: '/favicon.ico'
                    });
                } else if (Notification.permission !== 'denied') {
                    Notification.requestPermission();
                }
            }

            // Trigger server test push juga
            try {
                await fetch("{{ route('parent.push.test') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });
            } catch (e) {}

            Swal.fire({
                icon: 'success',
                title: 'Tes Bunyi & Notifikasi Berhasil!',
                text: 'Bunyi lonceng dan notifikasi layar telah diuji coba.',
                confirmButtonColor: '#2563eb'
            });
        }

        // ==========================================
        // 5. LIVE POLLING REAL-TIME SYNCHRONIZER
        // ==========================================
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
                        if (streakEl) streakEl.innerText = data.student.streak + ' Hari Berturut';
                    }

                    // Deteksi Checkin Baru
                    if (data.attendance && data.attendance.checkin) {
                        const checkinData = data.attendance.checkin;
                        const timeEl = document.getElementById('time-kedatangan');
                        const cardEl = document.getElementById('card-kedatangan');

                        if (timeEl && timeEl.innerText.trim() === '-') {
                            // Event Baru Masuk!
                            timeEl.innerText = checkinData.time_formatted;
                            const badge = document.getElementById('badge-kedatangan');
                            if (badge) {
                                badge.innerText = checkinData.status_label;
                                badge.className = 'inline-block px-2 py-0.5 rounded-md text-[11px] font-bold border ' + 
                                    (checkinData.is_ontime ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200');
                            }

                            if (cardEl) cardEl.classList.add('highlight-card-update');

                            // Bunyikan bel masuk & browser notification
                            playChimeSound('checkin');
                            if ('Notification' in window && Notification.permission === 'granted') {
                                new Notification(checkinData.notification_title, {
                                    body: checkinData.notification_body,
                                    icon: '/favicon.ico'
                                });
                            }

                            // Stepper update
                            const step1 = document.getElementById('step-1-circle');
                            if (step1) {
                                step1.className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs bg-emerald-600 text-white';
                                step1.innerHTML = '<i class="fas fa-check text-[10px]"></i>';
                            }
                            const step1Sub = document.getElementById('step-1-sub');
                            if (step1Sub) step1Sub.innerText = checkinData.time_formatted;

                            const step2 = document.getElementById('step-2-circle');
                            if (step2) {
                                step2.className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs bg-blue-600 text-white ring-4 ring-blue-100';
                                step2.innerHTML = '<i class="fas fa-book-open text-[10px] animate-pulse"></i>';
                            }
                            const step2Sub = document.getElementById('step-2-sub');
                            if (step2Sub) step2Sub.innerText = 'Sedang Belajar';

                            const bar = document.getElementById('stepper-progress-bar');
                            if (bar) bar.style.width = '50%';

                            Swal.fire({
                                icon: 'success',
                                title: checkinData.notification_title,
                                text: checkinData.notification_body,
                                confirmButtonColor: '#2563eb'
                            });
                        }
                    }

                    // Deteksi Checkout Baru
                    if (data.attendance && data.attendance.checkout) {
                        const checkoutData = data.attendance.checkout;
                        const timeOutEl = document.getElementById('time-kepulangan');
                        const cardOutEl = document.getElementById('card-kepulangan');

                        if (timeOutEl && (timeOutEl.innerText.trim() === '-' || timeOutEl.innerText.includes('KBM') || timeOutEl.innerText.includes('Belum'))) {
                            // Event Baru Pulang!
                            timeOutEl.innerText = checkoutData.time_formatted;
                            const badge = document.getElementById('badge-kepulangan');
                            if (badge) {
                                badge.innerText = checkoutData.status_label;
                                badge.className = 'inline-block px-2 py-0.5 rounded-md text-[11px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200';
                            }

                            const durationEl = document.getElementById('study-duration');
                            if (durationEl && data.attendance.study_duration) {
                                durationEl.innerText = data.attendance.study_duration;
                            }

                            if (cardOutEl) cardOutEl.classList.add('highlight-card-update');

                            // Bunyikan bel pulang & browser notification
                            playChimeSound('checkout');
                            if ('Notification' in window && Notification.permission === 'granted') {
                                new Notification(checkoutData.notification_title, {
                                    body: checkoutData.notification_body,
                                    icon: '/favicon.ico'
                                });
                            }

                            // Stepper update
                            const step2 = document.getElementById('step-2-circle');
                            if (step2) {
                                step2.className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs bg-emerald-600 text-white';
                                step2.innerHTML = '<i class="fas fa-check text-[10px]"></i>';
                            }
                            const step2Sub = document.getElementById('step-2-sub');
                            if (step2Sub) step2Sub.innerText = 'Selesai';

                            const step3 = document.getElementById('step-3-circle');
                            if (step3) {
                                step3.className = 'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition shadow-xs bg-emerald-600 text-white';
                                step3.innerHTML = '<i class="fas fa-check text-[10px]"></i>';
                            }
                            const step3Sub = document.getElementById('step-3-sub');
                            if (step3Sub) step3Sub.innerText = checkoutData.time_formatted;

                            const bar = document.getElementById('stepper-progress-bar');
                            if (bar) bar.style.width = '100%';

                            Swal.fire({
                                icon: 'info',
                                title: checkoutData.notification_title,
                                text: checkoutData.notification_body,
                                confirmButtonColor: '#2563eb'
                            });
                        }
                    }
                }
            } catch (e) {
                // Background polling fetch error ignored
            }
        }, 15000);
    </script>
</body>
</html>
