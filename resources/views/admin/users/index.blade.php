@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden border border-slate-800">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur text-xs font-semibold uppercase tracking-wider text-indigo-300 mb-2 border border-white/15">
                    <i class="fas fa-users-cog text-amber-400"></i> Master Akun & Keamanan
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Manajemen Pengguna (Users)
                </h1>
                <p class="text-slate-300 mt-1 text-sm sm:text-base max-w-2xl">
                    Kelola seluruh akun akses sistem, termasuk Dewan Guru, Kepala Sekolah, dan Administrator. Anda dapat menambah akun baru, mereset password, serta mengelola hak akses.
                </p>
            </div>

            <div>
                <button type="button" onclick="openAddUserModal()" class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-indigo-600/30 transition active:scale-95 text-sm">
                    <i class="fas fa-user-plus text-base"></i>
                    <span>Tambah Pengguna Baru</span>
                </button>
            </div>
        </div>

        <!-- Quick Stats Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-slate-800">
            <a href="{{ route('admin.users.index') }}" class="bg-slate-800/60 hover:bg-slate-800/90 backdrop-blur rounded-xl p-3 border border-slate-700/60 transition {{ empty($roleFilter) ? 'ring-2 ring-indigo-500' : '' }}">
                <div class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Total Pengguna</div>
                <div class="text-2xl font-black text-white mt-0.5">{{ $totalUsers }}</div>
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'kepala_sekolah']) }}" class="bg-slate-800/60 hover:bg-slate-800/90 backdrop-blur rounded-xl p-3 border border-slate-700/60 transition {{ $roleFilter === 'kepala_sekolah' ? 'ring-2 ring-amber-500' : '' }}">
                <div class="text-[11px] text-amber-400 uppercase tracking-wider font-semibold">Kepala Sekolah</div>
                <div class="text-2xl font-black text-amber-300 mt-0.5">{{ $totalKepsek }}</div>
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'guru']) }}" class="bg-slate-800/60 hover:bg-slate-800/90 backdrop-blur rounded-xl p-3 border border-slate-700/60 transition {{ $roleFilter === 'guru' ? 'ring-2 ring-emerald-500' : '' }}">
                <div class="text-[11px] text-emerald-400 uppercase tracking-wider font-semibold">Dewan Guru / Wali Kelas</div>
                <div class="text-2xl font-black text-emerald-300 mt-0.5">{{ $totalGuru }}</div>
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="bg-slate-800/60 hover:bg-slate-800/90 backdrop-blur rounded-xl p-3 border border-slate-700/60 transition {{ $roleFilter === 'admin' ? 'ring-2 ring-blue-500' : '' }}">
                <div class="text-[11px] text-blue-400 uppercase tracking-wider font-semibold">Administrator</div>
                <div class="text-2xl font-black text-blue-300 mt-0.5">{{ $totalAdmin }}</div>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.users.index', ['search' => $search]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ empty($roleFilter) ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Semua ({{ $totalUsers }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'kepala_sekolah', 'search' => $search]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $roleFilter === 'kepala_sekolah' ? 'bg-amber-600 text-white shadow-md' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                <i class="fas fa-crown text-xs mr-1"></i> Kepala Sekolah ({{ $totalKepsek }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'guru', 'search' => $search]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $roleFilter === 'guru' ? 'bg-emerald-600 text-white shadow-md' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                <i class="fas fa-chalkboard-teacher text-xs mr-1"></i> Guru ({{ $totalGuru }})
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'admin', 'search' => $search]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $roleFilter === 'admin' ? 'bg-blue-600 text-white shadow-md' : 'bg-blue-50 text-blue-800 hover:bg-blue-100 border border-blue-200' }}">
                <i class="fas fa-user-shield text-xs mr-1"></i> Admin ({{ $totalAdmin }})
            </a>
        </div>

        <!-- Search Form -->
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex items-center gap-2 max-w-sm w-full">
            @if(!empty($roleFilter))
                <input type="hidden" name="role" value="{{ $roleFilter }}">
            @endif
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Cari nama atau email..." 
                    class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
            </div>
            <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-semibold px-4 py-2 rounded-xl text-xs transition">
                Cari
            </button>
            @if(!empty($search) || !empty($roleFilter))
            <a href="{{ route('admin.users.index') }}" class="p-2 text-gray-400 hover:text-gray-700 text-xs" title="Reset Filter">
                <i class="fas fa-times"></i>
            </a>
            @endif
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-users text-indigo-600"></i>
                Daftar Akun Pengguna Terdaftar
            </h2>
            <span class="text-xs text-gray-500 font-medium">Menampilkan {{ $users->count() }} akun</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-600">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Email Login</th>
                        <th class="py-3.5 px-4">Peran (Role)</th>
                        <th class="py-3.5 px-4">Tugas / Penugasan</th>
                        <th class="py-3.5 px-4 text-center">Terdaftar</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($users as $idx => $user)
                    <tr class="hover:bg-gray-50/70 transition">
                        <td class="py-3.5 px-4 text-center font-bold text-gray-400">{{ $idx + 1 }}</td>
                        
                        <!-- Nama & Avatar -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl font-bold flex items-center justify-center text-xs shrink-0 shadow-sm
                                    @if($user->isKepalaSekolah()) bg-amber-100 text-amber-800 border border-amber-300
                                    @elseif($user->isAdmin()) bg-blue-100 text-blue-800 border border-blue-200
                                    @else bg-emerald-100 text-emerald-800 border border-emerald-200 @endif">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 flex items-center gap-2">
                                        <span>{{ $user->name }}</span>
                                        @if($user->id === Auth::id())
                                        <span class="text-[9px] bg-slate-900 text-white px-1.5 py-0.2 rounded font-semibold uppercase">Anda</span>
                                        @endif
                                    </div>
                                    <span class="text-xs text-gray-400 font-mono">{{ $user->email }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Email -->
                        <td class="py-3.5 px-4 font-mono text-xs text-gray-700">
                            {{ $user->email }}
                        </td>

                        <!-- Role Badge -->
                        <td class="py-3.5 px-4">
                            @if($user->isKepalaSekolah())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    <i class="fas fa-crown text-amber-600"></i> Kepala Sekolah
                                </span>
                            @elseif($user->isAdmin())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    <i class="fas fa-shield-alt text-blue-600"></i> Administrator
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <i class="fas fa-chalkboard-teacher text-emerald-600"></i> Guru / Wali Kelas
                                </span>
                            @endif
                        </td>

                        <!-- Penugasan -->
                        <td class="py-3.5 px-4">
                            @if($user->isKepalaSekolah())
                                <span class="text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                    Pimpinan Sekolah
                                </span>
                            @elseif($user->isAdmin())
                                <span class="text-xs font-medium text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                    Superadmin Sistem
                                </span>
                            @else
                                <div class="space-y-1">
                                    @if($user->schoolClass)
                                        <div class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                                            <i class="fas fa-chalkboard text-[10px]"></i> Wali: {{ $user->schoolClass->name }}
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400 italic">Bukan Wali Kelas</div>
                                    @endif

                                    @if($user->major)
                                        <div>
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                                <i class="fas fa-layer-group text-[10px]"></i> {{ $user->major->name }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <!-- Terdaftar Tanggal -->
                        <td class="py-3.5 px-4 text-center text-xs text-gray-500 font-mono">
                            {{ $user->created_at ? $user->created_at->format('d/m/Y') : '-' }}
                        </td>

                        <!-- Aksi -->
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Tombol Reset / Ganti Password -->
                                <button 
                                    type="button" 
                                    onclick="openResetPasswordModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}')" 
                                    class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition" 
                                    title="Ganti / Reset Password"
                                >
                                    <i class="fas fa-key text-xs"></i>
                                </button>

                                <!-- Tombol Edit User -->
                                <button 
                                    type="button" 
                                    onclick="openEditUserModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->role ?? 'admin' }}', '{{ $user->school_class_id }}', '{{ $user->major_id }}')" 
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                    title="Edit Data Akun"
                                >
                                    <i class="fas fa-edit text-xs"></i>
                                </button>

                                <!-- Tombol Hapus User -->
                                @if($user->id !== Auth::id())
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" data-confirm="Hapus akun '{{ $user->name }}' ({{ $user->email }}) secara permanen?" data-title="Konfirmasi Hapus Akun" data-danger="true" data-icon="warning" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Akun Pengguna">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                                @else
                                <span class="p-2 text-gray-300 cursor-not-allowed" title="Tidak dapat menghapus akun Anda sendiri">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            <i class="fas fa-user-slash text-4xl mb-3 text-gray-300"></i>
                            <p class="font-medium text-gray-700">Tidak ada data pengguna yang ditemukan.</p>
                            @if(!empty($search) || !empty($roleFilter))
                            <a href="{{ route('admin.users.index') }}" class="text-xs text-indigo-600 font-bold hover:underline mt-1 inline-block">
                                Bersihkan Pencarian & Filter
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 1: TAMBAH PENGGUNA BARU                                  -->
<!-- ============================================================== -->
<div id="modal-add-user" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex items-center justify-between">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fas fa-user-plus text-indigo-400"></i>
                Tambah Akun Pengguna Baru
            </h3>
            <button type="button" onclick="closeAddUserModal()" class="text-slate-400 hover:text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <!-- Role Selector -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Peran / Role Pengguna <span class="text-rose-500">*</span>
                </label>
                <select name="role" id="add-user-role" onchange="toggleAddRoleFields()" required class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold">
                    <option value="guru" selected>Dewan Guru / Wali Kelas</option>
                    <option value="kepala_sekolah">Kepala Sekolah</option>
                    <option value="admin">Administrator (Superadmin)</option>
                </select>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" required placeholder="Contoh: Drs. H. Ahmad Fauzi, M.Pd" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <!-- Email Login -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Email Login <span class="text-rose-500">*</span>
                </label>
                <input type="email" name="email" required placeholder="nama@sekolah.sch.id" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <!-- Password Awal -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Kata Sandi (Password) <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <span class="text-[11px] text-gray-400 mt-1 block">Beri tahu pengguna password ini untuk login pertama kali.</span>
            </div>

            <!-- Khusus Guru: Penugasan Wali Kelas & Kaprogli -->
            <div id="add-guru-fields" class="space-y-4 pt-2 border-t border-gray-100">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Tugaskan Sebagai Wali Kelas (Opsional)
                    </label>
                    <select name="school_class_id" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Bukan Wali Kelas (Hanya Guru Pengajar) --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Penugasan Kepala Program Keahlian / Jurusan (Opsional)
                    </label>
                    <select name="major_id" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Tidak Ditugaskan di Jurusan Tertentu --</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeAddUserModal()" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold text-xs hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: RESET / GANTI PASSWORD CEPAT                          -->
<!-- ============================================================== -->
<div id="modal-reset-password" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 bg-gradient-to-r from-amber-600 to-amber-700 text-white flex items-center justify-between">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fas fa-key text-amber-200"></i>
                Reset / Ganti Kata Sandi
            </h3>
            <button type="button" onclick="closeResetPasswordModal()" class="text-amber-200 hover:text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="form-reset-password" action="" method="POST" class="p-6 space-y-4">
            @csrf

            <!-- Info Pengguna -->
            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 space-y-1">
                <div>Pengguna: <strong id="reset-user-name" class="font-bold"></strong></div>
                <div>Email: <span id="reset-user-email" class="font-mono"></span></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Kata Sandi Baru <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="new_password" 
                        id="new-password-input"
                        required 
                        minlength="6" 
                        placeholder="Masukkan minimal 6 karakter..." 
                        class="w-full border border-gray-300 rounded-xl text-sm p-2.5 font-mono pr-20 focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    >
                    <button 
                        type="button" 
                        onclick="generateRandomPassword()" 
                        class="absolute right-2 top-2 px-2 py-1 bg-amber-100 hover:bg-amber-200 text-amber-800 text-[10px] font-bold rounded-lg border border-amber-300 transition"
                        title="Buat password acak otomatis"
                    >
                        Acak
                    </button>
                </div>
                <span class="text-[11px] text-gray-500 mt-1 block">Kata sandi langsung diperbarui tanpa perlu konfirmasi password lama.</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold text-xs hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-md transition">
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 3: EDIT PENGGUNA                                         -->
<!-- ============================================================== -->
<div id="modal-edit-user" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-700 to-indigo-800 text-white flex items-center justify-between">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fas fa-user-edit text-blue-300"></i>
                Edit Data Pengguna
            </h3>
            <button type="button" onclick="closeEditUserModal()" class="text-blue-200 hover:text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="form-edit-user" action="" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- Role Selector -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Peran / Role Pengguna <span class="text-rose-500">*</span>
                </label>
                <select name="role" id="edit-user-role" onchange="toggleEditRoleFields()" required class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold">
                    <option value="guru">Dewan Guru / Wali Kelas</option>
                    <option value="kepala_sekolah">Kepala Sekolah</option>
                    <option value="admin">Administrator (Superadmin)</option>
                </select>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" id="edit-user-name" required class="w-full border border-gray-300 rounded-xl text-sm p-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Email Login -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Email Login <span class="text-rose-500">*</span>
                </label>
                <input type="email" name="email" id="edit-user-email" required class="w-full border border-gray-300 rounded-xl text-sm p-2.5 font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Ganti Password (Opsional) -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Ganti Password Baru (Opsional)
                </label>
                <input type="password" name="password" minlength="6" placeholder="Kosongkan jika tidak ingin mengubah password" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Khusus Guru: Penugasan Wali Kelas & Kaprogli -->
            <div id="edit-guru-fields" class="space-y-4 pt-2 border-t border-gray-100">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Tugaskan Sebagai Wali Kelas
                    </label>
                    <select name="school_class_id" id="edit-user-class" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Bukan Wali Kelas (Hanya Guru Pengajar) --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Penugasan Kepala Program Keahlian / Jurusan
                    </label>
                    <select name="major_id" id="edit-user-major" class="w-full border border-gray-300 rounded-xl text-sm p-2.5 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Tidak Ditugaskan di Jurusan Tertentu --</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold text-xs hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition">
                    Perbarui Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal Tambah User
    function openAddUserModal() {
        document.getElementById('modal-add-user').classList.remove('hidden');
        toggleAddRoleFields();
    }
    function closeAddUserModal() {
        document.getElementById('modal-add-user').classList.add('hidden');
    }
    function toggleAddRoleFields() {
        const role = document.getElementById('add-user-role').value;
        const guruFields = document.getElementById('add-guru-fields');
        if (role === 'guru') {
            guruFields.classList.remove('hidden');
        } else {
            guruFields.classList.add('hidden');
        }
    }

    // Modal Edit User
    function openEditUserModal(id, name, email, role, classId, majorId) {
        document.getElementById('form-edit-user').action = `/admin/users/${id}`;
        document.getElementById('edit-user-name').value = name;
        document.getElementById('edit-user-email').value = email;
        document.getElementById('edit-user-role').value = role || 'admin';
        document.getElementById('edit-user-class').value = classId || '';
        document.getElementById('edit-user-major').value = majorId || '';
        toggleEditRoleFields();
        document.getElementById('modal-edit-user').classList.remove('hidden');
    }
    function closeEditUserModal() {
        document.getElementById('modal-edit-user').classList.add('hidden');
    }
    function toggleEditRoleFields() {
        const role = document.getElementById('edit-user-role').value;
        const guruFields = document.getElementById('edit-guru-fields');
        if (role === 'guru') {
            guruFields.classList.remove('hidden');
        } else {
            guruFields.classList.add('hidden');
        }
    }

    // Modal Reset Password Cepat
    function openResetPasswordModal(id, name, email) {
        document.getElementById('form-reset-password').action = `/admin/users/${id}/reset-password`;
        document.getElementById('reset-user-name').innerText = name;
        document.getElementById('reset-user-email').innerText = email;
        document.getElementById('new-password-input').value = '';
        document.getElementById('modal-reset-password').classList.remove('hidden');
    }
    function closeResetPasswordModal() {
        document.getElementById('modal-reset-password').classList.add('hidden');
    }
    function generateRandomPassword() {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
        let pass = '';
        for (let i = 0; i < 8; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('new-password-input').value = pass;
    }
</script>
@endsection
