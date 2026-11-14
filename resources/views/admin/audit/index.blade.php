@extends('layouts.admin')

@section('title', 'Audit Log')
@section('page_title', 'Audit Log & Jejak Aktivitas')

@section('content')
<div class="space-y-6">

    {{-- Filter Bar --}}
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.audit.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari deskripsi, aksi, IP..."
                       class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <select name="action" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Semua Aksi --</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'action', 'user_id']))
                    <a href="{{ route('admin.audit.index') }}" class="py-2 px-3 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Audit Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6">Waktu</th>
                        <th class="py-3.5 px-6">Pengguna</th>
                        <th class="py-3.5 px-6">Aksi</th>
                        <th class="py-3.5 px-6">Deskripsi Aktivitas</th>
                        <th class="py-3.5 px-6 font-mono">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3.5 px-6 text-xs text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-3.5 px-6 whitespace-nowrap">
                                <span class="font-bold text-slate-900 text-xs">{{ $log->user ? $log->user->name : 'SYSTEM' }}</span>
                                <span class="font-mono text-[11px] text-slate-400 block">{{ $log->user ? $log->user->username : '-' }}</span>
                            </td>
                            <td class="py-3.5 px-6 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md font-mono text-[11px] font-bold bg-slate-100 text-slate-800">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-700 leading-relaxed">
                                {{ $log->description }}
                            </td>
                            <td class="py-3.5 px-6 font-mono text-xs text-slate-400 whitespace-nowrap">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                Tidak ada catatan audit log yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
