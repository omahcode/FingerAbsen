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
        'GURU JURUSAN',
        'GURU BAHASA INDONESIA',
        'GURU BAHASA INGGRIS',
        'GURU PAI',
        'GURU PJOK',
        'GURU BK',
        'GURU SENI BUDAYA',
        'GURU IPAS',
        'GURU INFORMATIKA',
    ];

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
        return $this->hasMany(FingerprintTemplate::class, 'device_user_id', 'device_user_id');
    }

    public function getHasFingerprintAttribute(): bool
    {
        return ($this->fingerprint_templates_count ?? $this->fingerprintTemplates()->count()) > 0;
    }
}
