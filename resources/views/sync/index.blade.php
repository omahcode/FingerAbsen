@extends('layouts.app')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Sinkronisasi & Replikasi Multi-Mesin</h2>
        <p class="text-gray-600 text-sm mt-1">Tarik sidik jari dari satu mesin atau seluruh mesin sekaligus, dan sebarkan ke mesin-mesin lain via antrean bertahap (anti-overload).</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ ($totalPendingQueue ?? 0) > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' }}">
            <span class="w-2 h-2 rounded-full mr-2 {{ ($totalPendingQueue ?? 0) > 0 ? 'bg-amber-500 animate-ping' : 'bg-emerald-500' }}"></span>
            Total Antrean Pending: {{ $totalPendingQueue ?? 0 }} Perintah
        </span>
    </div>
</div>

<!-- Ringkasan Statistik Sidik Jari & Mesin -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
            <i class="fas fa-server"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">Mesin Terdaftar</span>
            <span class="text-lg font-bold text-gray-800">{{ $devices->count() }} Mesin</span>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0">
            <i class="fas fa-fingerprint"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">Sidik Jari di Database</span>
            <span class="text-lg font-bold text-purple-700">{{ $templatesCount ?? 0 }} Template</span>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
            <i class="fas fa-user-check"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">Siswa Punya Sidik Jari</span>
            <span class="text-lg font-bold text-emerald-700">{{ $studentsWithFpCount ?? 0 }} / {{ $studentsCount ?? 0 }}</span>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
            <i class="fas fa-tasks"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">Antrean Unduh Mesin</span>
            <span class="text-lg font-bold {{ ($totalPendingQueue ?? 0) > 0 ? 'text-amber-600' : 'text-gray-800' }}">{{ $totalPendingQueue ?? 0 }} Antrean</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Card 1: TARIK SIDIK JARI DARI MESIN KE SERVER -->
    <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-xl flex flex-col justify-between border border-slate-800 relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-purple-600/10 rounded-full blur-2xl pointer-events-none"></div>

        <div>
            <div class="flex items-center gap-2.5 mb-3">
                <span class="p-2.5 bg-purple-500/20 text-purple-300 border border-purple-400/30 rounded-xl text-lg">
                    <i class="fas fa-cloud-download-alt"></i>
                </span>
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-white">1. Tarik Sidik Jari dari Mesin ke Server</h3>
                    <p class="text-slate-300 text-xs">Sedot template sidik jari fisik dari mesin ke database web</p>
                </div>
            </div>

            <p class="text-xs text-slate-300 leading-relaxed mb-4">
                Fitur ini menarik sidik jari yang didaftarkan langsung pada sensor mesin masuk ke database server. Setelah tersimpan di database, Anda dapat langsung menyebarkannya ke mesin-mesin lain (replikasi).
            </p>

            <form action="{{ route('sync.pullTemplates') }}" method="POST" data-confirm="Tarik data template sidik jari dari mesin ke database server?" data-title="📥 Tarik Sidik Jari dari Mesin" data-icon="question">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-purple-200 mb-1.5">
                        📡 Pilih Mesin Asal
                    </label>
                    <select name="device_id" class="w-full bg-slate-800/90 text-white text-sm rounded-xl p-3 border border-slate-700 shadow-sm focus:ring-2 focus:ring-purple-400 focus:outline-none">
                        <option value="all">🌐 Seluruh Mesin Terdaftar (Tarik Sekaligus)</option>
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}">🖥️ {{ $d->name }} ({{ $d->serial_number ?: $d->ip_address }}) [{{ $d->purpose_label }}]</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        Bisa pilih <strong>1 per 1 mesin</strong> tertentu atau <strong>semua mesin sekaligus</strong>.
                    </p>
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <span class="text-[11px] text-purple-300 flex items-center gap-1">
                        <i class="fas fa-check-circle text-emerald-400"></i> Hybrid Socket & ADMS
                    </span>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-500 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 text-xs sm:text-sm">
                        <i class="fas fa-download"></i>
                        <span>Mulai Tarik Sidik Jari</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card 2: SEBAR DATA & SIDIK JARI KE MESIN (REPLIKASI) -->
    <div class="bg-gradient-to-br from-blue-700 via-indigo-700 to-purple-800 rounded-2xl p-6 text-white shadow-xl flex flex-col justify-between relative overflow-hidden" id="card-sebar-data">
        <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-blue-400/10 rounded-full blur-2xl pointer-events-none"></div>

        <div>
            <div class="flex items-center gap-2.5 mb-3">
                <span class="p-2.5 bg-white/20 backdrop-blur rounded-xl text-lg">
                    <i class="fas fa-paper-plane"></i>
                </span>
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-white">2. Sebar Sidik Jari & Data ke Mesin Lain</h3>
                    <p class="text-blue-100 text-xs">Replikasi template sidik jari database ke mesin-mesin fingerprint</p>
                </div>
            </div>

            <p class="text-xs text-blue-100 leading-relaxed mb-4">
                Kirim data siswa dan template sidik jari dari database web ke mesin-mesin fingerprint tujuan via antrean bertahap anti-overload (15 per request).
            </p>

            <form action="{{ route('sync.pushAll') }}" method="POST" data-confirm="Mulai jadwalkan penyebaran data terpilih ke antrean mesin?" data-title="⚡ Sebar Data ke Antrean Mesin" data-icon="question">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                    <!-- Pilihan Target Mesin -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-blue-100 mb-1">
                            🎯 Target Mesin
                        </label>
                        <select name="device_id" class="w-full bg-white text-gray-800 text-xs rounded-xl p-2.5 border border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                            <option value="">📡 Semua Mesin</option>
                            @foreach($devices as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Kelas / Rombel -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-blue-100 mb-1">
                            👥 Filter Kelas
                        </label>
                        <select name="school_class_id" class="w-full bg-white text-gray-800 text-xs rounded-xl p-2.5 border border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                            <option value="">👥 Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">Kelas {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pilihan Jenis Data -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-blue-100 mb-1">
                            📦 Konten Data
                        </label>
                        <select name="content_type" class="w-full bg-white text-gray-800 text-xs rounded-xl p-2.5 border border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                            <option value="templates_only">👆 Hanya Sidik Jari</option>
                            <option value="all">🔁 Lengkap (User & FP)</option>
                            <option value="users_only">👤 Hanya User (PIN)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <span class="text-[11px] text-blue-100 flex items-center gap-1">
                        <i class="fas fa-shield-alt text-emerald-300"></i> Anti-Overload Queue
                    </span>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-100 text-indigo-900 font-bold py-2.5 px-5 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 text-xs sm:text-sm">
                        <i class="fas fa-share-alt"></i>
                        <span>Mulai Sebar Sidik Jari</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Antrean Per Mesin (Queue Status Monitor & Aksi Cepat) -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
            <h3 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="mr-2">📊</span> Status & Aksi Cepat Per Mesin Fingerprint
            </h3>
            <p class="text-xs text-gray-500">Tarik sidik jari dari mesin spesifik (1 per 1), sebar sidik jari ke mesin spesifik, atau bersihkan antrean.</p>
        </div>
        @if(($totalPendingQueue ?? 0) > 0)
        <form action="{{ route('sync.clearQueue') }}" method="POST" data-confirm="Kosongkan seluruh antrean perintah untuk semua mesin?" data-title="Bersihkan Semua Antrean" data-icon="warning">
            @csrf
            <button type="submit" class="text-xs bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-1.5 px-3 rounded-xl border border-red-200 transition flex items-center gap-1.5">
                <i class="fas fa-trash-alt"></i> Bersihkan Semua Antrean
            </button>
        </form>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($devices as $d)
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow transition relative flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h4 class="font-bold text-gray-900 text-base flex items-center gap-1.5">
                            <i class="fas fa-desktop text-indigo-500 text-sm"></i>
                            {{ $d->name }}
                        </h4>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">SN: {{ $d->serial_number ?: 'Belum Terhubung' }}</p>
                        <p class="text-xs text-gray-500 font-mono">IP: {{ $d->ip_address }}:{{ $d->port }}</p>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ $d->purpose_label }}
                    </span>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-gray-400 block">Status Antrean:</span>
                        @if($d->pending_queue_count > 0)
                            <span class="inline-flex items-center text-xs font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200 mt-0.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse mr-1.5"></span>
                                {{ $d->pending_queue_count }} Perintah Tertunda
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200 mt-0.5">
                                <i class="fas fa-check-circle mr-1 text-emerald-600"></i>
                                Antrean Bersih (Sinkron)
                            </span>
                        @endif
                    </div>

                    @if($d->pending_queue_count > 0)
                    <form action="{{ route('sync.clearQueue') }}" method="POST" data-confirm="Kosongkan antrean sinkronisasi untuk mesin {{ $d->name }}?" data-title="Bersihkan Antrean" data-icon="warning">
                        @csrf
                        <input type="hidden" name="device_id" value="{{ $d->id }}">
                        <button type="submit" class="text-[11px] text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 font-semibold py-1 px-2.5 rounded-lg border border-red-200 transition" title="Reset Antrean Mesin Ini">
                            Reset
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Tombol Aksi Cepat 1 per 1 untuk Mesin Ini -->
            <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-2 gap-2">
                <!-- 1. Tarik Sidik Jari dari Mesin Ini -->
                <form action="{{ route('sync.pullTemplates') }}" method="POST" data-confirm="Tarik seluruh template sidik jari dari mesin {{ $d->name }} ke database server?" data-title="📥 Tarik Sidik Jari" data-icon="question">
                    @csrf
                    <input type="hidden" name="device_id" value="{{ $d->id }}">
                    <button type="submit" class="w-full text-center py-2 px-2.5 rounded-xl bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 text-xs font-bold border border-purple-200 transition flex items-center justify-center gap-1.5 shadow-sm" title="Tarik Sidik Jari dari Mesin Ini">
                        <i class="fas fa-download"></i>
                        <span>Tarik Sidik Jari</span>
                    </button>
                </form>

                <!-- 2. Sebar Sidik Jari ke Mesin Ini -->
                <form action="{{ route('sync.pushAll') }}" method="POST" data-confirm="Sebarkan seluruh template sidik jari dari database ke mesin {{ $d->name }}?" data-title="📤 Sebar Sidik Jari" data-icon="question">
                    @csrf
                    <input type="hidden" name="device_id" value="{{ $d->id }}">
                    <input type="hidden" name="content_type" value="templates_only">
                    <button type="submit" class="w-full text-center py-2 px-2.5 rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 text-xs font-bold border border-blue-200 transition flex items-center justify-center gap-1.5 shadow-sm" title="Kirim seluruh template sidik jari ke mesin ini">
                        <i class="fas fa-upload"></i>
                        <span>Sebar ke Sini</span>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full p-8 text-center text-gray-400 bg-white rounded-2xl border border-gray-200">
            <i class="fas fa-server text-3xl mb-2 text-gray-300"></i>
            <p>Belum ada mesin fingerprint yang terdaftar di database.</p>
        </div>
        @endforelse
    </div>
