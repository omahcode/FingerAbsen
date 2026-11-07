@extends('layouts.app')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Sinkronisasi & Replikasi Multi-Mesin</h2>
        <p class="text-gray-600 text-sm mt-1">Kelola penyebaran data siswa dan template sidik jari antar mesin secara otomatis dan bertahap via antrean (anti-overload).</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ ($totalPendingQueue ?? 0) > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' }}">
            <span class="w-2 h-2 rounded-full mr-2 {{ ($totalPendingQueue ?? 0) > 0 ? 'bg-amber-500 animate-ping' : 'bg-emerald-500' }}"></span>
            Total Antrean Pending: {{ $totalPendingQueue ?? 0 }} Perintah
        </span>
    </div>
</div>

<!-- Card Utama: Sebar Data Bertahap (Anti-Overload Queue) -->
<div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-800 rounded-xl p-6 text-white shadow-xl mb-8">
    <div class="mb-4">
        <div class="flex items-center gap-2">
            <span class="p-2 bg-white/20 backdrop-blur rounded-lg text-lg">🔁</span>
            <div>
                <h3 class="text-xl font-bold tracking-tight">Sebar Data Siswa & Sidik Jari ke Mesin</h3>
                <p class="text-blue-100 text-xs sm:text-sm mt-0.5">
                    Data di database: <strong>{{ $studentsCount ?? 0 }} Siswa</strong> dan <strong>{{ $templatesCount ?? 0 }} Template Sidik Jari</strong>.
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('sync.pushAll') }}" method="POST" class="bg-white/10 backdrop-blur-md rounded-lg p-4 sm:p-5 border border-white/20" data-confirm="Mulai jadwalkan penyebaran data terpilih ke antrean mesin?" data-title="⚡ Sebar Data ke Antrean Mesin" data-icon="question">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- Pilihan Target Mesin -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-blue-100 mb-1.5">
                    🎯 Target Mesin Tujuan
                </label>
                <select name="device_id" class="w-full bg-white text-gray-800 text-sm rounded-lg p-2.5 border border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    <option value="">📡 Semua Mesin Terdaftar (Broadcast)</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }}) [{{ $d->purpose_label }}]</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-blue-200 mt-1">Pilih satu mesin tertentu atau sebarkan ke seluruh mesin.</p>
            </div>

            <!-- Filter Kelas / Rombel -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-blue-100 mb-1.5">
                    👥 Filter Rombel / Kelas
                </label>
                <select name="school_class_id" class="w-full bg-white text-gray-800 text-sm rounded-lg p-2.5 border border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    <option value="">👥 Seluruh Siswa (Semua Kelas)</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">Kelas {{ $c->name }} ({{ $c->major->name ?? '-' }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-blue-200 mt-1">Gunakan untuk menyebarkan bertahap per kelas agar tidak menumpuk.</p>
            </div>

            <!-- Pilihan Jenis Data -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-blue-100 mb-1.5">
                    📦 Jenis Konten Data
                </label>
                <select name="content_type" class="w-full bg-white text-gray-800 text-sm rounded-lg p-2.5 border border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    <option value="all">🔁 Lengkap (Data Siswa & Sidik Jari)</option>
                    <option value="templates_only">👆 Hanya Template Sidik Jari</option>
                    <option value="users_only">👤 Hanya Data Siswa (PIN & Nama)</option>
                </select>
                <p class="text-[11px] text-blue-200 mt-1">Pilih apakah butuh sidik jari fisik, nama user, atau keduanya.</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2 border-t border-white/20">
            <div class="flex items-center text-xs text-blue-100">
                <svg class="w-4 h-4 mr-1.5 text-emerald-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span><strong>Anti-Overload:</strong> Perintah dikirim via antrean bertahap (15 data per siklus polling). Server tetap ringan & stabil.</span>
            </div>
            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center bg-white hover:bg-gray-100 text-indigo-800 font-bold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                ⚡ Mulai Sebar ke Antrean
            </button>
        </div>
    </form>
</div>

<!-- Status Antrean Per Mesin (Queue Status Monitor) -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
            <h3 class="font-bold text-gray-800 text-lg flex items-center">
                <span class="mr-2">📊</span> Status Antrean Sinkronisasi Mesin
            </h3>
            <p class="text-xs text-gray-500">Pantau proses pengunduhan antrean data pada masing-masing mesin fisik secara real-time.</p>
        </div>
        @if(($totalPendingQueue ?? 0) > 0)
        <form action="{{ route('sync.clearQueue') }}" method="POST" data-confirm="Kosongkan seluruh antrean perintah untuk semua mesin?" data-title="Bersihkan Semua Antrean" data-icon="warning">
            @csrf
            <button type="submit" class="text-xs bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-1.5 px-3 rounded border border-red-200 transition">
                🗑️ Bersihkan Semua Antrean
            </button>
        </form>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($devices as $d)
        <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm hover:shadow transition relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <h4 class="font-bold text-gray-800 text-base">{{ $d->name }}</h4>
                    <p class="text-xs text-gray-500 font-mono mt-0.5">SN: {{ $d->serial_number ?: 'Belum Terhubung' }}</p>
                    <p class="text-xs text-gray-500 font-mono">IP: {{ $d->ip_address }}</p>
                </div>
                <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-gray-100 text-gray-700">
                    {{ $d->purpose_label }}
                </span>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-[11px] text-gray-500 block">Status Antrean:</span>
                    @if($d->pending_queue_count > 0)
                        <span class="inline-flex items-center text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 mt-0.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse mr-1.5"></span>
                            {{ $d->pending_queue_count }} Perintah Tertunda
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 mt-0.5">
                            <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Antrean Bersih (Sinkron)
                        </span>
                    @endif
                </div>

                @if($d->pending_queue_count > 0)
                <form action="{{ route('sync.clearQueue') }}" method="POST" data-confirm="Kosongkan antrean sinkronisasi untuk mesin {{ $d->name }}?" data-title="Bersihkan Antrean" data-icon="warning">
                    @csrf
                    <input type="hidden" name="device_id" value="{{ $d->id }}">
                    <button type="submit" class="text-[11px] text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 font-medium py-1 px-2.5 rounded border border-red-200 transition">
                        Reset
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full p-6 text-center text-gray-400 bg-white rounded-lg border">
            Belum ada mesin fingerprint yang terdaftar di database.
        </div>
        @endforelse
    </div>
</div>

<h3 class="font-bold text-gray-700 text-lg mb-4">Fitur Tambahan (Tarik Data Mesin ke Server)</h3>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Card Tarik Siswa -->
    <div class="bg-white p-6 shadow rounded border-t-4 border-blue-500">
        <h3 class="font-bold mb-2 text-lg text-blue-700">1. Tarik Data Siswa dari Mesin</h3>
        <p class="text-sm text-gray-500 mb-4">Menarik daftar nama dan NIS dari memori mesin ke dalam database web.</p>
        <form action="{{ route('students.sync') }}" method="POST" data-confirm="Tarik data siswa dari mesin ke database web?" data-title="Tarik Data Siswa" data-icon="question">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-bold mb-1">Pilih Mesin Asal</label>
                <select name="device_id" class="border w-full p-2 rounded" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">Masukkan ke Kelas</label>
                <select name="school_class_id" class="border w-full p-2 rounded" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                Tarik Data Siswa
            </button>
        </form>
    </div>

    <!-- Card Tarik Sidik Jari -->
    <div class="bg-white p-6 shadow rounded border-t-4 border-purple-500">
        <h3 class="font-bold mb-2 text-lg text-purple-700">2. Backup Sidik Jari dari Mesin</h3>
        <p class="text-sm text-gray-500 mb-4">Menarik template sidik jari fisik dari mesin ke database cloud (untuk disebar ke mesin lain).</p>
        <form action="{{ route('sync.backupTemplates') }}" method="POST" data-confirm="Kirim perintah penarikan seluruh template sidik jari dari mesin ke server cloud?" data-title="Backup Sidik Jari" data-icon="question">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">Pilih Mesin Asal</label>
                <select name="device_id" class="border w-full p-2 rounded" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded w-full mt-[68px]">
                Backup Template Sidik Jari
            </button>
        </form>
    </div>

    <!-- Card Tarik Absen -->
    <div class="bg-white p-6 shadow rounded border-t-4 border-green-500">
        <h3 class="font-bold mb-2 text-lg text-green-700">3. Tarik Data Absensi Tertinggal</h3>
        <p class="text-sm text-gray-500 mb-4">Menarik log sidik jari dari mesin yang belum sempat masuk ke web secara Realtime.</p>
        <form action="{{ route('attendance.sync') }}" method="POST" data-confirm="Tarik data absensi dari mesin ke server web?" data-title="Tarik Log Absensi" data-icon="question">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">Pilih Mesin</label>
                <select name="device_id" class="border w-full p-2 rounded" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded w-full mt-[68px]">
                Tarik Log Absensi
            </button>
        </form>
    </div>
</div>

<!-- Live ADMS Monitor Panel -->
<div class="mt-8 bg-white p-6 shadow rounded border-t-4 border-indigo-600">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-bold text-lg text-gray-800 flex items-center">
                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse mr-2"></span>
                📡 Log Komunikasi ADMS Mesin (Live Traffic)
            </h3>
            <p class="text-xs text-gray-500">Memantau request dan respon data antara Mesin Fingerprint dan Server Cloud secara realtime.</p>
        </div>
        <button onclick="window.location.reload()" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-1.5 px-3 rounded border">
            🔄 Refresh Log
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
