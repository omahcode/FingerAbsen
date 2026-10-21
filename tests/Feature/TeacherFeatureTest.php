<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_view_teachers_page_with_required_columns(): void
    {
        Teacher::create([
            'nip' => '198501012010011001',
            'name' => 'Pak Joko Widodo, M.Pd',
            'category' => 'GURU MTK',
            'device_user_id' => '198501012010011001',
        ]);

        $response = $this->actingAs($this->admin)->get(route('teachers.index'));

        $response->assertStatus(200);
        $response->assertSee('Data Guru (User Mesin)');
        $response->assertSee('NIP');
        $response->assertSee('NAMA GURU');
        $response->assertSee('KATEGORI');
        $response->assertSee('SIDIK JARI');
        $response->assertSee('AKSI');
        $response->assertSee('198501012010011001');
        $response->assertSee('Pak Joko Widodo, M.Pd');
        $response->assertSee('GURU MTK');
    }

    public function test_admin_can_store_new_teacher(): void
    {
        $response = $this->actingAs($this->admin)->post(route('teachers.store'), [
            'nip' => '199002022015021002',
            'name' => 'Ibu Sri Rahayu, S.Pd',
            'category' => 'GURU PPKN',
            'phone' => '081234567890',
        ]);

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('teachers', [
            'nip' => '199002022015021002',
            'name' => 'Ibu Sri Rahayu, S.Pd',
            'category' => 'GURU PPKN',
            'device_user_id' => '199002022015021002',
        ]);
    }

    public function test_admin_can_update_teacher(): void
    {
        $teacher = Teacher::create([
            'nip' => '198803032012031003',
            'name' => 'Ahmad Suhendar, S.T',
            'category' => 'GURU JURUSAN',
            'device_user_id' => '198803032012031003',
        ]);

        $response = $this->actingAs($this->admin)->put(route('teachers.update', $teacher->id), [
            'nip' => '198803032012031003',
            'name' => 'Ahmad Suhendar, M.Kom',
            'category' => 'GURU INFORMATIKA',
        ]);

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'Ahmad Suhendar, M.Kom',
            'category' => 'GURU INFORMATIKA',
        ]);
    }

    public function test_admin_can_delete_teacher(): void
    {
        $teacher = Teacher::create([
            'nip' => '197504042000031004',
            'name' => 'Drs. Hendro Wibowo',
            'category' => 'GURU SEJARAH',
            'device_user_id' => '197504042000031004',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('teachers.destroy', $teacher->id));

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseMissing('teachers', [
            'id' => $teacher->id,
        ]);
    }

    public function test_admin_can_view_ceremony_attendance_page(): void
    {
        Teacher::create([
            'nip' => '198005052005011005',
            'name' => 'Bambang Supriyadi, S.Pd',
            'category' => 'GURU MTK',
            'device_user_id' => '198005052005011005',
        ]);

        $response = $this->actingAs($this->admin)->get(route('teachers.ceremony'));

        $response->assertStatus(200);
        $response->assertSee('Presensi Upacara Dewan Guru');
        $response->assertSee('Bambang Supriyadi, S.Pd');
        $response->assertSee('GURU MTK');
    }
}
