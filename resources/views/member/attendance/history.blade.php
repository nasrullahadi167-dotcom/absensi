@extends('layouts.member')

@section('title', 'Riwayat Kehadiran')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-black text-slate-900">Riwayat Kehadiran</h2>
            <p class="text-xs text-slate-500">Catatan lengkap presensi kehadiran yang telah Anda lakukan.</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <form action="{{ route('member.attendance.history') }}" method="GET" class="flex flex-wrap gap-2">
        <select name="status" class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 shadow-xs">
            <option value="">-- Semua Status --</option>
            <option value="HADIR" {{ request('status') === 'HADIR' ? 'selected' : '' }}>HADIR</option>
            <option value="TERLAMBAT" {{ request('status') === 'TERLAMBAT' ? 'selected' : '' }}>TERLAMBAT</option>
            <option value="IZIN" {{ request('status') === 'IZIN' ? 'selected' : '' }}>IZIN</option>
            <option value="SAKIT" {{ request('status') === 'SAKIT' ? 'selected' : '' }}>SAKIT</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900">
            Filter
        </button>
        @if(request('status'))
            <a href="{{ route('member.attendance.history') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800">Reset</a>
        @endif
    </form>

    {{-- Attendance List --}}
    <div class="space-y-3">
        @forelse($attendances as $att)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    @if($att->photo_path || $att->drive_file_id)
                        <img src="{{ route('attendance.photo', $att) }}" alt="Selfie" class="w-14 h-14 rounded-xl object-cover border border-slate-200 shadow-xs flex-shrink-0">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            {{ $att->status }}
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                {{ $att->event ? $att->event->code : '-' }}
                            </span>
                            @php
                                $stBadge = match($att->status) {
                                    'HADIR' => 'bg-emerald-100 text-emerald-800',
                                    'TERLAMBAT' => 'bg-amber-100 text-amber-800',
                                    'IZIN' => 'bg-blue-100 text-blue-800',
                                    'SAKIT' => 'bg-purple-100 text-purple-800',
                                    default => 'bg-rose-100 text-rose-800',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $stBadge }}">
                                {{ $att->status }}
                            </span>
                        </div>

                        <h4 class="font-bold text-slate-900 text-sm">{{ $att->event ? $att->event->title : '-' }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            ⏰ {{ $att->check_in_time ? $att->check_in_time->setTimezone('Asia/Jakarta')->format('d M Y - H:i:s') . ' WIB' : '-' }}
                        </p>
                        @if($att->notes)
                            <p class="text-[11px] text-slate-400 italic mt-1">"{{ $att->notes }}"</p>
                        @endif
                    </div>
                </div>

                @if($att->photo_path || $att->drive_file_id)
                    <div>
                        <a href="{{ route('attendance.photo', $att) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                            🔍 Lihat Foto
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center border border-slate-200 text-slate-400 text-xs">
                Belum ada catatan riwayat presensi.
            </div>
        @endforelse

        @if($attendances->hasPages())
            <div class="pt-4">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
