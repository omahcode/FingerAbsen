<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['name', 'serial_number', 'ip_address', 'port', 'status', 'purpose', 'last_seen_at'];

    public function isForTeachers(): bool
    {
        return $this->purpose === 'teachers';
    }

    public function isForStudents(): bool
    {
        return $this->purpose === 'students';
    }

    public function isGeneral(): bool
    {
        return empty($this->purpose) || $this->purpose === 'all';
    }

    public function getPurposeLabelAttribute(): string
    {
        return match ($this->purpose) {
            'teachers' => 'Khusus Upacara Guru',
            'students' => 'Khusus Siswa',
            default => 'Semua (Siswa & Guru)',
        };
    }

    public function deviceUsers()
    {
        return $this->hasMany(DeviceUser::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
