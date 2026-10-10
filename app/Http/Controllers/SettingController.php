<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Student;

class SettingController extends Controller
{
    /**
     * Tampilkan halaman pengaturan jam presensi dan sistem
     */
    public function index()
    {
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');

        $settings = [
            'school_name' => $schoolName,
            'checkin_start' => Setting::get('checkin_start', '06:00'),
            'checkin_end' => Setting::get('checkin_end', '07:15'),
            'checkin_late_end' => Setting::get('checkin_late_end', '08:30'),
            'checkout_start' => Setting::get('checkout_start', '14:00'),
            'checkout_end' => Setting::get('checkout_end', '18:00'),
            'wa_notification' => Setting::get('wa_notification', '1'),

            // TV Mode Settings
            'tv_title' => Setting::get('tv_title', 'SELAMAT DATANG DI JURUSAN PENGEMBANGAN PERANGKAT LUNAK DAN GIM'),
            'tv_subtitle' => Setting::get('tv_subtitle', 'Sistem Informasi Presensi Biometrik Fingerprint'),
            'tv_running_text' => Setting::get('tv_running_text', 'Selamat Datang! Batas absensi masuk tepat waktu adalah pukul 07:15 WIB. Jagalah selalu kedisiplinan dan semangat belajar.'),
            'tv_slide_interval' => Setting::get('tv_slide_interval', '30'),
            'tv_auto_fullscreen' => Setting::get('tv_auto_fullscreen', '1'),
            'tv_show_welcome' => Setting::get('tv_show_welcome', '1'),
            'tv_show_daily' => Setting::get('tv_show_daily', '1'),
            'tv_show_weekly' => Setting::get('tv_show_weekly', '1'),
            'tv_show_monthly' => Setting::get('tv_show_monthly', '1'),
            'tv_show_hof' => Setting::get('tv_show_hof', '1'),
            'tv_points_ontime' => Setting::get('tv_points_ontime', '10'),
            'tv_points_late' => Setting::get('tv_points_late', '5'),
            'tv_theme' => Setting::get('tv_theme', 'aurora'),
        ];

        // Daftar siswa untuk simulasi / uji coba streak
        $students = Student::with('schoolClass')->orderBy('name', 'asc')->get();
        $manualStreakStudents = Student::with('schoolClass')->where('manual_streak', '>', 0)->orderBy('manual_streak', 'desc')->get();

        return view('settings.index', compact('settings', 'students', 'manualStreakStudents'));
    }

