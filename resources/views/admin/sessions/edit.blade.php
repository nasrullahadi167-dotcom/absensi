@extends('layouts.admin')

@section('title', 'Edit Sesi Absensi: ' . $event->title)
@section('page_title', 'Ubah Sesi Absensi')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <span class="font-mono text-xs text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded">{{ $event->code }}</span>
            <h2 class="text-base font-extrabold text-slate-900 mt-1">{{ $event->title }}</h2>
        </div>

        <form action="{{ route('admin.events.session.update', [$event, $session]) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Judul Sesi</label>
                <input type="text" id="title" name="title" value="{{ old('title', $session->title) }}" required
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('title') border-rose-500 @enderror">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="open_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Waktu Buka Absensi</label>
                    <input type="datetime-local" id="open_time" name="open_time" value="{{ old('open_time', $session->open_time->format('Y-m-d\TH:i')) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('open_time') border-rose-500 @enderror">
                </div>

                <div>
                    <label for="close_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Waktu Tutup Absensi</label>
                    <input type="datetime-local" id="close_time" name="close_time" value="{{ old('close_time', $session->close_time->format('Y-m-d\TH:i')) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('close_time') border-rose-500 @enderror">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="late_tolerance_minutes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Toleransi Terlambat (Menit)</label>
                    <input type="number" id="late_tolerance_minutes" name="late_tolerance_minutes" value="{{ old('late_tolerance_minutes', $session->late_tolerance_minutes) }}" min="0" max="180" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status Sesi</label>
                    <select id="status" name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                        <option value="OPEN" {{ old('status', $session->status) === 'OPEN' ? 'selected' : '' }}>OPEN (Dibuka)</option>
                        <option value="CLOSED" {{ old('status', $session->status) === 'CLOSED' ? 'selected' : '' }}>CLOSED (Ditutup)</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('admin.events.show', $event) }}" class="px-4 py-2 rounded-xl text-slate-600 font-semibold text-sm hover:bg-slate-100">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
