@extends('layouts.admin')

@section('title', 'Data Kegiatan')
@section('page_title', 'Kelola Kegiatan Organisasi')

@section('content')
<div class="space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('admin.events.index') }}" method="GET" class="flex flex-wrap items-center gap-2 max-w-xl w-full">
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari kode, nama, lokasi..."
                       class="w-full pl-10 pr-4 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>

            <select name="status" class="px-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Semua Status --</option>
                <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                <option value="PUBLISHED" {{ request('status') === 'PUBLISHED' ? 'selected' : '' }}>PUBLISHED</option>
                <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
            </select>

            <button type="submit" class="px-3.5 py-2 bg-slate-800 text-white rounded-xl text-sm font-semibold hover:bg-slate-900">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.events.index') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.events.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow-md hover:bg-indigo-700 transition-all whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Buat Kegiatan
        </a>
    </div>

    {{-- Events Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6">Kegiatan</th>
                        <th class="py-3.5 px-6">Jadwal & Waktu</th>
                        <th class="py-3.5 px-6">Lokasi</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                        <th class="py-3.5 px-6 text-center">Sesi Absensi</th>
                        <th class="py-3.5 px-6 text-center">Peserta / Hadir</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($events as $event)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-6">
                                <a href="{{ route('admin.events.show', $event) }}" class="font-bold text-slate-900 hover:text-indigo-600 block">
                                    {{ $event->title }}
                                </a>
                                <span class="font-mono text-xs text-indigo-600 font-semibold">{{ $event->code }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <p class="font-medium text-slate-800 text-xs">{{ $event->event_date->format('d M Y') }}</p>
                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}</p>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600 max-w-xs truncate">{{ $event->location }}</td>
                            <td class="py-4 px-6 text-center">
                                @php
                                    $badgeClasses = match($event->status) {
                                        'PUBLISHED' => 'bg-emerald-100 text-emerald-800',
                                        'COMPLETED' => 'bg-blue-100 text-blue-800',
                                        'CANCELLED' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $badgeClasses }}">
                                    {{ $event->status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($event->attendanceSession)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $event->attendanceSession->status === 'OPEN' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $event->attendanceSession->status }}
                                    </span>
                                @else
                                    <span class="text-xs text-amber-600 font-medium">Belum Dibuat</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-xs font-semibold text-slate-800">{{ $event->participants_count }} Peserta</span>
                                <span class="text-[11px] text-slate-400 block">({{ $event->attendances_count }} Hadir)</span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.events.show', $event) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Detail</a>
                                <a href="{{ route('admin.events.edit', $event) }}" class="text-xs font-bold text-slate-600 hover:text-slate-800">Edit</a>
                                <form action="{{ route('admin.events.destroy', $event) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kegiatan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 text-sm">
                                Tidak ada data kegiatan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($events->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $events->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
