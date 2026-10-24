<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
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
        $response->assertSee('SIDIK JARI');
        $response->assertSee('AKSI');
        $response->assertSee('198501012010011001');
        $response->assertSee('Pak Joko Widodo, M.Pd');
    }

    public function test_admin_can_store_new_teacher(): void
    {
        $response = $this->actingAs($this->admin)->post(route('teachers.store'), [
            'nip' => '199002022015021002',
            'name' => 'Ibu Sri Rahayu, S.Pd',
            'phone' => '081234567890',
        ]);

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('teachers', [
            'nip' => '199002022015021002',
            'name' => 'Ibu Sri Rahayu, S.Pd',
            'category' => 'GURU UMUM',
            'device_user_id' => '199002022015021002',
        ]);
    }

    public function test_admin_can_update_teacher(): void
    {
        $teacher = Teacher::create([
            'nip' => '198803032012031003',
            'name' => 'Ahmad Suhendar, S.T',
            'category' => 'GURU UMUM',
            'device_user_id' => '198803032012031003',
        ]);

        $response = $this->actingAs($this->admin)->put(route('teachers.update', $teacher->id), [
            'nip' => '198803032012031003',
            'name' => 'Ahmad Suhendar, M.Kom',
        ]);

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'Ahmad Suhendar, M.Kom',
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
        $csvContent = "NIP,NAMA GURU,NO HP,PIN MESIN\n".
            "198101012006011001,Pak Wahyu Hidayat,081234567890,198101012006011001\n".
            "198202022007012002,Ibu Ratna Sari,082234567891,198202022007012002\n";

        $file = UploadedFile::fake()->createWithContent('guru.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('teachers.importExcel'), [
            'file_excel' => $file,
        ]);

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('teachers', [
            'nip' => '198101012006011001',
            'name' => 'Pak Wahyu Hidayat',
        ]);
        $this->assertDatabaseHas('teachers', [
            'nip' => '198202022007012002',
            'name' => 'Ibu Ratna Sari',
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

    public function test_ceremony_table_has_requested_headers_without_category(): void
    {
        $response = $this->actingAs($this->admin)->get(route('teachers.ceremony'));
        $response->assertStatus(200);
        $response->assertSee('No');
        $response->assertSee('NIP');
        $response->assertSee('NAMA GURU');
        $response->assertSee('WAKTU TAP');
        $response->assertSee('STATUS KEHADIRAN');
    }

    public function test_teacher_tap_does_not_appear_in_tv_latest_polling_or_student_attendance(): void
    {
        $device = Device::create([
            'name' => 'Mesin Fingerprint Guru',
            'serial_number' => 'DEV-GURU-001',
            'ip_address' => '192.168.1.199',
            'port' => 4370,
            'purpose' => 'teachers',
            'status' => 'online',
        ]);

        $teacher = Teacher::create([
            'nip' => '198501012010011001',
            'name' => 'Pak Joko S., M.Pd',
            'category' => 'GURU INFORMATIKA',
            'device_user_id' => '198501012010011001',
        ]);

        // Simulasikan POST ATTLOG dari mesin
        $attlogBody = "198501012010011001\t2026-10-22 06:45:00\t0\t1\n";
        $response = $this->call('POST', '/iclock/cdata?SN=DEV-GURU-001&table=ATTLOG', [], [], [], [], $attlogBody);

        $this->assertDatabaseHas('attendance_logs', [
            'teacher_id' => $teacher->id,
            'student_id' => null,
            'device_user_id' => '198501012010011001',
        ]);

        // 1. Cek /attendance/latest (yang di-poll oleh TV): data guru TIDAK BOLEH muncul
        $latestRes = $this->getJson(route('attendance.latest', ['since_id' => 0]));
        $latestRes->assertStatus(200);
        $logs = $latestRes->json('logs');
        $this->assertEmpty($logs, 'Log guru tidak boleh masuk ke endpoint TV attendance.latest');

        // 2. Cek /attendance (daftar absensi siswa): data guru TIDAK BOLEH muncul
        $attIndexRes = $this->actingAs($this->admin)->get(route('attendance.index'));
        $attIndexRes->assertStatus(200);
        $attIndexRes->assertDontSee('Pak Joko S., M.Pd');

        // 3. Cek /teachers/ceremony: data guru HARUS muncul sebagai hadir
        $ceremonyRes = $this->actingAs($this->admin)->get(route('teachers.ceremony'));
        $ceremonyRes->assertStatus(200);
        $ceremonyRes->assertSee('Pak Joko S., M.Pd');
        $ceremonyRes->assertSee('Hadir Upacara');
    }

    public function test_machine_dedicated_for_teachers_rejects_student_tap(): void
    {
        $device = Device::create([
            'name' => 'Mesin Upacara Khusus Guru',
            'serial_number' => 'DEV-UPACARA-002',
            'ip_address' => '192.168.1.198',
            'port' => 4370,
            'purpose' => 'teachers',
            'status' => 'online',
        ]);

        $major = Major::create(['name' => 'PPLG']);
        $class = SchoolClass::create(['name' => 'XI RPL 1', 'major_id' => $major->id]);
        $student = Student::create([
            'device_user_id' => '999111',
            'name' => 'Siswa Nyasar',
            'school_class_id' => $class->id,
        ]);

        // Murid mencoba tap di mesin upacara khusus guru
        $attlogBody = "999111\t2026-10-22 06:50:00\t0\t1\n";
        $response = $this->call('POST', '/iclock/cdata?SN=DEV-UPACARA-002&table=ATTLOG', [], [], [], [], $attlogBody);

        // Pastikan TIDAK tersimpan ke database absensi siswa
        $this->assertDatabaseMissing('attendance_logs', [
            'device_user_id' => '999111',
            'student_id' => $student->id,
        ]);
    }
}
