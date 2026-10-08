@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Log Absensi Siswa</h2>
</div>

<div class="mb-4 flex justify-between items-end">
    <div class="border-b border-gray-200 flex-1">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('attendance.index', ['filter' => 'all']) }}" class="{{ $filter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Semua Data
            </a>
            <a href="{{ route('attendance.index', ['filter' => 'registered']) }}" class="{{ $filter === 'registered' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Siswa Terdaftar
            </a>
            <a href="{{ route('attendance.index', ['filter' => 'unregistered']) }}" class="{{ $filter === 'unregistered' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Belum Terdaftar
            </a>
        </nav>
    </div>
    
    <div class="flex items-center space-x-4">
        <!-- Tombol Toggle Anti-Duplikat -->
        <form action="{{ route('attendance.toggleStrict') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="text-sm px-3 py-2 rounded font-bold transition-all {{ $isStrict ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-gray-100 text-gray-600 border border-gray-300' }}">
                <i class="fas fa-shield-alt mr-1"></i> Anti-Duplikat Harian: {{ $isStrict ? 'ON' : 'OFF' }}
            </button>
        </form>

        <!-- Tombol Hapus Massal -->
        <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-all">
            Hapus Terpilih (<span id="count-selected">0</span>)
        </button>
    </div>
</div>

<div class="bg-white shadow rounded overflow-x-auto p-4">
    <form id="bulk-delete-form" action="{{ route('attendance.destroyMultiple') }}" method="POST" onsubmit="return confirm('Hapus semua data absensi yang dicentang?')">
        @csrf
        <table class="w-full text-left border-collapse" id="attendance-table">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="p-3 w-10 text-center">
                        <input type="checkbox" id="check-all" class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    </th>
                    <th class="p-3 text-sm">Waktu</th>
                    <th class="p-3 text-sm">Nama Siswa</th>
                    <th class="p-3 text-sm">Kelas</th>
                    <th class="p-3 text-sm">Status</th>
                    <th class="p-3 text-sm">Mesin Asal</th>
                    <th class="p-3 text-sm text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="attendance-table-body">
                @forelse($logs as $log)
                <tr class="border-b hover:bg-gray-50" id="log-{{ $log->id }}">
                    <td class="p-3 text-center">
                        <input type="checkbox" name="ids[]" value="{{ $log->id }}" class="check-item w-4 h-4 text-blue-600 rounded border-gray-300">
                    </td>
                    <td class="p-3">{{ $log->timestamp }}</td>
                    <td class="p-3 font-semibold">{{ $log->student->name ?? 'Belum terdaftar' }}</td>
                    <td class="p-3">{{ $log->student->schoolClass->name ?? '-' }}</td>
                    <td class="p-3">
                        @if($log->status_code == 0)
                            <span class="text-blue-600 font-semibold">Masuk</span>
                        @elseif($log->status_code == 1)
                            <span class="text-red-600 font-semibold">Pulang</span>
                        @else
                            {{ $log->status_code }}
                        @endif
                    </td>
                    <td class="p-3 text-xs text-gray-500">{{ $log->device->name }}</td>
                    <td class="p-3 text-center">
                        <button type="button" onclick="deleteSingle({{ $log->id }})" class="text-red-600 hover:underline text-sm">Hapus</button>
                    </td>
                </tr>
                @empty
                <tr id="empty-row">
                    <td colspan="7" class="p-4 text-center text-gray-500">Belum ada absensi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </form>
    
    <!-- Form tersembunyi untuk hapus satuan agar tidak bentrok dengan form massal -->
    <form id="single-delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <div class="p-4">
        {{ $logs->links() }}
    </div>
</div>

<script>
    // Logic untuk Delete Satuan via JS
    function deleteSingle(id) {
        if(confirm('Hapus absensi ini?')) {
            const form = document.getElementById('single-delete-form');
            form.action = `/attendance/${id}`;
            form.submit();
        }
    }

    // Logic Checkbox & Hapus Massal
    document.addEventListener("DOMContentLoaded", () => {
        const checkAll = document.getElementById('check-all');
        const checkItems = document.querySelectorAll('.check-item');
        const btnBulkDelete = document.getElementById('btn-bulk-delete');
        const countSelected = document.getElementById('count-selected');

        function updateBulkButton() {
            const checkedCount = document.querySelectorAll('.check-item:checked').length;
            countSelected.innerText = checkedCount;
            
            if (checkedCount > 0) {
                btnBulkDelete.classList.remove('hidden');
            } else {
                btnBulkDelete.classList.add('hidden');
                checkAll.checked = false;
            }
        }

        // Event listener Check All
        checkAll.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.check-item').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateBulkButton();
        });

        // Event listener tiap checkbox (Pakai event delegation ke table-body agar efek ke baris realtime)
        document.getElementById('attendance-table-body').addEventListener('change', function(e) {
            if(e.target && e.target.classList.contains('check-item')) {
                updateBulkButton();
                
                // Uncheck 'check-all' jika ada yang tidak dicentang
                if(!e.target.checked) checkAll.checked = false;
            }
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const currentFilter = "{{ $filter }}";
        // Ambil ID log tertinggi yang saat ini ada di halaman
        let maxLogId = {{ $logs->first() ? $logs->first()->id : 0 }};

        function insertLogRow(log, isNew = true) {
            // Hindari duplikasi baris
            if (document.getElementById(`log-${log.id}`)) return;

            if (log.id > maxLogId) {
                maxLogId = log.id;
            }

            const isRegistered = log.student_id !== null;
            if (currentFilter === 'registered' && !isRegistered) return;
            if (currentFilter === 'unregistered' && isRegistered) return;
            
            const tr = document.createElement('tr');
            tr.id = `log-${log.id}`;
            tr.className = `border-b hover:bg-gray-50 ${isNew ? 'bg-emerald-100 transition-colors duration-1000' : ''}`;
            
            const studentName = log.student ? log.student.name : 'Belum terdaftar';
            const className = (log.student && (log.student.school_class || log.student.schoolClass)) 
                ? (log.student.school_class?.name || log.student.schoolClass?.name) 
                : '-';
            const deviceName = log.device ? log.device.name : '-';
            
            let statusHtml = log.status_code;
            if (log.status_code == 0) statusHtml = '<span class="text-blue-600 font-semibold">Masuk</span>';
            if (log.status_code == 1) statusHtml = '<span class="text-red-600 font-semibold">Pulang</span>';

            tr.innerHTML = `
                <td class="p-3 text-center">
                    <input type="checkbox" name="ids[]" value="${log.id}" class="check-item w-4 h-4 text-blue-600 rounded border-gray-300">
                </td>
                <td class="p-3">${log.timestamp}</td>
                <td class="p-3 font-semibold">${studentName}</td>
                <td class="p-3">${className}</td>
                <td class="p-3">${statusHtml}</td>
                <td class="p-3 text-xs text-gray-500">${deviceName}</td>
                <td class="p-3 text-center">
                    <button type="button" onclick="deleteSingle(${log.id})" class="text-red-600 hover:underline text-sm">Hapus</button>
                </td>
            `;

            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) emptyRow.remove();

            const tbody = document.getElementById('attendance-table-body');
            tbody.insertBefore(tr, tbody.firstChild);
            
            if (isNew) {
                setTimeout(() => {
                    tr.classList.remove('bg-emerald-100');
                }, 2500);
            }
        }

        // 1. Live Smart Polling (Otomatis cek data baru setiap 2.5 detik tanpa reload halaman)
        setInterval(async () => {
            try {
                const response = await fetch(`{{ route('attendance.latest') }}?since_id=${maxLogId}&filter=${currentFilter}`);
                if (!response.ok) return;
                const data = await response.json();
                
                if (data.status === 'success' && data.logs && data.logs.length > 0) {
                    // Masukkan data baru berurutan
                    data.logs.forEach(log => {
                        insertLogRow(log, true);
                    });
                }
            } catch (err) {
                // Ignore silent network errors
            }
        }, 2500);

        // 2. WebSocket Fallback (Jika Laravel Echo aktif)
        if (window.Echo) {
            window.Echo.channel('attendance')
                .listen('.AttendanceCreated', (e) => {
                    if (e && e.log) {
                        insertLogRow(e.log, true);
                    }
                });
        }
    });
</script>
@endsection