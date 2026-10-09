@extends('layouts.app')
@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Data Siswa (User Mesin)</h2>
        <p class="text-xs text-gray-500 mt-1">Daftar seluruh siswa yang terdaftar di database web dan disinkronkan ke mesin.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow transition-all text-sm">
            <i class="fas fa-trash-alt mr-1"></i> Hapus Terpilih (<span id="count-selected">0</span>)
        </button>
        <a href="{{ route('students.importForm') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
            <i class="fas fa-file-excel mr-1"></i> Import Excel
        </a>
        <a href="{{ route('students.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
            <i class="fas fa-plus mr-1"></i> Tambah Siswa Baru
        </a>
    </div>
</div>

<!-- Filter & Search Bar -->
<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6">
    <form action="{{ route('students.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
        <!-- Filter Kelas -->
        <div class="md:col-span-4">
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                <i class="fas fa-filter text-indigo-500 mr-1"></i> Filter Kelas:
            </label>
            <select name="class_id" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg text-sm p-2 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="">-- Semua Kelas ({{ $totalStudents }} Siswa) --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ ($selectedClassId == $c->id) ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->major->name ?? '' }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter Status Sidik Jari -->
        <div class="md:col-span-3">
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                <i class="fas fa-fingerprint text-emerald-500 mr-1"></i> Status Sidik Jari:
            </label>
            <select name="fingerprint" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg text-sm p-2 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <option value="" {{ empty($selectedFingerprint) ? 'selected' : '' }}>-- Semua Status --</option>
                <option value="registered" {{ ($selectedFingerprint === 'registered') ? 'selected' : '' }}>✓ Sudah Terdaftar</option>
                <option value="unregistered" {{ ($selectedFingerprint === 'unregistered') ? 'selected' : '' }}>✕ Belum Terdaftar</option>
            </select>
        </div>

        <!-- Pencarian Nama / NIS -->
        <div class="md:col-span-5">
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                <i class="fas fa-search text-blue-500 mr-1"></i> Cari Nama / NIS:
            </label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Ketik nama atau NIS..." class="w-full border border-gray-300 rounded-lg text-sm p-2 pl-8 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400 text-xs"></i>
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                    Cari
                </button>
                @if($selectedClassId || $search || $selectedFingerprint)
                    <a href="{{ route('students.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-3 py-2 rounded-lg text-sm transition flex items-center gap-1" title="Reset Filter">
                        <i class="fas fa-times"></i> Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Info hasil filter -->
    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
        <div>
            Menampilkan <strong class="text-gray-800">{{ $students->count() }}</strong> dari total <strong class="text-gray-800">{{ $totalStudents }}</strong> siswa
            @if($selectedClassId)
                @php $currentClass = $classes->firstWhere('id', $selectedClassId); @endphp
                untuk kelas <span class="bg-indigo-50 text-indigo-700 font-semibold px-2 py-0.5 rounded border border-indigo-100">{{ $currentClass->name ?? '' }}</span>
            @endif
        </div>
    </div>
</div>

