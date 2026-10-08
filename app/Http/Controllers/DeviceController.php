<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::all();
        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'ip_address' => 'required|ip',
            'port' => 'required|integer'
        ]);

        Device::create([
            'name' => $request->name,
            'ip_address' => $request->ip_address,
            'port' => $request->port,
            'status' => 'offline'
        ]);

        return redirect()->route('devices.index')->with('success', 'Mesin berhasil ditambahkan.');
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Mesin dihapus.');
    }

    public function checkStatus($id)
    {
        $device = Device::findOrFail($id);

        // 1. Cek apakah mesin aktif via ADMS dalam 5 menit terakhir
        $isRecentlyActive = $device->last_seen_at && \Carbon\Carbon::parse($device->last_seen_at)->gt(now()->subMinutes(5));

        if ($isRecentlyActive) {
            $device->update(['status' => 'online']);
            return redirect()->back()->with('success', 'Mesin terdeteksi ONLINE (ADMS Aktif: ' . \Carbon\Carbon::parse($device->last_seen_at)->diffForHumans() . ')');
        }

        // 2. Coba cek via Service Python jika ada URL service
        try {
            $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
            $response = Http::timeout(3)->post($serviceUrl . '/api/check_status', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);

            if ($response->successful() && ($response->json('status') === 'online')) {
                $device->update([
                    'status' => 'online',
                    'last_seen_at' => now()
                ]);
                return redirect()->back()->with('success', 'Mesin terdeteksi ONLINE via Bridge.');
            }
        } catch (\Exception $e) {
            // Service bridge tidak aktif
        }

        $device->update(['status' => 'offline']);
        return redirect()->back()->with('error', 'Mesin belum mengirim sinyal (OFFLINE). Pastikan setting ADMS dan Gateway/Internet di mesin sudah benar.');
    }

    public function clearAdmin($id)
    {
        $device = Device::findOrFail($id);
        
        // Simpan ke antrean perintah ADMS
        \Illuminate\Support\Facades\Cache::put('adms_cmd_ALL', 'CLEAR ADMIN', now()->addMinutes(10));
        
        // Hapus juga hak admin di database lokal
        \App\Models\Student::where('privilege', '!=', '0')->update(['privilege' => '0']);

        // Coba via service python jika aktif
        try {
            $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
            Http::timeout(3)->post($serviceUrl . '/api/clear_admins', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', 'Perintah buka kunci mesin telah dikirim ke antrean mesin.');
    }

    public function syncTime($id)
    {
        $device = Device::findOrFail($id);
        $time = now()->format('Y-m-d H:i:s');

        // Simpan ke antrean perintah ADMS
        \Illuminate\Support\Facades\Cache::put('adms_cmd_ALL', "SET OPTIONS DateTime={$time}", now()->addMinutes(10));

        // Coba via service python jika aktif
        try {
            $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
            Http::timeout(3)->post($serviceUrl . '/api/sync_time', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', "Perintah sinkronisasi waktu ({$time}) telah dikirim ke mesin.");
    }

    public function syncTimeAll()
    {
        $time = now()->format('Y-m-d H:i:s');
        \Illuminate\Support\Facades\Cache::put('adms_cmd_ALL', "SET OPTIONS DateTime={$time}", now()->addMinutes(10));

        return redirect()->back()->with('success', "Perintah sinkronisasi waktu ({$time}) telah dikirim ke semua mesin.");
    }
}
