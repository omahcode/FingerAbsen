<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDepartureMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolClass $class;

    private Student $student;

    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $major = Major::create(['name' => 'Rekayasa Perangkat Lunak']);
        $this->class = SchoolClass::create([
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
        ]);

        $this->student = Student::create([
            'name' => 'Ahmad Santoso',
            'school_class_id' => $this->class->id,
            'parent_phone' => '081234567890',
            'device_user_id' => '101',
            'privilege' => 0,
        ]);

        $this->device = Device::create([
            'name' => 'Mesin Utama',
            'ip_address' => '192.168.1.201',
            'port' => 4370,
            'status' => 'online',
            'serial_number' => 'DEV-TEST-001',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_departure_monitoring(): void
    {
        $response = $this->get(route('monitoring.kepulangan'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_departure_monitoring_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('monitoring.kepulangan'));

        $response->assertOk();
        $response->assertSee('Monitoring Kepulangan Siswa');
        $response->assertSee('Ahmad Santoso');
        $response->assertSee('Belum Hadir');
    }

    public function test_monitoring_holds_masuk_status_when_student_has_only_checked_in(): void
    {
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        // Siswa tap masuk pukul 06:20 WIB (status_code = 0)
        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => "{$today} 06:20:00",
            'status_code' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('monitoring.kepulangan'));

        $response->assertOk();
        $response->assertSee('MASUK (DI SEKOLAH)');
        $response->assertSee('06:20 WIB');

        $jsonResponse = $this->actingAs($this->admin)->getJson(route('monitoring.kepulangan.data'));
        $jsonResponse->assertOk();
        $students = collect($jsonResponse->json('students'));
        $target = $students->firstWhere('id', $this->student->id);
        $this->assertEquals('in_school', $target['status_code']);
        $this->assertEquals('Masuk (Di Sekolah)', $target['status_label']);
        $this->assertNull($target['jam_pulang']);
    }

    public function test_monitoring_switches_to_pulang_status_when_student_fingerprints_out(): void
    {
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        // Siswa tap masuk pagi hari (status_code = 0)
        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => "{$today} 06:20:00",
            'status_code' => 0,
        ]);

        // Kemudian siswa tap pulang (status_code = 1)
        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => "{$today} 14:15:00",
            'status_code' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('monitoring.kepulangan'));

        $response->assertOk();
        $response->assertSee('SUDAH PULANG');
        $response->assertSee('06:20 WIB');
        $response->assertSee('14:15 WIB');
    }

    public function test_realtime_data_endpoint_returns_json_with_correct_status_and_stats(): void
    {
        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');

        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => "{$today} 06:20:00",
            'status_code' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('monitoring.kepulangan.data'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'stats' => [
                'total' => 1,
                'attended_today' => 1,
                'in_school' => 1,
                'departed' => 0,
                'not_present' => 0,
            ],
        ]);
    }

    public function test_direct_alias_route_redirects_to_monitoring_kepulangan(): void
    {
        $response = $this->actingAs($this->admin)->get('/monitoring-pulang');
        $response->assertRedirect(route('monitoring.kepulangan'));
    }
}
