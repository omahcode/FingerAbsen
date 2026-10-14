@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-800 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold uppercase tracking-wider text-blue-100 mb-2 border border-white/20">
                    <i class="fas fa-chalkboard-teacher text-yellow-300"></i>
                    {{ $user->isGuru() ? 'Portal Wali Kelas' : 'Monitoring Kelas (Mode Admin)' }}
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Monitoring Presensi: <span class="text-yellow-300">{{ $currentClass ? $currentClass->name : 'Pilih Kelas' }}</span>
                </h1>
                <p class="text-blue-100 mt-1 text-sm sm:text-base">
                    Pantau kehadiran siswa secara real-time dan kirim pengingat WhatsApp 1-klik ke orang tua murid yang belum hadir.
                </p>
                <div class="flex flex-wrap items-center gap-3 mt-3 text-xs sm:text-sm text-blue-200">
                    <span class="inline-flex items-center gap-1.5"><i class="far fa-calendar-alt text-yellow-300"></i> Tanggal: <strong>{{ $stats['date_formatted'] }}</strong></span>
                    <span>&bull;</span>
                    <span><i class="far fa-clock mr-1"></i> Batas Masuk: <strong>{{ $stats['checkin_end'] }} WIB</strong></span>
                    @if($stats['is_holiday'])
                    <span class="bg-amber-400/25 border border-amber-300/40 text-amber-200 font-bold px-2.5 py-0.5 rounded-full text-xs inline-flex items-center gap-1">
                        <i class="fas fa-umbrella-beach"></i> Libur / Akhir Pekan
                    </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <!-- Filter Form: Kelas & Tanggal (Date Picker) -->
                <form action="{{ route('guru.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    @if($user->isAdmin() && $allClasses->isNotEmpty())
                    <select name="class_id" onchange="this.form.submit()" class="bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                        @foreach($allClasses as $cls)
                            <option value="{{ $cls->id }}" class="text-gray-900" {{ ($currentClass && $currentClass->id === $cls->id) ? 'selected' : '' }}>
                                Kelas: {{ $cls->name }}
                            </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Date Picker (Bisa Pilih Tanggal) -->
                    <div class="flex items-center bg-white/20 hover:bg-white/30 border border-white/30 rounded-xl px-2.5 py-1.5 text-white transition">
                        <i class="far fa-calendar-alt text-xs mr-2 text-blue-200"></i>
                        <input 
                            type="date" 
                            name="date" 
                            value="{{ $stats['date'] }}" 
                            onchange="this.form.submit()" 
                            title="Pilih tanggal presensi"
                            class="bg-transparent text-white text-xs font-semibold focus:outline-none cursor-pointer [color-scheme:dark]"
                        >
                    </div>

                    @if(!$stats['is_today'])
                    <a href="{{ route('guru.dashboard', array_filter(['class_id' => $currentClass?->id])) }}" class="text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-2 rounded-xl font-bold transition flex items-center gap-1 border border-white/30" title="Kembali ke Hari Ini">
                        <i class="fas fa-undo-alt text-[10px]"></i> Hari Ini
                    </a>
                    @endif
                </form>

                @if($currentClass)
                    @if($stats['is_holiday'])
                    <!-- Tombol Disabled Jika Akhir Pekan / Libur -->
                    <button type="button" disabled class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/10 text-white/60 font-semibold px-4 py-2.5 rounded-xl border border-white/20 text-sm cursor-not-allowed" title="Hari ini libur akhir pekan, pengingat WhatsApp dinonaktifkan">
                        <i class="fab fa-whatsapp text-lg opacity-40"></i>
                        <span>Pengingat WA Nonaktif (Libur)</span>
                    </button>
                    @elseif($stats['absent'] > 0)
                    <!-- Tombol 1-Klik Ingatkan Semua Ortu -->
                    <form action="{{ route('guru.reminder_all') }}" method="POST" data-confirm="Kirim pesan WhatsApp pengingat otomatis ke SEMUA orang tua siswa kelas {{ $currentClass->name }} yang belum hadir hari ini?" data-title="Kirim Pengingat Massal WhatsApp" data-danger="false" data-icon="question">
                        @csrf
                        <input type="hidden" name="school_class_id" value="{{ $currentClass->id }}">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-bold px-4 py-2.5 rounded-xl shadow-lg hover:shadow-emerald-500/30 transition border border-emerald-400/40 text-sm">
                            <i class="fab fa-whatsapp text-lg"></i>
                            <span>Ingatkan Semua Ortu ({{ $stats['absent'] }})</span>
                        </button>
                    </form>
                    @endif
                @endif

                <a href="{{ route('announcements.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 text-white font-semibold px-4 py-2.5 rounded-xl backdrop-blur transition border border-white/30 text-sm">
                    <i class="fas fa-bullhorn text-yellow-300"></i>
                    <span>Ajukan Pengumuman TV</span>
                </a>
            </div>
        </div>
    </div>

    @if($stats['is_holiday'])
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
                    Tanggal <strong>{{ $stats['date_formatted'] }}</strong> terdeteksi sebagai hari libur / akhir pekan. Siswa yang tidak hadir diberi status <span class="font-bold text-indigo-700">Libur Akhir Pekan</span> (bukan bolos/alpha). Tombol pengingat WhatsApp dinonaktifkan otomatis agar tidak mengganggu hari istirahat orang tua siswa.
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

    @if(!$currentClass)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center text-amber-800">
        <i class="fas fa-exclamation-circle text-3xl mb-2 text-amber-500"></i>
        <h3 class="font-bold text-lg">Akun Anda Belum Ditugaskan ke Kelas Mana pun</h3>
        <p class="text-sm mt-1 text-amber-700">Silakan hubungi Administrator untuk mengatur kelas tugas wali kelas pada akun Anda.</p>
    </div>
    @else

    <!-- Stat Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Siswa -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Siswa</p>
                <h3 class="text-2xl font-black text-gray-800">{{ $stats['total'] }}</h3>
            </div>
        </div>

        <!-- Tepat Waktu -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tepat Waktu</p>
                <h3 class="text-2xl font-black text-emerald-600">{{ $stats['ontime'] }}</h3>
            </div>
        </div>

        <!-- Terlambat -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Terlambat</p>
                <h3 class="text-2xl font-black text-amber-600">{{ $stats['late'] }}</h3>
            </div>
        </div>

        <!-- Belum Hadir / Libur Akhir Pekan -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl {{ $stats['is_holiday'] ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center text-xl shrink-0">
                <i class="fas {{ $stats['is_holiday'] ? 'fa-umbrella-beach' : 'fa-user-times' }}"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $stats['is_holiday'] ? 'Libur Akhir Pekan' : 'Belum Hadir' }}</p>
                <h3 class="text-2xl font-black {{ $stats['is_holiday'] ? 'text-slate-700' : 'text-rose-600' }}">{{ $stats['is_holiday'] ? $stats['holiday'] : $stats['absent'] }}</h3>
            </div>
        </div>

        <!-- Persentase Kehadiran -->
        <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-4 sm:p-5 border border-indigo-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">Kehadiran</p>
                <h3 class="text-2xl font-black text-indigo-900">{{ $stats['percentage'] }}%</h3>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" id="student-search" placeholder="Cari nama siswa..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
            <button type="button" class="filter-btn active px-3.5 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white transition" data-filter="all">
                Semua ({{ $stats['total'] }})
            </button>
            @if($stats['is_holiday'])
            <button type="button" class="filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition" data-filter="holiday">
                <i class="fas fa-umbrella-beach text-indigo-500 mr-1"></i> Libur ({{ $stats['holiday'] }})
            </button>
            @else
            <button type="button" class="filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="absent">
                Belum Hadir ({{ $stats['absent'] }})
            </button>
            @endif
            <button type="button" class="filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="ontime">
                Tepat Waktu ({{ $stats['ontime'] }})
            </button>
            <button type="button" class="filter-btn px-3.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="late">
                Terlambat ({{ $stats['late'] }})
            </button>
        </div>
    </div>

    <!-- Student Attendance List -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="students-table">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Siswa</th>
                        <th class="py-3.5 px-4 text-center">Status Kehadiran</th>
                        <th class="py-3.5 px-4 text-center">Waktu Presensi</th>
                        <th class="py-3.5 px-4">Kontak Orang Tua</th>
                        <th class="py-3.5 px-4 text-right">Aksi Notifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($studentsData as $idx => $s)
                    <tr class="student-row hover:bg-gray-50/80 transition" data-status="{{ $s['status'] }}" data-name="{{ strtolower($s['name']) }}">
                        <td class="py-3.5 px-4 text-center font-semibold text-gray-400">
                            {{ $idx + 1 }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                @if($s['photo'])
                                <img src="{{ $s['photo'] }}" alt="{{ $s['name'] }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 shrink-0">
                                @else
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 font-black flex items-center justify-center shrink-0 text-sm">
                                    {{ strtoupper(substr($s['name'], 0, 1)) }}
                                </div>
                                @endif
                                <div>
                                    <div class="font-bold text-gray-900">{{ $s['name'] }}</div>
                                    <div class="text-xs text-gray-500">PIN: {{ $s['device_user_id'] }}</div>
                                </div>
                            </div>
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
                                <button type="button" disabled class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 px-3 py-1.5 rounded-lg text-xs font-medium cursor-not-allowed" title="Nomor WA ortu belum ada">
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
                        <td colspan="6" class="py-8 text-center text-gray-400">
                            Belum ada siswa yang terdaftar di kelas ini.
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
    // Search & Filter Interactions
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('student-search');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const rows = document.querySelectorAll('.student-row');

        let currentFilter = 'all';

        function filterList() {
            const query = (searchInput?.value || '').toLowerCase().trim();

            rows.forEach(row => {
                const name = row.dataset.name || '';
                const status = row.dataset.status || '';

                const matchesQuery = name.includes(query);
                const matchesFilter = currentFilter === 'all' || status === currentFilter;

                if (matchesQuery && matchesFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterList);
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white', 'active');
                    b.classList.add('bg-gray-100', 'text-gray-700');
                });
                btn.classList.add('bg-blue-600', 'text-white', 'active');
                btn.classList.remove('bg-gray-100', 'text-gray-700');

                currentFilter = btn.dataset.filter || 'all';
                filterList();
            });
        });
    });
</script>
@endsection
