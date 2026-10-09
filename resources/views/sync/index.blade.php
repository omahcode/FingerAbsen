@extends('layouts.app')
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold">Sinkronisasi & Replikasi Multi-Mesin</h2>
    <p class="text-gray-600 text-sm mt-1">Kelola penyebaran data siswa dan template sidik jari antara Mesin 1, Mesin 2, dan database cloud secara otomatis.</p>
</div>

<!-- Card Utama: Sebar Data Siswa ke Semua Mesin -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg p-6 text-white shadow-lg mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h3 class="text-xl font-bold mb-1">🔁 Sebar Semua Siswa & Sidik Jari ke Seluruh Mesin</h3>
            <p class="text-blue-100 text-sm max-w-2xl">
                Menyinkronkan total <strong>{{ $studentsCount ?? 0 }} Siswa</strong> dan <strong>{{ $templatesCount ?? 0 }} Template Sidik Jari</strong> dari database web ke <strong>Mesin 1</strong> dan <strong>Mesin 2</strong>. Siswa yang sudah didaftarkan di satu mesin akan otomatis bisa absen di mesin lainnya.
            </p>
        </div>
        <form action="{{ route('sync.pushAll') }}" method="POST" class="mt-4 md:mt-0 w-full md:w-auto" data-confirm="Kirim seluruh data siswa dan template sidik jari dari database ke semua mesin fingerprint?" data-title="⚡ Sebar Data ke Semua Mesin" data-icon="question">
            @csrf
            <button type="submit" class="w-full md:w-auto text-center bg-white hover:bg-gray-100 text-indigo-700 font-bold py-3 px-6 rounded-lg shadow transition-all duration-200 transform hover:scale-105">
                ⚡ Mulai Sebar Data
            </button>
        </form>
    </div>
</div>

<h3 class="font-bold text-gray-700 text-lg mb-4">Fitur Tambahan (Tarik Data Mesin)</h3>

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