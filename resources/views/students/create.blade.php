@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-2">Tambah Siswa ke Mesin</h2>
    <p class="text-gray-600 mb-6 text-sm">Kirim langsung data siswa ke memori mesin. NIS akan dijadikan sebagai ID di dalam mesin fingerprint.</p>

    <form action="{{ route('students.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">Pilih Kelas</label>
            <select name="school_class_id" class="border w-full p-2 rounded" required>
                <option value="">-- Pilih Kelas --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->major->name ?? '' }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">NIS (Angka Saja)</label>
            <input type="number" name="device_user_id" class="border w-full p-2 rounded" placeholder="Contoh: 10024" required>
            <p class="text-xs text-gray-500 mt-1">NIS ini langsung dijadikan sebagai ID di mesin fingerprint.</p>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-bold mb-2">Nama Lengkap Siswa</label>
            <input type="text" name="name" class="border w-full p-2 rounded" required>
        </div>

        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded w-full">
            Simpan & Kirim ke Mesin
        </button>
    </form>
</div>
@endsection