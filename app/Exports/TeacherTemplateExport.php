<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherTemplateExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                '198005122005011003',
                'Budi Santoso, S.Pd',
                'GURU MTK',
                '081234567890',
                '198005122005011003',
            ],
            [
                '198308222008012011',
                'Siti Aminah, M.Pd',
                'GURU PPKN',
                '082198765432',
                '198308222008012011',
            ],
            [
                '197811052002121004',
                'Bambang Suryono, S.Pd',
                'GURU SEJARAH',
                '',
                '197811052002121004',
            ],
            [
                '199003152019031008',
                'Rizky Pratama, S.Kom',
                'GURU INFORMATIKA',
                '085712345678',
                '199003152019031008',
            ],
            [
                '198607142010012015',
                'Nurul Hidayah, S.Pd.I',
                'GURU PAI',
                '081398765432',
                '198607142010012015',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'NIP',
            'NAMA GURU',
            'KATEGORI',
            'NO HP',
            'PIN MESIN',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // NIP sebagai Text agar angka awal tidak hilang
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT, // No HP sebagai Text
            'E' => NumberFormat::FORMAT_TEXT, // PIN Mesin sebagai Text
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        // Style Header
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '047857'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Style Data Rows
        $sheet->getStyle('A2:E6')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A2:A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:E6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
