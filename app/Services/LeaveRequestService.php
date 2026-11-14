<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function __construct(
        protected SyncOutboxService $outboxService
    ) {}

    /**
     * Submit a leave request (IZIN / SAKIT) for an event.
     */
    public function submit(User $user, Event $event, string $type, string $reason, ?UploadedFile $attachment = null): LeaveRequest
    {
        // 1. Participant check
        $isParticipant = EventParticipant::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isParticipant) {
            throw ValidationException::withMessages([
                'event_id' => 'Anda tidak terdaftar sebagai peserta dalam kegiatan ini.',
            ]);
        }

        // 2. Check if already submitted leave or attended
        $existingLeave = LeaveRequest::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existingLeave) {
            throw ValidationException::withMessages([
                'leave' => "Anda sudah pernah mengajukan {$existingLeave->type} untuk kegiatan ini (Status: {$existingLeave->status}).",
            ]);
        }

        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existingAttendance && in_array($existingAttendance->status, ['HADIR', 'TERLAMBAT'])) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda sudah tercatat hadir pada kegiatan ini.',
            ]);
        }

        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('leave_attachments', 'local');
        }

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'type' => $type,
            'reason' => $reason,
            'attachment_path' => $attachmentPath,
            'status' => 'PENDING',
        ]);

        $this->outboxService->record(
            eventType: 'LEAVE_SYNC',
            aggregateType: 'LeaveRequest',
            aggregateId: $leave->id,
            payload: [
                'leave_id' => $leave->id,
                'user_id' => $user->id,
                'type' => $type,
                'status' => 'PENDING',
            ]
        );

        AuditLog::record(
            action: 'LEAVE_SUBMITTED',
            description: "Pengajuan {$type} diajukan oleh {$user->name} untuk kegiatan {$event->title}.",
            entity: $leave
        );

        return $leave;
    }

    /**
     * Admin review: Approve or Reject leave request.
     */
    public function review(LeaveRequest $leave, User $reviewer, string $action, ?string $adminNotes = null): LeaveRequest
    {
        if (! in_array($action, ['APPROVED', 'REJECTED'], true)) {
            throw new \InvalidArgumentException('Status review tidak valid.');
        }

        return DB::transaction(function () use ($leave, $reviewer, $action, $adminNotes) {
            $leave->update([
                'status' => $action,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'admin_notes' => $adminNotes,
            ]);

            // If approved, create or update attendance entry with IZIN or SAKIT
            if ($action === 'APPROVED') {
                $session = $leave->event->attendanceSession;
                $attendanceStatus = $leave->type; // 'IZIN' or 'SAKIT'

                $attendance = Attendance::updateOrCreate(
                    [
                        'event_id' => $leave->event_id,
                        'user_id' => $leave->user_id,
                    ],
                    [
                        'attendance_session_id' => $session ? $session->id : null,
                        'check_in_time' => now(),
                        'status' => $attendanceStatus,
                        'notes' => "Disetujui dari pengajuan {$leave->type}. Catatan: ".($adminNotes ?? '-'),
                    ]
                );

                $this->outboxService->record(
                    eventType: 'ATTENDANCE_SYNC',
                    aggregateType: 'Attendance',
                    aggregateId: $attendance->id,
                    payload: [
                        'attendance_id' => $attendance->id,
                        'status' => $attendanceStatus,
                    ]
                );
            }

            $this->outboxService->record(
                eventType: 'LEAVE_SYNC',
                aggregateType: 'LeaveRequest',
                aggregateId: $leave->id,
                payload: [
                    'leave_id' => $leave->id,
                    'status' => $action,
                ]
            );

            AuditLog::record(
                action: 'LEAVE_REVIEWED',
                description: "Pengajuan {$leave->type} #{$leave->id} telah di-{$action} oleh {$reviewer->name}.",
                entity: $leave
            );

            return $leave;
        });
    }
}
