@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-rose-700 via-pink-700 to-red-800 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold uppercase tracking-wider text-rose-100 mb-2 border border-white/20">
                    <i class="fas fa-flag text-yellow-300"></i> Presensi Upacara & Acara Sekolah
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Presensi Upacara Dewan Guru
                </h1>
                <p class="text-rose-100 mt-1 text-sm sm:text-base max-w-2xl">
                    Pemantauan kehadiran Bapak/Ibu Guru saat Upacara Bendera Hari Senin atau Acara Sekolah melalui tap sidik jari di mesin fingerprint.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" id="btn-toggle-live" onclick="toggleAutoRefresh()" class="inline-flex items-center gap-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-white font-bold px-3.5 py-2.5 rounded-xl border border-emerald-400/40 backdrop-blur shadow transition active:scale-95 text-xs sm:text-sm">
                    <span id="live-dot" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span id="live-text">Live Presensi: Aktif</span>
                </button>

                <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white font-bold px-4 py-2.5 rounded-xl border border-white/30 backdrop-blur shadow transition active:scale-95 text-xs sm:text-sm">
                    <i class="fas fa-arrow-left"></i>
                    <span>Data Guru</span>
                </a>

                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 bg-white text-rose-800 hover:bg-rose-50 font-bold px-4 py-2.5 rounded-xl shadow-lg transition active:scale-95 text-xs sm:text-sm">
                    <i class="fas fa-print"></i>
                    <span>Cetak</span>
                </button>
            </div>
        </div>

        <!-- Summary Stat Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-white/15">
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-rose-100 uppercase tracking-wider font-semibold">Total Guru</div>
                <div class="text-2xl font-black mt-0.5">{{ $teachers->count() }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-rose-100 uppercase tracking-wider font-semibold">Hadir (Sudah Tap)</div>
                <div class="text-2xl font-black text-emerald-200 mt-0.5">{{ $attendedCount }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-rose-100 uppercase tracking-wider font-semibold">Belum Tap</div>
                <div class="text-2xl font-black text-amber-200 mt-0.5">{{ $unattendedCount }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-rose-100 uppercase tracking-wider font-semibold">Persentase Hadir</div>
                @php
                    $total = $teachers->count();
                    $pct = $total > 0 ? round(($attendedCount / $total) * 100, 1) : 0;
                @endphp
                <div class="text-2xl font-black text-yellow-200 mt-0.5">{{ $pct }}%</div>
            </div>
        </div>
    </div>

    <!-- Filter Tanggal -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100">
        <form action="{{ route('teachers.ceremony') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Pilihan Tanggal -->
            <div class="md:col-span-9">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                    <i class="fas fa-calendar-day text-rose-500 mr-1"></i> Tanggal Upacara / Kegiatan:
                </label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <!-- Tombol Refresh / Submit -->
            <div class="md:col-span-3 flex items-end">
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 px-4 rounded-xl text-sm transition shadow-sm flex items-center justify-center gap-1.5 mt-5 md:mt-0">
                    <i class="fas fa-sync-alt"></i>
                    <span>Tampilkan</span>
                </button>
            </div>
        </form>

        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <div>
                Data Presensi Tanggal: <strong class="text-gray-900 font-bold">{{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM Y') }}</strong>
            </div>
            <div>
                Total Guru Terdata: <strong class="text-gray-900 font-bold">{{ $reportData->count() }} Guru</strong>
            </div>
        </div>
    </div>

    <!-- Tabel Presensi Upacara -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-600">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">NIP</th>
                        <th class="py-3.5 px-4">NAMA GURU</th>
                        <th class="py-3.5 px-4 text-center">WAKTU TAP</th>
                        <th class="py-3.5 px-4 text-center">STATUS KEHADIRAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($reportData as $idx => $row)
                    <tr class="hover:bg-gray-50/70 transition">
                        <td class="py-3.5 px-4 text-center font-bold text-gray-400">{{ $idx + 1 }}</td>

                        <!-- NIP -->
                        <td class="py-3.5 px-4 font-mono font-bold text-gray-800">
                            <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs border border-gray-200">
                                {{ $row['nip'] }}
                            </span>
                        </td>

                        <!-- NAMA GURU -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-700 font-bold flex items-center justify-center text-xs shrink-0 border border-rose-200">
                                    {{ strtoupper(substr($row['name'], 0, 1)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-gray-900 block">{{ $row['name'] }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- WAKTU TAP -->
                        <td class="py-3.5 px-4 text-center font-mono font-semibold text-xs">
                            @if($row['has_attended'])
                                <span class="text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                                    <i class="fas fa-clock mr-1 text-emerald-500"></i>{{ $row['tap_time'] }} WIB
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <!-- STATUS -->
                        <td class="py-3.5 px-4 text-center">
                            @if($row['has_attended'])
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <i class="fas fa-check-circle text-emerald-600"></i> Hadir Upacara
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <i class="fas fa-times-circle text-rose-500"></i> Belum Tap
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            <i class="fas fa-flag text-4xl mb-3 text-gray-300"></i>
                            <p class="font-medium text-gray-700">Belum ada guru terdaftar untuk presensi upacara.</p>
                            <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-1 text-xs text-rose-600 font-bold hover:underline mt-2">
                                <i class="fas fa-plus"></i> Tambah Data Guru
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let isLiveActive = true;
    let refreshTimer = null;
    const isToday = '{{ $date }}' === '{{ \Carbon\Carbon::today('Asia/Jakarta')->toDateString() }}';

    function scheduleRefresh() {
        if (!isLiveActive || !isToday) return;
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => {
            // Hanya reload jika user tidak sedang menginput form
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT') {
                window.location.reload();
            } else {
                scheduleRefresh();
            }
        }, 12000); // Auto-refresh setiap 12 detik saat halaman live
    }

    function toggleAutoRefresh() {
        isLiveActive = !isLiveActive;
        const btn = document.getElementById('btn-toggle-live');
        const dot = document.getElementById('live-dot');
        const text = document.getElementById('live-text');

        if (isLiveActive) {
            btn.className = "inline-flex items-center gap-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-white font-bold px-3.5 py-2.5 rounded-xl border border-emerald-400/40 backdrop-blur shadow transition active:scale-95 text-xs sm:text-sm";
            dot.className = "w-2 h-2 rounded-full bg-emerald-400 animate-pulse";
            text.textContent = "Live Presensi: Aktif";
            scheduleRefresh();
        } else {
            clearTimeout(refreshTimer);
            btn.className = "inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white/80 font-bold px-3.5 py-2.5 rounded-xl border border-white/20 backdrop-blur shadow transition active:scale-95 text-xs sm:text-sm";
            dot.className = "w-2 h-2 rounded-full bg-gray-400";
            text.textContent = "Live Presensi: Jeda";
        }
    }

    if (isToday) {
        scheduleRefresh();
    } else {
        // Jika sedang melihat tanggal masa lalu, matikan auto refresh
        isLiveActive = false;
        const btn = document.getElementById('btn-toggle-live');
        if (btn) btn.style.display = 'none';
    }
</script>
@endsection
