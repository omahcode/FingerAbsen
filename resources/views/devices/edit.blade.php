@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Pengaturan Mesin</h2>
        <a href="{{ route('devices.index') }}" class="text-xs text-gray-500 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>
    
    @if($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <ul class="text-xs">
                @foreach($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('devices.update', $device->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Nama Mesin</label>
            <input type="text" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ old('name', $device->name) }}" required>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Serial Number (SN Mesin)</label>
            <input type="text" name="serial_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 font-mono text-sm leading-tight focus:outline-none focus:shadow-outline" value="{{ old('serial_number', $device->serial_number) }}" placeholder="Contoh: DEV-DEMO-001">
            <p class="text-[11px] text-gray-400 mt-1">SN digunakan protokol ADMS cloud untuk mengenali mesin ini.</p>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Peruntukan Mesin (Fungsi)</label>
            <select name="purpose" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 font-semibold leading-tight focus:outline-none focus:shadow-outline">
                <option value="all" {{ old('purpose', $device->purpose) === 'all' ? 'selected' : '' }}>
                    Umum (Siswa & Dewan Guru)
                </option>
                <option value="teachers" {{ old('purpose', $device->purpose) === 'teachers' ? 'selected' : '' }}>
                    ⭐ Khusus Upacara Guru (Tap Siswa Otomatis Ditolak)
                </option>
                <option value="students" {{ old('purpose', $device->purpose) === 'students' ? 'selected' : '' }}>
                    Khusus Siswa (Presensi Masuk & Pulang)
                </option>
            </select>
            <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                <i class="fas fa-info-circle text-amber-600 mr-1"></i>
                Jika disetel ke <strong>Khusus Upacara Guru</strong>:
                <ul class="list-disc ml-5 mt-1 space-y-0.5 text-[11px]">
                    <li>Hanya guru terdaftar yang dapat tap pada mesin ini.</li>
                    <li>Siswa yang mencoba tap akan diabaikan (tidak tercatat sebagai absensi).</li>
                    <li>Hasil tap guru hanya masuk ke <strong>Presensi Upacara Guru</strong> (tidak masuk TV dan tidak masuk data absensi siswa).</li>
                </ul>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">IP Address</label>
                <input type="text" name="ip_address" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 font-mono text-sm leading-tight focus:outline-none focus:shadow-outline" value="{{ old('ip_address', $device->ip_address) }}" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Port</label>
                <input type="number" name="port" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 font-mono text-sm leading-tight focus:outline-none focus:shadow-outline" value="{{ old('port', $device->port) }}" required>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded shadow transition active:scale-95 text-sm">
                <i class="fas fa-save mr-1"></i> Simpan Perubahan
            </button>
            <a href="{{ route('devices.index') }}" class="font-bold text-sm text-gray-500 hover:text-gray-800">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
