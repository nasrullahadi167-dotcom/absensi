@extends('layouts.admin')

@section('title', 'Tinjau Pengajuan: ' . $leave->type)
@section('page_title', 'Tinjau Pengajuan Izin / Sakit')

@section('content')
<div class="max-w-2xl space-y-6">

    <div>
        <a href="{{ route('admin.leaves.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">&larr; Kembali ke Daftar Pengajuan</a>
    </div>

    {{-- Details Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">

        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $leave->type === 'IZIN' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                    Pengajuan {{ $leave->type }}
                </span>
                <h2 class="text-lg font-extrabold text-slate-900 mt-2">{{ $leave->user ? $leave->user->name : '-' }}</h2>
                <p class="text-xs text-slate-400 font-mono">{{ $leave->user ? $leave->user->username : '-' }} &bull; {{ $leave->user && $leave->user->division ? $leave->user->division->name : 'Tanpa Divisi' }}</p>
            </div>

            <div class="text-right">
                <span class="text-[11px] text-slate-400 block">Status Saat Ini:</span>
                @php
                    $stClass = match($leave->status) {
                        'APPROVED' => 'bg-emerald-100 text-emerald-800',
                        'REJECTED' => 'bg-rose-100 text-rose-800',
                        default => 'bg-amber-100 text-amber-800',
                    };
                @endphp
                <span class="inline-block mt-0.5 px-3 py-1 rounded-full text-xs font-black {{ $stClass }}">
                    {{ $leave->status }}
                </span>
            </div>
        </div>

        <div>
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Kegiatan Terkait</span>
            <p class="font-bold text-slate-900 text-sm">{{ $leave->event ? $leave->event->title : '-' }}</p>
            <p class="text-xs text-slate-500 font-mono">{{ $leave->event ? $leave->event->code : '-' }} &bull; {{ $leave->event ? $leave->event->event_date->format('d M Y') : '-' }}</p>
        </div>

        <div>
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Alasan Pengajuan</span>
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-sm text-slate-800 leading-relaxed">
                {{ $leave->reason }}
            </div>
        </div>

        @if($leave->attachment_path)
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Lampiran Berkas / Surat Dokter</span>
                <a href="{{ route('admin.leaves.attachment', $leave) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition-colors">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                    Buka / Unduh Lampiran Berkas Bukti
                </a>
            </div>
        @endif

        {{-- Review Action Box --}}
        <div class="pt-6 border-t border-slate-100">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Tindakan Keputusan Administrator</h3>

            <form action="{{ route('admin.leaves.review', $leave) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="admin_notes" class="block text-xs font-bold text-slate-700 mb-1">Catatan Administrator</label>
                    <textarea id="admin_notes" name="admin_notes" rows="2" placeholder="Catatan atau alasan persetujuan/penolakan..."
                              class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">{{ old('admin_notes', $leave->admin_notes) }}</textarea>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" name="action" value="APPROVED"
                            class="flex-1 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Setujui (Update Status Kehadiran ke {{ $leave->type }})
                    </button>
                    <button type="submit" name="action" value="REJECTED"
                            class="flex-1 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
@endsection
