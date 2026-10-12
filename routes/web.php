<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TvController;
use App\Http\Controllers\IclockController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\TeacherManagementController;
use App\Http\Controllers\MajorMonitoringController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\WhatsAppWebhookController;

// Rute Autentikasi
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Halaman Publik (Mode TV, Simulasi Uji Coba & API Realtime Polling)
Route::get('tv', [TvController::class, 'index'])->name('tv.index');
Route::get('attendance/latest', [AttendanceController::class, 'latest'])->name('attendance.latest');
Route::match(['get', 'post'], 'attendance/simulate', [AttendanceController::class, 'simulate'])->name('attendance.simulate');
Route::post('attendance/simulate/seed-demo', [AttendanceController::class, 'seedDemoStudents'])->name('attendance.simulate.seed_demo');
Route::post('attendance/simulate/reset-today', [AttendanceController::class, 'resetTodayAttendance'])->name('attendance.simulate.reset_today');

// Halaman Portal Manajemen (Dilindungi Autentikasi Login)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        if (Auth::user()->isGuru()) {
            return redirect()->route('guru.dashboard');
        }
        return redirect()->route('devices.index');
    });

    // ====================================================
    // FITUR GURU & WALI KELAS
    // ====================================================
    // Fitur 1: Monitoring Presensi Khusus Kelas Wali Kelas
    Route::get('guru/dashboard', [TeacherDashboardController::class, 'index'])->name('guru.dashboard');
    // Fitur 2: Pengingat WhatsApp 1-Klik ke Orang Tua Murid
    Route::post('guru/reminder/{student}', [TeacherDashboardController::class, 'sendParentReminder'])->name('guru.reminder');
    Route::post('guru/reminder-all', [TeacherDashboardController::class, 'sendParentReminderAll'])->name('guru.reminder_all');

    // Fitur Monitoring Tingkat Jurusan (Guru & Admin)
    Route::get('monitoring/jurusan', [MajorMonitoringController::class, 'index'])->name('monitoring.jurusan');
    Route::post('monitoring/jurusan/{major}/reminder-all', [MajorMonitoringController::class, 'sendMajorReminderAll'])->name('monitoring.jurusan.reminder_all');
    Route::post('monitoring/jurusan/{major}/class/{schoolClass}/reminder-all', [MajorMonitoringController::class, 'sendClassReminderAll'])->name('monitoring.jurusan.class_reminder_all');

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

    // Manajemen Akun Guru (Admin)
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
    Route::post('settings/test-whatsapp', [SettingController::class, 'testWhatsApp'])->name('settings.test_whatsapp');
    Route::post('settings/manual-streak', [SettingController::class, 'updateManualStreak'])->name('settings.manual_streak.update');
    Route::post('settings/manual-streak/reset', [SettingController::class, 'resetManualStreak'])->name('settings.manual_streak.reset');
    Route::post('settings/manual-streak/simulate-top3', [SettingController::class, 'simulateTop3Streak'])->name('settings.manual_streak.simulate_top3');
});

// Route Webhook OpenWA (Public & Bebas CSRF)
Route::match(['get', 'post'], 'webhook/openwa', [WhatsAppWebhookController::class, 'handleWebhook'])->name('webhook.openwa');

// Route Protokol ADMS / Cloud Server Mesin Fingerprint (Solution / ZKTeco) - Public & Unrestricted
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
