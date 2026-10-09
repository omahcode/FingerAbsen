<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

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
            'tv_title' => Setting::get('tv_title', 'SELAMAT DATANG DI ' . strtoupper($schoolName)),
            'tv_subtitle' => Setting::get('tv_subtitle', 'Sistem Informasi Presensi Biometrik Fingerprint'),
            'tv_running_text' => Setting::get('tv_running_text', 'Selamat Datang! Batas absensi masuk tepat waktu adalah pukul 07:15 WIB. Jagalah selalu kedisiplinan dan semangat belajar.'),
            'tv_slide_interval' => Setting::get('tv_slide_interval', '30'),
            'tv_show_welcome' => Setting::get('tv_show_welcome', '1'),
            'tv_show_daily' => Setting::get('tv_show_daily', '1'),
            'tv_show_weekly' => Setting::get('tv_show_weekly', '1'),
            'tv_show_monthly' => Setting::get('tv_show_monthly', '1'),
            'tv_show_hof' => Setting::get('tv_show_hof', '1'),
            'tv_points_ontime' => Setting::get('tv_points_ontime', '10'),
            'tv_points_late' => Setting::get('tv_points_late', '5'),
            'tv_theme' => Setting::get('tv_theme', 'aurora'),
        ];

        return view('settings.index', compact('settings'));
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
            'tv_slide_interval' => 'required|integer|min:5|max:300',
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
        Setting::set('tv_title', $request->tv_title ?: 'SELAMAT DATANG DI ' . strtoupper($request->school_name), 'Judul Utama TV');
        Setting::set('tv_subtitle', $request->tv_subtitle ?: 'Sistem Informasi Presensi Biometrik Fingerprint', 'Subjudul TV');
        Setting::set('tv_running_text', $request->tv_running_text ?: '', 'Teks Berjalan Pengumuman TV');
        Setting::set('tv_slide_interval', (string) $request->tv_slide_interval, 'Durasi Slide TV (Detik)');
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
}
