<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncAttendanceToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $attendanceId
    ) {}

    public function handle(GoogleSheetsService $sheetsService): void
    {
        $attendance = Attendance::with(['event', 'user'])->find($this->attendanceId);

        if (! $attendance) {
            return;
        }

        if (! $sheetsService->isAvailable()) {
            Log::info("Google Sheets not available. Skipping attendance sync for Attendance #{$attendance->id}");

            return;
        }

        $row = [
            $attendance->id,
            $attendance->event ? $attendance->event->code : '-',
            $attendance->event ? $attendance->event->title : '-',
            $attendance->user ? $attendance->user->username : '-',
            $attendance->user ? $attendance->user->name : '-',
            $attendance->check_in_time ? $attendance->check_in_time->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-',
            $attendance->status,
            $attendance->drive_file_id ?? '-',
            $attendance->notes ?? '-',
            $attendance->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];

        $sheetsService->upsertRecord('Attendance', $attendance->id, $row);
    }
}
