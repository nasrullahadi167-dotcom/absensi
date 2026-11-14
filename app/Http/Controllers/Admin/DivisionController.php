<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Division;
use App\Services\SyncOutboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function __construct(
        protected SyncOutboxService $outboxService
    ) {}

    public function index(Request $request): View
    {
        $query = Division::withCount('users');

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search);
            });
        }

        $divisions = $query->latest()->paginate(10)->withQueryString();

        return view('admin.divisions.index', compact('divisions'));
    }

    public function create(): View
    {
        return view('admin.divisions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:divisions,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'code.required' => 'Kode divisi wajib diisi.',
            'code.unique' => 'Kode divisi sudah digunakan.',
            'name.required' => 'Nama divisi wajib diisi.',
        ]);

        $division = Division::create($validated);

        $this->outboxService->record(
            eventType: 'DIVISION_SYNC',
            aggregateType: 'Division',
            aggregateId: $division->id,
            payload: $division->toArray()
        );

        AuditLog::record(
            action: 'DIVISION_CREATED',
            description: "Divisi {$division->name} ({$division->code}) dibuat.",
            entity: $division,
            newValues: $division->toArray()
        );

        return redirect()->route('admin.divisions.index')
            ->with('success', "Divisi '{$division->name}' berhasil ditambahkan.");
    }

    public function edit(Division $division): View
    {
        return view('admin.divisions.edit', compact('division'));
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:divisions,code,'.$division->id],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = $division->toArray();
        $division->update($validated);

        $this->outboxService->record(
            eventType: 'DIVISION_SYNC',
            aggregateType: 'Division',
            aggregateId: $division->id,
            payload: $division->toArray()
        );

        AuditLog::record(
            action: 'DIVISION_UPDATED',
            description: "Divisi {$division->name} ({$division->code}) diperbarui.",
            entity: $division,
            oldValues: $oldValues,
            newValues: $division->toArray()
        );

        return redirect()->route('admin.divisions.index')
            ->with('success', "Divisi '{$division->name}' berhasil diperbarui.");
    }

    public function destroy(Division $division): RedirectResponse
    {
        $usersCount = $division->users()->count();
        if ($usersCount > 0) {
            return back()->with('error', "Divisi tidak dapat dihapus karena masih memiliki {$usersCount} anggota.");
        }

        $oldValues = $division->toArray();
        $name = $division->name;
        $division->delete();

        AuditLog::record(
            action: 'DIVISION_DELETED',
            description: "Divisi {$name} telah dihapus.",
            entity: null,
            oldValues: $oldValues
        );

        return redirect()->route('admin.divisions.index')
            ->with('success', "Divisi '{$name}' berhasil dihapus.");
    }
}