<div class="bg-white shadow rounded-lg overflow-x-auto p-4 border border-gray-100">
    <form id="bulk-delete-form" action="{{ route('students.destroyMultiple') }}" method="POST" data-confirm="Hapus semua siswa yang dicentang dari database DAN SEMUA MESIN secara permanen?" data-title="Hapus Massal Siswa" data-danger="true" data-icon="warning">
        @csrf
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-gray-50 text-gray-700">
                    <th class="p-3 w-10 text-center">
                        <input type="checkbox" id="check-all" class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    </th>
                    <th class="p-3 text-sm font-semibold">NIS / PIN</th>
                    <th class="p-3 text-sm font-semibold">Nama Siswa</th>
                    <th class="p-3 text-sm font-semibold">Kelas</th>
                    <th class="p-3 text-sm font-semibold">Jurusan</th>
                    <th class="p-3 text-sm font-semibold text-center">Sidik Jari</th>
                    <th class="p-3 text-sm font-semibold">No. WA Ortu</th>
                    <th class="p-3 text-sm text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody id="student-table-body">
                @forelse($students as $s)
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-3 text-center">
                        <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="check-item w-4 h-4 text-blue-600 rounded border-gray-300">
                    </td>
                    <td class="p-3 font-mono text-gray-600">{{ $s->device_user_id }}</td>
                    <td class="p-3">
                        <div class="flex items-center gap-1.5">
                            <span class="font-semibold text-gray-900">{{ $s->name }}</span>
                            @if($s->fingerprint_templates_count > 0)
                                <i class="fas fa-fingerprint text-emerald-500 text-xs" title="Sidik Jari Terdaftar ({{ $s->fingerprint_templates_count }} jari)"></i>
                            @endif
                        </div>
                    </td>
                    <td class="p-3">{{ $s->schoolClass->name ?? '-' }}</td>
                    <td class="p-3">{{ $s->schoolClass->major->name ?? '-' }}</td>
                    <td class="p-3 text-center">
                        @if($s->fingerprint_templates_count > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Sidik Jari Terdaftar ({{ $s->fingerprint_templates_count }} jari)">
                                <i class="fas fa-fingerprint text-emerald-600 text-sm"></i>
                                <span>Terdaftar</span>
                                <span class="bg-emerald-200 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold">{{ $s->fingerprint_templates_count }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-400 border border-gray-200" title="Sidik jari belum didaftarkan di mesin">
                                <i class="fas fa-fingerprint text-gray-300"></i>
                                <span>Belum Ada</span>
                            </span>
                        @endif
                    </td>
                    <td class="p-3 text-xs">
                        @if($s->parent_phone)
                            <a href="https://wa.me/{{ \App\Services\WhatsAppService::formatPhoneNumber($s->parent_phone) }}" target="_blank" class="inline-flex items-center text-green-700 bg-green-50 px-2 py-1 rounded border border-green-200 hover:bg-green-100 font-mono">
                                <i class="fab fa-whatsapp mr-1 text-green-600"></i> {{ $s->parent_phone }}
                            </a>
                        @else
                            <span class="text-gray-400 italic">- Belum ada -</span>
                        @endif
                    </td>
                    <td class="p-3 text-center flex justify-center space-x-2">
                        <a href="{{ route('students.edit', $s->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs py-1.5 px-3 rounded shadow-sm">
                            <i class="fas fa-edit mr-0.5"></i> Edit
                        </a>
                        <button type="button" onclick="deleteSingle({{ $s->id }}, '{{ addslashes($s->name) }}')" class="bg-red-500 hover:bg-red-600 text-white text-xs py-1.5 px-3 rounded shadow-sm">
                            <i class="fas fa-trash mr-0.5"></i> Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-6 text-center text-gray-400">Belum ada data siswa terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </form>

    <!-- Form tersembunyi untuk hapus satuan -->
    <form id="single-delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
    // Logic Hapus Satuan dengan SweetAlert
    function deleteSingle(id, name) {
        Swal.fire({
            title: 'Hapus Siswa?',
            text: `Apakah Anda yakin ingin menghapus data siswa "${name}" dari website dan seluruh mesin fingerprint?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('single-delete-form');
                form.action = `/students/${id}`;
                form.submit();
            }
        });
    }

    // Logic Checkbox & Hapus Massal
    document.addEventListener("DOMContentLoaded", () => {
        const checkAll = document.getElementById('check-all');
        const btnBulkDelete = document.getElementById('btn-bulk-delete');
        const countSelected = document.getElementById('count-selected');

        function updateBulkButton() {
            const checkedCount = document.querySelectorAll('.check-item:checked').length;
            if (countSelected) countSelected.innerText = checkedCount;
            
            if (checkedCount > 0) {
                btnBulkDelete.classList.remove('hidden');
            } else {
                btnBulkDelete.classList.add('hidden');
                if (checkAll) checkAll.checked = false;
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.check-item').forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                updateBulkButton();
            });
        }

        const tableBody = document.getElementById('student-table-body');
        if (tableBody) {
            tableBody.addEventListener('change', function(e) {
                if(e.target && e.target.classList.contains('check-item')) {
                    updateBulkButton();
                    if(!e.target.checked && checkAll) checkAll.checked = false;
                }
            });
        }
    });
</script>
@endsection