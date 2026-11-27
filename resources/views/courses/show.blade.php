@extends('layouts.app')

@section('title', $course->name . ' - Detail Mata Kuliah')

@use('Carbon\Carbon')
@php
    $color = $course->color ?? '#0f766e';
    $examDate = Carbon::parse($course->exam_date);
    $daysLeft = (int) today()->diffInDays($examDate->copy()->startOfDay(), false);

    // Sama dengan dashboard: level 1-5 -> [persen, label, warna bar, warna teks]
    $mastery = fn ($level) => match (true) {
        $level <= 2 => [$level * 20, 'Lemah', 'bg-red-500', 'text-red-700'],
        $level === 3 => [$level * 20, 'Sedang', 'bg-yellow-500', 'text-yellow-800'],
        default => [$level * 20, 'Kuat', 'bg-emerald-600', 'text-emerald-700'],
    };
@endphp

@section('content')
<div>

    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
        <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Dashboard
    </a>

    <!-- Header -->
    <div class="mt-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div class="border-l-2 pl-4" style="border-color: {{ $color }}">
            <h1 class="text-2xl sm:text-3xl font-medium text-slate-900">
                {{ $course->name }}
                @if($course->code)
                    <span class="font-mono text-base font-normal text-slate-500">{{ $course->code }}</span>
                @endif
            </h1>
            <p class="mt-2 text-slate-600">
                Ujian {{ $examDate->locale('id')->isoFormat('dddd, D MMMM Y') }} ·
                @if($daysLeft > 0)
                    <span class="tabular-nums {{ $daysLeft <= 7 ? 'font-medium text-red-700' : '' }}">{{ $daysLeft }} hari lagi</span>
                @elseif($daysLeft === 0)
                    <span class="font-medium text-red-700">hari ini</span>
                @else
                    <span>sudah lewat</span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('courses.edit', $course->id) }}" class="inline-flex h-10 items-center rounded-full border border-slate-200 bg-white px-4 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
                Edit mata kuliah
            </a>
            <a href="{{ route('schedule.preview') }}" class="inline-flex h-10 items-center rounded-full bg-primary-600 px-4 text-sm font-medium text-white hover:bg-primary-700 transition-colors">
                Buat jadwal belajar
            </a>
        </div>
    </div>

    <!-- Bab -->
    <section class="mt-8" aria-labelledby="topics-title">
        <div class="flex items-baseline justify-between gap-3">
            <h2 id="topics-title" class="text-base font-medium text-slate-900">Bab dan penguasaan</h2>
            <span class="font-mono text-xs text-slate-500 tabular-nums">{{ $course->topics->count() }} bab</span>
        </div>

        <ol class="mt-3 space-y-4">
            @foreach($course->topics as $index => $topic)
                @php
                    [$pct, $label, $bar, $ink] = $mastery($topic->mastery_level);
                    $moduleFiles = $topic->allModuleFiles();
                    $due = $topic->next_review_due;
                @endphp
                <li class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <h3 class="text-base font-medium text-slate-900">
                            <span class="mr-2 font-mono text-sm font-normal text-slate-500 tabular-nums">{{ $index + 1 }}</span>{{ $topic->title }}
                        </h3>
                        <span class="text-xs text-slate-500">Bobot {{ $topic->difficulty_weight }}/5</span>
                    </div>

                    <div class="mt-4">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">Penguasaan</span>
                            <span class="font-medium tabular-nums {{ $ink }}">{{ $pct }}% · {{ $label }}</span>
                        </div>
                        <div class="mt-1.5 h-2 rounded-full bg-slate-200"><div class="h-2 rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div></div>
                    </div>

                    @if($topic->ai_summary)
                        <div class="mt-4 rounded-lg bg-slate-50 p-4">
                            <p class="text-xs text-slate-500">Rangkuman dari Gemini</p>
                            <p class="mt-1 text-sm leading-relaxed text-slate-700">{{ $topic->ai_summary }}</p>
                        </div>
                    @endif

                    <div class="mt-4 flex flex-col sm:flex-row sm:items-start justify-between gap-3 text-xs text-slate-500">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            @forelse($moduleFiles as $mf)
                                <a href="{{ asset('storage/' . $mf['path']) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-slate-700 hover:text-primary-700 hover:underline">
                                    <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    {{ $mf['name'] }}
                                </a>
                            @empty
                                <span>Belum ada modul</span>
                            @endforelse
                        </div>
                        <span class="shrink-0">
                            @if($due && $due->isPast())
                                <span class="font-medium text-amber-700">Perlu review sejak {{ $due->locale('id')->isoFormat('D MMMM') }}</span>
                            @elseif($due)
                                Review berikutnya {{ $due->locale('id')->isoFormat('dddd, D MMMM') }}
                            @else
                                Belum pernah di-review
                            @endif
                            @if($topic->repetition_count > 0)
                                · {{ $topic->repetition_count }}× review
                            @endif
                        </span>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

</div>
@endsection
