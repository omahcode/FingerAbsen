<?php

namespace App\Jobs;

use App\Models\AttendanceLog;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWebPushAttendanceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public AttendanceLog $attendanceLog) {}

    /**
     * Execute the job.
     */
    public function handle(WebPushService $webPushService): void
    {
        $log = $this->attendanceLog;
        $student = $log->student;

        if (! $student) {
            return;
        }

        $time = Carbon::parse($log->timestamp)->format('H:i');
        $isMasuk = ((int) $log->status_code === 0);
        $typeStr = $isMasuk ? 'Masuk' : 'Pulang';

        $title = "Presensi {$typeStr}: {$student->name}";
        $body = $isMasuk
            ? "Ananda {$student->name} telah melakukan presensi masuk di sekolah pada pukul {$time} WIB."
            : "Ananda {$student->name} telah melakukan presensi pulang sekolah pada pukul {$time} WIB.";

        $webPushService->sendToStudent($student, $title, $body, [
            'status_code' => $log->status_code,
            'time' => $time,
        ]);
    }
}
