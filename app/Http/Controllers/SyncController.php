<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\SchoolClass;

class SyncController extends Controller
{
    public function index()
    {
        $devices = Device::all();
        $classes = SchoolClass::with('major')->get();
        $studentsCount = \App\Models\Student::count();
        $templatesCount = \App\Models\FingerprintTemplate::count();
        return view('sync.index', compact('devices', 'classes', 'studentsCount', 'templatesCount'));
    }

    /**
     * Sebar semua data siswa dan sidik jari dari database web ke semua mesin ADMS
     */
    public function pushAll(Request $request)
    {
        $students = \App\Models\Student::all();
        $templates = \App\Models\FingerprintTemplate::all();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada data siswa di database.');
        }

        $userCount = 0;
        foreach ($students as $student) {
            \App\Http\Controllers\IclockController::queueCommand("DATA USER PIN={$student->device_user_id}\tName={$student->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");
            \App\Http\Controllers\IclockController::queueCommand("DATA UPDATE USER PIN={$student->device_user_id}\tName={$student->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");
            $userCount++;
        }

        $fpCount = 0;
        foreach ($templates as $t) {
            \App\Http\Controllers\IclockController::queueCommand("DATA FP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}");
            \App\Http\Controllers\IclockController::queueCommand("DATA UPDATE FINGERTMP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}");
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

        // Kirim perintah ADMS untuk meminta mesin mengunggah seluruh sidik jari
        \App\Http\Controllers\IclockController::queueCommand("QUERY FINGERTMP PIN=0", $targetSn);
        \App\Http\Controllers\IclockController::queueCommand("DATA QUERY FINGERTMP PIN=0", $targetSn);
        \App\Http\Controllers\IclockController::queueCommand("CHECK", $targetSn);

        return redirect()->back()->with('success', "Perintah backup sidik jari telah dikirim ke " . ($device->name ?? 'Semua Mesin') . ". Mesin akan segera mengunggah data sidik jari ke database web.");
    }
}