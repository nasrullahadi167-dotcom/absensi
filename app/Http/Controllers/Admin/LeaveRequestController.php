<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveService
    ) {}

    public function index(Request $request): View
    {
        $query = LeaveRequest::with(['user.division', 'event', 'reviewer'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $leaves = $query->paginate(15)->withQueryString();

        return view('admin.leaves.index', compact('leaves'));
    }

    public function show(LeaveRequest $leave): View
    {
        $leave->load(['user.division', 'event', 'reviewer']);

        return view('admin.leaves.show', compact('leave'));
    }

    public function review(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:APPROVED,REJECTED'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->leaveService->review(
            leave: $leave,
            reviewer: auth()->user(),
            action: $request->input('action'),
            adminNotes: $request->input('admin_notes')
        );

        $statusLabel = $request->input('action') === 'APPROVED' ? 'disetujui' : 'ditolak';

        return redirect()->route('admin.leaves.index')
            ->with('success', "Pengajuan {$leave->type} dari {$leave->user->name} berhasil {$statusLabel}.");
    }

    public function downloadAttachment(LeaveRequest $leave): BinaryFileResponse
    {
        if (! $leave->attachment_path || ! Storage::disk('local')->exists($leave->attachment_path)) {
            abort(404, 'Lampiran surat bukti tidak ditemukan.');
        }

        return response()->download(Storage::disk('local')->path($leave->attachment_path));
    }
}
