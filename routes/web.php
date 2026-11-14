<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceSessionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\EventParticipantController;
use App\Http\Controllers\Admin\GoogleSyncController;
use App\Http\Controllers\Admin\LeaveRequestController as AdminLeaveRequestController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\AttendancePhotoController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Member\AttendanceController as MemberAttendanceController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\EventController as MemberEventController;
use App\Http\Controllers\Member\LeaveRequestController as MemberLeaveRequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect
Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('member.dashboard');
    }

    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated common routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::put('/change-password', [AuthController::class, 'updatePassword'])->name('password.update');
    Route::get('/attendance/{attendance}/photo', [AttendancePhotoController::class, 'show'])->name('attendance.photo');
});

// ==========================================
// ADMINISTRATOR PANEL
// ==========================================
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:ADMIN'])
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Divisions CRUD
        Route::resource('divisions', DivisionController::class)->except(['show']);

        // Members CRUD
        Route::resource('members', MemberController::class)->except(['show']);
        Route::post('/members/{member}/toggle-status', [MemberController::class, 'toggleStatus'])->name('members.toggle-status');
        Route::post('/members/{member}/reset-password', [MemberController::class, 'resetPassword'])->name('members.reset-password');

        // Events CRUD & Sub-modules
        Route::resource('events', AdminEventController::class);

        // Event Participants
        Route::get('/events/{event}/participants', [EventParticipantController::class, 'index'])->name('events.participants.index');
        Route::post('/events/{event}/participants', [EventParticipantController::class, 'store'])->name('events.participants.store');
        Route::post('/events/{event}/participants/batch', [EventParticipantController::class, 'batchStore'])->name('events.participants.batch');
        Route::delete('/events/{event}/participants/{participant}', [EventParticipantController::class, 'destroy'])->name('events.participants.destroy');

        // Attendance Session (Single session per event)
        Route::get('/events/{event}/session/create', [AttendanceSessionController::class, 'create'])->name('events.session.create');
        Route::post('/events/{event}/session', [AttendanceSessionController::class, 'store'])->name('events.session.store');
        Route::get('/events/{event}/session/{session}/edit', [AttendanceSessionController::class, 'edit'])->name('events.session.edit');
        Route::put('/events/{event}/session/{session}', [AttendanceSessionController::class, 'update'])->name('events.session.update');
        Route::post('/events/{event}/session/{session}/toggle', [AttendanceSessionController::class, 'toggleStatus'])->name('events.session.toggle');

        // Attendances Management
        Route::resource('attendances', AdminAttendanceController::class);
        Route::post('/attendances/{attendance}/retry-drive', [AdminAttendanceController::class, 'retryDriveUpload'])->name('attendances.retry-drive');

        // Leave Requests (Izin / Sakit)
        Route::get('/leaves', [AdminLeaveRequestController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/{leave}', [AdminLeaveRequestController::class, 'show'])->name('leaves.show');
        Route::post('/leaves/{leave}/review', [AdminLeaveRequestController::class, 'review'])->name('leaves.review');
        Route::get('/leaves/{leave}/attachment', [AdminLeaveRequestController::class, 'downloadAttachment'])->name('leaves.attachment');

        // Reports & Exports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

        // Google Sheets & Drive Sync Monitoring
        Route::get('/sync', [GoogleSyncController::class, 'index'])->name('sync.index');
        Route::post('/sync/{outbox}/retry', [GoogleSyncController::class, 'retry'])->name('sync.retry');
        Route::post('/sync/retry-failed', [GoogleSyncController::class, 'retryFailed'])->name('sync.retry-failed');
        Route::post('/sync/full-resync', [GoogleSyncController::class, 'fullResync'])->name('sync.full-resync');
        Route::post('/sync/init-sheets', [GoogleSyncController::class, 'initSheets'])->name('sync.init-sheets');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

        // System Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    });

// ==========================================
// MEMBER PANEL
// ==========================================
Route::prefix('member')
    ->name('member.')
    ->middleware(['auth', 'active', 'role:MEMBER'])
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [MemberDashboardController::class, 'profile'])->name('profile');
        Route::put('/profile', [MemberDashboardController::class, 'updateProfile'])->name('profile.update');

        // Events
        Route::get('/events', [MemberEventController::class, 'index'])->name('events.index');
        Route::get('/events/{event}', [MemberEventController::class, 'show'])->name('events.show');

        // Attendance Camera Selfie
        Route::get('/events/{event}/attendance', [MemberAttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/events/{event}/attendance', [MemberAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance-history', [MemberAttendanceController::class, 'history'])->name('attendance.history');

        // Leave Requests (Izin / Sakit)
        Route::get('/leaves', [MemberLeaveRequestController::class, 'index'])->name('leaves.index');
        Route::get('/events/{event}/leave', [MemberLeaveRequestController::class, 'create'])->name('leaves.create');
        Route::post('/events/{event}/leave', [MemberLeaveRequestController::class, 'store'])->name('leaves.store');
    });
