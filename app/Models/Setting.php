<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'label'];

    /**
     * Ambil nilai pengaturan berdasarkan key dengan nilai default dan caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set nilai pengaturan dan perbarui cache
     */
    public static function set(string $key, $value, ?string $label = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'label' => $label]
        );
        Cache::forget("setting_{$key}");
    }

    /**
     * Ambil array hari sekolah aktif (1=Senin ... 6=Sabtu, 0=Minggu)
     * Default: Senin sampai Jumat [1, 2, 3, 4, 5]
     */
    public static function getSchoolDays(): array
    {
        $raw = self::get('school_days', null);
        if ($raw === null) {
            return [1, 2, 3, 4, 5];
        }

        if (is_array($raw)) {
            return array_map('intval', $raw);
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_map('intval', $decoded);
        }

        // Jika string koma misal "1,2,3,4,5"
        return array_map('intval', explode(',', $raw));
    }

    /**
     * Cek apakah suatu tanggal adalah hari sekolah aktif (bukan hari libur mingguan & bukan libur sekolah)
     */
    public static function isSchoolDay($date = null): bool
    {
        $carbon = $date ? Carbon::parse($date, 'Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $dayOfWeek = (int) $carbon->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday

        if (! in_array($dayOfWeek, self::getSchoolDays(), true)) {
            return false;
        }

        // Cek apakah ada libur umum seluruh sekolah (misal libur 1-2 minggu / libur nasional)
        try {
            if (SchoolEvent::isGeneralHoliday($carbon)) {
                return false;
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel belum ada saat migrasi awal
        }

        return true;
    }

    /**
     * Cek apakah suatu tanggal adalah hari libur sekolah / akhir pekan
     */
    public static function isHolidayOrWeekend($date = null): bool
    {
        return ! self::isSchoolDay($date);
    }
}
