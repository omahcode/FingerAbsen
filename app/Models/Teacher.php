<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'name',
        'category',
        'device_user_id',
        'user_id',
        'phone',
        'photo',
    ];

    protected $appends = ['photo_url'];

    public static array $defaultCategories = [
        'GURU MTK',
        'GURU PPKN',
        'GURU SEJARAH',
        'GURU BAHASA INDONESIA',
        'GURU BAHASA INGGRIS',
        'GURU PAI',
        'GURU PJOK',
        'GURU BK',
        'GURU SENI BUDAYA',
        'GURU IPAS',
        'GURU INFORMATIKA',
    ];

    protected static function booted()
    {
        static::saving(function ($teacher) {
            if (empty($teacher->device_user_id) && ! empty($teacher->nip)) {
                $teacher->device_user_id = (string) $teacher->nip;
            }
        });
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/'.$this->photo);
        }

        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function fingerprintTemplates()
    {
        return $this->hasMany(FingerprintTemplate::class, 'teacher_id');
    }

    public function fingerprintTemplatesByPin()
    {
        return $this->hasMany(FingerprintTemplate::class, 'device_user_id', 'device_user_id');
    }

    public function getHasFingerprintAttribute(): bool
    {
        if (isset($this->fingerprint_templates_count)) {
            return $this->fingerprint_templates_count > 0;
        }

        return $this->fingerprintTemplates()->count() > 0
            || (! empty($this->device_user_id) && FingerprintTemplate::where('device_user_id', $this->device_user_id)->count() > 0);
    }
}
