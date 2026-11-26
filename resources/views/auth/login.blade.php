@extends('layouts.app')

@section('title', 'Fluidity - Masuk dengan Akun Google')

@section('content')
@php
    // Warna mode sesi, sama dengan di dashboard dan Google Calendar
    $mode = [
        'pomodoro' => 'border-rose-500 bg-rose-50 text-rose-800',
        'recall'   => 'border-violet-600 bg-violet-50 text-violet-800',
        'feynman'  => 'border-amber-500 bg-amber-50 text-amber-900',
        'review'   => 'border-sky-600 bg-sky-50 text-sky-800',
    ];
    // Contoh minggu: kuliah dari kalender (abu), sesi belajar diisi di sela-selanya
    $week = [
        ['day' => 'Sen', 'items' => [['08.00', 'Kuliah'], ['19.00', 'Kalkulus II', 'pomodoro']]],
        ['day' => 'Sel', 'items' => [['10.00', 'Kuliah'], ['13.00', 'Kuliah']]],
        ['day' => 'Rab', 'items' => [['08.00', 'Kuliah'], ['19.00', 'Basis Data', 'recall']]],
        ['day' => 'Kam', 'items' => [['16.00', 'Fisika Dasar', 'feynman'], ['19.30', 'Kalkulus II', 'review']], 'more' => true],
        ['day' => 'Jum', 'items' => [['09.00', 'Kuliah'], ['15.00', 'Basis Data', 'review']], 'more' => true],
    ];
@endphp

<div class="min-h-[75vh] grid content-center justify-items-center gap-6 py-12 lg:grid-cols-[26rem_1fr]">
    <div class="flex w-full max-w-md flex-col justify-center rounded-2xl border border-slate-900/8 bg-white p-8">
        <h1 class="text-3xl font-medium leading-tight text-slate-900">Jadwal belajar dari waktu luang di kalendermu</h1>
        <p class="mt-4 text-base leading-relaxed text-slate-600">
            Fluidity menyusun sesi belajar di sela jadwal kuliah, lalu memandu tiap sesi dengan Pomodoro, Active Recall, dan Feynman
        </p>

        <a href="{{ route('auth.google') }}"
           class="mt-8 flex h-11 w-full items-center justify-center gap-3 rounded-lg border border-slate-900/12 bg-white px-5 text-sm font-medium text-slate-900 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            Masuk dengan Google
        </a>
        <p class="mt-3 text-center text-xs text-slate-500">Jadwal belajar langsung tersinkron ke Google Calendar</p>
    </div>

    <!-- 3 pilar utama -->
    <section class="grid w-full gap-4 sm:grid-cols-2" aria-label="Keunggulan Fluidity">
        <article class="rounded-2xl border border-slate-900/8 bg-white p-5 sm:col-span-2">
            <div class="flex items-center gap-2">
                <svg class="size-4.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <h2 class="text-sm font-medium text-slate-900">Otomatisasi kalender</h2>
            </div>
            <p class="mt-1 text-sm text-slate-600">Jadwal belajar langsung sinkron dengan waktu luangmu</p>

            <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5" aria-label="Contoh jadwal minggu ini">
                @foreach ($week as $d)
                    <div class="{{ ! empty($d['more']) ? 'hidden sm:block' : '' }}">
                        <p class="font-mono text-xs text-slate-500">{{ $d['day'] }}</p>
                        <div class="mt-2 space-y-1">
                            @foreach ($d['items'] as $it)
                                <div class="rounded-md border-l-2 px-2 py-1.5 text-xs {{ isset($it[2]) ? $mode[$it[2]] : 'border-slate-300 bg-slate-100 text-slate-600' }}">
                                    <span class="block font-mono tabular-nums opacity-80">{{ $it[0] }}</span>
                                    <span class="block truncate {{ isset($it[2]) ? 'font-medium' : '' }}">{{ $it[1] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="flex flex-col rounded-2xl border border-slate-900/8 bg-white p-5">
            <div class="flex items-center gap-2">
                <svg class="size-4.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                <h2 class="text-sm font-medium text-slate-900">Active Recall & Feynman</h2>
            </div>
            <p class="mt-1 text-sm text-slate-600">Gemini menguji konsep dari modul dosen</p>

            <div class="mt-4 rounded-md border-l-2 border-violet-600 bg-violet-50 px-3 py-2.5">
                <p class="text-xs text-violet-800">Active Recall · Basis Data</p>
                <p class="mt-1 text-sm text-slate-900">Kapan sebuah relasi sudah memenuhi bentuk normal ketiga (3NF)?</p>
            </div>
        </article>

        <article class="flex flex-col rounded-2xl border border-slate-900/8 bg-white p-5">
            <div class="flex items-center gap-2">
                <svg class="size-4.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="16" height="10" rx="2"/><path d="M22 11v2M6 11v2M10 11v2"/></svg>
                <h2 class="text-sm font-medium text-slate-900">Anti-burnout</h2>
            </div>
            <p class="mt-1 text-sm text-slate-600">Maksimal 2 sesi per hari dengan interval adaptif</p>

            <div class="mt-4">
                <div class="flex items-baseline justify-between text-xs">
                    <span class="text-slate-500">Hari ini</span>
                    <span class="font-mono tabular-nums text-slate-900">2 dari 2 sesi</span>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-1" aria-hidden="true">
                    <span class="h-2 rounded-full bg-primary-600"></span>
                    <span class="h-2 rounded-full bg-primary-600"></span>
                </div>
                <p class="mt-2 text-xs text-slate-500">Sesi berikutnya besok, 16.00</p>
            </div>
        </article>
    </section>
</div>
@endsection
