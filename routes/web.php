<?php

use Illuminate\Support\Facades\Route;
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

// Rute Autentikasi
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Halaman Publik (Mode TV & API Realtime Polling)
Route::get('tv', [TvController::class, 'index'])->name('tv.index');
Route::get('attendance/latest', [AttendanceController::class, 'latest'])->name('attendance.latest');

// Halaman Portal Manajemen (Dilindungi Autentikasi Login)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('devices.index');
    });

    Route::post('devices/sync-time-all', [DeviceController::class, 'syncTimeAll'])->name('devices.sync_time_all');
    Route::resource('devices', DeviceController::class);
    Route::post('devices/{device}/check', [DeviceController::class, 'checkStatus'])->name('devices.check');
    Route::post('devices/{device}/clear-admin', [DeviceController::class, 'clearAdmin'])->name('devices.clear_admin');
    Route::post('devices/{device}/sync-time', [DeviceController::class, 'syncTime'])->name('devices.sync_time');

    Route::resource('majors', MajorController::class)->except(['create', 'edit', 'update', 'show']);
    Route::resource('classes', SchoolClassController::class)->except(['create', 'edit', 'update', 'show']);

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

    Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
});

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
