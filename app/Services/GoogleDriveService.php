<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected ?Drive $drive = null;

    public function __construct(
        protected GoogleClientManager $clientManager
    ) {
        $client = $this->clientManager->getClient();
        if ($client) {
            $this->drive = new Drive($client);
        }
    }

    /**
     * Check if Drive service is available.
     */
    public function isAvailable(): bool
    {
        return $this->drive !== null;
    }

    /**
     * Upload an attendance photo to Google Drive maintaining the structure:
     * ORGA-ATTEND / Attendance / {EVENT_CODE} / {ATTENDANCE_ID}.jpg
     */
    public function uploadAttendancePhoto(Attendance $attendance, string $absoluteLocalPath): ?string
    {
        if (! $this->isAvailable()) {
            Log::info("Google Drive not available. Keeping attendance #{$attendance->id} photo locally.");
            $attendance->update([
                'drive_sync_status' => 'PENDING',
                'drive_sync_error' => 'Google Drive API belum dikonfigurasi.',
            ]);

            return null;
        }

        if (! file_exists($absoluteLocalPath)) {
            Log::error("Local photo file does not exist at {$absoluteLocalPath} for attendance #{$attendance->id}");
            $attendance->update([
                'drive_sync_status' => 'FAILED',
                'drive_sync_error' => 'File foto lokal tidak ditemukan.',
            ]);

            return null;
        }

        try {
            $attendance->loadMissing('event');
            $eventCode = $attendance->event ? $attendance->event->code : 'GENERAL';

            // Get root parent folder ID from config if defined, else create in root
            $rootParentId = config('services.google.drive_folder_id');

            // 1. Get or create ORGA-ATTEND folder
            $orgaFolderId = $this->getOrCreateFolder('ORGA-ATTEND', $rootParentId);

            // 2. Get or create Attendance subfolder
            $attendanceFolderId = $this->getOrCreateFolder('Attendance', $orgaFolderId);

            // 3. Get or create Event folder
            $eventFolderId = $this->getOrCreateFolder($eventCode, $attendanceFolderId);

            $fileName = "ATT-{$attendance->id}.jpg";
            $fileContent = file_get_contents($absoluteLocalPath);
            $mimeType = File::mimeType($absoluteLocalPath) ?: 'image/jpeg';

            $fileMetadata = new DriveFile([
                'name' => $fileName,
                'parents' => [$eventFolderId],
                'description' => "Attendance Photo for Event: {$eventCode}, User ID: {$attendance->user_id}",
            ]);

            $file = $this->drive->files->create($fileMetadata, [
                'data' => $fileContent,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, name, webViewLink',
            ]);

            $driveFileId = $file->getId();

            $attendance->update([
                'drive_file_id' => $driveFileId,
                'drive_file_name' => $fileName,
                'drive_sync_status' => 'SYNCED',
                'drive_sync_error' => null,
            ]);

            AuditLog::record(
                action: 'DRIVE_PHOTO_UPLOADED',
                description: "Foto absensi #{$attendance->id} berhasil diunggah ke Google Drive (File ID: {$driveFileId})",
                entity: $attendance
            );

            return $driveFileId;
        } catch (\Throwable $e) {
            Log::error("Failed to upload attendance #{$attendance->id} photo to Drive: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $attendance->update([
                'drive_sync_status' => 'FAILED',
                'drive_sync_error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download or retrieve the binary stream of a Drive file for authenticated viewing.
     */
    public function getFileContent(string $driveFileId): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            $response = $this->drive->files->get($driveFileId, [
                'alt' => 'media',
            ]);

            return (string) $response->getBody();
        } catch (\Throwable $e) {
            Log::error("Error retrieving Drive file {$driveFileId}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Find an existing folder by name and parent, or create it.
     */
    protected function getOrCreateFolder(string $folderName, ?string $parentId = null): string
    {
        $escapedName = str_replace("'", "\\'", $folderName);
        $query = "mimeType = 'application/vnd.google-apps.folder' and name = '{$escapedName}' and trashed = false";

        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }

        $results = $this->drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'pageSize' => 1,
        ]);

        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0]->getId();
        }

        // Folder not found, create it
        $folderMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        if ($parentId) {
            $folderMetadata->setParents([$parentId]);
        }

        $created = $this->drive->files->create($folderMetadata, [
            'fields' => 'id',
        ]);

        return $created->getId();
    }
}
