<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceSessionController extends Controller
{
    public function create(Event $event): View|RedirectResponse
    {
        if ($event->attendanceSession) {
            return redirect()->route('admin.events.show', $event)
                ->with('error', 'Kegiatan ini sudah memiliki sesi absensi. Setiap kegiatan hanya boleh memiliki satu sesi.');
        }

        // Default open/close times aligned with event date and times
        $defaultOpen = $event->event_date->format('Y-m-d').' '.substr($event->start_time, 0, 5);
        $defaultClose = $event->event_date->format('Y-m-d').' '.substr($event->end_time, 0, 5);

        return view('admin.sessions.create', compact('event', 'defaultOpen', 'defaultClose'));
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        // Enforce rule: only 1 session per event
        if ($event->attendanceSession()->exists()) {
            return redirect()->route('admin.events.show', $event)
                ->with('error', 'Sesi absensi untuk kegiatan ini sudah ada. Tidak diperbolehkan membuat sesi kedua.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'open_time' => ['required', 'date'],
            'close_time' => ['required', 'date', 'after:open_time'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'status' => ['required', Rule::in(['OPEN', 'CLOSED'])],
        ], [
            'title.required' => 'Judul sesi wajib diisi.',
            'open_time.required' => 'Waktu buka sesi wajib diisi.',
            'close_time.required' => 'Waktu tutup sesi wajib diisi.',
            'close_time.after' => 'Waktu tutup harus setelah waktu buka.',
            'late_tolerance_minutes.required' => 'Toleransi keterlambatan wajib diisi.',
        ]);

        $validated['event_id'] = $event->id;

        $session = AttendanceSession::create($validated);

        AuditLog::record(
            action: 'SESSION_CREATED',
            description: "Sesi absensi '{$session->title}' untuk kegiatan {$event->title} dibuat.",
            entity: $session
        );

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Sesi absensi berhasil dibuat.');
    }

    public function edit(Event $event, AttendanceSession $session): View
    {
        return view('admin.sessions.edit', compact('event', 'session'));
    }

    public function update(Request $request, Event $event, AttendanceSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'open_time' => ['required', 'date'],
            'close_time' => ['required', 'date', 'after:open_time'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'status' => ['required', Rule::in(['OPEN', 'CLOSED'])],
        ]);

        $oldValues = $session->toArray();
        $session->update($validated);

        AuditLog::record(
            action: 'SESSION_UPDATED',
            description: "Sesi absensi #{$session->id} diperbarui.",
            entity: $session,
            oldValues: $oldValues,
            newValues: $session->toArray()
        );

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Sesi absensi berhasil diperbarui.');
    }

    public function toggleStatus(Event $event, AttendanceSession $session): RedirectResponse
    {
        $newStatus = $session->status === 'OPEN' ? 'CLOSED' : 'OPEN';
        $session->update(['status' => $newStatus]);

        AuditLog::record(
            action: 'SESSION_STATUS_TOGGLED',
            description: "Status sesi absensi #{$session->id} diubah menjadi {$newStatus}.",
            entity: $session
        );

        return back()->with('success', "Status sesi absensi diubah menjadi {$newStatus}.");
    }
}
