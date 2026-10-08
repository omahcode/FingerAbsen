@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow border border-gray-100">
    <div class="flex items-center space-x-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
            <i class="fas fa-user-plus text-lg"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Siswa ke Mesin</h2>
            <p class="text-xs text-gray-500">Kirim langsung data siswa ke memori mesin dan database web.</p>
        </div>
    </div>

    <form action="{{ route('students.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Kelas</label>
            <select name="school_class_id" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                <option value="">-- Pilih Kelas --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->major->name ?? '' }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">NIS / ID Mesin (Angka Saja)</label>
            <input type="number" name="device_user_id" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Contoh: 10024" required>
            <p class="text-xs text-gray-500 mt-1">NIS ini digunakan sebagai PIN/User ID di mesin fingerprint.</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap Siswa</label>
            <input type="text" name="name" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Contoh: Ahmad Fauzan" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-1">
                <i class="fab fa-whatsapp text-green-500 mr-1"></i> No. WhatsApp Orang Tua / Wali
            </label>
            <input type="text" name="parent_phone" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Contoh: 081234567890 atau 6281234567890">
            <p class="text-xs text-gray-500 mt-1">Nomor ini akan otomatis menerima pesan WhatsApp saat siswa melakukan presensi masuk/pulang.</p>
        </div>

        <div class="flex space-x-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded shadow w-full transition text-sm">
                <i class="fas fa-save mr-1"></i> Simpan & Kirim ke Mesin
            </button>
            <a href="{{ route('students.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded border w-full text-center text-sm transition">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection