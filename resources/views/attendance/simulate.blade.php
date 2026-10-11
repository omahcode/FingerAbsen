@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-indigo-900 via-blue-900 to-slate-900 rounded-3xl p-6 md:p-8 text-white shadow-xl mb-8 relative overflow-hidden border border-white/10">
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-32 -bottom-16 w-56 h-56 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/30 border border-indigo-400/40 text-indigo-200 text-xs font-semibold mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Simulator Presensi Biometrik Realtime
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                    <i class="fas fa-flask text-yellow-400"></i> Uji Coba & Simulasi Absensi Siswa
                </h1>
                <p class="text-blue-200 text-sm md:text-base mt-2 max-w-2xl leading-relaxed">
                    Gunakan halaman ini untuk mensimulasikan tap absensi sidik jari siswa. Buka halaman <strong>Mode TV</strong> di layar/tab terpisah untuk melihat <strong>notifikasi pop-up</strong> absensi berhasil secara otomatis dan perpindahan halus ke <strong>Slide 2 (Top 10 Kehadiran)</strong>!
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('tv.index') }}" target="_blank" class="px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm shadow-lg shadow-emerald-500/30 flex items-center gap-2 transition transform hover:-translate-y-0.5">
                    <i class="fas fa-tv text-lg"></i> Buka Tampilan TV
                </a>
                
                <button type="button" id="btn-seed-demo" class="px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-sm border border-white/20 flex items-center gap-2 transition">
                    <i class="fas fa-user-plus text-sky-300"></i> Buat Siswa Demo
                </button>

                <button type="button" id="btn-reset-today" class="px-4 py-3 rounded-xl bg-red-500/20 hover:bg-red-500/40 text-red-200 font-medium text-sm border border-red-400/30 flex items-center gap-2 transition">
                    <i class="fas fa-trash-alt"></i> Reset Absen Hari Ini
                </button>
            </div>
        </div>
    </div>

    <!-- Live Stats Quick Counter -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-gray-800" id="stat-students-count">{{ $students->count() }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Siswa</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fas fa-fingerprint"></i>
            </div>
            <div>
                <div class="text-2xl font-black text-emerald-600" id="stat-logs-count">{{ $recentLogs->count() }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Absen Hari Ini</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fas fa-satellite-dish"></i>
            </div>
            <div>
                <div class="text-base font-bold text-indigo-600 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span> Realtime Aktif
                </div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fast Poll & Socket</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fas fa-bolt"></i>
            </div>
            <div>
                <div class="text-base font-bold text-gray-800">1-Klik Simulasi</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Otomatis Masuk TV</div>
            </div>
        </div>
    </div>

    <!-- Main Section: Grid & Simulator Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        
        <!-- Left: Quick Tap Grid (2 Columns on Desktop) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-id-card text-indigo-600"></i> Tap Cepat Siswa (One-Click Tap)
                        </h2>
                        <p class="text-gray-500 text-xs mt-0.5">Klik salah satu tombol waktu untuk langsung mengirimkan absensi siswa ini ke layar TV</p>
                    </div>

                    <button type="button" id="btn-tap-random" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 flex items-center gap-1.5 transition">
                        <i class="fas fa-dice"></i> Tap Siswa Acak (Random)
                    </button>
                </div>

                @if($students->isEmpty())
                <div class="text-center py-12 px-4 rounded-2xl bg-gray-50 border border-dashed border-gray-200">
                    <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto text-2xl mb-3">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">Belum Ada Data Siswa</h3>
                    <p class="text-gray-500 text-xs mt-1 max-w-md mx-auto">
                        Klik tombol di bawah ini untuk langsung membuat 8 siswa demo lengkap beserta kelas dan streak agar dapat langsung disimulasikan.
                    </p>
                    <button type="button" onclick="document.getElementById('btn-seed-demo').click()" class="mt-4 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-md shadow-indigo-500/20">
                        <i class="fas fa-magic mr-1"></i> Buat 8 Siswa Demo Sekarang
                    </button>
                </div>
                @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[600px] overflow-y-auto pr-1">
                    @foreach($students as $student)
                    @php
                        $todayLog = $recentLogs->where('student_id', $student->id)->where('status_code', 0)->first();
                    @endphp
                    <div class="bg-gray-50/80 hover:bg-white rounded-2xl p-4 border border-gray-100 hover:border-indigo-200 hover:shadow-md transition duration-200 flex flex-col justify-between" id="card-student-{{ $student->id }}">
                        <div class="flex items-center gap-3.5 mb-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white font-bold text-lg flex items-center justify-center shadow-sm flex-shrink-0 overflow-hidden">
                                @if($student->photo)
                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-gray-900 text-sm truncate">{{ $student->name }}</h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-blue-100/70 text-blue-700 text-[11px] font-semibold">
                                        {{ $student->schoolClass->name ?? 'Umum' }}
                                    </span>
                                    <span class="text-[11px] text-gray-500 font-mono">UID: {{ $student->device_user_id }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Absen Hari Ini -->
                        <div class="mb-3 text-[11px] flex items-center justify-between px-2.5 py-1 rounded-lg {{ $todayLog ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50' : 'bg-gray-100/70 text-gray-500' }}">
                            <span class="font-medium">
                                @if($todayLog)
                                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Sudah Absen ({{ \Carbon\Carbon::parse($todayLog->timestamp)->format('H:i') }} WIB)
                                @else
                                    <i class="far fa-circle text-gray-400 mr-1"></i> Belum Ada Absen Masuk
                                @endif
                            </span>
                            @if($student->manual_streak > 0)
                                <span class="font-bold text-amber-600"><i class="fas fa-fire mr-0.5"></i>{{ $student->manual_streak }}d</span>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-3 gap-1.5 pt-2 border-t border-gray-100">
                            <button type="button" onclick="simulateTap({{ $student->id }}, 'now', '{{ addslashes($student->name) }}')" class="px-2 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-semibold text-center transition flex flex-col items-center justify-center gap-0.5" title="Tap dengan jam sekarang">
                                <i class="fas fa-bolt text-xs text-yellow-300"></i>
                                <span>Sekarang</span>
                            </button>
                            <button type="button" onclick="simulateTap({{ $student->id }}, '06:15', '{{ addslashes($student->name) }}')" class="px-2 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-semibold text-center transition flex flex-col items-center justify-center gap-0.5" title="Tap tepat waktu (06:15)">
                                <i class="far fa-clock text-xs text-emerald-600"></i>
                                <span>06:15 WIB</span>
                            </button>
                            <button type="button" onclick="simulateTap({{ $student->id }}, '06:45', '{{ addslashes($student->name) }}')" class="px-2 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold text-center transition flex flex-col items-center justify-center gap-0.5" title="Tap terlambat (06:45)">
                                <i class="fas fa-running text-xs text-amber-600"></i>
                                <span>06:45 WIB</span>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Right: Custom Simulation Form & Live Log Stream -->
        <div class="space-y-6">
            <!-- Custom Form Card -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2 mb-4">
                    <i class="fas fa-sliders-h text-indigo-600"></i> Form Simulasi Khusus
                </h3>

                <form id="form-custom-simulate" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Pilih Siswa</label>
                        <select name="student_id" id="sim-student-id" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50">
                            <option value="random">-- Siswa Acak (Random) --</option>
                            @foreach($students as $st)
                                <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->schoolClass->name ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Waktu Tap (Jam:Menit)</label>
                        <input type="time" name="time" id="sim-time" value="{{ date('H:i') }}" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 font-mono">
                        <span class="text-[11px] text-gray-400 mt-1 block">Biarkan sesuai jam sekarang atau atur bebas (misal 06:10 untuk tepat waktu)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Jenis Presensi</label>
                        <select name="status_code" id="sim-status" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50">
                            <option value="0">Absen Masuk (Status 0 - Muncul di TV)</option>
                            <option value="1">Absen Pulang (Status 1)</option>
                        </select>
                    </div>

                    <button type="submit" id="btn-submit-custom" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-2 transition duration-150">
                        <i class="fas fa-paper-plane"></i> Kirim Absen ke Mode TV
                    </button>
                </form>
            </div>

            <!-- Live Simulation Feed Card -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-history text-indigo-600"></i> Riwayat Simulasi Hari Ini
                    </h3>
                    <span class="text-xs bg-emerald-100 text-emerald-700 font-semibold px-2 py-0.5 rounded-full" id="live-indicator">Live</span>
                </div>

                <div class="space-y-2.5 max-h-[350px] overflow-y-auto pr-1" id="simulation-logs-feed">
                    @forelse($recentLogs as $log)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs hover:bg-indigo-50/50 transition">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="truncate">
                                <div class="font-bold text-gray-900 truncate">{{ $log->student->name ?? 'Siswa ' . $log->device_user_id }}</div>
                                <div class="text-[11px] text-gray-500">{{ $log->student->schoolClass->name ?? 'Umum' }}</div>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ \Carbon\Carbon::parse($log->timestamp)->format('H:i') }} WIB</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-400 text-xs" id="empty-log-state">
                        <i class="fas fa-inbox text-2xl mb-1 text-gray-300"></i>
                        <p>Belum ada simulasi tap absensi hari ini</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Simulate Tap Function
    async function simulateTap(studentId, timeType, studentName) {
        let timeVal = null;
        if (timeType !== 'now') {
            timeVal = timeType;
        }

        try {
            Swal.fire({
                title: 'Mengirim Absensi...',
                text: `Memproses absensi untuk ${studentName}`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const res = await fetch("{{ route('attendance.simulate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    time: timeVal,
                    status_code: 0
                })
            });

            const data = await res.json();
            Swal.close();

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Absensi Terkirim!',
                    html: `<b>${data.student.name}</b> (${data.student.class})<br>Waktu Tap: <span class="font-mono text-indigo-600 font-bold">${data.time} WIB</span><br><br><span class="text-xs text-gray-500">Periksa tab Mode TV sekarang untuk melihat pop-up dan geseran otomatis ke Slide 2!</span>`,
                    timer: 3500,
                    showConfirmButton: true,
                    confirmButtonText: 'Mantap',
                    confirmButtonColor: '#4f46e5'
                });

                prependLogFeed(data.student.name, data.student.class, data.time);
            } else {
                Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
            }
        } catch(err) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        }
    }

    // Custom Form Submission
    document.getElementById('form-custom-simulate').addEventListener('submit', async (e) => {
        e.preventDefault();
        const studentId = document.getElementById('sim-student-id').value;
        const timeVal = document.getElementById('sim-time').value;
        const statusCode = document.getElementById('sim-status').value;

        try {
            Swal.fire({
                title: 'Mengirim Absensi...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const res = await fetch("{{ route('attendance.simulate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    time: timeVal,
                    status_code: statusCode
                })
            });

            const data = await res.json();
            Swal.close();

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Absensi Terkirim!',
                    html: `<b>${data.student.name}</b> (${data.student.class})<br>Waktu Tap: <span class="font-mono text-indigo-600 font-bold">${data.time} WIB</span><br><br><span class="text-xs text-gray-500">Buka Mode TV sekarang untuk melihat animasi pop-up dan transisi ke Slide 2!</span>`,
                    timer: 3500,
                    showConfirmButton: true,
                    confirmButtonColor: '#4f46e5'
                });

                prependLogFeed(data.student.name, data.student.class, data.time);
            } else {
                Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
            }
        } catch(err) {
            Swal.fire('Error', 'Gagal mengirim simulasi.', 'error');
        }
    });

    // Tap Random Student
    document.getElementById('btn-tap-random')?.addEventListener('click', () => {
        simulateTap('random', 'now', 'Siswa Acak');
    });

    // Seed Demo Students
    document.getElementById('btn-seed-demo')?.addEventListener('click', async () => {
        const confirm = await Swal.fire({
            title: 'Buat Siswa Demo?',
            text: 'Sistem akan otomatis membuat 8 data siswa demo beserta kelas dan streak agar simulasi dapat langsung dijalankan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Buat Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#4f46e5'
        });

        if (confirm.isConfirmed) {
            Swal.fire({ title: 'Membuat Data...', didOpen: () => Swal.showLoading() });
            const res = await fetch("{{ route('attendance.simulate.seed_demo') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.status === 'success') {
                Swal.fire('Berhasil', data.message, 'success').then(() => window.location.reload());
            } else {
                Swal.fire('Gagal', 'Terjadi kesalahan.', 'error');
            }
        }
    });

    // Reset Today's Attendance
    document.getElementById('btn-reset-today')?.addEventListener('click', async () => {
        const confirm = await Swal.fire({
            title: 'Reset Absen Hari Ini?',
            text: 'Semua data absensi yang dicatat pada hari ini akan dibersihkan agar Anda dapat menguji coba simulasi dari awal.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Bersihkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444'
        });

        if (confirm.isConfirmed) {
            Swal.fire({ title: 'Membersihkan Data...', didOpen: () => Swal.showLoading() });
            const res = await fetch("{{ route('attendance.simulate.reset_today') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.status === 'success') {
                Swal.fire('Berhasil', data.message, 'success').then(() => window.location.reload());
            } else {
                Swal.fire('Gagal', 'Terjadi kesalahan.', 'error');
            }
        }
    });

    // Prepend to feed helper
    function prependLogFeed(name, className, timeStr) {
        const feed = document.getElementById('simulation-logs-feed');
        const emptyState = document.getElementById('empty-log-state');
        if (emptyState) emptyState.remove();

        const item = document.createElement('div');
        item.className = 'flex items-center justify-between p-3 rounded-xl bg-emerald-50/70 border border-emerald-200/60 text-xs transition duration-300 transform -translate-y-2';
        item.innerHTML = `
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-bold">
                    <i class="fas fa-check"></i>
                </div>
                <div class="truncate">
                    <div class="font-bold text-gray-900 truncate">${name}</div>
                    <div class="text-[11px] text-gray-500">${className}</div>
                </div>
            </div>
            <div class="text-right flex-shrink-0">
                <span class="font-mono font-bold text-emerald-700 bg-white px-2 py-0.5 rounded shadow-sm">${timeStr} WIB</span>
            </div>
        `;

        if (feed) {
            feed.insertBefore(item, feed.firstChild);
            setTimeout(() => {
                item.classList.remove('-translate-y-2');
            }, 50);
        }

        const logCountEl = document.getElementById('stat-logs-count');
        if (logCountEl) {
            logCountEl.textContent = parseInt(logCountEl.textContent || '0') + 1;
        }
    }
</script>
@endsection
