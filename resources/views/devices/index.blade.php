@extends('layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Daftar Mesin Fingerprint</h2>
        <p class="text-xs text-gray-500 mt-1">Kelola dan inspeksi isi data mesin fingerprint fisik (Solution / ZKTeco) yang terhubung via ADMS Cloud.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sync.index') }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold py-2 px-3 sm:px-4 rounded-lg shadow-sm transition text-xs sm:text-sm flex items-center gap-1.5">
            <i class="fas fa-sync-alt"></i> Pusat Sebar Data
        </a>
        <form action="{{ route('devices.sync_time_all') }}" method="POST" data-confirm="Samakan jam SEMUA mesin fingerprint dengan jam komputer server sekarang?" data-title="Sinkronisasi Jam Semua Mesin" data-icon="question">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 sm:px-4 rounded-lg shadow transition text-xs sm:text-sm">
                <i class="fas fa-clock mr-1"></i> Sync Jam Mesin
            </button>
        </form>
        <a href="{{ route('devices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-3 sm:px-4 rounded-lg shadow transition text-xs sm:text-sm">
            <i class="fas fa-plus mr-1"></i> Tambah Mesin
        </a>
    </div>
</div>

<div class="bg-white shadow rounded-xl overflow-x-auto border border-gray-100">
    <table class="text-left w-full border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b text-gray-700 text-xs uppercase tracking-wider font-bold">
                <th class="py-3 px-5">Nama Mesin</th>
                <th class="py-3 px-5">Peruntukan</th>
                <th class="py-3 px-5">Isi Data Mesin</th>
                <th class="py-3 px-5">IP & Port</th>
                <th class="py-3 px-5">Status</th>
                <th class="py-3 px-5">Terakhir Aktif</th>
                <th class="py-3 px-5 text-center">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-xs">
            @forelse($devices as $device)
            <tr class="hover:bg-gray-50 transition">
                <td class="py-3.5 px-5 font-semibold text-gray-900">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-sm shrink-0">
                            📟
                        </div>
                        <div>
                            <a href="{{ route('devices.show', $device->id) }}" class="font-bold text-gray-900 hover:text-indigo-600 hover:underline block text-sm">
                                {{ $device->name }}
                            </a>
                            @if($device->serial_number)
                                <span class="text-[11px] text-gray-400 font-mono block">SN: {{ $device->serial_number }}</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="py-3.5 px-5">
                    @if($device->isForTeachers())
                        <span class="inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200 py-1 px-2.5 rounded-lg text-xs font-bold" title="Khusus presensi upacara guru. Tap siswa otomatis ditolak.">
                            <i class="fas fa-flag text-rose-500"></i>
                            Khusus Upacara Guru
                        </span>
                    @elseif($device->isForStudents())
                        <span class="inline-flex items-center gap-1.5 bg-sky-50 text-sky-700 border border-sky-200 py-1 px-2.5 rounded-lg text-xs font-bold">
                            <i class="fas fa-user-graduate text-sky-500"></i>
                            Khusus Siswa
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 py-1 px-2.5 rounded-lg text-xs font-medium">
                            <i class="fas fa-users text-gray-400"></i>
                            Umum (Siswa & Guru)
                        </span>
                    @endif
                </td>
                <td class="py-3.5 px-5">
                    @php
                        $uCount = $device->total_users_count ?? 0;
                        $sCount = $device->students_count ?? 0;
                        $tCount = $device->teachers_count ?? 0;
                    @endphp
                    @if($uCount > 0)
                        <div class="space-y-0.5">
                            <span class="font-bold text-gray-800">{{ $uCount }} Orang</span>
                            <div class="flex items-center gap-1 text-[10px]">
                                @if($sCount > 0)
                                    <span class="bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-semibold">{{ $sCount }} Siswa</span>
                                @endif
                                @if($tCount > 0)
                                    <span class="bg-rose-50 text-rose-700 px-1.5 py-0.5 rounded font-semibold">{{ $tCount }} Guru</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <span class="text-gray-400 text-xs italic">Belum Ada Data</span>
                    @endif
                </td>
                <td class="py-3.5 px-5 font-mono text-gray-600">{{ $device->ip_address }}:{{ $device->port }}</td>
                <td class="py-3.5 px-5">
                    @if($device->status == 'online')
                        <span class="inline-flex items-center bg-green-100 text-green-800 py-0.5 px-2.5 rounded-full text-xs font-semibold">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>
                            Online
                        </span>
                    @else
                        <span class="inline-flex items-center bg-red-100 text-red-800 py-0.5 px-2.5 rounded-full text-xs font-semibold">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>
                            Offline
                        </span>
                    @endif
                </td>
                <td class="py-3.5 px-5 text-gray-500">
                    {{ $device->last_seen_at ? \Carbon\Carbon::parse($device->last_seen_at)->diffForHumans() : '-' }}
                </td>
                <td class="py-3.5 px-5 text-center">
                    <div class="flex justify-center items-center gap-1">
                        <!-- Tombol Utama: Cek Isi Data Mesin -->
                        <a href="{{ route('devices.show', $device->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs py-1.5 px-2.5 rounded font-bold shadow-sm transition inline-flex items-center gap-1" title="Lihat daftar siswa dan guru di mesin ini">
                            <i class="fas fa-eye"></i> Cek Data
                        </a>

                        <form action="{{ route('devices.check', $device->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs py-1.5 px-2 rounded font-medium shadow-sm transition" title="Test Koneksi Mesin">
                                <i class="fas fa-signal"></i>
                            </button>
                        </form>

                        <form action="{{ route('devices.sync_time', $device->id) }}" method="POST" data-confirm="Samakan jam mesin '{{ $device->name }}' dengan jam server?" data-title="Sync Jam Mesin" data-icon="question">
                            @csrf
                            <button type="submit" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs py-1.5 px-2 rounded font-medium shadow-sm transition" title="Sync Jam">
                                <i class="fas fa-clock"></i>
                            </button>
                        </form>

                        <a href="{{ route('devices.edit', $device->id) }}" class="bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-xs py-1.5 px-2 rounded font-medium shadow-sm transition" title="Edit Pengaturan Mesin">
                            <i class="fas fa-edit"></i>
                        </a>

                        <form action="{{ route('devices.clear_admin', $device->id) }}" method="POST" data-confirm="Buka gembok menu di mesin '{{ $device->name }}' (Hapus hak admin mesin)?" data-title="Buka Kunci Admin Mesin" data-icon="warning">
                            @csrf
                            <button type="submit" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-800 border border-yellow-300 text-xs py-1.5 px-2 rounded font-medium shadow-sm transition" title="Buka Kunci Mesin">
                                <i class="fas fa-unlock"></i>
                            </button>
                        </form>

                        <form action="{{ route('devices.destroy', $device->id) }}" method="POST" data-confirm="Hapus data mesin '{{ $device->name }}' dari sistem?" data-title="Hapus Mesin" data-danger="true" data-icon="warning">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-xs py-1.5 px-2 rounded font-medium shadow-sm transition" title="Hapus Mesin">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-8 px-6 text-center text-gray-400">Belum ada mesin yang ditambahkan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection