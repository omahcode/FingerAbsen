<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\ParentPushSubscription;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPermission;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentDashboardController extends Controller
{
    /**
     * Tampilkan Portal Orang Tua (Form Login Verifikasi atau Dashboard Siswa).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $studentId = session('parent_student_id');

        if (! $studentId) {
            $classes = SchoolClass::orderBy('name')->get();

            return view('parent.login', compact('classes'));
        }

        $student = Student::with(['schoolClass.major'])->find($studentId);

        if (! $student) {
            session()->forget('parent_student_id');

            return redirect()->route('parent.index')->withErrors(['nis' => 'Data siswa tidak ditemukan.']);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $currentTimeWib = Carbon::now('Asia/Jakarta')->format('H:i');
        $checkinStart = Setting::get('checkin_start', '06:00');
        $checkinEnd = Setting::get('checkin_end', '07:15');
        $checkoutStart = Setting::get('checkout_start', '14:00');
        $schoolName = Setting::get('school_name', 'Sekolah Menengah Kejuruan');
        $isHolidayOrWeekend = Setting::isHolidayOrWeekend($today);

        // Data Presensi Hari Ini
        $todayCheckin = AttendanceLog::where('student_id', $student->id)
            ->whereDate('timestamp', $today)
            ->where('status_code', 0)
            ->orderBy('timestamp', 'asc')
            ->first();

        $todayCheckout = AttendanceLog::where('student_id', $student->id)
            ->whereDate('timestamp', $today)
            ->where('status_code', 1)
            ->orderBy('timestamp', 'desc')
            ->first();

        $todayPermission = StudentPermission::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        // Hitung Status Kedatangan
        if ($todayPermission) {
            $statusKedatangan = 'permission';
            $statusKedatanganLabel = $todayPermission->type;
            $statusKedatanganTime = 'Disampaikan Ortu';
        } elseif ($todayCheckin) {
            $checkinTime = Carbon::parse($todayCheckin->timestamp)->format('H:i');
            $isOntime = $checkinTime <= $checkinEnd;
            $statusKedatangan = $isOntime ? 'ontime' : 'late';
            $statusKedatanganLabel = $isOntime ? 'Hadir Tepat Waktu' : 'Hadir Terlambat';
            $statusKedatanganTime = $checkinTime.' WIB';
        } elseif ($isHolidayOrWeekend) {
            $statusKedatangan = 'holiday';
            $statusKedatanganLabel = 'Libur Akhir Pekan';
            $statusKedatanganTime = '-';
        } else {
            $statusKedatangan = 'absent';
            $statusKedatanganLabel = 'Belum Hadir';
            $statusKedatanganTime = '-';
        }

        // Hitung Status Kepulangan & Durasi
        $studyDuration = '-';
        if ($todayCheckout) {
            $checkoutTime = Carbon::parse($todayCheckout->timestamp)->format('H:i');
            $statusKepulangan = 'departed';
            $statusKepulanganLabel = 'Sudah Pulang Sekolah';
            $statusKepulanganTime = $checkoutTime.' WIB';

            if ($todayCheckin) {
                $start = Carbon::parse($todayCheckin->timestamp);
                $end = Carbon::parse($todayCheckout->timestamp);
                $diffMinutes = $start->diffInMinutes($end);
                $hours = floor($diffMinutes / 60);
                $minutes = $diffMinutes % 60;
                $studyDuration = ($hours > 0 ? "{$hours} Jam " : '')."{$minutes} Menit";
            }
        } elseif ($todayCheckin) {
            $statusKepulangan = 'in_school';
            $statusKepulanganLabel = 'Sedang Berada di Sekolah';
            $statusKepulanganTime = 'Mengikuti Pembelajaran';

            $start = Carbon::parse($todayCheckin->timestamp);
            $now = Carbon::now('Asia/Jakarta');
            $diffMinutes = $start->diffInMinutes($now);
            $hours = floor($diffMinutes / 60);
            $minutes = $diffMinutes % 60;
            $studyDuration = ($hours > 0 ? "{$hours} Jam " : '')."{$minutes} Menit (Berjalan)";
        } else {
            $statusKepulangan = 'not_arrived';
            $statusKepulanganLabel = 'Belum Hadir di Sekolah';
            $statusKepulanganTime = '-';
        }

        // Streak Kehadiran
        $streak = $student->calculateConsecutiveStreak($today);

        // Pemilihan Bulan & Tahun untuk Rekap Presensi Bulanan (Default: Bulan Ini)
        $selectedMonth = $request->query('month', Carbon::today('Asia/Jakarta')->format('Y-m'));
        try {
            $monthCarbon = Carbon::createFromFormat('Y-m', $selectedMonth, 'Asia/Jakarta')->startOfMonth();
        } catch (\Throwable $e) {
            $monthCarbon = Carbon::today('Asia/Jakarta')->startOfMonth();
            $selectedMonth = $monthCarbon->format('Y-m');
        }

        $prevMonth = $monthCarbon->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthCarbon->copy()->addMonth()->format('Y-m');
        $monthName = $monthCarbon->translatedFormat('F Y');
        $daysInMonth = $monthCarbon->daysInMonth;
        $startOfMonth = $monthCarbon->copy()->startOfMonth();
        $endOfMonth = $monthCarbon->copy()->endOfMonth();
        $startDayOfWeek = $startOfMonth->dayOfWeekIso; // 1 (Senin) .. 7 (Minggu)

        // Ambil Log & Izin untuk Bulan yang Dipilih
        $monthLogs = AttendanceLog::where('student_id', $student->id)
            ->whereBetween('timestamp', [$startOfMonth->toDateTimeString(), $endOfMonth->copy()->endOfDay()->toDateTimeString()])
            ->orderBy('timestamp', 'asc')
            ->get()
            ->groupBy(fn ($item) => Carbon::parse($item->timestamp)->toDateString());

        $monthPermissions = StudentPermission::where('student_id', $student->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->date)->toDateString());

        $monthlyDays = [];
        $statsHadir = 0;
        $statsTerlambat = 0;
        $statsIzin = 0;
        $statsAlpha = 0;
        $todayDateStr = Carbon::today('Asia/Jakarta')->toDateString();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDay = $monthCarbon->copy()->day($day);
            $dStr = $currentDay->toDateString();
            $isFuture = $dStr > $todayDateStr;
            $isToday = $dStr === $todayDateStr;
            $isHol = Setting::isHolidayOrWeekend($dStr);

            $dLog = $monthLogs->get($dStr);
            $dPerm = $monthPermissions->get($dStr);

            $checkin = $dLog ? $dLog->where('status_code', 0)->first() : null;
            $checkout = $dLog ? $dLog->where('status_code', 1)->last() : null;

            $checkinTime = $checkin ? Carbon::parse($checkin->timestamp)->format('H:i') : null;
            $checkoutTime = $checkout ? Carbon::parse($checkout->timestamp)->format('H:i') : null;

            $status = 'upcoming';
            $statusLabel = 'Mendatang';
            $notes = null;

            if ($isFuture) {
                $status = $isHol ? 'holiday' : 'upcoming';
                $statusLabel = $isHol ? 'Libur' : '-';
            } elseif ($dPerm) {
                $status = 'permission';
                $statusLabel = $dPerm->type;
                $notes = $dPerm->notes ?: 'Disampaikan Ortu';
                $statsIzin++;
            } elseif ($checkin) {
                $isOntime = $checkinTime <= $checkinEnd;
                $status = $isOntime ? 'ontime' : 'late';
                $statusLabel = $isOntime ? 'Tepat Waktu' : 'Terlambat';
                if ($isOntime) {
                    $statsHadir++;
                } else {
                    $statsTerlambat++;
                }
            } elseif ($isHol) {
                $status = 'holiday';
                $statusLabel = 'Libur Akhir Pekan';
            } else {
                $status = 'absent';
                $statusLabel = 'Alpha';
                $statsAlpha++;
            }

            $durationStr = '-';
            if ($checkin && $checkout) {
                $diff = Carbon::parse($checkin->timestamp)->diffInMinutes(Carbon::parse($checkout->timestamp));
                $h = floor($diff / 60);
                $m = $diff % 60;
                $durationStr = ($h > 0 ? "{$h}j " : '')."{$m}m";
            }

            $monthlyDays[] = [
                'day' => $day,
                'date' => $dStr,
                'day_name' => $currentDay->translatedFormat('l'),
                'day_short' => $currentDay->translatedFormat('D'),
                'date_formatted' => $currentDay->translatedFormat('d M Y'),
                'status' => $status,
                'label' => $statusLabel,
                'checkin_time' => $checkinTime ? $checkinTime.' WIB' : '-',
                'checkout_time' => $checkoutTime ? $checkoutTime.' WIB' : '-',
                'duration' => $durationStr,
                'notes' => $notes,
                'is_today' => $isToday,
                'is_future' => $isFuture,
                'is_holiday' => $isHol,
                'day_of_week' => $currentDay->dayOfWeekIso,
            ];
        }

        // Recent history untuk kompatibilitas
        $recentHistory = array_values(array_reverse(array_filter($monthlyDays, fn ($item) => ! $item['is_future'] && ! $item['is_holiday'])));

        $vapidPublicKey = config('webpush.vapid.public_key', '');

        return view('parent.dashboard', compact(
            'student',
            'today',
            'currentTimeWib',
            'checkinStart',
            'checkinEnd',
            'checkoutStart',
            'schoolName',
            'isHolidayOrWeekend',
            'todayCheckin',
            'todayCheckout',
            'todayPermission',
            'statusKedatangan',
            'statusKedatanganLabel',
            'statusKedatanganTime',
            'statusKepulangan',
            'statusKepulanganLabel',
            'statusKepulanganTime',
            'studyDuration',
            'streak',
            'recentHistory',
            'monthlyDays',
            'selectedMonth',
            'prevMonth',
            'nextMonth',
            'monthName',
            'startDayOfWeek',
            'statsHadir',
            'statsTerlambat',
            'statsIzin',
            'statsAlpha',
            'vapidPublicKey'
        ));
    }

    /**
     * Memvalidasi kombinasi NIS, Nama Lengkap, dan Kelas siswa untuk autentikasi orang tua.
     */
    public function selectStudent(Request $request): RedirectResponse
    {
        $request->validate([
            'nis' => 'required|string',
            'name' => 'required|string',
            'school_class_id' => 'required|exists:school_classes,id',
        ], [
            'nis.required' => 'Nomor Induk Siswa (NIS) wajib diisi.',
            'name.required' => 'Nama lengkap siswa wajib diisi.',
            'school_class_id.required' => 'Pilih kelas siswa.',
            'school_class_id.exists' => 'Kelas yang dipilih tidak valid.',
        ]);

        $nisInput = trim($request->input('nis'));
        $nameInput = strtolower(trim($request->input('name')));
        $classIdInput = (int) $request->input('school_class_id');

        // Pencarian berdasarkan NIS (atau device_user_id sebagai fallback)
        $studentWithNis = Student::where(function ($q) use ($nisInput) {
            $q->where('nis', $nisInput)
                ->orWhere('device_user_id', $nisInput);
        })->first();

        if (! $studentWithNis) {
            return back()->withInput()->withErrors(['nis' => 'Nomor Induk Siswa (NIS) tidak ditemukan dalam basis data sekolah.']);
        }

        if (strtolower(trim($studentWithNis->name)) !== $nameInput) {
            return back()->withInput()->withErrors(['name' => 'Nama lengkap siswa tidak cocok dengan data NIS yang dimasukkan.']);
        }

        if ((int) $studentWithNis->school_class_id !== $classIdInput) {
            return back()->withInput()->withErrors(['school_class_id' => 'Pilihan kelas tidak sesuai dengan kelas siswa terdaftar.']);
        }

        // Login Berhasil
        session(['parent_student_id' => $studentWithNis->id]);

        return redirect()->route('parent.index')->with('success', "Berhasil masuk. Memantau ananda {$studentWithNis->name}.");
    }

    /**
     * Mengakhiri sesi orang tua.
     */
    public function clear(): RedirectResponse
    {
        session()->forget('parent_student_id');

        return redirect()->route('parent.index')->with('success', 'Sesi portal orang tua berhasil diakhiri.');
    }

    /**
     * Endpoint data real-time JSON untuk polling pembaruan status anak.
     */
    public function data(Request $request): JsonResponse
    {
        $studentId = session('parent_student_id');
        if (! $studentId) {
            return response()->json(['error' => 'Sesi portal belum aktif.'], 401);
        }

        $student = Student::with('schoolClass')->find($studentId);
        if (! $student) {
            return response()->json(['error' => 'Siswa tidak ditemukan.'], 404);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $checkinEnd = Setting::get('checkin_end', '07:15');

        $todayCheckin = AttendanceLog::where('student_id', $student->id)
            ->whereDate('timestamp', $today)
            ->where('status_code', 0)
            ->orderBy('timestamp', 'asc')
            ->first();

        $todayCheckout = AttendanceLog::where('student_id', $student->id)
            ->whereDate('timestamp', $today)
            ->where('status_code', 1)
            ->orderBy('timestamp', 'desc')
            ->first();

        $todayPermission = StudentPermission::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        return response()->json([
            'status' => 'success',
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'class' => $student->schoolClass?->name,
                'streak' => $student->calculateConsecutiveStreak($today),
            ],
            'attendance' => [
                'checkin' => $todayCheckin ? [
                    'time' => Carbon::parse($todayCheckin->timestamp)->format('H:i'),
                    'is_ontime' => Carbon::parse($todayCheckin->timestamp)->format('H:i') <= $checkinEnd,
                ] : null,
                'checkout' => $todayCheckout ? [
                    'time' => Carbon::parse($todayCheckout->timestamp)->format('H:i'),
                ] : null,
                'permission' => $todayPermission ? [
                    'type' => $todayPermission->type,
                    'notes' => $todayPermission->notes,
                    'status' => $todayPermission->status,
                ] : null,
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Menyimpan formulir permohonan izin atau sakit anak ke database.
     */
    public function storePermission(Request $request): JsonResponse|RedirectResponse
    {
        $studentId = session('parent_student_id');
        if (! $studentId) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Sesi tidak sah.'], 401);
            }

            return redirect()->route('parent.index')->withErrors(['auth' => 'Sesi telah berakhir, silakan verifikasi ulang.']);
        }

        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:Sakit,Izin,Izin Ada Keperluan Keluarga,Izin Lainnya',
            'notes' => 'nullable|string|max:1000',
        ], [
            'date.required' => 'Tanggal izin wajib ditentukan.',
            'type.required' => 'Pilih keterangan izin atau sakit.',
            'type.in' => 'Jenis izin tidak valid.',
        ]);

        $permission = StudentPermission::updateOrCreate(
            [
                'student_id' => $studentId,
                'date' => $request->date,
            ],
            [
                'type' => $request->type,
                'notes' => $request->notes,
                'status' => 'disetujui',
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Permohonan keterangan '.$permission->type.' berhasil disimpan dan diverifikasi.',
                'data' => $permission,
            ]);
        }

        return redirect()->route('parent.index')->with('success', 'Keterangan '.$permission->type.' resmi disetujui.');
    }

    /**
     * Mendaftarkan langganan Web Push browser orang tua ke database.
     */
    public function subscribePush(Request $request): JsonResponse
    {
        $studentId = session('parent_student_id');
        if (! $studentId) {
            return response()->json(['error' => 'Sesi tidak sah.'], 401);
        }

        $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $endpoint = $request->input('endpoint');
        $endpointHash = hash('sha256', $endpoint);

        $subscription = ParentPushSubscription::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'student_id' => $studentId,
                'endpoint' => $endpoint,
                'p256dh' => $request->input('keys.p256dh'),
                'auth' => $request->input('keys.auth'),
                'device_name' => $request->input('device_name', $request->userAgent()),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi layar berhasil diaktifkan pada perangkat ini.',
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Membatalkan langganan Web Push perangkat.
     */
    public function unsubscribePush(Request $request): JsonResponse
    {
        $studentId = session('parent_student_id');
        if (! $studentId) {
            return response()->json(['error' => 'Sesi tidak sah.'], 401);
        }

        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $endpointHash = hash('sha256', $request->input('endpoint'));
        ParentPushSubscription::where('endpoint_hash', $endpointHash)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Langganan notifikasi push berhasil dinonaktifkan.',
        ]);
    }

    /**
     * Mengirimkan notifikasi push pengujian ke perangkat orang tua saat ini.
     */
    public function testPush(Request $request, WebPushService $webPushService): JsonResponse
    {
        $studentId = session('parent_student_id');
        if (! $studentId) {
            return response()->json(['error' => 'Sesi tidak sah.'], 401);
        }

        $student = Student::find($studentId);
        if (! $student) {
            return response()->json(['error' => 'Siswa tidak ditemukan.'], 404);
        }

        $sentCount = $webPushService->sendToStudent(
            $student,
            'Tes Notifikasi Layar',
            'Koneksi notifikasi HP orang tua untuk ananda '.$student->name.' berhasil terhubung dengan sistem presensi.',
            ['type' => 'test']
        );

        if ($sentCount > 0) {
            return response()->json([
                'status' => 'success',
                'message' => "Notifikasi uji coba berhasil dikirim ke {$sentCount} perangkat terdaftar.",
            ]);
        }

        return response()->json([
            'status' => 'warning',
            'message' => 'Belum ada perangkat terdaftar yang aktif untuk menerima push notification.',
        ], 422);
    }
}
