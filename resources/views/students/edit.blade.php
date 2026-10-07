@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-2">Edit Data Siswa</h2>
    <p class="text-gray-600 mb-6 text-sm">NIS tidak dapat diedit karena menjadi kunci ID di mesin. Jika salah NIS, silakan hapus dan buat ulang.</p>

    <form action="{{ route('students.update', $student->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">Pilih Kelas Baru</label>
            <select name="school_class_id" class="border w-full p-2 rounded" required>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $student->school_class_id == $c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->major->name ?? '' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold mb-2 text-gray-500">NIS (Angka Saja) - Terkunci</label>
            <input type="number" class="border w-full p-2 rounded bg-gray-100 text-gray-500" value="{{ $student->device_user_id }}" disabled>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-bold mb-2">Nama Lengkap Siswa</label>
            <input type="text" name="name" class="border w-full p-2 rounded" value="{{ $student->name }}" required>
        </div>

        <div class="flex space-x-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded w-full">
                Simpan & Update ke Mesin
            </button>
            <a href="{{ route('students.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded w-full text-center">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection