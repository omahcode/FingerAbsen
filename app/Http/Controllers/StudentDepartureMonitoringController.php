<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentDepartureMonitoringController extends Controller
{
    /**
     * Tampilkan halaman monitoring status kepulangan siswa (Beda Direct Page)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $allClasses = SchoolClass::with('major')->orderBy('name')->get();
        $allMajors = Major::orderBy('name')->get();

        $selectedClassId = $request->query('class_id');
        $selectedMajorId = $request->query('major_id');
        $selectedStatus = $request->query('status', 'all'); // 'all', 'in_school' (masuk), 'departed' (pulang), 'not_present'
        $search = trim((string) $request->query('search', ''));

        // Jika user adalah Guru/Wali Kelas dan belum memilih filter kelas, default ke kelasnya
        if ($user && $user->isGuru() && empty($selectedClassId) && ! empty($user->school_class_id)) {
            $selectedClassId = (string) $user->school_class_id;
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $nowWib = Carbon::now('Asia/Jakarta');
        $checkoutStart = Setting::get('checkout_start', '14:00');
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');

        // Query siswa non-admin
        $query = Student::with(['schoolClass.major'])
            ->where(function ($q) {
                $q->where('privilege', 0)->orWhereNull('privilege');
            })
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%admin%']);

        if (! empty($selectedClassId)) {
            $query->where('school_class_id', $selectedClassId);
        }

        if (! empty($selectedMajorId)) {
            $query->whereHas('schoolClass', function ($q) use ($selectedMajorId) {
                $q->where('major_id', $selectedMajorId);
            });
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('device_user_id', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->get();

        // Ambil semua log kehadiran hari ini untuk siswa-siswa tersebut
        $allStudentIds = $students->pluck('id');
        $todayLogs = AttendanceLog::whereIn('student_id', $allStudentIds)
            ->whereDate('timestamp', $today)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->groupBy('student_id');

        // Olah data status tiap siswa
        $inSchoolCount = 0;
        $departedCount = 0;
        $notPresentCount = 0;

        $processedStudents = $students->map(function ($student) use ($todayLogs, &$inSchoolCount, &$departedCount, &$notPresentCount) {
            $logs = $todayLogs->get($student->id, collect());

            // Log masuk: log paling awal dengan status_code = 0
            $masukLog = $logs->where('status_code', 0)->first();

            // Log pulang: log paling akhir dengan status_code = 1, atau jika ada log setelah checkout_start
            $pulangLog = $logs->where('status_code', 1)->last();

            $jamMasuk = $masukLog ? Carbon::parse($masukLog->timestamp)->format('H:i') : null;
            $jamPulang = $pulangLog ? Carbon::parse($pulangLog->timestamp)->format('H:i') : null;

            // Tentukan status presensi kepulangan
            if ($pulangLog) {
                $statusCode = 'departed';
                $statusLabel = 'Sudah Pulang';
                $departedCount++;
            } elseif ($masukLog) {
                $statusCode = 'in_school';
                $statusLabel = 'Masuk (Di Sekolah)';
                $inSchoolCount++;
            } else {
                $statusCode = 'not_present';
                $statusLabel = 'Belum Hadir';
                $notPresentCount++;
            }

            // Durasi di sekolah jika ada jam masuk & jam pulang
            $durationString = null;
            if ($masukLog && $pulangLog) {
                $inTime = Carbon::parse($masukLog->timestamp);
                $outTime = Carbon::parse($pulangLog->timestamp);
                $diffHours = $inTime->diffInHours($outTime);
                $diffMins = $inTime->diffInMinutes($outTime) % 60;
                $durationString = "{$diffHours}j {$diffMins}m";
            } elseif ($masukLog) {
                $inTime = Carbon::parse($masukLog->timestamp);
                $now = Carbon::now('Asia/Jakarta');
                $diffHours = $inTime->diffInHours($now);
                $diffMins = $inTime->diffInMinutes($now) % 60;
                $durationString = "{$diffHours}j {$diffMins}m (aktif)";
            }

            return [
                'id' => $student->id,
                'name' => $student->name,
                'photo' => $student->photo_url,
                'device_user_id' => $student->device_user_id,
                'parent_phone' => $student->parent_phone,
                'class_name' => $student->schoolClass->name ?? '-',
                'major_name' => $student->schoolClass->major->name ?? '-',
                'status_code' => $statusCode,
                'status_label' => $statusLabel,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang,
                'duration' => $durationString,
                'masuk_timestamp' => $masukLog ? $masukLog->timestamp : null,
                'pulang_timestamp' => $pulangLog ? $pulangLog->timestamp : null,
            ];
        });

        // Terapkan filter tab status jika dipilih selain 'all'
        $filteredStudents = $processedStudents;
        if ($selectedStatus === 'in_school') {
            $filteredStudents = $processedStudents->where('status_code', 'in_school')->values();
        } elseif ($selectedStatus === 'departed') {
            $filteredStudents = $processedStudents->where('status_code', 'departed')->values();
        } elseif ($selectedStatus === 'not_present') {
            $filteredStudents = $processedStudents->where('status_code', 'not_present')->values();
        }

        $totalCount = $students->count();
        $attendedTodayCount = $inSchoolCount + $departedCount;
        $departedPercent = $attendedTodayCount > 0 ? round(($departedCount / $attendedTodayCount) * 100, 1) : 0;

        $stats = [
            'total' => $totalCount,
            'attended_today' => $attendedTodayCount,
            'in_school' => $inSchoolCount,
            'departed' => $departedCount,
            'not_present' => $notPresentCount,
            'departed_percentage' => $departedPercent,
            'checkout_start' => $checkoutStart,
            'school_name' => $schoolName,
            'today_formatted' => $nowWib->translatedFormat('l, d F Y'),
            'current_time' => $nowWib->format('H:i'),
        ];

        $maxLogId = AttendanceLog::max('id') ?? 0;

        return view('monitoring.kepulangan', compact(
            'filteredStudents',
            'allClasses',
            'allMajors',
            'stats',
            'selectedClassId',
            'selectedMajorId',
            'selectedStatus',
            'search',
            'maxLogId'
        ));
    }

    /**
     * Endpoint API JSON untuk polling realtime status kepulangan tanpa reload halaman
     */
    public function data(Request $request)
    {
        $selectedClassId = $request->query('class_id');
        $selectedMajorId = $request->query('major_id');
        $search = trim((string) $request->query('search', ''));

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $nowWib = Carbon::now('Asia/Jakarta');
        $checkoutStart = Setting::get('checkout_start', '14:00');

        $query = Student::with(['schoolClass.major'])
            ->where(function ($q) {
                $q->where('privilege', 0)->orWhereNull('privilege');
            })
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%admin%']);

        if (! empty($selectedClassId)) {
            $query->where('school_class_id', $selectedClassId);
        }

        if (! empty($selectedMajorId)) {
            $query->whereHas('schoolClass', function ($q) use ($selectedMajorId) {
                $q->where('major_id', $selectedMajorId);
            });
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('device_user_id', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->get();
        $allStudentIds = $students->pluck('id');

        $todayLogs = AttendanceLog::whereIn('student_id', $allStudentIds)
            ->whereDate('timestamp', $today)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->groupBy('student_id');

        $inSchoolCount = 0;
        $departedCount = 0;
        $notPresentCount = 0;

        $processedStudents = $students->map(function ($student) use ($todayLogs, &$inSchoolCount, &$departedCount, &$notPresentCount) {
            $logs = $todayLogs->get($student->id, collect());

            $masukLog = $logs->where('status_code', 0)->first();
            $pulangLog = $logs->where('status_code', 1)->last();

            $jamMasuk = $masukLog ? Carbon::parse($masukLog->timestamp)->format('H:i') : null;
            $jamPulang = $pulangLog ? Carbon::parse($pulangLog->timestamp)->format('H:i') : null;

            if ($pulangLog) {
                $statusCode = 'departed';
                $statusLabel = 'Sudah Pulang';
                $departedCount++;
            } elseif ($masukLog) {
                $statusCode = 'in_school';
                $statusLabel = 'Masuk (Di Sekolah)';
                $inSchoolCount++;
            } else {
                $statusCode = 'not_present';
                $statusLabel = 'Belum Hadir';
                $notPresentCount++;
            }

            $durationString = null;
            if ($masukLog && $pulangLog) {
                $inTime = Carbon::parse($masukLog->timestamp);
                $outTime = Carbon::parse($pulangLog->timestamp);
                $diffHours = $inTime->diffInHours($outTime);
                $diffMins = $inTime->diffInMinutes($outTime) % 60;
                $durationString = "{$diffHours}j {$diffMins}m";
            } elseif ($masukLog) {
                $inTime = Carbon::parse($masukLog->timestamp);
                $now = Carbon::now('Asia/Jakarta');
                $diffHours = $inTime->diffInHours($now);
                $diffMins = $inTime->diffInMinutes($now) % 60;
                $durationString = "{$diffHours}j {$diffMins}m (aktif)";
            }

            return [
                'id' => $student->id,
                'name' => $student->name,
                'photo' => $student->photo_url,
                'device_user_id' => $student->device_user_id,
                'parent_phone' => $student->parent_phone,
                'class_name' => $student->schoolClass->name ?? '-',
                'major_name' => $student->schoolClass->major->name ?? '-',
                'status_code' => $statusCode,
                'status_label' => $statusLabel,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang,
                'duration' => $durationString,
            ];
        });

        $totalCount = $students->count();
        $attendedTodayCount = $inSchoolCount + $departedCount;
        $departedPercent = $attendedTodayCount > 0 ? round(($departedCount / $attendedTodayCount) * 100, 1) : 0;

        return response()->json([
            'status' => 'success',
            'max_id' => AttendanceLog::max('id') ?? 0,
            'time' => $nowWib->format('H:i:s'),
            'stats' => [
                'total' => $totalCount,
                'attended_today' => $attendedTodayCount,
                'in_school' => $inSchoolCount,
                'departed' => $departedCount,
                'not_present' => $notPresentCount,
                'departed_percentage' => $departedPercent,
            ],
            'students' => $processedStudents->values(),
        ]);
    }

    /**
     * Kirim pesan info kepulangan langsung ke orang tua via WhatsApp
     */
    public function notifyParent(Request $request, Student $student)
    {
        if (empty($student->parent_phone)) {
            return back()->with('error', "Nomor WhatsApp orang tua {$student->name} belum terdaftar.");
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $nowWib = Carbon::now('Asia/Jakarta');
        $timeStr = $nowWib->format('H:i');
        $dateStr = $nowWib->translatedFormat('l, d F Y');
        $className = $student->schoolClass->name ?? '-';
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');

        $logs = AttendanceLog::where('student_id', $student->id)
            ->whereDate('timestamp', $today)
            ->orderBy('timestamp', 'asc')
            ->get();

        $masukLog = $logs->where('status_code', 0)->first();
        $pulangLog = $logs->where('status_code', 1)->last();

        if ($pulangLog) {
            $jamPulang = Carbon::parse($pulangLog->timestamp)->format('H:i');
            $message = "📢 *INFORMASI KEPULANGAN SISWA*\n\n"
                ."Yth. Orang Tua / Wali dari ananda *{$student->name}*,\n\n"
                ."Menginformasikan bahwa ananda telah melakukan presensi *PULANG* pada pukul *{$jamPulang} WIB* ({$dateStr}) di kelas *{$className}*.\n\n"
                ."Ananda saat ini dalam perjalanan pulang ke rumah. Mohon pantau kepulangan ananda dengan aman.\n\n"
                ."🏢 *{$schoolName}*\n"
                .'_Pesan otomatis sistem presensi fingerprint._';
        } elseif ($masukLog) {
            $jamMasuk = Carbon::parse($masukLog->timestamp)->format('H:i');
            $message = "📢 *STATUS KEBERADAAN SISWA DI SEKOLAH*\n\n"
                ."Yth. Orang Tua / Wali dari ananda *{$student->name}*,\n\n"
                ."Menginformasikan bahwa ananda *MASIH BERADA DI SEKOLAH* (belum melakukan presensi kepulangan).\n"
                ."Ananda tercatat hadir sejak pukul *{$jamMasuk} WIB* ({$dateStr}) pada kelas *{$className}*.\n\n"
                ."🏢 *{$schoolName}*\n"
                .'_Pesan otomatis sistem presensi fingerprint._';
        } else {
            $message = "📢 *PEMBERITAHUAN PRESENSI SISWA*\n\n"
                ."Yth. Orang Tua / Wali dari ananda *{$student->name}*,\n\n"
                ."Menginformasikan bahwa hingga pukul *{$timeStr} WIB* ({$dateStr}), ananda *belum tercatat melakukan presensi* di sekolah pada kelas *{$className}*.\n\n"
                ."🏢 *{$schoolName}*";
        }

        $success = WhatsAppService::sendRawMessage($student->parent_phone, $message, $student->name);

        if ($success) {
            return back()->with('success', "Notifikasi WhatsApp berhasil dikirim ke orang tua {$student->name} ({$student->parent_phone}).");
        }

        return back()->with('error', 'Gagal mengirim notifikasi via WhatsApp Gateway. Pastikan OpenWA sedang aktif.');
    }
}
