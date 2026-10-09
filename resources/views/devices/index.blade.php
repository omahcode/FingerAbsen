@extends('layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Daftar Mesin Fingerprint</h2>
        <p class="text-xs text-gray-500 mt-1">Kelola mesin fingerprint fisik (Solution / ZKTeco) yang terhubung via ADMS Cloud.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form action="{{ route('devices.sync_time_all') }}" method="POST" data-confirm="Samakan jam SEMUA mesin fingerprint dengan jam komputer server sekarang?" data-title="Sinkronisasi Jam Semua Mesin" data-icon="question">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 sm:px-4 rounded shadow transition text-xs sm:text-sm">
                <i class="fas fa-clock mr-1"></i> Sync Jam Mesin
            </button>
        </form>
        <a href="{{ route('devices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-3 sm:px-4 rounded shadow transition text-xs sm:text-sm">
            <i class="fas fa-plus mr-1"></i> Tambah Mesin
        </a>
    </div>
</div>

<div class="bg-white shadow rounded-lg overflow-x-auto border border-gray-100">
    <table class="text-left w-full border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b text-gray-700 text-sm">
                <th class="py-3 px-6 font-semibold">Nama Mesin</th>
                <th class="py-3 px-6 font-semibold">IP & Port</th>
                <th class="py-3 px-6 font-semibold">Status</th>
                <th class="py-3 px-6 font-semibold">Terakhir Aktif</th>
                <th class="py-3 px-6 font-semibold text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $device)
            <tr class="hover:bg-gray-50 border-b transition">
                <td class="py-3.5 px-6 font-semibold text-gray-900">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-hdd text-gray-400"></i>
                        <div>
                            <span>{{ $device->name }}</span>
                            @if($device->serial_number)
                                <span class="text-xs text-gray-400 font-mono block">SN: {{ $device->serial_number }}</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="py-3.5 px-6 font-mono text-xs text-gray-600">{{ $device->ip_address }}:{{ $device->port }}</td>
                <td class="py-3.5 px-6">
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
                <td class="py-3.5 px-6 text-xs text-gray-500">
                    {{ $device->last_seen_at ? \Carbon\Carbon::parse($device->last_seen_at)->diffForHumans() : '-' }}
                </td>
                <td class="py-3.5 px-6 text-center">
                    <div class="flex justify-center space-x-1.5">
                        <form action="{{ route('devices.check', $device->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs py-1.5 px-2.5 rounded font-medium shadow-sm transition">
                                <i class="fas fa-signal mr-0.5"></i> Test
                            </button>
                        </form>
                        <form action="{{ route('devices.sync_time', $device->id) }}" method="POST" data-confirm="Samakan jam mesin '{{ $device->name }}' dengan jam server?" data-title="Sync Jam Mesin" data-icon="question">
                            @csrf
                            <button type="submit" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs py-1.5 px-2.5 rounded font-medium shadow-sm transition">
                                <i class="fas fa-clock mr-0.5"></i> Sync Jam
                            </button>
                        </form>
                        <form action="{{ route('devices.clear_admin', $device->id) }}" method="POST" data-confirm="Buka gembok menu di mesin '{{ $device->name }}' (Hapus hak admin mesin)?" data-title="Buka Kunci Admin Mesin" data-icon="warning">
                            @csrf
                            <button type="submit" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-800 border border-yellow-300 text-xs py-1.5 px-2.5 rounded font-medium shadow-sm transition">
                                <i class="fas fa-unlock mr-0.5"></i> Buka Kunci
                            </button>
                        </form>
                        <form action="{{ route('devices.destroy', $device->id) }}" method="POST" data-confirm="Hapus data mesin '{{ $device->name }}' dari sistem?" data-title="Hapus Mesin" data-danger="true" data-icon="warning">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-xs py-1.5 px-2.5 rounded font-medium shadow-sm transition">
                                <i class="fas fa-trash mr-0.5"></i> Hapus
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="py-6 px-6 text-center text-gray-400">Belum ada mesin yang ditambahkan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection