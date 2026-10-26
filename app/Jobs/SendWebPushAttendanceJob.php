<?php

namespace App\Jobs;

use App\Models\AttendanceLog;
use App\Models\Setting;
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

        if ($isMasuk) {
            $checkinEnd = Setting::get('checkin_end', '07:15');
            $isOntime = $time <= $checkinEnd;

            if ($isOntime) {
                $title = "🟢 Presensi Masuk Tepat Waktu: {$student->name}";
                $body = "Ananda {$student->name} hadir tepat waktu dan berhasil tap presensi masuk pada pukul {$time} WIB.";
            } else {
                $title = "🟡 Presensi Masuk (Terlambat): {$student->name}";
                $body = "Perhatian: Ananda {$student->name} telah tap presensi masuk pukul {$time} WIB (melewati batas waktu sekolah {$checkinEnd} WIB).";
            }
        } else {
            $title = "🔵 Presensi Pulang: {$student->name}";
            $body = "Ananda {$student->name} telah melakukan presensi kepulangan sekolah pada pukul {$time} WIB. Selamat beristirahat di rumah.";
        }

        $webPushService->sendToStudent($student, $title, $body, [
            'status_code' => $log->status_code,
            'time' => $time,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'type' => $isMasuk ? 'checkin' : 'checkout',
        ]);
    }
}
