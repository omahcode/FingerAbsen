<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'name',
        'photo',
        'school_class_id',
        'parent_phone',
        'device_user_id',
        'privilege',
        'manual_streak'
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return null;
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function fingerprintTemplates()
    {
        return $this->hasMany(FingerprintTemplate::class, 'device_user_id', 'device_user_id');
    }

    public function getHasFingerprintAttribute(): bool
    {
        return ($this->fingerprint_templates_count ?? $this->fingerprintTemplates()->count()) > 0;
    }
}