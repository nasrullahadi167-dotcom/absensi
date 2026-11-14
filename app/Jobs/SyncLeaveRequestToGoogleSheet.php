<?php

namespace App\Jobs;

use App\Models\LeaveRequest;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncLeaveRequestToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $leaveRequestId
    ) {}

    public function handle(GoogleSheetsService $sheetsService): void
    {
        $leave = LeaveRequest::with(['event', 'user', 'reviewer'])->find($this->leaveRequestId);

        if (! $leave) {
            return;
        }

        if (! $sheetsService->isAvailable()) {
            Log::info("Google Sheets not available. Skipping leave sync for LeaveRequest #{$leave->id}");

            return;
        }

        $row = [
            $leave->id,
            $leave->event ? $leave->event->code : '-',
            $leave->user ? $leave->user->username : '-',
            $leave->user ? $leave->user->name : '-',
            $leave->type,
            $leave->reason,
            $leave->status,
            $leave->reviewer ? $leave->reviewer->name : '-',
            $leave->reviewed_at ? $leave->reviewed_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-',
            $leave->admin_notes ?? '-',
            $leave->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];

        $sheetsService->upsertRecord('LeaveRequests', $leave->id, $row);
    }
}
