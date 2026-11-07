<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $admsLogs = Cache::get('adms_recent_logs', []);
        $totalPendingQueue = $devices->sum('pending_queue_count') + count(Cache::get('adms_queue_GLOBAL', []));

        return view('sync.index', compact('devices', 'classes', 'studentsCount', 'templatesCount', 'admsLogs', 'totalPendingQueue'));
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
     * Minta mesin mengirimkan seluruh template sidik jari ke database web.
     */
    public function backupTemplates(Request $request): RedirectResponse
    {
        $deviceId = $request->input('device_id');
        $device = Device::find($deviceId);
        $targetSn = $device ? $device->serial_number : null;

        // Kirim semua variasi perintah penarikan sidik jari ADMS
        IclockController::queueCommand('CHECK', $targetSn);
        IclockController::queueCommand('QUERY FINGERTMP PIN=0', $targetSn);
        IclockController::queueCommand('DATA QUERY FINGERTMP PIN=0', $targetSn);
        IclockController::queueCommand('DATA QUERY FINGERTMP', $targetSn);
        IclockController::queueCommand('QUERY BIODATA PIN=0', $targetSn);
        IclockController::queueCommand('DATA QUERY BIODATA', $targetSn);
        IclockController::queueCommand('QUERY USER PIN=0', $targetSn);
        IclockController::queueCommand('DATA QUERY USER PIN=0', $targetSn);
        IclockController::queueCommand('QUERY USERINFO PIN=0', $targetSn);
        IclockController::queueCommand('DATA QUERY USERINFO PIN=0', $targetSn);
        IclockController::queueCommand('DATA QUERY USERINFO', $targetSn);

        return redirect()->back()->with('success', 'Perintah backup sidik jari & data siswa telah dikirim ke '.($device->name ?? 'Semua Mesin').'. Mesin akan segera mengunggah data ke server.');
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
