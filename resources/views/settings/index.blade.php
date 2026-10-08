@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center space-x-3 mb-6">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
            <i class="fas fa-sliders-h text-lg"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Pengaturan Jam Presensi & Sistem</h2>
            <p class="text-xs text-gray-500">Konfigurasi rentang jam masuk, jam pulang, toleransi keterlambatan, dan notifikasi.</p>
        </div>
    </div>

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf

        <div class="space-y-6">
            <!-- Card 1: Identitas Sekolah -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-school text-indigo-600 mr-2"></i> Identitas Sekolah
                </h3>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Sekolah / Lembaga</label>
                    <input 
                        type="text" 
                        name="school_name" 
                        value="{{ old('school_name', $settings['school_name']) }}" 
                        required 
                        placeholder="Contoh: SMK Negeri 1 Jakarta"
                        class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >
                    <p class="text-xs text-gray-400 mt-1">Nama ini akan tercantum pada kop surat laporan PDF dan pesan WhatsApp.</p>
                </div>
            </div>

            <!-- Card 2: Pengaturan Jam Masuk -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-emerald-500">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 flex items-center">
                            <i class="fas fa-sign-in-alt text-emerald-600 mr-2"></i> Rentang Jam Absen Masuk
                        </h3>
                        <p class="text-xs text-gray-500">Tentukan batas jam mulai boleh absen masuk dan batas toleransi keterlambatan.</p>
                    </div>
                    <span class="bg-emerald-50 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded border border-emerald-200">
                        Presensi Masuk
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Jam Mulai Buka Masuk -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <i class="far fa-clock text-gray-400 mr-1"></i> Jam Mulai Masuk
                        </label>
                        <input 
                            type="time" 
                            name="checkin_start" 
                            value="{{ old('checkin_start', $settings['checkin_start']) }}" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <p class="text-[11px] text-gray-400 mt-1">Mesin mulai menerima absen masuk (Contoh: 06:00).</p>
                    </div>

                    <!-- Batas Jam Tepat Waktu -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Batas Tepat Waktu
                        </label>
                        <input 
                            type="time" 
                            name="checkin_end" 
                            value="{{ old('checkin_end', $settings['checkin_end']) }}" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <p class="text-[11px] text-gray-400 mt-1">Lewat dari jam ini dihitung terlambat (Contoh: 07:15).</p>
                    </div>

                    <!-- Batas Maksimal Masuk (Toleransi) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Batas Akhir Masuk
                        </label>
                        <input 
                            type="time" 
                            name="checkin_late_end" 
                            value="{{ old('checkin_late_end', $settings['checkin_late_end']) }}" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                        <p class="text-[11px] text-gray-400 mt-1">Batas maksimal absen masuk terlambat (Contoh: 08:30).</p>
                    </div>
                </div>
            </div>

            <!-- Card 3: Pengaturan Jam Pulang -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-rose-500">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 flex items-center">
                            <i class="fas fa-sign-out-alt text-rose-600 mr-2"></i> Rentang Jam Absen Pulang
                        </h3>
                        <p class="text-xs text-gray-500">Tentukan kapan siswa mulai diizinkan melakukan scan absen pulang.</p>
                    </div>
                    <span class="bg-rose-50 text-rose-700 text-xs font-bold px-2.5 py-1 rounded border border-rose-200">
                        Presensi Pulang
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Jam Mulai Buka Pulang -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <i class="far fa-clock text-gray-400 mr-1"></i> Jam Mulai Boleh Pulang
                        </label>
                        <input 
                            type="time" 
                            name="checkout_start" 
                            value="{{ old('checkout_start', $settings['checkout_start']) }}" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                        <p class="text-[11px] text-gray-400 mt-1">Jam kepulangan sekolah (Contoh: 14:00 atau 15:00).</p>
                    </div>

                    <!-- Batas Akhir Pulang -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <i class="far fa-clock text-gray-400 mr-1"></i> Batas Akhir Absen Pulang
                        </label>
                        <input 
                            type="time" 
                            name="checkout_end" 
                            value="{{ old('checkout_end', $settings['checkout_end']) }}" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                        <p class="text-[11px] text-gray-400 mt-1">Batas mesin menutup absensi hari ini (Contoh: 18:00).</p>
                    </div>
                </div>
            </div>

            <!-- Card 4: Notifikasi WhatsApp -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 flex items-center">
                            <i class="fab fa-whatsapp text-green-500 text-lg mr-2"></i> Notifikasi WhatsApp Otomatis
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Kirim pesan WhatsApp otomatis ke wali murid setiap kali anak melakukan presensi masuk/pulang.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="wa_notification" value="1" {{ $settings['wa_notification'] == '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="flex justify-end space-x-3 pt-2">
                <button 
                    type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow transition flex items-center text-sm"
                >
                    <i class="fas fa-save mr-2"></i> Simpan Semua Pengaturan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
