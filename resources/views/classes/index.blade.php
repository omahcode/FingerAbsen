@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Data Kelas</h2>
</div>
<div class="flex space-x-6">
    <div class="w-1/3">
        <div class="bg-white p-6 shadow rounded">
            <h3 class="font-bold mb-4">Tambah Kelas</h3>
            <form action="{{ route('classes.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Pilih Jurusan</label>
                    <select name="major_id" class="border w-full p-2 rounded" required>
                        <option value="">-- Pilih Jurusan --</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Nama Kelas</label>
                    <input type="text" name="name" class="border w-full p-2 rounded" placeholder="Contoh: X RPL 1" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded font-bold w-full">Simpan</button>
            </form>
        </div>
    </div>
    <div class="w-2/3">
        <div class="bg-white shadow rounded overflow-x-auto p-4">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="p-3">No</th>
                        <th class="p-3">Jurusan</th>
                        <th class="p-3">Nama Kelas</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($classes as $c)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">{{ $loop->iteration }}</td>
                        <td class="p-3">{{ $c->major->name ?? '-' }}</td>
                        <td class="p-3">{{ $c->name }}</td>
                        <td class="p-3">
                            <form action="{{ route('classes.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Hapus kelas ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection