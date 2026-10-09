<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Exports\StudentTemplateExport;

class StudentController extends Controller
{
    public function importForm()
    {
        $classes = SchoolClass::with('major')->orderBy('name')->get();
        return view('students.import', compact('classes'));
    }

    public function downloadTemplate(Request $request)
    {
        $format = $request->query('format', 'xlsx');
        $fileName = 'template-siswa.' . ($format === 'csv' ? 'csv' : 'xlsx');

        return Excel::download(new StudentTemplateExport, $fileName);
    }

    public function importExcel(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv,txt|max:4096'
        ], [
            'file_excel.required' => 'Pilih file Excel (.xlsx) atau CSV yang akan diunggah.',
            'file_excel.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file_excel.max' => 'Ukuran file maksimal adalah 4MB.'
        ]);

        try {
            $import = new StudentsImport();
            Excel::import($import, $request->file('file_excel'));

            $message = "Berhasil mengimpor {$import->importedCount} data siswa.";
            if ($import->skippedCount > 0) {
                $skippedInfo = !empty($import->errors) ? ' (Catatan: ' . implode(', ', array_slice($import->errors, 0, 3)) . ')' : '';
                return redirect()->route('students.index')->with('success', "{$message} Namun terdapat {$import->skippedCount} baris yang dilewati{$skippedInfo}.");
            }

            return redirect()->route('students.index')->with('success', "{$message} Data telah otomatis disinkronkan ke mesin fingerprint.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimpor data. Pastikan format kolom sesuai template. Error: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $query = Student::with('schoolClass.major')
            ->withCount('fingerprintTemplates');

        // Filter berdasarkan kelas
        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        }

        // Filter pencarian nama atau NIS
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('device_user_id', 'like', "%{$search}%");
            });
        }

        // Filter status sidik jari
        if ($request->query('fingerprint') === 'registered') {
            $query->has('fingerprintTemplates');
        } elseif ($request->query('fingerprint') === 'unregistered') {
            $query->doesntHave('fingerprintTemplates');
        }

        $students = $query->orderBy('name')->get();
        $classes = SchoolClass::with('major')->orderBy('name')->get();
        $devices = Device::all();
        $selectedClassId = $request->class_id;
        $search = $request->search;
        $selectedFingerprint = $request->fingerprint;
        $totalStudents = Student::count();

        return view('students.index', compact('students', 'classes', 'devices', 'selectedClassId', 'search', 'selectedFingerprint', 'totalStudents'));
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
            'school_class_id' => 'required|exists:school_classes,id',
            'parent_phone' => 'nullable|string|max:25'
        ]);

        // Simpan ke database
        Student::updateOrCreate(
            ['device_user_id' => $request->device_user_id],
            [
                'name' => $request->name,
                'school_class_id' => $request->school_class_id,
                'parent_phone' => $request->parent_phone,
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
        $student->loadCount('fingerprintTemplates');
        $classes = SchoolClass::with('major')->get();
        $devices = Device::all();
        return view('students.edit', compact('student', 'classes', 'devices'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'school_class_id' => 'required|exists:school_classes,id',
            'parent_phone' => 'nullable|string|max:25',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ], [
            'photo.image' => 'File foto profil harus berupa gambar.',
            'photo.mimes' => 'Format foto harus berupa JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran file foto maksimal adalah 2MB.'
        ]);

        $data = [
            'name' => $request->name,
            'school_class_id' => $request->school_class_id,
            'parent_phone' => $request->parent_phone
        ];

        // Hapus foto jika diminta
        if ($request->has('remove_photo') && $request->remove_photo == '1') {
            if ($student->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo);
            }
            $data['photo'] = null;
        }

        // Upload foto baru jika ada
        if ($request->hasFile('photo')) {
            if ($student->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo);
            }
            $data['photo'] = $request->file('photo')->store('students', 'public');
        }

        $student->update($data);

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
        
        return redirect()->route('students.index')->with('success', 'Data siswa dan foto profil berhasil diperbarui.');
    }

    public function destroy(Request $request, Student $student)
    {
        // Hapus foto dari storage
        if ($student->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo);
        }

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
            // Hapus foto dari storage
            if ($student->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo);
            }

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