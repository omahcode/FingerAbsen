<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'user_type',
        'student_id',
        'teacher_id',
        'device_user_id',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function fingerprintTemplates()
    {
        return $this->hasMany(FingerprintTemplate::class, 'device_user_id', 'device_user_id');
    }

    public function getNameAttribute(): string
    {
        if ($this->user_type === 'teacher' && $this->teacher) {
            return $this->teacher->name;
        }

        if ($this->student) {
            return $this->student->name;
        }

        return 'Pengguna '.$this->device_user_id;
    }

    public function getIdentifierAttribute(): ?string
    {
        if ($this->user_type === 'teacher' && $this->teacher) {
            return $this->teacher->nip ?: $this->device_user_id;
        }

        if ($this->student) {
            return $this->student->nis ?: $this->device_user_id;
        }

        return $this->device_user_id;
    }
}
