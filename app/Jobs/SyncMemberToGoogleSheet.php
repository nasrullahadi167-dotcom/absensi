<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncMemberToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public int $userId
    ) {}

    public function handle(GoogleSheetsService $sheetsService): void
    {
        $user = User::with('division')->find($this->userId);

        if (! $user) {
            return;
        }

        if (! $sheetsService->isAvailable()) {
            Log::info("Google Sheets is disabled or not configured. Skipping member sync for User #{$user->id}");

            return;
        }

        $row = [
            $user->id,
            $user->username,
            $user->name,
            $user->email ?? '-',
            $user->phone ?? '-',
            $user->role,
            $user->division ? $user->division->name : 'Tanpa Divisi',
            $user->is_active ? 'AKTIF' : 'NONAKTIF',
            $user->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            $user->updated_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];

        $sheetsService->upsertRecord('Users', $user->id, $row);
    }
}
