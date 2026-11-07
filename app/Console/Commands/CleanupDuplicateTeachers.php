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

        $cleanedStudentsCount = 0;
        $migratedTemplatesCount = 0;
        $migratedLogsCount = 0;

        // 1. Tangani kasus khusus Pak Agus Triwidiyanto (NIP: 197808222010011015, PIN Mesin: 1, Student ID: 239)
        $pakAgus = Teacher::where('nip', '197808222010011015')
            ->orWhere('name', 'like', '%Agus Triwidiyanto%')
            ->first();

        if ($pakAgus) {
            $this->info("- Memeriksa data Guru: {$pakAgus->name} (NIP: {$pakAgus->nip})...");

            // Cek apakah ada student dengan ID 239, nama 'Agus', atau PIN '1'
            $agusStudents = Student::where('id', 239)
                ->orWhere(function ($q) {
                    $q->where('name', 'like', '%Agus%')
                        ->where(function ($sq) {
                            $sq->where('device_user_id', '1')
                                ->orWhere('device_user_id', '197808222010011015')
                                ->orWhere('nis', '197808222010011015');
                        });
                })->get();

            foreach ($agusStudents as $as) {
                $this->warn("  * Menghubungkan siswa duplikat {$as->name} (ID: {$as->id}, PIN: {$as->device_user_id}) ke Guru {$pakAgus->name}");
                $asPin = (string) ($as->device_user_id ?: '1');

                // Pindahkan template
                $tCount = FingerprintTemplate::where('student_id', $as->id)
                    ->orWhere('device_user_id', $asPin)
                    ->update([
                        'student_id' => null,
                        'teacher_id' => $pakAgus->id,
                        'device_user_id' => $asPin,
                    ]);
                $migratedTemplatesCount += $tCount;

                // Pindahkan log presensi
                $lCount = AttendanceLog::where('student_id', $as->id)
                    ->orWhere('device_user_id', $asPin)
                    ->update([
                        'student_id' => null,
                        'teacher_id' => $pakAgus->id,
                        'device_user_id' => $asPin,
                    ]);
                $migratedLogsCount += $lCount;

                $as->delete();
                $cleanedStudentsCount++;
            }

            // Cek template sidik jari lepas pada PIN '1' atau '197808222010011015'
            $agusTemplates = FingerprintTemplate::whereIn('device_user_id', ['1', '197808222010011015'])
                ->get();

            if ($agusTemplates->isNotEmpty()) {
                $chosenPin = $agusTemplates->first()->device_user_id; // Biasanya '1' dari mesin
                foreach ($agusTemplates as $at) {
                    $at->update([
                        'student_id' => null,
                        'teacher_id' => $pakAgus->id,
                    ]);
                    $migratedTemplatesCount++;
                }

                // Update PIN mesin guru Pak Agus sesuai PIN fisiknya di mesin
                $pakAgus->update(['device_user_id' => $chosenPin]);
                $this->info("  * Berhasil menautkan {$agusTemplates->count()} sidik jari ke Guru {$pakAgus->name} (PIN Mesin: {$chosenPin})");
            } elseif (empty($pakAgus->device_user_id)) {
                $pakAgus->update(['device_user_id' => (string) $pakAgus->nip]);
            }
        }

        // 2. Pastikan seluruh Guru lainnya memiliki device_user_id
        $teachersWithoutPin = Teacher::whereNull('device_user_id')
            ->orWhere('device_user_id', '')
            ->get();

        foreach ($teachersWithoutPin as $t) {
            $t->update(['device_user_id' => (string) $t->nip]);
            $this->info("- Mengisi PIN Guru {$t->name} dengan NIP: {$t->nip}");
        }

        // 3. Periksa semua guru untuk student duplikat dan template lepas
        $allTeachers = Teacher::all();

        foreach ($allTeachers as $teacher) {
            $nip = (string) $teacher->nip;
            $pin = (string) ($teacher->device_user_id ?: $teacher->nip);

            // Cari data siswa nyasar yang memiliki PIN / NIS sama dengan NIP atau PIN guru ini
            $duplicateStudents = Student::where(function ($q) use ($nip, $pin, $teacher) {
                $q->where('device_user_id', $pin)
                    ->orWhere('device_user_id', $nip)
                    ->orWhere('nis', $nip)
                    ->orWhere('nis', $pin)
                    ->orWhere(function ($nameQ) use ($teacher) {
                        $nameQ->where('name', $teacher->name)
                            ->orWhere('name', 'like', "%{$teacher->name}%");
                    });
            })->get();

            foreach ($duplicateStudents as $student) {
                $this->warn("- Menemukan siswa duplikat: {$student->name} (PIN: {$student->device_user_id}) yang sebenarnya adalah Guru {$teacher->name} (NIP: {$teacher->nip})");

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

            // Tautkan template lepas yang cocok dengan PIN / NIP guru
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

        // 4. Bersihkan siswa nyasar di kelas siswa (seperti X PPLG 1) yang sebenarnya adalah NIP / Gelar Guru
        $this->info('- Memeriksa data siswa nyasar yang memiliki NIP 18 digit atau gelar guru...');
        $allStudents = Student::all();

        foreach ($allStudents as $student) {
            $pin = trim((string) $student->device_user_id);
            $nis = trim((string) $student->nis);
            $name = trim((string) $student->name);

            $isNipPin = (strlen($pin) === 18 && ctype_digit($pin) && (str_starts_with($pin, '19') || str_starts_with($pin, '20')));
            $isNipNis = (strlen($nis) === 18 && ctype_digit($nis) && (str_starts_with($nis, '19') || str_starts_with($nis, '20')));

            $lowerName = strtolower($name);
            $hasTeacherTitle = false;
            $titles = ['s.pd', 'm.pd', 's.t', 'm.t', 's.kom', 'm.kom', 's.ag', 'm.ag', 's.si', 'm.si', 'drs.', 'drs ', 'dra.', 'dra '];
            foreach ($titles as $t) {
                if (str_contains($lowerName, $t)) {
                    $hasTeacherTitle = true;
                    break;
                }
            }

            if ($isNipPin || $isNipNis || $hasTeacherTitle || $pin === 'BOPD') {
                $nip = $isNipNis ? $nis : ($isNipPin ? $pin : ($nis ?: $pin));

                // Cari apakah guru sudah terdaftar di teachers
                $targetTeacher = Teacher::where('nip', $nip)
                    ->orWhere('device_user_id', $pin)
                    ->orWhere('name', 'like', "%{$name}%")
                    ->first();

                if (! $targetTeacher) {
                    $targetTeacher = Teacher::create([
                        'nip' => $nip,
                        'name' => $name,
                        'category' => 'GURU UMUM',
                        'device_user_id' => $pin ?: $nip,
                    ]);
                    $this->info("  * Mendaftarkan Guru baru dari data nyasar: {$targetTeacher->name} (NIP: {$targetTeacher->nip})");
                } else {
                    $this->warn("  * Mengalihkan siswa nyasar '{$student->name}' (NIP: {$nip}) ke Guru '{$targetTeacher->name}'");
                }

                $teacherPin = (string) ($targetTeacher->device_user_id ?: $targetTeacher->nip);

                // Pindahkan template sidik jari
                $tCount = FingerprintTemplate::where('student_id', $student->id)
                    ->orWhere('device_user_id', $pin)
                    ->update([
                        'student_id' => null,
                        'teacher_id' => $targetTeacher->id,
                        'device_user_id' => $teacherPin,
                    ]);
                $migratedTemplatesCount += $tCount;

                // Pindahkan attendance logs
                $lCount = AttendanceLog::where('student_id', $student->id)
                    ->orWhere('device_user_id', $pin)
                    ->update([
                        'student_id' => null,
                        'teacher_id' => $targetTeacher->id,
                        'device_user_id' => $teacherPin,
                    ]);
                $migratedLogsCount += $lCount;

                $student->delete();
                $cleanedStudentsCount++;
            }
        }

        // 4. Bersihkan sisa kelas bernama 'admin' jika masih ada
        $adminClasses = SchoolClass::whereRaw('LOWER(name) = ?', ['admin'])->get();
        foreach ($adminClasses as $ac) {
            $studentsInAdminClass = Student::where('school_class_id', $ac->id)->get();
            foreach ($studentsInAdminClass as $sAdmin) {
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

        // 5. Bersihkan sisa jurusan bernama 'admin' jika masih ada
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
