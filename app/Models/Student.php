<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Student extends Model {
    protected $fillable = ['name', 'school_class_id', 'device_user_id', 'privilege'];
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function attendanceLogs() { return $this->hasMany(AttendanceLog::class); }
}