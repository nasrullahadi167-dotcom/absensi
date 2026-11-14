@extends('layouts.admin')

@section('title', 'Kelola Peserta: ' . $event->title)
@section('page_title', 'Peserta Kegiatan')

@section('content')
<div class="space-y-6">

    {{-- Header Banner --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.events.show', $event) }}" class="text-xs text-indigo-600 font-bold hover:underline">&larr; Kembali ke Detail Kegiatan</a>
            </div>
            <h1 class="text-xl font-extrabold text-slate-900">{{ $event->title }}</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar anggota yang terdaftar dan diwajibkan mengikuti presensi kegiatan ini.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 font-bold text-xs">
                Total {{ $participants->total() }} Peserta Terdaftar
            </span>
        </div>
    </div>

    {{-- Enrollment Forms Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Add Single Member Form --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Daftarkan Anggota Tunggal</h3>
            <form action="{{ route('admin.events.participants.store', $event) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <select name="user_id" required class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Pilih Anggota --</option>
                        @foreach($availableUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->username }}) - {{ $u->division ? $u->division->name : 'Tanpa Divisi' }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700 transition-colors">
                    + Daftarkan Anggota Ini
                </button>
            </form>
        </div>

        {{-- Batch Enrollment Form --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs" x-data="{ mode: 'division' }">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Daftarkan Massal (Batch Enroll)</h3>
            <form action="{{ route('admin.events.participants.batch', $event) }}" method="POST" class="space-y-3">
                @csrf

                <div class="flex items-center gap-4 text-xs font-semibold text-slate-700 mb-2">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="enroll_mode" value="division" x-model="mode" class="text-indigo-600">
                        Berdasarkan Divisi
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="enroll_mode" value="all" x-model="mode" class="text-indigo-600">
                        Seluruh Anggota Aktif
                    </label>
                </div>

                <div x-show="mode === 'division'">
                    <select name="division_id" :required="mode === 'division'" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Pilih Divisi --</option>
                        @foreach($divisions as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-slate-800 text-white font-bold text-xs hover:bg-slate-900 transition-colors">
                    ⚡ Jalankan Pendaftaran Massal
                </button>
            </form>
        </div>

    </div>

    {{-- Participants Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="font-bold text-slate-900 text-sm">Daftar Peserta Terdaftar</h3>

            <form action="{{ route('admin.events.participants.index', $event) }}" method="GET" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/username..."
                       class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold">Cari</button>
                @if(request('search'))
                    <a href="{{ route('admin.events.participants.index', $event) }}" class="text-xs text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-6">Anggota</th>
                        <th class="py-3 px-6">Username</th>
                        <th class="py-3 px-6">Divisi</th>
                        <th class="py-3 px-6">Waktu Terdaftar</th>
                        <th class="py-3 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($participants as $part)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3.5 px-6 font-bold text-slate-900 text-xs">
                                {{ $part->user ? $part->user->name : '-' }}
                            </td>
                            <td class="py-3.5 px-6 font-mono text-xs text-indigo-600 font-semibold">
                                {{ $part->user ? $part->user->username : '-' }}
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-600">
                                {{ $part->user && $part->user->division ? $part->user->division->name : '-' }}
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-400">
                                {{ $part->registered_at->format('d M Y H:i') }}
                            </td>
                            <td class="py-3.5 px-6 text-right">
                                <form action="{{ route('admin.events.participants.destroy', [$event, $part]) }}" method="POST" class="inline-block" onsubmit="return confirm('Keluarkan peserta ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Keluarkan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                Belum ada peserta terdaftar dalam kegiatan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($participants->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $participants->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
