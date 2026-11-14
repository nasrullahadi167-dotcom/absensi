@extends('layouts.admin')

@section('title', 'Data Divisi')
@section('page_title', 'Kelola Divisi Organisasi')

@section('content')
<div class="space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('admin.divisions.index') }}" method="GET" class="flex items-center gap-2 max-w-sm w-full">
            <div class="relative w-full">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari kode atau nama divisi..."
                       class="w-full pl-10 pr-4 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>
            <button type="submit" class="px-3.5 py-2 bg-slate-800 text-white rounded-xl text-sm font-semibold hover:bg-slate-900">Cari</button>
            @if(request('search'))
                <a href="{{ route('admin.divisions.index') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.divisions.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Divisi
        </a>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6">Kode</th>
                        <th class="py-3.5 px-6">Nama Divisi</th>
                        <th class="py-3.5 px-6">Deskripsi</th>
                        <th class="py-3.5 px-6 text-center">Jumlah Anggota</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($divisions as $division)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-6 font-mono font-bold text-indigo-600 text-xs">{{ $division->code }}</td>
                            <td class="py-4 px-6 font-semibold text-slate-900">{{ $division->name }}</td>
                            <td class="py-4 px-6 text-xs text-slate-500 max-w-xs truncate">{{ $division->description ?? '-' }}</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                    {{ $division->users_count }} Anggota
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="{{ route('admin.divisions.edit', $division) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Edit</a>
                                <form action="{{ route('admin.divisions.destroy', $division) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus divisi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-sm">
                                Tidak ada data divisi ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($divisions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $divisions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
