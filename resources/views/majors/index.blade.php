@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Data Jurusan</h2>
</div>
<div class="flex space-x-6">
    <div class="w-1/3">
        <div class="bg-white p-6 shadow rounded">
            <h3 class="font-bold mb-4">Tambah Jurusan</h3>
            <form action="{{ route('majors.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Nama Jurusan</label>
                    <input type="text" name="name" class="border w-full p-2 rounded" placeholder="Contoh: Rekayasa Perangkat Lunak" required>
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
                        <th class="p-3">Nama Jurusan</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($majors as $m)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">{{ $loop->iteration }}</td>
                        <td class="p-3">{{ $m->name }}</td>
                        <td class="p-3">
                            <form action="{{ route('majors.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Hapus jurusan ini?')">
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