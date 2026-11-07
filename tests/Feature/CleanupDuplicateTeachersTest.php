<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
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

        // Skenario nyata Pak Agus: NIP ada, tapi device_user_id null
        $teacher = Teacher::create([
            'name' => 'Agus Triwidiyanto, S.T.',
            'nip' => '197808222010011015',
            'category' => 'GURU UMUM',
            'device_user_id' => null,
        ]);

        // Siswa nyasar di kelas admin bernama 'Agus' dengan PIN 197808222010011015
        $duplicateStudent = Student::create([
            'name' => 'Agus',
            'school_class_id' => $classAdmin->id,
            'device_user_id' => '197808222010011015',
            'privilege' => '0',
        ]);

        $device = Device::create([
            'name' => 'Mesin 1',
            'ip_address' => '127.0.0.1',
            'port' => 4370,
        ]);

        // Log dan 2 template yang terhubung ke student duplikat
        $log = AttendanceLog::create([
            'device_id' => $device->id,
            'device_user_id' => '197808222010011015',
            'student_id' => $duplicateStudent->id,
            'timestamp' => '2026-11-07 07:00:00',
            'status_code' => 0,
        ]);

        $template1 = FingerprintTemplate::create([
            'student_id' => $duplicateStudent->id,
            'device_user_id' => '197808222010011015',
            'finger_index' => 0,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_AGUS_1',
        ]);

        $template2 = FingerprintTemplate::create([
            'student_id' => $duplicateStudent->id,
            'device_user_id' => '197808222010011015',
            'finger_index' => 1,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_AGUS_2',
        ]);

        // Jalankan perintah pembersihan
        $this->artisan('cleanup:duplicate-teachers')
            ->assertSuccessful();

        $teacher->refresh();

        // 1. PIN Guru harus otomatis terisi NIP
        $this->assertEquals('197808222010011015', $teacher->device_user_id);

        // 2. Siswa duplikat harus sudah terhapus
        $this->assertDatabaseMissing('students', [
            'id' => $duplicateStudent->id,
        ]);

        // 3. Kedua fingerprint template harus dialihkan ke teacher
        $this->assertDatabaseHas('fingerprint_templates', [
            'id' => $template1->id,
            'student_id' => null,
            'teacher_id' => $teacher->id,
            'device_user_id' => '197808222010011015',
        ]);

        $this->assertDatabaseHas('fingerprint_templates', [
            'id' => $template2->id,
            'student_id' => null,
            'teacher_id' => $teacher->id,
            'device_user_id' => '197808222010011015',
        ]);

        // Guru sekarang harus memiliki 2 sidik jari
        $this->assertEquals(2, $teacher->fingerprintTemplates()->count());
        $this->assertTrue($teacher->has_fingerprint);

        // 4. Attendance log harus dialihkan ke teacher
        $this->assertDatabaseHas('attendance_logs', [
            'id' => $log->id,
            'student_id' => null,
            'teacher_id' => $teacher->id,
        ]);

        // 5. Kelas 'admin' dan jurusan 'admin' yang kini kosong harus terhapus
        $this->assertDatabaseMissing('school_classes', [
            'id' => $classAdmin->id,
        ]);

        $this->assertDatabaseMissing('majors', [
            'id' => $majorAdmin->id,
        ]);
    }

    public function test_cleanup_handles_pak_agus_with_pin_1_and_shows_fingerprints_in_teacher_list(): void
    {
        // Skenario nyata server: Guru Pak Agus Triwidiyanto NIP 197808222010011015
        $teacher = Teacher::create([
            'name' => 'Agus Triwidiyanto, S.T.',
            'nip' => '197808222010011015',
            'category' => 'GURU UMUM',
            'device_user_id' => '197808222010011015',
        ]);

        $major = Major::create(['name' => 'RPL']);
        $class = SchoolClass::create(['name' => 'X RPL 1', 'major_id' => $major->id]);

        $student = Student::create([
            'id' => 239,
            'name' => 'Agus',
            'device_user_id' => '1',
            'school_class_id' => $class->id,
            'privilege' => '0',
        ]);

        // Di mesin PIN-nya 1, ditarik sebagai siswa ID 239
        $template1 = FingerprintTemplate::create([
            'student_id' => $student->id,
            'device_user_id' => '1',
            'finger_index' => 0,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_PIN1_F0',
        ]);

        $template2 = FingerprintTemplate::create([
            'student_id' => $student->id,
            'device_user_id' => '1',
            'finger_index' => 1,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_PIN1_F1',
        ]);

        $this->artisan('cleanup:duplicate-teachers')
            ->assertSuccessful();

        $teacher->refresh();

        // PIN guru disesuaikan ke PIN mesin fisiknya (PIN: 1)
        $this->assertEquals('1', $teacher->device_user_id);
        $this->assertEquals(2, $teacher->fingerprintTemplates()->count());
        $this->assertTrue($teacher->has_fingerprint);

        // Pastikan di halaman Data Guru, Pak Agus terhitung memiliki 2 sidik jari
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('teachers.index'));

        $response->assertStatus(200);
        $response->assertSee('Agus Triwidiyanto, S.T.');
        $response->assertSee('Terdaftar');
        $response->assertSee('2');
    }
}
