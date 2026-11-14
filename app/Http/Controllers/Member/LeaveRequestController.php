<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveService
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        $leaves = LeaveRequest::with(['event', 'reviewer'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('member.leaves.index', compact('leaves'));
    }

    public function create(Event $event): View|RedirectResponse
    {
        $user = Auth::user();

        // Check if member already applied
        $existing = LeaveRequest::where('user_id', $user->id)->where('event_id', $event->id)->first();
        if ($existing) {
            return redirect()->route('member.events.show', $event)
                ->with('error', "Anda sudah pernah mengajukan {$existing->type} untuk kegiatan ini (Status: {$existing->status}).");
        }

        return view('member.leaves.create', compact('event'));
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'type' => ['required', Rule::in(['IZIN', 'SAKIT'])],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'], // max 5MB
        ], [
            'type.required' => 'Pilih jenis pengajuan (Izin atau Sakit).',
            'reason.required' => 'Alasan pengajuan wajib diisi.',
            'reason.min' => 'Jelaskan alasan pengajuan minimal 10 karakter.',
            'attachment.mimes' => 'Format berkas lampiran harus berupa JPG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran berkas lampiran maksimal 5MB.',
        ]);

        try {
            $leave = $this->leaveService->submit(
                user: $user,
                event: $event,
                type: $validated['type'],
                reason: $validated['reason'],
                attachment: $request->file('attachment')
            );

            return redirect()->route('member.events.show', $event)
                ->with('success', "Pengajuan {$leave->type} berhasil dikirim dan menunggu peninjauan administrator.");
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }
}
