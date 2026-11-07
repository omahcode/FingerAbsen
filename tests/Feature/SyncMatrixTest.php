<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceFingerprintTemplate;
use App\Models\FingerprintTemplate;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_matrix_page(): void
    {
        $device1 = Device::create([
            'name' => 'Mesin Gerbang',
            'ip_address' => '192.168.1.201',
            'port' => 4370,
            'serial_number' => 'SN_GERBANG_01',
        ]);

        $device2 = Device::create([
            'name' => 'Mesin Lab',
            'ip_address' => '192.168.1.202',
            'port' => 4370,
            'serial_number' => 'SN_LAB_02',
        ]);

        $major = Major::create(['name' => 'RPL']);
        $class = SchoolClass::create(['name' => 'XII RPL 1', 'major_id' => $major->id]);

        $student = Student::create([
            'name' => 'Budi Santoso',
            'device_user_id' => '101',
            'school_class_id' => $class->id,
            'privilege' => '0',
        ]);

        // Template di database
        $fp = FingerprintTemplate::create([
            'student_id' => $student->id,
            'device_user_id' => '101',
            'finger_index' => 0,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_BUDI',
        ]);

        // Tercatat ada di Mesin 1 tapi belum ada di Mesin 2
        DeviceFingerprintTemplate::create([
            'device_id' => $device1->id,
            'device_user_id' => '101',
            'finger_index' => 0,
            'fingerprint_template_id' => $fp->id,
            'status' => 'synced',
        ]);

        $response = $this->actingAs($this->admin)->get(route('sync.matrix'));

        $response->assertStatus(200);
        $response->assertSee('Cek Sebaran Sidik Jari Antar Mesin');
        $response->assertSee('Mesin Gerbang');
        $response->assertSee('Mesin Lab');
        $response->assertSee('Budi Santoso');
        $response->assertSee('101');
        $response->assertSee('Ada (1)');
        $response->assertSee('Belum Ada');
        $response->assertSee('Kurang 1 Mesin');
    }

    public function test_admin_can_sync_missing_templates_to_devices(): void
    {
        $device1 = Device::create([
            'name' => 'Mesin 1',
            'ip_address' => '192.168.1.201',
            'port' => 4370,
            'serial_number' => 'SN_01',
        ]);

        $device2 = Device::create([
            'name' => 'Mesin 2',
            'ip_address' => '192.168.1.202',
            'port' => 4370,
            'serial_number' => 'SN_02',
        ]);

        $major = Major::create(['name' => 'TKJ']);
        $class = SchoolClass::create(['name' => 'X TKJ 1', 'major_id' => $major->id]);

        $student = Student::create([
            'name' => 'Citra Lestari',
            'device_user_id' => '202',
            'school_class_id' => $class->id,
            'privilege' => '0',
        ]);

        $fp = FingerprintTemplate::create([
            'student_id' => $student->id,
            'device_user_id' => '202',
            'finger_index' => 0,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_CITRA',
        ]);

        // Kirim perintah sync missing untuk PIN 202
        $response = $this->actingAs($this->admin)->post(route('sync.syncMissing'), [
            'pins' => ['202'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Harus tercatat di device_fingerprint_templates untuk kedua mesin
        $this->assertDatabaseHas('device_fingerprint_templates', [
            'device_id' => $device1->id,
            'device_user_id' => '202',
            'finger_index' => 0,
        ]);

        $this->assertDatabaseHas('device_fingerprint_templates', [
            'device_id' => $device2->id,
            'device_user_id' => '202',
            'finger_index' => 0,
        ]);
    }

    public function test_matrix_can_filter_by_search_and_role(): void
    {
        $teacher = Teacher::create([
            'name' => 'Drs. Supardi',
            'nip' => '198001012005011001',
            'category' => 'GURU SEJARAH',
            'device_user_id' => '198001012005011001',
        ]);

        $response = $this->actingAs($this->admin)->get(route('sync.matrix', [
            'search' => 'Supardi',
            'role' => 'teachers',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Drs. Supardi');
        $response->assertSee('198001012005011001');
    }

    public function test_user_devices_status_api_returns_correct_device_mapping(): void
    {
        $device1 = Device::create([
            'name' => 'Mesin 1',
            'ip_address' => '192.168.1.201',
            'port' => 4370,
            'serial_number' => 'SN_01',
        ]);

        $major = Major::create(['name' => 'RPL']);
        $class = SchoolClass::create(['name' => 'X RPL 1', 'major_id' => $major->id]);

        $student = Student::create([
            'name' => 'Ahmad Dani',
            'device_user_id' => '303',
            'school_class_id' => $class->id,
            'privilege' => '0',
        ]);

        $fp = FingerprintTemplate::create([
            'student_id' => $student->id,
            'device_user_id' => '303',
            'finger_index' => 0,
            'size' => 128,
            'valid' => 1,
            'template_data' => 'TMP_DANI',
        ]);

        DeviceFingerprintTemplate::create([
            'device_id' => $device1->id,
            'device_user_id' => '303',
            'finger_index' => 0,
            'fingerprint_template_id' => $fp->id,
            'status' => 'synced',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('sync.userDevicesStatus', ['pin' => '303']));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'user' => [
                'pin' => '303',
                'name' => 'Ahmad Dani',
                'role' => 'Siswa',
                'db_count' => 1,
            ],
            'devices' => [
                [
                    'id' => $device1->id,
                    'name' => 'Mesin 1',
                    'has_template' => true,
                    'template_count' => 1,
                ],
            ],
        ]);
    }
}
