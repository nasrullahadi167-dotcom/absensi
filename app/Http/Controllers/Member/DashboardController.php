<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the member dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();
        $user->load('division');

        // Events where member is enrolled
        $enrolledEventIds = $user->eventParticipants()->pluck('event_id');

        $activeEvents = Event::with('attendanceSession')
            ->whereIn('id', $enrolledEventIds)
            ->where('status', 'PUBLISHED')
            ->whereDate('event_date', '>=', Carbon::today()->subDays(1))
            ->orderBy('event_date')
            ->get();

        // Get user's attendance mapped by event_id for active events
        $userAttendances = Attendance::where('user_id', $user->id)
            ->whereIn('event_id', $activeEvents->pluck('id'))
            ->get()
            ->keyBy('event_id');

        // User statistics
        $stats = [
            'total_kegiatan' => $enrolledEventIds->count(),
            'total_hadir' => Attendance::where('user_id', $user->id)->where('status', 'HADIR')->count(),
            'total_terlambat' => Attendance::where('user_id', $user->id)->where('status', 'TERLAMBAT')->count(),
            'total_izin' => Attendance::where('user_id', $user->id)->where('status', 'IZIN')->count(),
            'total_sakit' => Attendance::where('user_id', $user->id)->where('status', 'SAKIT')->count(),
        ];

        // Recent 5 attendances
        $recentAttendances = Attendance::with(['event', 'attendanceSession'])
            ->where('user_id', $user->id)
            ->latest('check_in_time')
            ->take(5)
            ->get();

        // Recent 5 leave requests
        $recentLeaves = LeaveRequest::with('event')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('member.dashboard', compact(
            'user',
            'activeEvents',
            'userAttendances',
            'stats',
            'recentAttendances',
            'recentLeaves'
        ));
    }

    /**
     * Show member profile page.
     */
    public function profile(): View
    {
        $user = Auth::user();
        $user->load('division');

        return view('member.profile', compact('user'));
    }

    /**
     * Update member profile info.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh anggota lain.',
        ]);

        $oldValues = $user->only(['name', 'email', 'phone']);
        $user->update($validated);

        AuditLog::record(
            action: 'MEMBER_PROFILE_UPDATED',
            description: "Anggota {$user->username} memperbarui profil mandiri.",
            entity: $user,
            oldValues: $oldValues,
            newValues: $validated
        );

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
