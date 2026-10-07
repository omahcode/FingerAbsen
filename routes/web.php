<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SyncController;

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

Route::get('tv', [\App\Http\Controllers\TvController::class, 'index'])->name('tv.index');
