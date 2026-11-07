<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\FingerprintTemplate;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Console\Command;

class CleanupDuplicateTeachers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:duplicate-teachers';

    /**
     * The console command description.
     */
    protected $description = 'Bersihkan data guru atau akun admin yang terduplikasi secara keliru sebagai siswa/kelas admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan data duplikat guru & siswa...');

        $teacherPins = Teacher::whereNotNull('device_user_id')
            ->pluck('device_user_id')
            ->filter()
            ->all();

        // 1. Cari siswa yang PIN-nya sama dengan guru
        $duplicateStudents = Student::whereIn('device_user_id', $teacherPins)->get();
        $cleanedStudentsCount = 0;

        foreach ($duplicateStudents as $student) {
            $teacher = Teacher::where('device_user_id', $student->device_user_id)->first();
            $this->warn("- Menemukan siswa duplikat: {$student->name} (PIN: {$student->device_user_id}) yang sebenarnya adalah Guru.");

            if ($teacher) {
                // Alihkan attendance logs ke guru
                AttendanceLog::where('student_id', $student->id)->update([
                    'student_id' => null,
                    'teacher_id' => $teacher->id,
                ]);

                // Alihkan fingerprint templates ke guru
                FingerprintTemplate::where('student_id', $student->id)->update([
                    'student_id' => null,
                    'teacher_id' => $teacher->id,
                ]);
            }

            $student->delete();
            $cleanedStudentsCount++;
        }

        // 2. Cari siswa bernama 'admin' atau 'administrator' yang nyasar
        $adminStudents = Student::where(function ($q) {
            $q->whereRaw('LOWER(name) = ?', ['admin'])
                ->orWhereRaw('LOWER(name) = ?', ['administrator']);
        })->get();

        foreach ($adminStudents as $adminStudent) {
            $this->warn("- Menghapus akun sistem nyasar di data siswa: {$adminStudent->name} (PIN: {$adminStudent->device_user_id})");
            $adminStudent->delete();
            $cleanedStudentsCount++;
        }

        $this->info("✓ Berhasil membersihkan {$cleanedStudentsCount} data duplikat siswa/guru.");

        // 3. Bersihkan kelas 'admin' jika ada dan sudah kosong
        $adminClasses = SchoolClass::whereRaw('LOWER(name) = ?', ['admin'])->get();
        foreach ($adminClasses as $ac) {
            if ($ac->students()->count() === 0) {
                $this->info("- Menghapus kelas kosong '{$ac->name}' (ID: {$ac->id})");
                $ac->delete();
            } else {
                $this->warn("- Kelas '{$ac->name}' masih memiliki {$ac->students()->count()} siswa.");
            }
        }

        // 4. Bersihkan jurusan 'admin' jika ada dan sudah kosong
        $adminMajors = Major::whereRaw('LOWER(name) = ?', ['admin'])->get();
        foreach ($adminMajors as $am) {
            if ($am->schoolClasses()->count() === 0) {
                $this->info("- Menghapus jurusan kosong '{$am->name}' (ID: {$am->id})");
                $am->delete();
            } else {
                $this->warn("- Jurusan '{$am->name}' masih memiliki {$am->schoolClasses()->count()} kelas.");
            }
        }

        $this->info('Pembersihan selesai!');

        return Command::SUCCESS;
    }
}
