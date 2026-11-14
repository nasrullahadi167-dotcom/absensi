<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncEventToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $eventId
    ) {}

    public function handle(GoogleSheetsService $sheetsService): void
    {
        $event = Event::with('creator')->find($this->eventId);

        if (! $event) {
            return;
        }

        if (! $sheetsService->isAvailable()) {
            Log::info("Google Sheets not available. Skipping event sync for Event #{$event->id}");

            return;
        }

        $row = [
            $event->id,
            $event->code,
            $event->title,
            $event->location,
            $event->event_date->format('Y-m-d'),
            $event->start_time,
            $event->end_time,
            $event->status,
            $event->creator ? $event->creator->name : '-',
            $event->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];

        $sheetsService->upsertRecord('Events', $event->id, $row);
    }
}
