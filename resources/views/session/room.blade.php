@extends('layouts.app')

@section('title', $topic->title . ' - Sesi Belajar')

@php
    // Warna mode sesi dari palet: Pomodoro rose, Active Recall violet, Feynman amber
    $steps = [
        ['pomodoro', 'Pomodoro', 'bg-rose-500', 'text-rose-700'],
        ['recall', 'Active Recall', 'bg-violet-600', 'text-violet-700'],
        ['feynman', 'Feynman', 'bg-amber-500', 'text-amber-800'],
    ];
    $durations = [50, 25, 5, 1];
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="studyRoom()">

    <!-- Header sesi -->
    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <div class="border-l-2 pl-4" style="border-color: {{ $course->color ?? '#0f766e' }}">
            <p class="text-sm text-slate-500">{{ $course->name }}</p>
            <h1 class="mt-1 text-2xl font-medium text-slate-900">{{ $topic->title }}</h1>
            <p class="mt-1 text-xs text-slate-500">Bobot {{ $topic->difficulty_weight }}/5 · Penguasaan {{ $topic->mastery_level }}/5</p>
        </div>

        <ol class="mt-6 grid grid-cols-3 gap-2" aria-label="Tahap sesi">
            @foreach($steps as $n => [$key, $label, $bar, $ink])
                <li :aria-current="phase === '{{ $key }}' ? 'step' : null">
                    <div class="h-1.5 rounded-full transition-colors duration-300" :class="reached({{ $n }}) ? '{{ $bar }}' : 'bg-slate-200'"></div>
                    <p class="mt-2 text-xs" :class="phase === '{{ $key }}' ? 'font-medium {{ $ink }}' : 'text-slate-500'">
                        <span class="font-mono">{{ $n + 1 }}</span> {{ $label }}
                    </p>
                </li>
            @endforeach
        </ol>
    </section>

    @if($aiSummary)
        <section class="rounded-2xl bg-slate-100/70 p-5 sm:p-6" x-show="phase !== 'done'" aria-label="Rangkuman materi">
            <p class="text-xs text-slate-500">Rangkuman dari Gemini</p>
            <p class="mt-1 text-sm leading-relaxed text-slate-700">{{ $aiSummary }}</p>
        </section>
    @endif

    <!-- Tahap 1: Pomodoro -->
    <section x-show="phase === 'pomodoro'" x-transition.opacity class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 text-center" aria-labelledby="pomodoro-title">
        <h2 id="pomodoro-title" class="text-base font-medium text-slate-900">Fokus pada materinya</h2>
        <p class="mt-1 text-sm text-slate-600">Baca modul tanpa gangguan sampai timer habis</p>

        <p class="mt-8 font-mono text-6xl sm:text-7xl font-normal tabular-nums text-slate-900" x-text="timerDisplay" aria-live="off"></p>
        <div class="mx-auto mt-6 h-1 max-w-xs overflow-hidden rounded-full bg-slate-200" aria-hidden="true">
            <div class="h-1 origin-left rounded-full bg-rose-500 transition-transform duration-1000 ease-linear" :style="`transform: scaleX(${elapsedRatio})`"></div>
        </div>

        <div class="mt-8 flex items-center justify-center gap-3">
            <button type="button" @click="toggleTimer()"
                class="h-11 min-w-28 rounded-full bg-primary-600 px-6 text-sm font-medium text-white hover:bg-primary-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                x-text="timerRunning ? 'Jeda' : (startTime ? 'Lanjutkan' : 'Mulai')"></button>
            <button type="button" @click="resetTimer()"
                class="h-11 rounded-full border border-slate-200 bg-white px-5 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
                Ulang
            </button>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-2" role="group" aria-label="Durasi">
            @foreach($durations as $min)
                <button type="button" @click="setTimer({{ $min }})" :aria-pressed="totalMinutes === {{ $min }}"
                    class="rounded-full border px-3 py-1 text-xs tabular-nums transition-colors"
                    :class="totalMinutes === {{ $min }} ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:text-slate-900'">
                    {{ $min }} menit
                </button>
            @endforeach
        </div>

        <button type="button" @click="skipPomodoro()" class="mt-6 text-xs text-slate-500 underline-offset-4 hover:text-slate-900 hover:underline transition-colors">
            Lewati ke Active Recall
        </button>
    </section>

    <!-- Tahap 2: Active Recall -->
    <section x-show="phase === 'recall'" x-transition.opacity class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="recall-title">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div>
                <h2 id="recall-title" class="text-base font-medium text-slate-900">Pilih satu pertanyaan</h2>
                <p class="mt-1 text-sm text-slate-600">Jawab tanpa melihat modul. Setelah ini kamu menjelaskannya dengan bahasamu sendiri</p>
            </div>
            <button type="button" @click="refreshQuestions()" :disabled="isRefreshing"
                class="self-start shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-900 hover:bg-slate-100 disabled:opacity-50 transition-colors"
                x-text="isRefreshing ? 'Membuat soal…' : 'Soal lain'"></button>
        </div>

        <div class="mt-6 space-y-3">
            <template x-for="(question, qi) in questions" :key="qi">
                <button type="button" @click="selectQuestion(question)" :aria-pressed="selectedQuestion === question"
                    class="flex w-full gap-3 rounded-xl border p-4 text-left text-sm transition-colors"
                    :class="selectedQuestion === question ? 'border-violet-600 bg-violet-50 text-slate-900' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'">
                    <span class="font-mono text-slate-500" x-text="qi + 1"></span>
                    <span x-text="question"></span>
                </button>
            </template>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="button" @click="goToFeynman()" :disabled="!selectedQuestion"
                class="h-11 rounded-full bg-primary-600 px-6 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 transition-colors">
                Lanjut ke Feynman
            </button>
        </div>
    </section>

    <!-- Tahap 3: Feynman -->
    <section x-show="phase === 'feynman'" x-transition.opacity class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="feynman-title">
        <h2 id="feynman-title" class="text-base font-medium text-slate-900">Jelaskan dengan bahasamu sendiri</h2>
        <p class="mt-1 text-sm text-slate-600">Bayangkan kamu menjelaskannya ke teman yang belum pernah belajar topik ini</p>

        <div class="mt-5 rounded-xl bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Pertanyaan</p>
            <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedQuestion"></p>
        </div>

        <form method="POST" action="{{ route('session.submit', $session->id) }}" id="feynmanForm" @submit="isSubmitting = true" class="mt-5">
            @csrf
            <input type="hidden" name="question" :value="selectedQuestion">
            <input type="hidden" name="pomodoro_completed" :value="pomodoroCompleted ? 1 : 0">
            <input type="hidden" name="time_spent_minutes" :value="elapsedMinutes">

            <label for="explanation" class="mb-2 block text-sm text-slate-700">Penjelasanmu</label>
            <textarea id="explanation" name="explanation" rows="8"
                class="w-full resize-none rounded-xl border border-slate-300 p-4 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200"
                placeholder="Intinya… Cara kerjanya seperti… Contohnya…"
                x-model="explanation" required minlength="20" maxlength="5000"></textarea>
            <p class="mt-1 text-right font-mono text-xs tabular-nums text-slate-500"
               x-text="explanation.length < 20 ? `${20 - explanation.length} karakter lagi` : `${explanation.length}/5000`"></p>

            <div class="mt-4 flex justify-end">
                <button type="submit" :disabled="explanation.length < 20 || isSubmitting"
                    class="h-11 rounded-full bg-primary-600 px-6 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 transition-colors"
                    x-text="isSubmitting ? 'Gemini sedang menilai…' : 'Kirim penjelasan'"></button>
            </div>
        </form>
    </section>

