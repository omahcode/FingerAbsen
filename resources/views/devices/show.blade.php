@extends('layouts.app')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('devices.index') }}" class="text-xs font-semibold text-gray-500 hover:text-indigo-600 transition flex items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Mesin
                </a>
                <span class="text-gray-300">•</span>
                <span class="text-xs text-indigo-600 font-bold uppercase tracking-wider">Inspeksi Data Mesin</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <span>{{ $device->name }}</span>
                @if($device->status == 'online')
                    <span class="inline-flex items-center bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                        Online
                    </span>
                @else
                    <span class="inline-flex items-center bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full mr-1.5"></span>
                        Offline
                    </span>
                @endif
            </h2>
            <p class="text-xs text-gray-500 mt-1 font-mono">
                SN: <span class="text-gray-800 font-semibold">{{ $device->serial_number ?? 'Belum Diatur' }}</span> | IP: {{ $device->ip_address }}:{{ $device->port }} | Peruntukan: <strong class="text-indigo-700">{{ $device->purpose_label }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form action="{{ route('devices.pull_data', $device->id) }}" method="POST" data-confirm="Kirim perintah ke mesin fisik untuk mengecek dan mengunggah daftar user terbarunya?" data-title="Tarik Data dari Mesin Fisik" data-icon="question">
                @csrf
                <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold py-2 px-3.5 rounded-lg shadow-sm transition text-xs flex items-center gap-1.5">
                    <i class="fas fa-cloud-download-alt"></i> Tarik Data Mesin
                </button>
            </form>
            <a href="{{ route('sync.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-3.5 rounded-lg shadow-sm transition text-xs flex items-center gap-1.5">
                <i class="fas fa-paper-plane"></i> Sebar Data ke Mesin Ini
            </a>
            <a href="{{ route('devices.edit', $device->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-3 rounded-lg text-xs transition">
                <i class="fas fa-cog"></i>
            </a>
        </div>
    </div>
</div>

<!-- Kartu Ringkasan Komposisi Data di Mesin Ini -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-xs text-gray-500 font-medium">Total Data di Mesin Ini</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $totalUsers }} <span class="text-xs font-normal text-gray-500">Orang</span></div>
        <div class="text-[11px] text-gray-400 mt-1">Terdaftar di memory mesin ini</div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-xs text-blue-600 font-semibold flex items-center justify-between">
            <span>🎓 Data Siswa</span>
            <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full font-bold">
                {{ $totalUsers > 0 ? round(($totalStudents / $totalUsers) * 100) : 0 }}%
            </span>
        </div>
        <div class="text-2xl font-bold text-blue-700 mt-1">{{ $totalStudents }} <span class="text-xs font-normal text-blue-400">Siswa</span></div>
        <div class="text-[11px] text-gray-400 mt-1">
            @if($device->isForTeachers())
                <span class="text-amber-600 font-medium">⚠️ Mesin ini khusus upacara guru</span>
            @else
                Siap untuk absensi siswa
            @endif
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-xs text-rose-600 font-semibold flex items-center justify-between">
            <span>👨‍🏫 Data Dewan Guru</span>
            <span class="text-[10px] bg-rose-50 text-rose-700 px-2 py-0.5 rounded-full font-bold">
                {{ $totalUsers > 0 ? round(($totalTeachers / $totalUsers) * 100) : 0 }}%
            </span>
        </div>
        <div class="text-2xl font-bold text-rose-700 mt-1">{{ $totalTeachers }} <span class="text-xs font-normal text-rose-400">Guru</span></div>
        <div class="text-[11px] text-gray-400 mt-1">
            @if($device->isForTeachers())
                <span class="text-emerald-600 font-medium">✓ Sesuai peruntukan mesin</span>
            @else
                Guru & staf pengajar
            @endif
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-xs text-emerald-600 font-semibold">Komposisi Data Mesin</div>
        <div class="mt-1 font-bold text-sm text-gray-800 truncate">
            @if($totalUsers === 0)
                <span class="text-gray-400">Belum Ada Data</span>
            @elseif($totalStudents === 0 && $totalTeachers > 0)
                <span class="text-rose-600">Khusus Guru (100%)</span>
            @elseif($totalTeachers === 0 && $totalStudents > 0)
                <span class="text-blue-600">Khusus Siswa (100%)</span>
            @else
                <span class="text-indigo-600">Campuran (Siswa & Guru)</span>
            @endif
        </div>
        <div class="text-[11px] text-gray-500 mt-1 truncate">
            {{ $device->purpose_label }}
        </div>
    </div>
</div>

@if($classBreakdown->isNotEmpty())
<!-- Pills Rincian Kelas yang Ada di Mesin Ini -->
<div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-6">
    <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-2">
        <i class="fas fa-layer-group text-indigo-500"></i>
        <span>Rincian Kelas Siswa di Mesin Ini:</span>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($classBreakdown as $cb)
            <a href="{{ route('devices.show', [$device->id, 'type' => 'students', 'school_class_id' => $cb->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold {{ request('school_class_id') == $cb->id ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition">
                <span>{{ $cb->name }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ request('school_class_id') == $cb->id ? 'bg-white/30 text-white' : 'bg-gray-200 text-gray-700' }} font-bold">
                    {{ $cb->count }}
                </span>
            </a>
        @endforeach
        @if(request('school_class_id'))
            <a href="{{ route('devices.show', [$device->id, 'type' => 'students']) }}" class="inline-flex items-center gap-1 text-xs text-rose-600 hover:underline font-semibold ml-2">
                <i class="fas fa-times"></i> Reset Filter Kelas
            </a>
        @endif
    </div>
