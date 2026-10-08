@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Daftar Mesin Fingerprint</h2>
    <div class="flex space-x-2">
        <form action="{{ route('devices.sync_time_all') }}" method="POST" onsubmit="return confirm('Samakan jam SEMUA mesin dengan jam komputer ini?')">
            @csrf
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                ⌚ Sync Jam Semua Mesin
            </button>
        </form>
        <a href="{{ route('devices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
            + Tambah Mesin
        </a>
    </div>
</div>

<div class="bg-white shadow-md rounded my-6 overflow-x-auto">
    <table class="text-left w-full border-collapse">
        <thead>
            <tr>
                <th class="py-4 px-6 bg-gray-50 font-bold uppercase text-sm text-gray-700 border-b">Nama</th>
                <th class="py-4 px-6 bg-gray-50 font-bold uppercase text-sm text-gray-700 border-b">IP & Port</th>
                <th class="py-4 px-6 bg-gray-50 font-bold uppercase text-sm text-gray-700 border-b">Status</th>
                <th class="py-4 px-6 bg-gray-50 font-bold uppercase text-sm text-gray-700 border-b">Terakhir Aktif</th>
                <th class="py-4 px-6 bg-gray-50 font-bold uppercase text-sm text-gray-700 border-b text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $device)
            <tr class="hover:bg-gray-100 border-b">
                <td class="py-4 px-6 font-semibold">
                    {{ $device->name }}
                    @if($device->serial_number)
                        <span class="text-xs text-gray-400 font-mono block">SN: {{ $device->serial_number }}</span>
                    @endif
                </td>
                <td class="py-4 px-6">{{ $device->ip_address }}:{{ $device->port }}</td>
                <td class="py-4 px-6">
                    @if($device->status == 'online')
                        <span class="inline-block bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Online</span>
                    @else
                        <span class="inline-block bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Offline</span>
                    @endif
                </td>
                <td class="py-4 px-6 text-sm text-gray-600">
                    {{ $device->last_seen_at ? \Carbon\Carbon::parse($device->last_seen_at)->diffForHumans() : '-' }}
                </td>
                <td class="py-4 px-6 text-center flex justify-center space-x-2">
                    <form action="{{ route('devices.check', $device->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs py-1 px-3 rounded">
                            Test Koneksi
                        </button>
                    </form>
                    <form action="{{ route('devices.sync_time', $device->id) }}" method="POST" onsubmit="return confirm('Samakan jam mesin ini dengan jam komputer server?')">
                        @csrf
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-xs py-1 px-3 rounded">
                            Sync Jam
                        </button>
                    </form>
                    <form action="{{ route('devices.clear_admin', $device->id) }}" method="POST" onsubmit="return confirm('Buka gembok menu di mesin (Hapus hak admin)?')">
                        @csrf
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs py-1 px-3 rounded">
                            Buka Kunci
                        </button>
                    </form>
                    <form action="{{ route('devices.destroy', $device->id) }}" method="POST" onsubmit="return confirm('Hapus mesin ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs py-1 px-3 rounded">
                            Hapus
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="py-4 px-6 text-center text-gray-500">Belum ada mesin yang ditambahkan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection