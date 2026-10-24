@extends('layouts.app')
@section('content')
<div class="mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Sinkronisasi & Sebar Data Mesin</h2>
            <p class="text-gray-500 text-sm mt-1">Atur penyebaran data Siswa atau Guru ke mesin fingerprint tertentu secara terukur (batch) agar server tidak overload.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span>
                ADMS Server Siaga
            </span>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">🎓</div>
        <div>
            <div class="text-xs text-gray-400 font-medium">Total Siswa</div>
            <div class="text-lg font-bold text-gray-800" id="stat-total-students">{{ $studentsCount ?? 0 }}</div>
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg">👨‍🏫</div>
        <div>
            <div class="text-xs text-gray-400 font-medium">Total Guru</div>
            <div class="text-lg font-bold text-gray-800" id="stat-total-teachers">{{ $teachersCount ?? 0 }}</div>
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-lg">⚡</div>
        <div>
            <div class="text-xs text-gray-400 font-medium">Template Sidik Jari</div>
            <div class="text-lg font-bold text-gray-800">{{ $templatesCount ?? 0 }}</div>
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">📟</div>
        <div>
            <div class="text-xs text-gray-400 font-medium">Mesin Terdaftar</div>
            <div class="text-lg font-bold text-gray-800">{{ $devices->count() }} Mesin</div>
        </div>
    </div>
</div>

