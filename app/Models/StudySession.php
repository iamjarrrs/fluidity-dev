<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model StudySession (Sesi Belajar Terjadwal).
 * Merepresentasikan satu slot waktu belajar (60 menit) untuk satu topik,
 * termasuk ID event yang tersinkron di Google Calendar.
 */
class StudySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'topic_id',
        'scheduled_start',
        'scheduled_end',
        'duration_minutes',
        'status',
        'google_calendar_event_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(SessionReview::class);
    }
}
