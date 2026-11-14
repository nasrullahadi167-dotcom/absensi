<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Services\SyncOutboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventParticipantController extends Controller
{
    public function __construct(
        protected SyncOutboxService $outboxService
    ) {}

    public function index(Event $event, Request $request): View
    {
        $query = $event->participants()->with('user.division');

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('username', 'like', $search);
            });
        }

        $participants = $query->paginate(20)->withQueryString();
        $divisions = Division::orderBy('name')->get();

        // Get users not yet enrolled in this event
        $enrolledIds = $event->participants()->pluck('user_id')->toArray();
        $availableUsers = User::where('role', 'MEMBER')
            ->where('is_active', true)
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('name')
            ->get();

        return view('admin.events.participants', compact('event', 'participants', 'divisions', 'availableUsers'));
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ], [
            'user_id.required' => 'Pilih anggota terlebih dahulu.',
        ]);

        $userId = $request->input('user_id');

        $exists = EventParticipant::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Anggota ini sudah terdaftar sebagai peserta.');
        }

        $participant = EventParticipant::create([
            'event_id' => $event->id,
            'user_id' => $userId,
            'registered_at' => now(),
        ]);

        $this->outboxService->record(
            eventType: 'PARTICIPANT_SYNC',
            aggregateType: 'EventParticipant',
            aggregateId: $participant->id,
            payload: [
                'participant_id' => $participant->id,
                'event_code' => $event->code,
                'user_id' => $userId,
            ]
        );

        AuditLog::record(
            action: 'PARTICIPANT_ADDED',
            description: "Anggota #{$userId} didaftarkan ke kegiatan {$event->title}.",
            entity: $participant
        );

        return back()->with('success', 'Peserta berhasil ditambahkan.');
    }

    public function batchStore(Request $request, Event $event): RedirectResponse
    {
        $mode = $request->input('enroll_mode'); // 'all' or 'division'
        $query = User::where('role', 'MEMBER')->where('is_active', true);

        if ($mode === 'division') {
            $request->validate([
                'division_id' => ['required', 'exists:divisions,id'],
            ], [
                'division_id.required' => 'Pilih divisi terlebih dahulu.',
            ]);
            $query->where('division_id', $request->input('division_id'));
        }

        $candidateUsers = $query->get();
        $enrolledIds = $event->participants()->pluck('user_id')->toArray();

        $addedCount = 0;
        foreach ($candidateUsers as $u) {
            if (! in_array($u->id, $enrolledIds, true)) {
                $participant = EventParticipant::create([
                    'event_id' => $event->id,
                    'user_id' => $u->id,
                    'registered_at' => now(),
                ]);

                $this->outboxService->record(
                    eventType: 'PARTICIPANT_SYNC',
                    aggregateType: 'EventParticipant',
                    aggregateId: $participant->id,
                    payload: [
                        'participant_id' => $participant->id,
                        'event_code' => $event->code,
                        'user_id' => $u->id,
                    ]
                );

                $addedCount++;
            }
        }

        AuditLog::record(
            action: 'PARTICIPANTS_BATCH_ADDED',
            description: "Sebanyak {$addedCount} peserta berhasil didaftarkan secara massal ke kegiatan {$event->title}.",
            entity: $event
        );

        return back()->with('success', "Berhasil mendaftarkan {$addedCount} peserta baru.");
    }

    public function destroy(Event $event, EventParticipant $participant): RedirectResponse
    {
        $hasAttendance = $event->attendances()->where('user_id', $participant->user_id)->exists();
        if ($hasAttendance) {
            return back()->with('error', 'Peserta tidak dapat dihapus karena sudah memiliki data absensi pada kegiatan ini.');
        }

        $participant->delete();

        AuditLog::record(
            action: 'PARTICIPANT_REMOVED',
            description: "Peserta user ID #{$participant->user_id} dikeluarkan dari kegiatan {$event->title}.",
            entity: $event
        );

        return back()->with('success', 'Peserta berhasil dikeluarkan.');
    }
}
