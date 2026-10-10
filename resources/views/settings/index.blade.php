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
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-purple-600">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 pb-4 border-b border-gray-100 gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 flex items-center">
                            <i class="fas fa-tv text-purple-600 mr-2"></i> Pengaturan Mode TV Layar Absen
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Sesuaikan tampilan sambutan, tema, durasi putar otomatis, skema poin, dan slide yang aktif di layar TV.</p>
                    </div>
                    <a href="{{ route('tv.index') }}" target="_blank" class="inline-flex items-center gap-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-bold px-3.5 py-2 rounded-lg border border-purple-200 transition shadow-sm">
                        <i class="fas fa-external-link-alt text-purple-600"></i> Buka Layar TV
                    </a>
                </div>

                <div class="space-y-6">
                    <!-- 1. Judul & Subjudul TV -->
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center">
                            <i class="fas fa-heading text-purple-500 mr-1.5"></i> Teks Sambutan Header TV
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Judul Utama TV</label>
                                <input 
                                    type="text" 
                                    name="tv_title" 
                                    value="{{ old('tv_title', $settings['tv_title']) }}" 
                                    placeholder="Contoh: SELAMAT DATANG DI JURUSAN PENGEMBANGAN PERANGKAT LUNAK DAN GIM"
                                    class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Subjudul TV</label>
                                <input 
                                    type="text" 
                                    name="tv_subtitle" 
                                    value="{{ old('tv_subtitle', $settings['tv_subtitle']) }}" 
                                    placeholder="Contoh: Sistem Informasi Presensi Biometrik"
                                    class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 2. Running Text Pengumuman -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <i class="fas fa-bullhorn text-amber-500 mr-1"></i> Teks Berjalan Pengumuman (Running Text / Ticker Bawah)
                        </label>
                        <input 
                            type="text" 
                            name="tv_running_text" 
                            value="{{ old('tv_running_text', $settings['tv_running_text']) }}" 
                            placeholder="Contoh: Selamat Datang! Batas absensi masuk tepat waktu adalah pukul 06:30 WIB. Tingkatkan kedisiplinan!"
                            class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 focus:outline-none"
                        >
                        <p class="text-[11px] text-gray-400 mt-1">Pesan teks ini akan terus berjalan di bagian bawah layar mode TV sebagai pengumuman dinamis.</p>
                    </div>

                    <!-- 3. Tampilan & Carousel Layar -->
                    <div class="pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center">
                            <i class="fas fa-sliders-h text-purple-500 mr-1.5"></i> Perilaku Carousel & Tampilan
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Durasi Slide -->
                            <div class="bg-gray-50/80 p-3.5 rounded-xl border border-gray-200">
                                <label class="block text-xs font-bold text-gray-700 mb-1 flex items-center justify-between">
                                    <span><i class="fas fa-stopwatch text-indigo-500 mr-1"></i> Durasi Tiap Slide (Carousel)</span>
                                </label>
                                <div class="relative mt-1">
                                    <input 
                                        type="number" 
                                        id="tv_slide_interval_input"
                                        name="tv_slide_interval" 
                                        min="3" 
                                        max="600"
                                        value="{{ old('tv_slide_interval', $settings['tv_slide_interval']) }}" 
                                        required 
                                        class="w-full p-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                    >
                                    <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-medium">detik</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                    <span class="text-[11px] text-gray-500 font-medium">Pilih Cepat:</span>
                                    <button type="button" onclick="setSlideInterval(10)" class="text-[11px] bg-white hover:bg-purple-50 text-purple-700 font-semibold px-2 py-0.5 rounded border border-purple-200 transition">10s</button>
                                    <button type="button" onclick="setSlideInterval(15)" class="text-[11px] bg-white hover:bg-purple-50 text-purple-700 font-semibold px-2 py-0.5 rounded border border-purple-200 transition">15s</button>
                                    <button type="button" onclick="setSlideInterval(20)" class="text-[11px] bg-white hover:bg-purple-50 text-purple-700 font-semibold px-2 py-0.5 rounded border border-purple-200 transition">20s</button>
                                    <button type="button" onclick="setSlideInterval(30)" class="text-[11px] bg-white hover:bg-purple-50 text-purple-700 font-semibold px-2 py-0.5 rounded border border-purple-200 transition">30s</button>
                                    <button type="button" onclick="setSlideInterval(60)" class="text-[11px] bg-white hover:bg-purple-50 text-purple-700 font-semibold px-2 py-0.5 rounded border border-purple-200 transition">1m</button>
                                </div>
                            </div>

                            <!-- Tema Tampilan & Auto Fullscreen -->
                            <div class="space-y-3">
                                <div class="bg-gray-50/80 p-3 rounded-xl border border-gray-200">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        <i class="fas fa-tv text-purple-500 mr-1"></i> Tampilan Layar TV
                                    </label>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="inline-flex items-center gap-1.5 bg-slate-900 text-sky-400 text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-700 shadow-sm">
                                            <i class="fas fa-moon text-sky-400"></i> Dark Cyber High-Contrast (Khas Layar TV)
                                        </span>
                                    </div>
                                    <input type="hidden" name="tv_theme" value="dark">
                                    <p class="text-[10px] text-gray-400 mt-1.5">Desain permanen mode gelap dengan kontras teks tajam & glowing agar mudah dibaca dari jarak jauh.</p>
                                </div>

                                <label class="flex items-center p-2.5 bg-indigo-50/70 border border-indigo-200 rounded-lg cursor-pointer hover:bg-indigo-50 transition">
                                    <input type="checkbox" name="tv_auto_fullscreen" value="1" {{ ($settings['tv_auto_fullscreen'] ?? '1') == '1' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                    <div class="ml-2.5">
                                        <span class="text-xs font-bold text-indigo-900 flex items-center">
                                            <i class="fas fa-expand-arrows-alt text-indigo-600 mr-1"></i> Otomatis Layar Penuh (Auto Fullscreen)
                                        </span>
                                        <p class="text-[10px] text-indigo-700/80">Otomatis layar penuh saat TV dibuka atau pertama di-klik.</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Skema Poin Kehadiran (Gamifikasi TV) -->
                    <div class="pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center">
                            <i class="fas fa-trophy text-amber-500 mr-1.5"></i> Gamifikasi & Skema Poin Kehadiran
                        </h4>

                        <div class="bg-amber-50/60 border border-amber-200 rounded-xl p-4 space-y-4">
                            <!-- Pilihan Skema Radio -->
                            <div>
                                <label class="block text-xs font-bold text-amber-900 mb-2">Metode Perhitungan Poin:</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="flex items-start p-3 bg-white border-2 rounded-lg cursor-pointer transition {{ ($settings['tv_point_mode'] ?? 'tiered') === 'tiered' ? 'border-amber-500 bg-amber-50/30' : 'border-gray-200 hover:border-amber-300' }}">
                                        <input type="radio" name="tv_point_mode" value="tiered" {{ ($settings['tv_point_mode'] ?? 'tiered') === 'tiered' ? 'checked' : '' }} class="mt-0.5 w-4 h-4 text-amber-600 focus:ring-amber-500">
                                        <div class="ml-2.5">
                                            <span class="text-xs font-bold text-gray-800 block">Poin Bertingkat Dinamis</span>
                                            <p class="text-[11px] text-gray-500 mt-0.5">Makin pagi datang, makin besar poin yang didapat (Gradasi waktu menit ke menit).</p>
                                        </div>
                                    </label>

                                    <label class="flex items-start p-3 bg-white border-2 rounded-lg cursor-pointer transition {{ ($settings['tv_point_mode'] ?? 'tiered') === 'flat' ? 'border-amber-500 bg-amber-50/30' : 'border-gray-200 hover:border-amber-300' }}">
                                        <input type="radio" name="tv_point_mode" value="flat" {{ ($settings['tv_point_mode'] ?? 'tiered') === 'flat' ? 'checked' : '' }} class="mt-0.5 w-4 h-4 text-amber-600 focus:ring-amber-500">
                                        <div class="ml-2.5">
                                            <span class="text-xs font-bold text-gray-800 block">Poin Rata (Flat)</span>
                                            <p class="text-[11px] text-gray-500 mt-0.5">Semua yang tepat waktu mendapat nilai poin yang sama rata.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Input Parameter Poin Maksimal & Poin Terlambat -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-amber-200/60">
                                <div>
                                    <label class="block text-xs font-bold text-amber-900 mb-1">
                                        <i class="fas fa-star text-amber-500 mr-1"></i> Poin Maksimal (Datang Paling Awal)
                                    </label>
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            name="tv_points_ontime" 
                                            min="1" 
                                            max="100" 
                                            value="{{ old('tv_points_ontime', $settings['tv_points_ontime'] ?? '11') }}" 
                                            required 
                                            class="w-full p-2.5 bg-white border border-amber-300 rounded-lg text-sm text-gray-900 font-mono font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                        >
                                        <span class="absolute right-3 top-2.5 text-xs text-amber-600 font-bold">pt</span>
                                    </div>
                                    <p class="text-[10px] text-amber-700/80 mt-1">Diberikan bagi yang tap di awal jam buka (misal: 11 poin).</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-amber-900 mb-1">
                                        <i class="fas fa-clock text-rose-500 mr-1"></i> Poin Terlambat (Lewat Batas Jam)
                                    </label>
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            name="tv_points_late" 
                                            min="0" 
                                            max="100" 
                                            value="{{ old('tv_points_late', $settings['tv_points_late'] ?? '0') }}" 
                                            required 
                                            class="w-full p-2.5 bg-white border border-amber-300 rounded-lg text-sm text-gray-900 font-mono font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                        >
                                        <span class="absolute right-3 top-2.5 text-xs text-rose-600 font-bold">pt</span>
                                    </div>
                                    <p class="text-[10px] text-amber-700/80 mt-1">Poin jika tap setelah batas jam masuk (isi 0 agar tidak dapat poin).</p>
                                </div>
                            </div>

                            <!-- Penjelasan Cara Kerja -->
                            <div class="bg-white/80 rounded-lg p-3 text-[11px] text-amber-900 border border-amber-200/80 leading-relaxed">
                                <i class="fas fa-info-circle text-amber-600 mr-1"></i>
                                <strong>Cara Kerja:</strong> Pada rentang jam buka (<strong>{{ $settings['checkin_start'] }}</strong>) s/d batas tepat waktu (<strong>{{ $settings['checkin_end'] }}</strong>), siswa yang datang paling awal mendapat <strong>{{ $settings['tv_points_ontime'] ?? 11 }} pt</strong>. Poin akan berkurang secara bertahap menit demi menit mendekati jam <strong>{{ $settings['checkin_end'] }}</strong>. Di atas jam <strong>{{ $settings['checkin_end'] }} (terlambat)</strong> otomatis mendapat <strong>{{ $settings['tv_points_late'] ?? 0 }} pt</strong>.
                            </div>
                        </div>
                    </div>

                    <!-- 5. Slide yang Ditampilkan di Layar TV -->
                    <div class="pt-4 border-t border-gray-100">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">
                            <i class="fas fa-layer-group text-purple-600 mr-1.5"></i> Slide yang Ditampilkan di Layar TV:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <label class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50/70 hover:border-purple-300 transition shadow-sm">
                                <input type="checkbox" name="tv_show_welcome" value="1" {{ $settings['tv_show_welcome'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <div class="ml-2.5">
                                    <span class="text-xs font-bold text-gray-800 block">1. Sambutan</span>
                                    <span class="text-[10px] text-gray-400">Header & Jam</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50/70 hover:border-purple-300 transition shadow-sm">
                                <input type="checkbox" name="tv_show_daily" value="1" {{ $settings['tv_show_daily'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <div class="ml-2.5">
                                    <span class="text-xs font-bold text-gray-800 block">2. Kehadiran Hari Ini</span>
                                    <span class="text-[10px] text-gray-400">Log Realtime</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50/70 hover:border-purple-300 transition shadow-sm">
                                <input type="checkbox" name="tv_show_weekly" value="1" {{ $settings['tv_show_weekly'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <div class="ml-2.5">
                                    <span class="text-xs font-bold text-gray-800 block">3. Peringkat Minggu</span>
                                    <span class="text-[10px] text-gray-400">Top Mingguan</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50/70 hover:border-purple-300 transition shadow-sm">
                                <input type="checkbox" name="tv_show_monthly" value="1" {{ $settings['tv_show_monthly'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <div class="ml-2.5">
                                    <span class="text-xs font-bold text-gray-800 block">4. Peringkat Bulan</span>
                                    <span class="text-[10px] text-gray-400">Top Bulanan</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50/70 hover:border-purple-300 transition shadow-sm">
                                <input type="checkbox" name="tv_show_hof" value="1" {{ $settings['tv_show_hof'] == '1' ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <div class="ml-2.5">
                                    <span class="text-xs font-bold text-gray-800 block">5. Hall of Fame</span>
                                    <span class="text-[10px] text-gray-400">Streak Abadi</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Simpan Pengaturan Utama -->
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

    <!-- Card Terpisah: Simulasi & Uji Coba Manual Streak Mode TV -->
    <div class="mt-8 bg-white p-6 rounded-lg shadow-sm border border-indigo-100 border-l-4 border-l-indigo-600">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 pb-3 border-b border-gray-100">
            <div>
                <h3 class="text-base font-bold text-gray-800 flex items-center">
                    <i class="fas fa-flask text-indigo-600 mr-2"></i> Uji Coba & Simulasi Streak Siswa (Mode TV)
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Tambahkan nilai streak kehadiran secara manual untuk menguji tampilan podium juara (Juara 1, 2, 3), poin mingguan/bulanan, dan Hall of Fame di layar TV.</p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('settings.manual_streak.simulate_top3') }}" method="POST" data-confirm="Buat data simulasi otomatis untuk 3 siswa (Streak 15, 12, 10 hari) agar podium TV langsung terisi?" data-title="Simulasi Cepat Top 3" data-icon="question">
                    @csrf
                    <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-magic text-indigo-600"></i> ⚡ Simulasi Cepat Top 3
                    </button>
                </form>

                @if($manualStreakStudents->count() > 0)
                <form action="{{ route('settings.manual_streak.reset') }}" method="POST" data-confirm="Reset semua streak simulasi siswa kembali ke 0?" data-title="Reset Semua Streak" data-danger="true" data-icon="warning">
                    @csrf
                    <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-trash-alt text-red-600"></i> Reset Semua ({{ $manualStreakStudents->count() }})
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Form Tambah / Update Streak Siswa -->
        <form action="{{ route('settings.manual_streak.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
            @csrf
            <!-- Pilih Siswa -->
            <div class="md:col-span-6">
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    <i class="fas fa-user-graduate text-blue-500 mr-1"></i> Pilih Siswa:
                </label>
                <select name="student_id" required class="w-full p-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">-- Pilih Siswa yang Ingin Diuji --</option>
                    @foreach($students as $st)
                        <option value="{{ $st->id }}">
                            {{ $st->name }} ({{ $st->schoolClass->name ?? '-' }}) {{ $st->manual_streak > 0 ? "— [Streak Saat Ini: {$st->manual_streak} Hari]" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Jumlah Streak -->
            <div class="md:col-span-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    <i class="fas fa-fire text-amber-500 mr-1"></i> Jumlah Streak (Hari):
                </label>
                <div class="relative">
                    <input 
                        type="number" 
                        name="manual_streak" 
                        min="0" 
                        max="365" 
                        value="10" 
                        required 
                        placeholder="Contoh: 10"
                        class="w-full p-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-900 font-mono font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    >
                    <span class="absolute right-3 top-2 text-xs text-gray-400 font-medium">hari</span>
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="md:col-span-3">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm flex items-center justify-center gap-1.5">
                    <i class="fas fa-plus-circle"></i> Terapkan Streak
                </button>
            </div>
        </form>

        <!-- Daftar Siswa yang Memiliki Manual Streak -->
        @if($manualStreakStudents->count() > 0)
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-2 flex items-center">
                <i class="fas fa-list-ol text-indigo-500 mr-1.5"></i> Siswa dengan Streak Simulasi Aktif ({{ $manualStreakStudents->count() }} Siswa):
            </h4>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 font-bold border-b">
                            <th class="p-2.5 w-12 text-center">#</th>
                            <th class="p-2.5">Nama Siswa</th>
                            <th class="p-2.5">Kelas</th>
                            <th class="p-2.5 text-center">Streak Simulasi</th>
                            <th class="p-2.5 text-center">Poin Tambahan</th>
                            <th class="p-2.5 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($manualStreakStudents as $index => $ms)
                        <tr class="hover:bg-indigo-50/40 transition">
                            <td class="p-2.5 text-center font-mono font-bold text-indigo-600">#{{ $index + 1 }}</td>
                            <td class="p-2.5 font-bold text-gray-800">{{ $ms->name }}</td>
                            <td class="p-2.5 text-gray-500">{{ $ms->schoolClass->name ?? '-' }}</td>
                            <td class="p-2.5 text-center">
                                <span class="bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full font-mono text-[11px] inline-flex items-center gap-1">
                                    <i class="fas fa-fire text-amber-500 text-[10px]"></i> {{ $ms->manual_streak }} Hari
                                </span>
                            </td>
                            <td class="p-2.5 text-center font-mono font-bold text-emerald-600">+{{ $ms->manual_streak * ((int)($settings['tv_points_ontime'] ?? 10)) }} pt</td>
                            <td class="p-2.5 text-center">
                                <form action="{{ route('settings.manual_streak.reset') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $ms->id }}">
                                    <button type="submit" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1 rounded text-[11px] font-semibold transition" title="Hapus Streak Simulasi">
                                        <i class="fas fa-times mr-0.5"></i> Reset
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="text-center py-4 bg-gray-50 rounded-lg border border-dashed border-gray-200 text-xs text-gray-400">
            <i class="fas fa-info-circle mr-1 text-gray-400"></i> Belum ada streak simulasi manual yang diatur. Gunakan form di atas atau klik tombol <strong>"⚡ Simulasi Cepat Top 3"</strong>.
        </div>
        @endif
    </div>
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
