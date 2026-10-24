<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DeviceDataInspectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Device $device1;

    protected Device $device3;

    protected SchoolClass $classA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $major = Major::create([
            'name' => 'Pengembangan Perangkat Lunak',
            'code' => 'PPLG',
        ]);

        $this->classA = SchoolClass::create([
            'name' => 'X PPLG 1',
            'major_id' => $major->id,
        ]);

        $this->device1 = Device::create([
            'name' => 'Mesin 1 (Siswa)',
            'serial_number' => 'DEV-MESIN-001',
            'ip_address' => '192.168.1.101',
            'port' => 4370,
            'purpose' => 'students',
            'status' => 'online',
        ]);

        $this->device3 = Device::create([
            'name' => 'Mesin 3 (Khusus Guru)',
            'serial_number' => 'DEV-MESIN-003',
            'ip_address' => '192.168.1.103',
            'port' => 4370,
            'purpose' => 'teachers',
            'status' => 'online',
        ]);

        Cache::flush();
    }

    public function test_admin_can_view_device_index_with_data_summary(): void
    {
        // Daftarkan siswa ke Mesin 1
        $student = Student::create([
            'nis' => '1001',
            'name' => 'Ahmad Siswa',
            'school_class_id' => $this->classA->id,
            'device_user_id' => '1001',
        ]);

        DeviceUser::create([
            'device_id' => $this->device1->id,
            'device_user_id' => '1001',
            'user_type' => 'student',
            'student_id' => $student->id,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('devices.index'));

        $response->assertStatus(200);
        $response->assertSee('Mesin 1 (Siswa)');
        $response->assertSee('Mesin 3 (Khusus Guru)');
        $response->assertSee('Cek Data');
    }

    public function test_device_data_inspection_shows_correct_isolated_data(): void
    {
        // 1. Tambah 3 Siswa ke Mesin 1
        for ($i = 1; $i <= 3; $i++) {
            $s = Student::create([
                'nis' => '100'.$i,
                'name' => 'Siswa '.$i,
                'school_class_id' => $this->classA->id,
                'device_user_id' => '100'.$i,
            ]);

            DeviceUser::create([
                'device_id' => $this->device1->id,
                'device_user_id' => (string) $s->device_user_id,
                'user_type' => 'student',
                'student_id' => $s->id,
                'synced_at' => now(),
            ]);
        }

        // 2. Tambah 2 Guru ke Mesin 3
        for ($j = 1; $j <= 2; $j++) {
            $t = Teacher::create([
                'nip' => '19800'.$j,
                'name' => 'Guru '.$j,
                'device_user_id' => '19800'.$j,
            ]);

            DeviceUser::create([
                'device_id' => $this->device3->id,
                'device_user_id' => (string) $t->device_user_id,
                'user_type' => 'teacher',
                'teacher_id' => $t->id,
                'synced_at' => now(),
            ]);
        }

        // Cek Mesin 1: Harus ada Siswa, TIDAK ADA Guru
        $resDev1 = $this->actingAs($this->admin)->get(route('devices.show', $this->device1->id));
        $resDev1->assertStatus(200);
        $resDev1->assertSee('Siswa 1');
        $resDev1->assertSee('Siswa 2');
        $resDev1->assertDontSee('Guru 1');

        // Cek Mesin 3: Harus ada Guru, TIDAK ADA Siswa
        $resDev3 = $this->actingAs($this->admin)->get(route('devices.show', $this->device3->id));
        $resDev3->assertStatus(200);
        $resDev3->assertSee('Guru 1');
        $resDev3->assertSee('Guru 2');
        $resDev3->assertDontSee('Siswa 1');
        $resDev3->assertSee('Khusus Guru (100%)');
    }

    public function test_admin_can_remove_user_from_specific_device_only(): void
    {
        $student = Student::create([
            'nis' => '5001',
            'name' => 'Budi Santoso',
            'school_class_id' => $this->classA->id,
            'device_user_id' => '5001',
        ]);

        DeviceUser::create([
            'device_id' => $this->device1->id,
            'device_user_id' => '5001',
            'user_type' => 'student',
            'student_id' => $student->id,
            'synced_at' => now(),
        ]);

        $res = $this->actingAs($this->admin)->post(route('devices.remove_user', [
            'device' => $this->device1->id,
            'deviceUserId' => '5001',
        ]));

        $res->assertRedirect();
        $res->assertSessionHas('success');

        // Pastikan terhapus dari device_users
        $this->assertDatabaseMissing('device_users', [
            'device_id' => $this->device1->id,
            'device_user_id' => '5001',
        ]);

        // Pastikan siswa master tetap ada di database
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
        ]);

        // Pastikan perintah DATA DELETE USER masuk ke antrean mesin 1
        $queue = Cache::get('adms_queue_'.$this->device1->serial_number, []);
        $this->assertContains('DATA DELETE USER PIN=5001', $queue);
    }

    public function test_admin_can_send_pull_data_command(): void
    {
        $res = $this->actingAs($this->admin)->post(route('devices.pull_data', $this->device1->id));

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $queue = Cache::get('adms_queue_'.$this->device1->serial_number, []);
        $this->assertContains('QUERY USER PIN=0', $queue);
    }
}
