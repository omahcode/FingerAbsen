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

        // 1. Pastikan seluruh Guru memiliki device_user_id (jika null atau kosong, samakan dengan NIP)
        $teachersWithoutPin = Teacher::whereNull('device_user_id')
            ->orWhere('device_user_id', '')
            ->get();

        foreach ($teachersWithoutPin as $t) {
            $t->update(['device_user_id' => (string) $t->nip]);
            $this->info("- Mengisi PIN Guru {$t->name} dengan NIP: {$t->nip}");
        }

        $allTeachers = Teacher::all();
        $cleanedStudentsCount = 0;
        $migratedTemplatesCount = 0;
        $migratedLogsCount = 0;

        foreach ($allTeachers as $teacher) {
            $nip = (string) $teacher->nip;
            $pin = (string) ($teacher->device_user_id ?: $teacher->nip);

            // 2. Cari data siswa nyasar yang memiliki PIN / NIS sama dengan NIP atau PIN guru ini
            $duplicateStudents = Student::where(function ($q) use ($nip, $pin) {
                $q->where('device_user_id', $pin)
                    ->orWhere('device_user_id', $nip)
                    ->orWhere('nis', $nip)
                    ->orWhere('nis', $pin);
            })->get();

            foreach ($duplicateStudents as $student) {
                $this->warn("- Menemukan siswa duplikat: {$student->name} (PIN: {$student->device_user_id}) yang sebenarnya adalah Guru {$teacher->name} (NIP: {$teacher->nip})");

                // Pindahkan seluruh template sidik jari siswa ini ke Guru
                $templates = FingerprintTemplate::where('student_id', $student->id)
                    ->orWhere('device_user_id', $student->device_user_id)
                    ->get();

                foreach ($templates as $tmpl) {
                    $tmpl->update([
                        'student_id' => null,
                        'teacher_id' => $teacher->id,
                        'device_user_id' => $pin,
                    ]);
                    $migratedTemplatesCount++;
                }

                // Pindahkan log presensi siswa ini ke Guru
                $logs = AttendanceLog::where('student_id', $student->id)
                    ->orWhere('device_user_id', $student->device_user_id)
                    ->get();

                foreach ($logs as $log) {
                    $log->update([
                        'student_id' => null,
                        'teacher_id' => $teacher->id,
                        'device_user_id' => $pin,
                    ]);
                    $migratedLogsCount++;
                }

                $student->delete();
                $cleanedStudentsCount++;
            }

            // 3. Cek jika ada template sidik jari lepas yang cocok dengan NIP / PIN guru tapi belum ditautkan ke teacher_id
            $orphanTemplates = FingerprintTemplate::where(function ($q) use ($nip, $pin) {
                $q->where('device_user_id', $pin)
                    ->orWhere('device_user_id', $nip);
            })->whereNull('teacher_id')->get();

            foreach ($orphanTemplates as $ot) {
                $ot->update([
                    'student_id' => null,
                    'teacher_id' => $teacher->id,
                    'device_user_id' => $pin,
                ]);
                $migratedTemplatesCount++;
            }
        }

        // 4. Cari siswa yang berada di kelas bernama 'admin'
        $adminClasses = SchoolClass::whereRaw('LOWER(name) = ?', ['admin'])->get();
        foreach ($adminClasses as $ac) {
            $studentsInAdminClass = Student::where('school_class_id', $ac->id)->get();
            foreach ($studentsInAdminClass as $sAdmin) {
                // Cek apakah ada guru yang cocok
                $matchedTeacher = Teacher::where('nip', $sAdmin->device_user_id)
                    ->orWhere('device_user_id', $sAdmin->device_user_id)
                    ->orWhere('name', 'like', "%{$sAdmin->name}%")
                    ->first();

                if ($matchedTeacher) {
                    $pin = (string) ($matchedTeacher->device_user_id ?: $matchedTeacher->nip);
                    FingerprintTemplate::where('student_id', $sAdmin->id)->update([
                        'student_id' => null,
                        'teacher_id' => $matchedTeacher->id,
                        'device_user_id' => $pin,
                    ]);
                    AttendanceLog::where('student_id', $sAdmin->id)->update([
                        'student_id' => null,
                        'teacher_id' => $matchedTeacher->id,
                        'device_user_id' => $pin,
                    ]);
                    $this->warn("- Mengalihkan data siswa '{$sAdmin->name}' di kelas admin ke Guru '{$matchedTeacher->name}'");
                }

                $sAdmin->delete();
                $cleanedStudentsCount++;
            }

            $this->info("- Menghapus kelas '{$ac->name}' (ID: {$ac->id})");
            $ac->delete();
        }

        // 5. Cari jurusan bernama 'admin'
        $adminMajors = Major::whereRaw('LOWER(name) = ?', ['admin'])->get();
        foreach ($adminMajors as $am) {
            $am->schoolClasses()->each(fn ($c) => $c->students()->delete());
            $am->schoolClasses()->delete();
            $this->info("- Menghapus jurusan '{$am->name}' (ID: {$am->id})");
            $am->delete();
        }

        $this->info("✓ Selesai! Berhasil membersihkan {$cleanedStudentsCount} siswa duplikat, mengalihkan {$migratedTemplatesCount} template sidik jari ke Guru, dan {$migratedLogsCount} log presensi.");

        return Command::SUCCESS;
    }
}
