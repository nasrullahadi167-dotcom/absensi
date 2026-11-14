<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\GoogleDriveService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendancePhotoController extends Controller
{
    /**
     * Securely serve attendance selfie photo with strict authorization.
     */
    public function show(Attendance $attendance, GoogleDriveService $driveService): BinaryFileResponse|Response
    {
        $user = Auth::user();

        // Only Admin or the owner can view this photo
        if (! $user->isAdmin() && $user->id !== $attendance->user_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat foto ini.');
        }

        // 1. Try local private storage first
        if ($attendance->photo_path && Storage::disk('local')->exists($attendance->photo_path)) {
            $path = Storage::disk('local')->path($attendance->photo_path);
            $mimeType = File::mimeType($path) ?: 'image/jpeg';

            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]);
        }

        // 2. Fallback to Google Drive stream if configured
        if ($attendance->drive_file_id && $driveService->isAvailable()) {
            $content = $driveService->getFileContent($attendance->drive_file_id);
            if ($content) {
                return response($content, 200, [
                    'Content-Type' => 'image/jpeg',
                    'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                ]);
            }
        }

        abort(404, 'Foto absensi tidak ditemukan.');
    }
}
