<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Major;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MajorMonitoringController extends Controller
{
    /**
     * Dashboard Monitoring Presensi Khusus Tingkat Jurusan
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $allMajors = Major::with('schoolClasses')->orderBy('name')->get();

        // Tentukan jurusan yang dipilih
        $selectedMajorId = $request->input('major_id');

        if ($selectedMajorId) {
            $currentMajor = Major::find($selectedMajorId);
        } elseif ($user->major_id) {
            $currentMajor = Major::find($user->major_id);
        } elseif ($user->school_class_id && $user->schoolClass?->major_id) {
            $currentMajor = Major::find($user->schoolClass->major_id);
        } else {
            $currentMajor = $allMajors->first();
        }

        // Tanggal yang dimonitor (bisa pilih tanggal lewat date picker)
        $selectedDate = $request->input('date', Carbon::today('Asia/Jakarta')->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = Carbon::today('Asia/Jakarta')->toDateString();
        }

        $isToday = ($selectedDate === Carbon::today('Asia/Jakarta')->toDateString());
        $isSchoolDay = Setting::isSchoolDay($selectedDate);
        $isHolidayOrWeekend = Setting::isHolidayOrWeekend($selectedDate);

        $currentTimeWib = Carbon::now('Asia/Jakarta')->format('H:i');
        $checkinEnd = Setting::get('checkin_end', '06:30');
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');

        $majorStats = [
            'total_students' => 0,
            'ontime_count' => 0,
            'late_count' => 0,
            'absent_count' => 0,
            'holiday_count' => 0,
            'percentage' => 0,
            'date' => $selectedDate,
            'date_formatted' => Carbon::parse($selectedDate)->translatedFormat('l, d F Y'),
            'today_formatted' => Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y'),
            'is_today' => $isToday,
            'is_school_day' => $isSchoolDay,
            'is_holiday' => $isHolidayOrWeekend,
            'current_time' => $currentTimeWib,
            'checkin_end' => $checkinEnd,
        ];

        $classBreakdown = collect();
        $studentsList = collect();

        if ($currentMajor) {
            $classes = $currentMajor->schoolClasses;
            $classIds = $classes->pluck('id');

            // Ambil seluruh siswa non-admin di jurusan ini
            $students = Student::with('schoolClass')
                ->whereIn('school_class_id', $classIds)
                ->where(function ($q) {
                    $q->where('privilege', 0)->orWhereNull('privilege');
                })
                ->whereRaw('LOWER(name) NOT LIKE ?', ['%admin%'])
                ->orderBy('name')
                ->get();

            // Ambil semua log tap masuk pada tanggal terpilih untuk siswa di jurusan ini
            $dateLogs = AttendanceLog::whereIn('student_id', $students->pluck('id'))
                ->whereDate('timestamp', $selectedDate)
                ->where('status_code', 0)
                ->orderBy('timestamp', 'asc')
                ->get()
                ->groupBy('student_id');

            // Ambil daftar guru wali kelas per kelas
            $homeroomTeachers = User::where('role', 'guru')
                ->whereIn('school_class_id', $classIds)
                ->get()
                ->keyBy('school_class_id');

            $ontimeTotal = 0;
            $lateTotal = 0;
            $absentTotal = 0;
            $holidayTotal = 0;

            // 1. Data Siswa
            $studentsList = $students->map(function ($student) use ($dateLogs, $checkinEnd, $isHolidayOrWeekend, &$ontimeTotal, &$lateTotal, &$absentTotal, &$holidayTotal) {
                $logs = $dateLogs->get($student->id);
                $earliestLog = $logs ? $logs->first() : null;

                if ($earliestLog) {
                    $timeStr = Carbon::parse($earliestLog->timestamp)->format('H:i');
                    $isOntime = $timeStr <= $checkinEnd;
                    if ($isOntime) {
                        $status = 'ontime';
                        $statusLabel = 'Hadir Tepat Waktu';
                        $ontimeTotal++;
                    } else {
                        $status = 'late';
                        $statusLabel = 'Hadir Terlambat';
                        $lateTotal++;
                    }
                    $tapTime = $timeStr.' WIB';
                } else {
                    if ($isHolidayOrWeekend) {
                        $status = 'holiday';
                        $statusLabel = 'Libur Akhir Pekan';
                        $tapTime = '-';
                        $holidayTotal++;
                    } else {
                        $status = 'absent';
                        $statusLabel = 'Belum Hadir / Alpha';
                        $tapTime = '-';
                        $absentTotal++;
                    }
                }

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'photo' => $student->photo_url,
                    'school_class_id' => $student->school_class_id,
                    'class_name' => $student->schoolClass->name ?? '-',
                    'parent_phone' => $student->parent_phone,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'tap_time' => $tapTime,
                    'device_user_id' => $student->device_user_id,
                ];
            });

            // 2. Statistik Jurusan
            $totalStudents = $students->count();
            $presentTotal = $ontimeTotal + $lateTotal;
            $majorPercentage = $totalStudents > 0 ? round(($presentTotal / $totalStudents) * 100, 1) : 0;

            $majorStats['total_students'] = $totalStudents;
            $majorStats['ontime_count'] = $ontimeTotal;
            $majorStats['late_count'] = $lateTotal;
            $majorStats['absent_count'] = $absentTotal;
            $majorStats['holiday_count'] = $holidayTotal;
            $majorStats['percentage'] = $majorPercentage;

            // 3. Breakdown per Kelas dalam Jurusan
            $classBreakdown = $classes->map(function ($cls) use ($studentsList, $homeroomTeachers) {
                $classStudents = $studentsList->where('school_class_id', $cls->id);
                $total = $classStudents->count();
                $ontime = $classStudents->where('status', 'ontime')->count();
                $late = $classStudents->where('status', 'late')->count();
                $absent = $classStudents->where('status', 'absent')->count();
                $holiday = $classStudents->where('status', 'holiday')->count();
                $present = $ontime + $late;
                $pct = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                $wali = $homeroomTeachers->get($cls->id);

                return [
                    'id' => $cls->id,
                    'name' => $cls->name,
                    'wali_name' => $wali ? $wali->name : 'Belum Ditugaskan',
                    'total' => $total,
                    'ontime' => $ontime,
                    'late' => $late,
                    'absent' => $absent,
                    'holiday' => $holiday,
                    'present' => $present,
                    'percentage' => $pct,
                ];
            })->sortByDesc('percentage')->values();
        }

        return view('monitoring.jurusan', compact(
            'currentMajor',
            'allMajors',
            'majorStats',
            'classBreakdown',
            'studentsList',
            'user'
        ));
    }
}
