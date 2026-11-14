@extends('layouts.member')

@section('title', 'Beranda Anggota')

@section('content')
<div class="space-y-6">

    {{-- Member Greeting Card --}}
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-white/20 text-indigo-100 text-xs font-semibold backdrop-blur-xs mb-2">
                    {{ $user->division ? $user->division->name : 'Anggota Organisasi' }}
                </span>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight leading-tight">Halo, {{ $user->name }}!</h2>
                <p class="text-xs sm:text-sm text-indigo-200 mt-1">Pastikan selalu melakukan presensi tepat waktu di setiap kegiatan.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('member.profile') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs backdrop-blur-xs transition-colors">
                    Edit Profil
                </a>
            </div>
        </div>

        {{-- Mini Stats Bar inside Hero --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-indigo-500/40 text-center">
            <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
                <span class="text-[10px] text-indigo-200 font-bold uppercase block">Hadir</span>
                <span class="text-lg font-black text-white mt-0.5 block">{{ $stats['total_hadir'] }}</span>
            </div>
            <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
                <span class="text-[10px] text-indigo-200 font-bold uppercase block">Terlambat</span>
                <span class="text-lg font-black text-amber-300 mt-0.5 block">{{ $stats['total_terlambat'] }}</span>
            </div>
            <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
                <span class="text-[10px] text-indigo-200 font-bold uppercase block">Izin</span>
                <span class="text-lg font-black text-blue-200 mt-0.5 block">{{ $stats['total_izin'] }}</span>
            </div>
            <div class="bg-white/10 rounded-xl p-3 backdrop-blur-xs">
                <span class="text-[10px] text-indigo-200 font-bold uppercase block">Sakit</span>
                <span class="text-lg font-black text-purple-200 mt-0.5 block">{{ $stats['total_sakit'] }}</span>
            </div>
        </div>
    </div>

    {{-- Active / Today's Events --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Kegiatan Anda</h3>
            <a href="{{ route('member.events.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Semua Kegiatan &rarr;</a>
        </div>

        @if($activeEvents->isEmpty())
            <div class="bg-white rounded-2xl p-6 text-center border border-slate-200 shadow-xs">
                <p class="text-xs text-slate-500">Tidak ada kegiatan aktif atau mendatang saat ini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($activeEvents as $event)
                    @php
                        $attendance = $userAttendances->get($event->id);
                        $session = $event->attendanceSession;
                        $canCheckIn = ! $attendance && $session && $session->status === 'OPEN' && now()->between($session->open_time, $session->close_time);
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="font-mono text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                    {{ $event->code }}
                                </span>
                                <span class="text-xs text-slate-500 font-medium">
                                    📅 {{ $event->event_date->format('d M Y') }} &bull; {{ substr($event->start_time, 0, 5) }} WIB
                                </span>
                            </div>

                            <a href="{{ route('member.events.show', $event) }}" class="font-bold text-slate-900 text-base hover:text-indigo-600 transition-colors block">
                                {{ $event->title }}
                            </a>
                            <p class="text-xs text-slate-500 mt-0.5">📍 {{ $event->location }}</p>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if($attendance)
                                @php
                                    $stBadge = match($attendance->status) {
                                        'HADIR' => 'bg-emerald-100 text-emerald-800',
                                        'TERLAMBAT' => 'bg-amber-100 text-amber-800',
                                        'IZIN' => 'bg-blue-100 text-blue-800',
                                        'SAKIT' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-rose-100 text-rose-800',
                                    };
                                @endphp
                                <span class="px-3 py-1.5 rounded-xl text-xs font-black {{ $stBadge }}">
                                    ✓ {{ $attendance->status }}
                                </span>
                            @elseif($canCheckIn)
                                <a href="{{ route('member.attendance.create', $event) }}"
                                   class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-md shadow-emerald-600/30 flex items-center gap-1.5 transition-all">
                                    📸 Absensi Sekarang
                                </a>
                            @else
                                <a href="{{ route('member.events.show', $event) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                                    Lihat Detail
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Recent Attendances & Recent Leaves --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Recent Attendances --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Riwayat Kehadiran</h4>
                <a href="{{ route('member.attendance.history') }}" class="text-xs font-bold text-indigo-600 hover:underline">Semua</a>
            </div>

            @if($recentAttendances->isEmpty())
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada riwayat absensi.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recentAttendances as $att)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-800 truncate max-w-[180px]">{{ $att->event ? $att->event->title : '-' }}</p>
                                <span class="text-[11px] text-slate-400">{{ $att->check_in_time ? $att->check_in_time->format('d M Y H:i') : '-' }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $att->status === 'HADIR' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $att->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Recent Leaves --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Status Pengajuan Izin/Sakit</h4>
                <a href="{{ route('member.leaves.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Semua</a>
            </div>

            @if($recentLeaves->isEmpty())
                <p class="text-xs text-slate-400 py-6 text-center">Belum pernah mengajukan izin.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recentLeaves as $l)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold {{ $l->type === 'IZIN' ? 'text-blue-600' : 'text-purple-600' }}">{{ $l->type }}</span>
                                <p class="text-xs font-bold text-slate-800 truncate max-w-[180px]">{{ $l->event ? $l->event->title : '-' }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $l->status === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : ($l->status === 'REJECTED' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ $l->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
