<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Build attendance query with comprehensive filters.
     */
    public function getFilteredQuery(Request $request): Builder
    {
        $query = Attendance::with(['event', 'user.division', 'attendanceSession'])
            ->latest('check_in_time');

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('division_id')) {
            $query->whereHas('user', function (Builder $q) use ($request) {
                $q->where('division_id', $request->input('division_id'));
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('check_in_time', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('check_in_time', '<=', $request->input('end_date'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('user', function (Builder $qu) use ($search) {
                    $qu->where('name', 'like', $search)
                        ->orWhere('username', 'like', $search);
                })->orWhereHas('event', function (Builder $qe) use ($search) {
                    $qe->where('title', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            });
        }

        return $query;
    }

    /**
     * Generate CSV export stream with UTF-8 BOM for Microsoft Excel compatibility.
     */
    public function exportCsv(Builder $query): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_absensi_'.date('Ymd_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel to properly render Indonesian accents and characters
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'Kode Kegiatan',
                'Nama Kegiatan',
                'Tanggal Kegiatan',
                'Username Anggota',
                'Nama Anggota',
                'Divisi',
                'Waktu Absensi',
                'Status Kehadiran',
                'Catatan',
                'Google Drive File ID',
            ]);

            $no = 1;
            $query->chunk(200, function ($attendances) use ($handle, &$no) {
                foreach ($attendances as $att) {
                    fputcsv($handle, [
                        $no++,
                        $att->event ? $att->event->code : '-',
                        $att->event ? $att->event->title : '-',
                        $att->event ? $att->event->event_date->format('d/m/Y') : '-',
                        $att->user ? $att->user->username : '-',
                        $att->user ? $att->user->name : '-',
                        $att->user && $att->user->division ? $att->user->division->name : '-',
                        $att->check_in_time ? $att->check_in_time->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '-',
                        $att->status,
                        $att->notes ?? '-',
                        $att->drive_file_id ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
