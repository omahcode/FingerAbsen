@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow border border-gray-100">
    <div class="flex items-center space-x-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
            <i class="fas fa-user-edit text-lg"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Data Siswa</h2>
            <p class="text-xs text-gray-500">Perbarui nama, kelas, dan nomor WhatsApp orang tua.</p>
        </div>
    </div>

    <form action="{{ route('students.update', $student->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Kelas Baru</label>
            <select name="school_class_id" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $student->school_class_id == $c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->major->name ?? '' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-400 mb-1">NIS / ID Mesin (Terkunci)</label>
            <input type="number" class="border w-full p-2.5 rounded bg-gray-100 text-gray-500 text-sm font-mono" value="{{ $student->device_user_id }}" disabled>
            <p class="text-xs text-gray-400 mt-1">NIS menjadi kunci ID di mesin fingerprint.</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap Siswa</label>
            <input type="text" name="name" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" value="{{ $student->name }}" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-1">
                <i class="fab fa-whatsapp text-green-500 mr-1"></i> No. WhatsApp Orang Tua / Wali
            </label>
            <input type="text" name="parent_phone" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" value="{{ $student->parent_phone }}" placeholder="Contoh: 081234567890">
            <p class="text-xs text-gray-500 mt-1">Pesan notifikasi presensi otomatis dikirim ke nomor ini saat anak absen.</p>
        </div>

        <div class="flex space-x-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded shadow w-full transition text-sm">
                <i class="fas fa-save mr-1"></i> Simpan Perubahan
            </button>
            <a href="{{ route('students.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded border w-full text-center text-sm transition">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection