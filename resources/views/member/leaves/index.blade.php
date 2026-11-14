@extends('layouts.member')

@section('title', 'Riwayat Pengajuan Izin')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-black text-slate-900">Pengajuan Izin & Sakit</h2>
            <p class="text-xs text-slate-500">Daftar permohonan ketidakhadiran yang Anda ajukan.</p>
        </div>
    </div>

    {{-- Leaves List --}}
    <div class="space-y-4">
        @forelse($leaves as $leave)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $leave->type === 'IZIN' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                            {{ $leave->type }}
                        </span>
                        <span class="text-xs text-slate-400 font-mono">{{ $leave->created_at->format('d M Y H:i') }}</span>
                    </div>

                    @php
                        $stBadge = match($leave->status) {
                            'APPROVED' => 'bg-emerald-100 text-emerald-800',
                            'REJECTED' => 'bg-rose-100 text-rose-800',
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black {{ $stBadge }}">
                        {{ $leave->status }}
                    </span>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $leave->event ? $leave->event->title : '-' }}</h4>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100">
                        {{ $leave->reason }}
                    </p>
                </div>

                @if($leave->admin_notes)
                    <div class="text-xs text-slate-700 bg-amber-50/50 p-2.5 rounded-xl border border-amber-200/50">
                        <span class="font-bold text-amber-900">Catatan Admin:</span> {{ $leave->admin_notes }}
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center border border-slate-200 text-slate-400 text-xs">
                Belum pernah ada pengajuan izin atau sakit.
            </div>
        @endforelse

        @if($leaves->hasPages())
            <div class="pt-4">
                {{ $leaves->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
