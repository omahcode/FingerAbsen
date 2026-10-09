<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Device;
use App\Http\Controllers\IclockController;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class StudentsImport implements ToModel, WithHeadingRow
{
    protected $classesCache = [];
    protected $normalizedClassesCache = [];
    protected $devices;
    
    public $importedCount = 0;
    public $skippedCount = 0;
    public $errors = [];

    public function __construct()
    {
        $this->devices = Device::all();
        
        $classes = SchoolClass::all();
        foreach ($classes as $class) {
            $rawName = trim($class->name);
            $lowerName = strtolower($rawName);
            $normalized = preg_replace('/[^a-z0-9]/', '', $lowerName);

            $this->classesCache[$lowerName] = $class->id;
            if (!empty($normalized)) {
                $this->normalizedClassesCache[$normalized] = $class->id;
            }
        }
    }

    public function model(array $row): \Illuminate\Database\Eloquent\Model|array|null
    {
        // 1. Ambil kolom NIS dengan berbagai kemungkinan nama header
        $rawNis = $row['nis'] ?? $row['device_user_id'] ?? $row['nisn'] ?? $row['pin'] ?? $row['id'] ?? $row['no_induk'] ?? $row['user_id'] ?? null;
        
        // 2. Ambil kolom Nama Siswa dengan berbagai kemungkinan nama header
        $rawName = $row['nama_siswa'] ?? $row['nama'] ?? $row['name'] ?? $row['nama_lengkap'] ?? $row['nama_murid'] ?? null;
        
        // 3. Ambil kolom Kelas dengan berbagai kemungkinan nama header
        $rawClass = $row['kelas'] ?? $row['nama_kelas'] ?? $row['class'] ?? $row['school_class'] ?? null;
        
        // 4. Ambil No HP Ortu (opsional)
        $parentPhone = $row['no_hp_ortu'] ?? $row['no_hp'] ?? $row['no_wa'] ?? $row['whatsapp'] ?? $row['no_telepon_ortu'] ?? $row['parent_phone'] ?? $row['no_telepon'] ?? null;

        // Bersihkan NIS (bila berupa numeric/float/string)
        $nis = is_numeric($rawNis) ? (string) intval($rawNis) : trim((string) $rawNis);
        $name = trim((string) $rawName);
        $className = trim((string) $rawClass);

        // Jika baris kosong atau tidak memiliki data inti, lewati
        if (empty($nis) || empty($name) || empty($className)) {
            $this->skippedCount++;
            return null;
        }

        // Cari ID Kelas (exact match case-insensitive atau fuzzy normalized)
        $lowerClass = strtolower($className);
        $normalizedClass = preg_replace('/[^a-z0-9]/', '', $lowerClass);
        
        $classId = $this->classesCache[$lowerClass] ?? ($this->normalizedClassesCache[$normalizedClass] ?? null);

        if (!$classId) {
            $this->skippedCount++;
            $this->errors[] = "Siswa '{$name}' (NIS: {$nis}): Kelas '{$className}' tidak ditemukan di sistem.";
            Log::warning("Import Excel: Kelas '{$className}' tidak ditemukan untuk siswa {$name} (NIS: {$nis}).");
            return null;
        }

        // 5. Kirim perintah antrean ADMS (Iclock)
        try {
            IclockController::queueCommand("DATA USER PIN={$nis}\tName={$name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");
            IclockController::queueCommand("DATA UPDATE USER PIN={$nis}\tName={$name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");
        } catch (\Exception $e) {
            Log::error("Import Excel ADMS Queue Error: " . $e->getMessage());
        }

        // 6. Sinkronisasi via Python Service jika aktif (dengan timeout singkat agar tidak memicu bottleneck)
        $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
        foreach ($this->devices as $device) {
            try {
                Http::timeout(1)->post($serviceUrl . '/api/set_user', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port,
                    'device_user_id' => $nis,
                    'name' => $name,
                    'privilege' => 0
                ]);
            } catch (\Exception $e) {
                // Abaikan jika service offline agar proses import tetap cepat dan lancar
            }
        }

        $this->importedCount++;

        // 7. Simpan atau Update ke Database
        return Student::updateOrCreate(
            ['device_user_id' => $nis],
            [
                'name' => $name,
                'school_class_id' => $classId,
                'parent_phone' => $parentPhone ? trim((string) $parentPhone) : null,
                'privilege' => '0'
            ]
        );
    }
}