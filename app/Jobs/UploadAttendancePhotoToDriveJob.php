<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadAttendancePhotoToDriveJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(
        public int $attendanceId
    ) {}

    public function handle(GoogleDriveService $driveService): void
    {
        $attendance = Attendance::find($this->attendanceId);

        if (! $attendance || empty($attendance->photo_path)) {
            return;
        }

        $absolutePath = Storage::disk('local')->path($attendance->photo_path);

        if (! file_exists($absolutePath)) {
            Log::warning("Local photo file not found at {$absolutePath} for attendance #{$attendance->id}");

            return;
        }

        $fileId = $driveService->uploadAttendancePhoto($attendance, $absolutePath);

        if ($fileId) {
            // Once photo is in Drive and attendance updated, trigger Sheet sync
            SyncAttendanceToGoogleSheet::dispatch($attendance->id);
        }
    }
}
