<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UploadAttendancePhotoToDriveJob;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Event;
use App\Models\User;
use App\Services\SyncOutboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        protected SyncOutboxService $outboxService
    ) {}

    public function index(Request $request): View
    {
        $query = Attendance::with(['event', 'user.division', 'attendanceSession'])
            ->latest('check_in_time');

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('division_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('division_id', $request->input('division_id'));
            });
        }

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qu) use ($search) {
                    $qu->where('name', 'like', $search)
                        ->orWhere('username', 'like', $search);
                })->orWhereHas('event', function ($qe) use ($search) {
                    $qe->where('title', 'like', $search);
                });
            });
        }

        $attendances = $query->paginate(15)->withQueryString();
        $events = Event::orderBy('event_date', 'desc')->get();
        $divisions = Division::orderBy('name')->get();

        return view('admin.attendances.index', compact('attendances', 'events', 'divisions'));
    }

    public function show(Attendance $attendance): View
    {
        $attendance->load(['event', 'user.division', 'attendanceSession']);

        return view('admin.attendances.show', compact('attendance'));
    }

    public function create(): View
    {
        $events = Event::where('status', 'PUBLISHED')->orderBy('event_date', 'desc')->get();
        $users = User::where('role', 'MEMBER')->where('is_active', true)->orderBy('name')->get();

        return view('admin.attendances.create', compact('events', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', Rule::in(['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'ALPA'])],
            'check_in_time' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'event_id.required' => 'Pilih kegiatan.',
            'user_id.required' => 'Pilih anggota.',
            'status.required' => 'Pilih status kehadiran.',
        ]);

        $exists = Attendance::where('event_id', $validated['event_id'])
            ->where('user_id', $validated['user_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Anggota ini sudah tercatat kehadirannya pada kegiatan ini.');
        }

        $event = Event::findOrFail($validated['event_id']);
        $session = $event->attendanceSession;

        $attendance = Attendance::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session ? $session->id : null,
            'user_id' => $validated['user_id'],
            'check_in_time' => $validated['check_in_time'] ?? now(),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ? '(Input Manual Admin) '.$validated['notes'] : 'Dicatat manual oleh admin',
            'drive_sync_status' => 'PENDING',
        ]);

        $this->outboxService->record(
            eventType: 'ATTENDANCE_SYNC',
            aggregateType: 'Attendance',
            aggregateId: $attendance->id,
            payload: [
                'attendance_id' => $attendance->id,
                'status' => $attendance->status,
            ]
        );

        AuditLog::record(
            action: 'ATTENDANCE_MANUAL_CREATED',
            description: "Admin mencatat kehadiran user #{$attendance->user_id} pada kegiatan #{$event->id} dengan status {$attendance->status}.",
            entity: $attendance
        );

        return redirect()->route('admin.attendances.index')
            ->with('success', 'Data kehadiran manual berhasil disimpan.');
    }

    public function edit(Attendance $attendance): View
    {
        return view('admin.attendances.edit', compact('attendance'));
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'ALPA'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $attendance->status;
        $attendance->update($validated);

        $this->outboxService->record(
            eventType: 'ATTENDANCE_SYNC',
            aggregateType: 'Attendance',
            aggregateId: $attendance->id,
            payload: [
                'attendance_id' => $attendance->id,
                'status' => $attendance->status,
            ]
        );

        AuditLog::record(
            action: 'ATTENDANCE_UPDATED',
            description: "Status kehadiran #{$attendance->id} diubah dari {$oldStatus} menjadi {$attendance->status} oleh admin.",
            entity: $attendance
        );

        return redirect()->route('admin.attendances.index')
            ->with('success', 'Status absensi berhasil diperbarui.');
    }

    public function retryDriveUpload(Attendance $attendance): RedirectResponse
    {
        if (empty($attendance->photo_path)) {
            return back()->with('error', 'Kehadiran ini tidak memiliki file foto lokal.');
        }

        $attendance->update([
            'drive_sync_status' => 'PENDING',
            'drive_sync_error' => null,
        ]);

        UploadAttendancePhotoToDriveJob::dispatch($attendance->id);

        return back()->with('success', 'Job unggah foto ke Google Drive berhasil dijadwalkan ulang.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $id = $attendance->id;
        $attendance->delete();

        AuditLog::record(
            action: 'ATTENDANCE_DELETED',
            description: "Data kehadiran #{$id} telah dihapus oleh admin.",
            entity: null
        );

        return redirect()->route('admin.attendances.index')
            ->with('success', 'Data kehadiran berhasil dihapus.');
    }
}