</div>

<script>
function studyRoom() {
    return {
        phase: 'pomodoro',
        totalMinutes: 50,
        secondsLeft: 50 * 60,
        timerRunning: false,
        timerInterval: null,
        pomodoroCompleted: false,
        questions: @json($questions),
        isRefreshing: false,
        selectedQuestion: '',
        explanation: '',
        isSubmitting: false,
        startTime: null,

        // Tahap ke-n (0-based) sudah dicapai atau sedang berjalan
        reached(n) {
            return ['pomodoro', 'recall', 'feynman', 'done'].indexOf(this.phase) >= n;
        },

        get elapsedRatio() {
            return 1 - this.secondsLeft / (this.totalMinutes * 60);
        },

        get timerDisplay() {
            const mins = Math.floor(this.secondsLeft / 60);
            const secs = this.secondsLeft % 60;
            return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        },

        get elapsedMinutes() {
            if (!this.startTime) return 1;
            return Math.max(1, Math.round((Date.now() - this.startTime) / 60000));
        },

        setTimer(mins) {
            this.stopTimer();
            this.totalMinutes = mins;
            this.secondsLeft = mins * 60;
        },

        toggleTimer() {
            if (this.timerRunning) {
                this.stopTimer();
            } else {
                this.startTimer();
            }
        },

        startTimer() {
            if (!this.startTime) this.startTime = Date.now();
            this.timerRunning = true;
            this.timerInterval = setInterval(() => {
                if (this.secondsLeft > 0) {
                    this.secondsLeft--;
                } else {
                    this.pomodoroComplete();
                }
            }, 1000);
        },

        stopTimer() {
            this.timerRunning = false;
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },

        resetTimer() {
            this.stopTimer();
            this.secondsLeft = this.totalMinutes * 60;
        },

        pomodoroComplete() {
            this.stopTimer();
            this.pomodoroCompleted = true;
            this.phase = 'recall';
        },

        skipPomodoro() {
            this.stopTimer();
            if (!this.startTime) this.startTime = Date.now();
            this.pomodoroCompleted = false;
            this.phase = 'recall';
        },

        selectQuestion(q) {
            this.selectedQuestion = q;
        },

        async refreshQuestions() {
            if (this.isRefreshing) return;
            this.isRefreshing = true;
            try {
                const res = await fetch('{{ route('session.refresh_questions', $session->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    const data = await res.json();
                    if (Array.isArray(data.questions) && data.questions.length > 0) {
                        this.questions = data.questions;
                        this.selectedQuestion = '';
                    }
                }
            } finally {
                this.isRefreshing = false;
            }
        },

        goToFeynman() {
            if (this.selectedQuestion) {
                this.phase = 'feynman';
            }
        }
    };
}
</script>
@endsection
