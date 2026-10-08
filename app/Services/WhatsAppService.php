<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppService
{
    /**
     * Kirim notifikasi WhatsApp ke nomor HP orang tua ketika siswa melakukan presensi
     */
    public static function sendAttendanceNotification(Student $student, string $timestamp, int $statusCode, ?string $deviceName = null): bool
    {
        if (empty($student->parent_phone)) {
            Log::info("WhatsApp Skip: Siswa {$student->name} (PIN: {$student->device_user_id}) tidak memiliki nomor WA ortu.");
            return false;
        }

        $receiver = self::formatPhoneNumber($student->parent_phone);
        if (empty($receiver)) {
            Log::warning("WhatsApp Skip: Format nomor WA ortu siswa {$student->name} tidak valid: {$student->parent_phone}");
            return false;
        }

        $statusText = ($statusCode == 0) ? 'MASUK' : (($statusCode == 1) ? 'PULANG' : 'PRESENSI');
        $jamMasuk = Carbon::parse($timestamp)->translatedFormat('l, d F Y - H:i') . ' WIB';
        $location = $deviceName ? " pada {$deviceName}" : "";
        $className = $student->schoolClass->name ?? '-';

        $message = "Yth. Orang Tua / Wali Murid dari *{$student->name}*,\n\n"
                 . "Menginformasikan bahwa ananda telah presensi *{$statusText}* pada {$jamMasuk}{$location}.\n"
                 . "Kelas: *{$className}*\n\n"
                 . "_Pesan otomatis dari Sistem Presensi Fingerprint Sekolah._";

        try {
            $apiKey = env('WA_API_KEY', 'mysecretkey123');
            $gatewayUrl = env('WA_GATEWAY_URL', 'https://wa-gateway.neperone.id/api/send/text');

            Log::info("Mengirim WA ke {$receiver} untuk siswa {$student->name}...");

            $response = Http::timeout(5)->withHeaders([
                'x-api-key' => $apiKey,
            ])->post($gatewayUrl, [
                'receiver' => $receiver,
                'message'  => $message,
            ]);

            if ($response->successful()) {
                Log::info("✅ WhatsApp berhasil terkirim ke {$receiver} (Siswa: {$student->name})");
                return true;
            } else {
                Log::warning("❌ WhatsApp gagal terkirim ke {$receiver}: HTTP " . $response->status() . " Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("❌ WhatsApp Exception error ke {$receiver}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format nomor HP agar standar (misal 0812... menjadi 62812... atau tetap standar internasional)
     */
    public static function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleaned)) {
            return '';
        }

        if (str_starts_with($cleaned, '08')) {
            $cleaned = '628' . substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }
}
