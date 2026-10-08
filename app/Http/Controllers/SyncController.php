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
            $fpCount++;
        }

        return redirect()->back()->with('success', "Berhasil memasukkan {$userCount} data siswa dan {$fpCount} template sidik jari ke antrean sinkronisasi seluruh mesin.");
    }
}