<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekap Presensi Siswa - {{ $className }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h3 {
            margin: 3px 0 0 0;
            font-size: 13px;
            font-weight: normal;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #666;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            font-size: 11px;
        }
        .meta-table td {
            padding: 2px 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background-color: #f2f4f8;
            border: 1px solid #999;
            padding: 6px 5px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .data-table td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            font-size: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #fafafa; }
        
        .summary-box {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 25px;
        }
        .summary-box table {
            width: 50%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .summary-box td {
            padding: 3px 6px;
            border: 1px solid #ddd;
        }
        
        .signature-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            width: 50%;
            font-size: 11px;
        }
        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT / HEADER -->
    <div class="header">
        <h2>LAPORAN REKAPITULASI PRESENSI SISWA</h2>
        <h3>SMK / SEKOLAH MENENGAH KEJURUAN</h3>
        <p>Sistem Presensi Biometrik Sidik Jari (Fingerprint Management System)</p>
    </div>

    <!-- METADATA LAPORAN -->
    <table class="meta-table">
        <tr>
            <td style="width: 15%;"><strong>Kelas</strong></td>
            <td style="width: 35%;">: {{ $className }}</td>
            <td style="width: 15%;"><strong>Periode</strong></td>
            <td style="width: 35%;">: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</td>
        </tr>
        <tr>
            <td><strong>Jurusan</strong></td>
            <td>: {{ $selectedClass->major->name ?? 'Semua Jurusan' }}</td>
            <td><strong>Total Hari Kerja</strong></td>
            <td>: {{ $totalDays }} Hari</td>
        </tr>
    </table>

    <!-- TABEL DATA PRESENSI -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">NIS / PIN</th>
                <th style="width: 35%; text-align: left;">Nama Siswa</th>
                <th style="width: 15%;">Kehadiran</th>
                <th style="width: 15%;">Persentase</th>
                <th style="width: 15%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalHadirAll = 0;
            @endphp
            @forelse($reportData as $index => $row)
            @php
                $totalHadirAll += $row['total_present'];
            @endphp
            <tr class="{{ $index % 2 == 1 ? 'bg-gray' : '' }}">
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $row['student']->device_user_id }}</td>
                <td>{{ $row['student']->name }}</td>
                <td class="text-center">{{ $row['total_present'] }} / {{ $totalDays }} Hari</td>
                <td class="text-center font-bold">{{ $row['percentage'] }}%</td>
                <td class="text-center">
                    @if($row['percentage'] >= 85)
                        Sangat Baik
                    @elseif($row['percentage'] >= 75)
                        Baik
                    @elseif($row['percentage'] >= 60)
                        Cukup
                    @else
                        Kurang
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 15px;">Tidak ada data siswa pada kelas ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala Sekolah</strong>
                <div class="signature-space"></div>
                <strong>( ___________________________ )</strong><br>
                NIP. ........................................
            </td>
            <td>
                Dicetak pada: {{ now()->translatedFormat('d F Y') }}<br>
                <strong>Wali Kelas / Guru Piket</strong>
                <div class="signature-space"></div>
                <strong>( ___________________________ )</strong><br>
                NIP. ........................................
            </td>
        </tr>
    </table>

</body>
</html>
