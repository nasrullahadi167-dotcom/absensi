@extends('layouts.admin')

@section('title', 'Data Anggota')
@section('page_title', 'Kelola Anggota Organisasi')

@section('content')
<div class="space-y-6" x-data="{ resetModalOpen: false, selectedMember: null, resetPasswordValue: '' }">

    {{-- Filter & Actions Bar --}}
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.members.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari username, nama, email..."
                       class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <select name="division_id" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Semua Divisi --</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}" {{ request('division_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Semua Status --</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.members.index') }}" class="py-2 px-3 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 transition-colors">
                    Reset
                </a>
                <a href="{{ route('admin.members.create') }}" class="py-2 px-3.5 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors whitespace-nowrap">
                    + Anggota
                </a>
            </div>
        </form>
    </div>

    {{-- Members Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6">Anggota</th>
                        <th class="py-3.5 px-6">Username</th>
                        <th class="py-3.5 px-6">Divisi</th>
                        <th class="py-3.5 px-6">Peran</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $member)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 text-sm leading-tight">{{ $member->name }}</p>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ $member->phone ?? $member->email ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs text-indigo-600 font-bold">{{ $member->username }}</td>
                            <td class="py-4 px-6 text-xs text-slate-600 font-medium">
                                {{ $member->division ? $member->division->name : '-' }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $member->role === 'ADMIN' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $member->role }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($member->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="{{ route('admin.members.edit', $member) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Edit</a>

                                <button type="button"
                                        @click="selectedMember = { id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', username: '{{ $member->username }}' }; resetModalOpen = true"
                                        class="text-xs font-bold text-amber-600 hover:text-amber-800">
                                    Reset Pass
                                </button>

                                @if($member->id !== auth()->id())
                                    <form action="{{ route('admin.members.toggle-status', $member) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-xs font-bold {{ $member->is_active ? 'text-slate-500 hover:text-slate-700' : 'text-emerald-600 hover:text-emerald-800' }}">
                                            {{ $member->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.members.destroy', $member) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus anggota ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 text-sm">
                                Tidak ada data anggota yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    {{-- Reset Password Modal --}}
    <div x-show="resetModalOpen"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6" @click.away="resetModalOpen = false">
            <h3 class="text-base font-bold text-slate-900 mb-2">Reset Password Anggota</h3>
            <p class="text-xs text-slate-500 mb-4">
                Atur password baru untuk anggota <span class="font-bold text-slate-800" x-text="selectedMember?.name"></span> (<span class="font-mono text-indigo-600" x-text="selectedMember?.username"></span>).
            </p>

            <form :action="selectedMember ? `/admin/members/${selectedMember.id}/reset-password` : '#'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Password Baru</label>
                    <input type="password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="resetModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 text-xs font-bold hover:bg-slate-100">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 shadow-md">
                        Konfirmasi Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
