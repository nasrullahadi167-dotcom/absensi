@extends('layouts.admin')

@section('title', 'Data Kehadiran')
@section('page_title', 'Data Presensi Kehadiran')

@section('content')
<div class="space-y-6">

    {{-- Filter Bar --}}
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.attendances.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, username, kegiatan..."
                       class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <select name="event_id" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Semua Kegiatan --</option>
                    @foreach($events as $e)
                        <option value="{{ $e->id }}" {{ request('event_id') == $e->id ? 'selected' : '' }}>{{ $e->title }} ({{ $e->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Semua Status --</option>
                    <option value="HADIR" {{ request('status') === 'HADIR' ? 'selected' : '' }}>HADIR</option>
                    <option value="TERLAMBAT" {{ request('status') === 'TERLAMBAT' ? 'selected' : '' }}>TERLAMBAT</option>
                    <option value="IZIN" {{ request('status') === 'IZIN' ? 'selected' : '' }}>IZIN</option>
                    <option value="SAKIT" {{ request('status') === 'SAKIT' ? 'selected' : '' }}>SAKIT</option>
                    <option value="ALPA" {{ request('status') === 'ALPA' ? 'selected' : '' }}>ALPA</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.attendances.index') }}" class="py-2 px-3 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 transition-colors">
                    Reset
                </a>
                <a href="{{ route('admin.attendances.create') }}" class="py-2 px-3.5 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors whitespace-nowrap">
                    + Input Manual
                </a>
            </div>
        </form>
    </div>

    {{-- Attendance Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6">Anggota</th>
                        <th class="py-3.5 px-6">Kegiatan</th>
                        <th class="py-3.5 px-6">Waktu Presensi</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                        <th class="py-3.5 px-6 text-center">Foto Selfie</th>
                        <th class="py-3.5 px-6 text-center">Google Drive</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendances as $att)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900 text-xs">{{ $att->user ? $att->user->name : '-' }}</div>
                                <span class="font-mono text-[11px] text-slate-400">{{ $att->user ? $att->user->username : '-' }} &bull; {{ $att->user && $att->user->division ? $att->user->division->name : 'Tanpa Divisi' }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <p class="font-semibold text-slate-800 text-xs truncate max-w-xs">{{ $att->event ? $att->event->title : '-' }}</p>
                                <span class="font-mono text-[11px] text-indigo-600">{{ $att->event ? $att->event->code : '-' }}</span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600">
                                {{ $att->check_in_time ? $att->check_in_time->format('d/m/Y H:i:s') : '-' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                @php
                                    $stBadge = match($att->status) {
                                        'HADIR' => 'bg-emerald-100 text-emerald-800',
                                        'TERLAMBAT' => 'bg-amber-100 text-amber-800',
                                        'IZIN' => 'bg-blue-100 text-blue-800',
                                        'SAKIT' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-rose-100 text-rose-800',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $stBadge }}">
                                    {{ $att->status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($att->photo_path || $att->drive_file_id)
                                    <a href="{{ route('attendance.photo', $att) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-indigo-600 font-bold hover:underline">
                                        📷 Lihat
                                    </a>
                                @else
                                    <span class="text-xs text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-[11px] font-mono px-2 py-0.5 rounded-full {{ $att->drive_sync_status === 'SYNCED' ? 'bg-emerald-100 text-emerald-800 font-bold' : ($att->drive_sync_status === 'FAILED' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $att->drive_sync_status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.attendances.show', $att) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Detail</a>
                                <a href="{{ route('admin.attendances.edit', $att) }}" class="text-xs font-bold text-slate-600 hover:text-slate-800">Edit</a>
                                <form action="{{ route('admin.attendances.destroy', $att) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus data kehadiran ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                                Tidak ada data presensi yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
