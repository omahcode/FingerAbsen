<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolEvent extends Model
{
    protected $fillable = [
        'title',
        'type',
        'start_date',
        'end_date',
        'target_type',
        'grade_level',
        'school_class_id',
        'preserve_streak',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'preserve_streak' => 'boolean',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Cek apakah kegiatan/hari khusus ini berlaku untuk siswa yang diberikan
     */
    public function appliesToStudent(Student $student): bool
    {
        if ($this->target_type === 'all') {
            return true;
        }

        if ($this->target_type === 'grade') {
            if (empty($this->grade_level)) {
                return false;
            }

            $studentGrade = $student->schoolClass?->grade_level;

            return $studentGrade === (string) $this->grade_level;
        }

        if ($this->target_type === 'class') {
            return (int) $student->school_class_id === (int) $this->school_class_id;
        }

        return false;
    }

    /**
     * Cek apakah ada libur umum yang berlaku untuk seluruh sekolah pada tanggal tertentu
     */
    public static function isGeneralHoliday($date = null): bool
    {
        $carbon = $date ? Carbon::parse($date, 'Asia/Jakarta') : Carbon::today('Asia/Jakarta');
        $dateStr = $carbon->toDateString();

        return self::where('target_type', 'all')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->exists();
    }

    /**
     * Ambil event libur umum untuk tanggal tertentu jika ada
     */
    public static function getGeneralHoliday($date = null): ?self
    {
        $carbon = $date ? Carbon::parse($date, 'Asia/Jakarta') : Carbon::today('Asia/Jakarta');
        $dateStr = $carbon->toDateString();

        return self::where('target_type', 'all')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();
    }

    /**
     * Cek apakah siswa dibebaskan dari absensi / dilindungi streak-nya pada tanggal tertentu
     */
    public static function isExemptForStudent(Student $student, $date = null): bool
    {
        $carbon = $date ? Carbon::parse($date, 'Asia/Jakarta') : Carbon::today('Asia/Jakarta');
        $dateStr = $carbon->toDateString();

        $events = self::where('preserve_streak', true)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->get();

        foreach ($events as $event) {
            if ($event->appliesToStudent($student)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil event khusus aktif yang berlaku untuk siswa pada tanggal tertentu
     */
    public static function getActiveEventForStudent(Student $student, $date = null): ?self
    {
        $carbon = $date ? Carbon::parse($date, 'Asia/Jakarta') : Carbon::today('Asia/Jakarta');
        $dateStr = $carbon->toDateString();

        $events = self::where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->with('schoolClass')
            ->get();

        foreach ($events as $event) {
            if ($event->appliesToStudent($student)) {
                return $event;
            }
        }

        return null;
    }
}
