@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- Header Hero Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 rounded-3xl p-6 md:p-8 text-white shadow-xl mb-6 relative overflow-hidden border border-white/10">
        <div class="absolute -right-16 -top-16 w-72 h-72 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/30 border border-indigo-400/40 text-indigo-200 text-xs font-semibold mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span id="realtime-badge">Realtime Monitoring Aktif</span>
                    <span class="text-indigo-300">&bull;</span>
                    <span id="clock-wib" class="font-mono">{{ $stats['current_time'] }} WIB</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                    <i class="fas fa-door-open text-amber-400"></i> Monitoring Kepulangan Siswa
                </h1>
                <p class="text-blue-200 text-sm md:text-base mt-2 max-w-2xl leading-relaxed">
                    Pantau keberadaan siswa secara langsung. Siswa yang telah tap absen pagi akan berstatus 
                    <strong class="text-emerald-300">MASUK (DI SEKOLAH)</strong> sampai mereka menempelkan sidik jari pulang untuk berganti menjadi <strong class="text-sky-300">SUDAH PULANG</strong>.
                </p>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" id="btn-toggle-sound" class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-xs border border-white/20 flex items-center gap-2 transition" title="Aktifkan Suara Notifikasi">
                    <i class="fas fa-volume-up text-amber-300" id="sound-icon"></i>
                    <span id="sound-text">Suara: Aktif</span>
                </button>

                <button type="button" id="btn-kiosk-mode" class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-xs border border-white/20 flex items-center gap-2 transition" title="Tampilan Layar Penuh">
                    <i class="fas fa-expand text-sky-300"></i>
                    <span>Layar Penuh</span>
                </button>

                <a href="{{ route('attendance.simulate') }}" target="_blank" class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs shadow-lg shadow-emerald-500/25 flex items-center gap-2 transition transform hover:-translate-y-0.5" title="Buka Simulator untuk Uji Coba Absen">
                    <i class="fas fa-flask"></i> Uji Coba Tap
                </a>

                <button type="button" id="btn-manual-refresh" class="px-3.5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs shadow-md shadow-indigo-600/30 flex items-center gap-1.5 transition">
                    <i class="fas fa-sync-alt" id="refresh-icon"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Live Status Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Masuk (Sedang di Sekolah - HOLD STATE) -->
        <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/5 p-5 rounded-2xl border-2 border-emerald-500/30 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-ping"></span> HOLD STATUS
                </span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-sm shadow-sm">
                    <i class="fas fa-school"></i>
                </div>
            </div>
            <div class="text-3xl font-black text-emerald-600 tracking-tight" id="stat-in-school">{{ $stats['in_school'] }}</div>
            <div class="text-xs font-bold text-gray-700 mt-0.5">Masuk / Di Sekolah</div>
            <div class="text-[11px] text-gray-500 mt-1">Siswa belum tap sidik jari pulang</div>
        </div>

        <!-- Card 2: Sudah Pulang -->
        <div class="bg-gradient-to-br from-indigo-500/10 to-blue-500/5 p-5 rounded-2xl border border-indigo-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-bold">
                    <i class="fas fa-check-double text-[9px]"></i> SELESAI
                </span>
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm shadow-sm">
                    <i class="fas fa-walking"></i>
                </div>
            </div>
            <div class="text-3xl font-black text-indigo-600 tracking-tight" id="stat-departed">{{ $stats['departed'] }}</div>
            <div class="text-xs font-bold text-gray-700 mt-0.5">Sudah Pulang</div>
            <div class="text-[11px] text-gray-500 mt-1" id="stat-percent-text">{{ $stats['departed_percentage'] }}% dari yang hadir hari ini</div>
        </div>

        <!-- Card 3: Total Hadir Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Hadir</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
            <div class="text-3xl font-black text-gray-800 tracking-tight" id="stat-attended">{{ $stats['attended_today'] }}</div>
            <div class="text-xs font-semibold text-gray-600 mt-0.5">Siswa Hadir Hari Ini</div>
            <div class="text-[11px] text-gray-400 mt-1">Total siswa yang sudah tap masuk</div>
        </div>

        <!-- Card 4: Belum Hadir -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Tidak / Belum Ada</span>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center text-sm">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>
            <div class="text-3xl font-black text-slate-600 tracking-tight" id="stat-not-present">{{ $stats['not_present'] }}</div>
            <div class="text-xs font-semibold text-gray-600 mt-0.5">Belum Hadir</div>
            <div class="text-[11px] text-gray-400 mt-1">Dari total {{ $stats['total'] }} siswa terdaftar</div>
        </div>
    </div>

    <!-- Progress Bar Kepulangan -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm mb-6 flex flex-col md:flex-row items-center gap-4 justify-between">
        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg flex-shrink-0">
                <i class="far fa-clock"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-900">Jadwal Pulang Sekolah: Mulai pukul <span class="font-mono text-indigo-600">{{ $stats['checkout_start'] }} WIB</span></div>
                <div class="text-[11px] text-gray-500">Siswa yang tap fingerprint setelah jam ini otomatis tercatat sebagai absen pulang</div>
            </div>
        </div>

        <div class="w-full md:w-1/3">
            <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-gray-600">Progres Kepulangan</span>
                <span class="text-indigo-600 font-bold" id="progress-percent-label">{{ $stats['departed_percentage'] }}%</span>
            </div>
            <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-indigo-600 rounded-full transition-all duration-500" id="progress-bar-fill" style="width: {{ $stats['departed_percentage'] }}%;"></div>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar & Search -->
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm mb-6">
        <form method="GET" action="{{ route('monitoring.kepulangan') }}" id="filter-form" class="space-y-4">
            <!-- Filter Tabs Status -->
            <div class="flex flex-wrap items-center gap-2 pb-3 border-b border-gray-100">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-1">Filter Status:</span>
                
                <a href="{{ route('monitoring.kepulangan', array_merge(request()->query(), ['status' => 'all'])) }}" class="filter-tab-btn px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 {{ $selectedStatus === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    <i class="fas fa-users"></i> Semua Siswa
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedStatus === 'all' ? 'bg-white/20' : 'bg-gray-200' }}">{{ $stats['total'] }}</span>
                </a>

                <a href="{{ route('monitoring.kepulangan', array_merge(request()->query(), ['status' => 'in_school'])) }}" class="filter-tab-btn px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 {{ $selectedStatus === 'in_school' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Masuk / Di Sekolah
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedStatus === 'in_school' ? 'bg-white/20' : 'bg-emerald-200/60' }}">{{ $stats['in_school'] }}</span>
                </a>

                <a href="{{ route('monitoring.kepulangan', array_merge(request()->query(), ['status' => 'departed'])) }}" class="filter-tab-btn px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 {{ $selectedStatus === 'departed' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' }}">
                    <i class="fas fa-walking"></i> Sudah Pulang
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedStatus === 'departed' ? 'bg-white/20' : 'bg-indigo-200/60' }}">{{ $stats['departed'] }}</span>
                </a>

                <a href="{{ route('monitoring.kepulangan', array_merge(request()->query(), ['status' => 'not_present'])) }}" class="filter-tab-btn px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 {{ $selectedStatus === 'not_present' ? 'bg-slate-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <i class="far fa-circle"></i> Belum Hadir
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedStatus === 'not_present' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $stats['not_present'] }}</span>
                </a>
            </div>

            <!-- Dropdown Filters & Search Bar -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-center">
                <input type="hidden" name="status" id="input-status" value="{{ $selectedStatus }}">

                <!-- Filter Jurusan -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Jurusan</label>
                    <select name="major_id" id="filter-major" onchange="document.getElementById('filter-form').submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-indigo-500 bg-gray-50/50">
                        <option value="">Semua Jurusan</option>
                        @foreach($allMajors as $major)
                            <option value="{{ $major->id }}" {{ (string)$selectedMajorId === (string)$major->id ? 'selected' : '' }}>
                                {{ $major->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Kelas</label>
                    <select name="class_id" id="filter-class" onchange="document.getElementById('filter-form').submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-indigo-500 bg-gray-50/50">
                        <option value="">Semua Kelas</option>
                        @foreach($allClasses as $cls)
                            <option value="{{ $cls->id }}" {{ (string)$selectedClassId === (string)$cls->id ? 'selected' : '' }}>
                                {{ $cls->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Live Search Bar -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Cari Siswa</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input type="text" name="search" id="live-search" value="{{ $search }}" placeholder="Ketik nama siswa atau PIN/UID..." class="w-full pl-9 pr-8 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-indigo-500 bg-gray-50/50">
                        @if(!empty($search))
                            <a href="{{ route('monitoring.kepulangan', ['status' => $selectedStatus]) }}" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" title="Reset pencarian">
                                <i class="fas fa-times-circle text-xs"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Student Table List -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-8">
        <div class="p-4 md:px-6 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-list-ul text-indigo-600"></i>
                <span>Daftar Siswa (<span id="rendered-count">{{ $filteredStudents->count() }}</span> data)</span>
            </h2>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Auto-Update Realtime</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="students-table">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500 font-bold">
                        <th class="py-3.5 px-4 text-center w-12">#</th>
                        <th class="py-3.5 px-4">Nama Siswa</th>
                        <th class="py-3.5 px-4">Kelas & Jurusan</th>
                        <th class="py-3.5 px-4 text-center">Jam Masuk</th>
                        <th class="py-3.5 px-4 text-center">Jam Pulang</th>
                        <th class="py-3.5 px-4 text-center">Status Kepulangan</th>
                        <th class="py-3.5 px-4 text-center">Durasi di Sekolah</th>
                        <th class="py-3.5 px-4 text-right">Aksi Notifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs" id="students-tbody">
                    @forelse($filteredStudents as $index => $item)
                    <tr class="hover:bg-slate-50/80 transition duration-150 student-row" id="row-student-{{ $item['id'] }}" data-student-id="{{ $item['id'] }}" data-status="{{ $item['status_code'] }}">
                        <!-- Index -->
                        <td class="py-3.5 px-4 text-center font-bold text-gray-400">
                            {{ $loop->iteration }}
                        </td>

                        <!-- Name & Avatar -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm overflow-hidden">
                                    @if(!empty($item['photo']))
                                        <img src="{{ $item['photo'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($item['name'], 0, 1)) }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-gray-900 truncate student-name">{{ $item['name'] }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">PIN: {{ $item['device_user_id'] }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Class & Major -->
                        <td class="py-3.5 px-4">
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-semibold text-[11px]">
                                {{ $item['class_name'] }}
                            </span>
                            <div class="text-[10px] text-gray-400 mt-0.5 truncate max-w-[180px]">{{ $item['major_name'] }}</div>
                        </td>

                        <!-- Jam Masuk -->
                        <td class="py-3.5 px-4 text-center">
                            @if(!empty($item['jam_masuk']))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-mono font-bold text-[11px] border border-emerald-200/50">
                                    <i class="far fa-clock text-[10px]"></i> {{ $item['jam_masuk'] }} WIB
                                </span>
                            @else
                                <span class="text-gray-300 font-mono">-</span>
                            @endif
                        </td>

                        <!-- Jam Pulang -->
                        <td class="py-3.5 px-4 text-center">
                            @if(!empty($item['jam_pulang']))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-mono font-bold text-[11px] border border-indigo-200/50">
                                    <i class="fas fa-walking text-[10px]"></i> {{ $item['jam_pulang'] }} WIB
                                </span>
                            @else
                                <span class="text-gray-300 font-mono">-</span>
                            @endif
                        </td>

                        <!-- Status Badge (HOLD / PULANG / BELUM HADIR) -->
                        <td class="py-3.5 px-4 text-center status-badge-cell">
                            @if($item['status_code'] === 'in_school')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px] border border-emerald-300 shadow-sm animate-pulse">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> MASUK (DI SEKOLAH)
                                </span>
                            @elseif($item['status_code'] === 'departed')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 font-bold text-[11px] border border-indigo-300 shadow-sm">
                                    <i class="fas fa-check-circle text-indigo-600"></i> SUDAH PULANG
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium text-[11px]">
                                    <i class="far fa-circle text-[9px]"></i> Belum Hadir
                                </span>
                            @endif
                        </td>

                        <!-- Durasi di Sekolah -->
                        <td class="py-3.5 px-4 text-center font-mono text-[11px] text-gray-600 duration-cell">
                            {{ $item['duration'] ?? '-' }}
                        </td>

                        <!-- WhatsApp Action -->
                        <td class="py-3.5 px-4 text-right">
                            @if(!empty($item['parent_phone']))
                                <form action="{{ route('monitoring.kepulangan.notify', $item['id']) }}" method="POST" class="inline-block" onsubmit="return confirm('Kirim notifikasi status keberadaan {{ addslashes($item['name']) }} ke WhatsApp orang tua?')">
                                    @csrf
                                    <button type="submit" class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition" title="Kirim Status WA ke Orang Tua ({{ $item['parent_phone'] }})">
                                        <i class="fab fa-whatsapp text-sm"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-300 text-[10px] italic">No WA Ortu</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-gray-400">
                            <div class="w-12 h-12 rounded-full bg-gray-50 text-gray-300 flex items-center justify-center mx-auto text-xl mb-2">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <p class="font-medium text-xs">Tidak ada data siswa yang cocok dengan filter saat ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Audio Chimes using Web Audio API -->
<script>
    let isSoundEnabled = true;
    let maxKnownLogId = {{ $maxLogId ?? 0 }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Play pleasant synthesized chime when status changes
    function playMonitoringTone(type = 'masuk') {
        if (!isSoundEnabled) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            
            const playNote = (freq, start, duration) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
                gain.gain.setValueAtTime(0.001, ctx.currentTime + start);
                gain.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + start + 0.05);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + start + duration);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + start);
                osc.stop(ctx.currentTime + start + duration);
            };

            if (type === 'pulang') {
                // Warm descending chime (A5 -> F#5) for departure / pulang
                playNote(880.00, 0, 0.35);
                playNote(739.99, 0.15, 0.55);
            } else {
                // Cheerful ascending chime (E5 -> A5) for masuk
                playNote(659.25, 0, 0.35);
                playNote(880.00, 0.12, 0.55);
            }
        } catch(e) {}
    }

    // Toggle Sound Button
    const btnToggleSound = document.getElementById('btn-toggle-sound');
    const soundIcon = document.getElementById('sound-icon');
    const soundText = document.getElementById('sound-text');
    btnToggleSound?.addEventListener('click', () => {
        isSoundEnabled = !isSoundEnabled;
        if (isSoundEnabled) {
            soundIcon.className = 'fas fa-volume-up text-amber-300';
            soundText.textContent = 'Suara: Aktif';
            playMonitoringTone('masuk');
        } else {
            soundIcon.className = 'fas fa-volume-mute text-gray-300';
            soundText.textContent = 'Suara: Mute';
        }
    });

    // Kiosk Fullscreen Mode
    const btnKiosk = document.getElementById('btn-kiosk-mode');
    btnKiosk?.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
        } else {
            document.exitFullscreen().catch(() => {});
        }
    });

    // Realtime Polling Engine (Every 2 seconds)
    let isFetching = false;
    async function fetchDepartureData() {
        if (isFetching) return;
        isFetching = true;

        const classId = document.getElementById('filter-class')?.value || '';
        const majorId = document.getElementById('filter-major')?.value || '';
        const search = document.getElementById('live-search')?.value || '';
        const currentFilterStatus = document.getElementById('input-status')?.value || 'all';

        try {
            const url = `/monitoring/kepulangan/data?class_id=${encodeURIComponent(classId)}&major_id=${encodeURIComponent(majorId)}&search=${encodeURIComponent(search)}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.status === 'success') {
                // Update stats counter
                if (data.stats) {
                    const inSchoolEl = document.getElementById('stat-in-school');
                    const departedEl = document.getElementById('stat-departed');
                    const attendedEl = document.getElementById('stat-attended');
                    const notPresentEl = document.getElementById('stat-not-present');
                    const percentTextEl = document.getElementById('stat-percent-text');
                    const barFillEl = document.getElementById('progress-bar-fill');
                    const barLabelEl = document.getElementById('progress-percent-label');

                    if (inSchoolEl) inSchoolEl.textContent = data.stats.in_school;
                    if (departedEl) departedEl.textContent = data.stats.departed;
                    if (attendedEl) attendedEl.textContent = data.stats.attended_today;
                    if (notPresentEl) notPresentEl.textContent = data.stats.not_present;
                    if (percentTextEl) percentTextEl.textContent = `${data.stats.departed_percentage}% dari yang hadir hari ini`;
                    if (barFillEl) barFillEl.style.width = `${data.stats.departed_percentage}%`;
                    if (barLabelEl) barLabelEl.textContent = `${data.stats.departed_percentage}%`;
                }

                // Check if any student status changed
                if (data.students && Array.isArray(data.students)) {
                    data.students.forEach(student => {
                        const row = document.getElementById(`row-student-${student.id}`);
                        if (!row) return;

                        const oldStatus = row.dataset.status;
                        const newStatus = student.status_code;

                        if (oldStatus !== newStatus) {
                            // Status changed!
                            row.dataset.status = newStatus;
                            updateStudentRowHtml(row, student);

                            // Highlight row
                            row.style.transition = 'background-color 0.4s ease';
                            row.style.backgroundColor = newStatus === 'departed' ? '#e0e7ff' : '#d1fae5';
                            setTimeout(() => {
                                row.style.backgroundColor = '';
                            }, 2500);

                            // Play tone and show toast
                            if (newStatus === 'departed') {
                                playMonitoringTone('pulang');
                                showToast(`${student.name} baru saja Absen PULANG pukul ${student.jam_pulang} WIB`, 'info');
                            } else if (newStatus === 'in_school') {
                                playMonitoringTone('masuk');
                                showToast(`${student.name} baru saja Absen MASUK pukul ${student.jam_masuk} WIB`, 'success');
                            }
                        }
                    });
                }
            }
        } catch(e) {
            // Retry silently
        } finally {
            isFetching = false;
        }
    }

    function updateStudentRowHtml(row, student) {
        // Update jam masuk
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            // Cell 3: Jam Masuk
            if (student.jam_masuk) {
                cells[3].innerHTML = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-mono font-bold text-[11px] border border-emerald-200/50"><i class="far fa-clock text-[10px]"></i> ${student.jam_masuk} WIB</span>`;
            } else {
                cells[3].innerHTML = `<span class="text-gray-300 font-mono">-</span>`;
            }

            // Cell 4: Jam Pulang
            if (student.jam_pulang) {
                cells[4].innerHTML = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 font-mono font-bold text-[11px] border border-indigo-200/50"><i class="fas fa-walking text-[10px]"></i> ${student.jam_pulang} WIB</span>`;
            } else {
                cells[4].innerHTML = `<span class="text-gray-300 font-mono">-</span>`;
            }

            // Cell 5: Status Badge
            if (student.status_code === 'in_school') {
                cells[5].innerHTML = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px] border border-emerald-300 shadow-sm animate-pulse"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> MASUK (DI SEKOLAH)</span>`;
            } else if (student.status_code === 'departed') {
                cells[5].innerHTML = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 font-bold text-[11px] border border-indigo-300 shadow-sm"><i class="fas fa-check-circle text-indigo-600"></i> SUDAH PULANG</span>`;
            } else {
                cells[5].innerHTML = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium text-[11px]"><i class="far fa-circle text-[9px]"></i> Belum Hadir</span>`;
            }

            // Cell 6: Duration
            cells[6].textContent = student.duration || '-';
        }
    }

    function showToast(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
            Toast.fire({
                icon: type === 'success' ? 'success' : 'info',
                title: message
            });
        }
    }

    // Manual Refresh button
    document.getElementById('btn-manual-refresh')?.addEventListener('click', async () => {
        const icon = document.getElementById('refresh-icon');
        icon?.classList.add('fa-spin');
        await fetchDepartureData();
        setTimeout(() => icon?.classList.remove('fa-spin'), 600);
    });

    // Client-side quick filter for live search
    document.getElementById('live-search')?.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.student-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.querySelector('.student-name')?.textContent.toLowerCase() || '';
            const uid = row.querySelector('.font-mono')?.textContent.toLowerCase() || '';
            if (name.includes(term) || uid.includes(term)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const countEl = document.getElementById('rendered-count');
        if (countEl) countEl.textContent = visibleCount;
    });

    // Start Realtime Polling Loop every 2.5 seconds
    setInterval(fetchDepartureData, 2500);

    // Live Clock Update in Banner
    setInterval(() => {
        const n = new Date();
        const timePart = new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).format(n);
        const clockEl = document.getElementById('clock-wib');
        if (clockEl) clockEl.textContent = timePart + ' WIB';
    }, 1000);

    // Reverb / Echo WebSockets Listener if available
    document.addEventListener("DOMContentLoaded", () => {
        if (window.Echo) {
            window.Echo.channel('attendance')
                .listen('.AttendanceCreated', (e) => {
                    fetchDepartureData();
                });
        }
    });
</script>
@endsection
