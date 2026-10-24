@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-emerald-700 via-teal-700 to-cyan-800 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold uppercase tracking-wider text-emerald-100 mb-2 border border-white/20">
                    <i class="fas fa-chalkboard-teacher text-yellow-300"></i> Master Data Sekolah
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Data Guru (User Mesin)
                </h1>
                <p class="text-emerald-100 mt-1 text-sm sm:text-base max-w-2xl">
                    Daftar seluruh Bapak/Ibu Dewan Guru yang terdaftar di database web dan disinkronkan ke mesin fingerprint untuk identifikasi dan presensi upacara.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('teachers.ceremony') }}" class="inline-flex items-center gap-2 bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2.5 rounded-xl shadow-lg transition active:scale-95 text-xs sm:text-sm">
                    <i class="fas fa-flag"></i>
                    <span>Presensi Upacara</span>
                </a>

                <form action="{{ route('teachers.sync_all') }}" method="POST" data-confirm="Sinkronkan seluruh data guru (nama & PIN) ke semua mesin fingerprint aktif?" data-title="Sinkronisasi Guru ke Mesin" data-icon="question">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white font-bold px-4 py-2.5 rounded-xl border border-white/30 backdrop-blur shadow transition active:scale-95 text-xs sm:text-sm">
                        <i class="fas fa-sync-alt"></i>
                        <span>Sync ke Mesin</span>
                    </button>
                </form>

                <a href="{{ route('teachers.importForm') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-2.5 rounded-xl border border-white/20 shadow-lg transition active:scale-95 text-xs sm:text-sm">
                    <i class="fas fa-file-excel"></i>
                    <span>Import Excel</span>
                </a>

                <button type="button" onclick="openAddTeacherModal()" class="inline-flex items-center gap-2 bg-white text-emerald-800 hover:bg-emerald-50 font-bold px-4 py-2.5 rounded-xl shadow-lg transition active:scale-95 text-xs sm:text-sm">
                    <i class="fas fa-user-plus"></i>
                    <span>Tambah Guru Baru</span>
                </button>
            </div>
        </div>

        <!-- Quick Stats Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-white/15">
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-emerald-100 uppercase tracking-wider font-semibold">Total Guru</div>
                <div class="text-2xl font-black mt-0.5">{{ $totalTeachers }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-emerald-100 uppercase tracking-wider font-semibold">Sidik Jari Terdaftar</div>
                <div class="text-2xl font-black text-emerald-200 mt-0.5">{{ $registeredFpCount }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-emerald-100 uppercase tracking-wider font-semibold">Belum Ada Sidik Jari</div>
                <div class="text-2xl font-black text-amber-200 mt-0.5">{{ $unregisteredFpCount }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                <div class="text-[11px] text-emerald-100 uppercase tracking-wider font-semibold">Tap Hadir Hari Ini</div>
                <div class="text-2xl font-black text-cyan-200 mt-0.5">{{ $todayAttendanceCount }}</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100">
        <form action="{{ route('teachers.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Filter Status Sidik Jari -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                    <i class="fas fa-fingerprint text-emerald-500 mr-1"></i> Status Sidik Jari:
                </label>
                <select name="fingerprint" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="" {{ empty($selectedFingerprint) ? 'selected' : '' }}>-- Semua Status --</option>
                    <option value="registered" {{ ($selectedFingerprint === 'registered') ? 'selected' : '' }}>✓ Sudah Terdaftar</option>
                    <option value="unregistered" {{ ($selectedFingerprint === 'unregistered') ? 'selected' : '' }}>✕ Belum Terdaftar</option>
                </select>
            </div>

            <!-- Pencarian Nama / NIP -->
            <div class="md:col-span-8">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                    <i class="fas fa-search text-blue-500 mr-1"></i> Cari Nama / NIP:
                </label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Ketik nama atau NIP guru..." class="w-full border border-gray-300 rounded-xl text-sm p-2.5 pl-9 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <i class="fas fa-search absolute left-3 top-3.5 text-gray-400 text-xs"></i>
                    </div>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition shadow-sm">
                        Cari
                    </button>
                    @if($search || $selectedFingerprint)
                        <a href="{{ route('teachers.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-3 py-2.5 rounded-xl text-sm transition flex items-center gap-1" title="Reset Filter">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- Info Hasil Filter -->
        <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between text-xs text-gray-500 gap-2">
            <div>
                Menampilkan <strong class="text-gray-900 font-bold">{{ $teachers->count() }}</strong> dari total <strong class="text-gray-900 font-bold">{{ $totalTeachers }}</strong> guru
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden bg-rose-600 hover:bg-rose-700 text-white font-bold py-1.5 px-3 rounded-lg shadow transition text-xs">
                    <i class="fas fa-trash-alt mr-1"></i> Hapus Terpilih (<span id="count-selected">0</span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Tabel Data Guru: NIP | NAMA GURU | SIDIK JARI | AKSI -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form id="bulk-delete-form" action="{{ route('teachers.destroyMultiple') }}" method="POST" data-confirm="Hapus seluruh data guru yang dicentang dari database dan mesin fingerprint?" data-title="Hapus Massal Guru" data-danger="true" data-icon="warning">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-600">
                            <th class="py-3.5 px-4 w-10 text-center">
                                <input type="checkbox" id="check-all" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                            </th>
                            <th class="py-3.5 px-4 font-extrabold text-gray-700">NIP</th>
                            <th class="py-3.5 px-4 font-extrabold text-gray-700">NAMA GURU</th>
                            <th class="py-3.5 px-4 font-extrabold text-gray-700 text-center">SIDIK JARI</th>
                            <th class="py-3.5 px-4 font-extrabold text-gray-700 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($teachers as $idx => $t)
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="py-3.5 px-4 text-center">
                                <input type="checkbox" name="ids[]" value="{{ $t->id }}" class="check-item w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                            </td>

                            <!-- NIP -->
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-800">
                                <div class="flex items-center gap-1.5">
                                    <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-mono font-semibold border border-gray-200">
                                        {{ $t->nip }}
                                    </span>
                                    @if($t->device_user_id !== $t->nip)
                                        <span class="text-[10px] text-gray-400" title="PIN Mesin: {{ $t->device_user_id }}">(PIN: {{ $t->device_user_id }})</span>
                                    @endif
                                </div>
                            </td>

                            <!-- NAMA GURU -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs shrink-0 border border-emerald-200 shadow-xs">
                                        {{ strtoupper(substr($t->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900">{{ $t->name }}</div>
                                        @if($t->phone)
                                            <div class="text-[11px] text-gray-400 font-mono flex items-center gap-1 mt-0.5">
                                                <i class="fas fa-phone-alt text-[10px] text-gray-400"></i> {{ $t->phone }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- SIDIK JARI -->
                            <td class="py-3.5 px-4 text-center">
                                @if($t->fingerprint_templates_count > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Sidik Jari Terdaftar ({{ $t->fingerprint_templates_count }} jari)">
                                        <i class="fas fa-fingerprint text-emerald-600 text-sm"></i>
                                        <span>Terdaftar</span>
                                        <span class="bg-emerald-200 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold">{{ $t->fingerprint_templates_count }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-400 border border-gray-200" title="Sidik jari belum didaftarkan di mesin">
                                        <i class="fas fa-fingerprint text-gray-300"></i>
                                        <span>Belum Ada</span>
                                    </span>
                                @endif
                            </td>

                            <!-- AKSI -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" onclick="openEditTeacherModal({{ $t->id }}, '{{ addslashes($t->nip) }}', '{{ addslashes($t->name) }}', '{{ addslashes($t->device_user_id) }}', '{{ addslashes($t->phone ?? '') }}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-xl transition" title="Edit Data Guru">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button type="button" onclick="deleteSingleTeacher({{ $t->id }}, '{{ addslashes($t->name) }}', '{{ addslashes($t->nip) }}')" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition" title="Hapus Guru">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-14 text-center text-gray-400">
                                <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <p class="font-bold text-gray-700 text-base">Belum Ada Data Guru</p>
                                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                                    Klik tombol "Tambah Guru Baru" di atas untuk mendaftarkan NIP, Nama, dan Sidik Jari guru.
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Form tersembunyi untuk hapus satuan guru -->
        <form id="single-delete-teacher-form" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<!-- Modal Tambah Guru -->
<div id="modal-add-teacher" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in duration-200">
        <div class="bg-gradient-to-r from-emerald-700 to-teal-700 p-5 text-white flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-user-plus text-yellow-300"></i> Tambah Data Guru Baru
            </h3>
            <button type="button" onclick="closeAddTeacherModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('teachers.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <!-- NIP -->
            <div>
                <label for="add_nip" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    NIP (Nomor Induk Pegawai) <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="add_nip" name="nip" required placeholder="Contoh: 198501152010011002" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <p class="text-[11px] text-gray-400 mt-1">NIP juga akan otomatis menjadi PIN ID di mesin fingerprint.</p>
            </div>

            <!-- NAMA GURU -->
            <div>
                <label for="add_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Nama Lengkap Guru & Gelar <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="add_name" name="name" required placeholder="Contoh: Drs. H. Ahmad Fauzi, M.Pd" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- PIN MESIN (OPSIONAL) -->
            <div>
                <label for="add_device_user_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    PIN Mesin Fingerprint (Opsional)
                </label>
                <input type="text" id="add_device_user_id" name="device_user_id" placeholder="Biarkan kosong untuk samakan dengan NIP" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- NO TELEPON -->
            <div>
                <label for="add_phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    No. Telepon / WhatsApp (Opsional)
                </label>
                <input type="text" id="add_phone" name="phone" placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeAddTeacherModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-emerald-700 hover:bg-emerald-800 text-white shadow-md transition active:scale-95">
                    Simpan Data Guru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Guru -->
<div id="modal-edit-teacher" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in duration-200">
        <div class="bg-gradient-to-r from-blue-700 to-cyan-700 p-5 text-white flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-edit text-yellow-300"></i> Edit Data Guru
            </h3>
            <button type="button" onclick="closeEditTeacherModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="form-edit-teacher" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- NIP -->
            <div>
                <label for="edit_nip" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    NIP (Nomor Induk Pegawai) <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="edit_nip" name="nip" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- NAMA GURU -->
            <div>
                <label for="edit_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Nama Lengkap Guru & Gelar <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="edit_name" name="name" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- PIN MESIN -->
            <div>
                <label for="edit_device_user_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    PIN Mesin Fingerprint
                </label>
                <input type="text" id="edit_device_user_id" name="device_user_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- NO TELEPON -->
            <div>
                <label for="edit_phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    No. Telepon / WhatsApp (Opsional)
                </label>
                <input type="text" id="edit_phone" name="phone" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeEditTeacherModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-blue-700 hover:bg-blue-800 text-white shadow-md transition active:scale-95">
                    Perbarui Data Guru
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddTeacherModal() {
        document.getElementById('modal-add-teacher').classList.remove('hidden');
    }

    function closeAddTeacherModal() {
        document.getElementById('modal-add-teacher').classList.add('hidden');
    }

    function openEditTeacherModal(id, nip, name, deviceUserId, phone) {
        const modal = document.getElementById('modal-edit-teacher');
        const form = document.getElementById('form-edit-teacher');
        form.action = `/teachers/${id}`;

        document.getElementById('edit_nip').value = nip;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_device_user_id').value = deviceUserId;
        document.getElementById('edit_phone').value = phone || '';

        modal.classList.remove('hidden');
    }

    function closeEditTeacherModal() {
        document.getElementById('modal-edit-teacher').classList.add('hidden');
    }

    function deleteSingleTeacher(id, name, nip) {
        Swal.fire({
            title: 'Hapus Data Guru',
            text: `Hapus data guru '${name}' (NIP: ${nip}) dari sistem dan mesin fingerprint?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const form = document.getElementById('single-delete-teacher-form');
                form.action = `/teachers/${id}`;
                form.submit();
            }
        });
    }

    // Bulk Delete Checkbox Logic
    document.addEventListener('DOMContentLoaded', function () {
        const checkAll = document.getElementById('check-all');
        const checkItems = document.querySelectorAll('.check-item');
        const btnBulkDelete = document.getElementById('btn-bulk-delete');
        const countSelected = document.getElementById('count-selected');

        function updateBulkButton() {
            const checkedCount = document.querySelectorAll('.check-item:checked').length;
            if (countSelected) countSelected.textContent = checkedCount;

            if (checkedCount > 0) {
                btnBulkDelete.classList.remove('hidden');
            } else {
                btnBulkDelete.classList.add('hidden');
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                checkItems.forEach(item => item.checked = checkAll.checked);
                updateBulkButton();
            });
        }

        checkItems.forEach(item => {
            item.addEventListener('change', function () {
                if (checkAll) {
                    checkAll.checked = (document.querySelectorAll('.check-item:checked').length === checkItems.length);
                }
                updateBulkButton();
            });
        });
    });
</script>
@endsection
