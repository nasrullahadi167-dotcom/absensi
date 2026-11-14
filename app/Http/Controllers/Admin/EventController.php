<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Services\SyncOutboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        protected SyncOutboxService $outboxService
    ) {}

    public function index(Request $request): View
    {
        $query = Event::with(['creator', 'attendanceSession'])
            ->withCount(['participants', 'attendances']);

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('code', 'like', $search)
                    ->orWhere('location', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('event_date', $request->input('date'));
        }

        $events = $query->latest('event_date')->paginate(10)->withQueryString();

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        // Auto-generate suggested event code e.g. EVT-202611-001
        $prefix = 'EVT-'.date('Ym').'-';
        $lastEvent = Event::where('code', 'like', $prefix.'%')->latest('id')->first();
        $nextNumber = 1;
        if ($lastEvent) {
            $parts = explode('-', $lastEvent->code);
            $lastNum = (int) end($parts);
            $nextNumber = $lastNum + 1;
        }
        $suggestedCode = $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        return view('admin.events.create', compact('suggestedCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:events,code'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:200'],
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(['DRAFT', 'PUBLISHED', 'COMPLETED', 'CANCELLED'])],
        ], [
            'code.required' => 'Kode kegiatan wajib diisi.',
            'code.unique' => 'Kode kegiatan sudah digunakan.',
            'title.required' => 'Nama kegiatan wajib diisi.',
            'location.required' => 'Lokasi kegiatan wajib diisi.',
            'event_date.required' => 'Tanggal kegiatan wajib diisi.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        $validated['created_by'] = auth()->id();

        $event = Event::create($validated);

        $this->outboxService->record(
            eventType: 'EVENT_SYNC',
            aggregateType: 'Event',
            aggregateId: $event->id,
            payload: $event->toArray()
        );

        AuditLog::record(
            action: 'EVENT_CREATED',
            description: "Kegiatan {$event->title} ({$event->code}) dibuat dengan status {$event->status}.",
            entity: $event
        );

        return redirect()->route('admin.events.show', $event)
            ->with('success', "Kegiatan '{$event->title}' berhasil dibuat. Silakan atur peserta dan sesi absensi.");
    }

    public function show(Event $event): View
    {
        $event->load([
            'creator',
            'attendanceSession',
            'participants.user.division',
            'attendances.user',
            'leaveRequests.user',
        ]);

        $totalParticipants = $event->participants->count();
        $totalHadir = $event->attendances->where('status', 'HADIR')->count();
        $totalTerlambat = $event->attendances->where('status', 'TERLAMBAT')->count();
        $totalIzin = $event->attendances->where('status', 'IZIN')->count();
        $totalSakit = $event->attendances->where('status', 'SAKIT')->count();

        return view('admin.events.show', compact(
            'event',
            'totalParticipants',
            'totalHadir',
            'totalTerlambat',
            'totalIzin',
            'totalSakit'
        ));
    }

    public function edit(Event $event): View
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:events,code,'.$event->id],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:200'],
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(['DRAFT', 'PUBLISHED', 'COMPLETED', 'CANCELLED'])],
        ]);

        $oldValues = $event->toArray();
        $event->update($validated);

        $this->outboxService->record(
            eventType: 'EVENT_SYNC',
            aggregateType: 'Event',
            aggregateId: $event->id,
            payload: $event->toArray()
        );

        AuditLog::record(
            action: 'EVENT_UPDATED',
            description: "Kegiatan {$event->title} ({$event->code}) diperbarui.",
            entity: $event,
            oldValues: $oldValues,
            newValues: $event->toArray()
        );

        return redirect()->route('admin.events.show', $event)
            ->with('success', "Kegiatan '{$event->title}' berhasil diperbarui.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $hasAttendances = $event->attendances()->count() > 0;
        if ($hasAttendances) {
            return back()->with('error', 'Kegiatan tidak dapat dihapus karena sudah memiliki riwayat absensi.');
        }

        $title = $event->title;
        $event->delete();

        AuditLog::record(
            action: 'EVENT_DELETED',
            description: "Kegiatan {$title} telah dihapus.",
            entity: null
        );

        return redirect()->route('admin.events.index')
            ->with('success', "Kegiatan '{$title}' berhasil dihapus.");
    }
}
