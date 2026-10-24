<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SyncController extends Controller
{
    public function index()
    {
        $devices = Device::all();
        $classes = SchoolClass::withCount('students')->with('major')->orderBy('name')->get();
        $studentsCount = Student::count();
        $teachersCount = Teacher::count();
        $templatesCount = FingerprintTemplate::count();
        $admsLogs = Cache::get('adms_recent_logs', []);

        return view('sync.index', compact('devices', 'classes', 'studentsCount', 'teachersCount', 'templatesCount', 'admsLogs'));
    }

    /**
     * Sebar data pengguna (siswa atau guru) dan template sidik jari ke mesin ADMS secara kustom & terukur (batching).
     */
    public function push(Request $request)
    {
        $request->validate([
            'target_type' => 'nullable|in:students,teachers',
            'school_class_id' => 'nullable',
            'device_id' => 'nullable',
            'batch_size' => 'nullable',
            'batch_page' => 'nullable|integer|min:1',
            'custom_offset' => 'nullable|integer|min:0',
            'custom_limit' => 'nullable|integer|min:1|max:1000',
            'include_fingerprints' => 'nullable',
        ]);

        $targetType = $request->input('target_type', 'students');
        $includeTemplates = $request->has('include_fingerprints') ? $request->boolean('include_fingerprints') : true;

        // Siapkan Query
        if ($targetType === 'teachers') {
            $query = Teacher::query()->orderBy('id');
            $targetLabel = 'Dewan Guru';
        } else {
            $query = Student::query()->orderBy('id');
            $classId = $request->input('school_class_id');

            if (! empty($classId) && $classId !== 'all') {
                $class = SchoolClass::find($classId);
                if ($class) {
                    $query->where('school_class_id', $class->id);
                    $targetLabel = "Siswa Kelas {$class->name}";
                } else {
                    $targetLabel = 'Siswa';
                }
            } else {
                $targetLabel = 'Seluruh Siswa';
            }
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            return redirect()->back()->with('error', "Tidak ada data {$targetLabel} yang ditemukan untuk disebarkan.");
        }

        // Tentukan Range Offset & Limit
        $customLimit = $request->filled('custom_limit') ? (int) $request->input('custom_limit') : null;
        $customOffset = $request->filled('custom_offset') ? (int) $request->input('custom_offset') : null;

        if ($customLimit !== null) {
            $limit = $customLimit;
            $offset = $customOffset ?? 0;
            $currentPage = null;
        } else {
            $batchSizeInput = $request->input('batch_size', '50');
            if ($batchSizeInput === 'all') {
                $limit = null;
                $offset = 0;
                $currentPage = 1;
            } else {
                $batchSize = max(1, (int) $batchSizeInput);
                $currentPage = max(1, (int) $request->input('batch_page', 1));
                $limit = $batchSize;
                $offset = ($currentPage - 1) * $batchSize;
            }
        }

        if ($offset >= $totalCount) {
            return redirect()->back()->with('error', "Offset antrean ({$offset}) melebihi total data yang ada ({$totalCount} orang). Silakan pilih nomor batch yang sesuai.");
        }

        if ($limit !== null) {
            $items = $query->skip($offset)->take($limit)->get();
        } else {
            $items = $query->get();
        }

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data yang dapat diproses pada batch ini.');
        }

        // Tentukan Target Mesin
        $deviceId = $request->input('device_id');
        $targetSn = null;
        $targetDeviceName = 'Semua Mesin (Sesuai Peruntukan)';
        $roleFilter = $targetType; // default broadcast filter

        if (! empty($deviceId) && $deviceId !== 'all') {
            $device = Device::find($deviceId);
            if ($device) {
                $targetSn = $device->serial_number;
                $targetDeviceName = "Mesin {$device->name} ({$device->serial_number})";
                $roleFilter = null; // targetkan langsung ke nomor SN mesin spesifik
            }
        }

        // 1. Antrekan Data Pengguna
        $userCount = 0;
        foreach ($items as $item) {
            if ($targetType === 'students') {
                IclockController::queueCommand("DATA USER PIN={$item->device_user_id}\tName={$item->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0", $targetSn, null, $roleFilter);
                IclockController::queueCommand("DATA UPDATE USER PIN={$item->device_user_id}\tName={$item->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0", $targetSn, null, $roleFilter);
            } else {
                IclockController::queueCommand("DATA USER PIN={$item->device_user_id}\tName={$item->name}\tPri=0\tGrp=1", $targetSn, null, $roleFilter);
                IclockController::queueCommand("DATA UPDATE USER PIN={$item->device_user_id}\tName={$item->name}\tPri=0\tGrp=1", $targetSn, null, $roleFilter);
            }
            $userCount++;
        }

        // 2. Antrekan Template Sidik Jari (Hanya milik orang-orang di batch ini)
        $fpCount = 0;
        if ($includeTemplates) {
            $pins = $items->pluck('device_user_id')->filter()->unique();
            if ($pins->isNotEmpty()) {
                $templates = FingerprintTemplate::whereIn('device_user_id', $pins)->get();
                foreach ($templates as $t) {
                    IclockController::queueCommand("DATA FP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}", $targetSn, null, $roleFilter);
                    IclockController::queueCommand("DATA UPDATE FINGERTMP PIN={$t->device_user_id}\tFID={$t->finger_index}\tSize={$t->size}\tValid=1\tTMP={$t->template_data}", $targetSn, null, $roleFilter);
                    $fpCount++;
                }
            }
        }

        $startIdx = $offset + 1;
        $endIdx = $offset + $items->count();
        $remaining = $totalCount - $endIdx;

        $batchTip = '';
        if ($remaining > 0 && $limit !== null && $currentPage !== null) {
            $nextPage = $currentPage + 1;
            $nextStart = $endIdx + 1;
            $nextEnd = min($totalCount, $endIdx + $limit);
            $batchTip = " • Masih ada {$remaining} orang tersisa. Lanjutkan dengan memilih Batch {$nextPage} (urutan {$nextStart}-{$nextEnd}).";
        }

        $feedbackMsg = "⚡ Berhasil menjadwalkan {$userCount} data {$targetLabel} (urutan ke-{$startIdx} s/d {$endIdx} dari total {$totalCount}) dan {$fpCount} template sidik jari ke antrean {$targetDeviceName}.{$batchTip}";

        return redirect()->back()->with('success', $feedbackMsg);
    }

    /**
     * Kompatibilitas untuk rute lama pushAll
     */
    public function pushAll(Request $request)
    {
        return $this->push($request);
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
