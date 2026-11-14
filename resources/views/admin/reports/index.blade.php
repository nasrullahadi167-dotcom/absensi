@extends('layouts.admin')

@section('title', 'Laporan Presensi')
@section('page_title', 'Laporan & Rekapitulasi Presensi')

@section('content')
<div class="space-y-6">

    {{-- Filter Form --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Filter Laporan Presensi</h3>
        <form action="{{ route('admin.reports.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Kegiatan</label>
                <select name="event_id" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                    <option value="">-- Semua Kegiatan --</option>
                    @foreach($events as $e)
                        <option value="{{ $e->id }}" {{ request('event_id') == $e->id ? 'selected' : '' }}>{{ $e->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Divisi</label>
                <select name="division_id" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                    <option value="">-- Semua Divisi --</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}" {{ request('division_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Status Kehadiran</label>
                <select name="status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                    <option value="">-- Semua Status --</option>
                    <option value="HADIR" {{ request('status') === 'HADIR' ? 'selected' : '' }}>HADIR</option>
                    <option value="TERLAMBAT" {{ request('status') === 'TERLAMBAT' ? 'selected' : '' }}>TERLAMBAT</option>
                    <option value="IZIN" {{ request('status') === 'IZIN' ? 'selected' : '' }}>IZIN</option>
                    <option value="SAKIT" {{ request('status') === 'SAKIT' ? 'selected' : '' }}>SAKIT</option>
                    <option value="ALPA" {{ request('status') === 'ALPA' ? 'selected' : '' }}>ALPA</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl">
            </div>

            <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900">
                        Terapkan Filter
                    </button>
                    @if(request()->hasAny(['event_id', 'division_id', 'status', 'start_date', 'end_date', 'search']))
                        <a href="{{ route('admin.reports.index') }}" class="px-3 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200">
                            Reset Filter
                        </a>
                    @endif
                </div>

                {{-- Export Buttons --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.reports.export-excel', request()->all()) }}"
                       class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition-colors">
                        📊 Unduh Excel (CSV)
                    </a>
                    <a href="{{ route('admin.reports.export-pdf', request()->all()) }}" target="_blank"
                       class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs flex items-center gap-1.5 transition-colors">
                        📄 Cetak / PDF
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Summary Cards Based on Filter --}}
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Data</span>
            <p class="text-xl font-extrabold text-slate-800 mt-0.5">{{ number_format($totalRecords) }}</p>
        </div>
        <div class="bg-emerald-50 p-3.5 rounded-xl border border-emerald-100 text-center">
            <span class="text-[10px] font-bold text-emerald-700 uppercase">HADIR</span>
            <p class="text-xl font-extrabold text-emerald-800 mt-0.5">{{ number_format($totalHadir) }}</p>
        </div>
        <div class="bg-amber-50 p-3.5 rounded-xl border border-amber-100 text-center">
            <span class="text-[10px] font-bold text-amber-700 uppercase">TERLAMBAT</span>
            <p class="text-xl font-extrabold text-amber-800 mt-0.5">{{ number_format($totalTerlambat) }}</p>
        </div>
        <div class="bg-blue-50 p-3.5 rounded-xl border border-blue-100 text-center">
            <span class="text-[10px] font-bold text-blue-700 uppercase">IZIN</span>
            <p class="text-xl font-extrabold text-blue-800 mt-0.5">{{ number_format($totalIzin) }}</p>
        </div>
        <div class="bg-purple-50 p-3.5 rounded-xl border border-purple-100 text-center">
            <span class="text-[10px] font-bold text-purple-700 uppercase">SAKIT</span>
            <p class="text-xl font-extrabold text-purple-800 mt-0.5">{{ number_format($totalSakit) }}</p>
        </div>
        <div class="bg-rose-50 p-3.5 rounded-xl border border-rose-100 text-center">
            <span class="text-[10px] font-bold text-rose-700 uppercase">ALPA</span>
            <p class="text-xl font-extrabold text-rose-800 mt-0.5">{{ number_format($totalAlpa) }}</p>
        </div>
    </div>

    {{-- Report Data Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-6">No</th>
                        <th class="py-3 px-6">Anggota</th>
                        <th class="py-3 px-6">Divisi</th>
                        <th class="py-3 px-6">Kegiatan</th>
                        <th class="py-3 px-6">Waktu Presensi</th>
                        <th class="py-3 px-6 text-center">Status</th>
                        <th class="py-3 px-6">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendances as $index => $att)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-6 text-xs text-slate-400 font-mono">{{ $attendances->firstItem() + $index }}</td>
                            <td class="py-3 px-6">
                                <p class="font-bold text-slate-900 text-xs">{{ $att->user ? $att->user->name : '-' }}</p>
                                <span class="text-[11px] font-mono text-slate-400">{{ $att->user ? $att->user->username : '-' }}</span>
                            </td>
                            <td class="py-3 px-6 text-xs text-slate-600">
                                {{ $att->user && $att->user->division ? $att->user->division->name : '-' }}
                            </td>
                            <td class="py-3 px-6 text-xs text-slate-800">
                                <p class="font-semibold">{{ $att->event ? $att->event->title : '-' }}</p>
                                <span class="font-mono text-[11px] text-indigo-600">{{ $att->event ? $att->event->code : '-' }}</span>
                            </td>
                            <td class="py-3 px-6 text-xs text-slate-600">
                                {{ $att->check_in_time ? $att->check_in_time->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="py-3 px-6 text-center">
                                @php
                                    $stClass = match($att->status) {
                                        'HADIR' => 'bg-emerald-100 text-emerald-800',
                                        'TERLAMBAT' => 'bg-amber-100 text-amber-800',
                                        'IZIN' => 'bg-blue-100 text-blue-800',
                                        'SAKIT' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-rose-100 text-rose-800',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $stClass }}">
                                    {{ $att->status }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-xs text-slate-500 max-w-xs truncate">
                                {{ $att->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data presensi yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
