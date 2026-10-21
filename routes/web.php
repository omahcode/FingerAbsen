<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\IclockController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\MajorMonitoringController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDepartureMonitoringController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherManagementController;
use App\Http\Controllers\TvController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Rute Autentikasi
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Halaman Publik (Mode TV & API Realtime Polling Terproteksi)
Route::get('tv', [TvController::class, 'index'])->name('tv.index');
Route::get('attendance/latest', [AttendanceController::class, 'latest'])->middleware('throttle:300,1')->name('attendance.latest');

// ====================================================
// PORTAL ORANG TUA / WALI MURID
// ====================================================
Route::get('orang-tua', [ParentDashboardController::class, 'index'])->name('parent.index');
Route::post('orang-tua/pilih', [ParentDashboardController::class, 'selectStudent'])->name('parent.select');
Route::post('orang-tua/keluar', [ParentDashboardController::class, 'clear'])->name('parent.clear');
Route::get('orang-tua/data', [ParentDashboardController::class, 'data'])->name('parent.data');
Route::post('orang-tua/izin', [ParentDashboardController::class, 'storePermission'])->name('parent.permission.store');
Route::post('orang-tua/push-subscribe', [ParentDashboardController::class, 'subscribePush'])->name('parent.push.subscribe');
Route::post('orang-tua/push-unsubscribe', [ParentDashboardController::class, 'unsubscribePush'])->name('parent.push.unsubscribe');
Route::post('orang-tua/push-test', [ParentDashboardController::class, 'testPush'])->name('parent.push.test');

// Halaman Portal Manajemen (Dilindungi Autentikasi Login)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        if (Auth::user()->isGuru()) {
            return redirect()->route('guru.dashboard');
        }

        if (Auth::user()->isKepalaSekolah()) {
            return redirect()->route('teachers.ceremony');
        }

        return redirect()->route('devices.index');
    });

    // ====================================================
    // FITUR GURU & WALI KELAS
    // ====================================================
    // Fitur 1: Monitoring Presensi Khusus Kelas Wali Kelas
    Route::get('guru/dashboard', [TeacherDashboardController::class, 'index'])->name('guru.dashboard');

    // Fitur Monitoring Tingkat Jurusan (Guru & Admin)
    Route::get('monitoring/jurusan', [MajorMonitoringController::class, 'index'])->name('monitoring.jurusan');

    // Fitur Monitoring Status Kepulangan Siswa (Realtime & Direct Page)
    Route::get('monitoring/kepulangan', [StudentDepartureMonitoringController::class, 'index'])->name('monitoring.kepulangan');
    Route::get('monitoring/kepulangan/data', [StudentDepartureMonitoringController::class, 'data'])->name('monitoring.kepulangan.data');
    Route::post('monitoring/kepulangan/{student}/update-status', [StudentDepartureMonitoringController::class, 'updateStatus'])->name('monitoring.kepulangan.update_status');
    Route::get('monitoring-pulang', fn () => redirect()->route('monitoring.kepulangan'))->name('monitoring.pulang');

    // Fitur 3: Pengajuan Pengumuman TV oleh Guru
    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('announcements/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // ====================================================
    // FITUR ADMINISTRATOR
    // ====================================================
    // Approval Pengumuman TV (Admin)
    Route::get('admin/announcements', [AnnouncementController::class, 'adminIndex'])->name('admin.announcements.index');
    Route::post('admin/announcements/{id}/approve', [AnnouncementController::class, 'approve'])->name('admin.announcements.approve');
    Route::post('admin/announcements/{id}/reject', [AnnouncementController::class, 'reject'])->name('admin.announcements.reject');
    Route::delete('admin/announcements/{id}', [AnnouncementController::class, 'adminDestroy'])->name('admin.announcements.destroy');

    // Manajemen Seluruh Akun Pengguna (Users: Guru, Kepala Sekolah, Admin)
    Route::post('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset_password');
    Route::resource('admin/users', UserController::class)->names('admin.users')->except(['create', 'show', 'edit']);
    Route::get('users', fn () => redirect()->route('admin.users.index'))->name('users.index');

    // Manajemen Akun Guru (Admin) - Kompatibilitas rute lama
    Route::resource('admin/teachers', TeacherManagementController::class)->names('admin.teachers')->except(['create', 'show', 'edit']);

    // Manajemen Mesin & Master Data
    Route::post('devices/sync-time-all', [DeviceController::class, 'syncTimeAll'])->name('devices.sync_time_all');
    Route::resource('devices', DeviceController::class);
    Route::post('devices/{device}/check', [DeviceController::class, 'checkStatus'])->name('devices.check');
    Route::post('devices/{device}/clear-admin', [DeviceController::class, 'clearAdmin'])->name('devices.clear_admin');
    Route::post('devices/{device}/sync-time', [DeviceController::class, 'syncTime'])->name('devices.sync_time');

    Route::resource('majors', MajorController::class)->except(['create', 'edit', 'update', 'show']);
    Route::resource('classes', SchoolClassController::class)->except(['create', 'edit', 'update', 'show']);

    Route::get('students/download-template', [StudentController::class, 'downloadTemplate'])->name('students.downloadTemplate');
    Route::get('students/import', [StudentController::class, 'importForm'])->name('students.importForm');
    Route::post('students/import', [StudentController::class, 'importExcel'])->name('students.importExcel');
    Route::post('students/delete-multiple', [StudentController::class, 'destroyMultiple'])->name('students.destroyMultiple');
    Route::resource('students', StudentController::class)->except(['show']);
    Route::post('students/sync', [StudentController::class, 'sync'])->name('students.sync');

    // Master Data Guru (User Mesin & Fingerprint)
    Route::get('teachers/download-template', [TeacherController::class, 'downloadTemplate'])->name('teachers.downloadTemplate');
    Route::get('teachers/import', [TeacherController::class, 'importForm'])->name('teachers.importForm');
    Route::post('teachers/import', [TeacherController::class, 'importExcel'])->name('teachers.importExcel');
    Route::get('teachers/ceremony', [TeacherController::class, 'ceremonyAttendance'])->name('teachers.ceremony');
    Route::post('teachers/sync-all', [TeacherController::class, 'syncAll'])->name('teachers.sync_all');
    Route::post('teachers/delete-multiple', [TeacherController::class, 'destroyMultiple'])->name('teachers.destroyMultiple');
    Route::resource('teachers', TeacherController::class)->except(['create', 'show', 'edit']);

    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/sync', [AttendanceController::class, 'sync'])->name('attendance.sync');
    Route::post('attendance/delete-multiple', [AttendanceController::class, 'destroyMultiple'])->name('attendance.destroyMultiple');
    Route::post('attendance/toggle-strict', [AttendanceController::class, 'toggleStrict'])->name('attendance.toggleStrict');
    Route::delete('attendance/{id}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

    Route::get('sync', [SyncController::class, 'index'])->name('sync.index');
    Route::post('sync/push-all', [SyncController::class, 'pushAll'])->name('sync.pushAll');
    Route::post('sync/backup-templates', [SyncController::class, 'backupTemplates'])->name('sync.backupTemplates');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('reports/matrix-pdf', [ReportController::class, 'exportMatrixPdf'])->name('reports.matrix_pdf');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/manual-streak', [SettingController::class, 'updateManualStreak'])->name('settings.manual_streak.update');
    Route::post('settings/manual-streak/reset', [SettingController::class, 'resetManualStreak'])->name('settings.manual_streak.reset');
    Route::post('settings/manual-streak/simulate-top3', [SettingController::class, 'simulateTop3Streak'])->name('settings.manual_streak.simulate_top3');

    // Rute Simulasi Absensi (Hanya dapat diakses setelah login)
    Route::match(['get', 'post'], 'attendance/simulate', [AttendanceController::class, 'simulate'])->name('attendance.simulate');
    Route::post('attendance/simulate/seed-demo', [AttendanceController::class, 'seedDemoStudents'])->name('attendance.simulate.seed_demo');
    Route::post('attendance/simulate/reset-today', [AttendanceController::class, 'resetTodayAttendance'])->name('attendance.simulate.reset_today');
});

// Route Protokol ADMS / Cloud Server Mesin Fingerprint (Solution / ZKTeco) - Dilindungi Throttle
Route::middleware('throttle:120,1')->group(function () {
    Route::match(['get', 'post'], 'iclock/cdata', [IclockController::class, 'cdata']);
    Route::match(['get', 'post'], 'iclock/cdata.aspx', [IclockController::class, 'cdata']);
    Route::match(['get', 'post'], 'iclock/cdata.php', [IclockController::class, 'cdata']);
    Route::match(['get', 'post'], 'iclock/fdata', [IclockController::class, 'fdata']);
    Route::match(['get', 'post'], 'iclock/fdata.aspx', [IclockController::class, 'fdata']);
    Route::match(['get', 'post'], 'iclock/fdata.php', [IclockController::class, 'fdata']);
    Route::match(['get', 'post'], 'iclock/ping', [IclockController::class, 'ping']);
    Route::match(['get', 'post'], 'iclock/getrequest', [IclockController::class, 'getRequest']);
    Route::match(['get', 'post'], 'iclock/devicecmd', [IclockController::class, 'deviceCmd']);
    Route::match(['get', 'post'], 'iclock/registry', [IclockController::class, 'registry']);
});
