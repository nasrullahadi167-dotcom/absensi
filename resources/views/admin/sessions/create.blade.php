@extends('layouts.admin')

@section('title', 'Buat Sesi Absensi: ' . $event->title)
@section('page_title', 'Buat Sesi Absensi')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <span class="font-mono text-xs text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded">{{ $event->code }}</span>
            <h2 class="text-base font-extrabold text-slate-900 mt-1">{{ $event->title }}</h2>
            <p class="text-xs text-slate-500 mt-1">Konfigurasikan batas waktu buka, tutup, dan toleransi keterlambatan sesi absensi tunggal.</p>
        </div>

        <form action="{{ route('admin.events.session.store', $event) }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Judul Sesi</label>
                <input type="text" id="title" name="title" value="{{ old('title', 'Sesi Presensi Kehadiran') }}" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('title') border-rose-500 @enderror">
                @error('title')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="open_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Waktu Buka Absensi</label>
                    <input type="datetime-local" id="open_time" name="open_time" value="{{ old('open_time', date('Y-m-d\TH:i', strtotime($defaultOpen))) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('open_time') border-rose-500 @enderror">
                    @error('open_time')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="close_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Waktu Tutup Absensi</label>
                    <input type="datetime-local" id="close_time" name="close_time" value="{{ old('close_time', date('Y-m-d\TH:i', strtotime($defaultClose))) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('close_time') border-rose-500 @enderror">
                    @error('close_time')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="late_tolerance_minutes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Toleransi Terlambat (Menit)</label>
                    <input type="number" id="late_tolerance_minutes" name="late_tolerance_minutes" value="{{ old('late_tolerance_minutes', 15) }}" min="0" max="180" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('late_tolerance_minutes') border-rose-500 @enderror">
                    <p class="mt-1 text-[11px] text-slate-400">Lewat batas waktu toleransi ini dicatat sebagai TERLAMBAT.</p>
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status Sesi</label>
                    <select id="status" name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                        <option value="OPEN" {{ old('status', 'OPEN') === 'OPEN' ? 'selected' : '' }}>OPEN (Dibuka)</option>
                        <option value="CLOSED" {{ old('status') === 'CLOSED' ? 'selected' : '' }}>CLOSED (Ditutup)</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('admin.events.show', $event) }}" class="px-4 py-2 rounded-xl text-slate-600 font-semibold text-sm hover:bg-slate-100">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all">
                    Simpan Sesi Absensi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
