<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPermission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    /**
     * Dashboard Monitoring Khusus Kelas bagi Guru / Wali Kelas
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $allClasses = SchoolClass::orderBy('name')->get();

        // Tentukan kelas yang dimonitor
        if ($user->isGuru()) {
            $currentClassId = $user->school_class_id;
            $currentClass = $currentClassId ? SchoolClass::find($currentClassId) : null;
        } else {
            // Admin bisa memilih kelas mana saja untuk monitoring, default kelas pertama
            $currentClassId = $request->input('class_id', $allClasses->first()?->id);
            $currentClass = $currentClassId ? SchoolClass::find($currentClassId) : null;
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

        $studentsData = collect();
        $stats = [
            'total' => 0,
            'ontime' => 0,
            'late' => 0,
            'permission' => 0,
            'absent' => 0,
            'holiday' => 0,
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

        if ($currentClass) {
            // Scope hanya siswa non-admin
            $students = Student::where('school_class_id', $currentClass->id)
                ->where(function ($q) {
                    $q->where('privilege', 0)->orWhereNull('privilege');
                })
                ->whereRaw('LOWER(name) NOT LIKE ?', ['%admin%'])
                ->orderBy('name')
                ->get();

            // Ambil semua log kehadiran pada tanggal terpilih untuk kelas ini (absen masuk)
            $dateLogs = AttendanceLog::whereIn('student_id', $students->pluck('id'))
                ->whereDate('timestamp', $selectedDate)
                ->where('status_code', 0)
                ->orderBy('timestamp', 'asc')
                ->get()
                ->groupBy('student_id');

            // Ambil izin/sakit siswa pada tanggal terpilih
            $datePermissions = StudentPermission::whereIn('student_id', $students->pluck('id'))
                ->whereDate('date', $selectedDate)
                ->get()
                ->keyBy('student_id');

            $ontimeCount = 0;
            $lateCount = 0;
            $permissionCount = 0;
            $absentCount = 0;
            $holidayCount = 0;

            $studentsData = $students->map(function ($student) use ($dateLogs, $datePermissions, $checkinEnd, $isHolidayOrWeekend, &$ontimeCount, &$lateCount, &$permissionCount, &$absentCount, &$holidayCount) {
                $permission = $datePermissions->get($student->id);
                $logs = $dateLogs->get($student->id);
                $earliestLog = $logs ? $logs->first() : null;

                $notes = null;

                if ($permission) {
                    $status = 'permission';
                    $statusLabel = $permission->type;
                    $tapTime = 'Disampaikan Ortu';
                    $notes = $permission->notes;
                    $permissionCount++;
                } elseif ($earliestLog) {
                    $timeStr = Carbon::parse($earliestLog->timestamp)->format('H:i');
                    $isOntime = $timeStr <= $checkinEnd;
                    if ($isOntime) {
                        $status = 'ontime';
                        $statusLabel = 'Hadir Tepat Waktu';
                        $ontimeCount++;
                    } else {
                        $status = 'late';
                        $statusLabel = 'Hadir Terlambat';
                        $lateCount++;
                    }
                    $tapTime = $timeStr.' WIB';
                } else {
                    if ($isHolidayOrWeekend) {
                        $status = 'holiday';
                        $statusLabel = 'Libur Akhir Pekan';
                        $tapTime = '-';
                        $holidayCount++;
                    } else {
                        $status = 'absent';
                        $statusLabel = 'Belum Hadir / Alpha';
                        $tapTime = '-';
                        $absentCount++;
                    }
                }

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'photo' => $student->photo_url,
                    'parent_phone' => $student->parent_phone,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'tap_time' => $tapTime,
                    'device_user_id' => $student->device_user_id,
                    'permission' => $permission,
                    'notes' => $notes,
                ];
            });

            $total = $students->count();
            $present = $ontimeCount + $lateCount;
            $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $stats['total'] = $total;
            $stats['ontime'] = $ontimeCount;
            $stats['late'] = $lateCount;
            $stats['permission'] = $permissionCount;
            $stats['absent'] = $absentCount;
            $stats['holiday'] = $holidayCount;
            $stats['percentage'] = $percentage;
        }

        return view('guru.dashboard', compact(
            'currentClass',
            'allClasses',
            'studentsData',
            'stats',
            'user'
        ));
    }
}
