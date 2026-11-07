<?php

namespace App\Http\Controllers;

use App\Events\AttendanceCreated;
use App\Jobs\SendWhatsAppAttendanceJob;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLog::with(['student.schoolClass', 'device'])
            ->whereNull('teacher_id')
            ->orderBy('timestamp', 'desc');

        $filter = $request->query('filter', 'all');

        if ($filter === 'registered') {
            $query->whereNotNull('student_id');
        } elseif ($filter === 'unregistered') {
            $query->whereNull('student_id');
        }

        $logs = $query->paginate(50)->appends(['filter' => $filter]);
        $devices = Device::all();
        $isStrict = env('STRICT_ATTENDANCE', true);
        $maxLogId = AttendanceLog::whereNull('teacher_id')->max('id') ?? 0;

        return view('attendance.index', compact('logs', 'devices', 'filter', 'isStrict', 'maxLogId'));
    }

    public function latest(Request $request)
    {
        $sinceId = (int) $request->query('since_id', 0);
        $filter = $request->query('filter', 'all');

        $query = AttendanceLog::with(['student.schoolClass', 'device'])
            ->where('id', '>', $sinceId)
            ->whereNull('teacher_id')
            ->whereNotNull('student_id')
            ->orderBy('id', 'asc');

        if ($filter === 'registered') {
            $query->whereNotNull('student_id');
        } elseif ($filter === 'unregistered') {
            $query->whereNull('student_id');
        }

        $logs = $query->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'max_id' => AttendanceLog::whereNull('teacher_id')->max('id') ?? $sinceId,
            'logs' => $logs,
        ]);
    }

    public function toggleStrict()
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $current = env('STRICT_ATTENDANCE', true) ? 'true' : 'false';
            $new = $current === 'true' ? 'false' : 'true';

            $content = file_get_contents($path);

            if (strpos($content, 'STRICT_ATTENDANCE=') !== false) {
                file_put_contents($path, preg_replace('/^STRICT_ATTENDANCE=.*$/m', 'STRICT_ATTENDANCE='.$new, $content));
            } else {
                file_put_contents($path, $content."\nSTRICT_ATTENDANCE=".$new);
            }
        }

        return redirect()->back()->with('success', 'Mode Anti-Duplikat berhasil diubah.');
    }

    public function sync(Request $request)
    {
        $request->validate(['device_id' => 'required']);
        $device = Device::findOrFail($request->device_id);
        $isStrict = env('STRICT_ATTENDANCE', true);

        try {
            $response = Http::timeout(30)->post('http://127.0.0.1:5000/api/get_attendance', [
                'ip_address' => $device->ip_address,
                'port' => $device->port,
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $attendances = $response->json('attendance');
                $newCount = 0;

                foreach ($attendances as $att) {
                    if (! $att['user_id']) {
                        continue;
                    }

                    $student = Student::where('device_user_id', (string) $att['user_id'])->first();
                    $statusCode = $att['status'] ?? 0;
                    $timestamp = $att['timestamp'];

                    if ($isStrict && $student) {
                        // Strict Mode: 1 Murid hanya boleh 1x Masuk (0) dan 1x Pulang (1) per HARI di mesin manapun
                        $date = substr($timestamp, 0, 10); // Ambil YYYY-MM-DD

                        $existingLog = AttendanceLog::withTrashed()
                            ->where('student_id', $student->id)
                            ->where('status_code', $statusCode)
                            ->whereDate('timestamp', $date)
                            ->first();

                        if (! $existingLog) {
                            AttendanceLog::create([
                                'device_id' => $device->id,
                                'device_user_id' => (string) $att['user_id'],
                                'timestamp' => $timestamp,
                                'student_id' => $student->id,
                                'status_code' => $statusCode,
                            ]);
                            if ($student && ! empty($student->parent_phone)) {
                                SendWhatsAppAttendanceJob::dispatch($student, $timestamp, $statusCode, $device->name);
                            }
                            $newCount++;
                        }
                    } else {
                        // Non-Strict Mode (Aturan Lama): Abaikan jika timestamp dan mesin sama persis
                        $existingLog = AttendanceLog::withTrashed()->where([
                            'device_id' => $device->id,
                            'device_user_id' => (string) $att['user_id'],
                            'timestamp' => $timestamp,
                        ])->first();

                        if (! $existingLog) {
                            AttendanceLog::create([
                                'device_id' => $device->id,
                                'device_user_id' => (string) $att['user_id'],
                                'timestamp' => $timestamp,
                                'student_id' => $student ? $student->id : null,
                                'status_code' => $statusCode,
                            ]);
                            if ($student && ! empty($student->parent_phone)) {
                                SendWhatsAppAttendanceJob::dispatch($student, $timestamp, $statusCode, $device->name);
                            }
                            $newCount++;
                        }
                    }
                }

                return redirect()->back()->with('success', "Sinkronisasi selesai. $newCount absen baru ditambahkan.");
            }

            return redirect()->back()->with('error', 'Gagal menarik absen dari mesin: '.$response->json('error', 'Unknown Error'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal koneksi ke Service Python: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        $log = AttendanceLog::findOrFail($id);
        $log->delete();

        return redirect()->back()->with('success', 'Data absensi berhasil dihapus dari sistem (data di dalam mesin X1000-C tidak ikut terhapus).');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:attendance_logs,id',
        ]);

        $count = count($request->ids);
        AttendanceLog::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', "$count data absensi berhasil dihapus secara massal.");
    }

    /**
     * Halaman & Endpoint Uji Coba / Simulasi Absensi Siswa Realtime
     */
    public function simulate(Request $request)
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            Log::warning('Percobaan akses ilegal ke simulate absensi dari IP: '.$request->ip());
            abort(403, 'Akses simulasi ditolak. Hanya Administrator yang memiliki izin ini.');
        }

        if ($request->isMethod('get')) {
            $students = Student::with('schoolClass')->orderBy('name')->get();
            $devices = Device::all();
            $recentLogs = AttendanceLog::with(['student.schoolClass', 'device'])
                ->whereDate('timestamp', Carbon::today())
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();

            return view('attendance.simulate', compact('students', 'devices', 'recentLogs'));
        }

        // POST: Proses Simulasi Tap Absensi
        $studentId = $request->input('student_id');

        // Jika belum ada siswa di sistem, generate otomatis
        if (Student::count() === 0) {
            $this->createDemoStudentsInternal();
        }

        if (! $studentId || $studentId === 'random') {
            $student = Student::inRandomOrder()->first();
        } else {
            $student = Student::find($studentId);
        }

        if (! $student) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Data siswa tidak ditemukan.'], 404);
            }

            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // Tentukan waktu absen (WIB)
        $customTime = $request->input('time'); // misal "06:15"
        if ($customTime) {
            $timestamp = Carbon::today('Asia/Jakarta')->format('Y-m-d').' '.$customTime.':00';
        } else {
            $timestamp = $request->input('timestamp', Carbon::now('Asia/Jakarta')->toDateTimeString());
        }

        $statusCode = (int) $request->input('status_code', 0); // 0 = Masuk
        $device = Device::first();
        if (! $device) {
            $device = Device::create([
                'name' => 'Mesin Fingerprint Utama',
                'ip_address' => '192.168.1.201',
                'port' => 4370,
                'status' => 'online',
                'serial_number' => 'DEV-DEMO-001',
            ]);
        }

        $deviceUserId = (string) ($student->device_user_id ?? $student->id);

        // Cari apakah ada log yang sama di timestamp ini (termasuk trashed)
        $log = AttendanceLog::withTrashed()
            ->where('device_id', $device->id)
            ->where('device_user_id', $deviceUserId)
            ->where('timestamp', $timestamp)
            ->first();

        if ($log) {
            if ($log->trashed()) {
                $log->restore();
            }
            $log->update([
                'student_id' => $student->id,
                'status_code' => $statusCode,
            ]);
        } else {
            $log = AttendanceLog::create([
                'device_id' => $device->id,
                'device_user_id' => $deviceUserId,
                'timestamp' => $timestamp,
                'student_id' => $student->id,
                'status_code' => $statusCode,
            ]);
        }

        // Siarkan realtime event untuk WebSocket / Reverb
        try {
            broadcast(new AttendanceCreated($log));
        } catch (\Throwable $e) {
            Log::warning('Broadcast AttendanceCreated failed in simulation: '.$e->getMessage());
        }

        // Kirim notifikasi WhatsApp jika nomor orang tua tersedia
        if (! empty($student->parent_phone)) {
            try {
                SendWhatsAppAttendanceJob::dispatch($student, $timestamp, $statusCode, $device?->name);
            } catch (\Throwable $e) {
                Log::warning('Dispatch SendWhatsAppAttendanceJob failed in simulation: '.$e->getMessage());
            }
        }

        $timeFormatted = Carbon::parse($timestamp)->format('H:i');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => "Simulasi absensi {$student->name} berhasil dicatat!",
                'log' => $log->load('student.schoolClass', 'device'),
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'class' => $student->schoolClass->name ?? '-',
                    'photo' => $student->photo_url,
                    'initial' => strtoupper(substr($student->name, 0, 1)),
                ],
                'time' => $timeFormatted,
            ]);
        }

        return redirect()->back()->with('success', "Simulasi absensi {$student->name} ({$timeFormatted} WIB) berhasil!");
    }

    /**
     * Buat data siswa demo cepat untuk simulasi
     */
    public function seedDemoStudents(Request $request)
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            Log::warning('Percobaan akses ilegal ke seedDemoStudents dari IP: '.$request->ip());
            abort(403, 'Akses ditolak.');
        }

        $count = $this->createDemoStudentsInternal();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => "$count data siswa demo berhasil dibuat!"]);
        }

        return redirect()->back()->with('success', "$count data siswa demo berhasil dibuat!");
    }

    /**
     * Reset / bersihkan data absensi hari ini untuk uji coba ulang
     */
    public function resetTodayAttendance(Request $request)
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            Log::warning('Percobaan akses ilegal ke resetTodayAttendance dari IP: '.$request->ip());
            abort(403, 'Akses ditolak.');
        }

        $today = Carbon::today()->toDateString();
        $count = AttendanceLog::whereDate('timestamp', $today)->forceDelete();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => "$count absensi hari ini berhasil dibersihkan!"]);
        }

        return redirect()->back()->with('success', "$count absensi hari ini berhasil dibersihkan untuk pengujian ulang!");
    }

    /**
     * Helper internal untuk generate data demo awal
     */
    private function createDemoStudentsInternal(): int
    {
        $major = Major::firstOrCreate(['name' => 'Pengembangan Perangkat Lunak dan Gim (PPLG)']);
        $classes = [
            'XI RPL 1' => SchoolClass::firstOrCreate(['name' => 'XI RPL 1', 'major_id' => $major->id]),
            'XI RPL 2' => SchoolClass::firstOrCreate(['name' => 'XI RPL 2', 'major_id' => $major->id]),
            'X PPLG 1' => SchoolClass::firstOrCreate(['name' => 'X PPLG 1', 'major_id' => $major->id]),
            'XII RPL 1' => SchoolClass::firstOrCreate(['name' => 'XII RPL 1', 'major_id' => $major->id]),
        ];

        $demoStudents = [
            ['name' => 'Mohamad Jibril', 'class' => 'XI RPL 1', 'uid' => '101', 'manual_streak' => 8],
            ['name' => 'Kayla Aqila', 'class' => 'XI RPL 1', 'uid' => '102', 'manual_streak' => 7],
            ['name' => 'Achmad Dzaky', 'class' => 'XI RPL 2', 'uid' => '103', 'manual_streak' => 6],
            ['name' => 'Sheren Angelica', 'class' => 'XI RPL 2', 'uid' => '104', 'manual_streak' => 5],
            ['name' => 'Rafa Abrar', 'class' => 'X PPLG 1', 'uid' => '105', 'manual_streak' => 4],
            ['name' => 'Aulia Zahra', 'class' => 'X PPLG 1', 'uid' => '106', 'manual_streak' => 3],
            ['name' => 'Dwi Saputra', 'class' => 'XII RPL 1', 'uid' => '107', 'manual_streak' => 9],
            ['name' => 'Bima Satria', 'class' => 'XII RPL 1', 'uid' => '108', 'manual_streak' => 2],
        ];

        $created = 0;
        foreach ($demoStudents as $demo) {
            $student = Student::firstOrCreate(
                ['device_user_id' => $demo['uid']],
                [
                    'name' => $demo['name'],
                    'school_class_id' => $classes[$demo['class']]->id,
                    'parent_phone' => '081234567890',
                    'privilege' => 0,
                    'manual_streak' => $demo['manual_streak'],
                ]
            );
            if ($student->wasRecentlyCreated) {
                $created++;
            }
        }

        Device::firstOrCreate(
            ['ip_address' => '192.168.1.201'],
            ['name' => 'Mesin Fingerprint Utama', 'port' => 4370, 'status' => 'online', 'serial_number' => 'DEV-DEMO-001']
        );

        return $created ?: count($demoStudents);
    }
}
