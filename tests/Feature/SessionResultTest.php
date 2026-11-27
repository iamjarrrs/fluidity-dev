<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\SessionReview;
use App\Models\StudySession;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionResultTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(User $user, string $status): StudySession
    {
        $course = Course::forceCreate(['user_id' => $user->id, 'name' => 'Kalkulus II', 'exam_date' => today()->addDays(5), 'color' => '#0f766e']);
        $topic = Topic::forceCreate(['course_id' => $course->id, 'title' => 'Integral parsial', 'mastery_level' => 3, 'interval_days' => 6, 'next_review_due' => now()->addDays(6)]);

        return StudySession::forceCreate([
            'user_id' => $user->id, 'topic_id' => $topic->id, 'status' => $status, 'duration_minutes' => 25,
            'scheduled_start' => now()->subHour(), 'scheduled_end' => now()->subMinutes(35),
        ]);
    }

    public function test_result_without_review_redirects_to_room(): void
    {
        $user = User::factory()->create();
        $session = $this->makeSession($user, 'in_progress');

        $this->actingAs($user)
            ->get("/sessions/{$session->id}/result")
            ->assertRedirect(route('session.room', $session->id));
    }

    public function test_result_shows_score_and_mastery(): void
    {
        $user = User::factory()->create();
        $session = $this->makeSession($user, 'completed');
        SessionReview::forceCreate([
            'study_session_id' => $session->id, 'pomodoro_completed' => true, 'generated_question' => 'Kapan integral parsial dipakai?',
            'student_explanation' => 'Saat integran berupa hasil kali dua fungsi.', 'ai_feedback' => 'Sudah tepat.', 'score_awarded' => 4, 'time_spent_minutes' => 27,
        ]);

        $this->actingAs($user)
            ->get("/sessions/{$session->id}/result")
            ->assertOk()
            ->assertSee('Sesi selesai: Integral parsial')
            ->assertSee('60% · Sedang', false)
            ->assertSee('Kapan integral parsial dipakai?');
    }
}
