<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Topic (Bab / Topik Materi).
 * Menyimpan daftar file modul, rangkuman & bank soal AI, bobot kesulitan,
 * serta parameter algoritma Spaced Repetition SM-2 (mastery, ease_factor, interval).
 */
class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'difficulty_weight',
        'module_file_path',
        'module_files',
        'ai_summary',
        'ai_questions',
        'mastery_level',
        'ease_factor',
        'repetition_count',
        'interval_days',
        'last_reviewed_at',
        'next_review_due',
    ];

    protected function casts(): array
    {
        return [
            'difficulty_weight' => 'integer',
            'module_files' => 'array',
            'ai_questions' => 'array',
            'mastery_level' => 'integer',
            'ease_factor' => 'float',
            'repetition_count' => 'integer',
            'interval_days' => 'integer',
            'last_reviewed_at' => 'datetime',
            'next_review_due' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    /**
     * Kembalikan semua file modul (mendukung format baru multi-file maupun format lama single-file)
     *
     * @return array<int, array{path: string, name: string}>
     */
    public function allModuleFiles(): array
    {
        if (! empty($this->module_files) && is_array($this->module_files)) {
            return $this->module_files;
        }

        if (! empty($this->module_file_path)) {
            return [
                [
                    'path' => $this->module_file_path,
                    'name' => basename($this->module_file_path),
                ],
            ];
        }

        return [];
    }
}
