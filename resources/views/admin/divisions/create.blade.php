@extends('layouts.admin')

@section('title', 'Tambah Divisi')
@section('page_title', 'Tambah Divisi Baru')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="{{ route('admin.divisions.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Kode Divisi</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" required placeholder="Contoh: DIV-IT"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-mono uppercase @error('code') border-rose-500 @enderror">
                @error('code')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nama Divisi</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Divisi Teknologi Informasi"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm @error('name') border-rose-500 @enderror">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" rows="3" placeholder="Tugas dan fungsi divisi..."
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('description') }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="{{ route('admin.divisions.index') }}" class="px-4 py-2 rounded-xl text-slate-600 font-semibold text-sm hover:bg-slate-100">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all">
                    Simpan Divisi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
