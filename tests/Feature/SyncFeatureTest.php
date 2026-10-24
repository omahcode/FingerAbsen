<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\FingerprintTemplate;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SyncFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Device $device1;

    protected Device $device2;

    protected SchoolClass $classA;

    protected SchoolClass $classB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $major = Major::create([
            'name' => 'Pengembangan Perangkat Lunak & Gim',
            'code' => 'PPLG',
        ]);

        $this->classA = SchoolClass::create([
            'name' => 'X PPLG 1',
            'major_id' => $major->id,
        ]);

        $this->classB = SchoolClass::create([
            'name' => 'X PPLG 2',
            'major_id' => $major->id,
        ]);

        $this->device1 = Device::create([
            'name' => 'Mesin 1 (Siswa)',
            'serial_number' => 'DEV-SN-001',
            'ip_address' => '192.168.1.101',
            'port' => 4370,
            'purpose' => 'students',
            'status' => 'online',
        ]);

        $this->device2 = Device::create([
            'name' => 'Mesin 2 (Guru)',
            'serial_number' => 'DEV-SN-002',
            'ip_address' => '192.168.1.102',
            'port' => 4370,
            'purpose' => 'teachers',
            'status' => 'online',
        ]);

        Cache::flush();
    }

    public function test_sync_index_page_is_accessible_and_renders_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('sync.index'));

        $response->assertStatus(200);
        $response->assertSee('Sinkronisasi & Sebar Data Mesin', false);
        $response->assertSee('Kustomisasi Sebar Data ke Mesin Fingerprint');
        $response->assertSee('50 Orang per Batch');
    }

    public function test_custom_push_students_in_batch_of_50(): void
    {
        // Buat 60 siswa di kelas A
        for ($i = 1; $i <= 60; $i++) {
            $student = Student::create([
                'nis' => '1000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => "Siswa {$i}",
                'school_class_id' => $this->classA->id,
                'device_user_id' => '1000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            ]);

            FingerprintTemplate::create([
                'student_id' => $student->id,
                'device_user_id' => $student->device_user_id,
                'finger_index' => 0,
                'size' => 120,
                'valid' => 1,
                'template_data' => 'SAMPLE_TMP_'.$i,
            ]);
        }

        // 1. Kirim Batch 1 (50 orang pertama) ke Mesin 1
        $resBatch1 = $this->actingAs($this->admin)->post(route('sync.push'), [
            'target_type' => 'students',
            'school_class_id' => $this->classA->id,
            'device_id' => $this->device1->id,
            'batch_size' => '50',
            'batch_page' => 1,
            'include_fingerprints' => '1',
        ]);

        $resBatch1->assertRedirect();
        $resBatch1->assertSessionHas('success');

        $queue = Cache::get('adms_queue_'.$this->device1->serial_number, []);
        $this->assertNotEmpty($queue);

        // Verifikasi ada 50 siswa di antrean batch 1
        $userCommands = array_filter($queue, fn ($cmd) => str_starts_with($cmd, 'DATA USER PIN='));
        $this->assertCount(50, $userCommands);

        // 2. Kirim Batch 2 (10 orang sisa) ke Mesin 1
        Cache::flush();
        $resBatch2 = $this->actingAs($this->admin)->post(route('sync.push'), [
            'target_type' => 'students',
            'school_class_id' => $this->classA->id,
            'device_id' => $this->device1->id,
            'batch_size' => '50',
            'batch_page' => 2,
            'include_fingerprints' => '1',
        ]);

        $resBatch2->assertRedirect();
        $resBatch2->assertSessionHas('success');

        $queueBatch2 = Cache::get('adms_queue_'.$this->device1->serial_number, []);
        $userCommandsBatch2 = array_filter($queueBatch2, fn ($cmd) => str_starts_with($cmd, 'DATA USER PIN='));
        $this->assertCount(10, $userCommandsBatch2);
    }

    public function test_custom_push_teachers_to_specific_device(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Teacher::create([
                'nip' => '19800101'.$i,
                'name' => "Guru {$i}",
                'device_user_id' => '19800101'.$i,
            ]);
        }

        $res = $this->actingAs($this->admin)->post(route('sync.push'), [
            'target_type' => 'teachers',
            'device_id' => $this->device2->id,
            'batch_size' => 'all',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $queue = Cache::get('adms_queue_'.$this->device2->serial_number, []);
        $this->assertNotEmpty($queue);

        $userCommands = array_filter($queue, fn ($cmd) => str_starts_with($cmd, 'DATA USER PIN=19800101'));
        $this->assertCount(5, $userCommands);
    }
}
