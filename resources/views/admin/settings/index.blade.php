@extends('layouts.admin')

@section('title', 'Pengaturan Sistem')
@section('page_title', 'Pengaturan & Status Sistem')

@section('content')
<div class="max-w-4xl space-y-8">

    {{-- System Environment Status --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">Informasi Lingkungan Sistem</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block">Nama Aplikasi</span>
                <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $appInfo['app_name'] }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block">PHP Version</span>
                <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $appInfo['php_version'] }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block">Laravel Version</span>
                <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $appInfo['laravel_version'] }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block">Queue Driver</span>
                <span class="font-bold text-indigo-600 font-mono text-sm mt-0.5 block">{{ $appInfo['queue_connection'] }} (MySQL)</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block">Session Driver</span>
                <span class="font-bold text-indigo-600 font-mono text-sm mt-0.5 block">{{ $appInfo['session_driver'] }} (MySQL)</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block">Zona Waktu</span>
                <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $appInfo['timezone'] }}</span>
            </div>
        </div>
    </div>

    {{-- Google API Integration Status & Guide --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Status Integrasi Google Cloud (Drive & Sheets)</h3>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $googleConfig['client_ready'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $googleConfig['client_ready'] ? 'READY' : 'BELUM DIKONFIGURASI' }}
            </span>
        </div>

        <div class="space-y-3 text-xs mb-6">
            <div class="flex items-center justify-between py-2 border-b border-slate-50">
                <span class="text-slate-600 font-medium">Berkas Kredensial Service Account:</span>
                <span class="font-mono {{ $googleConfig['credentials_exists'] ? 'text-emerald-600 font-bold' : 'text-rose-600 font-semibold' }}">
                    {{ $googleConfig['credentials_exists'] ? '✓ Ditemukan' : '✗ Tidak Ditemukan (' . $googleConfig['credentials_path'] . ')' }}
                </span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-slate-50">
                <span class="text-slate-600 font-medium">Google Sheets Spreadsheet ID:</span>
                <span class="font-mono text-slate-800 font-semibold">
                    {{ $googleConfig['spreadsheet_id'] ?: 'Belum diisi di .env' }}
                </span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-slate-50">
                <span class="text-slate-600 font-medium">Google Drive Parent Folder ID:</span>
                <span class="font-mono text-slate-800 font-semibold">
                    {{ $googleConfig['drive_folder_id'] ?: 'Root (Default)' }}
                </span>
            </div>
        </div>

        {{-- Setup Instructions --}}
        <div class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100">
            <h4 class="font-bold text-indigo-900 text-xs uppercase tracking-wider mb-2">Panduan Konfigurasi Google Cloud Service Account</h4>
            <ol class="list-decimal list-inside space-y-1.5 text-xs text-indigo-950 leading-relaxed">
                <li>Buka <strong>Google Cloud Console</strong> dan buat project baru.</li>
                <li>Aktifkan <strong>Google Sheets API</strong> dan <strong>Google Drive API</strong> pada menu API Library.</li>
                <li>Buat <strong>Service Account</strong> baru, lalu buat Service Account Key dalam format <strong>JSON</strong>.</li>
                <li>Simpan file JSON tersebut ke lokasi: <code class="bg-indigo-100 px-1.5 py-0.5 rounded font-mono">storage/app/google-credentials.json</code>.</li>
                <li>Buat Google Spreadsheet baru di Google Drive, lalu bagikan (Share) spreadsheet tersebut ke alamat email service account dengan peran <strong>Editor</strong>.</li>
                <li>Salin ID spreadsheet dari URL Google Spreadsheet dan simpan ke variabel <code class="bg-indigo-100 px-1.5 py-0.5 rounded font-mono">GOOGLE_SHEETS_SPREADSHEET_ID</code> pada file <code class="bg-indigo-100 px-1.5 py-0.5 rounded font-mono">.env</code>.</li>
                <li>Klik tombol <strong>Inisialisasi Sheet & Header</strong> pada halaman Google Sheets Sync di panel admin.</li>
            </ol>
        </div>
    </div>

</div>
@endsection
