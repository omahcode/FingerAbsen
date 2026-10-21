<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_admin_can_view_teacher_import_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('teachers.importForm'));

        $response->assertStatus(200);
        $response->assertSee('Import Data Guru via Excel');
        $response->assertSee('Download Template Excel');
    }

    public function test_admin_can_download_teacher_import_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('teachers.downloadTemplate', ['format' => 'xlsx']));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('content-disposition'));
    }

    public function test_admin_can_import_teachers_via_csv(): void
    {
        $csvContent = "NIP,NAMA GURU,KATEGORI,NO HP,PIN MESIN\n".
            "198101012006011001,Pak Wahyu Hidayat,GURU MTK,081234567890,198101012006011001\n".
            "198202022007012002,Ibu Ratna Sari,GURU PPKN,082234567891,198202022007012002\n";

        $file = UploadedFile::fake()->createWithContent('guru.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('teachers.importExcel'), [
            'file_excel' => $file,
        ]);

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('teachers', [
            'nip' => '198101012006011001',
            'name' => 'Pak Wahyu Hidayat',
            'category' => 'GURU MTK',
        ]);
        $this->assertDatabaseHas('teachers', [
            'nip' => '198202022007012002',
            'name' => 'Ibu Ratna Sari',
            'category' => 'GURU PPKN',
        ]);
    }

    public function test_kepala_sekolah_can_view_teachers_page_and_ceremony(): void
    {
        $kepsek = User::factory()->create([
            'role' => 'kepala_sekolah',
            'name' => 'Bapak Kepala Sekolah',
            'email' => 'kepsek_test@sekolah.sch.id',
        ]);

        Teacher::create([
            'nip' => '197701012000011001',
            'name' => 'Drs. Suparman',
            'category' => 'GURU BIOLOGI',
            'device_user_id' => '197701012000011001',
        ]);

        $responseIndex = $this->actingAs($kepsek)->get(route('teachers.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Data Guru (User Mesin)');
        $responseIndex->assertSee('Drs. Suparman');
        $responseIndex->assertSee('Presensi Upacara');

        $responseCeremony = $this->actingAs($kepsek)->get(route('teachers.ceremony'));
        $responseCeremony->assertStatus(200);
        $responseCeremony->assertSee('Presensi Upacara Dewan Guru');
        $responseCeremony->assertSee('Drs. Suparman');
    }

    public function test_guru_cannot_view_teachers_page_or_ceremony(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'name' => 'Wali Kelas X',
        ]);

        $responseIndex = $this->actingAs($guru)->get(route('teachers.index'));
        $responseIndex->assertStatus(403);

        $responseCeremony = $this->actingAs($guru)->get(route('teachers.ceremony'));
        $responseCeremony->assertStatus(403);
    }

    public function test_kepala_sekolah_redirected_to_ceremony_page_on_login(): void
    {
        $kepsek = User::factory()->create([
            'role' => 'kepala_sekolah',
            'email' => 'kepsek_login@sekolah.sch.id',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'kepsek_login@sekolah.sch.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('teachers.ceremony'));
    }
}
