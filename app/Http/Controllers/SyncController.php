<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SyncController extends Controller
{
    public function index()
    {
        $devices = Device::all();
        $classes = SchoolClass::with('major')->get();
        $studentsCount = Student::count();
        $templatesCount = FingerprintTemplate::count();
        $admsLogs = Cache::get('adms_recent_logs', []);

        return view('sync.index', compact('devices', 'classes', 'studentsCount', 'templatesCount', 'admsLogs'));
    }

    /**
     * Sebar semua data siswa dan sidik jari dari database web ke semua mesin ADMS
     */
    public function pushAll(Request $request)
    {
        $students = Student::all();
        $templates = FingerprintTemplate::all();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada data siswa di database.');
        }

        $userCount = 0;
        foreach ($students as $student) {
            IclockController::queueCommand("DATA USER PIN={$student->device_user_id}\tName={$student->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0", null, null, 'students');
            IclockController::queueCommand("DATA UPDATE USER PIN={$student->device_user_id}\tName={$student->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0", null, null, 'students');
            $userCount++;
        }

        $fpCount = 0;
        foreach ($templates as $t) {
            IclockController::queueCommand("DATA FP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}", null, null, 'students');
            IclockController::queueCommand("DATA UPDATE FINGERTMP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}", null, null, 'students');
            $fpCount++;
        }

        return redirect()->back()->with('success', "Berhasil memasukkan {$userCount} data siswa dan {$fpCount} template sidik jari ke antrean sinkronisasi seluruh mesin.");
    }

    /**
     * Minta mesin mengirimkan seluruh template sidik jari ke database web
     */
    public function backupTemplates(Request $request)
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
}
