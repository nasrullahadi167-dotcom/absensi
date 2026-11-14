@extends('layouts.admin')

@section('title', 'Detail Kehadiran #' . $attendance->id)
@section('page_title', 'Detail Presensi')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.attendances.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">&larr; Kembali ke Daftar Kehadiran</a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.attendances.edit', $attendance) }}" class="px-3 py-1.5 rounded-xl bg-slate-800 text-white font-bold text-xs hover:bg-slate-900">
                Edit Status
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">

            {{-- Selfie Photo Column --}}
            <div>
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Foto Presensi Selfie</h3>
                @if($attendance->photo_path || $attendance->drive_file_id)
                    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 shadow-inner">
                        <img src="{{ route('attendance.photo', $attendance) }}"
                             alt="Selfie Presensi"
                             class="w-full h-80 object-cover">
                    </div>
                    <div class="mt-2 text-right">
                        <a href="{{ route('attendance.photo', $attendance) }}" target="_blank" class="text-xs text-indigo-600 font-bold hover:underline">
                            Buka Foto Ukuran Penuh &nearr;
                        </a>
                    </div>
                @else
                    <div class="w-full h-64 rounded-2xl bg-slate-100 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Tidak ada file foto (Input Manual / Izin)
                    </div>
                @endif
            </div>

            {{-- Metadata Details --}}
            <div class="space-y-4">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Anggota</span>
                    <p class="font-extrabold text-slate-900 text-base leading-tight">{{ $attendance->user ? $attendance->user->name : '-' }}</p>
                    <p class="font-mono text-xs text-indigo-600 mt-0.5">{{ $attendance->user ? $attendance->user->username : '-' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $attendance->user && $attendance->user->division ? $attendance->user->division->name : 'Tanpa Divisi' }}</p>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Kegiatan</span>
                    <p class="font-bold text-slate-800 text-sm">{{ $attendance->event ? $attendance->event->title : '-' }}</p>
                    <p class="font-mono text-xs text-slate-500">{{ $attendance->event ? $attendance->event->code : '-' }}</p>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Status Kehadiran</span>
                    <div class="mt-1">
                        @php
                            $stBadge = match($attendance->status) {
                                'HADIR' => 'bg-emerald-100 text-emerald-800',
                                'TERLAMBAT' => 'bg-amber-100 text-amber-800',
                                'IZIN' => 'bg-blue-100 text-blue-800',
                                'SAKIT' => 'bg-purple-100 text-purple-800',
                                default => 'bg-rose-100 text-rose-800',
                            };
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-black {{ $stBadge }}">
                            {{ $attendance->status }}
                        </span>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Waktu Check-In</span>
                    <p class="text-xs font-semibold text-slate-800 mt-0.5">
                        {{ $attendance->check_in_time ? $attendance->check_in_time->setTimezone('Asia/Jakarta')->format('d F Y - H:i:s') . ' WIB' : '-' }}
                    </p>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Catatan</span>
                    <p class="text-xs text-slate-600 mt-0.5">{{ $attendance->notes ?: '-' }}</p>
                </div>

                {{-- Google Drive Sync Box --}}
                <div class="pt-4 border-t border-slate-100 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-bold text-slate-700">Integrasi Google Drive</span>
                        <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-full {{ $attendance->drive_sync_status === 'SYNCED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $attendance->drive_sync_status }}
                        </span>
                    </div>

                    @if($attendance->drive_file_id)
                        <p class="text-[11px] text-slate-500 font-mono truncate">ID: {{ $attendance->drive_file_id }}</p>
                    @endif

                    @if($attendance->drive_sync_error)
                        <p class="text-[11px] text-rose-600 mt-1 font-medium">Error: {{ $attendance->drive_sync_error }}</p>
                    @endif

                    @if($attendance->drive_sync_status !== 'SYNCED' && $attendance->photo_path)
                        <form action="{{ route('admin.attendances.retry-drive', $attendance) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="w-full py-1.5 px-3 rounded-lg bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700">
                                ↻ Jadwalkan Unggah Ulang ke Google Drive
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Device/Client Footprint --}}
                <div class="text-[11px] text-slate-400 space-y-0.5 pt-2">
                    <p>IP Address: <span class="font-mono">{{ $attendance->ip_address ?? '-' }}</span></p>
                    <p class="truncate" title="{{ $attendance->user_agent }}">Perangkat: {{ $attendance->user_agent ?? '-' }}</p>
                </div>

            </div>

        </div>
    </div>

</div>
@endsection
