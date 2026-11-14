@extends('layouts.admin')

@section('title', 'Pengajuan Izin & Sakit')
@section('page_title', 'Kelola Pengajuan Izin / Sakit')

@section('content')
<div class="space-y-6">

    {{-- Filter Bar --}}
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.leaves.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <select name="status" class="px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Semua Status --</option>
                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>PENDING (Menunggu Persetujuan)</option>
                <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>APPROVED (Disetujui)</option>
                <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>REJECTED (Ditolak)</option>
            </select>

            <select name="type" class="px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Semua Jenis --</option>
                <option value="IZIN" {{ request('type') === 'IZIN' ? 'selected' : '' }}>IZIN</option>
                <option value="SAKIT" {{ request('type') === 'SAKIT' ? 'selected' : '' }}>SAKIT</option>
            </select>

            <button type="submit" class="py-2 px-4 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['status', 'type']))
                <a href="{{ route('admin.leaves.index') }}" class="py-2 px-3 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200">Reset</a>
            @endif
        </form>
    </div>

    {{-- Leaves Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6">Anggota</th>
                        <th class="py-3.5 px-6">Kegiatan</th>
                        <th class="py-3.5 px-6">Jenis</th>
                        <th class="py-3.5 px-6">Alasan</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                        <th class="py-3.5 px-6 text-center">Lampiran</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($leaves as $leave)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-6">
                                <p class="font-bold text-slate-900 text-xs">{{ $leave->user ? $leave->user->name : '-' }}</p>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $leave->user ? $leave->user->username : '-' }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <p class="font-semibold text-slate-800 text-xs">{{ $leave->event ? $leave->event->title : '-' }}</p>
                                <span class="text-[11px] text-indigo-600 font-mono">{{ $leave->event ? $leave->event->code : '-' }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $leave->type === 'IZIN' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $leave->type }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600 max-w-xs truncate">
                                {{ $leave->reason }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                @php
                                    $stClass = match($leave->status) {
                                        'APPROVED' => 'bg-emerald-100 text-emerald-800',
                                        'REJECTED' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $stClass }}">
                                    {{ $leave->status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($leave->attachment_path)
                                    <a href="{{ route('admin.leaves.attachment', $leave) }}" target="_blank" class="text-xs text-indigo-600 font-bold hover:underline">
                                        📎 Unduh
                                    </a>
                                @else
                                    <span class="text-xs text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <a href="{{ route('admin.leaves.show', $leave) }}" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-bold text-xs">
                                    Tinjau
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                                Tidak ada data pengajuan izin atau sakit yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leaves->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $leaves->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
