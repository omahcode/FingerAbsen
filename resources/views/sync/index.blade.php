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
        <form action="{{ route('sync.pushAll') }}" method="POST" class="mt-4 md:mt-0" onsubmit="return confirm('Kirim semua data siswa & sidik jari ke seluruh mesin fingerprint?')">
            @csrf
            <button type="submit" class="bg-white hover:bg-gray-100 text-indigo-700 font-bold py-3 px-6 rounded-lg shadow transition-all duration-200 transform hover:scale-105">
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
        <form action="{{ route('students.sync') }}" method="POST">
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
        <form action="{{ route('sync.backupTemplates') }}" method="POST">
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
        <form action="{{ route('attendance.sync') }}" method="POST">
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
@endsection