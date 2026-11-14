@extends('layouts.app')

@section('body')
<div class="min-h-full flex flex-col bg-slate-100">

    {{-- Member App Top Header --}}
    <header class="bg-indigo-600 text-white sticky top-0 z-30 shadow-md">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('member.dashboard') }}" class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center font-black text-white text-base">
                    OA
                </a>
                <div>
                    <h1 class="text-base font-bold tracking-tight leading-none text-white">ORGA-ATTEND</h1>
                    <span class="text-[11px] text-indigo-200">Panel Anggota</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('member.profile') }}" class="flex items-center gap-2 hover:bg-white/10 px-2.5 py-1.5 rounded-lg transition-colors">
                    <div class="w-7 h-7 rounded-full bg-indigo-200 text-indigo-900 font-bold text-xs flex items-center justify-center">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="hidden sm:inline text-xs font-semibold text-white">{{ auth()->user()->name }}</span>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Keluar" class="p-2 text-indigo-200 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 max-w-4xl w-full mx-auto p-4 sm:p-6 pb-24 sm:pb-12">
        @yield('content')
    </main>

    {{-- Mobile Bottom Navigation Bar (Sticky on phones) --}}
    <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-40 py-2 sm:hidden shadow-lg">
        <div class="max-w-md mx-auto grid grid-cols-5 text-center">

            <a href="{{ route('member.dashboard') }}"
               class="flex flex-col items-center py-1 {{ request()->routeIs('member.dashboard') ? 'text-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-600' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px] mt-1">Beranda</span>
            </a>

            <a href="{{ route('member.events.index') }}"
               class="flex flex-col items-center py-1 {{ request()->routeIs('member.events.*') ? 'text-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-600' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-[10px] mt-1">Kegiatan</span>
            </a>

            <a href="{{ route('member.attendance.history') }}"
               class="flex flex-col items-center py-1 {{ request()->routeIs('member.attendance.*') ? 'text-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-600' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="text-[10px] mt-1">Riwayat</span>
            </a>

            <a href="{{ route('member.leaves.index') }}"
               class="flex flex-col items-center py-1 {{ request()->routeIs('member.leaves.*') ? 'text-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-600' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-[10px] mt-1">Izin</span>
            </a>

            <a href="{{ route('member.profile') }}"
               class="flex flex-col items-center py-1 {{ request()->routeIs('member.profile') ? 'text-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-600' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[10px] mt-1">Profil</span>
            </a>

        </div>
    </nav>
</div>
@endsection
