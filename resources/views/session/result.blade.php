@extends('layouts.app')

@section('title', 'Hasil Sesi Belajar - Fluidity')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div>
        <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-800 transition flex items-center gap-1 mb-2">
            &larr; Kembali ke Dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900">🎉 Sesi Belajar Selesai!</h1>
        <p class="text-sm text-slate-500 mt-1">
            {{ $session->topic->course->name ?? 'Mata Kuliah' }} &bull; {{ $session->topic->title }}
        </p>
    </div>

    <!-- Score & Mastery Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
            <div class="p-4 rounded-xl bg-primary-50 border border-primary-100">
                <div class="text-xs font-semibold text-primary-700 mb-1">Skor Pemahaman</div>
                <div class="text-4xl font-extrabold text-primary-600">
                    {{ $review->score_awarded ?? 4 }}<span class="text-lg text-primary-400">/5</span>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                <div class="text-xs font-semibold text-emerald-700 mb-1">Tingkat Penguasaan Baru</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-1">
                    Level {{ $session->topic->mastery_level }}/5
                </div>
                <div class="text-[11px] text-emerald-600 mt-0.5">
                    Repetisi ke-{{ $session->topic->repetition_count }}
                </div>
            </div>

            <div class="p-4 rounded-xl bg-sky-50 border border-sky-100">
                <div class="text-xs font-semibold text-sky-700 mb-1">Jadwal Review Berikutnya</div>
                <div class="text-lg font-extrabold text-sky-600 mt-1">
                    {{ $session->topic->interval_days }} Hari Lagi
                </div>
                @if($session->topic->next_review_due)
                    <div class="text-[11px] text-sky-600 mt-0.5">
                        {{ \Carbon\Carbon::parse($session->topic->next_review_due)->isoFormat('D MMM Y') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- AI Feedback Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center gap-2">
            <span class="text-xl">💬</span>
            <h2 class="text-base font-bold text-slate-900">Evaluasi & Masukan Pemahaman</h2>
        </div>

        <div class="rounded-xl p-4 border text-sm text-slate-700 leading-relaxed {{ ($review->score_awarded ?? 4) <= 2 ? 'bg-amber-50/70 border-amber-200' : 'bg-emerald-50/70 border-emerald-200' }}">
            {{ $review->ai_feedback }}
        </div>

        <div class="border-t border-slate-100 pt-4 space-y-3">
            <div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pertanyaan Active Recall</div>
                <p class="text-sm font-medium text-slate-800">{{ $review->generated_question }}</p>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Penjelasan Anda (Feynman)</div>
                <p class="text-sm text-slate-600 bg-slate-50 p-3.5 rounded-xl border border-slate-100 leading-relaxed">{{ $review->student_explanation }}</p>
            </div>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-100">
            <span>Status Pomodoro: {{ $review->pomodoro_completed ? '✅ Selesai Penuh' : '⚡ Mode Cepat / Dilewati' }}</span>
            <span>Waktu Sesi: {{ $review->time_spent_minutes }} menit</span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <a href="{{ route('courses.show', $session->topic->course_id) }}" class="w-full sm:w-auto text-center px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition">
            Lihat Detail Mata Kuliah
        </a>
        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto text-center px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold shadow-sm transition">
            Kembali ke Dashboard &rarr;
        </a>
    </div>

</div>
@endsection
