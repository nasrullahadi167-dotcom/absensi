<?php

namespace App\Services;

use App\Jobs\UploadAttendancePhotoToDriveJob;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        protected SyncOutboxService $outboxService
    ) {}

    /**
     * Check if a member is eligible to check-in for an event.
     */
    public function checkEligibility(User $user, Event $event): array
    {
        // 1. Event status check
        if ($event->status !== 'PUBLISHED') {
            return [
                'eligible' => false,
                'message' => 'Kegiatan ini tidak dalam status aktif atau telah selesai.',
            ];
        }

        // 2. Participant verification
        $isParticipant = EventParticipant::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isParticipant) {
            return [
                'eligible' => false,
                'message' => 'Anda tidak terdaftar sebagai peserta dalam kegiatan ini.',
            ];
        }

        // 3. Session verification
        $session = $event->attendanceSession;
        if (! $session) {
            return [
                'eligible' => false,
                'message' => 'Sesi absensi untuk kegiatan ini belum dibuat.',
            ];
        }

        if ($session->status !== 'OPEN') {
            return [
                'eligible' => false,
                'message' => 'Sesi absensi telah DITUTUP oleh administrator.',
            ];
        }

        $now = Carbon::now();
        if ($now->lessThan($session->open_time)) {
            $formattedOpen = $session->open_time->setTimezone('Asia/Jakarta')->format('d M Y H:i');

            return [
                'eligible' => false,
                'message' => "Sesi absensi belum dibuka. Dibuka pada: {$formattedOpen} WIB.",
            ];
        }

        if ($now->greaterThan($session->close_time)) {
            $formattedClose = $session->close_time->setTimezone('Asia/Jakarta')->format('d M Y H:i');

            return [
                'eligible' => false,
                'message' => "Sesi absensi telah berakhir pada: {$formattedClose} WIB.",
            ];
        }

        // 4. Duplicate attendance check
        $existing = Attendance::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return [
                'eligible' => false,
                'message' => "Anda sudah melakukan absensi untuk kegiatan ini (Status: {$existing->status}).",
                'attendance' => $existing,
            ];
        }

        return [
            'eligible' => true,
            'message' => 'Anda memenuhi syarat untuk melakukan absensi.',
            'session' => $session,
        ];
    }

    /**
     * Process and record member attendance with selfie photo and transactional integrity.
     */
    public function recordAttendance(
        User $user,
        Event $event,
        string $photoData,
        ?string $notes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Attendance {
        return DB::transaction(function () use ($user, $event, $photoData, $notes, $ipAddress, $userAgent) {
            // Lock event & session
            $eventLocked = Event::where('id', $event->id)->lockForUpdate()->firstOrFail();
            $session = AttendanceSession::where('event_id', $eventLocked->id)->lockForUpdate()->first();

            if (! $session || $session->status !== 'OPEN') {
                throw ValidationException::withMessages([
                    'session' => 'Sesi absensi tidak tersedia atau sudah ditutup.',
                ]);
            }

            $now = Carbon::now();
            if ($now->lessThan($session->open_time) || $now->greaterThan($session->close_time)) {
                throw ValidationException::withMessages([
                    'session' => 'Waktu absensi di luar batas waktu sesi yang ditentukan.',
                ]);
            }

            // Check participant
            $isParticipant = EventParticipant::where('event_id', $eventLocked->id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $isParticipant) {
                throw ValidationException::withMessages([
                    'participant' => 'Anda tidak terdaftar sebagai peserta kegiatan ini.',
                ]);
            }

            // Strict double-check duplicate check
            $alreadyAttended = Attendance::where('event_id', $eventLocked->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyAttended) {
                throw ValidationException::withMessages([
                    'attendance' => 'Anda sudah tercatat melakukan absensi pada kegiatan ini.',
                ]);
            }

            // Decode photo data (either data URL base64 or raw base64)
            $decodedImage = $this->decodePhoto($photoData);

            // Determine status: HADIR vs TERLAMBAT
            $status = $session->isLate($now) ? 'TERLAMBAT' : 'HADIR';

            // Store photo to private local disk
            $cleanEventCode = Str::slug($eventLocked->code, '-');
            $uniqueId = Str::uuid()->toString();
            $relativeDir = "attendance_photos/{$cleanEventCode}";
            $relativePath = "{$relativeDir}/ATT-{$uniqueId}.jpg";

            Storage::disk('local')->put($relativePath, $decodedImage);

            // Save attendance record
            $attendance = Attendance::create([
                'event_id' => $eventLocked->id,
                'attendance_session_id' => $session->id,
                'user_id' => $user->id,
                'check_in_time' => $now,
                'status' => $status,
                'photo_path' => $relativePath,
                'drive_sync_status' => 'PENDING',
                'notes' => $notes,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            // Queue Google Drive photo upload after commit
            $attendanceId = $attendance->id;
            DB::afterCommit(function () use ($attendanceId) {
                UploadAttendancePhotoToDriveJob::dispatch($attendanceId);
            });

            // Record outbox for Sheets sync
            $this->outboxService->record(
                eventType: 'ATTENDANCE_SYNC',
                aggregateType: 'Attendance',
                aggregateId: $attendance->id,
                payload: [
                    'attendance_id' => $attendance->id,
                    'event_code' => $eventLocked->code,
                    'user_id' => $user->id,
                    'status' => $status,
                ]
            );

            // Audit Log
            AuditLog::record(
                action: 'ATTENDANCE_RECORDED',
                description: "Anggota {$user->name} ({$user->username}) berhasil check-in pada kegiatan {$eventLocked->title} dengan status {$status}.",
                entity: $attendance
            );

            return $attendance;
        });
    }

    /**
     * Decode base64 / data-url selfie image with validation.
     */
    protected function decodePhoto(string $photoData): string
    {
        if (empty($photoData)) {
            throw ValidationException::withMessages([
                'photo' => 'Foto selfie wajib diambil untuk melakukan absensi.',
            ]);
        }

        // Check for Data URI format: data:image/(png|jpeg|webp);base64,....
        if (preg_match('/^data:image\/(jpeg|png|webp|jpg);base64,(.+)$/i', $photoData, $matches)) {
            $base64Data = $matches[2];
        } else {
            $base64Data = $photoData;
        }

        $decoded = base64_decode($base64Data, true);

        if ($decoded === false) {
            throw ValidationException::withMessages([
                'photo' => 'Format foto tidak valid atau data korup.',
            ]);
        }

        // Validate minimum and maximum size: 1KB to 6MB
        $byteSize = strlen($decoded);
        if ($byteSize < 1024) { // < 1KB
            throw ValidationException::withMessages([
                'photo' => 'Ukuran foto terlalu kecil. Pastikan foto selfie diambil dengan jelas.',
            ]);
        }

        if ($byteSize > 6 * 1024 * 1024) { // > 6MB
            throw ValidationException::withMessages([
                'photo' => 'Ukuran foto melebihi batas maksimal 6MB.',
            ]);
        }

        // Validate image header bytes using getimagesizefromstring
        $imageInfo = @getimagesizefromstring($decoded);
        if ($imageInfo === false || ! in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages([
                'photo' => 'File bukan gambar valid (JPEG, PNG, WEBP).',
            ]);
        }

        return $decoded;
    }
}
