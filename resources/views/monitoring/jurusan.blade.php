@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-violet-800 via-indigo-700 to-blue-700 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold uppercase tracking-wider text-indigo-100 mb-2 border border-white/20">
                    <i class="fas fa-layer-group text-yellow-300"></i>
                    Monitoring Program Keahlian / Jurusan
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Monitoring: <span class="text-yellow-300">{{ $currentMajor ? $currentMajor->name : 'Pilih Jurusan' }}</span>
                </h1>
                <p class="text-indigo-100 mt-1 text-sm sm:text-base max-w-2xl">
                    Pantau akumulasi kehadiran seluruh kelas di jurusan, bandingkan tingkat kedisiplinan antar-kelas, dan kirim pengingat WhatsApp ke orang tua murid.
                </p>
                <div class="flex flex-wrap items-center gap-3 mt-3 text-xs sm:text-sm text-indigo-200">
                    <span class="inline-flex items-center gap-1.5"><i class="far fa-calendar-alt text-yellow-300"></i> Tanggal: <strong>{{ $majorStats['date_formatted'] }}</strong></span>
                    <span>&bull;</span>
                    <span><i class="far fa-clock mr-1"></i> Batas Masuk: <strong>{{ $majorStats['checkin_end'] }} WIB</strong></span>
                    @if($majorStats['is_holiday'])
                    <span class="bg-amber-400/25 border border-amber-300/40 text-amber-200 font-bold px-2.5 py-0.5 rounded-full text-xs inline-flex items-center gap-1">
                        <i class="fas fa-umbrella-beach"></i> Libur / Akhir Pekan
                    </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <!-- Filter Form: Jurusan & Tanggal (Date Picker) -->
                <form action="{{ route('monitoring.jurusan') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    @if($allMajors->count() > 1)
                    <select name="major_id" onchange="this.form.submit()" class="bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                        @foreach($allMajors as $m)
                            <option value="{{ $m->id }}" class="text-gray-900" {{ ($currentMajor && $currentMajor->id === $m->id) ? 'selected' : '' }}>
                                Jurusan: {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Date Picker (Bisa Pilih Tanggal) -->
                    <div class="flex items-center bg-white/20 hover:bg-white/30 border border-white/30 rounded-xl px-2.5 py-1.5 text-white transition">
                        <i class="far fa-calendar-alt text-xs mr-2 text-indigo-200"></i>
                        <input 
                            type="date" 
                            name="date" 
                            value="{{ $majorStats['date'] }}" 
                            onchange="this.form.submit()" 
                            title="Pilih tanggal presensi"
                            class="bg-transparent text-white text-xs font-semibold focus:outline-none cursor-pointer [color-scheme:dark]"
                        >
                    </div>

                    @if(!$majorStats['is_today'])
                    <a href="{{ route('monitoring.jurusan', array_filter(['major_id' => $currentMajor?->id])) }}" class="text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-2 rounded-xl font-bold transition flex items-center gap-1 border border-white/30" title="Kembali ke Hari Ini">
                        <i class="fas fa-undo-alt text-[10px]"></i> Hari Ini
                    </a>
                    @endif
                </form>

                @if($currentMajor)
                    @if($majorStats['is_holiday'])
                    <!-- Tombol Disabled Jika Akhir Pekan / Libur -->
                    <button type="button" disabled class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/10 text-white/60 font-semibold px-4 py-2.5 rounded-xl border border-white/20 text-sm cursor-not-allowed" title="Hari ini libur akhir pekan, pengingat WhatsApp dinonaktifkan">
                        <i class="fab fa-whatsapp text-lg opacity-40"></i>
                        <span>Pengingat WA Nonaktif (Libur)</span>
                    </button>
                    @elseif($majorStats['absent_count'] > 0)
                    <!-- Tombol 1-Klik Ingatkan Semua Ortu di Jurusan -->
                    <form action="{{ route('monitoring.jurusan.reminder_all', $currentMajor->id) }}" method="POST" data-confirm="Kirim WhatsApp pengingat otomatis ke SEMUA orang tua siswa jurusan {{ $currentMajor->name }} yang belum hadir hari ini ({{ $majorStats['absent_count'] }} siswa)?" data-title="Kirim Pengingat Seluruh Jurusan" data-danger="false" data-icon="question">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-bold px-4 py-2.5 rounded-xl shadow-lg hover:shadow-emerald-500/30 transition border border-emerald-400/40 text-sm">
                            <i class="fab fa-whatsapp text-lg"></i>
                            <span>Ingatkan Se-Jurusan ({{ $majorStats['absent_count'] }})</span>
                        </button>
                    </form>
                    @endif
                @endif

                <a href="{{ route('guru.dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 text-white font-semibold px-4 py-2.5 rounded-xl backdrop-blur transition border border-white/30 text-sm">
                    <i class="fas fa-chalkboard-teacher text-yellow-300"></i>
                    <span>Monitoring Per-Kelas</span>
                </a>
            </div>
        </div>
    </div>

    @if($majorStats['is_holiday'])
    <!-- Banner Pemberitahuan Mode Libur / Akhir Pekan -->
    <div class="bg-gradient-to-r from-slate-50 to-indigo-50/70 border border-indigo-200/80 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
        <div class="flex items-start sm:items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl shrink-0 shadow-sm border border-indigo-200">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div>
                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    Mode Akhir Pekan / Hari Libur
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">Aktif</span>
                </h4>
                <p class="text-xs text-gray-600 mt-0.5 leading-relaxed">
                    Tanggal <strong>{{ $majorStats['date_formatted'] }}</strong> terdeteksi sebagai hari libur / akhir pekan. Status siswa yang tidak hadir di jurusan ini tercatat sebagai <span class="font-bold text-indigo-700">Libur Akhir Pekan</span> (bukan bolos/alpha). Tombol pengingat WhatsApp dinonaktifkan demi kenyamanan orang tua murid.
                </p>
            </div>
        </div>
        <div class="shrink-0 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 bg-white text-indigo-700 border border-indigo-200 text-xs font-bold px-3 py-1.5 rounded-xl shadow-xs">
                <i class="fas fa-shield-alt text-indigo-500"></i> Proteksi WA Aktif
            </span>
        </div>
    </div>
    @endif

    @if(!$currentMajor)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center text-amber-800">
        <i class="fas fa-exclamation-circle text-3xl mb-2 text-amber-500"></i>
        <h3 class="font-bold text-lg">Belum Ada Data Jurusan</h3>
        <p class="text-sm mt-1 text-amber-700">Silakan tambahkan data Jurusan pada menu Master Data Jurusan terlebih dahulu.</p>
    </div>
    @else

    <!-- 5 Stat Summary Cards Jurusan -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Siswa Jurusan -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Siswa</p>
                <h3 class="text-2xl font-black text-gray-800">{{ $majorStats['total_students'] }}</h3>
            </div>
        </div>

        <!-- Tepat Waktu -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tepat Waktu</p>
                <h3 class="text-2xl font-black text-emerald-600">{{ $majorStats['ontime_count'] }}</h3>
            </div>
        </div>

        <!-- Terlambat -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Terlambat</p>
                <h3 class="text-2xl font-black text-amber-600">{{ $majorStats['late_count'] }}</h3>
            </div>
        </div>

        <!-- Belum Hadir / Libur Akhir Pekan -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl {{ $majorStats['is_holiday'] ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center text-xl shrink-0">
                <i class="fas {{ $majorStats['is_holiday'] ? 'fa-umbrella-beach' : 'fa-user-times' }}"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $majorStats['is_holiday'] ? 'Libur Akhir Pekan' : 'Belum Hadir' }}</p>
                <h3 class="text-2xl font-black {{ $majorStats['is_holiday'] ? 'text-slate-700' : 'text-rose-600' }}">{{ $majorStats['is_holiday'] ? $majorStats['holiday_count'] : $majorStats['absent_count'] }}</h3>
            </div>
        </div>

        <!-- Persentase Kehadiran Jurusan -->
        <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-violet-50 to-indigo-50 rounded-2xl p-4 sm:p-5 border border-violet-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-violet-600 text-white flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-violet-700 uppercase tracking-wider">Kehadiran Jurusan</p>
                <h3 class="text-2xl font-black text-violet-900">{{ $majorStats['percentage'] }}%</h3>
            </div>
        </div>
    </div>

    <!-- Section Komparasi & Ranking Kehadiran Antar-Kelas dalam Jurusan -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i>
                    Perbandingan & Peringkat Kehadiran Antar-Kelas
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Urutan kelas di jurusan {{ $currentMajor->name }} berdasarkan persentase kehadiran tertinggi ({{ $majorStats['date_formatted'] }}).</p>
            </div>
            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-3 py-1.5 rounded-lg">
                {{ $classBreakdown->count() }} Kelas Terdaftar
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @forelse($classBreakdown as $rank => $cb)
            @php
                $rankColor = match($rank) {
                    0 => 'bg-amber-400 text-gray-950 border-amber-300',
                    1 => 'bg-slate-300 text-slate-800 border-slate-200',
                    2 => 'bg-amber-700 text-amber-100 border-amber-600',
                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                };
                $barColor = $cb['percentage'] >= 90 ? 'bg-emerald-500' : ($cb['percentage'] >= 75 ? 'bg-blue-500' : ($cb['percentage'] >= 50 ? 'bg-amber-500' : 'bg-rose-500'));
            @endphp
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-100 p-4.5 shadow-sm hover:shadow-md transition flex flex-col justify-between relative overflow-hidden">
                <!-- Top Rank Badge -->
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black border {{ $rankColor }}">
                            #{{ $rank + 1 }} {{ $rank === 0 ? '🏆 Tertinggi' : '' }}
                        </span>
                        <h3 class="text-lg font-extrabold text-gray-900 mt-1.5">{{ $cb['name'] }}</h3>
                        <p class="text-xs text-gray-500"><i class="fas fa-user-tie text-gray-400 mr-1"></i> {{ $cb['wali_name'] }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-black {{ $cb['percentage'] >= 80 ? 'text-emerald-600' : 'text-gray-800' }}">
                            {{ $cb['percentage'] }}%
                        </span>
                        <div class="text-[10px] text-gray-400 uppercase font-semibold">Hadir</div>
                    </div>
                </div>

                <!-- Progress Bar Kehadiran Kelas -->
                <div class="space-y-1.5 mb-3.5">
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full {{ $barColor }} rounded-full transition-all duration-500" style="width: {{ $cb['percentage'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-gray-500">
                        <span>Hadir: <strong class="text-emerald-600">{{ $cb['present'] }}</strong> / {{ $cb['total'] }}</span>
                        @if($majorStats['is_holiday'])
                        <span>Libur: <strong class="text-slate-600">{{ $cb['holiday'] }}</strong></span>
                        @else
                        <span>Alpha: <strong class="text-rose-600">{{ $cb['absent'] }}</strong></span>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons Per Kelas -->
                <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                    <a href="{{ route('guru.dashboard', ['class_id' => $cb['id'], 'date' => $majorStats['date']]) }}" class="flex-1 text-center py-1.5 px-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition">
                        Detail Kelas
                    </a>
                    @if($majorStats['is_holiday'])
                    <button type="button" disabled class="py-1.5 px-2.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold cursor-not-allowed border border-gray-200" title="Hari ini libur akhir pekan, pengingat WhatsApp dinonaktifkan">
                        <i class="fab fa-whatsapp opacity-40"></i> Libur
                    </button>
                    @elseif($cb['absent'] > 0)
                    <form action="{{ route('monitoring.jurusan.class_reminder_all', [$currentMajor->id, $cb['id']]) }}" method="POST" data-confirm="Kirim WhatsApp pengingat ke orang tua {{ $cb['absent'] }} siswa kelas {{ $cb['name'] }} yang belum hadir hari ini?" data-title="Kirim Pengingat Kelas" data-danger="false" data-icon="question">
                        @csrf
                        <button type="submit" class="py-1.5 px-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition shadow-sm" title="Kirim WA ke ortu siswa belum hadir di kelas ini">
                            <i class="fab fa-whatsapp"></i> WA ({{ $cb['absent'] }})
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-span-4 py-8 text-center text-gray-400">
                Belum ada kelas yang terdaftar pada jurusan ini.
            </div>
            @endforelse
        </div>
    </div>

    <!-- Filter & Search Bar untuk Siswa Jurusan -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-72">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="major-student-search" placeholder="Cari nama siswa di jurusan..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Filter Kelas -->
            <select id="class-filter" class="border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                <option value="all">Semua Kelas</option>
                @foreach($currentMajor->schoolClasses as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
            <button type="button" class="major-filter-btn active px-3.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white transition" data-status="all">
                Semua ({{ $majorStats['total_students'] }})
            </button>
            @if($majorStats['is_holiday'])
            <button type="button" class="major-filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition" data-status="holiday">
                <i class="fas fa-umbrella-beach text-indigo-500 mr-1"></i> Libur ({{ $majorStats['holiday_count'] }})
            </button>
            @else
            <button type="button" class="major-filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-status="absent">
                Belum Hadir ({{ $majorStats['absent_count'] }})
            </button>
            @endif
            <button type="button" class="major-filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-status="ontime">
                Tepat Waktu ({{ $majorStats['ontime_count'] }})
            </button>
            <button type="button" class="major-filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-status="late">
                Terlambat ({{ $majorStats['late_count'] }})
            </button>
        </div>
    </div>

    <!-- Tabel Daftar Siswa Se-Jurusan -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="major-students-table">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Siswa</th>
                        <th class="py-3.5 px-4">Kelas</th>
                        <th class="py-3.5 px-4 text-center">Status Kehadiran</th>
                        <th class="py-3.5 px-4 text-center">Waktu Presensi</th>
                        <th class="py-3.5 px-4">Kontak Orang Tua</th>
                        <th class="py-3.5 px-4 text-right">Aksi Notifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($studentsList as $idx => $s)
                    <tr class="major-student-row hover:bg-gray-50/80 transition" data-class="{{ $s['school_class_id'] }}" data-status="{{ $s['status'] }}" data-name="{{ strtolower($s['name']) }}">
                        <td class="py-3.5 px-4 text-center font-semibold text-gray-400">
                            {{ $idx + 1 }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                @if($s['photo'])
                                <img src="{{ $s['photo'] }}" alt="{{ $s['name'] }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 shrink-0">
                                @else
                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 font-black flex items-center justify-center shrink-0 text-sm">
                                    {{ strtoupper(substr($s['name'], 0, 1)) }}
                                </div>
                                @endif
                                <div>
                                    <div class="font-bold text-gray-900">{{ $s['name'] }}</div>
                                    <div class="text-xs text-gray-500">PIN: {{ $s['device_user_id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-lg bg-gray-100 text-gray-800">
                                <i class="fas fa-chalkboard text-gray-400 text-xs"></i>
                                {{ $s['class_name'] }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($s['status'] === 'ontime')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                <i class="fas fa-check-circle"></i> Tepat Waktu
                            </span>
                            @elseif($s['status'] === 'late')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                <i class="fas fa-clock"></i> Terlambat
                            </span>
                            @elseif($s['status'] === 'holiday')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                <i class="fas fa-umbrella-beach text-indigo-500"></i> Libur Akhir Pekan
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                <i class="fas fa-times-circle"></i> Belum Hadir
                            </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center font-mono font-semibold {{ in_array($s['status'], ['absent', 'holiday']) ? 'text-gray-400' : 'text-gray-700' }}">
                            {{ $s['tap_time'] }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($s['parent_phone'])
                            <div class="flex items-center gap-1.5 font-mono text-xs text-gray-700">
                                <i class="fab fa-whatsapp text-emerald-500 text-sm"></i>
                                <span>{{ $s['parent_phone'] }}</span>
                            </div>
                            @else
                            <span class="text-xs text-gray-400 italic">Belum didaftarkan</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            @if($s['status'] === 'holiday')
                                <button type="button" disabled class="inline-flex items-center gap-1.5 bg-gray-50 text-gray-400 border border-gray-200 px-3 py-1.5 rounded-lg text-xs font-medium cursor-not-allowed" title="Hari ini libur akhir pekan, pengingat WhatsApp dinonaktifkan">
                                    <i class="fab fa-whatsapp opacity-40"></i>
                                    <span>Libur Akhir Pekan</span>
                                </button>
                            @elseif($s['status'] === 'absent')
                                @if($s['parent_phone'])
                                <form action="{{ route('guru.reminder', $s['id']) }}" method="POST" class="inline-block" data-confirm="Kirim WhatsApp pengingat kehadiran sekarang ke orang tua {{ $s['name'] }} ({{ $s['parent_phone'] }})?" data-title="Kirim Pengingat WhatsApp" data-danger="false" data-icon="question">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition active:scale-95">
                                        <i class="fab fa-whatsapp"></i>
                                        <span>Kirim Pengingat</span>
                                    </button>
                                </form>
                                @else
                                <button type="button" disabled class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 px-3 py-1.5 rounded-lg text-xs font-medium cursor-not-allowed">
                                    <i class="fab fa-whatsapp"></i> Tanpa No WA
                                </button>
                                @endif
                            @else
                            <span class="text-xs text-emerald-600 font-semibold inline-flex items-center gap-1">
                                <i class="fas fa-check"></i> Sudah Hadir
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">
                            Belum ada siswa yang terdaftar di jurusan ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
    // Live Search & Multi-filter Siswa di Tingkat Jurusan
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('major-student-search');
        const classFilter = document.getElementById('class-filter');
        const statusBtns = document.querySelectorAll('.major-filter-btn');
        const rows = document.querySelectorAll('.major-student-row');

        let currentStatus = 'all';

        function applyFilters() {
            const query = (searchInput?.value || '').toLowerCase().trim();
            const selectedClass = classFilter?.value || 'all';

            rows.forEach(row => {
                const name = row.dataset.name || '';
                const status = row.dataset.status || '';
                const classId = row.dataset.class || '';

                const matchesQuery = name.includes(query);
                const matchesClass = selectedClass === 'all' || classId === selectedClass;
                const matchesStatus = currentStatus === 'all' || status === currentStatus;

                if (matchesQuery && matchesClass && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput?.addEventListener('input', applyFilters);
        classFilter?.addEventListener('change', applyFilters);

        statusBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                statusBtns.forEach(b => {
                    b.classList.remove('bg-indigo-600', 'text-white', 'active');
                    b.classList.add('bg-gray-100', 'text-gray-700');
                });
                btn.classList.add('bg-indigo-600', 'text-white', 'active');
                btn.classList.remove('bg-gray-100', 'text-gray-700');

                currentStatus = btn.dataset.status || 'all';
                applyFilters();
            });
        });
    });
</script>
@endsection
