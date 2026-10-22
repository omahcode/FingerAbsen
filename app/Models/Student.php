<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'nis',
        'name',
        'photo',
        'school_class_id',
        'parent_phone',
        'device_user_id',
        'privilege',
        'manual_streak',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/'.$this->photo);
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

    public function permissions()
    {
        return $this->hasMany(StudentPermission::class);
    }

    public function studentPermissions()
    {
        return $this->hasMany(StudentPermission::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(ParentPushSubscription::class);
    }

    public function fingerprintTemplates()
    {
        return $this->hasMany(FingerprintTemplate::class, 'device_user_id', 'device_user_id');
    }

    public function getHasFingerprintAttribute(): bool
    {
        return ($this->fingerprint_templates_count ?? $this->fingerprintTemplates()->count()) > 0;
    }

    /**
     * Hitung streak kehadiran berurutan siswa dengan melewati hari libur / akhir pekan / hari khusus (kemah, ekspram, libur).
     * Hari Sabtu & Minggu serta kegiatan khusus kelas (seperti kemah kelas 10) atau libur sekolah otomatis dilewati
     * sehingga streak siswa tidak akan hancur/rusak.
     */
    public function calculateConsecutiveStreak($endDate = null): int
    {
        $currentDate = $endDate ? Carbon::parse($endDate, 'Asia/Jakarta') : Carbon::today('Asia/Jakarta');
        $streak = (int) ($this->manual_streak ?? 0);

        // Ambil riwayat tanggal siswa pernah absen masuk
        $attendedDates = $this->attendanceLogs()
            ->where('status_code', 0)
            ->whereDate('timestamp', '<=', $currentDate->toDateString())
            ->pluck('timestamp')
            ->map(fn ($t) => Carbon::parse($t, 'Asia/Jakarta')->toDateString())
            ->unique()
            ->flip(); // Menggunakan flip untuk O(1) lookup

        $lookbackLimit = 60; // Cek hingga 60 hari ke belakang
        $startDateLimit = $currentDate->copy()->subDays($lookbackLimit)->toDateString();

        // Ambil semua kegiatan / hari khusus yang melindungi streak yang relevan untuk siswa ini
        try {
            $exemptEvents = SchoolEvent::where('preserve_streak', true)
                ->where('end_date', '>=', $startDateLimit)
                ->where('start_date', '<=', $currentDate->toDateString())
                ->with('schoolClass')
                ->get()
                ->filter(fn ($event) => $event->appliesToStudent($this));
        } catch (\Throwable $e) {
            $exemptEvents = collect();
        }

        // Helper closure untuk mengecek apakah hari adalah hari libur atau hari bebas kegiatan bagi siswa
        $isExemptDay = function (Carbon $date) use ($exemptEvents): bool {
            if (! Setting::isSchoolDay($date)) {
                return true;
            }

            $dateStr = $date->toDateString();
            foreach ($exemptEvents as $event) {
                $startStr = $event->start_date ? Carbon::parse($event->start_date)->toDateString() : '';
                $endStr = $event->end_date ? Carbon::parse($event->end_date)->toDateString() : '';

                if ($startStr <= $dateStr && $endStr >= $dateStr) {
                    return true;
                }
            }

            return false;
        };

        $checkDate = $currentDate->copy();

        // Jika hari ini bukan hari sekolah efektif / sedang kemah/libur, mundur ke hari sekolah efektif terakhir
        if ($isExemptDay($checkDate)) {
            while ($isExemptDay($checkDate) && $lookbackLimit > 0) {
                $checkDate->subDay();
                $lookbackLimit--;
            }
        } elseif (! isset($attendedDates[$checkDate->toDateString()])) {
            // Hari ini adalah hari sekolah tapi siswa belum hadir hari ini (misal pagi hari):
            // Jangan langsung hanguskan streak, periksa hari sekolah sebelumnya
            $checkDate->subDay();
            while ($isExemptDay($checkDate) && $lookbackLimit > 0) {
                $checkDate->subDay();
                $lookbackLimit--;
            }
        }

        while ($lookbackLimit > 0) {
            // Lewati hari yang bukan hari sekolah atau hari khusus siswa
            if ($isExemptDay($checkDate)) {
                $checkDate->subDay();
                $lookbackLimit--;

                continue;
            }

            // Jika hari sekolah dan siswa hadir -> streak bertambah
            if (isset($attendedDates[$checkDate->toDateString()])) {
                $streak++;
                $checkDate->subDay();
                $lookbackLimit--;
            } else {
                // Hari sekolah dan siswa tidak hadir -> streak berhenti
                break;
            }
        }

        return $streak;
    }

    /**
     * Ambil kegiatan/libur khusus aktif yang sedang berlangsung untuk siswa ini
     */
    public function getCurrentSchoolEvent($date = null): ?SchoolEvent
    {
        return SchoolEvent::getActiveEventForStudent($this, $date);
    }
}
