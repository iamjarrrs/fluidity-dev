@extends('layouts.app')

@section('title', $topic->title . ' - Sesi Belajar')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="studyRoom()">

    <!-- Session Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                    {{ $course->name }}
                </span>
                <h1 class="text-xl font-bold text-slate-900 mt-1">{{ $topic->title }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Bobot: {{ $topic->difficulty_weight }}/5 • Penguasaan: Level {{ $topic->mastery_level }}/5
                </p>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold px-3 py-1 rounded-full"
                    :class="{
                        'bg-amber-50 text-amber-700 border border-amber-200': phase === 'pomodoro',
                        'bg-blue-50 text-blue-700 border border-blue-200': phase === 'recall',
                        'bg-purple-50 text-purple-700 border border-purple-200': phase === 'feynman',
                        'bg-emerald-50 text-emerald-700 border border-emerald-200': phase === 'done',
                    }">
                    <span x-text="phaseLabel"></span>
                </span>
            </div>
        </div>

        <!-- Phase Progress Bar -->
        <div class="mt-4 flex gap-1">
            <div class="h-1.5 rounded-full flex-1 transition-all duration-500"
                :class="phase === 'pomodoro' || phase === 'recall' || phase === 'feynman' || phase === 'done' ? 'bg-blue-500' : 'bg-slate-200'"></div>
            <div class="h-1.5 rounded-full flex-1 transition-all duration-500"
                :class="phase === 'recall' || phase === 'feynman' || phase === 'done' ? 'bg-blue-500' : 'bg-slate-200'"></div>
            <div class="h-1.5 rounded-full flex-1 transition-all duration-500"
                :class="phase === 'feynman' || phase === 'done' ? 'bg-blue-500' : 'bg-slate-200'"></div>
        </div>
    </div>

    <!-- AI Summary Card (selalu visible) -->
    @if($aiSummary)
    <div class="bg-slate-50 rounded-xl p-5 border border-slate-200" x-show="phase !== 'done'">
        <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5 mb-2">
            <span>📖</span> Rangkuman Materi:
        </div>
        <p class="text-sm text-slate-600 leading-relaxed">{{ $aiSummary }}</p>
    </div>
    @endif

    <!-- ===== PHASE 1: POMODORO TIMER ===== -->
    <div x-show="phase === 'pomodoro'" x-transition class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm text-center">
        <h2 class="text-lg font-bold text-slate-900 mb-2">🍅 Pomodoro — Waktu Fokus Belajar</h2>
        <p class="text-xs text-slate-500 mb-6">Pelajari materi modul Anda dengan fokus penuh. Timer bisa dipercepat untuk demo.</p>

        <!-- Timer Display -->
        <div class="text-6xl font-extrabold text-slate-900 font-mono tracking-wider mb-6">
            <span x-text="timerDisplay"></span>
        </div>

        <!-- Timer Controls -->
        <div class="flex items-center justify-center gap-3 mb-6">
            <button @click="toggleTimer()" class="px-6 py-2.5 rounded-xl font-semibold text-sm transition shadow-sm"
                :class="timerRunning ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'">
                <span x-text="timerRunning ? '⏸ Jeda' : '▶ Mulai'"></span>
            </button>
            <button @click="resetTimer()" class="px-4 py-2.5 rounded-xl font-semibold text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                🔄 Reset
            </button>
        </div>

        <!-- Quick Durations -->
        <div class="flex items-center justify-center gap-2 mb-6">
            <span class="text-xs text-slate-500">Atur durasi:</span>
            <button @click="setTimer(50)" class="text-xs px-3 py-1 rounded-lg border border-slate-200 hover:border-blue-300 transition" :class="totalMinutes === 50 ? 'bg-blue-50 text-blue-700 border-blue-300' : ''">50 menit</button>
            <button @click="setTimer(25)" class="text-xs px-3 py-1 rounded-lg border border-slate-200 hover:border-blue-300 transition" :class="totalMinutes === 25 ? 'bg-blue-50 text-blue-700 border-blue-300' : ''">25 menit</button>
            <button @click="setTimer(5)" class="text-xs px-3 py-1 rounded-lg border border-slate-200 hover:border-blue-300 transition" :class="totalMinutes === 5 ? 'bg-blue-50 text-blue-700 border-blue-300' : ''">5 menit (demo)</button>
            <button @click="setTimer(1)" class="text-xs px-3 py-1 rounded-lg border border-slate-200 hover:border-blue-300 transition" :class="totalMinutes === 1 ? 'bg-blue-50 text-blue-700 border-blue-300' : ''">1 menit (test)</button>
        </div>

        <!-- Skip to next phase (for demo) -->
        <button @click="skipPomodoro()" class="text-xs text-slate-400 hover:text-blue-600 underline transition">
            Lewati ke Active Recall →
        </button>
    </div>

    <!-- ===== PHASE 2: ACTIVE RECALL ===== -->
    <div x-show="phase === 'recall'" x-transition class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
            <h2 class="text-lg font-bold text-slate-900">🧠 Active Recall — Uji Pemahaman</h2>
            <button type="button" @click="refreshQuestions()" :disabled="isRefreshing"
                class="text-xs font-semibold px-3.5 py-1.5 rounded-xl border border-slate-200 hover:border-blue-300 bg-slate-50 hover:bg-blue-50 text-slate-700 hover:text-blue-700 disabled:opacity-50 transition self-start sm:self-auto">
                <span x-show="!isRefreshing">🔄 Buat Soal Baru</span>
                <span x-show="isRefreshing">⏳ Membuat soal...</span>
            </button>
        </div>
        <p class="text-xs text-slate-500 mb-6">Pilih satu pertanyaan konseptual, lalu Anda akan menjelaskan jawabannya dengan bahasa sendiri (Teknik Feynman).</p>

        <div class="space-y-3">
            <template x-for="(question, qi) in questions" :key="qi">
                <button type="button" @click="selectQuestion(question)"
                    class="w-full text-left p-4 rounded-xl border-2 transition text-sm"
                    :class="selectedQuestion === question ? 'border-blue-500 bg-blue-50 text-blue-900' : 'border-slate-200 hover:border-blue-300 bg-white text-slate-700'">
                    <span class="font-bold text-blue-600 mr-2" x-text="'Q' + (qi + 1) + '.'"></span>
                    <span x-text="question"></span>
                </button>
            </template>
        </div>

        <div class="mt-6 text-center">
            <button type="button" @click="goToFeynman()" :disabled="!selectedQuestion"
                class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-sm transition text-sm">
                Lanjut ke Penjelasan Feynman →
            </button>
        </div>
    </div>

    <!-- ===== PHASE 3: FEYNMAN TECHNIQUE ===== -->
    <div x-show="phase === 'feynman'" x-transition class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 mb-2 text-center">✍️ Teknik Feynman — Jelaskan dengan Bahasa Sendiri</h2>
        <p class="text-xs text-slate-500 mb-4 text-center">Bayangkan Anda menjelaskan konsep ini ke teman yang belum pernah belajar topik ini.</p>

        <!-- Selected Question -->
        <div class="bg-blue-50 rounded-xl p-4 border border-blue-200 mb-5">
            <div class="text-xs font-bold text-blue-700 mb-1">Pertanyaan yang dipilih:</div>
            <p class="text-sm text-blue-900 font-medium" x-text="selectedQuestion"></p>
        </div>

        <!-- Feynman Form -->
        <form method="POST" action="{{ route('session.submit', $session->id) }}" id="feynmanForm" @submit="isSubmitting = true">
            @csrf
            <input type="hidden" name="question" :value="selectedQuestion">
            <input type="hidden" name="pomodoro_completed" :value="pomodoroCompleted ? 1 : 0">
            <input type="hidden" name="time_spent_minutes" :value="elapsedMinutes">

            <div class="mb-4">
                <label for="explanation" class="block text-xs font-bold text-slate-700 mb-2">
                    Penjelasan Anda (minimal 20 karakter):
                </label>
                <textarea id="explanation" name="explanation" rows="8"
                    class="w-full rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 p-4 text-sm text-slate-800 placeholder-slate-400 resize-none transition"
                    placeholder="Jadi konsep ini intinya adalah... Cara kerjanya seperti... Contohnya dalam kehidupan nyata..."
                    x-model="explanation"
                    required minlength="20" maxlength="5000"></textarea>
                <div class="flex justify-between mt-1">
                    <span class="text-[11px] text-slate-400" x-text="explanation.length + '/5000 karakter'"></span>
                    <span class="text-[11px]" :class="explanation.length >= 20 ? 'text-emerald-600' : 'text-rose-500'"
                        x-text="explanation.length >= 20 ? '✓ Cukup panjang' : 'Minimal 20 karakter lagi'"></span>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" :disabled="explanation.length < 20 || isSubmitting"
                    class="px-10 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-sm transition text-sm">
                    <span x-show="!isSubmitting">🚀 Kirim & Dapatkan Feedback AI</span>
                    <span x-show="isSubmitting">⏳ Memproses evaluasi...</span>
                </button>
            </div>
        </form>
    </div>

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

        get phaseLabel() {
            const labels = {
                pomodoro: '🍅 Fase 1: Pomodoro',
                recall: '🧠 Fase 2: Active Recall',
                feynman: '✍️ Fase 3: Feynman',
                done: '✅ Selesai'
            };
            return labels[this.phase] || '';
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
