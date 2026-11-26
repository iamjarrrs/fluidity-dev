<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Course (Mata Kuliah).
 * Menyimpan identitas mata kuliah, target tanggal ujian, dan warna penanda kartu/kalender.
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'code',
        'exam_date',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
        ];
    }

    /** Mahasiswa pemilik mata kuliah */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Daftar bab / topik materi di dalam mata kuliah ini */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }
}
