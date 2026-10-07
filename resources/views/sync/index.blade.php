@extends('layouts.app')
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold">Sinkronisasi Data (Mode Manual / Darurat)</h2>
    <p class="text-gray-600 text-sm mt-1">Karena sistem sudah Realtime, tombol di bawah ini hanya digunakan saat web mati/putus koneksi dengan mesin dalam waktu yang lama, atau saat memasang mesin baru.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Card Tarik Siswa -->
    <div class="bg-white p-6 shadow rounded">
        <h3 class="font-bold mb-2 text-lg text-blue-700">1. Tarik Data Siswa</h3>
        <p class="text-sm text-gray-500 mb-4">Menarik daftar nama dan NIS dari memori mesin ke dalam database web.</p>
        <form action="{{ route('students.sync') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-bold mb-1">Pilih Mesin</label>
                <select name="device_id" class="border w-full p-2 rounded" required>
                    <option value="">-- Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
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

    <!-- Card Tarik Absen -->
    <div class="bg-white p-6 shadow rounded">
        <h3 class="font-bold mb-2 text-lg text-green-700">2. Tarik Data Absensi Tertinggal</h3>
        <p class="text-sm text-gray-500 mb-4">Menarik log sidik jari dari mesin yang belum sempat masuk ke web secara Realtime.</p>
        <form action="{{ route('attendance.sync') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">Pilih Mesin</label>
                <select name="device_id" class="border w-full p-2 rounded" required>
                    <option value="">-- Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
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