<?php

namespace App\Exports;

use App\Models\SchoolClass;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StudentTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    public function array(): array
    {
        // Ambil beberapa contoh nama kelas dari database
        $classes = SchoolClass::pluck('name')->toArray();
        $sampleClass1 = $classes[0] ?? 'X RPL 1';
        $sampleClass2 = $classes[1] ?? ($classes[0] ?? 'XI TKJ 2');
        $sampleClass3 = $classes[2] ?? ($classes[0] ?? 'XII RPL 1');

        return [
            [
                '1001',
                'Ahmad Dahlan',
                $sampleClass1,
                '081234567890'
            ],
            [
                '1002',
                'Budi Santoso',
                $sampleClass1,
                '082198765432'
            ],
            [
                '1003',
                'Citra Maharani',
                $sampleClass2,
                '085712345678'
            ],
            [
                '1004',
                'Dewi Anggraini',
                $sampleClass3,
                ''
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'NIS',
            'NAMA SISWA',
            'KELAS',
            'NO HP ORTU'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        // Header Styling
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '16A34A'], // Emerald green
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data Rows Styling
        $sheet->getStyle('A2:D5')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Center NIS and KELAS columns
        $sheet->getStyle('A2:A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getRowDimension(1)->setRowHeight(26);

        return [];
    }
}
