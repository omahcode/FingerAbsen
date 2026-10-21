<?php

namespace App\Http\Controllers;

use App\Exports\TeacherTemplateExport;
use App\Imports\TeachersImport;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TeacherController extends Controller
{
    /**
     * Otorisasi akses Master Data Guru & Presensi Upacara
     */
    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->canAccessTeachers()) {
            abort(403, 'Akses terbatas untuk Administrator dan Kepala Sekolah.');
        }
    }

    /**
     * Tampilkan data guru (NIP, NAMA GURU, KATEGORI, SIDIK JARI, AKSI)
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $query = Teacher::withCount('fingerprintTemplates');

        // Filter Kategori (GURU MTK, GURU PPKN, GURU SEJARAH, GURU JURUSAN, dll)
        $selectedCategory = $request->query('category');
        if (! empty($selectedCategory)) {
            $query->where('category', $selectedCategory);
        }

        // Filter Status Sidik Jari
        $selectedFingerprint = $request->query('fingerprint');
        if ($selectedFingerprint === 'registered') {
            $query->has('fingerprintTemplates');
        } elseif ($selectedFingerprint === 'unregistered') {
            $query->doesntHave('fingerprintTemplates');
        }

        // Pencarian Nama / NIP
        $search = $request->query('search');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('device_user_id', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name')->get();

        // Kategori dinamis dari database digabung dengan kategori bawaan
        $existingCategories = Teacher::distinct()->pluck('category')->filter()->toArray();
        $categories = array_values(array_unique(array_merge(Teacher::$defaultCategories, $existingCategories)));
        sort($categories);

        $totalTeachers = Teacher::count();
        $registeredFpCount = Teacher::has('fingerprintTemplates')->count();
        $unregisteredFpCount = $totalTeachers - $registeredFpCount;

        // Presensi Guru Hari Ini (khusus Upacara / Tap Mesin)
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $todayAttendanceCount = AttendanceLog::whereNotNull('teacher_id')
            ->whereDate('timestamp', $today)
            ->distinct('teacher_id')
            ->count('teacher_id');

        return view('teachers.index', compact(
            'teachers',
            'categories',
            'selectedCategory',
            'selectedFingerprint',
            'search',
            'totalTeachers',
            'registeredFpCount',
            'unregisteredFpCount',
            'todayAttendanceCount'
        ));
    }

    /**
     * Simpan data guru baru
     */
    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'nip' => 'required|string|max:50|unique:teachers,nip',
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:100',
            'device_user_id' => 'nullable|string|max:50|unique:teachers,device_user_id',
            'phone' => 'nullable|string|max:30',
        ]);

        // Default PIN mesin ke NIP jika tidak diisi manual
        $pin = ! empty($validated['device_user_id']) ? $validated['device_user_id'] : $validated['nip'];

        // Cek PIN unik di tabel students juga agar tidak bentrok di mesin
        $teacher = Teacher::create([
            'nip' => $validated['nip'],
            'name' => $validated['name'],
            'category' => strtoupper(trim($validated['category'])),
            'device_user_id' => $pin,
            'phone' => $validated['phone'] ?? null,
        ]);

        // Kirim perintah registrasi nama guru ke semua mesin fingerprint aktif
        try {
            IclockController::queueCommand("DATA USER PIN={$pin}\tName={$teacher->name}\tPri=0\tGrp=1");
        } catch (\Throwable $e) {
            // Abaikan jika tabel device_commands belum siap
        }

        return redirect()->route('teachers.index')
            ->with('success', "Data Guru '{$teacher->name}' berhasil ditambahkan dan disinkronkan ke mesin!");
    }

    /**
     * Perbarui data guru
     */
    public function update(Request $request, Teacher $teacher)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'nip' => 'required|string|max:50|unique:teachers,nip,'.$teacher->id,
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:100',
            'device_user_id' => 'nullable|string|max:50|unique:teachers,device_user_id,'.$teacher->id,
            'phone' => 'nullable|string|max:30',
        ]);

        $oldPin = $teacher->device_user_id;
        $newPin = ! empty($validated['device_user_id']) ? $validated['device_user_id'] : $validated['nip'];

        $teacher->update([
            'nip' => $validated['nip'],
            'name' => $validated['name'],
            'category' => strtoupper(trim($validated['category'])),
            'device_user_id' => $newPin,
            'phone' => $validated['phone'] ?? null,
        ]);

        // Sinkronkan update ke mesin
        try {
            if ($oldPin !== $newPin) {
                IclockController::queueCommand("DATA DELETE USER PIN={$oldPin}");
            }
            IclockController::queueCommand("DATA USER PIN={$newPin}\tName={$teacher->name}\tPri=0\tGrp=1");
        } catch (\Throwable $e) {
        }

        return redirect()->route('teachers.index')
            ->with('success', "Data Guru '{$teacher->name}' berhasil diperbarui.");
    }

    /**
     * Hapus data guru
     */
    public function destroy(Teacher $teacher)
    {
        $this->authorizeAccess();

        $name = $teacher->name;
        $pin = $teacher->device_user_id;

        // Hapus dari mesin fingerprint
        try {
            IclockController::queueCommand("DATA DELETE USER PIN={$pin}");
            IclockController::queueCommand("DATA DELETE FP PIN={$pin}");
        } catch (\Throwable $e) {
        }

        $teacher->delete();

        return redirect()->route('teachers.index')
            ->with('success', "Data Guru '{$name}' berhasil dihapus dari sistem dan mesin.");
    }

    /**
     * Hapus beberapa data guru sekaligus (bulk delete)
     */
    public function destroyMultiple(Request $request)
    {
        $this->authorizeAccess();

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Pilih minimal satu data guru untuk dihapus.');
        }

        $teachers = Teacher::whereIn('id', $ids)->get();
        $count = $teachers->count();

        foreach ($teachers as $teacher) {
            try {
                IclockController::queueCommand("DATA DELETE USER PIN={$teacher->device_user_id}");
                IclockController::queueCommand("DATA DELETE FP PIN={$teacher->device_user_id}");
            } catch (\Throwable $e) {
            }
            $teacher->delete();
        }

        return redirect()->route('teachers.index')
            ->with('success', "{$count} data guru terpilih berhasil dihapus.");
    }

    /**
     * Push sinkronisasi seluruh data guru ke semua mesin fingerprint
     */
    public function syncAll()
    {
        $this->authorizeAccess();

        $teachers = Teacher::all();
        $devices = Device::all();

        if ($devices->isEmpty()) {
            return back()->with('error', 'Belum ada mesin fingerprint yang terdaftar.');
        }

        $count = 0;
        foreach ($teachers as $t) {
            IclockController::queueCommand("DATA USER PIN={$t->device_user_id}\tName={$t->name}\tPri=0\tGrp=1");
            $count++;
        }

        return back()->with('success', "Berhasil menjadwalkan sinkronisasi {$count} data guru ke {$devices->count()} mesin fingerprint.");
    }

    /**
     * Tampilkan monitoring presensi guru (khusus Upacara Hari Senin / Acara Sekolah)
     */
    public function ceremonyAttendance(Request $request)
    {
        $this->authorizeAccess();

        $date = $request->query('date', Carbon::today('Asia/Jakarta')->toDateString());
        $category = $request->query('category');

        $teachersQuery = Teacher::orderBy('name');
        if (! empty($category)) {
            $teachersQuery->where('category', $category);
        }
        $teachers = $teachersQuery->get();

        // Ambil log absensi guru pada tanggal tersebut
        $logs = AttendanceLog::whereNotNull('teacher_id')
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->groupBy('teacher_id');

        // Kategori list untuk filter
        $existingCategories = Teacher::distinct()->pluck('category')->filter()->toArray();
        $categories = array_values(array_unique(array_merge(Teacher::$defaultCategories, $existingCategories)));
        sort($categories);

        $attendedCount = 0;
        $unattendedCount = 0;

        $reportData = $teachers->map(function ($teacher) use ($logs, &$attendedCount, &$unattendedCount) {
            $teacherLogs = $logs->get($teacher->id);
            $hasAttended = ! empty($teacherLogs) && $teacherLogs->isNotEmpty();

            if ($hasAttended) {
                $attendedCount++;
                $firstLog = $teacherLogs->first();
                $tapTime = Carbon::parse($firstLog->timestamp)->format('H:i:s');
                $status = 'Hadir';
            } else {
                $unattendedCount++;
                $tapTime = '-';
                $status = 'Belum Tap';
            }

            return [
                'id' => $teacher->id,
                'nip' => $teacher->nip,
                'name' => $teacher->name,
                'category' => $teacher->category,
                'device_user_id' => $teacher->device_user_id,
                'has_attended' => $hasAttended,
                'tap_time' => $tapTime,
                'status' => $status,
            ];
        });

        return view('teachers.ceremony', compact(
            'reportData',
            'date',
            'category',
            'categories',
            'attendedCount',
            'unattendedCount',
            'teachers'
        ));
    }

    /**
     * Tampilkan form upload Excel untuk data guru
     */
    public function importForm()
    {
        $this->authorizeAccess();

        $existingCategories = Teacher::distinct()->pluck('category')->filter()->toArray();
        $categories = array_values(array_unique(array_merge(Teacher::$defaultCategories, $existingCategories)));
        sort($categories);

        return view('teachers.import', compact('categories'));
    }

    /**
     * Proses impor file Excel / CSV data guru
     */
    public function importExcel(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:4096',
        ], [
            'file_excel.required' => 'Pilih file Excel atau CSV terlebih dahulu.',
            'file_excel.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file_excel.max' => 'Ukuran file tidak boleh melebihi 4 MB.',
        ]);

        try {
            $import = new TeachersImport;
            Excel::import($import, $request->file('file_excel'));

            $msg = "Berhasil mengimpor {$import->importedCount} data guru ke sistem dan mesin!";
            if ($import->skippedCount > 0) {
                $msg .= " ({$import->skippedCount} baris kosong/tidak valid dilewati).";
            }

            return redirect()->route('teachers.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses file Excel: '.$e->getMessage());
        }
    }

    /**
     * Download template Excel untuk impor data guru
     */
    public function downloadTemplate(Request $request)
    {
        $this->authorizeAccess();

        $format = $request->query('format', 'xlsx');
        if (! in_array($format, ['xlsx', 'csv'])) {
            $format = 'xlsx';
        }

        $filename = 'template_import_guru_'.date('Ymd_His').'.'.$format;
        $writerType = ($format === 'csv') ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(new TeacherTemplateExport, $filename, $writerType);
    }
}
