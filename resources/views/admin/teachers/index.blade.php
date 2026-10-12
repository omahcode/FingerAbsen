@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-teal-700 via-cyan-700 to-blue-800 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold uppercase tracking-wider text-teal-100 mb-2 border border-white/20">
                    <i class="fas fa-users-cog text-yellow-300"></i> Manajemen Pengguna
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Kelola Akun Guru & Wali Kelas
                </h1>
                <p class="text-teal-100 mt-1 text-sm sm:text-base max-w-2xl">
                    Buat akun login untuk Bapak/Ibu Dewan Guru dan tugaskan sebagai Wali Kelas agar dapat memonitor kehadiran kelasnya serta mengajukan pengumuman TV.
                </p>
            </div>

            <div>
                <button type="button" onclick="document.getElementById('modal-add-teacher').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-xl shadow-lg transition active:scale-95 text-sm">
                    <i class="fas fa-user-plus text-base"></i>
                    <span>Tambah Akun Guru</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Teachers Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-chalkboard-teacher text-teal-600"></i>
                Daftar Guru / Wali Kelas Terdaftar
            </h2>
            <span class="text-xs text-gray-500 font-medium">Total: {{ $teachers->count() }} Guru</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Lengkap Guru</th>
                        <th class="py-3 px-4">Email Login</th>
                        <th class="py-3 px-4">Tugas Wali Kelas</th>
                        <th class="py-3 px-4 text-center">Status Akun</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($teachers as $idx => $teacher)
                    <tr class="hover:bg-gray-50/70 transition">
                        <td class="py-3.5 px-4 text-center font-semibold text-gray-400">{{ $idx + 1 }}</td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-full bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-sm shrink-0">
                                    {{ strtoupper(substr($teacher->name, 0, 1)) }}
                                </div>
                                <div class="font-bold text-gray-900">{{ $teacher->name }}</div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-mono text-xs text-gray-600">
                            {{ $teacher->email }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($teacher->schoolClass)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-800">
                                <i class="fas fa-chalkboard"></i> Wali Kelas: {{ $teacher->schoolClass->name }}
                            </span>
                            @else
                            <span class="text-xs text-gray-400 italic">Belum Ditugaskan</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                <i class="fas fa-check-circle text-[10px]"></i> Aktif
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick="openEditTeacherModal({{ $teacher->id }}, '{{ addslashes($teacher->name) }}', '{{ $teacher->email }}', '{{ $teacher->school_class_id }}')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Data Guru">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.teachers.destroy', $teacher->id) }}" method="POST" data-confirm="Hapus akun guru '{{ $teacher->name }}'?" data-title="Hapus Akun Guru" data-danger="true" data-icon="warning">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Akun">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fas fa-chalkboard-teacher text-4xl mb-3 text-gray-300"></i>
                            <p class="font-medium">Belum ada akun guru yang dibuat.</p>
                            <p class="text-xs text-gray-400 mt-1">Klik tombol "Tambah Akun Guru" di atas untuk membuat akun baru.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Guru -->
<div id="modal-add-teacher" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-teal-700 to-cyan-700 p-5 text-white flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Tambah Akun Guru Baru
            </h3>
            <button type="button" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.teachers.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Lengkap & Gelar</label>
                <input type="text" id="name" name="name" required placeholder="Contoh: Budi Santoso, S.Kom" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Alamat Email (Username Login)</label>
                <input type="email" id="email" name="email" required placeholder="guru@sekolah.sch.id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Kata Sandi (Password)</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>

            <div>
                <label for="school_class_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tugas Sebagai Wali Kelas</label>
                <select id="school_class_id" name="school_class_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
                    <option value="">-- Bukan Wali Kelas (Hanya Guru) --</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-teal-700 hover:bg-teal-800 text-white shadow-md transition active:scale-95">
                    Simpan Akun Guru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Guru -->
<div id="modal-edit-teacher" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 p-5 text-white flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Akun Guru
            </h3>
            <button type="button" onclick="document.getElementById('modal-edit-teacher').classList.add('hidden')" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="form-edit-teacher" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="edit_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" id="edit_name" name="name" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="edit_email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email Login</label>
                <input type="email" id="edit_email" name="email" required class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="edit_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Kata Sandi Baru (Kosongkan jika tidak diganti)</label>
                <input type="password" id="edit_password" name="password" minlength="6" placeholder="Biarkan kosong jika tetap" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="edit_school_class_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tugas Sebagai Wali Kelas</label>
                <select id="edit_school_class_id" name="school_class_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">-- Bukan Wali Kelas (Hanya Guru) --</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-edit-teacher').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-blue-700 hover:bg-blue-800 text-white shadow-md transition active:scale-95">
                    Perbarui Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditTeacherModal(id, name, email, classId) {
        const modal = document.getElementById('modal-edit-teacher');
        const form = document.getElementById('form-edit-teacher');
        form.action = `/admin/teachers/${id}`;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_school_class_id').value = classId || '';
        modal.classList.remove('hidden');
    }
</script>
@endsection
