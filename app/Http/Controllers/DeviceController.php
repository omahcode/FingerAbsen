<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::withCount([
            'deviceUsers as total_users_count',
            'deviceUsers as students_count' => fn ($q) => $q->where('user_type', 'student'),
            'deviceUsers as teachers_count' => fn ($q) => $q->where('user_type', 'teacher'),
        ])->get();

        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required',
            'port' => 'required|integer',
            'serial_number' => 'nullable|string',
            'purpose' => 'nullable|in:all,teachers,students',
        ]);

        Device::create([
            'name' => $request->name,
            'serial_number' => $request->serial_number,
            'ip_address' => $request->ip_address,
            'port' => $request->port,
            'purpose' => $request->purpose ?? 'all',
            'status' => 'offline',
        ]);

        return redirect()->route('devices.index')->with('success', 'Mesin berhasil ditambahkan.');
    }

    public function show(Request $request, Device $device)
    {
        // Auto-sinkronisasi awal jika pencatatan mesin masih kosong
        if ($device->deviceUsers()->count() === 0) {
            $logs = AttendanceLog::where('device_id', $device->id)
                ->whereNotNull('device_user_id')
                ->select('student_id', 'teacher_id', 'device_user_id')
                ->distinct()
                ->get();

            foreach ($logs as $log) {
                DeviceUser::firstOrCreate(
                    [
                        'device_id' => $device->id,
                        'device_user_id' => (string) $log->device_user_id,
                    ],
                    [
                        'user_type' => $log->teacher_id ? 'teacher' : 'student',
                        'student_id' => $log->student_id,
                        'teacher_id' => $log->teacher_id,
                        'synced_at' => now(),
                    ]
                );
            }

            if ($device->isForTeachers() && $device->deviceUsers()->count() === 0) {
                $teachers = Teacher::all();
                foreach ($teachers as $t) {
                    DeviceUser::firstOrCreate(
                        [
                            'device_id' => $device->id,
                            'device_user_id' => (string) $t->device_user_id,
                        ],
                        [
                            'user_type' => 'teacher',
                            'teacher_id' => $t->id,
                            'synced_at' => now(),
                        ]
                    );
                }
            }
        }

        $type = $request->query('type', 'all');
        $search = $request->query('search');
        $classId = $request->query('school_class_id');

        $query = $device->deviceUsers()->with([
            'student.schoolClass',
            'teacher',
            'fingerprintTemplates',
        ]);

        if ($type === 'students') {
            $query->where('user_type', 'student');
        } elseif ($type === 'teachers') {
            $query->where('user_type', 'teacher');
        }

        if (! empty($classId) && $classId !== 'all') {
            $query->whereHas('student', function ($q) use ($classId) {
                $q->where('school_class_id', $classId);
            });
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('device_user_id', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%"));
            });
        }

        $deviceUsers = $query->paginate(30)->withQueryString();

        // Statistik Ringkas Mesin
        $totalUsers = $device->deviceUsers()->count();
        $totalStudents = $device->deviceUsers()->where('user_type', 'student')->count();
        $totalTeachers = $device->deviceUsers()->where('user_type', 'teacher')->count();

        // Rincian Siswa per Kelas di Mesin Ini
        $classBreakdown = SchoolClass::whereHas('students.deviceUsers', function ($q) use ($device) {
            $q->where('device_id', $device->id);
        })->withCount(['students as count' => function ($q) use ($device) {
            $q->whereHas('deviceUsers', fn ($du) => $du->where('device_id', $device->id));
        }])->get();

        $classes = SchoolClass::orderBy('name')->get();

        // 10 Riwayat Tap Terakhir di Mesin Ini
        $recentLogs = AttendanceLog::where('device_id', $device->id)
            ->with(['student.schoolClass', 'teacher'])
            ->latest('timestamp')
            ->take(10)
            ->get();

        return view('devices.show', compact(
            'device',
            'deviceUsers',
            'totalUsers',
            'totalStudents',
            'totalTeachers',
            'classBreakdown',
            'classes',
            'type',
            'search',
            'classId',
            'recentLogs'
        ));
    }

    public function removeUser(Device $device, string $deviceUserId)
    {
        // Kirim perintah hapus ke antrean ADMS mesin tersebut
        IclockController::queueCommand("DATA DELETE USER PIN={$deviceUserId}", $device->serial_number);
        IclockController::queueCommand("DATA DELETE FP PIN={$deviceUserId}", $device->serial_number);

        // Hapus dari catatan user mesin ini
        DeviceUser::where('device_id', $device->id)
            ->where('device_user_id', $deviceUserId)
            ->delete();

        return redirect()->back()->with('success', "Perintah hapus pengguna PIN {$deviceUserId} telah dikirim ke mesin {$device->name}.");
    }

    public function pullData(Device $device)
    {
        IclockController::queueCommand('CHECK', $device->serial_number);
        IclockController::queueCommand('QUERY USER PIN=0', $device->serial_number);
        IclockController::queueCommand('DATA QUERY USER', $device->serial_number);
        IclockController::queueCommand('QUERY USERINFO PIN=0', $device->serial_number);
        IclockController::queueCommand('QUERY FINGERTMP PIN=0', $device->serial_number);

        return redirect()->back()->with('success', "Perintah inspeksi & penarikan daftar user telah dikirim ke mesin {$device->name}. Mesin akan segera mengunggah data.");
    }

    public function edit(Device $device)
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required',
            'port' => 'required|integer',
            'serial_number' => 'nullable|string',
            'purpose' => 'nullable|in:all,teachers,students',
        ]);

        $device->update([
            'name' => $request->name,
            'serial_number' => $request->serial_number,
            'ip_address' => $request->ip_address,
            'port' => $request->port,
            'purpose' => $request->purpose ?? 'all',
        ]);

        return redirect()->route('devices.index')->with('success', 'Data mesin berhasil diperbarui.');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Mesin dihapus.');
    }

    public function checkStatus($id)
    {
        $device = Device::findOrFail($id);

        // 1. Cek apakah mesin aktif via ADMS dalam 5 menit terakhir
        $isRecentlyActive = $device->last_seen_at && Carbon::parse($device->last_seen_at)->gt(now()->subMinutes(5));

        if ($isRecentlyActive) {
            $device->update(['status' => 'online']);

            return redirect()->back()->with('success', 'Mesin terdeteksi ONLINE (ADMS Aktif: '.Carbon::parse($device->last_seen_at)->diffForHumans().')');
        }

        // 2. Coba cek via Service Python jika ada URL service
        try {
            $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
            $response = Http::timeout(3)->post($serviceUrl.'/api/check_status', [
                'ip_address' => $device->ip_address,
                'port' => $device->port,
            ]);

            if ($response->successful() && ($response->json('status') === 'online')) {
                $device->update([
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Mesin terdeteksi ONLINE via Bridge.');
            }
        } catch (\Exception $e) {
            // Service bridge tidak aktif
        }

        $device->update(['status' => 'offline']);

        return redirect()->back()->with('error', 'Mesin belum mengirim sinyal (OFFLINE). Pastikan setting ADMS dan Gateway/Internet di mesin sudah benar.');
    }

    public function clearAdmin($id)
    {
        $device = Device::findOrFail($id);

        // Kirim ke antrean ADMS
        IclockController::queueCommand('CLEAR ADMIN', $device->serial_number);
        IclockController::queueCommand('DATA DELETE USER PIN=0', $device->serial_number);

        // Hapus juga hak admin di database lokal
        Student::where('privilege', '!=', '0')->update(['privilege' => '0']);

        return redirect()->back()->with('success', 'Perintah buka kunci mesin telah dikirim ke antrean mesin.');
    }

    public function syncTime($id)
    {
        $device = Device::findOrFail($id);
        $time = now()->format('Y-m-d H:i:s');

        // Kirim ke antrean ADMS
        IclockController::queueCommand("SET OPTIONS DateTime={$time}", $device->serial_number);
        IclockController::queueCommand("SET TIME {$time}", $device->serial_number);

        return redirect()->back()->with('success', "Perintah sinkronisasi waktu ({$time}) telah dikirim ke mesin.");
    }

    public function syncTimeAll()
    {
        $time = now()->format('Y-m-d H:i:s');
        IclockController::queueCommand("SET OPTIONS DateTime={$time}");
        IclockController::queueCommand("SET TIME {$time}");

        return redirect()->back()->with('success', "Perintah sinkronisasi waktu ({$time}) telah dikirim ke semua mesin.");
    }
}
