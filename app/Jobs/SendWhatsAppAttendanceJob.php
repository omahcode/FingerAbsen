<?php

namespace App\Jobs;

use App\Models\Student;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppAttendanceJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public Student $student;
    public string $timestamp;
    public int $statusCode;
    public ?string $deviceName;

    /**
     * Jumlah percobaan maksimal jika gagal
     */
    public int $tries = 3;

    /**
     * Jeda waktu (detik) sebelum mencoba ulang jika request gagal
     */
    public int $backoff = 10;

    /**
     * Waktu timeout job (detik)
     */
    public int $timeout = 30;

    /**
     * Buat instance Job baru
     */
    public function __construct(Student $student, string $timestamp, int $statusCode, ?string $deviceName = null)
    {
        $this->student = $student;
        $this->timestamp = $timestamp;
        $this->statusCode = $statusCode;
        $this->deviceName = $deviceName;
    }

    /**
     * Eksekusi pengiriman pesan WhatsApp dalam antrean background
     */
    public function handle(): void
    {
        Log::info("Processing SendWhatsAppAttendanceJob for student: {$this->student->name} (WA: {$this->student->parent_phone})");

        // Kirim notifikasi via WhatsAppService
        $success = WhatsAppService::sendAttendanceNotification(
            $this->student,
            $this->timestamp,
            $this->statusCode,
            $this->deviceName
        );

        if (!$success) {
            Log::warning("Job WhatsApp belum berhasil terkirim untuk {$this->student->name}.");
        }

        // Beri jeda 1 detik antar pesan antrean agar tidak memicu deteksi spam
        sleep(1);
    }
}
