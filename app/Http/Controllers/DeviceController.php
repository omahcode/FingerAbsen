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

        try {
            // Panggil API Python
            $response = Http::timeout(6)->post('http://127.0.0.1:5000/api/check_status', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $device->update([
                    'status' => $data['status'] ?? 'offline',
                    'last_seen_at' => (isset($data['status']) && $data['status'] === 'online') ? now() : $device->last_seen_at
                ]);
            } else {
                $device->update(['status' => 'offline']);
            }
        } catch (\Exception $e) {
            $device->update(['status' => 'offline']);
        }

        return redirect()->back()->with('success', 'Status koneksi diperbarui.');
    }

    public function clearAdmin($id)
    {
        $device = Device::findOrFail($id);
        try {
            $response = Http::timeout(10)->post('http://127.0.0.1:5000/api/clear_admins', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                // Hapus juga hak admin di database lokal
                \App\Models\Student::where('privilege', '!=', '0')->update(['privilege' => '0']);
                return redirect()->back()->with('success', 'Hak Admin berhasil dihapus dari mesin dan sistem. Gembok mesin sudah terbuka.');
            }
            return redirect()->back()->with('error', 'Gagal buka kunci mesin: ' . $response->json('error', 'Unknown Error'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal koneksi ke Service Python: ' . $e->getMessage());
        }
    }

    public function syncTime($id)
    {
        $device = Device::findOrFail($id);
        try {
            $response = Http::timeout(10)->post('http://127.0.0.1:5000/api/sync_time', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $time = $response->json('time_synced');
                return redirect()->back()->with('success', "Waktu pada mesin {$device->name} berhasil disinkronkan menjadi: $time.");
            }
            return redirect()->back()->with('error', 'Gagal sinkronisasi waktu mesin: ' . $response->json('error', 'Unknown Error'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal koneksi ke Service Python: ' . $e->getMessage());
        }
    }

    public function syncTimeAll()
    {
        $devices = Device::all();
        $successCount = 0;
        $failedCount = 0;

        foreach ($devices as $device) {
            try {
                $response = Http::timeout(5)->post('http://127.0.0.1:5000/api/sync_time', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port
                ]);

                if ($response->successful() && $response->json('status') === 'success') {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        if ($failedCount > 0) {
            return redirect()->back()->with('success', "Sync jam selesai. Berhasil: $successCount mesin. Gagal: $failedCount mesin (pastikan mesin menyala).");
        }

        return redirect()->back()->with('success', "Waktu pada semua mesin ($successCount mesin) berhasil disinkronkan dengan jam komputer ini.");
    }
}
