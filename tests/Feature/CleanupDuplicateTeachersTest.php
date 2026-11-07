<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupDuplicateTeachersTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_removes_duplicate_students_matching_teachers_and_cleans_admin_class(): void
    {
        $majorAdmin = Major::create(['name' => 'admin']);
        $classAdmin = SchoolClass::create([
            'major_id' => $majorAdmin->id,
            'name' => 'admin',
        ]);

        $teacher = Teacher::create([
            'name' => 'Budi Guru',
            'nip' => '19800101',
            'category' => 'GURU INFORMATIKA',
            'device_user_id' => '999',
        ]);

        // Siswa duplikat yang memiliki PIN sama dengan guru di kelas admin
        $duplicateStudent = Student::create([
            'name' => 'Budi Guru',
            'school_class_id' => $classAdmin->id,
            'device_user_id' => '999',
            'privilege' => '0',
        ]);

        $device = Device::create([
            'name' => 'Mesin 1',
            'ip_address' => '127.0.0.1',
            'port' => 4370,
        ]);

        // Log dan template yang terhubung ke student duplikat
        $log = AttendanceLog::create([
            'device_id' => $device->id,
            'device_user_id' => '999',
            'student_id' => $duplicateStudent->id,
            'timestamp' => '2026-11-07 07:00:00',
            'status_code' => 0,
        ]);

        $template = FingerprintTemplate::create([
            'student_id' => $duplicateStudent->id,
            'device_user_id' => '999',
            'finger_index' => 0,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_999',
        ]);

        // Jalankan perintah pembersihan
        $this->artisan('cleanup:duplicate-teachers')
            ->assertSuccessful();

        // 1. Siswa duplikat harus sudah terhapus
        $this->assertDatabaseMissing('students', [
            'id' => $duplicateStudent->id,
        ]);

        // 2. Attendance log dan template sidik jari harus dialihkan ke teacher
        $this->assertDatabaseHas('attendance_logs', [
            'id' => $log->id,
            'student_id' => null,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertDatabaseHas('fingerprint_templates', [
            'id' => $template->id,
            'student_id' => null,
            'teacher_id' => $teacher->id,
        ]);

        // 3. Kelas 'admin' dan jurusan 'admin' yang kini kosong harus terhapus
        $this->assertDatabaseMissing('school_classes', [
            'id' => $classAdmin->id,
        ]);

        $this->assertDatabaseMissing('majors', [
            'id' => $majorAdmin->id,
        ]);
    }
}
