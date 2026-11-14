@extends('layouts.member')

@section('title', 'Daftar Kegiatan')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-black text-slate-900">Kegiatan Terdaftar</h2>
            <p class="text-xs text-slate-500">Seluruh kegiatan organisasi di mana Anda terdaftar sebagai peserta.</p>
        </div>
    </div>

    {{-- Search Bar --}}
    <form action="{{ route('member.events.index') }}" method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kegiatan atau lokasi..."
               class="flex-1 px-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs focus:ring-2 focus:ring-indigo-500 shadow-xs">
        <button type="submit" class="px-4 py-2.5 bg-slate-800 text-white rounded-2xl text-xs font-bold hover:bg-slate-900">
            Cari
        </button>
    </form>

    {{-- Events List --}}
    <div class="space-y-4">
        @forelse($events as $event)
            @php
                $attendance = $userAttendances->get($event->id);
                $session = $event->attendanceSession;
                $canCheckIn = ! $attendance && $session && $session->status === 'OPEN' && now()->between($session->open_time, $session->close_time);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 hover:border-indigo-200 transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="font-mono text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                {{ $event->code }}
                            </span>
                            <span class="text-xs text-slate-400">
                                📅 {{ $event->event_date->format('d M Y') }} &bull; {{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }} WIB
                            </span>
                        </div>

                        <a href="{{ route('member.events.show', $event) }}" class="font-extrabold text-slate-900 text-base hover:text-indigo-600 transition-colors block">
                            {{ $event->title }}
                        </a>
                        <p class="text-xs text-slate-500 mt-1">📍 {{ $event->location }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($attendance)
                            <span class="px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-100 text-emerald-800">
                                ✓ {{ $attendance->status }}
                            </span>
                        @elseif($canCheckIn)
                            <a href="{{ route('member.attendance.create', $event) }}" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-md shadow-emerald-600/30 hover:bg-emerald-700">
                                📸 Absensi Sekarang
                            </a>
                        @endif

                        <a href="{{ route('member.events.show', $event) }}" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                            Detail &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center border border-slate-200 text-slate-400 text-xs">
                Tidak ada kegiatan yang ditemukan.
            </div>
        @endforelse

        @if($events->hasPages())
            <div class="pt-4">
                {{ $events->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
