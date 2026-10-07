<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class StudentsImport implements ToModel, WithHeadingRow
{
    protected $classesCache;
    protected $devices;

    public function __construct()
    {
        $this->devices = Device::all();
        $this->classesCache = SchoolClass::all()->mapWithKeys(function ($item) {
            return [strtolower(trim($item->name)) => $item->id];
        })->toArray();
    }

    public function model(array $row): \Illuminate\Database\Eloquent\Model|array|null
    {
        if (empty($row['nis']) || empty($row['nama_siswa']) || empty($row['kelas'])) {
            return null; 
        }

        $className = strtolower(trim($row['kelas']));
        $classId = $this->classesCache[$className] ?? null;

        if (!$classId) {
            Log::warning("Import Excel: Kelas '{$row['kelas']}' tidak ditemukan untuk siswa {$row['nama_siswa']}.");
            return null;
        }

        $nis = $row['nis'];
        $name = $row['nama_siswa'];

        // Sebar data ke semua mesin
        foreach ($this->devices as $device) {
            try {
                Http::timeout(5)->post('http://127.0.0.1:5000/api/set_user', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port,
                    'device_user_id' => $nis,
                    'name' => $name,
                    'privilege' => 0
                ]);
            } catch (\Exception $e) {
                // Abaikan mesin error agar tidak menghentikan proses
                Log::error("Import Excel: Gagal kirim $name ke mesin $device->name.");
            }
        }

        // Simpan ke Database
        return Student::updateOrCreate(
            ['device_user_id' => $nis],
            [
                'name' => $name,
                'school_class_id' => $classId,
                'privilege' => '0'
            ]
        );
    }
}