</div>

<h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
    <i class="fas fa-tools text-indigo-600"></i> Fitur Penarikan Data Tambahan dari Mesin
</h3>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Card Tarik Siswa -->
    <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100 border-t-4 border-t-blue-500">
        <h3 class="font-bold mb-1 text-base text-blue-700 flex items-center gap-1.5">
            <i class="fas fa-user-plus"></i> Tarik Data Siswa Baru dari Mesin
        </h3>
        <p class="text-xs text-gray-500 mb-4">Menarik daftar nama dan nomor PIN dari memori mesin ke dalam database dan langsung menetapkan kelasnya.</p>
        <form action="{{ route('students.sync') }}" method="POST" data-confirm="Tarik data siswa dari mesin ke database web?" data-title="Tarik Data Siswa" data-icon="question">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Mesin Asal</label>
                <select name="device_id" class="border border-gray-200 w-full p-2.5 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Masukkan ke Kelas</label>
                <select name="school_class_id" class="border border-gray-200 w-full p-2.5 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl w-full text-xs sm:text-sm shadow-sm transition">
                Tarik Data Siswa ke Kelas
            </button>
        </form>
    </div>

    <!-- Card Tarik Absen -->
    <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100 border-t-4 border-t-emerald-500">
        <h3 class="font-bold mb-1 text-base text-emerald-700 flex items-center gap-1.5">
            <i class="fas fa-history"></i> Tarik Data Absensi Tertinggal
        </h3>
        <p class="text-xs text-gray-500 mb-4">Menarik log absensi kehadiran dari mesin jika mesin sempat mati lampu atau koneksi internet terputus.</p>
        <form action="{{ route('attendance.sync') }}" method="POST" data-confirm="Tarik data absensi dari mesin ke server web?" data-title="Tarik Log Absensi" data-icon="question">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Mesin</label>
                <select name="device_id" class="border border-gray-200 w-full p-2.5 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-emerald-400 focus:outline-none" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl w-full text-xs sm:text-sm shadow-sm transition mt-10">
                Tarik Log Absensi Sekarang
            </button>
        </form>
    </div>
