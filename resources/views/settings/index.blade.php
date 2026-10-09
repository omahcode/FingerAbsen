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

            <!-- Card 5: Pengaturan Mode TV Layar Absen (Leaderboard) -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-purple-500">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 pb-4 border-b border-gray-100 gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 flex items-center">
                            <i class="fas fa-tv text-purple-600 mr-2"></i> Pengaturan Mode TV Layar Absen
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Atur tampilan teks, durasi slide, tema warna, dan slide yang ingin ditampilkan di layar TV.</p>
                    </div>
                    <a href="{{ route('tv.index') }}" target="_blank" class="inline-flex items-center gap-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-bold px-3 py-1.5 rounded-lg border border-purple-200 transition">
                        <i class="fas fa-external-link-alt"></i> Buka Layar TV
                    </a>
                </div>

                <div class="space-y-4">
                    <!-- Judul & Subjudul TV -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <i class="fas fa-heading text-gray-400 mr-1"></i> Judul Utama TV (Slide Sambutan)
                            </label>
                            <input 
                                type="text" 
                                name="tv_title" 
                                value="{{ old('tv_title', $settings['tv_title']) }}" 
                                placeholder="Contoh: SELAMAT DATANG DI JURUSAN PENGEMBANGAN PERANGKAT LUNAK DAN GIM"
                                class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <i class="fas fa-subscript text-gray-400 mr-1"></i> Subjudul Layar TV
                            </label>
                            <input 
                                type="text" 
                                name="tv_subtitle" 
                                value="{{ old('tv_subtitle', $settings['tv_subtitle']) }}" 
                                placeholder="Contoh: Sistem Informasi Presensi Biometrik"
                                class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                            >
                        </div>
                    </div>

                    <!-- Running Text Pengumuman -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <i class="fas fa-bullhorn text-amber-500 mr-1"></i> Teks Berjalan Pengumuman (Running Text / Ticker Bawah)
                        </label>
                        <input 
                            type="text" 
                            name="tv_running_text" 
                            value="{{ old('tv_running_text', $settings['tv_running_text']) }}" 
                            placeholder="Contoh: Selamat Datang! Batas absensi masuk adalah pukul 07:15 WIB. Jagalah selalu kedisiplinan!"
                            class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                        >
                        <p class="text-[11px] text-gray-400 mt-1">Pesan teks ini akan berjalan terus di bagian bawah layar mode TV.</p>
                    </div>

                    <!-- Parameter Durasi, Poin, & Tema -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <i class="fas fa-stopwatch text-indigo-500 mr-1"></i> Durasi Tiap Slide (Carousel)
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    id="tv_slide_interval_input"
                                    name="tv_slide_interval" 
                                    min="3" 
                                    max="600"
                                    value="{{ old('tv_slide_interval', $settings['tv_slide_interval']) }}" 
                                    required 
                                    class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                >
                                <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-medium">detik</span>
                            </div>
                            <!-- Preset Chips -->
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                <span class="text-[10px] text-gray-400 font-semibold">Pilih Cepat:</span>
                                <button type="button" onclick="setSlideInterval(10)" class="text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold px-1.5 py-0.5 rounded border border-purple-200 transition">10s</button>
                                <button type="button" onclick="setSlideInterval(15)" class="text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold px-1.5 py-0.5 rounded border border-purple-200 transition">15s</button>
                                <button type="button" onclick="setSlideInterval(20)" class="text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold px-1.5 py-0.5 rounded border border-purple-200 transition">20s</button>
                                <button type="button" onclick="setSlideInterval(30)" class="text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold px-1.5 py-0.5 rounded border border-purple-200 transition">30s</button>
                                <button type="button" onclick="setSlideInterval(60)" class="text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold px-1.5 py-0.5 rounded border border-purple-200 transition">1m</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <i class="fas fa-palette text-pink-500 mr-1"></i> Tema Tampilan
                            </label>
                            <select name="tv_theme" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                <option value="aurora" {{ $settings['tv_theme'] == 'aurora' ? 'selected' : '' }}>🌈 Dynamic Aurora (Default)</option>
                                <option value="dark" {{ $settings['tv_theme'] == 'dark' ? 'selected' : '' }}>🌙 Dark Mode Cyber</option>
                                <option value="clean" {{ $settings['tv_theme'] == 'clean' ? 'selected' : '' }}>✨ Clean Modern Light</option>
                                <option value="ocean" {{ $settings['tv_theme'] == 'ocean' ? 'selected' : '' }}>🌊 Deep Ocean Blue</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <i class="fas fa-star text-emerald-500 mr-1"></i> Poin Tepat Waktu
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    name="tv_points_ontime" 
                                    min="1" 
                                    max="100" 
                                    value="{{ old('tv_points_ontime', $settings['tv_points_ontime']) }}" 
                                    required 
                                    class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                >
                                <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-medium">pt</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <i class="fas fa-clock text-amber-500 mr-1"></i> Poin Terlambat
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    name="tv_points_late" 
                                    min="0" 
                                    max="100" 
                                    value="{{ old('tv_points_late', $settings['tv_points_late']) }}" 
                                    required 
                                    class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                >
                                <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-medium">pt</span>
                            </div>
                        </div>
                    </div>

                    <!-- Otomatis Fullscreen Toggle -->
                    <div class="pt-3 border-t border-gray-100">
                        <label class="flex items-center p-3 bg-indigo-50/60 border border-indigo-200 rounded-lg cursor-pointer hover:bg-indigo-50 transition">
                            <input type="checkbox" name="tv_auto_fullscreen" value="1" {{ ($settings['tv_auto_fullscreen'] ?? '1') == '1' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-indigo-900 flex items-center">
                                    <i class="fas fa-expand-arrows-alt text-indigo-600 mr-1.5"></i> Otomatis Layar Penuh (Auto Fullscreen) Saat Halaman TV Dibuka
                                </span>
                                <p class="text-[11px] text-indigo-700/80 mt-0.5">Otomatis beralih ke mode layar penuh saat layar TV pertama kali dibuka atau di-klik.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Slide Toggles -->
                    <div class="pt-3 border-t border-gray-100">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">
                            <i class="fas fa-layer-group text-purple-600 mr-1"></i> Slide yang Ditampilkan di Layar TV:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                            <label class="flex items-center p-2.5 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-purple-50 transition">
                                <input type="checkbox" name="tv_show_welcome" value="1" {{ $settings['tv_show_welcome'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span class="ml-2 text-xs font-semibold text-gray-700">1. Sambutan & Jam</span>
                            </label>
                            <label class="flex items-center p-2.5 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-purple-50 transition">
                                <input type="checkbox" name="tv_show_daily" value="1" {{ $settings['tv_show_daily'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span class="ml-2 text-xs font-semibold text-gray-700">2. Kehadiran Hari Ini</span>
                            </label>
                            <label class="flex items-center p-2.5 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-purple-50 transition">
                                <input type="checkbox" name="tv_show_weekly" value="1" {{ $settings['tv_show_weekly'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span class="ml-2 text-xs font-semibold text-gray-700">3. Peringkat Mingguan</span>
                            </label>
                            <label class="flex items-center p-2.5 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-purple-50 transition">
                                <input type="checkbox" name="tv_show_monthly" value="1" {{ $settings['tv_show_monthly'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span class="ml-2 text-xs font-semibold text-gray-700">4. Peringkat Bulanan</span>
                            </label>
                            <label class="flex items-center p-2.5 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-purple-50 transition">
                                <input type="checkbox" name="tv_show_hof" value="1" {{ $settings['tv_show_hof'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span class="ml-2 text-xs font-semibold text-gray-700">5. Hall of Fame</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="flex justify-end pt-2">
                <button 
                    type="submit" 
                    class="w-full sm:w-auto justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow transition flex items-center text-sm"
                >
                    <i class="fas fa-save mr-2"></i> Simpan Semua Pengaturan
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function setSlideInterval(sec) {
        const input = document.getElementById('tv_slide_interval_input');
        if (input) {
            input.value = sec;
            input.classList.add('ring-2', 'ring-purple-500');
            setTimeout(() => input.classList.remove('ring-2', 'ring-purple-500'), 600);
        }
    }
</script>
@endsection
