@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-rose-700 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold uppercase tracking-wider text-amber-100 mb-2 border border-white/20">
                    <i class="fas fa-tv text-yellow-300"></i> Mode TV Display
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Pengajuan Pengumuman Layar TV
                </h1>
                <p class="text-orange-100 mt-1 text-sm sm:text-base max-w-2xl">
                    Ajukan jadwal ulangan, reminder tugas, atau pengumuman sekolah untuk ditayangkan di layar monitor TV. Pengumuman dari guru memerlukan persetujuan Administrator sebelum live.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="document.getElementById('modal-create-announcement').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-white text-orange-700 hover:bg-orange-50 font-bold px-5 py-3 rounded-xl shadow-lg transition active:scale-95 text-sm">
                    <i class="fas fa-plus-circle text-base"></i>
                    <span>Buat Pengumuman Baru</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Announcements List -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-list-alt text-orange-600"></i>
                Riwayat Pengajuan Pengumuman Saya
            </h2>
            <span class="text-xs text-gray-500 font-medium">Total: {{ $announcements->total() }} Pengumuman</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4">Kategori & Judul</th>
                        <th class="py-3 px-4">Sasaran Kelas</th>
                        <th class="py-3 px-4 text-center">Periode Tayang</th>
                        <th class="py-3 px-4 text-center">Status Approval</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($announcements as $ann)
                    @php $badge = $ann->type_badge; @endphp
                    <tr class="hover:bg-gray-50/70 transition">
                        <td class="py-4 px-4 max-w-md">
                            <div class="flex items-start gap-2.5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $badge['bg'] }} shrink-0 mt-0.5">
                                    <i class="fas {{ $badge['icon'] }}"></i> {{ $ann->type_label }}
                                </span>
                                <div>
                                    <h4 class="font-bold text-gray-900 leading-snug">{{ $ann->title }}</h4>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2 leading-relaxed">{{ $ann->content }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-md bg-gray-100 text-gray-700">
                                <i class="fas fa-chalkboard text-gray-400"></i>
                                {{ $ann->schoolClass ? $ann->schoolClass->name : 'Seluruh Siswa' }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center text-xs">
                            <div class="font-semibold text-gray-800">
                                {{ \Carbon\Carbon::parse($ann->start_date)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($ann->end_date)->translatedFormat('d M Y') }}
                            </div>
                            @php
                                $today = \Carbon\Carbon::today('Asia/Jakarta')->toDateString();
                                $isExpired = $ann->end_date->toDateString() < $today;
                            @endphp
                            @if($isExpired)
                                <span class="text-[10px] text-gray-400 font-semibold uppercase">Kedaluwarsa</span>
                            @else
                                <span class="text-[10px] text-emerald-600 font-semibold uppercase">Aktif Periode</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if($ann->status === 'approved')
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    <i class="fas fa-check-circle"></i> Disetujui (Tayang TV)
                                </span>
                                @if($ann->approver)
                                <span class="text-[10px] text-gray-400 mt-0.5">Disetujui oleh: {{ $ann->approver->name }}</span>
                                @endif
                            </div>
                            @elseif($ann->status === 'rejected')
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                    <i class="fas fa-times-circle"></i> Ditolak Admin
                                </span>
                                @if($ann->rejection_reason)
                                <span class="text-[10px] text-rose-600 mt-0.5 max-w-[180px] truncate" title="{{ $ann->rejection_reason }}">
                                    Alasan: {{ $ann->rejection_reason }}
                                </span>
                                @endif
                            </div>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                <i class="fas fa-hourglass-half"></i> Menunggu Persetujuan
                            </span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <form action="{{ route('announcements.destroy', $ann->id) }}" method="POST" data-confirm="Hapus pengumuman '{{ $ann->title }}'?" data-title="Konfirmasi Hapus" data-danger="true" data-icon="warning">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Pengumuman">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            <i class="fas fa-bullhorn text-4xl mb-3 text-gray-300"></i>
                            <p class="font-medium">Belum ada pengumuman yang Anda ajukan.</p>
                            <p class="text-xs text-gray-400 mt-1">Klik tombol "Buat Pengumuman Baru" di atas untuk menambahkan informasi.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($announcements->hasPages())
        <div class="mt-4">
            {{ $announcements->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Form Pengajuan Pengumuman Baru -->
<div id="modal-create-announcement" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden border border-gray-100 transform transition-all">
        <div class="bg-gradient-to-r from-orange-600 to-amber-600 p-5 text-white flex items-center justify-between">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-bullhorn"></i> Buat Pengajuan Pengumuman TV
            </h3>
            <button type="button" onclick="document.getElementById('modal-create-announcement').classList.add('hidden')" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('announcements.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <!-- Kategori / Tipe -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Kategori Informasi</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2.5 border rounded-xl cursor-pointer hover:bg-amber-50/50 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                        <input type="radio" name="type" value="exam" class="accent-amber-600" required>
                        <span class="text-xs font-semibold text-gray-800"><i class="fas fa-file-signature text-amber-500 mr-1"></i> Ulangan / Ujian</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 border rounded-xl cursor-pointer hover:bg-purple-50/50 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50">
                        <input type="radio" name="type" value="reminder" class="accent-purple-600">
                        <span class="text-xs font-semibold text-gray-800"><i class="fas fa-bell text-purple-500 mr-1"></i> Pengingat Tugas</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 border rounded-xl cursor-pointer hover:bg-blue-50/50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="type" value="info" class="accent-blue-600" checked>
                        <span class="text-xs font-semibold text-gray-800"><i class="fas fa-info-circle text-blue-500 mr-1"></i> Info Umum</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 border rounded-xl cursor-pointer hover:bg-rose-50/50 has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50">
                        <input type="radio" name="type" value="urgent" class="accent-rose-600">
                        <span class="text-xs font-semibold text-gray-800"><i class="fas fa-exclamation-triangle text-rose-500 mr-1"></i> Mendesak</span>
                    </label>
                </div>
            </div>

            <!-- Judul -->
            <div>
                <label for="title" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Pengumuman</label>
                <input type="text" id="title" name="title" required maxlength="150" placeholder="Contoh: Ulangan Harian Pemrograman Web XII RPL 1" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <!-- Sasaran Kelas -->
            <div>
                <label for="school_class_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Sasaran Kelas (Opsional)</label>
                <select id="school_class_id" name="school_class_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="">Semua Kelas (Umum)</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}" {{ ($user->isGuru() && $user->school_class_id === $cls->id) ? 'selected' : '' }}>
                            {{ $cls->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Periode Tanggal Tayang -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="start_date" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mulai Tayang</label>
                    <input type="date" id="start_date" name="start_date" value="{{ \Carbon\Carbon::today('Asia/Jakarta')->toDateString() }}" required class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Selesai Tayang</label>
                    <input type="date" id="end_date" name="end_date" value="{{ \Carbon\Carbon::today('Asia/Jakarta')->addDays(3)->toDateString() }}" required class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <!-- Konten Isi Pengumuman -->
            <div>
                <label for="content" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Isi Pengumuman (Tampil di Layar TV)</label>
                <textarea id="content" name="content" rows="3" required maxlength="1000" placeholder="Tuliskan detail jadwal, materi ulangan, atau pengingat yang ingin dibaca siswa..." class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                <p class="text-[11px] text-gray-400 mt-1">Pengumuman akan tayang di slide carousel dan running text TV setelah disetujui Admin.</p>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-create-announcement').classList.add('hidden')" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-orange-600 hover:bg-orange-700 text-white shadow-md transition active:scale-95 flex items-center gap-1.5">
                    <i class="fas fa-paper-plane"></i> Ajukan Pengumuman
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
