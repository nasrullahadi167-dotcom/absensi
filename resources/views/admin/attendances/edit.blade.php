@extends('layouts.admin')

@section('title', 'Edit Status Kehadiran')
@section('page_title', 'Ubah Status Presensi')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900">{{ $attendance->user ? $attendance->user->name : '-' }}</h2>
            <p class="text-xs text-slate-500">{{ $attendance->event ? $attendance->event->title : '-' }} ({{ $attendance->event ? $attendance->event->code : '-' }})</p>
        </div>

        <form action="{{ route('admin.attendances.update', $attendance) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status Kehadiran</label>
                <select id="status" name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold">
                    <option value="HADIR" {{ old('status', $attendance->status) === 'HADIR' ? 'selected' : '' }}>HADIR</option>
                    <option value="TERLAMBAT" {{ old('status', $attendance->status) === 'TERLAMBAT' ? 'selected' : '' }}>TERLAMBAT</option>
                    <option value="IZIN" {{ old('status', $attendance->status) === 'IZIN' ? 'selected' : '' }}>IZIN</option>
                    <option value="SAKIT" {{ old('status', $attendance->status) === 'SAKIT' ? 'selected' : '' }}>SAKIT</option>
                    <option value="ALPA" {{ old('status', $attendance->status) === 'ALPA' ? 'selected' : '' }}>ALPA</option>
                </select>
            </div>

            <div>
                <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Catatan Perubahan</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('notes', $attendance->notes) }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('admin.attendances.show', $attendance) }}" class="px-4 py-2 rounded-xl text-slate-600 font-semibold text-sm hover:bg-slate-100">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
