@extends('layouts.admin')

@section('title', 'Buat Kegiatan')
@section('page_title', 'Buat Kegiatan Baru')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="{{ route('admin.events.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Kode Kegiatan</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $suggestedCode) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-mono uppercase @error('code') border-rose-500 @enderror">
                    @error('code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status Kegiatan</label>
                    <select id="status" name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                        <option value="DRAFT" {{ old('status') === 'DRAFT' ? 'selected' : '' }}>DRAFT (Belum Dipublikasi)</option>
                        <option value="PUBLISHED" {{ old('status', 'PUBLISHED') === 'PUBLISHED' ? 'selected' : '' }}>PUBLISHED (Terbuka untuk Peserta)</option>
                        <option value="COMPLETED" {{ old('status') === 'COMPLETED' ? 'selected' : '' }}>COMPLETED (Selesai)</option>
                        <option value="CANCELLED" {{ old('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED (Dibatalkan)</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nama / Judul Kegiatan</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="Contoh: Rapat Kerja Anggota Tahunan 2026"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('title') border-rose-500 @enderror">
                @error('title')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="location" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Lokasi Kegiatan</label>
                <input type="text" id="location" name="location" value="{{ old('location') }}" required placeholder="Contoh: Auditorium Utama Gd. A Lt. 3"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('location') border-rose-500 @enderror">
                @error('location')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="event_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Tanggal Kegiatan</label>
                    <input type="date" id="event_date" name="event_date" value="{{ old('event_date', date('Y-m-d')) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('event_date') border-rose-500 @enderror">
                    @error('event_date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="start_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Waktu Mulai</label>
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '08:00') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('start_time') border-rose-500 @enderror">
                    @error('start_time')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_time" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Waktu Selesai</label>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '12:00') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('end_time') border-rose-500 @enderror">
                    @error('end_time')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Deskripsi & Agenda (Opsional)</label>
                <textarea id="description" name="description" rows="3" placeholder="Informasi rundown dan instruksi untuk peserta..."
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('description') }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('admin.events.index') }}" class="px-4 py-2 rounded-xl text-slate-600 font-semibold text-sm hover:bg-slate-100">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all">
                    Simpan Kegiatan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
