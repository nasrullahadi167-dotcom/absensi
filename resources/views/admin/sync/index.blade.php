@extends('layouts.admin')

@section('title', 'Monitoring Sinkronisasi Google')
@section('page_title', 'Google Sheets & Drive Sync Monitor')

@section('content')
<div class="space-y-6" x-data="{ errorModalOpen: false, errorDetail: '' }">

    {{-- Connection Status Banner --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl {{ $isConfigured ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center text-xl font-bold flex-shrink-0">
                {{ $isConfigured ? '✓' : '!' }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-slate-900 text-base">Google Sheets & Drive API Integration</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isConfigured ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $isConfigured ? 'TERHUBUNG' : 'KREDENSIAL BELUM DIKONFIGURASI' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Spreadsheet ID: <code class="font-mono text-indigo-600">{{ $spreadsheetId ?: 'Belum diisi di .env' }}</code>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form action="{{ route('admin.sync.init-sheets') }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                    📄 Inisialisasi Sheet & Header
                </button>
            </form>

            <form action="{{ route('admin.sync.full-resync') }}" method="POST" onsubmit="return confirm('Jadwalkan sinkronisasi penuh untuk seluruh data master ke Google Sheets?')">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition-colors">
                    ↻ Sinkronisasi Ulang Semua Data
                </button>
            </form>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tersinkronisasi</span>
            <p class="text-3xl font-black text-emerald-600 mt-1">{{ number_format($totalSynced) }}</p>
            <span class="text-xs text-slate-400 mt-1 block">Berhasil masuk ke Google Sheets</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Menunggu Antrean</span>
            <p class="text-3xl font-black text-amber-600 mt-1">{{ number_format($totalPending) }}</p>
            <span class="text-xs text-slate-400 mt-1 block">Dalam antrean transactional outbox</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Gagal Sinkronisasi</span>
            <div class="flex items-baseline justify-between">
                <p class="text-3xl font-black text-rose-600 mt-1">{{ number_format($totalFailed) }}</p>
                @if($totalFailed > 0)
                    <form action="{{ route('admin.sync.retry-failed') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">
                            Retry Semua Gagal &rarr;
                        </button>
                    </form>
                @endif
            </div>
            <span class="text-xs text-slate-400 mt-1 block">Membutuhkan perbaikan / retry</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sinkronisasi Terakhir</span>
            <p class="text-sm font-extrabold text-slate-800 mt-2 truncate">
                {{ $lastSync ? $lastSync->synced_at->setTimezone('Asia/Jakarta')->format('d M Y - H:i:s') : 'Belum pernah' }}
            </p>
            <span class="text-xs text-slate-400 mt-1 block">Waktu eksekusi sukses terakhir</span>
        </div>
    </div>

    {{-- Outbox Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-bold text-slate-900 text-sm">Riwayat Antrean Sinkronisasi (Transactional Outbox)</h3>

            <form action="{{ route('admin.sync.index') }}" method="GET" class="flex items-center gap-2">
                <select name="status" class="px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl">
                    <option value="">-- Semua Status --</option>
                    <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                    <option value="PROCESSING" {{ request('status') === 'PROCESSING' ? 'selected' : '' }}>PROCESSING</option>
                    <option value="SYNCED" {{ request('status') === 'SYNCED' ? 'selected' : '' }}>SYNCED</option>
                    <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>FAILED</option>
                </select>
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-6">ID</th>
                        <th class="py-3 px-6">Tipe Event</th>
                        <th class="py-3 px-6">Agregat & ID</th>
                        <th class="py-3 px-6 text-center">Percobaan</th>
                        <th class="py-3 px-6 text-center">Status</th>
                        <th class="py-3 px-6">Terakhir Diperbarui</th>
                        <th class="py-3 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($outboxItems as $item)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3.5 px-6 font-mono text-xs text-slate-400">#{{ $item->id }}</td>
                            <td class="py-3.5 px-6 font-mono text-xs font-bold text-indigo-600">{{ $item->event_type }}</td>
                            <td class="py-3.5 px-6 text-xs text-slate-800">
                                {{ $item->aggregate_type }} <span class="font-mono text-slate-400">#{{ $item->aggregate_id }}</span>
                            </td>
                            <td class="py-3.5 px-6 text-center text-xs font-mono">
                                {{ $item->attempts }}x
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                @php
                                    $stBadge = match($item->status) {
                                        'SYNCED' => 'bg-emerald-100 text-emerald-800',
                                        'FAILED' => 'bg-rose-100 text-rose-800',
                                        'PROCESSING' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $stBadge }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-xs text-slate-400">
                                {{ $item->updated_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-3.5 px-6 text-right space-x-2 whitespace-nowrap">
                                @if($item->error_message)
                                    <button type="button"
                                            @click="errorDetail = '{{ addslashes($item->error_message) }}'; errorModalOpen = true"
                                            class="text-xs font-bold text-rose-600 hover:underline">
                                        Lihat Error
                                    </button>
                                @endif

                                @if($item->status !== 'SYNCED')
                                    <form action="{{ route('admin.sync.retry', $item) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">
                                            ↻ Retry
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada antrean transaksi outbox ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($outboxItems->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $outboxItems->links() }}
            </div>
        @endif
    </div>

    {{-- Error Modal --}}
    <div x-show="errorModalOpen"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6" @click.away="errorModalOpen = false">
            <h3 class="text-base font-bold text-rose-600 mb-2">Detail Pesan Error Sinkronisasi</h3>
            <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl text-xs text-rose-900 font-mono overflow-x-auto max-h-60" x-text="errorDetail"></div>
            <div class="mt-4 text-right">
                <button type="button" @click="errorModalOpen = false" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
