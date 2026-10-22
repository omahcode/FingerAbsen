<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = ['major_id', 'name'];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function getGradeLevelAttribute(): ?string
    {
        return self::detectGradeLevel($this->name);
    }

    public static function detectGradeLevel(?string $className): ?string
    {
        if (empty($className)) {
            return null;
        }

        $clean = strtoupper(trim($className));
        // Hilangkan kata "KELAS" jika ada
        $clean = trim(preg_replace('/^KELAS\s+/i', '', $clean));

        if (preg_match('/^(XII|12)\b/i', $clean)) {
            return '12';
        }

        if (preg_match('/^(XI|11)\b/i', $clean)) {
            return '11';
        }

        if (preg_match('/^(X|10)\b/i', $clean)) {
            return '10';
        }

        return null;
    }
}
