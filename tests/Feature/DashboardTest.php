<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StudySession;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_without_courses(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Belum ada sesi yang dijadwalkan');
    }

    public function test_dashboard_renders_next_session_exams_and_mastery(): void
    {
        $user = User::factory()->create();
        $course = Course::forceCreate([
            'user_id' => $user->id, 'name' => 'Kalkulus II', 'exam_date' => today()->addDays(5), 'color' => '#0f766e',
        ]);
        $topic = Topic::forceCreate([
            'course_id' => $course->id, 'title' => 'Integral parsial', 'mastery_level' => 2,
            'next_review_due' => now()->subDay(),
        ]);
        StudySession::forceCreate([
            'user_id' => $user->id, 'topic_id' => $topic->id, 'status' => 'scheduled', 'duration_minutes' => 25,
            'scheduled_start' => now()->addHour(), 'scheduled_end' => now()->addHour()->addMinutes(25),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Kalkulus II: Integral parsial')
            ->assertSee('5 hari')
            ->assertSee('40% · Lemah', false)
            ->assertSee('Mulai sesi');
    }
}
