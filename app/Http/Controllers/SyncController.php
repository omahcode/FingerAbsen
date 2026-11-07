<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        $targetName = $targetDevice ? $targetDevice->name : 'Seluruh Mesin';
        $filterName = $classId ? ('Kelas '.optional(SchoolClass::find($classId))->name) : 'Semua Kelas';

        return redirect()->back()->with(
            'success',
            "Berhasil menjadwalkan {$userCount} data user dan {$fpCount} sidik jari ({$filterName}) ke antrean {$targetName}. Mesin akan mengunduh data secara bertahap (15 per request) agar server tidak overload."
        );
    }

    /**
     * Tarik template sidik jari dari mesin ke database web.
     * Mendukung penarikan dari 1 mesin tertentu atau dari seluruh mesin sekaligus.
     * Menggunakan strategi hybrid:
     * 1. Direct Socket TCP via service Python jika mesin online di jaringan lokal.
     * 2. Antrean Query ADMS agar mesin ADMS cloud mengunggah template-nya.
     */
    public function pullTemplates(Request $request): RedirectResponse
    {
        $deviceId = $request->input('device_id');

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

                        $student = Student::where('device_user_id', (string) $t['uid'])->first();
                        $teacher = Teacher::where('device_user_id', (string) $t['uid'])->first();

                        FingerprintTemplate::updateOrCreate(
                            [
                                'device_user_id' => (string) $t['uid'],
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
                        $directPulledCount++;
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan jika service direct socket offline, lanjutkan antrean ADMS
            }

            // 2. Kirim kumpulan perintah query penarikan ADMS
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

            IclockController::queueCommands($admsCommands, $targetSn, null, null, $d);
        }

        $targetLabel = (count($devices) === 1) ? $devices->first()->name : 'Seluruh Mesin ('.count($devices).' mesin)';

        $msg = "Perintah tarik sidik jari telah berhasil dikirim ke {$targetLabel}.";
        if ($directPulledCount > 0) {
            $msg .= " Berhasil menarik {$directPulledCount} template sidik jari secara langsung ke database.";
        } else {
            $msg .= ' Mesin ADMS akan segera mengunggah data sidik jari ke server saat melakukan kontak / polling.';
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
}
