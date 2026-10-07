@extends('layouts.app')
@section('content')
<div class="flex justify-between items-end mb-6">
    <h2 class="text-2xl font-bold">Data Siswa (User Mesin)</h2>
    <div class="flex items-center space-x-2">
        <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-all">
            Hapus Terpilih (<span id="count-selected">0</span>)
        </button>
        <a href="{{ route('students.importForm') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Import Excel
        </a>
        <a href="{{ route('students.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
            + Tambah Siswa Baru
        </a>
    </div>
</div>

<div class="bg-white shadow rounded overflow-x-auto p-4">
    <form id="bulk-delete-form" action="{{ route('students.destroyMultiple') }}" method="POST" onsubmit="return confirm('Hapus semua siswa yang dicentang dari database DAN SEMUA MESIN secara permanen?')">
        @csrf
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="p-3 w-10 text-center">
                        <input type="checkbox" id="check-all" class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    </th>
                    <th class="p-3 text-sm">NIS</th>
                    <th class="p-3 text-sm">Nama Siswa</th>
                    <th class="p-3 text-sm">Kelas</th>
                    <th class="p-3 text-sm">Jurusan</th>
                    <th class="p-3 text-sm text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="student-table-body">
                @forelse($students as $s)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 text-center">
                        <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="check-item w-4 h-4 text-blue-600 rounded border-gray-300">
                    </td>
                    <td class="p-3">{{ $s->device_user_id }}</td>
                    <td class="p-3 font-semibold">{{ $s->name }}</td>
                    <td class="p-3">{{ $s->schoolClass->name ?? '-' }}</td>
                    <td class="p-3">{{ $s->schoolClass->major->name ?? '-' }}</td>
                    <td class="p-3 text-center flex justify-center space-x-2">
                        <a href="{{ route('students.edit', $s->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs py-1 px-3 rounded">
                            Edit
                        </a>
                        <button type="button" onclick="deleteSingle({{ $s->id }})" class="bg-red-500 hover:bg-red-600 text-white text-xs py-1 px-3 rounded">
                            Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500">Belum ada siswa terdaftar.</td>
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
    // Logic Hapus Satuan
    function deleteSingle(id) {
        if(confirm('Hapus siswa ini dari web dan seluruh mesin?')) {
            const form = document.getElementById('single-delete-form');
            form.action = `/students/${id}`;
            form.submit();
        }
    }

    // Logic Checkbox & Hapus Massal
    document.addEventListener("DOMContentLoaded", () => {
        const checkAll = document.getElementById('check-all');
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

        checkAll.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.check-item').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateBulkButton();
        });

        document.getElementById('student-table-body').addEventListener('change', function(e) {
            if(e.target && e.target.classList.contains('check-item')) {
                updateBulkButton();
                if(!e.target.checked) checkAll.checked = false;
            }
        });
    });
</script>
@endsection