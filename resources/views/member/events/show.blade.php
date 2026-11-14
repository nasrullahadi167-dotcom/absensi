@extends('layouts.member')

@section('title', 'Detail Kegiatan: ' . $event->title)

@section('content')
<div class="space-y-6">

    <div>
        <a href="{{ route('member.events.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">&larr; Kembali ke Daftar Kegiatan</a>
    </div>

    {{-- Event Banner Card --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-md">{{ $event->code }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $event->status === 'PUBLISHED' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                    {{ $event->status }}
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">{{ $event->title }}</h1>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs">
            <div>
                <span class="text-slate-400 block font-medium">Tanggal</span>
                <span class="font-bold text-slate-800 mt-0.5 block">📅 {{ $event->event_date->format('d F Y') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Waktu Pelaksanaan</span>
                <span class="font-bold text-slate-800 mt-0.5 block">⏰ {{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }} WIB</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium">Lokasi Kegiatan</span>
                <span class="font-bold text-slate-800 mt-0.5 block">📍 {{ $event->location }}</span>
            </div>
        </div>

        @if($event->description)
            <div>
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Deskripsi & Agenda</h4>
                <div class="text-xs sm:text-sm text-slate-600 leading-relaxed bg-slate-50/50 p-4 rounded-xl border border-slate-100 whitespace-pre-line">
                    {{ $event->description }}
                </div>
            </div>
        @endif

        {{-- Attendance Action / Status Box --}}
        <div class="pt-6 border-t border-slate-100">

            @if($existingAttendance)
                {{-- Already Checked In --}}
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h4 class="text-sm font-extrabold text-emerald-950">Presensi Berhasil Dicatat</h4>
                        </div>
                        <p class="text-xs text-emerald-800 mt-1">
                            Status: <strong class="uppercase font-black text-emerald-900">{{ $existingAttendance->status }}</strong> &bull; Waktu: {{ $existingAttendance->check_in_time->format('d/m/Y H:i:s') }} WIB
                        </p>
                    </div>

                    @if($existingAttendance->photo_path || $existingAttendance->drive_file_id)
                        <a href="{{ route('attendance.photo', $existingAttendance) }}" target="_blank"
                           class="px-4 py-2 rounded-xl bg-white text-emerald-800 font-bold text-xs shadow-xs border border-emerald-200 hover:bg-emerald-100 transition-colors inline-flex items-center gap-1.5 justify-center">
                            📷 Lihat Foto Selfie Presensi
                        </a>
                    @endif
                </div>

            @elseif($existingLeave)
                {{-- Already submitted leave --}}
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <h4 class="text-sm font-extrabold text-amber-950">Pengajuan {{ $existingLeave->type }} Terkirim</h4>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black {{ $existingLeave->status === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : ($existingLeave->status === 'REJECTED' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                            {{ $existingLeave->status }}
                        </span>
                    </div>
                    <p class="text-xs text-amber-800 mt-2">Alasan: {{ $existingLeave->reason }}</p>
                    @if($existingLeave->admin_notes)
                        <p class="text-xs text-slate-600 mt-1 pt-2 border-t border-amber-200">
                            <strong>Catatan Admin:</strong> {{ $existingLeave->admin_notes }}
                        </p>
                    @endif
                </div>

            @else
                {{-- Not yet attended --}}
                @if($eligibility['eligible'])
                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center gap-2 text-indigo-900 font-bold text-sm">
                            <span>🕒 Sesi Presensi Sedang Dibuka</span>
                        </div>
                        <p class="text-xs text-indigo-800 leading-relaxed">
                            Ambil foto selfie di tempat kegiatan berlangsung untuk menyelesaikan presensi Anda sekarang.
                        </p>

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <a href="{{ route('member.attendance.create', $event) }}"
                               class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 transition-all">
                                📸 Buka Kamera Selfie Presensi
                            </a>

                            <a href="{{ route('member.leaves.create', $event) }}"
                               class="w-full sm:w-auto px-4 py-3 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200 text-center transition-all">
                                Ajukan Izin / Sakit
                            </a>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-bold text-slate-600 block">Status Presensi:</span>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $eligibility['message'] }}</p>
                        </div>

                        <a href="{{ route('member.leaves.create', $event) }}"
                           class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200 text-center transition-all">
                            Ajukan Izin / Sakit
                        </a>
                    </div>
                @endif

            @endif

        </div>

    </div>

</div>
@endsection
