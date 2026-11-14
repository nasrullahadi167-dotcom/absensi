<?php

namespace App\Services;

use App\Jobs\ProcessSyncOutboxJob;
use App\Models\SyncOutbox;
use Illuminate\Support\Facades\DB;

class SyncOutboxService
{
    /**
     * Record an event in the sync outbox and dispatch background job strictly after DB commit.
     */
    public function record(string $eventType, string $aggregateType, int $aggregateId, array $payload): SyncOutbox
    {
        $outbox = SyncOutbox::create([
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
            'status' => 'PENDING',
            'attempts' => 0,
        ]);

        $outboxId = $outbox->id;

        // Transactional Outbox: strictly trigger job after database transaction commits
        DB::afterCommit(function () use ($outboxId) {
            ProcessSyncOutboxJob::dispatch($outboxId);
        });

        return $outbox;
    }

    /**
     * Dispatch all pending or failed outbox records for retry.
     */
    public function retryOutbox(SyncOutbox $outbox): void
    {
        $outbox->update([
            'status' => 'PENDING',
            'error_message' => null,
        ]);

        ProcessSyncOutboxJob::dispatch($outbox->id);
    }
}
