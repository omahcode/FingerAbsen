<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SyncFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Device $device1;
    private Device $device2;
    private SchoolClass $classA;
    private SchoolClass $classB;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $major = Major::create(['name' => 'Teknik Komputer']);
        $this->classA = SchoolClass::create(['name' => 'X TKJ 1', 'major_id' => $major->id]);
        $this->classB = SchoolClass::create(['name' => 'X TKJ 2', 'major_id' => $major->id]);

        $this->device1 = Device::create([
            'name' => 'Mesin Utama',
            'serial_number' => 'DEV1111',
            'ip_address' => '192.168.1.201',
            'port' => 4370,
            'purpose' => 'all',
        ]);

        $this->device2 = Device::create([
            'name' => 'Mesin Kedua',
            'serial_number' => 'DEV2222',
            'ip_address' => '192.168.1.202',
            'port' => 4370,
            'purpose' => 'all',
        ]);
    }

    public function test_sync_index_page_displays_devices_and_queue_status(): void
    {
        Cache::put('adms_queue_DEV1111', ['DATA USER PIN=101'], now()->addDays(1));

        $response = $this->actingAs($this->admin)->get(route('sync.index'));

        $response->assertStatus(200);
        $response->assertSee('Sinkronisasi &amp; Replikasi Multi-Mesin', false);
        $response->assertSee('Mesin Utama');
        $response->assertSee('Mesin Kedua');
        $response->assertSee('1 Perintah Tertunda');
    }

    public function test_push_all_queues_commands_to_all_devices_by_default(): void
    {
        $studentA = Student::create([
            'name' => 'Budi Santoso',
            'device_user_id' => '1001',
            'school_class_id' => $this->classA->id,
            'privilege' => '0',
        ]);

        FingerprintTemplate::create([
            'student_id' => $studentA->id,
            'device_user_id' => '1001',
            'finger_index' => 0,
            'size' => 10,
            'valid' => 1,
            'template_data' => 'dummy_template_data_1',
        ]);

        $response = $this->actingAs($this->admin)->post(route('sync.pushAll'), [
            'content_type' => 'all',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $queueDev1 = Cache::get('adms_queue_DEV1111', []);
        $queueDev2 = Cache::get('adms_queue_DEV2222', []);

        $this->assertNotEmpty($queueDev1);
        $this->assertNotEmpty($queueDev2);
        $this->assertTrue(collect($queueDev1)->contains(fn ($cmd) => str_contains($cmd, 'PIN=1001')));
    }

    public function test_push_filtered_by_specific_target_device(): void
    {
        $student = Student::create([
            'name' => 'Citra Lestari',
            'device_user_id' => '1002',
            'school_class_id' => $this->classA->id,
            'privilege' => '0',
        ]);

        $response = $this->actingAs($this->admin)->post(route('sync.pushAll'), [
            'device_id' => $this->device2->id,
            'content_type' => 'users_only',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $queueDev1 = Cache::get('adms_queue_DEV1111', []);
        $queueDev2 = Cache::get('adms_queue_DEV2222', []);

        $this->assertEmpty($queueDev1);
        $this->assertNotEmpty($queueDev2);
        $this->assertTrue(collect($queueDev2)->contains(fn ($cmd) => str_contains($cmd, 'Citra Lestari')));
    }

    public function test_push_filtered_by_specific_class(): void
    {
        $studentA = Student::create([
            'name' => 'Siswa Kelas A',
            'device_user_id' => '2001',
            'school_class_id' => $this->classA->id,
            'privilege' => '0',
        ]);

        $studentB = Student::create([
            'name' => 'Siswa Kelas B',
            'device_user_id' => '2002',
            'school_class_id' => $this->classB->id,
            'privilege' => '0',
        ]);

        $response = $this->actingAs($this->admin)->post(route('sync.pushAll'), [
            'school_class_id' => $this->classA->id,
            'content_type' => 'users_only',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $queueDev1 = Cache::get('adms_queue_DEV1111', []);
        $this->assertTrue(collect($queueDev1)->contains(fn ($cmd) => str_contains($cmd, 'Siswa Kelas A')));
        $this->assertFalse(collect($queueDev1)->contains(fn ($cmd) => str_contains($cmd, 'Siswa Kelas B')));
    }

    public function test_clear_queue_for_specific_device_and_all_devices(): void
    {
        Cache::put('adms_queue_DEV1111', ['CMD1', 'CMD2'], now()->addDays(1));
        Cache::put('adms_queue_DEV2222', ['CMD3'], now()->addDays(1));

        // Clear only device 1
        $response = $this->actingAs($this->admin)->post(route('sync.clearQueue'), [
            'device_id' => $this->device1->id,
        ]);

        $response->assertRedirect();
        $this->assertEmpty(Cache::get('adms_queue_DEV1111', []));
        $this->assertNotEmpty(Cache::get('adms_queue_DEV2222', []));

        // Clear all devices
        $responseAll = $this->actingAs($this->admin)->post(route('sync.clearQueue'));
        $responseAll->assertRedirect();
        $this->assertEmpty(Cache::get('adms_queue_DEV2222', []));
    }
}

