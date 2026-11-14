<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Show the camera check-in page for an event.
     */
    public function create(Event $event): View|RedirectResponse
    {
        $user = Auth::user();
        $eligibility = $this->attendanceService->checkEligibility($user, $event);

        if (! $eligibility['eligible']) {
            return redirect()->route('member.events.show', $event)
                ->with('error', $eligibility['message']);
        }

        $session = $eligibility['session'];

        return view('member.attendance.checkin', compact('event', 'session'));
    }

    /**
     * Store the attendance submission with selfie capture.
     */
    public function store(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        // Support both direct JSON/AJAX base64 upload and traditional form submit (with fallback camera file)
        $photoData = $request->input('photo_data');

        if (empty($photoData) && $request->hasFile('photo_fallback')) {
            $file = $request->file('photo_fallback');
            $mime = $file->getMimeType();
            $photoData = 'data:'.$mime.';base64,'.base64_encode($file->getContent());
        }

        if (empty($photoData)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto selfie wajib diambil untuk melakukan absensi.',
                ], 422);
            }

            return back()->withInput()->with('error', 'Foto selfie wajib diambil.');
        }

        try {
            $attendance = $this->attendanceService->recordAttendance(
                user: $user,
                event: $event,
                photoData: $photoData,
                notes: $request->input('notes'),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Absensi berhasil dicatat dengan status {$attendance->status}!",
                    'redirect_url' => route('member.events.show', $event),
                    'attendance' => $attendance,
                ]);
            }

            return redirect()->route('member.events.show', $event)
                ->with('success', "Absensi Anda berhasil dicatat dengan status {$attendance->status}!");
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Validasi absensi gagal.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withInput()->with('error', $msg);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal memproses absensi: '.$e->getMessage()], 500);
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menyimpan absensi.');
        }
    }

    /**
     * Display member attendance history.
     */
    public function history(Request $request): View
    {
        $user = Auth::user();

        $query = Attendance::with(['event', 'attendanceSession'])
            ->where('user_id', $user->id)
            ->latest('check_in_time');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->whereHas('event', function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('code', 'like', $search);
            });
        }

        $attendances = $query->paginate(15)->withQueryString();

        return view('member.attendance.history', compact('attendances'));
    }
}
