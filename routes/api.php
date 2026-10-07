<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Device;
use App\Models\Student;
use App\Models\AttendanceLog;
use App\Models\FingerprintTemplate;
use App\Events\AttendanceCreated;
use Illuminate\Support\Facades\Log;

Route::get('/devices', function() {
    return response()->json(Device::all(['id', 'ip_address', 'port']));
});

Route::post('/attendance/realtime', function(Request $request) {
    $ip = $request->input('ip_address');
    $uid = $request->input('user_id');
    $timestamp = $request->input('timestamp');
    $status = $request->input('status') ?? 0;
    
    if (!$ip || !$uid || !$timestamp) {
        return response()->json(['error' => 'Missing data'], 400);
    }
    
    $device = Device::where('ip_address', $ip)->first();
    if (!$device) {
        return response()->json(['error' => 'Device not found'], 404);
    }
    
    $student = Student::where('device_user_id', (string) $uid)->first();
    $isStrict = env('STRICT_ATTENDANCE', true);

    if ($isStrict && $student) {
        // Strict Mode (Anti-Duplikat Harian)
        $date = substr($timestamp, 0, 10);
        $existingLog = AttendanceLog::withTrashed()
            ->where('student_id', $student->id)
            ->where('status_code', $status)
            ->whereDate('timestamp', $date)
            ->first();
            
        if ($existingLog) {
            // Sudah absen status ini pada hari ini. Drop data (jangan masukkan ke DB dan TV)
            return response()->json(['status' => 'skipped', 'message' => 'Duplicate daily attendance']);
        }
    } else {
        // Non-Strict Mode
        $existingLog = AttendanceLog::withTrashed()->where([
            'device_id' => $device->id,
            'device_user_id' => (string) $uid,
            'timestamp' => $timestamp
        ])->first();
        
        if ($existingLog) {
            return response()->json(['status' => 'skipped']);
        }
    }

    // Jika lolos pengecekan, simpan data
    $log = AttendanceLog::create([
        'device_id' => $device->id,
        'device_user_id' => (string) $uid,
        'timestamp' => $timestamp,
        'student_id' => $student ? $student->id : null,
        'status_code' => $status
    ]);
    
    // Siarkan ke layar TV
    broadcast(new AttendanceCreated($log));

    return response()->json(['status' => 'success']);
});

Route::post('/sync/templates', function(Request $request) {
    $ip = $request->input('ip_address');
    $templates = $request->input('templates', []);
    
    if (empty($templates)) {
        return response()->json(['status' => 'success', 'new_templates' => []]);
    }

    $device = Device::where('ip_address', $ip)->first();
    if (!$device) return response()->json(['error' => 'Device not found'], 404);

    $newTemplates = [];

    foreach ($templates as $t) {
        // Abaikan template admin (uid = 1 atau privilege tinggi yang bukan murid)
        // Kita cocokan uid string (NIS)
        $student = Student::where('device_user_id', (string) $t['uid'])->first();
        if (!$student) continue;

        // Cek apakah template sudah ada di database berdasarkan NIS & Jari
        $finger = FingerprintTemplate::where('device_user_id', (string) $t['uid'])
                    ->where('finger_index', $t['fid'])
                    ->first();

        if (!$finger) {
            // Ini jari baru, simpan ke database
            FingerprintTemplate::create([
                'student_id' => $student->id,
                'device_user_id' => (string) $t['uid'],
                'finger_index' => $t['fid'],
                'size' => $t['size'] ?? 0,
                'valid' => $t['valid'],
                'template_data' => $t['template']
            ]);
            
            // Masukkan ke array response agar Python tahu ini harus disebar
            $newTemplates[] = [
                'device_user_id' => $student->device_user_id,
                'name' => $student->name,
                'privilege' => $student->privilege,
                'fid' => $t['fid'],
                'valid' => $t['valid'],
                'template' => $t['template']
            ];
        }
    }

    return response()->json([
        'status' => 'success', 
        'new_templates' => $newTemplates
    ]);
});