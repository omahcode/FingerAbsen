@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Data Jurusan</h2>
        <p class="text-xs text-gray-500 mt-1">Kelola data kompetensi keahlian / jurusan sekolah.</p>
    </div>
</div>
<div class="flex flex-col md:flex-row gap-6">
    <div class="w-full md:w-1/3">
        <div class="bg-white p-6 shadow rounded-lg border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4 text-base"><i class="fas fa-plus-circle text-blue-600 mr-1"></i> Tambah Jurusan</h3>
            <form action="{{ route('majors.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Jurusan</label>
                    <input type="text" name="name" class="border border-gray-300 w-full p-2.5 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm" placeholder="Contoh: Rekayasa Perangkat Lunak" required>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded font-bold w-full shadow transition">
                    Simpan Jurusan
                </button>
            </form>
        </div>
    </div>
    <div class="w-full md:w-2/3">
        <div class="bg-white shadow rounded-lg overflow-x-auto p-4 border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50 text-gray-700 text-sm">
                        <th class="p-3 w-16">No</th>
                        <th class="p-3">Nama Jurusan</th>
                        <th class="p-3 w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($majors as $m)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-3 text-gray-500">{{ $loop->iteration }}</td>
                        <td class="p-3 font-semibold text-gray-900">{{ $m->name }}</td>
                        <td class="p-3 text-center">
                            <form action="{{ route('majors.destroy', $m->id) }}" method="POST" data-confirm="Hapus jurusan '{{ $m->name }}'?" data-title="Hapus Jurusan" data-danger="true" data-icon="warning">
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs py-1 px-2.5 rounded bg-red-50 hover:bg-red-100 border border-red-200 transition">
                                    <i class="fas fa-trash mr-0.5"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="p-6 text-center text-gray-400">Belum ada jurusan yang ditambahkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection