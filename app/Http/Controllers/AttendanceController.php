<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Student;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLog::with(['student.schoolClass', 'device'])->orderBy('timestamp', 'desc');

        $filter = $request->query('filter', 'all');
        
        if ($filter === 'registered') {
            $query->whereNotNull('student_id');
        } elseif ($filter === 'unregistered') {
            $query->whereNull('student_id');
        }

        $logs = $query->paginate(50)->appends(['filter' => $filter]);
        $devices = Device::all();
        $isStrict = env('STRICT_ATTENDANCE', true);
        $maxLogId = AttendanceLog::max('id') ?? 0;
        
        return view('attendance.index', compact('logs', 'devices', 'filter', 'isStrict', 'maxLogId'));
    }

    public function latest(Request $request)
    {
        $sinceId = (int) $request->query('since_id', 0);
        $filter = $request->query('filter', 'all');

        $query = AttendanceLog::with(['student.schoolClass', 'device'])
            ->where('id', '>', $sinceId)
            ->orderBy('id', 'asc');

        if ($filter === 'registered') {
            $query->whereNotNull('student_id');
        } elseif ($filter === 'unregistered') {
            $query->whereNull('student_id');
        }

        $logs = $query->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'max_id' => AttendanceLog::max('id') ?? $sinceId,
            'logs' => $logs
        ]);
    }

    public function toggleStrict()
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $current = env('STRICT_ATTENDANCE', true) ? 'true' : 'false';
            $new = $current === 'true' ? 'false' : 'true';
            
            $content = file_get_contents($path);
            
            if (strpos($content, 'STRICT_ATTENDANCE=') !== false) {
                file_put_contents($path, preg_replace('/^STRICT_ATTENDANCE=.*$/m', 'STRICT_ATTENDANCE='.$new, $content));
            } else {
                file_put_contents($path, $content . "\nSTRICT_ATTENDANCE=" . $new);
            }
        }
        return redirect()->back()->with('success', 'Mode Anti-Duplikat berhasil diubah.');
    }

    public function sync(Request $request)
    {
        $request->validate(['device_id' => 'required']);
        $device = Device::findOrFail($request->device_id);
        $isStrict = env('STRICT_ATTENDANCE', true);

        try {
            $response = Http::timeout(30)->post('http://127.0.0.1:5000/api/get_attendance', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $attendances = $response->json('attendance');
                $newCount = 0;
                
                foreach ($attendances as $att) {
                    if (!$att['user_id']) continue;
                    
                    $student = Student::where('device_user_id', (string) $att['user_id'])->first();
                    $statusCode = $att['status'] ?? 0;
                    $timestamp = $att['timestamp'];

                    if ($isStrict && $student) {
                        // Strict Mode: 1 Murid hanya boleh 1x Masuk (0) dan 1x Pulang (1) per HARI di mesin manapun
                        $date = substr($timestamp, 0, 10); // Ambil YYYY-MM-DD
                        
                        $existingLog = AttendanceLog::withTrashed()
                            ->where('student_id', $student->id)
                            ->where('status_code', $statusCode)
                            ->whereDate('timestamp', $date)
                            ->first();
                            
                        if (!$existingLog) {
                            AttendanceLog::create([
                                'device_id' => $device->id,
                                'device_user_id' => (string) $att['user_id'],
                                'timestamp' => $timestamp,
                                'student_id' => $student->id,
                                'status_code' => $statusCode
                            ]);
                            if ($student && !empty($student->parent_phone)) {
                                \App\Jobs\SendWhatsAppAttendanceJob::dispatch($student, $timestamp, $statusCode, $device->name);
                            }
                            $newCount++;
                        }
                    } else {
                        // Non-Strict Mode (Aturan Lama): Abaikan jika timestamp dan mesin sama persis
                        $existingLog = AttendanceLog::withTrashed()->where([
                            'device_id' => $device->id,
                            'device_user_id' => (string) $att['user_id'],
                            'timestamp' => $timestamp
                        ])->first();

                        if (!$existingLog) {
                            AttendanceLog::create([
                                'device_id' => $device->id,
                                'device_user_id' => (string) $att['user_id'],
                                'timestamp' => $timestamp,
                                'student_id' => $student ? $student->id : null,
                                'status_code' => $statusCode
                            ]);
                            if ($student && !empty($student->parent_phone)) {
                                \App\Jobs\SendWhatsAppAttendanceJob::dispatch($student, $timestamp, $statusCode, $device->name);
                            }
                            $newCount++;
                        }
                    }
                }
                return redirect()->back()->with('success', "Sinkronisasi selesai. $newCount absen baru ditambahkan.");
            }
            
            return redirect()->back()->with('error', 'Gagal menarik absen dari mesin: ' . $response->json('error', 'Unknown Error'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal koneksi ke Service Python: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $log = AttendanceLog::findOrFail($id);
        $log->delete();
        return redirect()->back()->with('success', 'Data absensi berhasil dihapus dari sistem (data di dalam mesin X1000-C tidak ikut terhapus).');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:attendance_logs,id'
        ]);

        $count = count($request->ids);
        AttendanceLog::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', "$count data absensi berhasil dihapus secara massal.");
    }
}