</div>
@endif

<!-- Tabel Data Pengguna di Mesin Ini -->
<div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-8">
    <!-- Filter Bar & Search -->
    <div class="p-4 bg-gray-50/70 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <!-- Tab Pills Tipe -->
        <div class="flex items-center gap-1.5 bg-gray-200/70 p-1 rounded-xl text-xs font-semibold w-full md:w-auto">
            <a href="{{ route('devices.show', [$device->id, 'type' => 'all', 'search' => request('search')]) }}" class="px-3.5 py-1.5 rounded-lg transition {{ $type === 'all' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                Semua ({{ $totalUsers }})
            </a>
            <a href="{{ route('devices.show', [$device->id, 'type' => 'students', 'search' => request('search')]) }}" class="px-3.5 py-1.5 rounded-lg transition {{ $type === 'students' ? 'bg-white text-blue-700 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                🎓 Siswa ({{ $totalStudents }})
            </a>
            <a href="{{ route('devices.show', [$device->id, 'type' => 'teachers', 'search' => request('search')]) }}" class="px-3.5 py-1.5 rounded-lg transition {{ $type === 'teachers' ? 'bg-white text-rose-700 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                👨‍🏫 Dewan Guru ({{ $totalTeachers }})
            </a>
        </div>

        <!-- Form Pencarian -->
        <form method="GET" action="{{ route('devices.show', $device->id) }}" class="flex items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="type" value="{{ $type }}">
            @if(request('school_class_id'))
                <input type="hidden" name="school_class_id" value="{{ request('school_class_id') }}">
            @endif
            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, PIN, atau NIS..." class="w-full bg-white border border-gray-300 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
            </div>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-3 py-1.5 rounded-lg text-xs border transition">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('devices.show', [$device->id, 'type' => $type]) }}" class="text-xs text-rose-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel User di Mesin -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b text-gray-600 uppercase tracking-wider text-[11px]">
                    <th class="py-3 px-4 font-bold">PIN / ID Mesin</th>
                    <th class="py-3 px-4 font-bold">Nama Pengguna</th>
                    <th class="py-3 px-4 font-bold">Tipe</th>
                    <th class="py-3 px-4 font-bold">Identitas / Kelas</th>
                    <th class="py-3 px-4 font-bold text-center">Status Sidik Jari</th>
                    <th class="py-3 px-4 font-bold">Waktu Sinkron</th>
                    <th class="py-3 px-4 font-bold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($deviceUsers as $du)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 font-mono font-bold text-indigo-700">
                        {{ $du->device_user_id }}
                    </td>
                    <td class="py-3 px-4 font-medium text-gray-900">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0 {{ $du->user_type === 'teacher' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $du->user_type === 'teacher' ? '👨‍🏫' : '🎓' }}
                            </div>
                            <div>
                                <span class="font-bold text-gray-900 block">{{ $du->name }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        @if($du->user_type === 'teacher')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                Dewan Guru
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                Siswa
                            </span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-600">
                        @if($du->user_type === 'teacher')
                            <span class="font-mono text-xs">NIP: {{ $du->teacher->nip ?? '-' }}</span>
                        @else
                            <span class="font-semibold text-gray-800">{{ $du->student->schoolClass->name ?? '-' }}</span>
                            <span class="text-gray-400 block text-[10px] font-mono">NIS: {{ $du->student->nis ?? '-' }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        @php
                            $fpCount = $du->fingerprintTemplates->count();
                        @endphp
                        @if($fpCount > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <i class="fas fa-fingerprint"></i> {{ $fpCount }} Sidik Jari
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500">
                                Belum Ada
                            </span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-500 text-[11px]">
                        {{ $du->synced_at ? $du->synced_at->translatedFormat('d M Y H:i') : '-' }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        <form action="{{ route('devices.remove_user', [$device->id, $du->device_user_id]) }}" method="POST" data-confirm="Hapus {{ $du->name }} (PIN: {{ $du->device_user_id }}) HANYA dari mesin '{{ $device->name }}'? Data master di web tidak akan dihapus." data-title="Hapus dari Mesin Ini" data-danger="true" data-icon="warning">
                            @csrf
                            <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 py-1 px-2.5 rounded text-[11px] font-semibold transition" title="Hapus pengguna dari mesin ini">
                                <i class="fas fa-trash-alt mr-0.5"></i> Hapus dari Mesin
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-400">
                        <i class="fas fa-users-slash text-3xl mb-2 text-gray-300"></i>
                        <p class="font-medium text-gray-600">Belum ada data pengguna yang dialokasikan di mesin ini.</p>
                        <p class="text-xs text-gray-400 mt-1">Gunakan fitur <a href="{{ route('sync.index') }}" class="text-indigo-600 font-bold hover:underline">Sebar Data</a> untuk menyebarkan data siswa atau guru ke mesin ini.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($deviceUsers->hasPages())
    <div class="p-4 border-t border-gray-200 bg-gray-50">
        {{ $deviceUsers->links() }}
    </div>
    @endif
</div>

<!-- Panel 10 Riwayat Tap Terakhir di Mesin Ini -->
<div class="bg-white p-6 shadow-sm rounded-xl border border-gray-200">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Riwayat 10 Tap Terakhir di Mesin {{ $device->name }}</span>
            </h3>
            <p class="text-xs text-gray-500">Melihat siapa saja yang baru saja melakukan absensi di mesin fisik ini.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b text-gray-600">
                    <th class="p-2.5">Waktu Tap</th>
                    <th class="p-2.5">PIN</th>
                    <th class="p-2.5">Nama</th>
                    <th class="p-2.5">Tipe & Kelas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentLogs as $log)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2.5 font-mono text-gray-600">{{ $log->timestamp }}</td>
                    <td class="p-2.5 font-mono font-bold text-indigo-700">{{ $log->device_user_id }}</td>
                    <td class="p-2.5 font-semibold text-gray-900">
                        {{ $log->teacher->name ?? $log->student->name ?? 'User '.$log->device_user_id }}
                    </td>
                    <td class="p-2.5">
                        @if($log->teacher)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700">Dewan Guru</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">{{ $log->student->schoolClass->name ?? 'Siswa' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="p-4 text-center text-gray-400">Belum ada riwayat tap di mesin ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
