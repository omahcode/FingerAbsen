<?php

namespace App\Imports;

use App\Http\Controllers\IclockController;
use App\Models\Device;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TeachersImport implements ToModel, WithHeadingRow
{
    protected $devices;

    public int $importedCount = 0;

    public int $skippedCount = 0;

    public array $errors = [];

    public function __construct()
    {
        $this->devices = Device::all();
    }

    public function model(array $row): Model|array|null
    {
        // 1. Ambil kolom NIP
        $rawNip = $row['nip'] ?? $row['nip_guru'] ?? $row['no_induk'] ?? $row['pin'] ?? $row['device_user_id'] ?? $row['id'] ?? null;

        // 2. Ambil kolom Nama Guru
        $rawName = $row['nama_guru'] ?? $row['nama'] ?? $row['name'] ?? $row['nama_lengkap'] ?? $row['guru'] ?? null;

        // 3. Ambil kolom Kategori (Mapel/Bidang)
        $rawCategory = $row['kategori'] ?? $row['kategori_guru'] ?? $row['bidang'] ?? $row['rumpun'] ?? $row['mapel'] ?? $row['category'] ?? 'GURU UMUM';

        // 4. Ambil No Telepon / WhatsApp
        $rawPhone = $row['no_hp'] ?? $row['no_wa'] ?? $row['whatsapp'] ?? $row['phone'] ?? $row['telepon'] ?? $row['no_telepon'] ?? null;

        // 5. Ambil PIN Mesin (Opsional)
        $rawPin = $row['pin_mesin'] ?? $row['pin'] ?? $row['device_user_id'] ?? null;

        // Bersihkan NIP & Nama
        $nip = is_numeric($rawNip) ? (string) intval($rawNip) : trim((string) $rawNip);
        $name = trim((string) $rawName);
        $category = strtoupper(trim((string) $rawCategory));
        if (empty($category)) {
            $category = 'GURU UMUM';
        }

        $phone = ! empty($rawPhone) ? trim((string) $rawPhone) : null;
        $pin = ! empty($rawPin) ? (string) $rawPin : $nip;

        // Jika NIP atau Nama kosong, lewati baris ini
        if (empty($nip) || empty($name)) {
            $this->skippedCount++;

            return null;
        }

        // Simpan / Perbarui data Guru
        $teacher = Teacher::updateOrCreate(
            ['nip' => $nip],
            [
                'name' => $name,
                'category' => $category,
                'device_user_id' => $pin,
                'phone' => $phone,
            ]
        );

        // Kirim perintah antrean ADMS ke mesin fingerprint
        try {
            IclockController::queueCommand("DATA USER PIN={$pin}\tName={$name}\tPri=0\tGrp=1");
            IclockController::queueCommand("DATA UPDATE USER PIN={$pin}\tName={$name}\tPri=0\tGrp=1");
        } catch (\Throwable $e) {
            Log::error('Import Excel Teacher ADMS Queue Error: '.$e->getMessage());
        }

        // Sinkronisasi via Python Service jika aktif
        $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
        foreach ($this->devices as $device) {
            try {
                Http::timeout(1)->post($serviceUrl.'/api/set_user', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port,
                    'uid' => $teacher->id,
                    'name' => $name,
                    'privilege' => 0,
                    'password' => '',
                    'group_id' => '',
                    'user_id' => $pin,
                ]);
            } catch (\Throwable $e) {
                // Abaikan jika service python offline
            }
        }

        $this->importedCount++;

        return $teacher;
    }
}
