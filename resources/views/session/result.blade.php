@extends('layouts.app')

@section('title', 'Hasil Sesi Belajar - Fluidity')

@use('Carbon\Carbon')
@php
    $topic = $session->topic;
    $score = $review->score_awarded ?? 4;
    $level = $topic->mastery_level;
    // Sama dengan dashboard dan detail mata kuliah: level 1-5 -> [persen, label, warna bar, warna teks]
    [$pct, $label, $bar, $ink] = match (true) {
        $level <= 2 => [$level * 20, 'Lemah', 'bg-red-500', 'text-red-700'],
        $level === 3 => [$level * 20, 'Sedang', 'bg-yellow-500', 'text-yellow-800'],
        default => [$level * 20, 'Kuat', 'bg-emerald-600', 'text-emerald-700'],
    };
    $due = $topic->next_review_due ? Carbon::parse($topic->next_review_due) : null;
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="border-l-2 pl-4" style="border-color: {{ $topic->course->color ?? '#0f766e' }}">
        <p class="text-sm text-slate-500">{{ $topic->course->name ?? 'Mata kuliah' }}</p>
        <h1 class="mt-1 text-2xl sm:text-3xl font-medium text-slate-900">Sesi selesai: {{ $topic->title }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $review->time_spent_minutes }} menit ·
            {{ $review->pomodoro_completed ? 'Pomodoro selesai penuh' : 'Pomodoro dilewati' }}
        </p>
    </div>

    <!-- Skor & penguasaan -->
    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-label="Hasil">
        <div class="grid gap-6 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-center">
            <div>
                <p class="text-sm text-slate-500">Skor pemahaman</p>
                <p class="mt-1 text-5xl font-medium tabular-nums text-slate-900">{{ $score }}<span class="text-2xl text-slate-500">/5</span></p>
            </div>
            <div class="sm:border-l sm:border-slate-200 sm:pl-6">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Penguasaan topik sekarang</span>
                    <span class="font-medium tabular-nums {{ $ink }}">{{ $pct }}% · {{ $label }}</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-slate-200"><div class="h-2 rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div></div>
                <p class="mt-3 text-sm text-slate-600">
                    @if($due)
                        Review berikutnya {{ $due->locale('id')->isoFormat('dddd, D MMMM') }}
                        <span class="text-slate-500">({{ $topic->interval_days }} hari lagi)</span>
                    @else
                        Belum ada jadwal review berikutnya
                    @endif
                </p>
            </div>
        </div>
    </section>

    <!-- Masukan Gemini -->
    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="feedback-title">
        <h2 id="feedback-title" class="text-base font-medium text-slate-900">Masukan dari Gemini</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ $review->ai_feedback }}</p>

        <dl class="mt-6 space-y-5 border-t border-slate-200 pt-5">
            <div>
                <dt class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-violet-600" aria-hidden="true"></span>Pertanyaan Active Recall
                </dt>
                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $review->generated_question }}</dd>
            </div>
            <div>
                <dt class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>Penjelasanmu (Feynman)
                </dt>
                <dd class="mt-2 rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-700">{{ $review->student_explanation }}</dd>
            </div>
        </dl>
    </section>

    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
        <a href="{{ route('courses.show', $topic->course_id) }}" class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-5 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
            Lihat {{ $topic->course->name ?? 'mata kuliah' }}
        </a>
        <a href="{{ route('dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-full bg-primary-600 px-6 text-sm font-medium text-white hover:bg-primary-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
            Kembali ke Dashboard
        </a>
    </div>

</div>
@endsection
