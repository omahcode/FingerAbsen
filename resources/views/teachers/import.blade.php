@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold mb-2 border border-emerald-100">
                <i class="fas fa-file-excel"></i> Import Massal Data Guru
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                Import Data Guru via Excel
            </h2>
            <p class="text-gray-500 text-sm mt-1 max-w-xl">
                Daftarkan seluruh data guru secara massal ke database dan antrean sinkronisasi mesin fingerprint menggunakan file spreadsheet (Excel / CSV).
            </p>
        </div>

        <div>
            <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2.5 rounded-xl text-sm transition">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl shadow-xs">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-rose-500 text-lg mr-3"></i>
                <p class="text-sm text-rose-700 font-semibold">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Template Section -->
    <div class="bg-gradient-to-br from-emerald-50 via-teal-50 to-green-50 p-6 border border-emerald-200 rounded-2xl shadow-xs">
        <h3 class="font-extrabold text-emerald-900 text-base mb-2 flex items-center gap-2">
            <i class="fas fa-table-columns text-emerald-600"></i> Struktur Kolom File Excel:
        </h3>
        <p class="text-xs text-emerald-800 mb-4">Pastikan susunan kolom pada file Excel Anda sesuai dengan panduan tabel di bawah ini:</p>

        <!-- Table Preview -->
        <div class="overflow-x-auto bg-white rounded-xl shadow-xs border border-emerald-200 mb-4">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="bg-emerald-700 text-white font-bold">
                        <th class="p-3 border border-emerald-700 text-center w-36">Kolom A (NIP)*</th>
                        <th class="p-3 border border-emerald-700">Kolom B (NAMA GURU)*</th>
                        <th class="p-3 border border-emerald-700 text-center w-36">Kolom C (NO HP)</th>
                        <th class="p-3 border border-emerald-700 text-center w-36">Kolom D (PIN MESIN)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 font-sans">
                    <tr class="hover:bg-emerald-50/40">
                        <td class="p-3 border border-gray-200 text-center font-mono font-bold text-gray-800">198005122005011003</td>
                        <td class="p-3 border border-gray-200 font-bold text-gray-900">Budi Santoso, S.Pd</td>
                        <td class="p-3 border border-gray-200 text-center font-mono text-gray-600">081234567890</td>
                        <td class="p-3 border border-gray-200 text-center font-mono text-gray-400"><i>(Boleh Kosong)</i></td>
                    </tr>
                    <tr class="hover:bg-emerald-50/40">
                        <td class="p-3 border border-gray-200 text-center font-mono font-bold text-gray-800">198308222008012011</td>
                        <td class="p-3 border border-gray-200 font-bold text-gray-900">Siti Aminah, M.Pd</td>
                        <td class="p-3 border border-gray-200 text-center font-mono text-gray-600">082198765432</td>
                        <td class="p-3 border border-gray-200 text-center font-mono text-gray-400"><i>(Boleh Kosong)</i></td>
                    </tr>
                    <tr class="hover:bg-emerald-50/40">
                        <td class="p-3 border border-gray-200 text-center font-mono font-bold text-gray-800">199003152019031008</td>
                        <td class="p-3 border border-gray-200 font-bold text-gray-900">Rizky Pratama, S.Kom</td>
                        <td class="p-3 border border-gray-200 text-center font-mono text-gray-400"><i>(Boleh Kosong)</i></td>
                        <td class="p-3 border border-gray-200 text-center font-mono text-gray-400"><i>(Boleh Kosong)</i></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ul class="text-xs text-emerald-800 space-y-1 mb-4">
            <li>• Kolom bertanda bintang <strong>(*)</strong> wajib diisi.</li>
            <li>• Kolom <strong>PIN MESIN</strong> jika dikosongkan akan otomatis disamakan dengan <strong>NIP</strong>.</li>
            <li>• Kolom <strong>NO HP</strong> bersifat opsional.</li>
        </ul>

        <!-- Download Buttons -->
        <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-emerald-200">
            <a href="{{ route('teachers.downloadTemplate', ['format' => 'xlsx']) }}" class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md transition active:scale-95">
                <i class="fas fa-download"></i> Download Template Excel (.xlsx)
            </a>
            <a href="{{ route('teachers.downloadTemplate', ['format' => 'csv']) }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-emerald-800 border border-emerald-300 text-xs font-bold py-2.5 px-4 rounded-xl shadow-sm transition active:scale-95">
                <i class="fas fa-file-csv text-emerald-600"></i> Download Template CSV (.csv)
            </a>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-gray-100">
        <form action="{{ route('teachers.importExcel') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="file_excel" class="block text-sm font-bold text-gray-800 mb-2">
                    Unggah File Excel atau CSV <span class="text-rose-500">*</span>
                </label>
                <input type="file" id="file_excel" name="file_excel" accept=".xlsx, .xls, .csv" required class="block w-full text-sm text-gray-700 border border-gray-300 rounded-xl cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-3 file:px-5 file:rounded-l-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-700 file:text-white hover:file:bg-emerald-800 transition">
                <p class="text-xs text-gray-400 mt-2">Mendukung format file <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code> (Maksimal ukuran 4 MB).</p>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('teachers.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold px-6 py-2.5 rounded-xl shadow-md transition active:scale-95 text-sm">
                    <i class="fas fa-cloud-upload-alt text-base"></i>
                    <span>Mulai Impor Data Guru</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
