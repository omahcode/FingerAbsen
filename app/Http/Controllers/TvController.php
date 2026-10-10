<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Student;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TvController extends Controller
{
    public function index()
    {
        // 1. Ambil Pengaturan Sistem & TV
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');
        $checkinStart = Setting::get('checkin_start', '05:00');
        $checkinEnd = Setting::get('checkin_end', '06:30');
        $checkoutStart = Setting::get('checkout_start', '14:00');
        $pointsOntime = (int) Setting::get('tv_points_ontime', '11');
        $pointsLate = (int) Setting::get('tv_points_late', '0');
        $pointMode = Setting::get('tv_point_mode', 'tiered');

        $tvSettings = [
            'school_name' => $schoolName,
            'tv_title' => Setting::get('tv_title', 'SELAMAT DATANG DI JURUSAN PENGEMBANGAN PERANGKAT LUNAK DAN GIM'),
            'tv_subtitle' => Setting::get('tv_subtitle', 'Sistem Informasi Presensi Biometrik Fingerprint'),
            'tv_running_text' => Setting::get('tv_running_text', "Selamat Datang! Batas absensi masuk tepat waktu adalah pukul {$checkinEnd} WIB. Tingkatkan kedisiplinan dan raih prestasi terbaik!"),
            'tv_slide_interval' => (int) Setting::get('tv_slide_interval', '30'),
            'tv_auto_fullscreen' => Setting::get('tv_auto_fullscreen', '1') === '1',
            'tv_show_welcome' => Setting::get('tv_show_welcome', '1') === '1',
            'tv_show_daily' => Setting::get('tv_show_daily', '1') === '1',
            'tv_show_weekly' => Setting::get('tv_show_weekly', '1') === '1',
            'tv_show_monthly' => Setting::get('tv_show_monthly', '1') === '1',
            'tv_show_hof' => Setting::get('tv_show_hof', '1') === '1',
            'tv_theme' => Setting::get('tv_theme', 'aurora'),
        ];

        // 2. Data Harian (Daily) - HANYA Absen Masuk (status_code 0 & waktu sebelum checkout_start)
        $today = Carbon::today()->toDateString();
        $activeDate = $today;

        // Ambil tap masuk pertama tiap siswa di hari aktif
        $rawDailyLogs = AttendanceLog::with(['student.schoolClass'])
            ->whereDate('timestamp', $activeDate)
            ->whereNotNull('student_id')
            ->where('status_code', 0)
            ->whereTime('timestamp', '<', $checkoutStart)
            ->orderBy('timestamp', 'asc')
            ->get();

        $dailyUnique = $rawDailyLogs->unique('student_id');

        $dailyData = $dailyUnique->map(function ($log) use ($checkinEnd) {
            $student = $log->student;
            $timeObj = Carbon::parse($log->timestamp);
            $timeStr = $timeObj->format('H:i');
            $isOnTime = $timeStr <= $checkinEnd;

            return [
                'name' => $student->name ?? 'Siswa ' . $log->device_user_id,
                'cls' => $student->schoolClass->name ?? '-',
                'time' => $timeStr,
                'is_ontime' => $isOnTime,
                'ini' => strtoupper(substr($student->name ?? 'S', 0, 1)),
                'photo' => ($student && $student->photo) ? asset('storage/' . $student->photo) : null,
            ];
        })->values()->take(10)->toArray();

        // 3. Hitung Statistik Hari Ini (Hanya Absen Masuk)
        $totalStudents = Student::count();
        $presentCount = $dailyUnique->count();
        $ontimeCount = $dailyUnique->filter(fn($l) => Carbon::parse($l->timestamp)->format('H:i') <= $checkinEnd)->count();
        $lateCount = $presentCount - $ontimeCount;
        $attendancePercentage = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0;

        $stats = [
            'total_students' => $totalStudents,
            'present_count' => $presentCount,
            'ontime_count' => $ontimeCount,
            'late_count' => $lateCount,
            'percentage' => $attendancePercentage,
            'active_date_label' => Carbon::parse($activeDate)->translatedFormat('l, d F Y'),
        ];

        // 4. Hitung Peringkat Mingguan (Weekly) - HANYA Absen Masuk
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        $weeklyLogs = AttendanceLog::with('student.schoolClass')
            ->whereBetween('timestamp', [$startOfWeek . ' 00:00:00', $endOfWeek . ' 23:59:59'])
            ->whereNotNull('student_id')
            ->where('status_code', 0)
            ->whereTime('timestamp', '<', $checkoutStart)
            ->orderBy('timestamp', 'asc')
            ->get();

        $weeklyData = $this->calculateLeaderboard($weeklyLogs, $checkinStart, $checkinEnd, $pointsOntime, $pointsLate, $pointMode, 50);

        // 5. Hitung Peringkat Bulanan (Monthly) - HANYA Absen Masuk
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $monthlyLogs = AttendanceLog::with('student.schoolClass')
            ->whereBetween('timestamp', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
            ->whereNotNull('student_id')
            ->where('status_code', 0)
            ->whereTime('timestamp', '<', $checkoutStart)
            ->orderBy('timestamp', 'asc')
            ->get();

        $monthlyData = $this->calculateLeaderboard($monthlyLogs, $checkinStart, $checkinEnd, $pointsOntime, $pointsLate, $pointMode, 200);

        // 6. Hitung Hall of Fame (All-Time Leaderboard) - HANYA Absen Masuk
        $allLogs = AttendanceLog::with('student.schoolClass')
            ->whereNotNull('student_id')
            ->where('status_code', 0)
            ->whereTime('timestamp', '<', $checkoutStart)
            ->orderBy('timestamp', 'asc')
            ->get();

        $hofData = $this->calculateLeaderboard($allLogs, $checkinStart, $checkinEnd, $pointsOntime, $pointsLate, $pointMode, null, true);

        // Jika data mingguan / bulanan / HoF kosong karena sistem baru dan belum ada streak manual, isi dengan fallback
        if (empty($weeklyData) && !empty($dailyData)) {
            $weeklyData = $this->generateFallbackBoard($dailyData, 1);
        }
        if (empty($monthlyData) && !empty($dailyData)) {
            $monthlyData = $this->generateFallbackBoard($dailyData, 4);
        }
        if (empty($hofData) && !empty($dailyData)) {
            $hofData = $this->generateFallbackBoard($dailyData, 10);
        }

        // Ambil juga raw logs masuk terbaru untuk live socket feed
        $logs = AttendanceLog::with(['student.schoolClass', 'device'])
            ->whereNotNull('student_id')
            ->where('status_code', 0)
            ->whereTime('timestamp', '<', $checkoutStart)
            ->orderBy('timestamp', 'desc')
            ->limit(30)
            ->get();

        return view('tv.index', compact(
            'logs',
            'dailyData',
            'weeklyData',
            'monthlyData',
            'hofData',
            'stats',
            'tvSettings'
        ));
    }

    /**
     * Hitung akumulasi poin bertingkat, ketepatan waktu, dan streak kehadiran (termasuk manual streak untuk uji coba)
     */
    private function calculateLeaderboard($logsCollection, string $checkinStart, string $checkinEnd, int $pointsOntime, int $pointsLate, string $pointMode = 'tiered', ?int $maxPoints = null, bool $isHof = false): array
    {
        // Kelompokkan log berdasarkan student_id
        $grouped = $logsCollection->groupBy('student_id');

        // Ambil semua siswa yang memiliki manual_streak > 0 untuk simulasi / uji coba (jika kolom sudah ada di database)
        $hasManualStreakCol = Schema::hasColumn('students', 'manual_streak');
        $manualStreakStudents = $hasManualStreakCol
            ? Student::with('schoolClass')->where('manual_streak', '>', 0)->get()->keyBy('id')
            : collect();

        // Gabungkan semua ID siswa yang punya log presensi atau punya streak manual
        $allStudentIds = $grouped->keys()->merge($manualStreakStudents->keys())->unique();

        if ($allStudentIds->isEmpty()) {
            return [];
        }

        $leaderboard = [];

        foreach ($allStudentIds as $studentId) {
            $studentLogs = $grouped->get($studentId, collect());
            $student = $studentLogs->first()?->student ?? $manualStreakStudents->get($studentId);
            if (!$student) continue;

            // Kelompokkan log per tanggal unik (hanya 1 tap masuk pertama per hari yang dihitung)
            $byDate = $studentLogs->groupBy(function ($item) {
                return Carbon::parse($item->timestamp)->toDateString();
            });

            $totalPoints = 0;
            $daysCount = $byDate->count();
            $latestTap = '';

            foreach ($byDate as $date => $dayLogs) {
                $earliestTap = $dayLogs->sortBy('timestamp')->first();
                $tapTime = Carbon::parse($earliestTap->timestamp)->format('H:i');
                $latestTap = $tapTime;

                // Hitung poin bertingkat (makin pagi makin tinggi)
                $tapPoint = $this->calculateTapPoints($tapTime, $checkinStart, $checkinEnd, $pointsOntime, $pointsLate, $pointMode);
                $totalPoints += $tapPoint;
            }

            // Tambahkan manual streak (jika diatur di setting untuk simulasi/testing)
            $manualStreak = (int) ($student->manual_streak ?? 0);
            $effectiveStreak = $daysCount + $manualStreak;
            $totalPoints += ($manualStreak * $pointsOntime);

            $leaderboard[] = [
                'name' => $student->name,
                'ini' => strtoupper(substr($student->name, 0, 1)),
                'cls' => $student->schoolClass->name ?? '-',
                'pts' => $totalPoints,
                'streak' => $effectiveStreak,
                'tap' => $latestTap ?: '06:45',
                'days' => $effectiveStreak,
                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
            ];
        }

        // Urutkan berdasarkan poin tertinggi, lalu streak tertinggi
        usort($leaderboard, function ($a, $b) {
            if ($a['pts'] === $b['pts']) {
                return $b['streak'] <=> $a['streak'];
            }
            return $b['pts'] <=> $a['pts'];
        });

        // Tentukan nilai max points untuk progress bar
        $topPts = !empty($leaderboard) ? max($leaderboard[0]['pts'], 10) : 100;
        $effectiveMax = $maxPoints ? max($maxPoints, $topPts) : $topPts;

        return array_map(function ($item) use ($effectiveMax) {
            $item['max'] = $effectiveMax;
            return $item;
        }, array_slice($leaderboard, 0, 10));
    }

    /**
     * Hitung poin kedatangan berdasarkan jam tap (Poin bertingkat: semakin awal semakin besar)
     */
    private function calculateTapPoints(string $tapTime, string $checkinStart, string $checkinEnd, int $maxPoints = 11, int $latePoints = 0, string $mode = 'tiered'): int
    {
        // Jika sudah melewati batas tepat waktu (terlambat), beri late points (default: 0 poin)
        if ($tapTime > $checkinEnd) {
            return $latePoints;
        }

        if ($mode === 'flat') {
            return $maxPoints;
        }

        // Mode Tiered (Poin Dinamis Bertingkat)
        $startParts = explode(':', $checkinStart);
        $endParts = explode(':', $checkinEnd);
        $tapParts = explode(':', $tapTime);

        $startMinutes = ((int)$startParts[0] * 60) + (int)($startParts[1] ?? 0);
        $endMinutes = ((int)$endParts[0] * 60) + (int)($endParts[1] ?? 0);
        $tapMinutes = ((int)$tapParts[0] * 60) + (int)($tapParts[1] ?? 0);

        if ($endMinutes <= $startMinutes) {
            return $maxPoints;
        }

        // Jika tap sebelum jam buka, berikan poin maksimal penuh
        if ($tapMinutes <= $startMinutes) {
            return $maxPoints;
        }

        // Proporsi waktu kedatangan (0.0 = jam buka paling awal, 1.0 = tepat di batas akhir jam masuk)
        $totalRange = $endMinutes - $startMinutes;
        $elapsed = $tapMinutes - $startMinutes;
        $progress = min(1.0, max(0.0, $elapsed / $totalRange));

        // Gradasi poin dari $maxPoints (misal 11) turun secara bertahap ke 1 poin
        $calculated = (int) round($maxPoints - ($progress * ($maxPoints - 1)));

        return max(1, min($maxPoints, $calculated));
    }

    /**
     * Helper fallback jika database baru dan belum memiliki riwayat panjang
     */
    private function generateFallbackBoard(array $dailyData, int $multiplier): array
    {
        return array_map(function ($d, $i) use ($multiplier) {
            $pts = (10 - $i) * 5 * $multiplier;
            return [
                'name' => $d['name'],
                'ini' => $d['ini'] ?? strtoupper(substr($d['name'], 0, 1)),
                'cls' => $d['cls'],
                'pts' => $pts,
                'max' => 50 * $multiplier,
                'streak' => max(1, 10 - $i),
                'tap' => $d['time'] ?? '06:45',
                'days' => max(1, 5 - $i),
            ];
        }, $dailyData, array_keys($dailyData));
    }
}