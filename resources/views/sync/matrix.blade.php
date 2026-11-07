@extends('layouts.app')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('sync.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg border border-indigo-200 transition">
                <i class="fas fa-arrow-left"></i> Dashboard Sinkronisasi
            </a>
            <span class="text-xs text-gray-400">/</span>
            <span class="text-xs font-semibold text-gray-600">Matriks Sebaran Sidik Jari</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="text-purple-600">🔍</span> Cek Sebaran Sidik Jari Antar Mesin
        </h2>
        <p class="text-gray-600 text-sm mt-1">Audit dan pantau sidik jari siswa maupun guru tersimpan di mesin mana saja, serta sebar langsung ke mesin yang belum memilikinya.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <!-- Tombol Pindai Status Mesin -->
        <form action="{{ route('sync.scanDevices') }}" method="POST" data-confirm="Pindai dan periksa data sidik jari langsung dari seluruh mesin yang terhubung?" data-title="🔄 Pindai Ketersediaan Mesin" data-icon="question">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-indigo-700 font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm border border-indigo-200 shadow-sm transition transform hover:-translate-y-0.5">
                <i class="fas fa-sync-alt text-indigo-500"></i>
                <span>Pindai Status Mesin</span>
            </button>
        </form>

        <!-- Tombol Sebar Semua yang Belum Lengkap -->
        <form action="{{ route('sync.syncMissing') }}" method="POST" data-confirm="Sebarkan sidik jari seluruh user yang belum lengkap ke mesin-mesin yang belum memilikinya?" data-title="📤 Sebar Otomatis ke Mesin Kurang" data-icon="question">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm shadow-md transition transform hover:-translate-y-0.5">
                <i class="fas fa-share-alt"></i>
                <span>Sebar Otomatis yang Belum Lengkap</span>
            </button>
        </form>
    </div>
</div>

<!-- Ringkasan Statistik Matriks -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0 border border-blue-100">
            <i class="fas fa-server"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">Mesin Terdaftar</span>
            <span class="text-lg font-bold text-gray-800">{{ $devices->count() }} Mesin</span>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0 border border-purple-100">
            <i class="fas fa-fingerprint"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">User Terdaftar Sidik Jari</span>
            <span class="text-lg font-bold text-purple-700">{{ $totalRegisteredWithFp }} User</span>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0 border border-emerald-100">
            <i class="fas fa-check-double"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">Lengkap di Semua Mesin</span>
            <span class="text-lg font-bold text-emerald-700">{{ $completeCount }} User (100%)</span>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0 border border-amber-100">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <span class="text-xs text-gray-500 block font-medium">Belum Lengkap (Perlu Sebar)</span>
            <span class="text-lg font-bold text-amber-600">{{ $incompleteCount }} User</span>
        </div>
    </div>
</div>

<!-- Card Filter & Pencarian -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" action="{{ route('sync.matrix') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
        <!-- Pencarian Nama / PIN -->
        <div class="lg:col-span-4">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                <i class="fas fa-search text-gray-400 mr-1"></i> Cari User / PIN:
            </label>
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Ketik nama atau PIN user..." class="w-full bg-gray-50/70 border border-gray-200 rounded-xl text-xs sm:text-sm p-2.5 pl-9 focus:ring-2 focus:ring-purple-500 focus:outline-none focus:bg-white transition">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
            </div>
        </div>

        <!-- Filter Role -->
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                <i class="fas fa-user-tag text-gray-400 mr-1"></i> Kategori:
            </label>
            <select name="role" onchange="this.form.submit()" class="w-full bg-gray-50/70 border border-gray-200 rounded-xl text-xs sm:text-sm p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none focus:bg-white transition">
                <option value="all" {{ $role === 'all' ? 'selected' : '' }}>Semua (Siswa & Guru)</option>
                <option value="students" {{ $role === 'students' ? 'selected' : '' }}>👤 Hanya Siswa</option>
                <option value="teachers" {{ $role === 'teachers' ? 'selected' : '' }}>👨‍🏫 Hanya Dewan Guru</option>
            </select>
        </div>

        <!-- Filter Kelas -->
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                <i class="fas fa-chalkboard text-gray-400 mr-1"></i> Filter Kelas:
            </label>
            <select name="school_class_id" onchange="this.form.submit()" class="w-full bg-gray-50/70 border border-gray-200 rounded-xl text-xs sm:text-sm p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none focus:bg-white transition">
                <option value="">-- Semua Kelas --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ (string)$classId === (string)$c->id ? 'selected' : '' }}>
                        Kelas {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter Status Sebaran -->
        <div class="lg:col-span-3">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                <i class="fas fa-filter text-gray-400 mr-1"></i> Status Sebaran:
            </label>
            <select name="status" onchange="this.form.submit()" class="w-full bg-gray-50/70 border border-gray-200 rounded-xl text-xs sm:text-sm p-2.5 focus:ring-2 focus:ring-purple-500 focus:outline-none focus:bg-white transition">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>-- Semua Status Sebaran --</option>
                <option value="complete" {{ $statusFilter === 'complete' ? 'selected' : '' }}>✓ Lengkap di Semua Mesin</option>
                <option value="incomplete" {{ $statusFilter === 'incomplete' ? 'selected' : '' }}>⚠️ Belum Lengkap (Perlu Disebar)</option>
                @foreach($devices as $devOption)
                    <option value="missing_on_device" data-device="{{ $devOption->id }}" {{ ($statusFilter === 'missing_on_device' && (string)$targetDeviceId === (string)$devOption->id) ? 'selected' : '' }}>
                        ✕ Belum Ada di: {{ $devOption->name }}
                    </option>
                @endforeach
            </select>
            @if($statusFilter === 'missing_on_device')
                <input type="hidden" name="target_device_id" value="{{ $targetDeviceId }}">
            @endif
        </div>

        <!-- Tombol Cari & Reset -->
        <div class="lg:col-span-1 flex items-end gap-1.5">
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2.5 px-3 rounded-xl text-xs sm:text-sm transition shadow-sm text-center">
                Filter
            </button>
            @if($search || $role !== 'all' || $classId || $statusFilter !== 'all')
                <a href="{{ route('sync.matrix') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold p-2.5 rounded-xl text-xs sm:text-sm transition flex items-center justify-center" title="Reset Filter">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Tabel Matriks Sebaran Sidik Jari Antar Mesin -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <form id="matrix-bulk-sync-form" action="{{ route('sync.syncMissing') }}" method="POST" data-confirm="Sebarkan sidik jari seluruh user yang dicentang ke mesin yang belum memilikinya?" data-title="📤 Sebar Sidik Jari Terpilih" data-icon="question">
        @csrf

        <!-- Top Action Bar saat Checkbox Dicentang -->
        <div id="bulk-action-bar" class="hidden bg-purple-50/90 border-b border-purple-100 p-3.5 px-6 flex flex-wrap items-center justify-between gap-3 animate-in fade-in duration-150">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-xs" id="count-selected">0</span>
                <span class="text-xs sm:text-sm font-bold text-purple-900">User dipilih untuk sinkronisasi</span>
            </div>

            <div class="flex items-center gap-2.5">
                <select name="target_device_id" class="bg-white border border-purple-200 text-xs sm:text-sm rounded-xl py-1.5 px-3 text-purple-900 font-semibold focus:outline-none focus:ring-2 focus:ring-purple-400">
                    <option value="">🎯 Kirim ke Semua Mesin yang Kurang</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">Kirim Khusus ke: {{ $d->name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-xs sm:text-sm font-bold py-1.5 px-4 rounded-xl shadow-sm transition">
                    <i class="fas fa-paper-plane mr-1"></i> Sebar Sidik Jari Terpilih
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200/70 text-xs font-bold uppercase tracking-wider text-gray-600">
                        <th class="py-3.5 px-4 w-10 text-center">
                            <input type="checkbox" id="check-all" class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                        </th>
                        <th class="py-3.5 px-4 font-extrabold text-gray-700">PIN / NIS</th>
                        <th class="py-3.5 px-4 font-extrabold text-gray-700 min-w-[200px]">NAMA USER</th>
                        <th class="py-3.5 px-4 font-extrabold text-gray-700">KELAS / BIDANG</th>
                        <th class="py-3.5 px-4 font-extrabold text-gray-700 text-center bg-purple-50/50 border-x border-purple-100">
                            DATABASE WEB
                        </th>

                        <!-- Kolom Dinamis Setiap Mesin -->
                        @foreach($devices as $d)
                            <th class="py-3.5 px-4 font-extrabold text-gray-700 text-center min-w-[140px] border-r border-gray-100">
                                <div class="flex flex-col items-center">
                                    <span class="text-indigo-950 font-bold">{{ $d->name }}</span>
                                    <span class="text-[10px] text-gray-400 font-normal font-mono">({{ $d->serial_number ?: $d->ip_address }})</span>
                                </div>
                            </th>
                        @endforeach

                        <th class="py-3.5 px-4 font-extrabold text-gray-700 text-center">STATUS SEBARAN</th>
                        <th class="py-3.5 px-4 font-extrabold text-gray-700 text-right">AKSI CEPAT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($rows as $idx => $row)
                    <tr class="hover:bg-purple-50/30 transition {{ $row['is_complete'] ? '' : 'bg-amber-50/15' }}">
                        <!-- Checkbox -->
                        <td class="py-3.5 px-4 text-center">
                            <input type="checkbox" name="pins[]" value="{{ $row['pin'] }}" class="check-item w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                        </td>

                        <!-- PIN -->
                        <td class="py-3.5 px-4 font-mono font-bold text-gray-800">
                            <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-mono font-semibold border border-gray-200">
                                {{ $row['pin'] }}
                            </span>
                        </td>

                        <!-- Nama User & Role -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                @if($row['photo_url'])
                                    <img src="{{ $row['photo_url'] }}" alt="{{ $row['name'] }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 shrink-0">
                                @else
                                    <div class="w-9 h-9 rounded-full {{ $row['role'] === 'Guru' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-indigo-100 text-indigo-800 border-indigo-200' }} font-bold flex items-center justify-center text-xs shrink-0 border shadow-xs">
                                        {{ strtoupper(substr($row['name'], 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-bold text-gray-900 leading-tight">{{ $row['name'] }}</div>
                                    <span class="inline-block mt-0.5 px-2 py-0.2 rounded-full text-[10px] font-bold {{ $row['role'] === 'Guru' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                        {{ $row['role'] }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Kelas / Bidang -->
                        <td class="py-3.5 px-4 text-xs font-semibold text-gray-600">
                            {{ $row['class_name'] }}
                        </td>

                        <!-- Status di Database Web -->
                        <td class="py-3.5 px-4 text-center bg-purple-50/30 border-x border-purple-100">
                            @if($row['db_count'] > 0)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200" title="{{ $row['db_count'] }} template sidik jari tersimpan di database web">
                                    <i class="fas fa-fingerprint text-purple-600"></i>
                                    <span>{{ $row['db_count'] }} Jari</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-400 border border-gray-200">
                                    <i class="fas fa-times text-gray-400"></i> Belum Ada
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Status Tiap Mesin -->
                        @foreach($devices as $d)
                            @php
                                $devStatus = $row['devices'][$d->id] ?? ['has_template' => false, 'count' => 0];
                            @endphp
                            <td class="py-3.5 px-4 text-center border-r border-gray-100">
                                @if($devStatus['has_template'])
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Ada di {{ $d->name }} ({{ $devStatus['count'] }} jari)">
                                        <i class="fas fa-check-circle text-emerald-600"></i>
                                        <span>Ada ({{ $devStatus['count'] }})</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200" title="Belum ada di mesin {{ $d->name }}">
                                        <i class="fas fa-times-circle text-rose-500"></i>
                                        <span>Belum Ada</span>
                                    </span>
                                @endif
                            </td>
                        @endforeach

                        <!-- Status Sebaran Keseluruhan -->
                        <td class="py-3.5 px-4 text-center">
                            @if($row['db_count'] === 0)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">
                                    Belum Rekam FP
                                </span>
                            @elseif($row['is_complete'])
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <i class="fas fa-check-double text-emerald-600"></i> Lengkap (100%)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200" title="Kurang di {{ count($row['missing_device_ids']) }} mesin">
                                    <i class="fas fa-exclamation-circle text-amber-600"></i> Kurang {{ count($row['missing_device_ids']) }} Mesin
                                </span>
                            @endif
                        </td>

                        <!-- Aksi Cepat Sebar User Ini -->
                        <td class="py-3.5 px-4 text-right">
                            @if($row['db_count'] > 0 && ! $row['is_complete'])
                                <button type="submit" form="single-sync-form-{{ $row['pin'] }}" class="inline-flex items-center gap-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-1.5 px-3 rounded-xl text-xs transition shadow-xs">
                                    <i class="fas fa-share-alt"></i> Sebar
                                </button>
                            @elseif($row['db_count'] > 0)
                                <button type="submit" form="single-sync-form-{{ $row['pin'] }}" class="inline-flex items-center gap-1 bg-gray-100 hover:bg-purple-100 text-gray-600 hover:text-purple-700 font-semibold py-1.5 px-2.5 rounded-xl text-xs transition" title="Kirim ulang ke semua mesin">
                                    <i class="fas fa-redo-alt text-[10px]"></i> Ulang
                                </button>
                            @else
                                <span class="text-xs text-gray-400 italic">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 6 + $devices->count() }}" class="p-8 text-center text-gray-400">
                            <i class="fas fa-user-slash text-3xl mb-2 text-gray-300"></i>
                            <p>Tidak ada data user yang cocok dengan kriteria filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Hidden Individual Forms for Single Sync -->
    @foreach($rows as $r)
        @if($r['db_count'] > 0)
        <form id="single-sync-form-{{ $r['pin'] }}" action="{{ route('sync.syncMissing') }}" method="POST" data-confirm="Sebarkan sidik jari {{ $r['name'] }} ke mesin yang belum memilikinya?" data-title="Sebar Sidik Jari User" data-icon="question" class="hidden">
            @csrf
            <input type="hidden" name="pins[]" value="{{ $r['pin'] }}">
        </form>
        @endif
    @endforeach
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const checkAll = document.getElementById('check-all');
        const bulkActionBar = document.getElementById('bulk-action-bar');
        const countSelected = document.getElementById('count-selected');

        function updateBulkBar() {
            const checkedCount = document.querySelectorAll('.check-item:checked').length;
            if (countSelected) countSelected.innerText = checkedCount;

            if (checkedCount > 0) {
                bulkActionBar.classList.remove('hidden');
            } else {
                bulkActionBar.classList.add('hidden');
                if (checkAll) checkAll.checked = false;
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.check-item').forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                updateBulkBar();
            });
        }

        document.querySelectorAll('.check-item').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkBar();
                if (!this.checked && checkAll) checkAll.checked = false;
            });
        });
    });
</script>
@endsection

