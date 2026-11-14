<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\EventParticipant;
use App\Models\SyncOutbox;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSyncOutboxJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $outboxId
    ) {}

    public function handle(GoogleSheetsService $sheetsService): void
    {
        $outbox = SyncOutbox::find($this->outboxId);

        if (! $outbox || $outbox->status === 'SYNCED') {
            return;
        }

        $outbox->update([
            'status' => 'PROCESSING',
            'attempts' => $outbox->attempts + 1,
            'last_attempt_at' => now(),
        ]);

        try {
            if (! $sheetsService->isAvailable()) {
                // If sheets API is not enabled/configured yet, keep record as PENDING
                $outbox->update([
                    'status' => 'PENDING',
                    'error_message' => 'Google Sheets API belum dikonfigurasi atau dinonaktifkan.',
                ]);

                return;
            }

            switch ($outbox->event_type) {
                case 'USER_SYNC':
                    SyncMemberToGoogleSheet::dispatchSync($outbox->aggregate_id);
                    break;

                case 'EVENT_SYNC':
                    SyncEventToGoogleSheet::dispatchSync($outbox->aggregate_id);
                    break;

                case 'ATTENDANCE_SYNC':
                    SyncAttendanceToGoogleSheet::dispatchSync($outbox->aggregate_id);
                    break;

                case 'LEAVE_SYNC':
                    SyncLeaveRequestToGoogleSheet::dispatchSync($outbox->aggregate_id);
                    break;

                case 'DIVISION_SYNC':
                    $division = Division::find($outbox->aggregate_id);
                    if ($division) {
                        $sheetsService->upsertRecord('Divisions', $division->id, [
                            $division->id,
                            $division->code,
                            $division->name,
                            $division->description ?? '-',
                            $division->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            $division->updated_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]);
                    }
                    break;

                case 'PARTICIPANT_SYNC':
                    $participant = EventParticipant::with(['event', 'user'])->find($outbox->aggregate_id);
                    if ($participant) {
                        $sheetsService->upsertRecord('EventParticipants', $participant->id, [
                            $participant->id,
                            $participant->event ? $participant->event->code : '-',
                            $participant->event ? $participant->event->title : '-',
                            $participant->user ? $participant->user->username : '-',
                            $participant->user ? $participant->user->name : '-',
                            $participant->registered_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                            $participant->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]);
                    }
                    break;

                case 'AUDIT_SYNC':
                    $audit = AuditLog::with('user')->find($outbox->aggregate_id);
                    if ($audit) {
                        $sheetsService->upsertRecord('AuditLog', $audit->id, [
                            $audit->id,
                            $audit->user ? $audit->user->username : 'SYSTEM',
                            $audit->action,
                            $audit->description,
                            $audit->entity_type ?? '-',
                            $audit->entity_id ?? '-',
                            $audit->ip_address ?? '-',
                            $audit->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]);
                    }
                    break;

                default:
                    Log::warning("Unknown outbox event type: {$outbox->event_type}");
            }

            $outbox->update([
                'status' => 'SYNCED',
                'synced_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to process outbox record #{$outbox->id}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $outbox->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
