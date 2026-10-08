<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AttendanceLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReportController extends Controller
{
    /**
     * Tampilan Halaman Rekapitulasi Absensi Siswa per Kelas
     */
    public function index(Request $request)
    {
        $classes = SchoolClass::with('major')->orderBy('name')->get();
        $selectedClassId = $request->get('class_id', $classes->first()->id ?? null);
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        // Ambil siswa sesuai kelas
        $studentsQuery = Student::query()->with('schoolClass.major');
        if ($selectedClassId && $selectedClassId !== 'all') {
            $studentsQuery->where('school_class_id', $selectedClassId);
        }
        $students = $studentsQuery->orderBy('name')->get();

        // Hitung range hari
        $period = CarbonPeriod::create($startDate, $endDate);
        $totalDays = count($period);

        // Ambil log absensi pada rentang tanggal
        $logs = AttendanceLog::whereBetween('timestamp', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
            ->whereIn('student_id', $students->pluck('id'))
            ->orderBy('timestamp', 'asc')
            ->get();

        // Olah data per siswa
        $reportData = [];
        foreach ($students as $student) {
            $studentLogs = $logs->where('student_id', $student->id);
            
            // Hari unik di mana siswa melakukan absen
            $attendedDates = $studentLogs->map(function ($l) {
                return substr($l->timestamp, 0, 10);
            })->unique();

            $totalPresent = $attendedDates->count();
            $percentage = $totalDays > 0 ? round(($totalPresent / $totalDays) * 100, 1) : 0;

            // Log terbaru dalam periode
            $latestLog = $studentLogs->last();

            $reportData[] = [
                'student' => $student,
                'total_present' => $totalPresent,
                'total_days' => $totalDays,
                'percentage' => $percentage,
                'latest_log' => $latestLog,
                'dates' => $attendedDates->toArray()
            ];
        }

        return view('reports.index', compact(
            'classes',
            'selectedClassId',
            'selectedClass',
            'startDate',
            'endDate',
            'reportData',
            'totalDays'
        ));
    }

    /**
     * Export PDF Rekap Ringkasan Presensi Siswa per Kelas (Daftar & Persentase)
     */
    public function exportPdf(Request $request)
    {
        $classes = SchoolClass::with('major')->orderBy('name')->get();
        $selectedClassId = $request->get('class_id', $classes->first()->id ?? null);
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        $studentsQuery = Student::query()->with('schoolClass.major');
        if ($selectedClassId && $selectedClassId !== 'all') {
            $studentsQuery->where('school_class_id', $selectedClassId);
        }
        $students = $studentsQuery->orderBy('name')->get();

        $period = CarbonPeriod::create($startDate, $endDate);
        $totalDays = count($period);

        $logs = AttendanceLog::whereBetween('timestamp', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
            ->whereIn('student_id', $students->pluck('id'))
            ->orderBy('timestamp', 'asc')
            ->get();

        $reportData = [];
        foreach ($students as $student) {
            $studentLogs = $logs->where('student_id', $student->id);
            $attendedDates = $studentLogs->map(fn($l) => substr($l->timestamp, 0, 10))->unique();

            $totalPresent = $attendedDates->count();
            $percentage = $totalDays > 0 ? round(($totalPresent / $totalDays) * 100, 1) : 0;

            $firstLog = $studentLogs->first();
            $lastLog = $studentLogs->last();

            $reportData[] = [
                'student' => $student,
                'total_present' => $totalPresent,
                'total_days' => $totalDays,
                'percentage' => $percentage,
                'first_scan' => $firstLog ? Carbon::parse($firstLog->timestamp)->format('H:i') : '-',
                'last_scan' => $lastLog ? Carbon::parse($lastLog->timestamp)->format('H:i') : '-',
            ];
        }

        $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';
        $fileName = 'Rekap_Presensi_' . str_replace(' ', '_', $className) . "_{$startDate}_{$endDate}.pdf";

        // Generate PDF via DomPDF
        try {
            $pdf = Pdf::loadView('reports.pdf', compact(
                'selectedClass',
                'className',
                'startDate',
                'endDate',
                'reportData',
                'totalDays'
            ))->setPaper('a4', 'portrait');

            if ($request->has('stream')) {
                return $pdf->stream($fileName);
            }
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            // Fallback: render view HTML langsung untuk dicetak
            return view('reports.pdf', compact(
                'selectedClass',
                'className',
                'startDate',
                'endDate',
                'reportData',
                'totalDays'
            ));
        }
    }

    /**
     * Export PDF Rekap Matriks Presensi Bulanan (Siswa x Tanggal 1..31)
     */
    public function exportMatrixPdf(Request $request)
    {
        $classes = SchoolClass::with('major')->orderBy('name')->get();
        $selectedClassId = $request->get('class_id', $classes->first()->id ?? null);
        $month = $request->get('month', now()->format('Y-m'));

        $startDate = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');

        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        $studentsQuery = Student::query()->with('schoolClass.major');
        if ($selectedClassId && $selectedClassId !== 'all') {
            $studentsQuery->where('school_class_id', $selectedClassId);
        }
        $students = $studentsQuery->orderBy('name')->get();

        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        $logs = AttendanceLog::whereBetween('timestamp', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        $matrix = [];
        foreach ($students as $student) {
            $studentLogs = $logs->where('student_id', $student->id);
            $attendedMap = [];
            foreach ($dates as $d) {
                $hasAttended = $studentLogs->contains(fn($l) => substr($l->timestamp, 0, 10) === $d);
                $attendedMap[$d] = $hasAttended;
            }

            $totalPresent = count(array_filter($attendedMap));
            $matrix[] = [
                'student' => $student,
                'days' => $attendedMap,
                'total_present' => $totalPresent,
                'percentage' => count($dates) > 0 ? round(($totalPresent / count($dates)) * 100, 1) : 0
            ];
        }

        $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';
        $monthName = Carbon::parse($startDate)->translatedFormat('F Y');
        $fileName = 'Rekap_Matriks_Bulanan_' . str_replace(' ', '_', $className) . "_{$month}.pdf";

        try {
            $pdf = Pdf::loadView('reports.matrix_pdf', compact(
                'selectedClass',
                'className',
                'monthName',
                'dates',
                'matrix'
            ))->setPaper('a4', 'landscape');

            if ($request->has('stream')) {
                return $pdf->stream($fileName);
            }
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            return view('reports.matrix_pdf', compact(
                'selectedClass',
                'className',
                'monthName',
                'dates',
                'matrix'
            ));
        }
    }
}
