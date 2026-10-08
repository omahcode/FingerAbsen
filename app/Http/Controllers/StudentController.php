<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;

class StudentController extends Controller
{
    public function importForm()
    {
        return view('students.import');
    }

    public function importExcel(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            Excel::import(new StudentsImport(), $request->file('file_excel'));
            return redirect()->route('students.index')->with('success', 'Data siswa berhasil diimport dan otomatis disebar ke seluruh mesin.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimport data. Pastikan format Excel sesuai. Error: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $students = Student::with('schoolClass.major')->get();
        $devices = Device::all();
        return view('students.index', compact('students', 'devices'));
    }

    public function create()
    {
        $classes = SchoolClass::with('major')->get();
        $devices = Device::all();
        return view('students.create', compact('classes', 'devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_user_id' => 'required|numeric',
            'name' => 'required',
            'school_class_id' => 'required|exists:school_classes,id'
        ]);

        // Simpan ke database
        Student::updateOrCreate(
            ['device_user_id' => $request->device_user_id],
            [
                'name' => $request->name,
                'school_class_id' => $request->school_class_id,
                'privilege' => '0'
            ]
        );

        // Kirim ke antrean ADMS
        \App\Http\Controllers\IclockController::queueCommand("DATA USER PIN={$request->device_user_id}\tName={$request->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");
        \App\Http\Controllers\IclockController::queueCommand("DATA UPDATE USER PIN={$request->device_user_id}\tName={$request->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");

        // Coba via service Python jika aktif
        $devices = Device::all();
        foreach ($devices as $device) {
            try {
                $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
                Http::timeout(2)->post($serviceUrl . '/api/set_user', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port,
                    'device_user_id' => $request->device_user_id,
                    'name' => $request->name,
                    'privilege' => 0
                ]);
            } catch (\Exception $e) {}
        }
        
        return redirect()->route('students.index')->with('success', "Data siswa {$request->name} berhasil disimpan dan dikirim ke mesin.");
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::with('major')->get();
        $devices = Device::all();
        return view('students.edit', compact('student', 'classes', 'devices'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required',
            'school_class_id' => 'required|exists:school_classes,id'
        ]);

        $student->update([
            'name' => $request->name,
            'school_class_id' => $request->school_class_id
        ]);

        // Kirim ke antrean ADMS
        \App\Http\Controllers\IclockController::queueCommand("DATA USER PIN={$student->device_user_id}\tName={$request->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");
        \App\Http\Controllers\IclockController::queueCommand("DATA UPDATE USER PIN={$student->device_user_id}\tName={$request->name}\tPri=0\tPasswd=\tCard=\tGrp=1\tTZ=0000000100000000\tVerify=0");

        // Coba via service Python jika aktif
        $devices = Device::all();
        foreach ($devices as $device) {
            try {
                $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
                Http::timeout(2)->post($serviceUrl . '/api/set_user', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port,
                    'device_user_id' => $student->device_user_id,
                    'name' => $request->name,
                    'privilege' => 0
                ]);
            } catch (\Exception $e) {}
        }
        
        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Request $request, Student $student)
    {
        // Kirim ke antrean ADMS untuk hapus user di mesin
        \App\Http\Controllers\IclockController::queueCommand("DATA DELETE USER PIN={$student->device_user_id}");

        $devices = Device::all();
        foreach ($devices as $device) {
            try {
                $serviceUrl = env('FINGERPRINT_SERVICE_URL', 'http://127.0.0.1:5000');
                Http::timeout(2)->post($serviceUrl . '/api/delete_user', [
                    'ip_address' => $device->ip_address,
                    'port' => $device->port,
                    'device_user_id' => $student->device_user_id
                ]);
            } catch (\Exception $e) {}
        }

        $student->delete();
        return redirect()->route('students.index')->with('success', 'Siswa berhasil dihapus dari sistem dan mesin.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:students,id'
        ]);

        $students = Student::whereIn('id', $request->ids)->get();
        $devices = Device::all();
        $count = 0;

        foreach ($students as $student) {
            // Hapus dari mesin
            foreach ($devices as $device) {
                try {
                    Http::timeout(2)->post('http://127.0.0.1:5000/api/delete_user', [
                        'ip_address' => $device->ip_address,
                        'port' => $device->port,
                        'device_user_id' => $student->device_user_id
                    ]);
                } catch (\Exception $e) {
                    // Abaikan jika mesin offline agar proses tetap jalan
                }
            }
            $student->delete();
            $count++;
        }

        return redirect()->route('students.index')->with('success', "$count Siswa berhasil dihapus secara massal dari web dan seluruh mesin.");
    }

    public function sync(Request $request)
    {
        $request->validate(['device_id' => 'required', 'school_class_id' => 'required']);
        $device = Device::findOrFail($request->device_id);

        try {
            $response = Http::timeout(10)->post('http://127.0.0.1:5000/api/get_users', [
                'ip_address' => $device->ip_address,
                'port' => $device->port
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                $users = $response->json('users');
                $count = 0;
                foreach ($users as $u) {
                    if (!$u['user_id']) continue;
                    // Hanya tarik user biasa (privilege = 0) sebagai siswa
                    if ($u['privilege'] == 0) {
                        Student::firstOrCreate(
                            ['device_user_id' => (string) $u['user_id']],
                            [
                                'name' => $u['name'] ?: 'Siswa ' . $u['user_id'],
                                'school_class_id' => $request->school_class_id,
                                'privilege' => '0'
                            ]
                        );
                        $count++;
                    }
                }
                return redirect()->back()->with('success', "Berhasil sinkronisasi $count data siswa dari mesin.");
            }
            return redirect()->back()->with('error', 'Gagal menarik data.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal koneksi: ' . $e->getMessage());
        }
    }
}