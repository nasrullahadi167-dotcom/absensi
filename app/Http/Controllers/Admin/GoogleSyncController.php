<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Event;
use App\Models\LeaveRequest;
use App\Models\SyncOutbox;
use App\Models\User;
use App\Services\GoogleClientManager;
use App\Services\GoogleSheetsService;
use App\Services\SyncOutboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoogleSyncController extends Controller
{
    public function __construct(
        protected GoogleSheetsService $sheetsService,
        protected GoogleClientManager $clientManager,
        protected SyncOutboxService $outboxService
    ) {}

    public function index(Request $request): View
    {
        $totalSynced = SyncOutbox::synced()->count();
        $totalPending = SyncOutbox::pending()->count();
        $totalFailed = SyncOutbox::failed()->count();
        $lastSync = SyncOutbox::whereNotNull('synced_at')->latest('synced_at')->first();

        $query = SyncOutbox::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        $outboxItems = $query->paginate(20)->withQueryString();
        $isConfigured = $this->clientManager->isConfigured();
        $spreadsheetId = config('services.google.spreadsheet_id');

        return view('admin.sync.index', compact(
            'totalSynced',
            'totalPending',
            'totalFailed',
            'lastSync',
            'outboxItems',
            'isConfigured',
            'spreadsheetId'
        ));
    }

    public function retry(SyncOutbox $outbox): RedirectResponse
    {
        $this->outboxService->retryOutbox($outbox);

        return back()->with('success', "Item antrean sinkronisasi #{$outbox->id} berhasil dijadwalkan ulang.");
    }

    public function retryFailed(): RedirectResponse
    {
        $failedItems = SyncOutbox::failed()->get();

        foreach ($failedItems as $item) {
            $this->outboxService->retryOutbox($item);
        }

        return back()->with('success', "Sebanyak {$failedItems->count()} data gagal berhasil dijadwalkan ulang untuk disinkronkan.");
    }

    public function fullResync(): RedirectResponse
    {
        // Enqueue all users, divisions, events, attendances, and leaves for fresh sync
        $count = 0;

        foreach (Division::all() as $d) {
            $this->outboxService->record('DIVISION_SYNC', 'Division', $d->id, $d->toArray());
            $count++;
        }

        foreach (User::all() as $u) {
            $this->outboxService->record('USER_SYNC', 'User', $u->id, ['id' => $u->id]);
            $count++;
        }

        foreach (Event::all() as $e) {
            $this->outboxService->record('EVENT_SYNC', 'Event', $e->id, ['id' => $e->id]);
            $count++;
        }

        foreach (Attendance::all() as $a) {
            $this->outboxService->record('ATTENDANCE_SYNC', 'Attendance', $a->id, ['id' => $a->id]);
            $count++;
        }

        foreach (LeaveRequest::all() as $l) {
            $this->outboxService->record('LEAVE_SYNC', 'LeaveRequest', $l->id, ['id' => $l->id]);
            $count++;
        }

        return back()->with('success', "Sinkronisasi penuh berhasil dijadwalkan: {$count} data dimasukkan ke antrean outbox.");
    }

    public function initSheets(): RedirectResponse
    {
        $result = $this->sheetsService->initializeSpreadsheet();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
