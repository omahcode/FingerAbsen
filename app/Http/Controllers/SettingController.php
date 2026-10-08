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
        $settings = [
            'school_name' => Setting::get('school_name', 'Sekolah Menengah Kejuruan'),
            'checkin_start' => Setting::get('checkin_start', '06:00'),
            'checkin_end' => Setting::get('checkin_end', '07:15'),
            'checkin_late_end' => Setting::get('checkin_late_end', '08:30'),
            'checkout_start' => Setting::get('checkout_start', '14:00'),
            'checkout_end' => Setting::get('checkout_end', '18:00'),
            'wa_notification' => Setting::get('wa_notification', '1'),
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
        ]);

        Setting::set('school_name', $request->school_name, 'Nama Sekolah');
        Setting::set('checkin_start', $request->checkin_start, 'Jam Mulai Absen Masuk');
        Setting::set('checkin_end', $request->checkin_end, 'Batas Jam Masuk Tepat Waktu');
        Setting::set('checkin_late_end', $request->checkin_late_end, 'Batas Maksimal Masuk Terlambat');
        Setting::set('checkout_start', $request->checkout_start, 'Jam Mulai Absen Pulang');
        Setting::set('checkout_end', $request->checkout_end, 'Batas Akhir Absen Pulang');
        Setting::set('wa_notification', $request->has('wa_notification') ? '1' : '0', 'Status Notifikasi WhatsApp');

        return redirect()->route('settings.index')->with('success', 'Pengaturan jam presensi berhasil disimpan.');
    }
}