<!-- Card Utama: Sebar Data Kustom & Bertahap -->
<div class="bg-white rounded-2xl shadow-sm border border-indigo-100 overflow-hidden mb-8">
    <div class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-blue-700 p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold backdrop-blur-sm mb-2 text-indigo-100">
                    <span>⚡ Fitur Fleksibel</span>
                    <span>•</span>
                    <span>Anti Server Down</span>
                </div>
                <h3 class="text-xl font-bold">Kustomisasi Sebar Data ke Mesin Fingerprint</h3>
                <p class="text-indigo-100 text-sm mt-1 max-w-2xl">
                    Pilih target data (Siswa per kelas atau Guru), tentukan mesin tujuan spesifik, dan kirim bertahap (50 orang per batch) agar koneksi mesin dan server tetap stabil.
                </p>
            </div>
            <div class="hidden md:block text-right">
                <span class="text-3xl">📡</span>
            </div>
        </div>
    </div>

    <form id="custom-sync-form" action="{{ route('sync.push') }}" method="POST" class="p-6 space-y-6" data-confirm="Kirim data terpilih ke antrean mesin fingerprint?" data-title="Konfirmasi Sebar Data" data-icon="question">
        @csrf

        <!-- Langkah 1: Pilih Sumber & Tipe Data -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
                1. Pilih Sasaran Data yang Ingin Disebar:
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 border-indigo-500 bg-indigo-50/40 hover:bg-indigo-50" id="label-target-students">
                    <input type="radio" name="target_type" value="students" class="sr-only" checked onchange="handleTargetTypeChange(this.value)">
                    <div class="flex items-center gap-3 w-full">
                        <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-lg shrink-0">
                            🎓
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-gray-900 text-sm flex items-center justify-between">
                                <span>Data Siswa</span>
                                <span class="text-xs bg-indigo-100 text-indigo-700 font-semibold px-2 py-0.5 rounded-full">{{ $studentsCount }} Siswa</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Sebar data siswa dari seluruh kelas atau kelas tertentu.</p>
                        </div>
                    </div>
                </label>

                <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 border-gray-200 hover:border-indigo-300 hover:bg-gray-50" id="label-target-teachers">
                    <input type="radio" name="target_type" value="teachers" class="sr-only" onchange="handleTargetTypeChange(this.value)">
                    <div class="flex items-center gap-3 w-full">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-lg shrink-0">
                            👨‍🏫
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-gray-900 text-sm flex items-center justify-between">
                                <span>Data Dewan Guru</span>
                                <span class="text-xs bg-gray-100 text-gray-700 font-semibold px-2 py-0.5 rounded-full">{{ $teachersCount }} Guru</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Sebar data guru & staf pengajar ke mesin fingerprint.</p>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Filter Kelas (Khusus Siswa) -->
            <div id="wrapper-class-filter" class="transition-all duration-200">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                    <span>Filter Kelas Siswa</span>
                    <span class="text-[11px] text-gray-400 font-normal">Pilih spesifik atau semua</span>
                </label>
                <div class="relative">
                    <select name="school_class_id" id="select-school-class" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" onchange="updateBatchCalculation()">
                        <option value="all" data-count="{{ $studentsCount }}">-- Semua Kelas (Total {{ $studentsCount }} Siswa) --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" data-count="{{ $c->students_count }}">
                                {{ $c->name }} ({{ $c->students_count }} Siswa)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Target Mesin Fingerprint -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                    <span>2. Target Mesin Fingerprint</span>
                    <span class="text-[11px] text-gray-400 font-normal">Tujuan pengiriman</span>
                </label>
                <div class="relative">
                    <select name="device_id" id="select-device" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" onchange="updateSummaryPreview()">
                        <option value="all">⚡ Semua Mesin (Otomatis Sesuai Peruntukan)</option>
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}" data-purpose="{{ $d->purpose }}">
                                📟 {{ $d->name }} (SN: {{ $d->serial_number ?? '-' }}) — {{ $d->purpose_label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Langkah 3: Pengaturan Batching (Chunking 50 Orang) -->
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4.5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <span>3. Pengaturan Batch (Anti Server & Mesin Overload)</span>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-semibold px-2 py-0.5 rounded">Rekomendasi 50 Orang</span>
                </label>
                <button type="button" onclick="toggleCustomRange()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                    <span id="btn-toggle-range-text">⚙️ Gunakan Offset & Limit Manual</span>
                </button>
            </div>

            <!-- Mode Standar Batch -->
            <div id="mode-standard-batch" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 font-medium mb-1">Ukuran Data per Pengiriman (Batch Size)</label>
                    <select name="batch_size" id="select-batch-size" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" onchange="updateBatchCalculation()">
                        <option value="50" selected>50 Orang per Batch (⭐ Direkomendasikan)</option>
                        <option value="25">25 Orang per Batch (Koneksi Lebih Ringan)</option>
                        <option value="100">100 Orang per Batch (Jaringan Cepat)</option>
                        <option value="all">Kirim Semua Sekaligus (Tanpa Batch)</option>
                    </select>
                </div>
                <div id="wrapper-batch-page">
                    <label class="block text-xs text-gray-500 font-medium mb-1">Pilih Batch yang Ingin Disebar</label>
                    <select name="batch_page" id="select-batch-page" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 font-medium text-indigo-700" onchange="updateSummaryPreview()">
                        <!-- Diisi otomatis oleh JavaScript -->
                    </select>
                </div>
            </div>

            <!-- Mode Manual Custom Range (Offset & Limit) -->
            <div id="mode-custom-range" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs text-gray-500 font-medium mb-1">Mulai Dari Urutan Ke- (Offset):</label>
                    <input type="number" name="custom_offset" id="input-custom-offset" placeholder="Contoh: 0 untuk orang pertama, 50 untuk ke-51" min="0" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" oninput="updateSummaryPreview()">
                    <span class="text-[11px] text-gray-400">0 = mulai dari data paling awal</span>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 font-medium mb-1">Jumlah Orang yang Dikirim (Limit):</label>
                    <input type="number" name="custom_limit" id="input-custom-limit" placeholder="Contoh: 50" min="1" max="1000" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" oninput="updateSummaryPreview()">
                    <span class="text-[11px] text-gray-400">Maksimal disarankan 50-100 orang per kirim</span>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="include_fingerprints" id="chk-fingerprints" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onchange="updateSummaryPreview()">
                    <label for="chk-fingerprints" class="cursor-pointer font-medium text-gray-700">Sertakan Template Sidik Jari Biometrik yang tersimpan</label>
                </div>
            </div>
        </div>

        <!-- Box Live Ringkasan Sebelum Kirim -->
        <div class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="text-xs font-bold uppercase tracking-wider text-indigo-900 flex items-center gap-2">
                    <span>📋 Ringkasan Rencana Pengiriman:</span>
                    <span id="badge-server-load" class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-700">🟢 Beban Server: Sangat Ringan</span>
                </div>
                <div class="text-xs text-indigo-800" id="summary-details">
                    Menghitung data...
                </div>
            </div>
            <button type="submit" id="btn-submit-sync" class="w-full sm:w-auto shrink-0 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                <span>⚡</span>
                <span id="btn-submit-label">Mulai Sebar Data</span>
            </button>
        </div>
    </form>
</div>

<h3 class="font-bold text-gray-700 text-lg mb-4">Fitur Tambahan (Tarik Data dari Mesin)</h3>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Card Tarik Siswa -->
    <div class="bg-white p-6 shadow-sm rounded-xl border-t-4 border-blue-500">
        <h3 class="font-bold mb-2 text-lg text-blue-700">1. Tarik Data Siswa dari Mesin</h3>
        <p class="text-sm text-gray-500 mb-4">Menarik daftar nama dan NIS dari memori mesin ke dalam database web.</p>
        <form action="{{ route('students.sync') }}" method="POST" data-confirm="Tarik data siswa dari mesin ke database web?" data-title="Tarik Data Siswa" data-icon="question">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-bold mb-1">Pilih Mesin Asal</label>
                <select name="device_id" class="border w-full p-2 rounded text-sm" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">Masukkan ke Kelas</label>
                <select name="school_class_id" class="border w-full p-2 rounded text-sm" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm w-full">
                Tarik Data Siswa
            </button>
        </form>
    </div>

    <!-- Card Tarik Sidik Jari -->
    <div class="bg-white p-6 shadow-sm rounded-xl border-t-4 border-purple-500 flex flex-col justify-between">
        <div>
            <h3 class="font-bold mb-2 text-lg text-purple-700">2. Backup Sidik Jari dari Mesin</h3>
            <p class="text-sm text-gray-500 mb-4">Menarik template sidik jari fisik dari mesin ke database cloud (untuk disebar ke mesin lain).</p>
        </div>
        <form action="{{ route('sync.backupTemplates') }}" method="POST" data-confirm="Kirim perintah penarikan seluruh template sidik jari dari mesin ke server cloud?" data-title="Backup Sidik Jari" data-icon="question">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">Pilih Mesin Asal</label>
                <select name="device_id" class="border w-full p-2 rounded text-sm" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded text-sm w-full">
                Backup Template Sidik Jari
            </button>
        </form>
    </div>

    <!-- Card Tarik Absen -->
    <div class="bg-white p-6 shadow-sm rounded-xl border-t-4 border-green-500 flex flex-col justify-between">
        <div>
            <h3 class="font-bold mb-2 text-lg text-green-700">3. Tarik Log Absensi Tertinggal</h3>
            <p class="text-sm text-gray-500 mb-4">Menarik log absensi dari mesin yang belum sempat terkirim secara realtime.</p>
        </div>
        <form action="{{ route('attendance.sync') }}" method="POST" data-confirm="Tarik data absensi dari mesin ke server web?" data-title="Tarik Log Absensi" data-icon="question">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold mb-1">Pilih Mesin</label>
                <select name="device_id" class="border w-full p-2 rounded text-sm" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->serial_number ?? $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm w-full">
                Tarik Log Absensi
            </button>
        </form>
    </div>
</div>

<!-- Live ADMS Monitor Panel -->
<div class="mt-8 bg-white p-6 shadow-sm rounded-xl border border-gray-200">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-bold text-lg text-gray-800 flex items-center">
                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse mr-2"></span>
                📡 Log Komunikasi ADMS Mesin (Live Traffic)
            </h3>
            <p class="text-xs text-gray-500">Memantau request dan respon data antara Mesin Fingerprint dan Server Cloud secara realtime.</p>
        </div>
        <button onclick="window.location.reload()" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-1.5 px-3 rounded-lg border transition-colors">
            🔄 Refresh Log
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b text-gray-600">
                    <th class="p-2.5">Waktu</th>
                    <th class="p-2.5">Serial Number (SN)</th>
                    <th class="p-2.5">Endpoint</th>
                    <th class="p-2.5">Metode</th>
                    <th class="p-2.5">Keterangan / Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admsLogs ?? [] as $log)
                <tr class="border-b hover:bg-gray-50 font-mono">
                    <td class="p-2.5 text-gray-500">{{ $log['time'] }}</td>
                    <td class="p-2.5 font-bold text-indigo-700">{{ $log['sn'] }}</td>
                    <td class="p-2.5 text-gray-700">{{ $log['endpoint'] }}</td>
                    <td class="p-2.5">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $log['method'] === 'POST' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $log['method'] }}
                        </span>
                    </td>
                    <td class="p-2.5 font-sans font-medium text-gray-800">
                        {{ $log['info'] }}
                        @if(!empty($log['preview']))
                            <span class="block text-[10px] text-gray-400 font-mono mt-0.5 truncate max-w-md">{{ $log['preview'] }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-400 font-sans">Belum ada aktivitas request dari mesin fisik.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    const totalStudentsCount = {{ (int) ($studentsCount ?? 0) }};
    const totalTeachersCount = {{ (int) ($teachersCount ?? 0) }};
    let isCustomRangeActive = false;

    function handleTargetTypeChange(type) {
        const studentLabel = document.getElementById('label-target-students');
        const teacherLabel = document.getElementById('label-target-teachers');
        const classFilterWrapper = document.getElementById('wrapper-class-filter');

        if (type === 'students') {
            studentLabel.className = 'relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 border-indigo-500 bg-indigo-50/40 hover:bg-indigo-50';
            teacherLabel.className = 'relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 border-gray-200 hover:border-indigo-300 hover:bg-gray-50';
            classFilterWrapper.style.display = 'block';
        } else {
            teacherLabel.className = 'relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 border-indigo-500 bg-indigo-50/40 hover:bg-indigo-50';
            studentLabel.className = 'relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 border-gray-200 hover:border-indigo-300 hover:bg-gray-50';
            classFilterWrapper.style.display = 'none';
        }

        updateBatchCalculation();
    }

    function toggleCustomRange() {
        isCustomRangeActive = !isCustomRangeActive;
        const standardMode = document.getElementById('mode-standard-batch');
        const customMode = document.getElementById('mode-custom-range');
        const toggleBtnText = document.getElementById('btn-toggle-range-text');

        if (isCustomRangeActive) {
            standardMode.classList.add('hidden');
            customMode.classList.remove('hidden');
            toggleBtnText.innerText = '🔄 Beralih ke Mode Batch Otomatis (Rekomendasi)';
        } else {
            standardMode.classList.remove('hidden');
            customMode.classList.add('hidden');
            toggleBtnText.innerText = '⚙️ Gunakan Offset & Limit Manual';
            document.getElementById('input-custom-offset').value = '';
            document.getElementById('input-custom-limit').value = '';
        }

        updateSummaryPreview();
    }

    function getSelectedTotalCount() {
        const targetType = document.querySelector('input[name="target_type"]:checked')?.value || 'students';
        if (targetType === 'teachers') {
            return totalTeachersCount;
        }

        const classSelect = document.getElementById('select-school-class');
        const selectedOpt = classSelect.options[classSelect.selectedIndex];
        return parseInt(selectedOpt?.getAttribute('data-count') || totalStudentsCount, 10);
    }

    function updateBatchCalculation() {
        const total = getSelectedTotalCount();
        const batchSizeVal = document.getElementById('select-batch-size').value;
        const batchPageWrapper = document.getElementById('wrapper-batch-page');
        const selectBatchPage = document.getElementById('select-batch-page');

        selectBatchPage.innerHTML = '';

        if (batchSizeVal === 'all') {
            batchPageWrapper.style.display = 'none';
            const opt = document.createElement('option');
            opt.value = '1';
            opt.innerText = `Semua Sekaligus (1 - ${total} orang)`;
            selectBatchPage.appendChild(opt);
        } else {
            batchPageWrapper.style.display = 'block';
            const batchSize = parseInt(batchSizeVal, 10) || 50;
            const totalBatches = Math.max(1, Math.ceil(total / batchSize));

            for (let i = 1; i <= totalBatches; i++) {
                const start = (i - 1) * batchSize + 1;
                const end = Math.min(total, i * batchSize);
                const opt = document.createElement('option');
                opt.value = i;
                opt.innerText = `Batch ${i}: Orang ke-${start} s/d ${end} (${end - start + 1} orang)`;
                selectBatchPage.appendChild(opt);
            }
        }

        updateSummaryPreview();
    }

    function updateSummaryPreview() {
        const targetType = document.querySelector('input[name="target_type"]:checked')?.value || 'students';
        const total = getSelectedTotalCount();
        const deviceSelect = document.getElementById('select-device');
        const deviceText = deviceSelect.options[deviceSelect.selectedIndex].text.replace(/^[📟⚡]\s*/, '');
        const withTemplates = document.getElementById('chk-fingerprints').checked;

        let targetLabel = '';
        if (targetType === 'teachers') {
            targetLabel = `Dewan Guru (${total} orang)`;
        } else {
            const classSelect = document.getElementById('select-school-class');
            const classText = classSelect.options[classSelect.selectedIndex].text;
            targetLabel = classSelect.value === 'all' ? `Semua Siswa (${total} orang)` : `Siswa ${classText}`;
        }

        let batchText = '';
        let countToSend = total;
        let serverLoadBadge = '';

        if (isCustomRangeActive) {
            const offset = parseInt(document.getElementById('input-custom-offset').value || 0, 10);
            const limit = parseInt(document.getElementById('input-custom-limit').value || total, 10);
            const start = offset + 1;
            const end = Math.min(total, offset + limit);
            countToSend = Math.max(0, end - offset);
            batchText = `Rentang Manual (Urutan ke-${start} s/d ${end}, total ${countToSend} orang)`;
        } else {
            const batchSizeVal = document.getElementById('select-batch-size').value;
            if (batchSizeVal === 'all') {
                batchText = `Kirim Semua Sekaligus (${total} orang)`;
                countToSend = total;
            } else {
                const selectBatchPage = document.getElementById('select-batch-page');
                const selectedBatchOpt = selectBatchPage.options[selectBatchPage.selectedIndex];
                batchText = selectedBatchOpt ? selectedBatchOpt.text : `Batch 1`;
                const batchSize = parseInt(batchSizeVal, 10);
                countToSend = Math.min(total, batchSize);
            }
        }

        const badgeEl = document.getElementById('badge-server-load');
        if (countToSend <= 50) {
            badgeEl.className = 'text-[10px] px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-700';
            badgeEl.innerText = '🟢 Beban Server: Sangat Ringan (Stabil)';
        } else if (countToSend <= 100) {
            badgeEl.className = 'text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-100 text-amber-700';
            badgeEl.innerText = '🟡 Beban Server: Sedang';
        } else {
            badgeEl.className = 'text-[10px] px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700';
            badgeEl.innerText = '🔴 Beban Server: Tinggi (Banyak Data Sekaligus)';
        }

        const summaryDetails = document.getElementById('summary-details');
        summaryDetails.innerHTML = `
            <div>• <strong>Target Data:</strong> ${targetLabel}</div>
            <div>• <strong>Tujuan Mesin:</strong> ${deviceText}</div>
            <div>• <strong>Pengiriman:</strong> ${batchText} ${withTemplates ? '<em>+ Sidik Jari</em>' : '(Tanpa sidik jari)'}</div>
        `;

        const submitBtnLabel = document.getElementById('btn-submit-label');
        submitBtnLabel.innerText = isCustomRangeActive ? `Mulai Sebar Data (${countToSend} Orang)` : `Mulai Sebar Data (${batchText.split(':')[0]})`;

        // Update confirm dialog message
        const form = document.getElementById('custom-sync-form');
        form.setAttribute('data-confirm', `Kirim data ${targetLabel} sejumlah ${countToSend} orang ke ${deviceText}?`);
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateBatchCalculation();
    });
</script>
@endsection