<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi — {{ config('app.name', 'ORGA-ATTEND') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            font-size: 13px;
            margin: 4px 0 0 0;
            color: #475569;
            font-weight: normal;
        }
        .meta-box {
            margin-bottom: 16px;
            font-size: 11px;
        }
        .meta-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-box td {
            padding: 2px 4px;
        }
        .summary-boxes {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .summary-item {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            background: #f8fafc;
        }
        .summary-item .label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .summary-item .value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .font-mono { font-family: monospace; }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-space {
            height: 60px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #4f46e5; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ Cetak Dokumen / Simpan PDF
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; font-weight: bold; margin-left: 8px; cursor: pointer;">
            Tutup
        </button>
    </div>

    <div class="header">
        <h1>REKAPITULASI LAPORAN PRESENSI ORGANISASI</h1>
        <h2>{{ config('app.name', 'ORGA-ATTEND') }} — Sistem Presensi & Kehadiran Anggota</h2>
    </div>

    <div class="meta-box">
        <table>
            <tr>
                <td style="width: 15%; font-weight: bold;">Kegiatan</td>
                <td style="width: 35%;">: {{ $filters['event'] }}</td>
                <td style="width: 15%; font-weight: bold;">Waktu Cetak</td>
                <td style="width: 35%;">: {{ now()->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') }} WIB</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Divisi</td>
                <td>: {{ $filters['division'] }}</td>
                <td style="font-weight: bold;">Status Filter</td>
                <td>: {{ $filters['status'] }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Dicetak Oleh</td>
                <td>: {{ auth()->user()->name }} ({{ auth()->user()->username }})</td>
                <td style="font-weight: bold;">Rentang Tanggal</td>
                <td>: {{ $filters['start_date'] }} s/d {{ $filters['end_date'] }}</td>
            </tr>
        </table>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <td style="padding: 8px; border: 1px solid #cbd5e1; background: #f8fafc; text-align: center;">
                <div style="font-size: 9px; font-weight: bold; color: #64748b;">TOTAL DATA</div>
                <div style="font-size: 15px; font-weight: bold;">{{ number_format($totalRecords) }}</div>
            </td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; background: #f0fdf4; text-align: center;">
                <div style="font-size: 9px; font-weight: bold; color: #166534;">HADIR</div>
                <div style="font-size: 15px; font-weight: bold; color: #166534;">{{ number_format($totalHadir) }}</div>
            </td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; background: #fffbeb; text-align: center;">
                <div style="font-size: 9px; font-weight: bold; color: #92400e;">TERLAMBAT</div>
                <div style="font-size: 15px; font-weight: bold; color: #92400e;">{{ number_format($totalTerlambat) }}</div>
            </td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; background: #eff6ff; text-align: center;">
                <div style="font-size: 9px; font-weight: bold; color: #1e40af;">IZIN</div>
                <div style="font-size: 15px; font-weight: bold; color: #1e40af;">{{ number_format($totalIzin) }}</div>
            </td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; background: #faf5ff; text-align: center;">
                <div style="font-size: 9px; font-weight: bold; color: #6b21a8;">SAKIT</div>
                <div style="font-size: 15px; font-weight: bold; color: #6b21a8;">{{ number_format($totalSakit) }}</div>
            </td>
            <td style="padding: 8px; border: 1px solid #cbd5e1; background: #fff1f2; text-align: center;">
                <div style="font-size: 9px; font-weight: bold; color: #9f1239;">ALPA</div>
                <div style="font-size: 15px; font-weight: bold; color: #9f1239;">{{ number_format($totalAlpa) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 22%;">Nama Anggota</th>
                <th style="width: 15%;">Divisi</th>
                <th style="width: 23%;">Kegiatan</th>
                <th style="width: 15%;">Waktu Presensi</th>
                <th style="width: 10%;" class="text-center">Status</th>
                <th style="width: 10%;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $att)
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $att->user ? $att->user->name : '-' }}</strong><br>
                        <span class="font-mono" style="color: #64748b;">{{ $att->user ? $att->user->username : '-' }}</span>
                    </td>
                    <td>{{ $att->user && $att->user->division ? $att->user->division->name : '-' }}</td>
                    <td>
                        {{ $att->event ? $att->event->title : '-' }}<br>
                        <span class="font-mono" style="color: #64748b;">{{ $att->event ? $att->event->code : '-' }}</span>
                    </td>
                    <td>{{ $att->check_in_time ? $att->check_in_time->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}</td>
                    <td class="text-center"><strong>{{ $att->status }}</strong></td>
                    <td>{{ $att->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data presensi yang ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <p style="margin: 0;">Mengetahui,</p>
            <p style="margin: 2px 0 0 0; font-weight: bold;">Administrator Organisasi</p>
            <div class="signature-space"></div>
            <p style="margin: 0; font-weight: bold; text-decoration: underline;">{{ auth()->user()->name }}</p>
            <p style="margin: 2px 0 0 0; font-size: 9px; color: #64748b;">NIP/ID: {{ auth()->user()->username }}</p>
        </div>
    </div>

</body>
</html>
