@extends('layouts.app')

@section('title', $course->name . ' - Detail Mata Kuliah')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Navigation -->
    <div>
        <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition flex items-center gap-1 mb-2">
            &larr; Kembali ke Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl border"
             style="background-color: {{ $course->color ?? '#3b82f6' }}12; border-color: {{ $course->color ?? '#3b82f6' }}40; border-left: 5px solid {{ $course->color ?? '#3b82f6' }};">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full inline-block" style="background-color: {{ $course->color }};"></span>
                    {{ $course->name }}
                    @if($course->code)
                        <span class="text-sm font-semibold text-slate-500">({{ $course->code }})</span>
                    @endif
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Jadwal Ujian: <strong class="text-rose-600">{{ \Carbon\Carbon::parse($course->exam_date)->isoFormat('dddd, D MMMM Y') }}</strong>
                    ({{ max(0, (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($course->exam_date)->startOfDay(), false)) }} hari lagi)
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('courses.edit', $course->id) }}" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl shadow-sm transition text-center">
                    ✏️ Edit Mata Kuliah
                </a>
                <a href="{{ route('schedule.preview') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition text-center">
                    ✨ Generate Jadwal Belajar &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Topics List -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-slate-800">Daftar Bab & Status Penguasaan Materi</h2>

        @foreach($course->topics as $index => $topic)
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-700">
                            Bab {{ $index + 1 }}
                        </span>
                        <h3 class="text-base font-bold text-slate-900">{{ $topic->title }}</h3>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Difficulty Badge -->
                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                            Bobot: {{ $topic->difficulty_weight }}/5
                        </span>
                        <!-- Mastery Badge -->
                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                            Penguasaan: Level {{ $topic->mastery_level }}/5
                        </span>
                    </div>
                </div>

                <!-- AI Summary Snippet -->
                @if($topic->ai_summary)
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5 mb-1.5">
                            <span>✨</span> Rangkuman Konsep Inti:
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            {{ $topic->ai_summary }}
                        </p>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 pt-1">
                    @php $moduleFiles = $topic->allModuleFiles(); @endphp
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if(! empty($moduleFiles))
                            <span class="font-semibold text-slate-600">📎 Modul ({{ count($moduleFiles) }}):</span>
                            @foreach($moduleFiles as $mf)
                                <a href="{{ asset('storage/' . $mf['path']) }}" target="_blank"
                                   class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 border border-slate-200 transition">
                                    {{ $mf['name'] }}
                                </a>
                            @endforeach
                        @else
                            <span>Modul teks manual</span>
                        @endif
                    </div>
                    <span class="flex-shrink-0">
                        Repetisi: {{ $topic->repetition_count }}x • Interval: {{ $topic->interval_days }} hari
                    </span>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
