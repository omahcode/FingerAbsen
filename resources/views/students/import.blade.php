@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-2">Import Siswa dari Excel</h2>
    <p class="text-gray-600 mb-6 text-sm">Upload file Excel atau CSV untuk mendaftarkan ratusan siswa sekaligus ke database dan mesin fingerprint secara otomatis.</p>

    <div class="bg-blue-50 p-4 border border-blue-200 rounded mb-6">
        <h4 class="font-bold text-blue-800 mb-2">Cara Penggunaan:</h4>
        <ol class="list-decimal ml-5 text-sm text-blue-700 space-y-1">
            <li>Download template file di bawah ini.</li>
            <li>Isi kolom <strong>NIS</strong> (angka), <strong>Nama Siswa</strong>, dan <strong>Kelas</strong>.</li>
            <li>Pastikan <strong>Nama Kelas</strong> persis sama dengan yang ada di menu Kelas web ini (Contoh: <code class="bg-blue-100 px-1">X RPL 1</code>).</li>
            <li>Upload file yang sudah diisi ke form ini.</li>
        </ol>
        <div class="mt-4">
            <a href="{{ asset('template-siswa.csv') }}" class="text-blue-600 hover:underline font-semibold" download>⬇ Download Template CSV</a>
        </div>
    </div>

    <form action="{{ route('students.importExcel') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-6">
            <label class="block text-sm font-bold mb-2">Upload File (Excel/CSV)</label>
            <input type="file" name="file_excel" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="border w-full p-2 rounded" required>
        </div>

        <div class="flex items-center space-x-3">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded w-full">
                Upload & Mulai Proses
            </button>
            <a href="{{ route('students.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded w-full text-center">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection