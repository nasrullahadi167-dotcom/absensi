<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\SyncOutbox;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main administrator dashboard.
     */
    public function index(): View
    {
        $totalMembers = User::where('role', 'MEMBER')->count();
        $totalActiveMembers = User::where('role', 'MEMBER')->where('is_active', true)->count();
        $totalEvents = Event::count();
        $totalParticipants = EventParticipant::count();

        // Attendance stats
        $totalHadir = Attendance::where('status', 'HADIR')->count();
        $totalTerlambat = Attendance::where('status', 'TERLAMBAT')->count();
        $totalIzin = Attendance::where('status', 'IZIN')->count();
        $totalSakit = Attendance::where('status', 'SAKIT')->count();
        $totalAlpa = Attendance::where('status', 'ALPA')->count();

        // Recent events
        $recentEvents = Event::with('attendanceSession')
            ->latest('event_date')
            ->take(5)
            ->get();

        // Recent activities
        $recentActivities = AuditLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        // Outbox & Sync status
        $outboxSynced = SyncOutbox::synced()->count();
        $outboxPending = SyncOutbox::pending()->count();
        $outboxFailed = SyncOutbox::failed()->count();
        $lastSync = SyncOutbox::whereNotNull('synced_at')->latest('synced_at')->first();

        // Attendance trend for the last 7 days
        $days = [];
        $hadirSeries = [];
        $terlambatSeries = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayLabel = $date->format('d M');
            $days[] = $dayLabel;

            $hadirSeries[] = Attendance::where('status', 'HADIR')
                ->whereDate('check_in_time', $date)
                ->count();

            $terlambatSeries[] = Attendance::where('status', 'TERLAMBAT')
                ->whereDate('check_in_time', $date)
                ->count();
        }

        return view('admin.dashboard', compact(
            'totalMembers',
            'totalActiveMembers',
            'totalEvents',
            'totalParticipants',
            'totalHadir',
            'totalTerlambat',
            'totalIzin',
            'totalSakit',
            'totalAlpa',
            'recentEvents',
            'recentActivities',
            'outboxSynced',
            'outboxPending',
            'outboxFailed',
            'lastSync',
            'days',
            'hadirSeries',
            'terlambatSeries'
        ));
    }
}
