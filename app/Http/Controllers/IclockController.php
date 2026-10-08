<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Student;
use App\Models\AttendanceLog;
use App\Events\AttendanceCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IclockController extends Controller
{
    /**
     * Handshake (GET) dan Data Push (POST) dari mesin ADMS
     */
    public function cdata(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $ip = $request->ip();

        Log::info("ADMS cdata request from IP: {$ip}, SN: {$sn}, Method: " . $request->method());

        // Cari atau update status mesin
        $this->updateDeviceStatus($sn, $ip);

        // Jika request GET: Mesin meminta parameter inisialisasi / handshake
        if ($request->isMethod('get')) {
            $responseContent = "GET OPTION FROM: {$sn}\n" .
                "ATTLOGStamp=None\n" .
                "OPERLOGStamp=None\n" .
                "ATTPHOTOStamp=None\n" .
                "ErrorDelay=60\n" .
                "Delay=30\n" .
                "TransTimes=00:00;14:05\n" .
                "TransInterval=1\n" .
                "TransFlag=TransData AttLog\tOpLog\tAttPhoto\tEnrollFP\tEnrollUser\n" .
                "TimeZone=7\n" .
                "Realtime=1\n" .
                "Encrypt=0\n";

            return response($responseContent, 200)
                ->header('Content-Type', 'text/plain');
        }

        // Jika request POST: Mesin mengirim data (ATTLOG / User / Template)
        $table = strtoupper($request->query('table', 'ATTLOG'));
        $body = $request->getContent();

        if (empty($body)) {
            return response("OK\n", 200)->header('Content-Type', 'text/plain');
        }

        $count = 0;
        if ($table === 'ATTLOG') {
            $count = $this->processAttendanceLogs($body, $sn, $ip);
        }

        return response("OK: {$count}\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Heartbeat / Ping dari mesin
     */
    public function ping(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $this->updateDeviceStatus($sn, $request->ip());

        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Antrean perintah ke mesin (Get Request)
     */
    public function getRequest(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $this->updateDeviceStatus($sn, $request->ip());

        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Laporan hasil eksekusi perintah dari mesin
     */
    public function deviceCmd(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $this->updateDeviceStatus($sn, $request->ip());

        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Parse dan simpan raw data ATTLOG dari mesin
     */
    private function processAttendanceLogs(string $rawBody, string $sn, string $ip): int
    {
        $device = Device::where('ip_address', $ip)->first() ?: Device::first();
        $deviceId = $device ? $device->id : null;

        $lines = preg_split('/\r\n|\r|\n/', trim($rawBody));
        $savedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $userId = null;
            $timestamp = null;
            $status = 0;

            // Format 1: Tab-separated atau Space-separated: PIN\tTime\tStatus\tVerify...
            $parts = preg_split('/\t+|\s{2,}/', $line);
            if (count($parts) >= 2) {
                $userId = trim($parts[0]);
                $timestamp = trim($parts[1]);
                $status = isset($parts[2]) ? (int)trim($parts[2]) : 0;
            } else {
                // Format 2: Key-value string (misal: PIN=101\tCHECKTIME=2026-10-08 17:00:00)
                parse_str(str_replace("\t", '&', $line), $parsed);
                $userId = $parsed['PIN'] ?? $parsed['USERID'] ?? null;
                $timestamp = $parsed['CHECKTIME'] ?? $parsed['TIME'] ?? null;
                $status = $parsed['STATUS'] ?? $parsed['CHECKTYPE'] ?? 0;
            }

            if (!$userId || !$timestamp) {
                continue;
            }

            // Validasi tanggal
            $timestamp = date('Y-m-d H:i:s', strtotime($timestamp));
            $student = Student::where('device_user_id', (string) $userId)->first();
            $isStrict = env('STRICT_ATTENDANCE', true);

            if ($isStrict && $student) {
                // Anti-duplikat harian per status
                $date = substr($timestamp, 0, 10);
                $exists = AttendanceLog::withTrashed()
                    ->where('student_id', $student->id)
                    ->where('status_code', $status)
                    ->whereDate('timestamp', $date)
                    ->exists();

                if ($exists) {
                    $savedCount++;
                    continue;
                }
            } else {
                // Cek duplikat persis
                $exists = AttendanceLog::withTrashed()
                    ->where('device_user_id', (string) $userId)
                    ->where('timestamp', $timestamp)
                    ->exists();

                if ($exists) {
                    $savedCount++;
                    continue;
                }
            }

            // Simpan log absensi ke database
            $log = AttendanceLog::create([
                'device_id' => $deviceId,
                'device_user_id' => (string) $userId,
                'timestamp' => $timestamp,
                'student_id' => $student ? $student->id : null,
                'status_code' => $status
            ]);

            // Broadcast event websocket / realtime jika ada
            try {
                broadcast(new AttendanceCreated($log));
            } catch (\Exception $e) {
                // Abaikan jika broadcast driver lokal tidak aktif
            }

            $savedCount++;
        }

        return $savedCount;
    }

    /**
     * Update status mesin jadi online & last_seen_at
     */
    private function updateDeviceStatus(string $sn, string $ip)
    {
        try {
            $device = Device::where('ip_address', $ip)->first() ?: Device::first();
            if ($device) {
                $device->update([
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Gagal update device status ADMS: " . $e->getMessage());
        }
    }
}
