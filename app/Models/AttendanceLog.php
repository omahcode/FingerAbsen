<?php

namespace App\Models;

use App\Jobs\SendWebPushAttendanceJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLog extends Model
{
    use SoftDeletes;

    protected $fillable = ['device_id', 'device_user_id', 'student_id', 'teacher_id', 'timestamp', 'status_code'];

    protected static function booted(): void
    {
        static::created(function (AttendanceLog $log) {
            if ($log->student_id && in_array((int) $log->status_code, [0, 1])) {
                SendWebPushAttendanceJob::dispatch($log);
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
