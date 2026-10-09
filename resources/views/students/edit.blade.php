@extends('layouts.app')
@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded-xl shadow-md border border-gray-100">
    <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-gray-100">
        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
            <i class="fas fa-user-edit text-lg"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Data Siswa</h2>
            <p class="text-xs text-gray-500">Perbarui foto profil, nama, kelas, dan nomor WhatsApp orang tua.</p>
        </div>
    </div>

    <form action="{{ route('students.update', $student->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <!-- Bagian Foto Profil Siswa -->
        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">
                <i class="fas fa-camera text-indigo-500 mr-1"></i> Foto Profil Siswa
            </label>
            <div class="flex items-center gap-4">
                <!-- Preview Avatar Container -->
                <div class="relative group">
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-indigo-300 shadow-sm bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-2xl" id="avatar-preview-box">
                        @if($student->photo)
                            <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->name }}" id="avatar-preview-img" class="w-full h-full object-cover">
                        @else
                            <img src="" alt="" id="avatar-preview-img" class="w-full h-full object-cover hidden">
                            <span id="avatar-fallback-text">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                        @endif
                    </div>
                </div>

                <!-- Input Upload & Aksi -->
                <div class="flex-1 space-y-2">
                    <label for="photo-input" class="inline-flex items-center gap-2 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold py-2 px-3.5 rounded-lg border border-gray-300 shadow-sm cursor-pointer transition">
                        <i class="fas fa-upload text-indigo-600"></i>
                        <span>Unggah Foto Profil</span>
                    </label>
                    <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden">
                    <p class="text-[11px] text-gray-500">Mendukung format JPG, PNG, WEBP (Maksimal 2 MB).</p>

                    @if($student->photo)
                    <div class="pt-1">
                        <label class="inline-flex items-center text-xs text-red-600 cursor-pointer select-none">
                            <input type="checkbox" name="remove_photo" value="1" id="remove-photo-checkbox" class="w-3.5 h-3.5 text-red-600 rounded border-gray-300 focus:ring-red-500 mr-1.5">
                            <span>Hapus foto profil saat ini</span>
                        </label>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- NIS & Status Fingerprint -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">NIS / ID Mesin (Terkunci)</label>
                @if(($student->fingerprint_templates_count ?? 0) > 0)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        <i class="fas fa-fingerprint text-emerald-600"></i> Terdaftar ({{ $student->fingerprint_templates_count }} Jari)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        <i class="fas fa-fingerprint text-amber-500"></i> Belum Didaftarkan
                    </span>
                @endif
            </div>
            <input type="number" class="border w-full p-2.5 rounded-lg bg-gray-100 text-gray-500 text-sm font-mono" value="{{ $student->device_user_id }}" disabled>
            <p class="text-[11px] text-gray-400 mt-1">NIS menjadi nomor PIN pengenal di seluruh mesin fingerprint.</p>
        </div>

        <!-- Nama Lengkap -->
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Lengkap Siswa <span class="text-red-500">*</span></label>
            <input type="text" name="name" class="border border-gray-300 w-full p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" value="{{ old('name', $student->name) }}" required>
        </div>

        <!-- Pilihan Kelas -->
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pilih Kelas <span class="text-red-500">*</span></label>
            <select name="school_class_id" class="border border-gray-300 w-full p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ old('school_class_id', $student->school_class_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->major->name ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- No WhatsApp Ortu -->
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                <i class="fab fa-whatsapp text-green-500 mr-1"></i> No. WhatsApp Orang Tua / Wali
            </label>
            <input type="text" name="parent_phone" class="border border-gray-300 w-full p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" value="{{ old('parent_phone', $student->parent_phone) }}" placeholder="Contoh: 081234567890">
            <p class="text-[11px] text-gray-500 mt-1">Pesan notifikasi presensi otomatis dikirim ke nomor ini saat anak absen.</p>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex space-x-3 pt-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-lg shadow w-full transition text-sm flex items-center justify-center gap-1.5">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="{{ route('students.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-4 rounded-lg border w-full text-center text-sm transition">
                Batal
            </a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const photoInput = document.getElementById('photo-input');
        const previewImg = document.getElementById('avatar-preview-img');
        const fallbackText = document.getElementById('avatar-fallback-text');
        const removeCheckbox = document.getElementById('remove-photo-checkbox');

        if (photoInput) {
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        previewImg.src = event.target.result;
                        previewImg.classList.remove('hidden');
                        if (fallbackText) fallbackText.classList.add('hidden');
                        if (removeCheckbox) removeCheckbox.checked = false;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        if (removeCheckbox) {
            removeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    previewImg.classList.add('hidden');
                    if (fallbackText) fallbackText.classList.remove('hidden');
                    photoInput.value = '';
                } else {
                    @if($student->photo)
                        previewImg.src = "{{ asset('storage/' . $student->photo) }}";
                        previewImg.classList.remove('hidden');
                        if (fallbackText) fallbackText.classList.add('hidden');
                    @endif
                }
            });
        }
    });
</script>
@endsection