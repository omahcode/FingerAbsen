<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPermission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin & Guru
        $admin = User::updateOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Jurusan
        $majorPplg = Major::firstOrCreate(['name' => 'Pengembangan Perangkat Lunak dan Gim']);
        $majorTkj = Major::firstOrCreate(['name' => 'Teknik Jaringan Komputer dan Telekomunikasi']);

        // 3. Kelas
        $classRpl1 = SchoolClass::firstOrCreate(
            ['name' => 'XII PPLG 1'],
            ['major_id' => $majorPplg->id]
        );

        $classRpl2 = SchoolClass::firstOrCreate(
            ['name' => 'XII PPLG 2'],
            ['major_id' => $majorPplg->id]
        );

        // 4. Akun Wali Kelas
        $guru = User::updateOrCreate(
            ['email' => 'guru@sekolah.sch.id'],
            [
                'name' => 'Bambang Sudarsono, S.Kom',
                'password' => Hash::make('guru123'),
                'role' => 'guru',
                'school_class_id' => $classRpl1->id,
                'major_id' => $majorPplg->id,
                'email_verified_at' => now(),
            ]
        );

        // 5. Perangkat Fingerprint
        $device = Device::firstOrCreate(
            ['serial_number' => 'FP-MAIN-001'],
            [
                'name' => 'Mesin Fingerprint Gerbang',
                'ip_address' => '192.168.1.201',
                'port' => 4370,
                'status' => 'online',
            ]
        );

        // 6. Data Siswa
        $student1 = Student::updateOrCreate(
            ['nis' => '20241001'],
            [
                'name' => 'Ahmad Pratama',
                'school_class_id' => $classRpl1->id,
                'parent_phone' => '081234567891',
                'device_user_id' => '1001',
                'privilege' => 0,
            ]
        );

        $student2 = Student::updateOrCreate(
            ['nis' => '20241002'],
            [
                'name' => 'Budi Santoso',
                'school_class_id' => $classRpl1->id,
                'parent_phone' => '081234567892',
                'device_user_id' => '1002',
                'privilege' => 0,
            ]
        );

        $student3 = Student::updateOrCreate(
            ['nis' => '20241003'],
            [
                'name' => 'Citra Lestari',
                'school_class_id' => $classRpl1->id,
                'parent_phone' => '081234567893',
                'device_user_id' => '1003',
                'privilege' => 0,
            ]
        );

        $student4 = Student::updateOrCreate(
            ['nis' => '20241004'],
            [
                'name' => 'Dewi Anggraini',
                'school_class_id' => $classRpl2->id,
                'parent_phone' => '081234567894',
                'device_user_id' => '1004',
                'privilege' => 0,
            ]
        );

        // 7. Data Kehadiran Hari Ini
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        // Ahmad: Hadir tepat waktu (sedang di sekolah)
        AttendanceLog::firstOrCreate(
            [
                'student_id' => $student1->id,
                'timestamp' => "{$todayStr} 06:45:00",
            ],
            [
                'device_id' => $device->id,
                'device_user_id' => $student1->device_user_id,
                'status_code' => 0,
            ]
        );

        // Budi: Hadir tepat waktu & sudah pulang
        AttendanceLog::firstOrCreate(
            [
                'student_id' => $student2->id,
                'timestamp' => "{$todayStr} 06:50:00",
            ],
            [
                'device_id' => $device->id,
                'device_user_id' => $student2->device_user_id,
                'status_code' => 0,
            ]
        );

        AttendanceLog::firstOrCreate(
            [
                'student_id' => $student2->id,
                'timestamp' => "{$todayStr} 14:15:00",
            ],
            [
                'device_id' => $device->id,
                'device_user_id' => $student2->device_user_id,
                'status_code' => 1,
            ]
        );

        // Citra: Izin Sakit
        StudentPermission::updateOrCreate(
            [
                'student_id' => $student3->id,
                'date' => $todayStr,
            ],
            [
                'type' => 'Sakit',
                'notes' => 'Demam tinggi dan istirahat dokter.',
                'status' => 'disetujui',
            ]
        );
    }
}
