<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\User;
use App\Services\SyncOutboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(
        protected SyncOutboxService $outboxService
    ) {}

    public function index(Request $request): View
    {
        $query = User::with('division');

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('username', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->input('division_id'));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $members = $query->latest()->paginate(15)->withQueryString();
        $divisions = Division::orderBy('name')->get();

        return view('admin.members.index', compact('members', 'divisions'));
    }

    public function create(): View
    {
        $divisions = Division::orderBy('name')->get();

        return view('admin.members.create', compact('divisions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['ADMIN', 'MEMBER'])],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'is_active' => ['boolean'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'password.required' => 'Password awal wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active');

        $user = User::create($validated);

        $this->outboxService->record(
            eventType: 'USER_SYNC',
            aggregateType: 'User',
            aggregateId: $user->id,
            payload: [
                'user_id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'role' => $user->role,
            ]
        );

        AuditLog::record(
            action: 'USER_CREATED',
            description: "Pengguna {$user->username} ({$user->name}) ditambahkan dengan peran {$user->role}.",
            entity: $user
        );

        return redirect()->route('admin.members.index')
            ->with('success', "Anggota {$user->name} ({$user->username}) berhasil didaftarkan.");
    }

    public function edit(User $member): View
    {
        $divisions = Division::orderBy('name')->get();

        return view('admin.members.edit', compact('member', 'divisions'));
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username,'.$member->id],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email,'.$member->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['ADMIN', 'MEMBER'])],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $oldValues = $member->only(['username', 'name', 'email', 'phone', 'role', 'division_id', 'is_active']);
        $member->update($validated);

        $this->outboxService->record(
            eventType: 'USER_SYNC',
            aggregateType: 'User',
            aggregateId: $member->id,
            payload: [
                'user_id' => $member->id,
                'username' => $member->username,
                'name' => $member->name,
                'role' => $member->role,
            ]
        );

        AuditLog::record(
            action: 'USER_UPDATED',
            description: "Data pengguna {$member->username} diperbarui.",
            entity: $member,
            oldValues: $oldValues,
            newValues: $validated
        );

        return redirect()->route('admin.members.index')
            ->with('success', "Data anggota {$member->name} berhasil diperbarui.");
    }

    public function toggleStatus(User $member): RedirectResponse
    {
        if ($member->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newStatus = ! $member->is_active;
        $member->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        $this->outboxService->record(
            eventType: 'USER_SYNC',
            aggregateType: 'User',
            aggregateId: $member->id,
            payload: ['is_active' => $newStatus]
        );

        AuditLog::record(
            action: 'USER_STATUS_TOGGLED',
            description: "Status akun {$member->username} {$statusLabel}.",
            entity: $member
        );

        return back()->with('success', "Akun {$member->name} berhasil {$statusLabel}.");
    }

    public function resetPassword(Request $request, User $member): RedirectResponse
    {
        $request->validate([
            'new_password' => ['required', 'string', 'min:8'],
        ], [
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $member->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        AuditLog::record(
            action: 'USER_PASSWORD_RESET',
            description: "Password pengguna {$member->username} telah direset oleh admin ".auth()->user()->username.'.',
            entity: $member
        );

        return back()->with('success', "Password untuk anggota {$member->name} berhasil direset.");
    }

    public function destroy(User $member): RedirectResponse
    {
        if ($member->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $hasAttendances = $member->attendances()->count() > 0;
        if ($hasAttendances) {
            return back()->with('error', "Anggota {$member->name} tidak dapat dihapus karena memiliki riwayat absensi. Silakan nonaktifkan akun saja.");
        }

        $username = $member->username;
        $name = $member->name;
        $member->delete();

        AuditLog::record(
            action: 'USER_DELETED',
            description: "Anggota {$name} ({$username}) telah dihapus dari sistem.",
            entity: null
        );

        return redirect()->route('admin.members.index')
            ->with('success', "Anggota {$name} berhasil dihapus.");
    }
}
