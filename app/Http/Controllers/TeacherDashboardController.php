<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
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

            $ontimeCount = 0;
            $lateCount = 0;
            $absentCount = 0;
            $holidayCount = 0;

            $studentsData = $students->map(function ($student) use ($dateLogs, $checkinEnd, $isHolidayOrWeekend, &$ontimeCount, &$lateCount, &$absentCount, &$holidayCount) {
                $logs = $dateLogs->get($student->id);
                $earliestLog = $logs ? $logs->first() : null;

                if ($earliestLog) {
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
                ];
            });

            $total = $students->count();
            $present = $ontimeCount + $lateCount;
            $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $stats['total'] = $total;
            $stats['ontime'] = $ontimeCount;
            $stats['late'] = $lateCount;
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

    /**
     * Kirim Pengingat WhatsApp 1-Klik ke Orang Tua Murid yang Belum Hadir
     */
    public function sendParentReminder(Request $request, Student $student)
    {
        // Proteksi jika hari ini akhir pekan / libur
        if (Setting::isHolidayOrWeekend()) {
            return back()->with('error', 'Hari ini libur akhir pekan, pengingat WhatsApp dinonaktifkan.');
        }

        $user = Auth::user();

        // Otorisasi: hanya wali kelas bersangkutan atau admin yang boleh mengirim
        if ($user->isGuru() && $user->school_class_id !== $student->school_class_id) {
            return back()->with('error', 'Anda hanya memiliki wewenang untuk siswa di kelas Anda.');
        }

        if (empty($student->parent_phone)) {
            return back()->with('error', "Nomor WhatsApp orang tua {$student->name} belum terdaftar.");
        }

        // Cek apakah siswa memang belum hadir
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $alreadyPresent = AttendanceLog::where('student_id', $student->id)
            ->whereDate('timestamp', $today)
            ->where('status_code', 0)
            ->exists();

        if ($alreadyPresent) {
            return back()->with('warning', "Siswa {$student->name} sudah tercatat hadir hari ini.");
        }

        $nowWib = Carbon::now('Asia/Jakarta');
        $timeStr = $nowWib->format('H:i');
        $dateStr = $nowWib->translatedFormat('l, d F Y');
        $className = $student->schoolClass->name ?? 'Kelas Siswa';
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');
        $teacherName = $user->name;

        $message = "📢 *PEMBERITAHUAN KEHADIRAN SISWA*\n\n"
            ."Yth. Bapak/Ibu Orang Tua / Wali dari ananda *{$student->name}*,\n\n"
            ."Menginformasikan bahwa hingga pukul *{$timeStr} WIB* ({$dateStr}), ananda *belum tercatat melakukan presensi fingerprint* di sekolah pada kelas *{$className}*.\n\n"
            ."Mohon konfirmasi kepada pihak sekolah / wali kelas apabila ananda berhalangan hadir (sakit/izin) atau sedang dalam perjalanan ke sekolah.\n\n"
            ."Terima kasih atas perhatian dan kerja samanya.\n\n"
            ."Salam hormat,\n"
            ."Wali Kelas: *{$teacherName}*\n"
            ."🏫 *{$schoolName}*";

        $success = WhatsAppService::sendRawMessage($student->parent_phone, $message, $student->name);

        if ($success) {
            return back()->with('success', "Pesan pengingat WhatsApp berhasil dikirim ke orang tua {$student->name} ({$student->parent_phone}).");
        } else {
            return back()->with('error', 'Gagal mengirim pesan via Gateway WhatsApp. Pastikan OpenWA sedang aktif.');
        }
    }

    /**
     * Kirim Pengingat Massal (Batch) 1-Klik ke Semua Ortu Siswa yang Belum Hadir
     */
    public function sendParentReminderAll(Request $request)
    {
        // Proteksi jika hari ini akhir pekan / libur
        if (Setting::isHolidayOrWeekend()) {
            return back()->with('error', 'Hari ini libur akhir pekan, pengingat WhatsApp dinonaktifkan.');
        }

        $user = Auth::user();
        $classId = $user->isGuru() ? $user->school_class_id : $request->input('school_class_id');

        if (! $classId) {
            return back()->with('error', 'Kelas belum dipilih atau belum ditugaskan.');
        }

        $targetClass = SchoolClass::find($classId);
        if (! $targetClass) {
            return back()->with('error', 'Data kelas tidak ditemukan.');
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $nowWib = Carbon::now('Asia/Jakarta');
        $timeStr = $nowWib->format('H:i');
        $dateStr = $nowWib->translatedFormat('l, d F Y');
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');
        $teacherName = $user->name;

        // Ambil siswa di kelas ini yang belum hadir hari ini
        $presentStudentIds = AttendanceLog::whereDate('timestamp', $today)
            ->where('status_code', 0)
            ->pluck('student_id')
            ->filter()
            ->unique();

        $absentStudents = Student::where('school_class_id', $classId)
            ->whereNotIn('id', $presentStudentIds)
            ->where(function ($q) {
                $q->where('privilege', 0)->orWhereNull('privilege');
            })
            ->whereNotNull('parent_phone')
            ->where('parent_phone', '!=', '')
            ->get();

        if ($absentStudents->isEmpty()) {
            return back()->with('warning', 'Tidak ada siswa yang belum hadir dengan nomor WhatsApp orang tua terdaftar.');
        }

        $sentCount = 0;
        foreach ($absentStudents as $student) {
            $message = "📢 *PEMBERITAHUAN KEHADIRAN SISWA*\n\n"
                ."Yth. Bapak/Ibu Orang Tua / Wali dari ananda *{$student->name}*,\n\n"
                ."Menginformasikan bahwa hingga pukul *{$timeStr} WIB* ({$dateStr}), ananda *belum tercatat melakukan presensi fingerprint* di sekolah pada kelas *{$targetClass->name}*.\n\n"
                ."Mohon konfirmasi kepada wali kelas apabila ananda berhalangan hadir (sakit/izin) atau sedang dalam perjalanan ke sekolah.\n\n"
                ."Terima kasih atas perhatian dan kerja samanya.\n\n"
                ."Salam hormat,\n"
                ."Wali Kelas: *{$teacherName}*\n"
                ."🏫 *{$schoolName}*";

            if (WhatsAppService::sendRawMessage($student->parent_phone, $message, $student->name)) {
                $sentCount++;
            }
        }

        return back()->with('success', "Berhasil mengirim pengingat WhatsApp ke {$sentCount} dari ".$absentStudents->count().' orang tua siswa yang belum hadir.');
    }
}
