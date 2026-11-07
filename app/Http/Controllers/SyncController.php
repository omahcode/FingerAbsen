<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceFingerprintTemplate;
use App\Models\FingerprintTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SyncController extends Controller
{
    /**
     * Tampilkan halaman manajemen sinkronisasi & monitoring antrean ADMS.
     */
    public function index(): View
    {
        $devices = Device::all()->map(function (Device $device): Device {
            $countSn = $device->serial_number ? count(Cache::get('adms_queue_'.$device->serial_number, [])) : 0;
            $countDev = count(Cache::get('adms_queue_DEVICE_'.$device->id, []));
            $device->pending_queue_count = max($countSn, $countDev);

            return $device;
        });

        $classes = SchoolClass::with('major')->get();
        $studentsCount = Student::count();
        $templatesCount = FingerprintTemplate::count();
        $studentsWithFpCount = Student::whereHas('fingerprintTemplates')->count();
        $studentsWithoutFpCount = max(0, $studentsCount - $studentsWithFpCount);

        $admsLogs = Cache::get('adms_recent_logs', []);
        $totalPendingQueue = $devices->sum('pending_queue_count') + count(Cache::get('adms_queue_GLOBAL', []));

        return view('sync.index', compact(
            'devices',
            'classes',
            'studentsCount',
            'templatesCount',
            'studentsWithFpCount',
            'studentsWithoutFpCount',
            'admsLogs',
            'totalPendingQueue'
        ));
    }

    /**
     * Sebar data siswa dan sidik jari dari database web ke mesin ADMS secara bertahap via antrean.
     */
    public function pushAll(Request $request): RedirectResponse
    {
        $deviceId = $request->input('device_id');
        $classId = $request->input('school_class_id');
        $contentType = $request->input('content_type', 'all');

        $targetDevice = $deviceId ? Device::find($deviceId) : null;
        $targetSn = $targetDevice ? $targetDevice->serial_number : null;

        $studentQuery = Student::query();
        if ($classId) {
            $studentQuery->where('school_class_id', $classId);
        }
        $students = $studentQuery->get();

        if ($students->isEmpty() && $contentType !== 'templates_only') {
            return redirect()->back()->with('error', 'Tidak ada data siswa yang cocok dengan filter yang dipilih.');
        }

        $studentPins = $students->pluck('device_user_id')->filter()->all();

        $templateQuery = FingerprintTemplate::query();
        if ($classId) {
            $templateQuery->whereIn('device_user_id', $studentPins);
        }
        $templates = $templateQuery->get();

        $commands = [];
        $userCount = 0;
        $fpCount = 0;

        if (in_array($contentType, ['all', 'users_only'])) {
            foreach ($students as $student) {
                if (empty($student->device_user_id)) {
                    continue;
                }
                $name = $student->name;
                $commands[] = "DATA USER PIN={$student->device_user_id}\tName={$name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0";
                $commands[] = "DATA UPDATE USER PIN={$student->device_user_id}\tName={$name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0";
                $userCount++;
            }
        }

        if (in_array($contentType, ['all', 'templates_only'])) {
            foreach ($templates as $t) {
                if (empty($t->device_user_id) || empty($t->template_data)) {
                    continue;
                }
                $commands[] = "DATA FP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}";
                $commands[] = "DATA UPDATE FINGERTMP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}";
                $fpCount++;
            }
        }

        if (empty($commands)) {
            return redirect()->back()->with('error', 'Tidak ada data sidik jari atau user yang dapat disebarkan untuk kriteria yang dipilih.');
        }

        IclockController::queueCommands($commands, $targetSn, null, 'students', $targetDevice);

        // Catat distribusi template ke tabel device_fingerprint_templates
        $targetDevices = $targetDevice ? collect([$targetDevice]) : Device::all();
        foreach ($targetDevices as $dev) {
            foreach ($templates as $t) {
                DeviceFingerprintTemplate::updateOrCreate(
                    [
                        'device_id' => $dev->id,
                        'device_user_id' => (string) $t->device_user_id,
                        'finger_index' => (int) $t->finger_index,
                    ],
                    [
                        'fingerprint_template_id' => $t->id,
                        'status' => 'synced',
                        'last_synced_at' => now(),
                    ]
                );
            }
        }

        $targetName = $targetDevice ? $targetDevice->name : 'Seluruh Mesin';
        $filterName = $classId ? ('Kelas '.optional(SchoolClass::find($classId))->name) : 'Semua Kelas';

        return redirect()->back()->with(
            'success',
            "Berhasil menjadwalkan {$userCount} data user dan {$fpCount} sidik jari ({$filterName}) ke antrean {$targetName}. Mesin akan mengunduh data secara bertahap (15 per request) agar server tidak overload."
        );
    }

    /**
     * Pratinjau data user & sidik jari dari mesin sebelum dilakukan penarikan.
     */
    public function previewTemplates(Request $request): JsonResponse
    {
        $deviceId = $request->query('device_id');
        if (! $deviceId || $deviceId === 'all') {
            $device = Device::first();
        } else {
            $device = Device::find($deviceId);
        }

        if (! $device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mesin fingerprint tidak ditemukan.',
            ], 404);
        }

        $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
        $machineUsers = [];
        $machineTemplates = [];
        $source = 'database_catalog';
        $isOnlineSocket = false;

        try {
            $resUsers = Http::timeout(2.5)->post($serviceUrl.'/api/get_users', [
                'ip_address' => $device->ip_address,
                'port' => $device->port,
            ]);

            if ($resUsers->successful() && $resUsers->json('status') === 'success') {
                $machineUsers = $resUsers->json('users', []);
                $isOnlineSocket = true;
            }

            $resTemplates = Http::timeout(3.5)->post($serviceUrl.'/api/get_templates', [
                'ip_address' => $device->ip_address,
                'port' => $device->port,
            ]);

            if ($resTemplates->successful() && $resTemplates->json('status') === 'success') {
                $machineTemplates = $resTemplates->json('templates', []);
                $isOnlineSocket = true;
            }
        } catch (\Throwable $e) {
            // Socket python offline atau mesin ADMS via internet
        }

        $usersList = [];

        if ($isOnlineSocket && ! empty($machineUsers)) {
            $source = 'machine_live';
            $fpCountsByPin = collect($machineTemplates)->groupBy('uid')->map->count();
            $pins = collect($machineUsers)->pluck('user_id')->map(fn ($p) => (string) $p)->filter()->unique()->values();

            $students = Student::with('schoolClass')->whereIn('device_user_id', $pins)->get()->keyBy('device_user_id');
            $teachers = Teacher::whereIn('device_user_id', $pins)->get()->keyBy('device_user_id');
            $dbTemplates = FingerprintTemplate::whereIn('device_user_id', $pins)->get()->groupBy('device_user_id');

            foreach ($machineUsers as $u) {
                $pin = (string) $u['user_id'];
                if (empty($pin)) {
                    continue;
                }

                $student = $students->get($pin);
                $teacher = $teachers->get($pin);

                $name = $student ? $student->name : ($teacher ? $teacher->name : ($u['name'] ?: 'User '.$pin));
                $role = $student ? 'Siswa' : ($teacher ? 'Guru' : 'Belum Terdaftar di Web');
                $className = $student ? ($student->schoolClass->name ?? '-') : ($teacher ? 'Dewan Guru' : '-');
                $machineFpCount = $fpCountsByPin->get($pin, 0);
                $dbFpCount = isset($dbTemplates[$pin]) ? $dbTemplates[$pin]->count() : 0;

                $usersList[] = [
                    'pin' => $pin,
                    'name' => $name,
                    'role' => $role,
                    'class_name' => $className,
                    'machine_fp_count' => $machineFpCount,
                    'db_fp_count' => $dbFpCount,
                    'is_synced' => ($machineFpCount > 0 && $dbFpCount >= $machineFpCount),
                ];
            }
        } else {
            // Fallback: Data katalog siswa & guru di database web
            $source = 'database_catalog';
            $students = Student::with('schoolClass')->orderBy('name')->get();
            $teachers = Teacher::orderBy('name')->get();
            $allPins = $students->pluck('device_user_id')->concat($teachers->pluck('device_user_id'))->filter()->unique();
            $dbTemplates = FingerprintTemplate::whereIn('device_user_id', $allPins)->get()->groupBy('device_user_id');

            foreach ($students as $s) {
                $pin = (string) $s->device_user_id;
                $dbFpCount = isset($dbTemplates[$pin]) ? $dbTemplates[$pin]->count() : 0;
                $usersList[] = [
                    'pin' => $pin,
                    'name' => $s->name,
                    'role' => 'Siswa',
                    'class_name' => $s->schoolClass->name ?? '-',
                    'machine_fp_count' => null,
                    'db_fp_count' => $dbFpCount,
                    'is_synced' => ($dbFpCount > 0),
                ];
            }

            foreach ($teachers as $t) {
                $pin = (string) $t->device_user_id;
                $dbFpCount = isset($dbTemplates[$pin]) ? $dbTemplates[$pin]->count() : 0;
                $usersList[] = [
                    'pin' => $pin,
                    'name' => $t->name,
                    'role' => 'Guru',
                    'class_name' => 'Dewan Guru',
                    'machine_fp_count' => null,
                    'db_fp_count' => $dbFpCount,
                    'is_synced' => ($dbFpCount > 0),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'ip_address' => $device->ip_address,
                'serial_number' => $device->serial_number,
            ],
            'source' => $source,
            'source_label' => ($source === 'machine_live') ? 'Terhubung Langsung ke Mesin (Live)' : 'Katalog Siswa & Guru (Database)',
            'total_users' => count($usersList),
            'users' => $usersList,
        ]);
    }

    /**
     * Tarik template sidik jari dari mesin ke database web.
     * Mendukung penarikan dari 1 mesin tertentu atau dari seluruh mesin sekaligus.
     * Mendukung penarikan untuk user/PIN tertentu yang dipilih.
     */
    public function pullTemplates(Request $request): RedirectResponse
    {
        $deviceId = $request->input('device_id');
        $pinsInput = $request->input('pins');

        $selectedPins = [];
        if (is_array($pinsInput)) {
            $selectedPins = array_values(array_filter(array_map('strval', $pinsInput)));
        } elseif (is_string($pinsInput) && strlen(trim($pinsInput)) > 0) {
            $selectedPins = array_values(array_filter(array_map('trim', explode(',', $pinsInput))));
        }

        $devices = collect();
        if ($deviceId && $deviceId !== 'all') {
            $device = Device::find($deviceId);
            if (! $device) {
                return redirect()->back()->with('error', 'Mesin fingerprint yang dipilih tidak ditemukan.');
            }
            $devices = collect([$device]);
        } else {
            $devices = Device::all();
            if ($devices->isEmpty()) {
                return redirect()->back()->with('error', 'Belum ada mesin fingerprint yang terdaftar di database.');
            }
        }

        $directPulledCount = 0;
        $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');

        foreach ($devices as $d) {
            $targetSn = $d->serial_number ?: null;

            // 1. Coba tarik langsung via helper service jika aktif
            try {
                $response = Http::timeout(3)->post($serviceUrl.'/api/get_templates', [
                    'ip_address' => $d->ip_address,
                    'port' => $d->port,
                ]);

                if ($response->successful() && $response->json('status') === 'success') {
                    $templates = $response->json('templates', []);
                    foreach ($templates as $t) {
                        if (empty($t['uid']) || empty($t['template'])) {
                            continue;
                        }

                        $pin = (string) $t['uid'];
                        if (! empty($selectedPins) && ! in_array($pin, $selectedPins, true)) {
                            continue;
                        }

                        $student = Student::where('device_user_id', $pin)->first();
                        $teacher = Teacher::where('device_user_id', $pin)->first();

                        $savedT = FingerprintTemplate::updateOrCreate(
                            [
                                'device_user_id' => $pin,
                                'finger_index' => (int) ($t['fid'] ?? 0),
                            ],
                            [
                                'student_id' => $student ? $student->id : null,
                                'teacher_id' => $teacher ? $teacher->id : null,
                                'size' => (int) ($t['size'] ?? strlen($t['template'])),
                                'valid' => (int) ($t['valid'] ?? 1),
                                'template_data' => $t['template'],
                            ]
                        );

                        DeviceFingerprintTemplate::updateOrCreate(
                            [
                                'device_id' => $d->id,
                                'device_user_id' => $pin,
                                'finger_index' => (int) ($t['fid'] ?? 0),
                            ],
                            [
                                'fingerprint_template_id' => $savedT->id,
                                'status' => 'synced',
                                'last_synced_at' => now(),
                            ]
                        );
                        $directPulledCount++;
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan jika service direct socket offline, lanjutkan antrean ADMS
            }

            // 2. Kirim perintah query ADMS
            if (! empty($selectedPins)) {
                $admsCommands = ['CHECK'];
                foreach ($selectedPins as $pin) {
                    $admsCommands[] = "QUERY FINGERTMP PIN={$pin}";
                    $admsCommands[] = "DATA QUERY FINGERTMP PIN={$pin}";
                    $admsCommands[] = "QUERY BIODATA PIN={$pin}";
                    $admsCommands[] = "DATA QUERY BIODATA PIN={$pin}";
                }
            } else {
                $admsCommands = [
                    'CHECK',
                    'QUERY FINGERTMP PIN=0',
                    'DATA QUERY FINGERTMP PIN=0',
                    'DATA QUERY FINGERTMP',
                    'QUERY BIODATA PIN=0',
                    'DATA QUERY BIODATA',
                    'QUERY USER PIN=0',
                    'DATA QUERY USER PIN=0',
                    'QUERY USERINFO PIN=0',
                    'DATA QUERY USERINFO PIN=0',
                    'DATA QUERY USERINFO',
                ];
            }

            IclockController::queueCommands($admsCommands, $targetSn, null, null, $d);
        }

        $targetLabel = (count($devices) === 1) ? $devices->first()->name : 'Seluruh Mesin ('.count($devices).' mesin)';

        if (! empty($selectedPins)) {
            $msg = 'Perintah tarik sidik jari untuk '.count($selectedPins)." user terpilih telah dikirim ke {$targetLabel}.";
            if ($directPulledCount > 0) {
                $msg .= " Berhasil menarik {$directPulledCount} template sidik jari secara langsung ke database.";
            } else {
                $msg .= ' Mesin ADMS akan segera mengunggah sidik jari user terpilih saat melakukan kontak / polling.';
            }
        } else {
            $msg = "Perintah tarik sidik jari telah berhasil dikirim ke {$targetLabel}.";
            if ($directPulledCount > 0) {
                $msg .= " Berhasil menarik {$directPulledCount} template sidik jari secara langsung ke database.";
            } else {
                $msg .= ' Mesin ADMS akan segera mengunggah data sidik jari ke server saat melakukan kontak / polling.';
            }
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Kompatibilitas untuk rute lama backupTemplates.
     */
    public function backupTemplates(Request $request): RedirectResponse
    {
        return $this->pullTemplates($request);
    }

    /**
     * Bersihkan antrean perintah ADMS jika ada antrean tertunda atau dibatalkan.
     */
    public function clearQueue(Request $request): RedirectResponse
    {
        $deviceId = $request->input('device_id');

        if ($deviceId) {
            $device = Device::find($deviceId);
            if ($device) {
                if ($device->serial_number) {
                    Cache::forget('adms_queue_'.$device->serial_number);
                }
                Cache::forget('adms_queue_DEVICE_'.$device->id);

                return redirect()->back()->with('success', "Antrean sinkronisasi untuk {$device->name} berhasil dikosongkan.");
            }
        }

        $devices = Device::all();
        foreach ($devices as $d) {
            if ($d->serial_number) {
                Cache::forget('adms_queue_'.$d->serial_number);
            }
            Cache::forget('adms_queue_DEVICE_'.$d->id);
        }
        Cache::forget('adms_queue_GLOBAL');

        return redirect()->back()->with('success', 'Seluruh antrean sinkronisasi perintah mesin berhasil dikosongkan.');
    }

    /**
     * Tampilkan matriks audit & ketersediaan sidik jari per mesin (cek sidik jari ada di mesin mana saja).
     */
    public function matrix(Request $request): View
    {
        $devices = Device::orderBy('name')->get();
        $classes = SchoolClass::with('major')->orderBy('name')->get();

        $search = $request->query('search');
        $role = $request->query('role', 'all');
        $classId = $request->query('school_class_id');
        $statusFilter = $request->query('status', 'all');
        $targetDeviceId = $request->query('target_device_id');

        // Ambil seluruh template di database web
        $allDbTemplates = FingerprintTemplate::all()->groupBy('device_user_id');

        // Ambil template yang tercatat per mesin
        $allDeviceTemplates = DeviceFingerprintTemplate::all()
            ->groupBy('device_user_id')
            ->map(fn ($items) => $items->groupBy('device_id'));

        // Query Siswa
        $students = collect();
        if ($role === 'all' || $role === 'students') {
            $studentQuery = Student::with('schoolClass.major');
            if ($classId) {
                $studentQuery->where('school_class_id', $classId);
            }
            if ($search) {
                $studentQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('device_user_id', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                });
            }
            $students = $studentQuery->orderBy('name')->get();
        }

        // Query Guru
        $teachers = collect();
        if (($role === 'all' || $role === 'teachers') && empty($classId)) {
            $teacherQuery = Teacher::query();
            if ($search) {
                $teacherQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('device_user_id', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            }
            $teachers = $teacherQuery->orderBy('name')->get();
        }

        // Susun baris matriks
        $rows = collect();

        foreach ($students as $s) {
            $pin = (string) $s->device_user_id;
            $dbCount = isset($allDbTemplates[$pin]) ? $allDbTemplates[$pin]->count() : 0;
            $userDeviceMaps = $allDeviceTemplates->get($pin, collect());

            $deviceStatuses = [];
            $missingInDeviceIds = [];

            foreach ($devices as $dev) {
                $devTemplates = $userDeviceMaps->get($dev->id, collect());
                $devCount = $devTemplates->count();
                $hasInDevice = $devCount > 0;

                if (! $hasInDevice && $dbCount > 0) {
                    $missingInDeviceIds[] = $dev->id;
                }

                $deviceStatuses[$dev->id] = [
                    'has_template' => $hasInDevice,
                    'count' => $devCount,
                ];
            }

            $isComplete = ($dbCount > 0) && empty($missingInDeviceIds) && $devices->isNotEmpty();

            $rows->push([
                'id' => $s->id,
                'pin' => $pin,
                'name' => $s->name,
                'role' => 'Siswa',
                'class_name' => $s->schoolClass->name ?? '-',
                'photo_url' => $s->photo_url,
                'db_count' => $dbCount,
                'devices' => $deviceStatuses,
                'is_complete' => $isComplete,
                'missing_device_ids' => $missingInDeviceIds,
            ]);
        }

        foreach ($teachers as $t) {
            $pin = (string) $t->device_user_id;
            $dbCount = isset($allDbTemplates[$pin]) ? $allDbTemplates[$pin]->count() : 0;
            $userDeviceMaps = $allDeviceTemplates->get($pin, collect());

            $deviceStatuses = [];
            $missingInDeviceIds = [];

            foreach ($devices as $dev) {
                $devTemplates = $userDeviceMaps->get($dev->id, collect());
                $devCount = $devTemplates->count();
                $hasInDevice = $devCount > 0;

                if (! $hasInDevice && $dbCount > 0) {
                    $missingInDeviceIds[] = $dev->id;
                }

                $deviceStatuses[$dev->id] = [
                    'has_template' => $hasInDevice,
                    'count' => $devCount,
                ];
            }

            $isComplete = ($dbCount > 0) && empty($missingInDeviceIds) && $devices->isNotEmpty();

            $rows->push([
                'id' => $t->id,
                'pin' => $pin,
                'name' => $t->name,
                'role' => 'Guru',
                'class_name' => $t->category ?: 'Dewan Guru',
                'photo_url' => $t->photo_url,
                'db_count' => $dbCount,
                'devices' => $deviceStatuses,
                'is_complete' => $isComplete,
                'missing_device_ids' => $missingInDeviceIds,
            ]);
        }

        // Filter status sebaran
        if ($statusFilter === 'complete') {
            $rows = $rows->filter(fn ($r) => $r['is_complete']);
        } elseif ($statusFilter === 'incomplete') {
            $rows = $rows->filter(fn ($r) => $r['db_count'] > 0 && ! $r['is_complete']);
        } elseif ($statusFilter === 'missing_on_device' && $targetDeviceId) {
            $rows = $rows->filter(fn ($r) => in_array((int) $targetDeviceId, $r['missing_device_ids']));
        }

        // Statistik
        $totalRegisteredWithFp = $rows->filter(fn ($r) => $r['db_count'] > 0)->count();
        $completeCount = $rows->filter(fn ($r) => $r['is_complete'])->count();
        $incompleteCount = max(0, $totalRegisteredWithFp - $completeCount);

        return view('sync.matrix', compact(
            'devices',
            'classes',
            'rows',
            'search',
            'role',
            'classId',
            'statusFilter',
            'targetDeviceId',
            'totalRegisteredWithFp',
            'completeCount',
            'incompleteCount'
        ));
    }

    /**
     * Ambil status ketersediaan sidik jari user tertentu di seluruh mesin (API JSON untuk Modal).
     */
    public function userDevicesStatus(Request $request): JsonResponse
    {
        try {
            $pin = trim((string) $request->query('pin'));
            $id = $request->query('id');
            $role = $request->query('role');

            $student = null;
            $teacher = null;

            if (! empty($id)) {
                if ($role === 'Guru' || $role === 'teachers') {
                    $teacher = Teacher::find($id);
                } elseif ($role === 'Siswa' || $role === 'students') {
                    $student = Student::with('schoolClass')->find($id);
                }
            }

            if (! $student && ! $teacher && ! empty($pin)) {
                $student = Student::with('schoolClass')->where('device_user_id', $pin)->first();
                $teacher = Teacher::where('device_user_id', $pin)->orWhere('nip', $pin)->first();
            }

            if (! $student && ! $teacher && ! empty($id)) {
                $student = Student::with('schoolClass')->find($id);
                if (! $student) {
                    $teacher = Teacher::find($id);
                }
            }

            $effectivePin = $pin ?: ($student ? ($student->device_user_id ?: $student->nis) : ($teacher ? ($teacher->device_user_id ?: $teacher->nip) : ''));
            $name = $student ? $student->name : ($teacher ? $teacher->name : ($effectivePin ? 'User '.$effectivePin : 'User'));
            $userRole = $student ? 'Siswa' : ($teacher ? 'Guru' : ($role ?: 'User'));
            $className = $student ? ($student->schoolClass->name ?? '-') : ($teacher ? ($teacher->category ?: 'Dewan Guru') : '-');

            $templateQuery = FingerprintTemplate::query();
            if ($student) {
                $templateQuery->where(function ($q) use ($student, $effectivePin) {
                    $q->where('student_id', $student->id);
                    if (! empty($effectivePin)) {
                        $q->orWhere('device_user_id', $effectivePin);
                    }
                });
            } elseif ($teacher) {
                $templateQuery->where(function ($q) use ($teacher, $effectivePin) {
                    $q->where('teacher_id', $teacher->id);
                    if (! empty($effectivePin)) {
                        $q->orWhere('device_user_id', $effectivePin)
                            ->orWhere('device_user_id', $teacher->nip);
                    }
                });
            } elseif (! empty($effectivePin)) {
                $templateQuery->where('device_user_id', $effectivePin);
            } else {
                $templateQuery->whereRaw('1 = 0');
            }

            $templates = $templateQuery->get();
            $dbCount = $templates->count();

            $devices = Device::orderBy('name')->get();

            $deviceTemplates = collect();
            if (! empty($effectivePin)) {
                try {
                    if (Schema::hasTable('device_fingerprint_templates')) {
                        $deviceTemplates = DeviceFingerprintTemplate::where('device_user_id', $effectivePin)->get()->groupBy('device_id');
                    }
                } catch (\Throwable $t) {
                    Log::warning('DeviceFingerprintTemplate query skipped: '.$t->getMessage());
                }
            }

            $deviceList = [];
            $missingDeviceIds = [];

            foreach ($devices as $dev) {
                $hasInDevice = isset($deviceTemplates[$dev->id]);
                $countInDevice = $hasInDevice ? $deviceTemplates[$dev->id]->count() : 0;

                if (! $hasInDevice && $dbCount > 0) {
                    $missingDeviceIds[] = $dev->id;
                }

                $deviceList[] = [
                    'id' => $dev->id,
                    'name' => $dev->name,
                    'ip_address' => $dev->ip_address,
                    'port' => $dev->port,
                    'serial_number' => $dev->serial_number ?: '-',
                    'purpose_label' => $dev->purpose_label,
                    'has_template' => $hasInDevice,
                    'template_count' => $countInDevice,
                    'last_synced_at' => $hasInDevice && $deviceTemplates[$dev->id]->first()->last_synced_at ? $deviceTemplates[$dev->id]->first()->last_synced_at->diffForHumans() : null,
                ];
            }

            return response()->json([
                'status' => 'success',
                'user' => [
                    'pin' => $effectivePin ?: 'Belum Ada PIN',
                    'name' => $name,
                    'role' => $userRole,
                    'class_name' => $className,
                    'db_count' => $dbCount,
                ],
                'devices' => $deviceList,
                'is_complete' => ($dbCount > 0) && empty($missingDeviceIds) && $devices->isNotEmpty(),
                'missing_count' => count($missingDeviceIds),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in userDevicesStatus: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan di server: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pindai dan periksa ketersediaan sidik jari langsung dari seluruh mesin.
     */
    public function scanDevices(Request $request): RedirectResponse
    {
        $devices = Device::all();
        $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
        $foundCount = 0;

        foreach ($devices as $d) {
            $isLive = false;
            try {
                $response = Http::timeout(3)->post($serviceUrl.'/api/get_templates', [
                    'ip_address' => $d->ip_address,
                    'port' => $d->port,
                ]);

                if ($response->successful() && $response->json('status') === 'success') {
                    $templates = $response->json('templates', []);
                    $isLive = true;

                    foreach ($templates as $t) {
                        if (empty($t['uid']) || empty($t['template'])) {
                            continue;
                        }

                        $pin = (string) $t['uid'];
                        $fid = (int) ($t['fid'] ?? 0);

                        $student = Student::where('device_user_id', $pin)->first();
                        $teacher = Teacher::where('device_user_id', $pin)->orWhere('nip', $pin)->first();

                        $dbFp = FingerprintTemplate::updateOrCreate(
                            [
                                'device_user_id' => $pin,
                                'finger_index' => $fid,
                            ],
                            [
                                'student_id' => $student ? $student->id : null,
                                'teacher_id' => $teacher ? $teacher->id : null,
                                'size' => (int) ($t['size'] ?? strlen($t['template'])),
                                'valid' => (int) ($t['valid'] ?? 1),
                                'template_data' => $t['template'],
                            ]
                        );

                        DeviceFingerprintTemplate::updateOrCreate(
                            [
                                'device_id' => $d->id,
                                'device_user_id' => $pin,
                                'finger_index' => $fid,
                            ],
                            [
                                'fingerprint_template_id' => $dbFp->id,
                                'status' => 'synced',
                                'last_synced_at' => now(),
                            ]
                        );
                        $foundCount++;
                    }
                }
            } catch (\Throwable $e) {
            }

            // Fallback: Jika socket offline, gunakan log kehadiran untuk mengenali user yang pernah tap di mesin ini
            if (! $isLive) {
                $userPinsWithAttendance = AttendanceLog::where('device_id', $d->id)
                    ->distinct()
                    ->pluck('device_user_id')
                    ->filter();

                foreach ($userPinsWithAttendance as $pin) {
                    $dbTemplates = FingerprintTemplate::where('device_user_id', (string) $pin)->get();
                    foreach ($dbTemplates as $dt) {
                        DeviceFingerprintTemplate::firstOrCreate(
                            [
                                'device_id' => $d->id,
                                'device_user_id' => (string) $pin,
                                'finger_index' => $dt->finger_index,
                            ],
                            [
                                'fingerprint_template_id' => $dt->id,
                                'status' => 'synced',
                                'last_synced_at' => now(),
                            ]
                        );
                        $foundCount++;
                    }
                }
            }
        }

        return redirect()->back()->with(
            'success',
            "Pemeriksaan mesin selesai! Berhasil memetakan {$foundCount} template sidik jari pada mesin-mesin terdaftar."
        );
    }

    /**
     * Sebarkan sidik jari yang belum ada ke mesin-mesin yang belum memilikinya.
     */
    public function syncMissing(Request $request): RedirectResponse|JsonResponse
    {
        $pinsInput = $request->input('pins');
        $targetDeviceId = $request->input('target_device_id');

        $selectedPins = [];
        if (is_array($pinsInput)) {
            $selectedPins = array_values(array_filter(array_map('strval', $pinsInput)));
        }

        $devices = Device::all();
        if ($devices->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada mesin fingerprint terdaftar.');
        }

        $targetDevices = $targetDeviceId ? Device::where('id', $targetDeviceId)->get() : $devices;

        // Ambil template di database web
        $templateQuery = FingerprintTemplate::query();
        if (! empty($selectedPins)) {
            $templateQuery->whereIn('device_user_id', $selectedPins);
        }
        $templates = $templateQuery->get()->groupBy('device_user_id');

        if ($templates->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data sidik jari di database untuk disebarkan.');
        }

        $allDeviceTemplates = DeviceFingerprintTemplate::all()
            ->groupBy('device_user_id')
            ->map(fn ($items) => $items->groupBy('device_id'));

        $totalCommandsQueued = 0;
        $totalFpQueued = 0;

        foreach ($targetDevices as $dev) {
            $commands = [];
            $targetSn = $dev->serial_number ?: null;

            foreach ($templates as $pin => $userTemplates) {
                $hasInThisDevice = isset($allDeviceTemplates[$pin][$dev->id]);

                // Jika belum ada di mesin ini atau jika user memilih PIN secara spesifik
                if (! $hasInThisDevice || ! empty($selectedPins)) {
                    $student = Student::where('device_user_id', $pin)->first();
                    $teacher = Teacher::where('device_user_id', $pin)->first();
                    $name = $student ? $student->name : ($teacher ? $teacher->name : 'User '.$pin);

                    $commands[] = "DATA USER PIN={$pin}\tName={$name}\tPri=0\tGrp=1";
                    $commands[] = "DATA UPDATE USER PIN={$pin}\tName={$name}\tPri=0\tGrp=1";

                    foreach ($userTemplates as $t) {
                        $commands[] = "DATA FP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}";
                        $commands[] = "DATA UPDATE FINGERTMP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}";
                        $totalFpQueued++;

                        DeviceFingerprintTemplate::updateOrCreate(
                            [
                                'device_id' => $dev->id,
                                'device_user_id' => (string) $pin,
                                'finger_index' => (int) $t->finger_index,
                            ],
                            [
                                'fingerprint_template_id' => $t->id,
                                'status' => 'synced',
                                'last_synced_at' => now(),
                            ]
                        );
                    }
                }
            }

            if (! empty($commands)) {
                IclockController::queueCommands($commands, $targetSn, null, null, $dev);
                $totalCommandsQueued += count($commands);
            }
        }

        if ($totalCommandsQueued === 0) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Seluruh sidik jari sudah lengkap dan tersinkron di semua mesin target!',
                ]);
            }

            return redirect()->back()->with('info', 'Seluruh sidik jari sudah lengkap dan tersinkron di semua mesin target!');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => "Berhasil menjadwalkan {$totalFpQueued} sidik jari ke antrean mesin target.",
                'queued_count' => $totalFpQueued,
            ]);
        }

        return redirect()->back()->with(
            'success',
            "Berhasil menjadwalkan {$totalFpQueued} sidik jari ke antrean mesin yang belum memilikinya. Data akan terunduh otomatis secara bertahap."
        );
    }
}
