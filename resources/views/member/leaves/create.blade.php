@extends('layouts.member')

@section('title', 'Ajukan Izin / Sakit: ' . $event->title)

@section('content')
<div class="max-w-xl mx-auto space-y-6">

    <div>
        <a href="{{ route('member.events.show', $event) }}" class="text-xs font-bold text-indigo-600 hover:underline">&larr; Kembali ke Detail Kegiatan</a>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <span class="font-mono text-xs text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded">{{ $event->code }}</span>
            <h2 class="text-base font-extrabold text-slate-900 mt-1">{{ $event->title }}</h2>
            <p class="text-xs text-slate-500 mt-1">Formulir permohonan ketidakhadiran resmi.</p>
        </div>

        <form action="{{ route('member.leaves.store', $event) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Jenis Permohonan</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3.5 rounded-2xl border border-slate-200 cursor-pointer flex items-center gap-3 hover:border-indigo-400">
                        <input type="radio" name="type" value="IZIN" checked class="text-indigo-600">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">IZIN</span>
                            <span class="text-[11px] text-slate-400">Keperluan mendesak</span>
                        </div>
                    </label>
                    <label class="p-3.5 rounded-2xl border border-slate-200 cursor-pointer flex items-center gap-3 hover:border-indigo-400">
                        <input type="radio" name="type" value="SAKIT" class="text-indigo-600">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">SAKIT</span>
                            <span class="text-[11px] text-slate-400">Kondisi kesehatan</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label for="reason" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Alasan Pengajuan</label>
                <textarea id="reason" name="reason" rows="4" required placeholder="Jelaskan secara rinci alasan tidak dapat hadir..."
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-xs @error('reason') border-rose-500 @enderror">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Surat Keterangan / Bukti (Opsional)</label>
                <input type="file" id="attachment" name="attachment" accept="image/*,.pdf"
                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                <p class="mt-1 text-[11px] text-slate-400">Format: JPG, PNG, PDF (Maks. 5MB). Sangat disarankan untuk pengajuan Sakit.</p>
                @error('attachment')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('member.events.show', $event) }}" class="px-4 py-2 rounded-xl text-slate-600 font-semibold text-xs hover:bg-slate-100">Batal</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-md hover:bg-indigo-700 transition-all">
                    Kirim Permohonan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
