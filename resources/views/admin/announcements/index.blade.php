@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-indigo-700 via-blue-700 to-sky-700 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur text-xs font-semibold uppercase tracking-wider text-blue-100 mb-2 border border-white/20">
                    <i class="fas fa-check-double text-yellow-300"></i> Administrator Hub
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Persetujuan Pengumuman Layar TV
                </h1>
                <p class="text-blue-100 mt-1 text-sm sm:text-base max-w-2xl">
                    Tinjau pengajuan informasi, ulangan, dan reminder dari dewan guru sebelum ditampilkan ke layar TV Display sekolah.
                </p>
            </div>

            <!-- Status Counters -->
            <div class="flex items-center gap-3">
                <div class="bg-white/15 backdrop-blur border border-white/20 rounded-xl px-4 py-2.5 text-center">
                    <div class="text-2xl font-black text-amber-300">{{ $pendingCount }}</div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-blue-100">Menunggu</div>
                </div>
                <div class="bg-white/15 backdrop-blur border border-white/20 rounded-xl px-4 py-2.5 text-center">
                    <div class="text-2xl font-black text-emerald-300">{{ $approvedCount }}</div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-blue-100">Disetujui</div>
                </div>
                <div class="bg-white/15 backdrop-blur border border-white/20 rounded-xl px-4 py-2.5 text-center">
                    <div class="text-2xl font-black text-rose-300">{{ $rejectedCount }}</div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-blue-100">Ditolak</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.announcements.index', ['status' => 'all']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $statusFilter === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Semua
            </a>
            <a href="{{ route('admin.announcements.index', ['status' => 'pending']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                <i class="fas fa-hourglass-half"></i> Menunggu Approval ({{ $pendingCount }})
            </a>
            <a href="{{ route('admin.announcements.index', ['status' => 'approved']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'approved' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                <i class="fas fa-check-circle"></i> Disetujui / Live ({{ $approvedCount }})
            </a>
            <a href="{{ route('admin.announcements.index', ['status' => 'rejected']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'rejected' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}">
                <i class="fas fa-times-circle"></i> Ditolak ({{ $rejectedCount }})
            </a>
        </div>

        <a href="{{ route('tv.index') }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200">
            <i class="fas fa-tv"></i> Pratinjau Mode TV
        </a>
    </div>

    <!-- Announcements Approval Cards / Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="py-3 px-4">Pengusul & Sasaran</th>
                        <th class="py-3 px-4">Pengumuman</th>
                        <th class="py-3 px-4 text-center">Periode Tayang</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Tindakan Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($announcements as $ann)
                    @php $badge = $ann->type_badge; @endphp
                    <tr class="hover:bg-gray-50/70 transition">
                        <td class="py-4 px-4 align-top w-56">
                            <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                <i class="fas fa-user-circle text-gray-400"></i>
                                {{ $ann->user->name ?? 'User' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                Role: <span class="capitalize font-semibold text-gray-700">{{ $ann->user->role ?? 'admin' }}</span>
                            </div>
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                    <i class="fas fa-chalkboard text-xs"></i>
                                    {{ $ann->schoolClass ? $ann->schoolClass->name : 'Semua Siswa' }}
                                </span>
                            </div>
                        </td>

                        <td class="py-4 px-4 align-top max-w-lg">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="px-2.5 py-0.5 rounded text-[11px] font-bold {{ $badge['bg'] }}">
                                    <i class="fas {{ $badge['icon'] }}"></i> {{ $ann->type_label }}
                                </span>
                                <span class="text-xs text-gray-400">Diajukan: {{ $ann->created_at->diffForHumans() }}</span>
                            </div>
                            <h4 class="font-extrabold text-gray-900 text-base leading-snug">{{ $ann->title }}</h4>
                            <p class="text-xs text-gray-600 mt-1.5 leading-relaxed bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                {{ $ann->content }}
                            </p>
                            @if($ann->rejection_reason)
                            <div class="mt-2 text-xs text-rose-600 bg-rose-50 p-2 rounded-lg border border-rose-100">
                                <strong>Alasan Penolakan:</strong> {{ $ann->rejection_reason }}
                            </div>
                            @endif
                        </td>

                        <td class="py-4 px-4 align-top text-center text-xs whitespace-nowrap">
                            <div class="font-semibold text-gray-800">
                                {{ \Carbon\Carbon::parse($ann->start_date)->translatedFormat('d M') }} s.d {{ \Carbon\Carbon::parse($ann->end_date)->translatedFormat('d M Y') }}
                            </div>
                            @php
                                $today = \Carbon\Carbon::today('Asia/Jakarta')->toDateString();
                                $isExpired = $ann->end_date->toDateString() < $today;
                            @endphp
                            @if($isExpired)
                                <span class="text-[10px] text-gray-400 font-bold uppercase mt-1 block">Kedaluwarsa</span>
                            @else
                                <span class="text-[10px] text-emerald-600 font-bold uppercase mt-1 block">Aktif</span>
                            @endif
                        </td>

                        <td class="py-4 px-4 align-top text-center whitespace-nowrap">
                            @if($ann->status === 'approved')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                <i class="fas fa-check-circle"></i> Disetujui
                            </span>
                            @elseif($ann->status === 'rejected')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                <i class="fas fa-times-circle"></i> Ditolak
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                <i class="fas fa-hourglass-half"></i> Pending
                            </span>
                            @endif
                        </td>

                        <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($ann->status !== 'approved')
                                <form action="{{ route('admin.announcements.approve', $ann->id) }}" method="POST" data-confirm="Setujui pengumuman '{{ $ann->title }}' untuk langsung ditayangkan di layar TV?" data-title="Setujui Pengumuman" data-danger="false" data-icon="question">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition active:scale-95" title="Setujui Pengumuman">
                                        <i class="fas fa-check"></i> Setujui
                                    </button>
                                </form>
                                @endif

                                @if($ann->status !== 'rejected')
                                <button type="button" onclick="openRejectModal({{ $ann->id }}, '{{ addslashes($ann->title) }}')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition active:scale-95" title="Tolak Pengumuman">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                                @endif

                                <form action="{{ route('admin.announcements.destroy', $ann->id) }}" method="POST" data-confirm="Hapus permanen pengumuman '{{ $ann->title }}'?" data-title="Hapus Pengumuman" data-danger="true" data-icon="warning">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-gray-100 transition" title="Hapus Permanen">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            <i class="fas fa-check-circle text-4xl mb-3 text-emerald-400"></i>
                            <p class="font-medium">Tidak ada pengumuman yang sesuai dengan filter.</p>
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

<!-- Modal Tolak Pengumuman -->
<div id="modal-reject" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden border border-gray-100">
        <div class="bg-rose-600 p-4 text-white flex items-center justify-between">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fas fa-times-circle"></i> Tolak Pengajuan Pengumuman
            </h3>
            <button type="button" onclick="document.getElementById('modal-reject').classList.add('hidden')" class="text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="form-reject" method="POST" class="p-5 space-y-4">
            @csrf
            <div>
                <p class="text-xs text-gray-600 mb-2">Pengumuman: <strong id="reject-ann-title" class="text-gray-900"></strong></p>
                <label for="rejection_reason" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Alasan Penolakan</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Contoh: Jadwal bertabrakan dengan kegiatan sekolah / Redaksi kurang jelas..." class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-reject').classList.add('hidden')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-100">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm">
                    Konfirmasi Tolak
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(id, title) {
        const modal = document.getElementById('modal-reject');
        const form = document.getElementById('form-reject');
        const titleEl = document.getElementById('reject-ann-title');

        form.action = `/admin/announcements/${id}/reject`;
        titleEl.textContent = title;
        modal.classList.remove('hidden');
    }
</script>
@endsection