    /**
     * Simpan pembaruan pengaturan
     */
    public function update(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:150',
            'checkin_start' => 'required|date_format:H:i',
            'checkin_end' => 'required|date_format:H:i|after:checkin_start',
            'checkin_late_end' => 'required|date_format:H:i|after:checkin_end',
            'checkout_start' => 'required|date_format:H:i',
            'checkout_end' => 'required|date_format:H:i|after:checkout_start',
            'tv_title' => 'nullable|string|max:150',
            'tv_subtitle' => 'nullable|string|max:200',
            'tv_running_text' => 'nullable|string|max:500',
            'tv_slide_interval' => 'required|integer|min:3|max:600',
            'tv_points_ontime' => 'required|integer|min:1|max:100',
            'tv_points_late' => 'required|integer|min:0|max:100',
            'tv_theme' => 'nullable|string|in:aurora,dark,clean,ocean',
        ]);

        // Pengaturan Utama & Jam
        Setting::set('school_name', $request->school_name, 'Nama Sekolah');
        Setting::set('checkin_start', $request->checkin_start, 'Jam Mulai Absen Masuk');
        Setting::set('checkin_end', $request->checkin_end, 'Batas Jam Masuk Tepat Waktu');
        Setting::set('checkin_late_end', $request->checkin_late_end, 'Batas Maksimal Masuk Terlambat');
        Setting::set('checkout_start', $request->checkout_start, 'Jam Mulai Absen Pulang');
        Setting::set('checkout_end', $request->checkout_end, 'Batas Akhir Absen Pulang');
        Setting::set('wa_notification', $request->has('wa_notification') ? '1' : '0', 'Status Notifikasi WhatsApp');

        // Pengaturan TV Mode
        Setting::set('tv_title', $request->tv_title ?: 'SELAMAT DATANG DI JURUSAN PENGEMBANGAN PERANGKAT LUNAK DAN GIM', 'Judul Utama TV');
        Setting::set('tv_subtitle', $request->tv_subtitle ?: 'Sistem Informasi Presensi Biometrik Fingerprint', 'Subjudul TV');
        Setting::set('tv_running_text', $request->tv_running_text ?: '', 'Teks Berjalan Pengumuman TV');
        Setting::set('tv_slide_interval', (string) $request->tv_slide_interval, 'Durasi Slide TV (Detik)');
        Setting::set('tv_auto_fullscreen', $request->has('tv_auto_fullscreen') ? '1' : '0', 'Otomatis Layar Penuh Saat Buka TV');
        Setting::set('tv_points_ontime', (string) $request->tv_points_ontime, 'Poin Hadir Tepat Waktu');
        Setting::set('tv_points_late', (string) $request->tv_points_late, 'Poin Hadir Terlambat');
        Setting::set('tv_theme', $request->tv_theme ?: 'aurora', 'Tema Tampilan TV');

        // Slide Toggles
        Setting::set('tv_show_welcome', $request->has('tv_show_welcome') ? '1' : '0', 'Tampilkan Slide Sambutan TV');
        Setting::set('tv_show_daily', $request->has('tv_show_daily') ? '1' : '0', 'Tampilkan Slide Kehadiran Hari Ini TV');
        Setting::set('tv_show_weekly', $request->has('tv_show_weekly') ? '1' : '0', 'Tampilkan Slide Peringkat Mingguan TV');
        Setting::set('tv_show_monthly', $request->has('tv_show_monthly') ? '1' : '0', 'Tampilkan Slide Peringkat Bulanan TV');
        Setting::set('tv_show_hof', $request->has('tv_show_hof') ? '1' : '0', 'Tampilkan Slide Hall of Fame TV');

        return redirect()->route('settings.index')->with('success', 'Semua pengaturan presensi dan mode TV berhasil diperbarui.');
    }

    /**
     * Tambah / Update manual streak siswa untuk simulasi/uji coba Mode TV
     */
    public function updateManualStreak(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'manual_streak' => 'required|integer|min:0|max:365',
        ]);

        $student = Student::findOrFail($request->student_id);
        $student->manual_streak = (int) $request->manual_streak;
        $student->save();

        return redirect()->route('settings.index')->with('success', "Streak simulasi untuk {$student->name} berhasil diatur menjadi {$student->manual_streak} hari.");
    }

    /**
     * Reset manual streak untuk semua siswa atau siswa tertentu
     */
    public function resetManualStreak(Request $request)
    {
        if ($request->has('student_id') && $request->student_id) {
            $student = Student::find($request->student_id);
            if ($student) {
                $student->manual_streak = 0;
                $student->save();
                return redirect()->route('settings.index')->with('success', "Streak simulasi untuk {$student->name} telah di-reset ke 0.");
            }
        }

        Student::query()->update(['manual_streak' => 0]);
        return redirect()->route('settings.index')->with('success', 'Semua streak simulasi siswa telah di-reset ke 0.');
    }

    /**
     * Simulasi Cepat: Berikan streak otomatis ke 3 siswa pertama untuk uji coba podium TV
     */
    public function simulateTop3Streak()
    {
        $students = Student::take(3)->get();
        if ($students->count() < 1) {
            return redirect()->route('settings.index')->with('warning', 'Belum ada data siswa untuk disimulasikan.');
        }

        $streaks = [15, 12, 10];
        foreach ($students as $index => $student) {
            $student->manual_streak = $streaks[$index] ?? 5;
            $student->save();
        }

        return redirect()->route('settings.index')->with('success', 'Simulasi Top 3 Podium TV berhasil dibuat (Juara 1: 15 hari, Juara 2: 12 hari, Juara 3: 10 hari). Buka Mode TV untuk melihat hasilnya!');
    }
}