</div>

<!-- Live ADMS Monitor Panel -->
<div class="mt-8 bg-white p-6 shadow-sm rounded-2xl border border-gray-100 border-t-4 border-t-indigo-600">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-bold text-lg text-gray-800 flex items-center">
                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse mr-2"></span>
                📡 Log Komunikasi ADMS Mesin (Live Traffic)
            </h3>
            <p class="text-xs text-gray-500">Memantau request dan respon data antara Mesin Fingerprint dan Server Cloud secara realtime.</p>
        </div>
        <button onclick="window.location.reload()" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-1.5 px-3 rounded-xl border transition flex items-center gap-1.5">
            <i class="fas fa-sync-alt"></i> Refresh Log
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b text-gray-600">
                    <th class="p-2.5">Waktu</th>
                    <th class="p-2.5">Serial Number (SN)</th>
                    <th class="p-2.5">Endpoint</th>
                    <th class="p-2.5">Metode</th>
                    <th class="p-2.5">Keterangan / Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admsLogs ?? [] as $log)
                <tr class="border-b hover:bg-gray-50 font-mono">
                    <td class="p-2.5 text-gray-500">{{ $log['time'] }}</td>
                    <td class="p-2.5 font-bold text-indigo-700">{{ $log['sn'] }}</td>
                    <td class="p-2.5 text-gray-700">{{ $log['endpoint'] }}</td>
                    <td class="p-2.5">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $log['method'] === 'POST' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $log['method'] }}
                        </span>
                    </td>
                    <td class="p-2.5 font-sans font-medium text-gray-800">
                        {{ $log['info'] }}
                        @if(!empty($log['preview']))
                            <span class="block text-[10px] text-gray-400 font-mono mt-0.5 truncate max-w-md">{{ $log['preview'] }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-400 font-sans">Belum ada aktivitas request dari mesin fisik.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
