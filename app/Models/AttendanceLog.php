<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLog extends Model {
    use SoftDeletes;
    protected $fillable = ['device_id', 'device_user_id', 'student_id', 'timestamp', 'status_code'];
    public function student() { return $this->belongsTo(Student::class); }
    public function device() { return $this->belongsTo(Device::class); }
}