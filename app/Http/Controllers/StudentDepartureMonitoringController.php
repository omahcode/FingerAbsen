<?php

namespace App\Http\Controllers;

use App\Events\AttendanceCreated;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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
                $durationString = $this->formatDurationString(
                    Carbon::parse($masukLog->timestamp),
                    Carbon::parse($pulangLog->timestamp),
                    false
                );
            } elseif ($masukLog) {
                $durationString = $this->formatDurationString(
                    Carbon::parse($masukLog->timestamp),
                    Carbon::now('Asia/Jakarta'),
                    true
                );
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
            'today' => $today,
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
            'maxLogId',
            'today'
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
                $durationString = $this->formatDurationString(
                    Carbon::parse($masukLog->timestamp),
                    Carbon::parse($pulangLog->timestamp),
                    false
                );
            } elseif ($masukLog) {
                $durationString = $this->formatDurationString(
                    Carbon::parse($masukLog->timestamp),
                    Carbon::now('Asia/Jakarta'),
                    true
                );
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
            'date' => $today,
            'date_formatted' => $nowWib->translatedFormat('l, d F Y'),
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

    /**
     * Ubah status kepulangan / presensi siswa secara manual
     */
    public function updateStatus(Request $request, Student $student)
    {
        $request->validate([
            'action' => 'required|in:departed,in_school,reset',
            'time' => 'nullable|string',
            'notes' => 'nullable|string|max:255',
            'notify_parent' => 'nullable',
        ]);

        $action = $request->input('action');
        $timeInput = $request->input('time'); // format H:i misal "14:15"
        $notes = $request->input('notes');
        $notifyParent = $request->boolean('notify_parent', false);

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $nowWib = Carbon::now('Asia/Jakarta');

        // Validasi dan parse target timestamp
        if (! empty($timeInput) && preg_match('/^\d{2}:\d{2}$/', $timeInput)) {
            $timestamp = "{$today} {$timeInput}:00";
            $timeStr = $timeInput;
        } else {
            $timestamp = $nowWib->format('Y-m-d H:i:s');
            $timeStr = $nowWib->format('H:i');
        }

        $device = Device::first();
        if (! $device) {
            $device = Device::create([
                'name' => 'Mesin Fingerprint Utama',
                'ip_address' => '127.0.0.1',
                'port' => 4370,
            ]);
        }
        $deviceId = $device->id;

        $deviceUserId = (string) $student->device_user_id;

        $todayLogs = AttendanceLog::where('student_id', $student->id)
            ->whereDate('timestamp', $today)
            ->get();

        $masukLog = $todayLogs->where('status_code', 0)->first();
        $pulangLogs = $todayLogs->where('status_code', 1);

        if ($action === 'departed') {
            // 1. Jika belum ada log masuk pagi sama sekali, buatkan log masuk default
            if (! $masukLog) {
                $checkinStart = Setting::get('checkin_start', '06:30');
                $inTimestamp = "{$today} {$checkinStart}:00";

                $existingIn = AttendanceLog::withTrashed()
                    ->where('device_id', $deviceId)
                    ->where('device_user_id', $deviceUserId)
                    ->where('timestamp', $inTimestamp)
                    ->first();

                if ($existingIn) {
                    if ($existingIn->trashed()) {
                        $existingIn->restore();
                    }
                    $existingIn->update([
                        'student_id' => $student->id,
                        'status_code' => 0,
                    ]);
                    $masukLog = $existingIn;
                } else {
                    $masukLog = AttendanceLog::create([
                        'device_id' => $deviceId,
                        'device_user_id' => $deviceUserId,
                        'student_id' => $student->id,
                        'timestamp' => $inTimestamp,
                        'status_code' => 0,
                    ]);
                }
            }

            // 2. Jika timestamp pulang sama persis dengan timestamp masuk, tambahkan 1 detik agar tidak bentrok
            if ($masukLog && $timestamp === $masukLog->timestamp) {
                $timestamp = Carbon::parse($timestamp)->addSecond()->format('Y-m-d H:i:s');
            }

            // 3. Cek apakah ada record yang cocok di timestamp ini (termasuk trashed)
            $existingPulang = AttendanceLog::withTrashed()
                ->where('device_id', $deviceId)
                ->where('device_user_id', $deviceUserId)
                ->where('timestamp', $timestamp)
                ->first();

            if ($existingPulang) {
                if ($existingPulang->trashed()) {
                    $existingPulang->restore();
                }
                $existingPulang->update([
                    'student_id' => $student->id,
                    'status_code' => 1,
                ]);
                $pulangLog = $existingPulang;
            } else {
                $pulangLog = $pulangLogs->last();
                if ($pulangLog) {
                    $pulangLog->update([
                        'timestamp' => $timestamp,
                        'device_id' => $deviceId,
                    ]);
                } else {
                    try {
                        $pulangLog = AttendanceLog::create([
                            'device_id' => $deviceId,
                            'device_user_id' => $deviceUserId,
                            'student_id' => $student->id,
                            'timestamp' => $timestamp,
                            'status_code' => 1,
                        ]);
                    } catch (QueryException $e) {
                        // Fallback jika terjadi tabrakan constraint 1062
                        $pulangLog = AttendanceLog::withTrashed()
                            ->where('device_id', $deviceId)
                            ->where('device_user_id', $deviceUserId)
                            ->where('timestamp', $timestamp)
                            ->first();

                        if ($pulangLog) {
                            if ($pulangLog->trashed()) {
                                $pulangLog->restore();
                            }
                            $pulangLog->update([
                                'student_id' => $student->id,
                                'status_code' => 1,
                            ]);
                        } else {
                            throw $e;
                        }
                    }
                }
            }

            // Siarkan realtime WebSocket event
            try {
                broadcast(new AttendanceCreated($pulangLog));
            } catch (\Throwable $e) {
                Log::warning('Broadcast AttendanceCreated failed in updateStatus: '.$e->getMessage());
            }

            // Kirim notifikasi WhatsApp ke orang tua jika diminta
            if ($notifyParent && ! empty($student->parent_phone)) {
                $reasonText = $notes ? " (Keterangan: {$notes})" : '';
                $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');
                $message = "📢 *INFORMASI KEPULANGAN SISWA (MANUAL)*\n\n"
                    ."Yth. Orang Tua / Wali dari ananda *{$student->name}*,\n\n"
                    ."Menginformasikan bahwa status kepulangan ananda telah dicatat secara resmi oleh pihak sekolah:\n"
                    ."• Status: *SUDAH PULANG*\n"
                    ."• Waktu Pulang: *{$timeStr} WIB*{$reasonText}\n\n"
                    ."Ananda diperkenankan meninggalkan area sekolah. Mohon pantau kepulangan ananda dengan aman.\n\n"
                    ."🏢 *{$schoolName}*";

                WhatsAppService::sendRawMessage($student->parent_phone, $message, $student->name);
            }

            $message = "Status {$student->name} berhasil diubah menjadi SUDAH PULANG ({$timeStr} WIB).";

        } elseif ($action === 'in_school') {
            // Batalkan status pulang: Hapus log pulang hari ini secara permanen
            AttendanceLog::withTrashed()
                ->where('student_id', $student->id)
                ->whereDate('timestamp', $today)
                ->where('status_code', 1)
                ->forceDelete();

            // Jika belum ada log masuk pagi, buatkan log masuk
            if (! $masukLog) {
                $existingIn = AttendanceLog::withTrashed()
                    ->where('device_id', $deviceId)
                    ->where('device_user_id', $deviceUserId)
                    ->where('timestamp', $timestamp)
                    ->first();

                if ($existingIn) {
                    if ($existingIn->trashed()) {
                        $existingIn->restore();
                    }
                    $existingIn->update([
                        'student_id' => $student->id,
                        'status_code' => 0,
                    ]);
                    $logMasuk = $existingIn;
                } else {
                    $logMasuk = AttendanceLog::create([
                        'device_id' => $deviceId,
                        'device_user_id' => $deviceUserId,
                        'student_id' => $student->id,
                        'timestamp' => $timestamp,
                        'status_code' => 0,
                    ]);
                }

                try {
                    broadcast(new AttendanceCreated($logMasuk));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast AttendanceCreated failed in updateStatus: '.$e->getMessage());
                }
            }

            $message = "Status {$student->name} berhasil diubah menjadi MASUK (DI SEKOLAH).";

        } elseif ($action === 'reset') {
            // Hapus semua log kehadiran hari ini untuk siswa ini secara permanen
            AttendanceLog::withTrashed()
                ->where('student_id', $student->id)
                ->whereDate('timestamp', $today)
                ->forceDelete();

            $message = "Status presensi hari ini untuk {$student->name} berhasil direset ke Belum Hadir.";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'action' => $action,
                'time' => $timeStr,
                'student_id' => $student->id,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Format durasi waktu menjadi jam dan menit singkat dan bulat (tanpa desimal panjang)
     */
    private function formatDurationString(Carbon $start, Carbon $end, bool $isActive = false): string
    {
        $totalMinutes = (int) abs($start->diffInMinutes($end));
        $hours = intdiv($totalMinutes, 60);
        $mins = $totalMinutes % 60;

        if ($totalMinutes < 1) {
            $formatted = '< 1 menit';
        } elseif ($hours > 0 && $mins > 0) {
            $formatted = "{$hours} jam {$mins} menit";
        } elseif ($hours > 0) {
            $formatted = "{$hours} jam";
        } else {
            $formatted = "{$mins} menit";
        }

        return $isActive ? "{$formatted} (aktif)" : $formatted;
    }
}
