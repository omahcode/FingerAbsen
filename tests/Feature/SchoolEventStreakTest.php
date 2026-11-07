<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\SchoolEvent;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolEventStreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_10_camping_event_preserves_streak_while_grade_11_must_attend(): void
    {
        $device = Device::create([
            'name' => 'Mesin Utama',
            'serial_number' => 'DEV001',
            'ip_address' => '192.168.1.201',
            'port' => 4370,
        ]);

        // 1. Buat Jurusan dan Kelas (Kelas 10 dan Kelas 11)
        $major = Major::create(['name' => 'PPLG']);
        $class10 = SchoolClass::create(['name' => 'X PPLG 1', 'major_id' => $major->id]);
        $class11 = SchoolClass::create(['name' => 'XI PPLG 1', 'major_id' => $major->id]);

        $this->assertEquals('10', $class10->grade_level);
        $this->assertEquals('11', $class11->grade_level);

        // 2. Buat Siswa Kelas 10 dan Siswa Kelas 11
        $student10 = Student::create([
            'name' => 'Budi Santoso (Kelas 10)',
            'nis' => '10001',
            'device_user_id' => 10001,
            'school_class_id' => $class10->id,
        ]);

        $student11 = Student::create([
            'name' => 'Rina Wijaya (Kelas 11)',
            'nis' => '11001',
            'device_user_id' => 11001,
            'school_class_id' => $class11->id,
        ]);

        // Absensi Senin 12 Okt & Selasa 13 Okt
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student10->id, 'device_user_id' => 10001, 'status_code' => 0, 'timestamp' => '2026-10-12 06:45:00']);
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student11->id, 'device_user_id' => 11001, 'status_code' => 0, 'timestamp' => '2026-10-12 06:45:00']);

        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student10->id, 'device_user_id' => 10001, 'status_code' => 0, 'timestamp' => '2026-10-13 06:50:00']);
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student11->id, 'device_user_id' => 11001, 'status_code' => 0, 'timestamp' => '2026-10-13 06:50:00']);

        // Rina (Kelas 11) hadir Rabu & Kamis
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student11->id, 'device_user_id' => 11001, 'status_code' => 0, 'timestamp' => '2026-10-14 06:40:00']);
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student11->id, 'device_user_id' => 11001, 'status_code' => 0, 'timestamp' => '2026-10-15 06:40:00']);
        // Rina TIDAK hadir hari Jumat 16 Okt (bolos)

        // Buat SchoolEvent: Kemah Ekspram Khusus Tingkat Kelas 10 (Rabu-Jumat, 14-16 Okt)
        SchoolEvent::create([
            'title' => 'Kemah Blok Pramuka Kelas 10',
            'type' => 'event',
            'target_type' => 'grade',
            'grade_level' => '10',
            'start_date' => '2026-10-14',
            'end_date' => '2026-10-16',
            'preserve_streak' => true,
        ]);

        // Cek pada hari Jumat 16 Okt (saat kemah sedang berlangsung):
        // Budi (Kelas 10) streak-nya harus tetap 2 (Senin & Selasa), tidak hilang!
        $this->assertEquals(2, $student10->calculateConsecutiveStreak('2026-10-16'));

        // Absensi Senin 19 Okt
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student10->id, 'device_user_id' => 10001, 'status_code' => 0, 'timestamp' => '2026-10-19 06:45:00']);
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student11->id, 'device_user_id' => 11001, 'status_code' => 0, 'timestamp' => '2026-10-19 06:45:00']);

        // Budi (Kelas 10) hadir Senin 19 Okt:
        // Streak = Senin 19 Okt (1) + skip weekend + skip kemah 3 hari + Selasa 13 Okt (1) + Senin 12 Okt (1) = 3!
        $this->assertEquals(3, $student10->calculateConsecutiveStreak('2026-10-19'));

        // Sedangkan Rina (Kelas 11): karena bolos hari Jumat 16 Okt, streak-nya putus!
        // Rina hanya dapat streak 1 (dari kehadiran hari Senin 19 Okt saja).
        $this->assertEquals(1, $student11->calculateConsecutiveStreak('2026-10-19'));
    }

    public function test_two_weeks_school_holiday_preserves_streak_for_all_students(): void
    {
        $device = Device::create([
            'name' => 'Mesin Utama',
            'serial_number' => 'DEV002',
            'ip_address' => '192.168.1.202',
            'port' => 4370,
        ]);

        $major = Major::create(['name' => 'PPLG']);
        $class = SchoolClass::create(['name' => 'X PPLG 1', 'major_id' => $major->id]);

        $student = Student::create([
            'name' => 'Ahmad Dhani',
            'nis' => '10002',
            'device_user_id' => 10002,
            'school_class_id' => $class->id,
        ]);

        // Siswa hadir hari Jumat sebelum libur (2026-12-18)
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student->id, 'device_user_id' => 10002, 'status_code' => 0, 'timestamp' => '2026-12-18 06:45:00']);
        // Dan hari Kamis sebelumnya (2026-12-17)
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student->id, 'device_user_id' => 10002, 'status_code' => 0, 'timestamp' => '2026-12-17 06:45:00']);

        // Buat Libur Sekolah 2 Minggu (2026-12-19 s/d 2027-01-03)
        SchoolEvent::create([
            'title' => 'Libur Semester Ganjil (2 Minggu)',
            'type' => 'holiday',
            'target_type' => 'all',
            'start_date' => '2026-12-19',
            'end_date' => '2027-01-03',
            'preserve_streak' => true,
        ]);

        // Cek Setting::isSchoolDay selama masa libur harus false
        $this->assertFalse(Setting::isSchoolDay('2026-12-22')); // Selasa dalam libur
        $this->assertFalse(Setting::isSchoolDay('2026-12-29')); // Selasa dalam libur

        // Siswa masuk hari pertama sekolah di semester baru (Senin 2027-01-04)
        AttendanceLog::create(['device_id' => $device->id, 'student_id' => $student->id, 'device_user_id' => 10002, 'status_code' => 0, 'timestamp' => '2027-01-04 06:40:00']);

        // Streak harus 3 (Senin 4 Jan + libur 2 minggu dilewati + Jumat 18 Des + Kamis 17 Des)
        $this->assertEquals(3, $student->calculateConsecutiveStreak('2027-01-04'));
    }

    public function test_authenticated_user_can_create_and_delete_school_event(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('school-events.store'), [
            'title' => 'Kemah Akhir Tahun Kelas 10',
            'type' => 'event',
            'target_type' => 'grade',
            'grade_level' => '10',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'preserve_streak' => '1',
            'description' => 'Kegiatan kepramukaan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_events', [
            'title' => 'Kemah Akhir Tahun Kelas 10',
            'grade_level' => '10',
            'preserve_streak' => 1,
        ]);

        $event = SchoolEvent::where('title', 'Kemah Akhir Tahun Kelas 10')->first();
        $deleteResponse = $this->actingAs($user)->delete(route('school-events.destroy', $event->id));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('school_events', [
            'id' => $event->id,
        ]);
    }
}
