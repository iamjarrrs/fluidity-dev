<?php

namespace App\Http\Controllers;

use App\Models\SessionReview;
use App\Models\StudySession;
use App\Services\GeminiService;
use App\Services\SM2SchedulerEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudySessionController extends Controller
{
    /**
     * Masuk ke Study Room — halaman sesi belajar interaktif
     * Flow: Pomodoro Timer → Active Recall → Feynman Explanation → AI Feedback
     */
    public function room(StudySession $session): View
    {
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke sesi ini.');
        }

        $session->load(['topic.course', 'review']);

        // Jika sesi sudah selesai, redirect ke halaman hasil
        if ($session->status === 'completed' && $session->review) {
            return view('session.result', [
                'session' => $session,
                'review' => $session->review,
            ]);
        }

        // Tandai sesi sedang berjalan
        if ($session->status === 'scheduled') {
            $session->update(['status' => 'in_progress']);
        }

        // Ambil rangkuman & bank pertanyaan yang sudah tersimpan di database agar loading instan
        $aiSummary = $session->topic->ai_summary ?? '';
        $allQuestions = $session->topic->ai_questions ?? [];

        // Hanya panggil Gemini jika bank pertanyaan belum pernah disimpan (mis. topik lama)
        if (empty($allQuestions) || empty($aiSummary)) {
            $gemini = app(GeminiService::class);
            $moduleContent = $aiSummary ?: $session->topic->title;
            $pdfAbsolutePath = empty($aiSummary) && $session->topic->module_file_path
                ? storage_path('app/public/' . $session->topic->module_file_path)
                : null;

            $aiResult = $gemini->processModule($moduleContent, $session->topic->title, $pdfAbsolutePath);
            $allQuestions = $aiResult['questions'] ?? [];

            $updates = ['ai_questions' => $allQuestions];
            if (! empty($aiResult['summary'])) {
                $aiSummary = $aiResult['summary'];
                $updates['ai_summary'] = $aiSummary;
            }
            $session->topic->update($updates);
        }

        // Prioritaskan 3 soal yang belum pernah dijawab di sesi sebelumnya untuk topik ini
        $answeredQuestions = SessionReview::whereHas('studySession', function ($q) use ($session) {
            $q->where('topic_id', $session->topic_id);
        })->pluck('generated_question')->filter()->all();

        $unanswered = array_values(array_diff($allQuestions, $answeredQuestions));
        if (count($unanswered) >= 3) {
            $questions = array_slice($unanswered, 0, 3);
        } elseif (! empty($unanswered)) {
            $remaining = array_values(array_diff($allQuestions, $unanswered));
            shuffle($remaining);
            $questions = array_slice(array_merge($unanswered, $remaining), 0, 3);
        } else {
            $shuffled = $allQuestions;
            shuffle($shuffled);
            $questions = array_slice($shuffled, 0, 3);
        }

        return view('session.room', [
            'session' => $session,
            'topic' => $session->topic,
            'course' => $session->topic->course,
            'aiSummary' => $aiSummary,
            'questions' => $questions,
        ]);
    }

    /**
     * Buat 3 pertanyaan Active Recall baru via Gemini (dipanggil tombol Buat Soal Baru)
     */
    public function refreshQuestions(StudySession $session, GeminiService $geminiService): JsonResponse
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->load('topic');
        $existing = $session->topic->ai_questions ?? [];
        $freshQuestions = $geminiService->generateFreshQuestions($session->topic, $existing);

        if (! empty($freshQuestions)) {
            $merged = array_values(array_unique(array_merge($existing, $freshQuestions)));
            $session->topic->update(['ai_questions' => array_slice($merged, -18)]);
        }

        return response()->json([
            'questions' => $freshQuestions,
        ]);
    }

    /**
     * Submit penjelasan Feynman & terima evaluasi AI
     */
    public function submitFeynman(Request $request, StudySession $session, GeminiService $geminiService, SM2SchedulerEngine $scheduler): RedirectResponse
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'explanation' => ['required', 'string', 'min:20', 'max:5000'],
            'pomodoro_completed' => ['required', 'boolean'],
            'time_spent_minutes' => ['required', 'integer', 'min:1'],
        ], [
            'explanation.min' => 'Penjelasan konsep minimal 20 karakter. Coba jelaskan lebih detail ya!',
            'explanation.required' => 'Silakan tulis penjelasan konsep dengan bahasa sendiri (Teknik Feynman).',
        ]);

        $session->load('topic');

        // Panggil Gemini AI untuk evaluasi Feynman
        $evaluation = $geminiService->evaluateFeynman(
            $session->topic,
            $validated['question'],
            $validated['explanation']
        );

        // Simpan review sesi belajar
        $review = SessionReview::create([
            'study_session_id' => $session->id,
            'pomodoro_completed' => (bool) $validated['pomodoro_completed'],
            'generated_question' => $validated['question'],
            'student_explanation' => $validated['explanation'],
            'ai_feedback' => $evaluation['feedback'],
            'score_awarded' => $evaluation['score'],
            'time_spent_minutes' => (int) $validated['time_spent_minutes'],
        ]);

        // Update mastery topik dengan algoritma SM-2
        $nextReview = $scheduler->calculateNextReview($session->topic, $evaluation['score']);
        $session->topic->update($nextReview);

        // Tandai sesi selesai
        $session->update(['status' => 'completed']);

        return redirect()->route('session.result', $session->id)
            ->with('success', 'Sesi belajar selesai! Lihat feedback dan skor Anda.');
    }

    /**
     * Halaman hasil sesi belajar — feedback AI & skor
     */
    public function result(StudySession $session): View
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $session->load(['topic.course', 'review']);

        if (! $session->review) {
            return redirect()->route('session.room', $session->id)
                ->with('info', 'Sesi ini belum diselesaikan. Lanjutkan belajar!');
        }

        return view('session.result', [
            'session' => $session,
            'review' => $session->review,
        ]);
    }
}
