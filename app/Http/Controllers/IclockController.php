<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Student;
use App\Models\FingerprintTemplate;
use App\Models\AttendanceLog;
use App\Models\SchoolClass;
use App\Events\AttendanceCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class IclockController extends Controller
{
    /**
     * Catat log komunikasi ADMS ke memori web untuk monitoring
     */
    public static function recordAdmsLog(string $endpoint, string $method, string $sn, string $info, ?string $body = null)
    {
        try {
            $logs = Cache::get('adms_recent_logs', []);
            array_unshift($logs, [
                'time' => now()->format('H:i:s'),
                'endpoint' => $endpoint,
                'method' => $method,
                'sn' => $sn,
                'info' => $info,
                'preview' => $body ? substr(trim($body), 0, 150) : null
            ]);
            $logs = array_slice($logs, 0, 30);
            Cache::put('adms_recent_logs', $logs, now()->addDays(1));
        } catch (\Exception $e) {}
    }

    /**
     * Ekstrak isi request body dari mesin (raw text, form-data, atau stream)
     */
    private function extractRequestBody(Request $request): string
    {
        $content = $request->getContent();
        if (!empty($content)) {
            return (string) $content;
        }

        $input = @file_get_contents('php://input');
        if (!empty($input)) {
            return (string) $input;
        }

        if (!empty($request->all())) {
            $parts = [];
            foreach ($request->all() as $k => $v) {
                if (is_string($v) && strlen($v) > 0) {
                    $parts[] = "{$k}={$v}";
                }
            }
            return implode("\n", $parts);
        }

        return '';
    }

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
            self::recordAdmsLog('/iclock/cdata', 'GET', $sn, 'Inisialisasi Handshake ADMS');
            $responseContent = "GET OPTION FROM: {$sn}\n" .
                "ATTLOGStamp=None\n" .
                "OPERLOGStamp=None\n" .
                "ATTPHOTOStamp=None\n" .
                "ServerVersion=3.1.1\n" .
                "PushOptionsFlag=1\n" .
                "PushProtVer=2.4.1\n" .
                "PushOptions=User,FP,AttLog,BioData\n" .
                "ErrorDelay=15\n" .
                "Delay=5\n" .
                "TransTimes=00:00;14:05\n" .
                "TransInterval=1\n" .
                "TransFlag=TransData AttLog\tOpLog\tAttPhoto\tEnrollFP\tEnrollUser\tFP\tUser\tBioData\tFPTmp\tBioPhoto\n" .
                "TimeZone=7\n" .
                "Realtime=1\n" .
                "Encrypt=0\n";

            return response($responseContent, 200)
                ->header('Content-Type', 'text/plain');
        }

        // Jika request POST: Mesin mengirim data
        $table = strtoupper($request->query('table', 'ATTLOG'));
        $body = $this->extractRequestBody($request);

        if (empty($body)) {
            return response("OK\n", 200)->header('Content-Type', 'text/plain');
        }

        $count = 0;
        if (str_contains($table, 'TMP') || str_contains($table, 'FP') || str_contains($table, 'TEMPLATE') || str_contains($body, 'TMP=')) {
            $count = $this->processFingerprintLogs($body, $sn);
            self::recordAdmsLog('/iclock/cdata', 'POST', $sn, "Kirim Template Sidik Jari (Table: {$table}) - {$count} data", $body);
        } elseif (str_contains($table, 'USER') || str_contains($table, 'BIODATA') || str_contains($body, 'Name=')) {
            $count = $this->processUserLogs($body, $sn);
            self::recordAdmsLog('/iclock/cdata', 'POST', $sn, "Kirim Data User (Table: {$table}) - {$count} data", $body);
        } elseif ($table === 'ATTLOG' || preg_match('/\d{4}-\d{2}-\d{2}/', $body)) {
            $count = $this->processAttendanceLogs($body, $sn, $ip);
            self::recordAdmsLog('/iclock/cdata', 'POST', $sn, "Kirim Log Absensi (Table: {$table}) - {$count} data", $body);
        } else {
            self::recordAdmsLog('/iclock/cdata', 'POST', $sn, "Kirim Data Lain (Table: {$table})", $body);
        }

        return response("OK: {$count}\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint /iclock/fdata khusus pengunggahan template sidik jari (FINGERTMP / BIODATA)
     */
    public function fdata(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $ip = $request->ip();
        $this->updateDeviceStatus($sn, $ip);

        $table = strtoupper($request->query('table', 'FINGERTMP'));
        $body = $this->extractRequestBody($request);

        Log::info("ADMS fdata from SN {$sn}, Table: {$table}, Query: " . json_encode($request->query()) . ", Length: " . strlen($body));

        $count = 0;

        // Format 1: PIN & FID ada di parameter query URL
        if ($request->has('PIN') && !empty($body)) {
            $pin = $request->query('PIN');
            $fid = $request->query('FID', 0);
            $size = $request->query('size', strlen($body));
            $tmp = base64_encode($body);

            $defaultClass = SchoolClass::first();
            $student = Student::firstOrCreate(
                ['device_user_id' => (string) $pin],
                [
                    'name' => 'Siswa ' . $pin,
                    'school_class_id' => $defaultClass ? $defaultClass->id : 1,
                    'privilege' => '0'
                ]
            );

            FingerprintTemplate::updateOrCreate(
                [
                    'device_user_id' => (string) $pin,
                    'finger_index' => (int) $fid
                ],
                [
                    'student_id' => $student->id,
                    'size' => (int) $size,
                    'valid' => 1,
                    'template_data' => $tmp
                ]
            );

            self::queueCommand("DATA FP PIN={$pin}\tFID={$fid}\tSize={$size}\tValid=1\tTMP={$tmp}", null, $sn);
            self::queueCommand("DATA UPDATE FINGERTMP PIN={$pin}\tFID={$fid}\tSize={$size}\tValid=1\tTMP={$tmp}", null, $sn);
            $count = 1;
            self::recordAdmsLog('/iclock/fdata', 'POST', $sn, "Kirim Template Sidik Jari PIN: {$pin}, Finger: {$fid}", $tmp);
        } elseif (!empty($body)) {
            // Format 2: Multi-line text template di body
            $count = $this->processFingerprintLogs($body, $sn);
            self::recordAdmsLog('/iclock/fdata', 'POST', $sn, "Kirim Multi Template Sidik Jari - {$count} data", $body);
        }

        return response("OK: {$count}\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint /iclock/registry untuk pendaftaran awal mesin
     */
    public function registry(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $this->updateDeviceStatus($sn, $request->ip());
        self::recordAdmsLog('/iclock/registry', 'GET', $sn, 'Pendaftaran Otomatis Mesin (Registry)');

        return response("RegistryCode=1\nServerVersion=3.1.1\n", 200)
            ->header('Content-Type', 'text/plain');
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
     * Helper untuk menambahkan perintah ke antrean ADMS tiap mesin
     */
    public static function queueCommand(string $command, ?string $targetSn = null, ?string $excludeSn = null)
    {
        $devices = Device::all();

        if ($targetSn) {
            $queueKey = "adms_queue_" . $targetSn;
            $queue = Cache::get($queueKey, []);
            $queue[] = $command;
            Cache::put($queueKey, $queue, now()->addDays(2));
            return;
        }

        // Broadcast ke setiap mesin secara independen
        if ($devices->isNotEmpty()) {
            foreach ($devices as $device) {
                if ($excludeSn && $device->serial_number === $excludeSn) {
                    continue;
                }

                $keys = [];
                if (!empty($device->serial_number)) {
                    $keys[] = "adms_queue_" . $device->serial_number;
                }
                $keys[] = "adms_queue_DEVICE_" . $device->id;

                foreach ($keys as $k) {
                    $q = Cache::get($k, []);
                    $q[] = $command;
                    Cache::put($k, $q, now()->addDays(2));
                }
            }
        }

        // Antrean global fallback
        $globalKey = "adms_queue_GLOBAL";
        $globalQueue = Cache::get($globalKey, []);
        $globalQueue[] = $command;
        Cache::put($globalKey, $globalQueue, now()->addDays(2));
    }

    /**
     * Antrean perintah ke mesin (Get Request)
     */
    public function getRequest(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $device = $this->updateDeviceStatus($sn, $request->ip());

        // Ambil antrean spesifik untuk mesin ini
        $queueKey = "adms_queue_" . $sn;
        $queue = Cache::get($queueKey, []);

        if (empty($queue) && $device) {
            $queueKey = "adms_queue_DEVICE_" . $device->id;
            $queue = Cache::get($queueKey, []);
        }

        if (empty($queue)) {
            $queueKey = "adms_queue_GLOBAL";
            $queue = Cache::get($queueKey, []);
        }

        if (!empty($queue)) {
            // Ambil hingga 15 perintah sekaligus (batch)
            $batch = [];
            $batchCount = min(count($queue), 15);
            for ($i = 0; $i < $batchCount; $i++) {
                $batch[] = array_shift($queue);
            }
            Cache::put($queueKey, $queue, now()->addDays(2));

            $responseOutput = "";
            foreach ($batch as $cmd) {
                $cmdId = rand(1000, 9999);
                Log::info("Mengirim ADMS Command ke mesin {$sn}: C:{$cmdId}:{$cmd}");
                $responseOutput .= "C:{$cmdId}:{$cmd}\n";
            }

            self::recordAdmsLog('/iclock/getrequest', 'GET', $sn, "Kirim " . count($batch) . " Perintah Sinkronisasi ke Mesin", $responseOutput);

            return response($responseOutput, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Laporan hasil eksekusi perintah dari mesin
     */
    public function deviceCmd(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN');
        $this->updateDeviceStatus($sn, $request->ip());
        $body = $this->extractRequestBody($request);
        Log::info("Hasil ADMS Command dari mesin {$sn}: " . $body);

        $fpCount = 0;
        $userCount = 0;

        if (str_contains($body, 'TMP=') || str_contains($body, 'FID=')) {
            $fpCount = $this->processFingerprintLogs($body, $sn);
        }
        if (str_contains($body, 'USER') || str_contains($body, 'PIN=')) {
            $userCount = $this->processUserLogs($body, $sn);
        }

        $info = 'Hasil Eksekusi Perintah Mesin';
        if ($fpCount > 0) $info .= " (Ditemukan {$fpCount} sidik jari)";
        if ($userCount > 0) $info .= " (Ditemukan {$userCount} user)";

        self::recordAdmsLog('/iclock/devicecmd', 'POST', $sn, $info, $body);

        return response("OK\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Parse dan simpan raw data ATTLOG dari mesin
     */
    private function processAttendanceLogs(string $rawBody, string $sn, string $ip): int
    {
        $device = $this->updateDeviceStatus($sn, $ip) ?: Device::first();
        $deviceId = $device ? $device->id : null;

        $lines = preg_split('/\r\n|\r|\n/', trim($rawBody));
        $savedCount = 0;

        Log::info("ADMS Raw ATTLOG Body from SN {$sn} (IP: {$ip}):\n" . $rawBody);

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
                Log::warning("ADMS: Format line tidak valid: {$line}");
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
                    Log::info("ADMS Skip: Siswa {$student->name} sudah absen status {$status} hari ini.");
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
                    Log::info("ADMS Skip: Duplikat timestamp {$timestamp} untuk user {$userId}");
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

            Log::info("ADMS Success: Log absensi berhasil disimpan (ID: {$log->id}, User: {$userId}, Siswa: " . ($student->name ?? 'Belum terdaftar') . ")");

            // Masukkan pengiriman notifikasi WhatsApp ke antrean antrean (Queue)
            if ($student && !empty($student->parent_phone)) {
                \App\Jobs\SendWhatsAppAttendanceJob::dispatch($student, $timestamp, $status, $device ? $device->name : null);
            }

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
     * Parse data pendaftaran User dari mesin dan sebar ke mesin lain
     */
    private function processUserLogs(string $rawBody, string $sn): int
    {
        Log::info("ADMS User Data from SN {$sn}:\n" . $rawBody);
        $lines = preg_split('/\r\n|\r|\n/', trim($rawBody));
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $tokens = preg_split('/\t+|\s{2,}/', $line);
            $params = [];
            foreach ($tokens as $token) {
                if (strpos($token, '=') !== false) {
                    [$k, $v] = explode('=', $token, 2);
                    $params[strtoupper(trim($k))] = trim($v);
                }
            }

            $pin = $params['PIN'] ?? $params['USERID'] ?? null;
            $name = $params['NAME'] ?? null;
            $pri = $params['PRI'] ?? $params['PRIVILEGE'] ?? 0;

            if (!$pin && preg_match('/PIN=([0-9a-zA-Z_-]+)/i', $line, $m)) {
                $pin = trim($m[1]);
            }
            if (!$name && preg_match('/Name=([^\t\r\n]+)/i', $line, $m)) {
                $name = trim($m[1]);
            }

            if ($pin) {
                $defaultClass = SchoolClass::first();
                $student = Student::firstOrCreate(
                    ['device_user_id' => (string) $pin],
                    [
                        'name' => $name ?: 'Siswa ' . $pin,
                        'school_class_id' => $defaultClass ? $defaultClass->id : 1,
                        'privilege' => (string) $pri
                    ]
                );

                if ($name && $student->name === 'Siswa ' . $pin) {
                    $student->update(['name' => $name]);
                }

                // Replicate ke mesin lain
                self::queueCommand("DATA USER PIN={$pin}\tName=" . ($student->name ?? $name) . "\tPri={$pri}\tGrp=1", null, $sn);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Parse template sidik jari baru dari mesin dan sebar ke mesin lain (Base64 Safe)
     */
    private function processFingerprintLogs(string $rawBody, string $sn): int
    {
        Log::info("ADMS Fingerprint Template received from SN {$sn}:\n" . substr($rawBody, 0, 300) . "...");
        $lines = preg_split('/\r\n|\r|\n/', trim($rawBody));
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $pin = null;
            $fid = 0;
            $size = 0;
            $tmp = null;

            // Ekstrak via token pemisah tab terlebih dahulu
            $tokens = explode("\t", $line);
            $params = [];
            foreach ($tokens as $token) {
                $token = trim($token);
                if (strpos($token, '=') !== false) {
                    [$k, $v] = explode('=', $token, 2);
                    $params[strtoupper(trim($k))] = trim($v);
                }
            }

            $pin = $params['PIN'] ?? $params['USERID'] ?? null;
            $fid = isset($params['FID']) ? (int) $params['FID'] : (isset($params['INDEX']) ? (int) $params['INDEX'] : (isset($params['NO']) ? (int) $params['NO'] : null));
            $size = isset($params['SIZE']) ? (int) $params['SIZE'] : 0;
            $tmp = $params['TMP'] ?? $params['TEMPLATE'] ?? null;

            // Ekstrak via Regex aman jika tidak terdeteksi via token
            if (!$pin && preg_match('/PIN=([0-9a-zA-Z_-]+)/i', $line, $m)) {
                $pin = trim($m[1]);
            }
            if ($fid === null && (preg_match('/FID=([0-9]+)/i', $line, $m) || preg_match('/Index=([0-9]+)/i', $line, $m) || preg_match('/No=([0-9]+)/i', $line, $m))) {
                $fid = (int) $m[1];
            }
            if (!$size && preg_match('/Size=([0-9]+)/i', $line, $m)) {
                $size = (int) $m[1];
            }
            if (!$tmp && preg_match('/TMP=([A-Za-z0-9+\/=_~-]+)/i', $line, $m)) {
                $tmp = trim($m[1]);
            }

            // Fallback: positional TSV (PIN\tFID\tSize\tValid\tTMP atau PIN\tFID\tTMP)
            if (!$pin || !$tmp) {
                if (count($tokens) >= 3) {
                    $pin = trim($tokens[0]);
                    $fid = (int) trim($tokens[1]);
                    $tmp = trim(end($tokens));
                    $size = strlen($tmp);
                }
            }

            if ($pin && $tmp) {
                $fid = $fid ?? 0;
                $defaultClass = SchoolClass::first();
                $student = Student::firstOrCreate(
                    ['device_user_id' => (string) $pin],
                    [
                        'name' => 'Siswa ' . $pin,
                        'school_class_id' => $defaultClass ? $defaultClass->id : 1,
                        'privilege' => '0'
                    ]
                );

                FingerprintTemplate::updateOrCreate(
                    [
                        'device_user_id' => (string) $pin,
                        'finger_index' => (int) $fid
                    ],
                    [
                        'student_id' => $student->id,
                        'size' => (int) $size ?: strlen($tmp),
                        'valid' => 1,
                        'template_data' => $tmp
                    ]
                );

                Log::info("ADMS Template Saved: PIN {$pin}, Finger {$fid}, Size: " . strlen($tmp));

                // Replicate template sidik jari ke mesin lain
                self::queueCommand("DATA FP PIN={$pin}\tFID={$fid}\tSize={$size}\tValid=1\tTMP={$tmp}", null, $sn);
                self::queueCommand("DATA UPDATE FINGERTMP PIN={$pin}\tFID={$fid}\tSize={$size}\tValid=1\tTMP={$tmp}", null, $sn);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Update status mesin jadi online & last_seen_at dengan pencocokan SN
     */
    private function updateDeviceStatus(string $sn, string $ip): ?Device
    {
        try {
            $device = null;

            // 1. Cari berdasarkan Serial Number (SN) jika ada
            if ($sn !== 'UNKNOWN' && !empty($sn)) {
                $device = Device::where('serial_number', $sn)->first();
            }

            // 2. Jika belum ditemukan dengan SN, cari device yang belum punya SN atau cari berdasarkan IP
            if (!$device) {
                $device = Device::where('ip_address', $ip)->first()
                       ?: Device::whereNull('serial_number')->first();

                // Simpan SN ke device tersebut jika SN valid
                if ($device && $sn !== 'UNKNOWN' && !empty($sn) && empty($device->serial_number)) {
                    $device->update(['serial_number' => $sn]);
                }
            }

            // 3. Jika tetap belum ada device sama sekali di DB, buatkan otomatis
            if (!$device && $sn !== 'UNKNOWN') {
                $device = Device::create([
                    'name' => 'Mesin ' . substr($sn, -4),
                    'serial_number' => $sn,
                    'ip_address' => $ip,
                    'port' => 4370,
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);
                return $device;
            }

            if ($device) {
                $device->update([
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);
            }

            return $device;
        } catch (\Exception $e) {
            Log::warning("Gagal update device status ADMS: " . $e->getMessage());
            return null;
        }
    }
}
