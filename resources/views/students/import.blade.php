@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-xl shadow-md border border-gray-100">
    <div class="flex items-center justify-between mb-4 border-b pb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-file-excel text-green-600"></i> Import Data Siswa
            </h2>
            <p class="text-gray-500 text-sm mt-1">Daftarkan siswa secara massal langsung ke database dan sinkronkan ke seluruh mesin fingerprint.</p>
        </div>
        <a href="{{ route('students.index') }}" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r">
            <div class="flex">
                <div class="shrink-0"><i class="fas fa-exclamation-circle text-red-500"></i></div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Template Section -->
    <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-5 border border-green-200 rounded-xl mb-6">
        <h4 class="font-bold text-green-900 mb-2 flex items-center gap-2">
            <i class="fas fa-table-columns text-green-600"></i> Struktur Kolom Template Excel:
        </h4>
        <p class="text-xs text-green-800 mb-3">Pastikan file Excel Anda memiliki susunan kolom seperti tabel di bawah ini:</p>
        
        <!-- Table Preview -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-green-200 mb-4">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="bg-green-600 text-white font-semibold">
                        <th class="p-2 border border-green-600 text-center w-28">Kolom A (NIS)</th>
                        <th class="p-2 border border-green-600">Kolom B (NAMA SISWA)</th>
                        <th class="p-2 border border-green-600 text-center w-36">Kolom C (KELAS)</th>
                        <th class="p-2 border border-green-600 text-center text-green-100 w-36">Kolom D (NO HP ORTU)*</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <tr class="hover:bg-green-50/50">
                        <td class="p-2 border border-gray-200 text-center font-mono font-medium text-blue-600">1001</td>
                        <td class="p-2 border border-gray-200 font-medium">Ahmad Dahlan</td>
                        <td class="p-2 border border-gray-200 text-center"><span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-mono">{{ $classes->first()->name ?? 'X RPL 1' }}</span></td>
                        <td class="p-2 border border-gray-200 text-center text-gray-400">081234567890</td>
                    </tr>
                    <tr class="hover:bg-green-50/50">
                        <td class="p-2 border border-gray-200 text-center font-mono font-medium text-blue-600">1002</td>
                        <td class="p-2 border border-gray-200 font-medium">Budi Santoso</td>
                        <td class="p-2 border border-gray-200 text-center"><span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-mono">{{ $classes->first()->name ?? 'X RPL 1' }}</span></td>
                        <td class="p-2 border border-gray-200 text-center text-gray-400"><i>(Boleh Kosong)</i></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-[11px] text-green-700 mb-3">* Kolom <strong>NO HP ORTU</strong> bersifat opsional dan dapat diisi kemudian secara manual melalui menu edit siswa.</p>

        <!-- Download Buttons -->
        <div class="flex flex-wrap gap-2 pt-2 border-t border-green-200/60">
            <a href="{{ route('students.downloadTemplate', ['format' => 'xlsx']) }}" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3.5 rounded-lg shadow-sm transition">
                <i class="fas fa-download"></i> Download Template Excel (.xlsx)
            </a>
            <a href="{{ route('students.downloadTemplate', ['format' => 'csv']) }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-green-800 border border-green-300 text-xs font-semibold py-2 px-3.5 rounded-lg shadow-sm transition">
                <i class="fas fa-file-csv"></i> Download Template CSV
            </a>
        </div>
    </div>

    <!-- Available Classes Reference -->
    @if(isset($classes) && $classes->count() > 0)
    <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-xl">
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
            <i class="fas fa-tags text-indigo-500 mr-1"></i> Referensi Nama Kelas Terdaftar di Sistem:
        </label>
        <p class="text-xs text-gray-500 mb-2">Tuliskan nama kelas pada file Excel persis seperti daftar berikut:</p>
        <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto p-1 bg-white border border-gray-200 rounded-lg">
            @foreach($classes as $c)
                <span class="inline-block bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs px-2 py-0.5 rounded font-medium">
                    {{ $c->name }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Upload Form -->
    <form action="{{ route('students.importExcel') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">
                Pilih File Excel / CSV <span class="text-red-500">*</span>
            </label>
            <input type="file" name="file_excel" accept=".xlsx, .xls, .csv" class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2.5 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-green-600 file:text-white hover:file:bg-green-700" required>
            <p class="text-xs text-gray-500 mt-1">Mendukung format .xlsx, .xls, dan .csv (Maksimal 4 MB).</p>
        </div>

        <div class="pt-3 flex items-center gap-3">
            <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg shadow transition flex items-center justify-center gap-2">
                <i class="fas fa-upload"></i> Upload & Mulai Import
            </button>
            <a href="{{ route('students.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2.5 px-5 rounded-lg transition">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection