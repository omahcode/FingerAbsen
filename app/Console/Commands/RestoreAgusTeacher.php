<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\FingerprintTemplate;
use App\Models\Teacher;
use Illuminate\Console\Command;

class RestoreAgusTeacher extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fix:agus';

    /**
     * The console command description.
     */
    protected $description = 'Pulihkan dan pastikan data Guru Agus Triwidiyanto, S.T. beserta sidik jarinya ada di daftar Guru (/teachers)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== MEMULIHKAN / MEMASTIKAN DATA GURU AGUS TRIWIDIYANTO, S.T. ===');

        $nip = '197808222010011015';
        $fullName = 'Agus Triwidiyanto, S.T.';

        // 1. Cari apakah sudah ada di tabel teachers
        $teacher = Teacher::where('nip', $nip)
            ->orWhere('name', 'like', '%Agus Triwidiyanto%')
            ->first();

        if (! $teacher) {
            $this->warn("Data Guru '{$fullName}' belum ada di tabel teachers. Membuat record baru...");
            $teacher = Teacher::create([
                'nip' => $nip,
                'name' => $fullName,
                'category' => 'GURU UMUM',
                'device_user_id' => '1',
            ]);
            $this->info("✓ Berhasil membuat Guru baru: ID {$teacher->id} | {$teacher->name} | NIP: {$teacher->nip}");
        } else {
            $this->info("✓ Ditemukan Guru: ID {$teacher->id} | {$teacher->name} | NIP: {$teacher->nip}");
            // Pastikan NIP dan Nama lengkap sesuai
            $teacher->update([
                'nip' => $nip,
                'name' => $fullName,
                'category' => $teacher->category ?: 'GURU UMUM',
                'device_user_id' => $teacher->device_user_id ?: '1',
            ]);
        }

        // 2. Cari template sidik jari fisik di database
        // Mesin umumnya mengirim PIN '1' atau NIP '197808222010011015'
        $templates = FingerprintTemplate::whereIn('device_user_id', ['1', $nip])
            ->orWhere('teacher_id', $teacher->id)
            ->get();

        $this->info("Memeriksa template sidik jari (ditemukan {$templates->count()} template)...");

        $linkedCount = 0;
        $chosenPin = $teacher->device_user_id ?: '1';

        foreach ($templates as $tmpl) {
            if (! empty($tmpl->device_user_id)) {
                $chosenPin = (string) $tmpl->device_user_id;
            }
            $tmpl->update([
                'student_id' => null,
                'teacher_id' => $teacher->id,
                'device_user_id' => $chosenPin,
            ]);
            $linkedCount++;
        }

        // Sinkronkan device_user_id guru dengan PIN fisik sidik jari
        $teacher->update(['device_user_id' => $chosenPin]);

        // 3. Tautkan juga log absensi
        $logCount = AttendanceLog::whereIn('device_user_id', ['1', $nip])
            ->orWhere('teacher_id', $teacher->id)
            ->update([
                'student_id' => null,
                'teacher_id' => $teacher->id,
                'device_user_id' => $chosenPin,
            ]);

        $this->info("✓ Menautkan {$linkedCount} sidik jari dan {$logCount} log absensi ke Guru {$teacher->name} (PIN Mesin: {$chosenPin})");
        $this->info("Selesai! Sekarang buka menu http://hadirin-sekalian.neperone.id/teachers dan cari 'Agus'.");

        return 0;
    }
}
