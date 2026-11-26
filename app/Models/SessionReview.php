<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model SessionReview (Hasil Evaluasi Sesi Belajar).
 * Menyimpan pertanyaan Active Recall yang dipilih, penjelasan Feynman mahasiswa,
 * feedback dari Gemini AI, serta skor pemahaman (1-5).
 */
class SessionReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_session_id',
        'pomodoro_completed',
        'generated_question',
        'student_explanation',
        'ai_feedback',
        'score_awarded',
        'time_spent_minutes',
    ];

    protected function casts(): array
    {
        return [
            'pomodoro_completed' => 'boolean',
            'score_awarded' => 'integer',
            'time_spent_minutes' => 'integer',
        ];
    }

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }
}
