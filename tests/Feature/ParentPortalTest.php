<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushAttendanceJob;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Major;
use App\Models\ParentPushSubscription;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPermission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;

    private SchoolClass $otherClass;

    private Student $student;

    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $major = Major::create(['name' => 'Rekayasa Perangkat Lunak']);

        $this->class = SchoolClass::create([
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
        ]);

        $this->otherClass = SchoolClass::create([
            'major_id' => $major->id,
            'name' => 'XII RPL 2',
        ]);

        $this->student = Student::create([
            'nis' => '12345',
            'name' => 'Raditya Pratama',
            'school_class_id' => $this->class->id,
            'parent_phone' => '081234567890',
            'device_user_id' => '1001',
            'privilege' => 0,
        ]);

        $this->device = Device::create([
            'name' => 'Fingerprint Gerbang',
            'ip_address' => '192.168.1.200',
            'port' => 4370,
            'status' => 'online',
            'serial_number' => 'FP-GATE-01',
        ]);
    }

    public function test_unauthenticated_parent_sees_verification_login_page(): void
    {
        $response = $this->get(route('parent.index'));

        $response->assertOk();
        $response->assertViewIs('parent.login');
        $response->assertSee('Portal Wali Murid');
        $response->assertSee('Verifikasi Akses Orang Tua');
        $response->assertSee('XII RPL 1');
    }

    public function test_parent_login_succeeds_with_valid_requirements(): void
    {
        $response = $this->post(route('parent.select'), [
            'nis' => '12345',
            'name' => 'Raditya Pratama',
            'school_class_id' => $this->class->id,
        ]);

        $response->assertRedirect(route('parent.index'));
        $response->assertSessionHas('parent_student_id', $this->student->id);
        $response->assertSessionHas('success');
    }

    public function test_parent_login_fails_if_nis_does_not_match(): void
    {
        $response = $this->from(route('parent.index'))->post(route('parent.select'), [
            'nis' => '99999',
            'name' => 'Raditya Pratama',
            'school_class_id' => $this->class->id,
        ]);

        $response->assertRedirect(route('parent.index'));
        $response->assertSessionHasErrors(['nis']);
        $this->assertNull(session('parent_student_id'));
    }

    public function test_parent_login_fails_if_student_name_does_not_match(): void
    {
        $response = $this->from(route('parent.index'))->post(route('parent.select'), [
            'nis' => '12345',
            'name' => 'Nama Yang Salah',
            'school_class_id' => $this->class->id,
        ]);

        $response->assertRedirect(route('parent.index'));
        $response->assertSessionHasErrors(['name']);
        $this->assertNull(session('parent_student_id'));
    }

    public function test_parent_login_fails_if_class_does_not_match(): void
    {
        $response = $this->from(route('parent.index'))->post(route('parent.select'), [
            'nis' => '12345',
            'name' => 'Raditya Pratama',
            'school_class_id' => $this->otherClass->id,
        ]);

        $response->assertRedirect(route('parent.index'));
        $response->assertSessionHasErrors(['school_class_id']);
        $this->assertNull(session('parent_student_id'));
    }

    public function test_authenticated_parent_can_view_child_dashboard(): void
    {
        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->get(route('parent.index'));

        $response->assertOk();
        $response->assertViewIs('parent.dashboard');
        $response->assertSee('Raditya Pratama');
        $response->assertSee('XII RPL 1');
        $response->assertSee('Rekayasa Perangkat Lunak');
    }

    public function test_parent_dashboard_shows_in_school_status_when_student_checked_in(): void
    {
        $today = Carbon::today('Asia/Jakarta')->setTime(6, 45, 0);

        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => $today->toDateTimeString(),
            'status_code' => 0,
        ]);

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->get(route('parent.index'));

        $response->assertOk();
        $response->assertSee('Hadir Tepat Waktu');
        $response->assertSee('Sedang Berada di Sekolah');
    }

    public function test_parent_dashboard_shows_departed_status_when_student_checked_out(): void
    {
        $todayIn = Carbon::today('Asia/Jakarta')->setTime(6, 45, 0);
        $todayOut = Carbon::today('Asia/Jakarta')->setTime(15, 10, 0);

        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => $todayIn->toDateTimeString(),
            'status_code' => 0,
        ]);

        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => $todayOut->toDateTimeString(),
            'status_code' => 1,
        ]);

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->get(route('parent.index'));

        $response->assertOk();
        $response->assertSee('Sudah Pulang Sekolah');
        $response->assertSee('15:10 WIB');
    }

    public function test_parent_can_logout_and_clear_session(): void
    {
        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->post(route('parent.clear'));

        $response->assertRedirect(route('parent.index'));
        $this->assertNull(session('parent_student_id'));
    }

    public function test_parent_can_subscribe_device_for_push_notifications(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint-token-abc-123';

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->postJson(route('parent.push.subscribe'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'BNcRdreALRF8M+CNEnrollmentKeyTestSample==',
                    'auth' => 'tBHITQ==',
                ],
                'device_name' => 'Chrome Mobile Android',
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('parent_push_subscriptions', [
            'student_id' => $this->student->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => hash('sha256', $endpoint),
            'device_name' => 'Chrome Mobile Android',
        ]);
    }

    public function test_parent_can_unsubscribe_device_from_push_notifications(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint-token-to-delete';

        ParentPushSubscription::create([
            'student_id' => $this->student->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => hash('sha256', $endpoint),
            'p256dh' => 'test-p256dh-key',
            'auth' => 'test-auth-secret',
            'device_name' => 'Safari iOS',
        ]);

        $this->assertDatabaseHas('parent_push_subscriptions', [
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->postJson(route('parent.push.unsubscribe'), [
                'endpoint' => $endpoint,
            ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseMissing('parent_push_subscriptions', [
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);
    }

    public function test_attendance_log_creation_dispatches_webpush_job(): void
    {
        Queue::fake();

        $log = AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => now()->toDateTimeString(),
            'status_code' => 0,
        ]);

        Queue::assertPushed(SendWebPushAttendanceJob::class, function ($job) use ($log) {
            return $job->attendanceLog->id === $log->id;
        });
    }

    public function test_parent_can_submit_permission_form_for_sick(): void
    {
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->post(route('parent.permission.store'), [
                'date' => $todayStr,
                'type' => 'Sakit',
                'notes' => 'Sakit demam tinggi dan batuk pilek.',
            ]);

        $response->assertRedirect(route('parent.index'));

        $this->assertDatabaseHas('student_permissions', [
            'student_id' => $this->student->id,
            'type' => 'Sakit',
            'status' => 'disetujui',
            'notes' => 'Sakit demam tinggi dan batuk pilek.',
        ]);

        $permission = StudentPermission::where('student_id', $this->student->id)->first();
        $this->assertNotNull($permission);
        $this->assertEquals($todayStr, Carbon::parse($permission->date)->toDateString());
    }

    public function test_parent_can_submit_permission_form_for_permit(): void
    {
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->post(route('parent.permission.store'), [
                'date' => $todayStr,
                'type' => 'Izin',
                'notes' => 'Menghadiri acara pernikahan saudara di luar kota.',
            ]);

        $response->assertRedirect(route('parent.index'));

        $this->assertDatabaseHas('student_permissions', [
            'student_id' => $this->student->id,
            'type' => 'Izin',
            'status' => 'disetujui',
            'notes' => 'Menghadiri acara pernikahan saudara di luar kota.',
        ]);

        $permission = StudentPermission::where('student_id', $this->student->id)->first();
        $this->assertNotNull($permission);
        $this->assertEquals($todayStr, Carbon::parse($permission->date)->toDateString());
    }

    public function test_parent_dashboard_displays_sick_banner_and_status(): void
    {
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        StudentPermission::create([
            'student_id' => $this->student->id,
            'date' => $todayStr,
            'type' => 'Sakit',
            'status' => 'disetujui',
            'notes' => 'Istirahat dokter 2 hari.',
        ]);

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->get(route('parent.index'));

        $response->assertOk();
        $response->assertSee('Sakit');
        $response->assertSee('Disampaikan');
        $response->assertSee('Istirahat dokter 2 hari.');
    }

    public function test_parent_can_submit_multi_day_permission(): void
    {
        $today = Carbon::today('Asia/Jakarta');
        $tomorrow = $today->copy()->addDay();

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->post(route('parent.permission.store'), [
                'date' => $today->toDateString(),
                'end_date' => $tomorrow->toDateString(),
                'type' => 'Sakit',
                'notes' => 'Rawat inap demam berdarah.',
            ]);

        $response->assertRedirect(route('parent.index'));

        $this->assertDatabaseHas('student_permissions', [
            'student_id' => $this->student->id,
            'date' => $today->toDateString(),
            'type' => 'Sakit',
        ]);

        $this->assertDatabaseHas('student_permissions', [
            'student_id' => $this->student->id,
            'date' => $tomorrow->toDateString(),
            'type' => 'Sakit',
        ]);
    }

    public function test_parent_can_cancel_own_permission(): void
    {
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        $permission = StudentPermission::create([
            'student_id' => $this->student->id,
            'date' => $todayStr,
            'type' => 'Izin',
            'status' => 'disetujui',
            'notes' => 'Izin keperluan keluarga.',
        ]);

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->delete(route('parent.permission.destroy', $permission->id));

        $response->assertRedirect(route('parent.index'));
        $this->assertDatabaseMissing('student_permissions', ['id' => $permission->id]);
    }

    public function test_parent_polling_data_endpoint_returns_enhanced_attendance_structure(): void
    {
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        AttendanceLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->device_user_id,
            'student_id' => $this->student->id,
            'timestamp' => $todayStr.' 06:45:00',
            'status_code' => 0,
        ]);

        $response = $this->withSession(['parent_student_id' => $this->student->id])
            ->get(route('parent.data'));

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('attendance.checkin.time', '06:45');
        $response->assertJsonPath('attendance.checkin.is_ontime', true);
        $response->assertJsonPath('attendance.checkin.status_label', 'Hadir Tepat Waktu');
    }
}
