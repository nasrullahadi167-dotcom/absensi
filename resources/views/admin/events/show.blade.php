@extends('layouts.admin')

@section('title', 'Detail Kegiatan: ' . $event->title)
@section('page_title', 'Detail Kegiatan')

@section('content')
<div class="space-y-6">

    {{-- Header Banner --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md">{{ $event->code }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $event->status === 'PUBLISHED' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                    {{ $event->status }}
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900">{{ $event->title }}</h1>
            <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                <span>📍 {{ $event->location }}</span>
                <span>📅 {{ $event->event_date->format('d M Y') }}</span>
                <span>⏰ {{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }} WIB</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.events.participants.index', $event) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5">
                👥 Kelola Peserta ({{ $totalParticipants }})
            </a>
            <a href="{{ route('admin.events.edit', $event) }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs">
                Edit Kegiatan
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white p-4 rounded-xl border border-slate-200 text-center">
            <span class="text-[11px] font-bold text-slate-400 uppercase">Peserta</span>
            <p class="text-xl font-black text-slate-800 mt-0.5">{{ $totalParticipants }}</p>
        </div>
        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100 text-center">
            <span class="text-[11px] font-bold text-emerald-700 uppercase">Hadir</span>
            <p class="text-xl font-black text-emerald-800 mt-0.5">{{ $totalHadir }}</p>
        </div>
        <div class="bg-amber-50 p-4 rounded-xl border border-amber-100 text-center">
            <span class="text-[11px] font-bold text-amber-700 uppercase">Terlambat</span>
            <p class="text-xl font-black text-amber-800 mt-0.5">{{ $totalTerlambat }}</p>
        </div>
        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 text-center">
            <span class="text-[11px] font-bold text-blue-700 uppercase">Izin</span>
            <p class="text-xl font-black text-blue-800 mt-0.5">{{ $totalIzin }}</p>
        </div>
        <div class="bg-purple-50 p-4 rounded-xl border border-purple-100 text-center">
            <span class="text-[11px] font-bold text-purple-700 uppercase">Sakit</span>
            <p class="text-xl font-black text-purple-800 mt-0.5">{{ $totalSakit }}</p>
        </div>
    </div>

    {{-- Attendance Session Section (Single Session Per Event Enforcement) --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                    🕒
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Sesi Absensi Kegiatan</h3>
                    <p class="text-xs text-slate-500">Setiap kegiatan hanya memiliki satu sesi absensi utama.</p>
                </div>
            </div>

            @if($event->attendanceSession)
                <div class="flex items-center gap-2">
                    <form action="{{ route('admin.events.session.toggle', [$event, $event->attendanceSession]) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $event->attendanceSession->status === 'OPEN' ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}">
                            {{ $event->attendanceSession->status === 'OPEN' ? 'Tutup Sesi' : 'Buka Sesi' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.events.session.edit', [$event, $event->attendanceSession]) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200">
                        Edit Sesi
                    </a>
                </div>
            @else
                <a href="{{ route('admin.events.session.create', $event) }}" class="px-3.5 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-md hover:bg-indigo-700">
                    + Buat Sesi Absensi
                </a>
            @endif
        </div>

        @if($event->attendanceSession)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs">
                <div>
                    <span class="text-slate-400 block">Status Sesi:</span>
                    <span class="font-bold {{ $event->attendanceSession->status === 'OPEN' ? 'text-emerald-700' : 'text-slate-700' }} uppercase text-sm">
                        {{ $event->attendanceSession->status }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block">Waktu Buka:</span>
                    <span class="font-semibold text-slate-800">
                        {{ $event->attendanceSession->open_time->format('d M Y H:i') }} WIB
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block">Waktu Tutup:</span>
                    <span class="font-semibold text-slate-800">
                        {{ $event->attendanceSession->close_time->format('d M Y H:i') }} WIB
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block">Toleransi Keterlambatan:</span>
                    <span class="font-semibold text-slate-800">
                        {{ $event->attendanceSession->late_tolerance_minutes }} Menit
                    </span>
                </div>
            </div>
        @else
            <div class="py-6 text-center text-slate-400 text-xs bg-slate-50 rounded-xl border border-dashed border-slate-200">
                Sesi absensi belum dibuat untuk kegiatan ini. Klik tombol di atas untuk membuat sesi tunggal.
            </div>
        @endif
    </div>

    {{-- Attendance Records in This Event --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Daftar Kehadiran Anggota</h3>
            <span class="text-xs text-slate-500">{{ $event->attendances->count() }} Data Tercatat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-6">Anggota</th>
                        <th class="py-3 px-6">Waktu Presensi</th>
                        <th class="py-3 px-6 text-center">Status</th>
                        <th class="py-3 px-6 text-center">Foto Selfie</th>
                        <th class="py-3 px-6 text-center">Drive Sync</th>
                        <th class="py-3 px-6 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($event->attendances as $att)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-6">
                                <p class="font-bold text-slate-900 text-xs">{{ $att->user ? $att->user->name : '-' }}</p>
                                <span class="font-mono text-[11px] text-slate-400">{{ $att->user ? $att->user->username : '-' }}</span>
                            </td>
                            <td class="py-3 px-6 text-xs text-slate-600">
                                {{ $att->check_in_time ? $att->check_in_time->format('d M Y H:i:s') : '-' }}
                            </td>
                            <td class="py-3 px-6 text-center">
                                @php
                                    $stBadge = match($att->status) {
                                        'HADIR' => 'bg-emerald-100 text-emerald-800',
                                        'TERLAMBAT' => 'bg-amber-100 text-amber-800',
                                        'IZIN' => 'bg-blue-100 text-blue-800',
                                        'SAKIT' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-rose-100 text-rose-800',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $stBadge }}">
                                    {{ $att->status }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-center">
                                @if($att->photo_path || $att->drive_file_id)
                                    <a href="{{ route('attendance.photo', $att) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-indigo-600 font-bold hover:underline">
                                        📷 Lihat Foto
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-6 text-center">
                                <span class="text-[11px] font-mono px-2 py-0.5 rounded-full {{ $att->drive_sync_status === 'SYNCED' ? 'bg-emerald-100 text-emerald-800 font-bold' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $att->drive_sync_status }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-right">
                                <a href="{{ route('admin.attendances.show', $att) }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                Belum ada kehadiran yang tercatat untuk kegiatan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
