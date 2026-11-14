<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $enrolledIds = $user->eventParticipants()->pluck('event_id');

        $query = Event::with('attendanceSession')
            ->whereIn('id', $enrolledIds)
            ->latest('event_date');

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('code', 'like', $search)
                    ->orWhere('location', 'like', $search);
            });
        }

        $events = $query->paginate(10)->withQueryString();

        // Map existing attendance for these events
        $userAttendances = Attendance::where('user_id', $user->id)
            ->whereIn('event_id', $events->pluck('id'))
            ->get()
            ->keyBy('event_id');

        return view('member.events.index', compact('events', 'userAttendances'));
    }

    public function show(Event $event): View
    {
        $user = Auth::user();
        $event->load(['creator', 'attendanceSession']);

        $eligibility = $this->attendanceService->checkEligibility($user, $event);

        $existingAttendance = Attendance::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        $existingLeave = LeaveRequest::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        return view('member.events.show', compact(
            'event',
            'eligibility',
            'existingAttendance',
            'existingLeave'
        ));
    }
}
