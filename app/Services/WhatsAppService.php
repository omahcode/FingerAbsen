<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim notifikasi WhatsApp ke nomor HP orang tua ketika siswa melakukan presensi
     */
    public static function sendAttendanceNotification(Student $student, string $timestamp, int $statusCode, ?string $deviceName = null): bool
    {
        // Cek apakah fitur notifikasi WhatsApp aktif di pengaturan
        if (Setting::get('wa_notification', '1') !== '1') {
            Log::info('WhatsApp Skip: Notifikasi WhatsApp dinonaktifkan di Pengaturan Sistem.');

            return false;
        }

        if (empty($student->parent_phone)) {
            Log::info("WhatsApp Skip: Siswa {$student->name} (PIN: {$student->device_user_id}) tidak memiliki nomor WA ortu.");

            return false;
        }

        $receiver = self::formatPhoneNumber($student->parent_phone);
        if (empty($receiver)) {
            Log::warning("WhatsApp Skip: Format nomor WA ortu siswa {$student->name} tidak valid: {$student->parent_phone}");

            return false;
        }

        // Ambil jam presensi
        $scanTime = Carbon::parse($timestamp, 'Asia/Jakarta');
        $timeOnly = $scanTime->format('H:i');

        // Ambil pengaturan jam
        $checkinEnd = Setting::get('checkin_end', '06:30');
        $checkoutStart = Setting::get('checkout_start', '14:00');
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');

        // Tentukan label status berdasarkan kode dan jam presensi
        if ($statusCode == 1 || $timeOnly >= $checkoutStart) {
            $statusText = 'PULANG';
        } elseif ($timeOnly > $checkinEnd) {
            $statusText = 'MASUK (TERLAMBAT)';
        } else {
            $statusText = 'MASUK (TEPAT WAKTU)';
        }

        $jamFormatted = $scanTime->translatedFormat('l, d F Y - H:i').' WIB';
        $location = $deviceName ? " pada {$deviceName}" : '';
        $className = $student->schoolClass->name ?? '-';

        $message = "📢 *NOTIFIKASI PRESENSI SISWA*\n\n"
                 ."Yth. Orang Tua / Wali Murid dari *{$student->name}*,\n\n"
                 ."Menginformasikan bahwa ananda telah presensi *{$statusText}* pada:\n"
                 ."📅 Waktu: {$jamFormatted}{$location}\n"
                 ."🏫 Kelas: *{$className}*\n"
                 ."🏢 {$schoolName}\n\n"
                 .'_Pesan otomatis dari Sistem Presensi Fingerprint Sekolah._';

        return self::sendRawMessage($receiver, $message, $student->name);
    }

    /**
     * Kirim pesan teks sembarang via OpenWA REST API / Gateway
     *
     * @param  string  $to  Nomor tujuan atau JID (e.g. 628123456789 atau 80535200227425@lid atau 6281234@c.us)
     * @param  string  $message  Isi pesan teks
     * @param  string|null  $recipientName  Nama penerima untuk logging
     */
    public static function sendRawMessage(string $to, string $message, ?string $recipientName = null): bool
    {
        // Format destination JID / ChatId jika belum berformat @
        $chatId = self::formatChatId($to);

        $openwaUrl = rtrim(Setting::get('openwa_url', env('OPENWA_URL', env('WA_GATEWAY_URL', 'http://localhost:2785'))), '/');
        $sessionId = Setting::get('openwa_session', env('OPENWA_SESSION', 'default'));
        $apiKey = Setting::get('openwa_api_key', env('OPENWA_API_KEY', env('WA_API_KEY', '')));

        Log::info("OpenWA: Mengirim pesan ke {$chatId}".($recipientName ? " ({$recipientName})" : '')." via {$openwaUrl}");

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        if (! empty($apiKey)) {
            $headers['X-API-Key'] = $apiKey;
            $headers['x-api-key'] = $apiKey;
            $headers['Authorization'] = 'Bearer '.$apiKey;
        }

        // Susun payload yang kompatibel dengan OpenWA Easy API, REST API, & bridge gateway
        $payload = [
            'chatId' => $chatId,
            'to' => $chatId,
            'receiver' => $chatId,
            'text' => $message,
            'message' => $message,
        ];

        // Tentukan kandidat endpoint
        if (str_contains($openwaUrl, '/send') || str_contains($openwaUrl, '/messages')) {
            $endpoints = [$openwaUrl];
        } else {
            $endpoints = [
                "{$openwaUrl}/api/sessions/{$sessionId}/messages/send-text",
                "{$openwaUrl}/sendText",
                "{$openwaUrl}/api/sendText",
                "{$openwaUrl}/api/send/text",
            ];
        }

        $lastError = null;

        foreach ($endpoints as $url) {
            try {
                $response = Http::timeout(6)->withHeaders($headers)->post($url, $payload);

                if ($response->successful()) {
                    Log::info("✅ OpenWA berhasil terkirim ke {$chatId} via [{$url}]");

                    return true;
                } else {
                    $lastError = "HTTP {$response->status()} Body: ".substr($response->body(), 0, 150);
                    Log::warning("OpenWA attempt gagal [{$url}]: {$lastError}");
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("OpenWA exception pada [{$url}]: {$lastError}");
            }
        }

        Log::error("❌ OpenWA gagal terkirim ke {$chatId} setelah mencoba endpoint. Error: {$lastError}");

        return false;
    }

    /**
     * Format chatId / JID untuk WhatsApp (OpenWA membutuhkan format JID seperti 628xxx@c.us atau tetap @lid / @g.us)
     */
    public static function formatChatId(string $to): string
    {
        $to = trim($to);

        // Jika sudah berupa JID (mengandung @ seperti @c.us, @lid, @g.us, @s.whatsapp.net)
        if (str_contains($to, '@')) {
            return $to;
        }

        // Jika berupa nomor telepon biasa, bersihkan dan format ke 628xxx@c.us
        $phone = self::formatPhoneNumber($to);

        return $phone ? ($phone.'@c.us') : $to;
    }

    /**
     * Format nomor HP agar standar internasional (0812... menjadi 62812...)
     */
    public static function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleaned)) {
            return '';
        }

        if (str_starts_with($cleaned, '08')) {
            $cleaned = '628'.substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62'.$cleaned;
        }

        return $cleaned;
    }
}
