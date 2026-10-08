<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi Bulanan (Matriks) - {{ $className }}</title>
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 9px;
            line-height: 1.2;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 9px;
            color: #666;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .matrix-table th {
            background-color: #eef2f7;
            border: 1px solid #777;
            padding: 4px 2px;
            text-align: center;
            font-size: 8px;
        }
        .matrix-table td {
            border: 1px solid #bbb;
            padding: 3px 2px;
            font-size: 8px;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .attended {
            color: #059669;
            font-weight: bold;
        }
        .absent {
            color: #dc2626;
        }
        .signature-table {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            width: 50%;
            font-size: 9px;
        }
        .signature-space {
            height: 45px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>REKAPITULASI PRESENSI BULANAN SISWA</h2>
        <p>Kelas: {{ $className }} | Bulan: {{ $monthName }} | Jurusan: {{ $selectedClass->major->name ?? 'Semua Jurusan' }}</p>
    </div>

    <table class="matrix-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 45px;">NIS</th>
                <th style="width: 130px; text-align: left; padding-left: 4px;">Nama Siswa</th>
                @foreach($dates as $d)
                    <th style="width: 16px;">{{ (int) substr($d, 8, 2) }}</th>
                @endforeach
                <th style="width: 35px;">Hadir</th>
                <th style="width: 35px;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($matrix as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td class="text-center font-mono">{{ $row['student']->device_user_id }}</td>
                <td style="padding-left: 4px;">{{ $row['student']->name }}</td>
                @foreach($dates as $d)
                    <td class="text-center">
                        @if($row['days'][$d])
                            <span class="attended">&#10003;</span>
                        @else
                            <span class="absent">-</span>
                        @endif
                    </td>
                @endforeach
                <td class="text-center font-bold">{{ $row['total_present'] }}</td>
                <td class="text-center font-bold">{{ $row['percentage'] }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($dates) + 5 }}" class="text-center" style="padding: 10px;">Tidak ada data siswa.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala Sekolah</strong>
                <div class="signature-space"></div>
                <strong>( ___________________________ )</strong>
            </td>
            <td>
                Dicetak pada: {{ now()->translatedFormat('d F Y') }}<br>
                <strong>Wali Kelas</strong>
                <div class="signature-space"></div>
                <strong>( ___________________________ )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
