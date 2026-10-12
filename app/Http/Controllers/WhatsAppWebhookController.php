<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;
use App\Models\Student;
use App\Models\AttendanceLog;
use App\Models\Setting;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhook dari OpenWA
     */
    public function handleWebhook(Request $request)
    {
        Log::info("OpenWA Webhook Received: " . json_encode($request->all()));

        $event = $request->input('event');

        // 1. Cek apakah ini event pesan masuk (message.received atau onMessage)
        if ($event === 'message.received' || $event === 'onMessage' || $request->has('data.body') || $request->has('body')) {

            // 2. Ambil data penting dari payload OpenWA
            $senderId = $request->input('data.from') 
                     ?? $request->input('from') 
                     ?? $request->input('data.chatId') 
                     ?? $request->input('chatId');

            $message  = trim((string) (
                $request->input('data.body') 
                ?? $request->input('body') 
                ?? $request->input('data.content') 
                ?? $request->input('content')
            ));

            $name     = $request->input('data.contact.pushName') 
                     ?? $request->input('data.sender.pushname') 
                     ?? $request->input('contact.pushName') 
                     ?? $request->input('pushName') 
                     ?? 'Kak';

            // Jangan membalas pesan yang dikirim oleh bot/diri sendiri
            $fromMe = $request->input('data.fromMe') ?? $request->input('fromMe') ?? false;

            if (!empty($senderId) && !empty($message) && !$fromMe) {
                $this->processAutoReply($senderId, $message, $name);
            }
        }

        // Wajib me-return HTTP 200 OK agar OpenWA tahu webhook sukses diterima
        return response()->json(['status' => 'success']);
    }

    /**
     * Logika Auto-Reply pesan masuk dari WhatsApp
     */
    private function processAutoReply(string $senderId, string $message, string $name): void
    {
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');
        $checkinStart = Setting::get('checkin_start', '05:00');
        $checkinEnd = Setting::get('checkin_end', '06:30');
        $checkoutStart = Setting::get('checkout_start', '14:00');
        $lowerMsg = strtolower(trim($message));

        // 1. Logika Keyword Sapaan Dasar (seperti permintaan user)
        if ($lowerMsg === 'halo' || $lowerMsg === 'hai' || $lowerMsg === 'hello' || $lowerMsg === 'assalamualaikum' || $lowerMsg === 'p') {
            $balasan = "Hai Kak *{$name}*! Ada yang bisa kami bantu?\n\n"
                     . "Selamat datang di Layanan Informasi Presensi Otomatis *{$schoolName}*.\n\n"
                     . "📋 *Daftar Perintah Layanan:*\n"
                     . "• Ketik *CEK [PIN/Nama Siswa]* untuk melihat status presensi ananda hari ini (contoh: *CEK 101* atau *CEK JIBRIL*)\n"
                     . "• Ketik *INFO* untuk melihat jadwal batas absensi masuk sekolah\n"
                     . "• Ketik *BANTUAN* untuk menampilkan menu ini kembali\n\n"
                     . "_Pesan otomatis dari Sistem Presensi Fingerprint Sekolah._";

            WhatsAppService::sendRawMessage($senderId, $balasan, $name);
            return;
        }

        // 2. Cek Presensi Siswa (Keyword 'CEK ...')
        if (str_starts_with($lowerMsg, 'cek')) {
            $query = trim(substr($message, 3));
            if (empty($query)) {
                $balasan = "Mohon sertakan PIN atau nama siswa yang ingin dicek.\n\nContoh: *CEK 101* atau *CEK JIBRIL*";
                WhatsAppService::sendRawMessage($senderId, $balasan, $name);
                return;
            }

            // Cari siswa berdasarkan device_user_id (PIN) atau nama
            $student = Student::with('schoolClass')
                ->where('device_user_id', $query)
                ->orWhere('name', 'LIKE', "%{$query}%")
                ->first();

            if (!$student) {
                $balasan = "Maaf Kak {$name}, data siswa dengan kata kunci *{$query}* tidak ditemukan di sistem sekolah.";
                WhatsAppService::sendRawMessage($senderId, $balasan, $name);
                return;
            }

            $today = Carbon::today('Asia/Jakarta')->toDateString();
            $log = AttendanceLog::where('student_id', $student->id)
                ->whereDate('timestamp', $today)
                ->where('status_code', 0)
                ->orderBy('timestamp', 'asc')
                ->first();

            $className = $student->schoolClass->name ?? '-';
            $todayLabel = Carbon::parse($today, 'Asia/Jakarta')->translatedFormat('l, d F Y');

            if ($log) {
                $timeStr = Carbon::parse($log->timestamp, 'Asia/Jakarta')->format('H:i');
                $isOntime = $timeStr <= $checkinEnd;
                $statusBadge = $isOntime ? '✅ TEPAT WAKTU' : '⚠️ TERLAMBAT';

                $balasan = "📋 *STATUS PRESENSI HARI INI*\n\n"
                         . "Nama: *{$student->name}*\n"
                         . "Kelas: *{$className}*\n"
                         . "Hari/Tgl: {$todayLabel}\n"
                         . "Waktu Tap: *{$timeStr} WIB*\n"
                         . "Status: *{$statusBadge}*\n\n"
                         . "Ananda telah berhasil melakukan presensi sidik jari pada mesin sekolah. Terima kasih!";
            } else {
                $balasan = "⚠️ *BELUM MELAKUKAN PRESENSI*\n\n"
                         . "Nama: *{$student->name}*\n"
                         . "Kelas: *{$className}*\n"
                         . "Hari/Tgl: {$todayLabel}\n\n"
                         . "Ananda tercatat *belum melakukan tap sidik jari masuk* pada hari ini.\n"
                         . "Batas waktu masuk tepat waktu adalah pukul *{$checkinEnd} WIB*.";
            }

            WhatsAppService::sendRawMessage($senderId, $balasan, $name);
            return;
        }

        // 3. Info Jadwal Sekolah (Keyword 'INFO' / 'JADWAL')
        if ($lowerMsg === 'info' || $lowerMsg === 'jadwal') {
            $balasan = "ℹ️ *INFORMASI JADWAL PRESENSI SEKOLAH*\n\n"
                     . "🏫 *{$schoolName}*\n"
                     . "⏰ Gerbang Dibuka: *{$checkinStart} WIB*\n"
                     . "⏰ Batas Tepat Waktu: *{$checkinEnd} WIB*\n"
                     . "⏰ Jam Presensi Pulang: *{$checkoutStart} WIB*\n\n"
                     . "Siswa yang hadir lebih awal akan mendapatkan poin penghargaan tertinggi di layar Live Leaderboard TV Sekolah! 🏆";

            WhatsAppService::sendRawMessage($senderId, $balasan, $name);
            return;
        }

        // 4. Bantuan (Keyword 'BANTUAN' / 'MENU')
        if ($lowerMsg === 'bantuan' || $lowerMsg === 'menu') {
            $balasan = "📌 *PANDUAN PENGGUNAAN BOT PRESENSI*\n\n"
                     . "• *CEK [PIN/Nama]* : Melihat status kehadiran anak hari ini\n"
                     . "• *INFO* : Melihat jam operasional & batas absensi\n"
                     . "• *HALO* : Menyapa bot presensi\n\n"
                     . "Layanan Informasi Otomatis Presensi {$schoolName}.";

            WhatsAppService::sendRawMessage($senderId, $balasan, $name);
            return;
        }

        // 5. Default Fallback Auto-Reply
        $balasan = "Hai Kak *{$name}*! Ada yang bisa kami bantu? Ketik *INFO* untuk melihat batas jam masuk atau ketik *CEK [PIN]* untuk melihat status presensi siswa hari ini.";
        WhatsAppService::sendRawMessage($senderId, $balasan, $name);
    }
}
