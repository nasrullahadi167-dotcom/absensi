@extends('layouts.admin')

@section('title', 'Input Kehadiran Manual')
@section('page_title', 'Catat Kehadiran Manual')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="{{ route('admin.attendances.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="event_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Kegiatan</label>
                <select id="event_id" name="event_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">
                    <option value="">-- Pilih Kegiatan --</option>
                    @foreach($events as $e)
                        <option value="{{ $e->id }}" {{ old('event_id') == $e->id ? 'selected' : '' }}>{{ $e->title }} ({{ $e->code }})</option>
                    @endforeach
                </select>
                @error('event_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="user_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Anggota</label>
                <select id="user_id" name="user_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">
                    <option value="">-- Pilih Anggota --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->username }})</option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status Kehadiran</label>
                    <select id="status" name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold">
                        <option value="HADIR">HADIR</option>
                        <option value="TERLAMBAT">TERLAMBAT</option>
                        <option value="IZIN">IZIN</option>
                        <option value="SAKIT">SAKIT</option>
                        <option value="ALPA">ALPA</option>
                    </select>
                </div>

                <div>
                    <label for="check_in_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Waktu Presensi</label>
                    <input type="datetime-local" id="check_in_time" name="check_in_time" value="{{ old('check_in_time', date('Y-m-d\TH:i')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Catatan Tambahan (Opsional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Alasan pencatatan manual..."
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('admin.attendances.index') }}" class="px-4 py-2 rounded-xl text-slate-600 font-semibold text-sm hover:bg-slate-100">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all">
                    Simpan Kehadiran
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
