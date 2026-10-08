@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Rekapitulasi Kehadiran Siswa</h2>
        <p class="text-xs text-gray-500 mt-1">Laporan rekap presensi per kelas dengan statistik kehadiran dan ekspor dokumen PDF resmi.</p>
    </div>
    
    <!-- Action Export Buttons -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('reports.pdf', ['class_id' => $selectedClassId, 'start_date' => $startDate, 'end_date' => $endDate, 'stream' => 1]) }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-sm transition text-sm flex items-center">
            <i class="fas fa-file-pdf mr-1.5"></i> Cetak / Preview PDF
        </a>
        <a href="{{ route('reports.pdf', ['class_id' => $selectedClassId, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded shadow-sm transition text-sm flex items-center">
            <i class="fas fa-download mr-1.5"></i> Download PDF
        </a>
        <a href="{{ route('reports.matrix_pdf', ['class_id' => $selectedClassId, 'month' => substr($startDate, 0, 7), 'stream' => 1]) }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow-sm transition text-sm flex items-center">
            <i class="fas fa-calendar-alt mr-1.5"></i> PDF Matriks Bulanan
        </a>
    </div>
</div>

<!-- Filter Panel -->
<div class="bg-white p-5 shadow rounded-lg mb-6 border border-gray-100">
    <form action="{{ route('reports.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Kelas</label>
            <select name="class_id" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="this.form.submit()">
                <option value="all" {{ $selectedClassId === 'all' ? 'selected' : '' }}>-- Semua Kelas --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ (string)$selectedClassId === (string)$c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->major->name ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="border border-gray-300 w-full p-2.5 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded shadow-sm w-full text-sm transition">
                <i class="fas fa-filter mr-1"></i> Terapkan Filter
            </button>
            <a href="{{ route('reports.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-3 rounded border text-sm transition">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Statistik Ringkasan -->
@php
    $totalStudents = count($reportData);
    $avgPercentage = $totalStudents > 0 ? round(collect($reportData)->avg('percentage'), 1) : 0;
    $perfectAttendance = collect($reportData)->where('percentage', '>=', 90)->count();
@endphp
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-600">
        <span class="text-xs text-gray-500 block uppercase font-bold">Kelas Terpilih</span>
        <span class="text-lg font-bold text-gray-800">{{ $selectedClass ? $selectedClass->name : 'Semua Kelas' }}</span>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-indigo-600">
        <span class="text-xs text-gray-500 block uppercase font-bold">Total Siswa</span>
        <span class="text-2xl font-bold text-indigo-700">{{ $totalStudents }} Siswa</span>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-600">
        <span class="text-xs text-gray-500 block uppercase font-bold">Rata-Rata Kehadiran</span>
        <span class="text-2xl font-bold text-green-600">{{ $avgPercentage }}%</span>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-purple-600">
        <span class="text-xs text-gray-500 block uppercase font-bold">Siswa Rajin (≥90%)</span>
        <span class="text-2xl font-bold text-purple-700">{{ $perfectAttendance }} Siswa</span>
    </div>
</div>

<!-- Tabel Laporan Siswa -->
<div class="bg-white shadow rounded-lg overflow-x-auto p-4 border border-gray-100">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b text-gray-700 text-sm">
                <th class="p-3 w-12 text-center">No</th>
                <th class="p-3">NIS / PIN</th>
                <th class="p-3">Nama Siswa</th>
                <th class="p-3">Kelas</th>
                <th class="p-3 text-center">Hari Hadir</th>
                <th class="p-3 text-center">Persentase</th>
                <th class="p-3 text-center">Status Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $idx => $row)
            <tr class="border-b hover:bg-gray-50 transition">
                <td class="p-3 text-center text-gray-500 text-sm">{{ $idx + 1 }}</td>
                <td class="p-3 font-mono text-xs text-gray-600">{{ $row['student']->device_user_id }}</td>
                <td class="p-3 font-semibold text-gray-900">{{ $row['student']->name }}</td>
                <td class="p-3 text-sm text-gray-600">{{ $row['student']->schoolClass->name ?? '-' }}</td>
                <td class="p-3 text-center">
                    <span class="inline-block bg-blue-50 text-blue-700 font-bold px-2.5 py-1 rounded text-xs border border-blue-200">
                        {{ $row['total_present'] }} / {{ $row['total_days'] }} Hari
                    </span>
                </td>
                <td class="p-3 text-center">
                    <div class="flex items-center justify-center space-x-2">
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $row['percentage'] >= 75 ? 'bg-green-500' : ($row['percentage'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ min($row['percentage'], 100) }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-gray-700">{{ $row['percentage'] }}%</span>
                    </div>
                </td>
                <td class="p-3 text-center text-xs">
                    @if($row['latest_log'])
                        <span class="text-gray-600 font-medium">{{ \Carbon\Carbon::parse($row['latest_log']->timestamp)->translatedFormat('d M, H:i') }}</span>
                    @else
                        <span class="text-gray-400 italic">Belum ada absen</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-8 text-center text-gray-400">
                    <i class="fas fa-folder-open text-3xl mb-2 block"></i>
                    Tidak ada data siswa ditemukan untuk filter kelas ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
