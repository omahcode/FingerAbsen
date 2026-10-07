<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Student;
use App\Models\AttendanceLog;
use App\Models\FingerprintTemplate;
use Illuminate\Support\Facades\Http;

class SyncDevices extends Command
{
    protected $signature = 'sync:devices';
    protected $description = 'Otomatis tarik absen dan sinkron template sidik jari antar mesin';

    public function handle()
    {
        $devices = Device::all();
        if ($devices->count() == 0) {
            $this->info("Tidak ada mesin yang terdaftar.");
            return;
        }

        foreach ($devices as $device) {
            $this->info("Memproses mesin: " . $device->name);
            
            // 1. Tarik Absensi
            try {
                $resAbsen = Http::timeout(20)->post('http://127.0.0.1:5000/api/get_attendance', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port
                ]);
                
                if ($resAbsen->successful() && $resAbsen->json('status') === 'success') {
                    $attendances = $resAbsen->json('attendance');
                    $newCount = 0;
                    foreach ($attendances as $att) {
                        if (!$att['user_id']) continue;
                        $student = Student::where('device_user_id', (string) $att['user_id'])->first();
                        $existingLog = AttendanceLog::withTrashed()->where([
                            'device_id' => $device->id,
                            'device_user_id' => (string) $att['user_id'],
                            'timestamp' => $att['timestamp']
                        ])->first();

                        if (!$existingLog) {
                            AttendanceLog::create([
                                'device_id' => $device->id,
                                'device_user_id' => (string) $att['user_id'],
                                'timestamp' => $att['timestamp'],
                                'student_id' => $student ? $student->id : null,
                                'status_code' => $att['status'] ?? 0
                            ]);
                            $newCount++;
                        }
                    }
                    $this->info("- Ditarik $newCount absensi baru.");
                }
            } catch (\Exception $e) {
                $this->error("- Gagal tarik absen dari {$device->name}.");
            }

            // 2. Tarik Template Sidik Jari
            try {
                $resTemp = Http::timeout(20)->post('http://127.0.0.1:5000/api/get_templates', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port
                ]);

                if ($resTemp->successful() && $resTemp->json('status') === 'success') {
                    $templates = $resTemp->json('templates');
                    $newTempCount = 0;
                    foreach ($templates as $t) {
                        $student = Student::where('device_user_id', (string) $t['uid'])->first();
                        if (!$student) continue;

                        $finger = FingerprintTemplate::where('device_user_id', (string) $t['uid'])
                            ->where('finger_index', $t['fid'])->first();

                        if (!$finger) {
                            FingerprintTemplate::create([
                                'student_id' => $student->id,
                                'device_user_id' => (string) $t['uid'],
                                'finger_index' => $t['fid'],
                                'size' => $t['size'] ?? 0,
                                'valid' => $t['valid'],
                                'template_data' => $t['template']
                            ]);
                            $newTempCount++;
                        }
                    }
                    $this->info("- Ditarik $newTempCount sidik jari baru.");
                }
            } catch (\Exception $e) {
                $this->error("- Gagal tarik template sidik jari dari {$device->name}.");
            }
        }

        // 3. Broadcast ke Semua Mesin
        $this->info("Menyebarkan sidik jari ke seluruh mesin...");
        $allTemplates = FingerprintTemplate::with('student')->get();
        
        foreach ($devices as $device) {
            $pushCount = 0;
            foreach ($allTemplates as $t) {
                if (!$t->student) continue;
                try {
                    $resPush = Http::timeout(5)->post('http://127.0.0.1:5000/api/set_template', [
                        'ip_address' => $device->ip_address,
                        'port' => $device->port,
                        'device_user_id' => $t->device_user_id,
                        'name' => $t->student->name,
                        'privilege' => $t->student->privilege,
                        'fid' => $t->finger_index,
                        'valid' => $t->valid,
                        'template' => $t->template_data
                    ]);
                    if ($resPush->successful() && $resPush->json('status') === 'success') {
                        $pushCount++;
                    }
                } catch (\Exception $e) {
                    // Abaikan gagal push untuk perulangan
                }
            }
            $this->info("- Selesai push $pushCount template ke {$device->name}.");
        }
        
        $this->info("Proses Sinkronisasi Otomatis Selesai!");
    }
}