@extends('layouts.admin')

@section('title', 'Dashboard Administrator')
@section('page_title', 'Ringkasan Dashboard')

@section('content')
<div class="space-y-8">

    {{-- Top Metrics Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Card 1: Anggota --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Anggota</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalMembers) }}</h3>
                <span class="inline-flex items-center text-xs font-medium text-emerald-600 mt-1">
                    {{ $totalActiveMembers }} Aktif
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>

        {{-- Card 2: Kegiatan --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Kegiatan</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalEvents) }}</h3>
                <span class="text-xs text-slate-500 mt-1 block">{{ number_format($totalParticipants) }} Registrasi Peserta</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>

        {{-- Card 3: Total Kehadiran --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Hadir & Tepat Waktu</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($totalHadir) }}</h3>
                <span class="text-xs text-amber-600 font-medium mt-1 block">{{ number_format($totalTerlambat) }} Terlambat</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- Card 4: Google Sheets Sync --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Google Sheets</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($outboxSynced) }} <span class="text-xs font-normal text-slate-500">Tersinkron</span></h3>
                <span class="text-xs {{ $outboxFailed > 0 ? 'text-rose-600 font-bold' : 'text-slate-500' }} mt-1 block">
                    {{ $outboxPending }} Menunggu / {{ $outboxFailed }} Gagal
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Attendance Status Summary Badges --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">Statistik Total Akumulasi Kehadiran</h4>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-center">
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                <span class="text-xs font-bold text-emerald-700 uppercase">HADIR</span>
                <p class="text-2xl font-extrabold text-emerald-800 mt-1">{{ number_format($totalHadir) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-100">
                <span class="text-xs font-bold text-amber-700 uppercase">TERLAMBAT</span>
                <p class="text-2xl font-extrabold text-amber-800 mt-1">{{ number_format($totalTerlambat) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                <span class="text-xs font-bold text-blue-700 uppercase">IZIN</span>
                <p class="text-2xl font-extrabold text-blue-800 mt-1">{{ number_format($totalIzin) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-purple-50 border border-purple-100">
                <span class="text-xs font-bold text-purple-700 uppercase">SAKIT</span>
                <p class="text-2xl font-extrabold text-purple-800 mt-1">{{ number_format($totalSakit) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100">
                <span class="text-xs font-bold text-rose-700 uppercase">ALPA</span>
                <p class="text-2xl font-extrabold text-rose-800 mt-1">{{ number_format($totalAlpa) }}</p>
            </div>
        </div>
    </div>

    {{-- Two Column Section: Recent Events & Recent Audit Activities --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- Left: Recent Events --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <h3 class="font-bold text-slate-900 text-sm">Kegiatan Terbaru</h3>
                    <a href="{{ route('admin.events.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Lihat Semua &rarr;</a>
                </div>

                @if($recentEvents->isEmpty())
                    <p class="text-sm text-slate-500 py-6 text-center">Belum ada kegiatan yang dibuat.</p>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentEvents as $event)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <a href="{{ route('admin.events.show', $event) }}" class="font-semibold text-slate-800 text-sm hover:text-indigo-600 transition-colors">
                                        {{ $event->title }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500">
                                        <span class="font-mono">{{ $event->code }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $event->event_date->format('d M Y') }}</span>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-full {{ $event->status === 'PUBLISHED' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $event->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.events.create') }}" class="w-full py-2.5 px-4 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center gap-2 border border-slate-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Buat Kegiatan Baru
                </a>
            </div>
        </div>

        {{-- Right: Recent Activities (Audit Log) --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <h3 class="font-bold text-slate-900 text-sm">Aktivitas Sistem Terbaru</h3>
                    <a href="{{ route('admin.audit.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Lihat Semua &rarr;</a>
                </div>

                @if($recentActivities->isEmpty())
                    <p class="text-sm text-slate-500 py-6 text-center">Belum ada aktivitas tercatat.</p>
                @else
                    <div class="space-y-3">
                        @foreach($recentActivities as $act)
                            <div class="flex items-start gap-3 text-xs">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 truncate">{{ $act->description }}</p>
                                    <span class="text-[11px] text-slate-400">
                                        {{ $act->user ? $act->user->name : 'Sistem' }} &bull; {{ $act->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Sinkronisasi Terakhir:</span>
                <span class="font-semibold text-slate-700">{{ $lastSync ? $lastSync->synced_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') : 'Belum pernah' }}</span>
            </div>
        </div>

    </div>

</div>
@endsection
