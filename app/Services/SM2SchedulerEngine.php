<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mesin Penjadwalan Adaptif Fluidity (Adaptasi SuperMemo SM-2 + Exam-Proximity Compression).
 * Mengatur prioritas belajar berdasarkan bobot kesulitan, tingkat penguasaan (mastery),
 * dan sisa hari menuju ujian, sambil menghindari bentrok jadwal sibuk & mencegah burnout.
 */
class SM2SchedulerEngine
{
    /**
     * Durasi standar 1 sesi belajar (50 menit Pomodoro + 10 menit Active Recall/Feynman)
     */
    public const SESSION_DURATION_MINUTES = 60;
    public const MAX_SESSIONS_PER_DAY = 2;

    /**
     * 1. Menghitung Urgency & Priority Score sebuah topik materi.
     * Menggabungkan Bobot Materi (1-5), Kelemahan Penguasaan (1-5), dan Sisa Hari ke Ujian.
     */
    public function calculatePriority(Topic $topic): float
    {
        $daysToExam = $this->getDaysToExam($topic);

        // Bobot kesulitan: 1 (mudah) s.d. 5 (sangat berat) -> skala 0.2 s.d. 1.0
        $difficultyFactor = max(1, min(5, (int) $topic->difficulty_weight)) / 5.0;

        // Faktor kelemahan: semakin rendah mastery (1), semakin tinggi nilai weakness-nya (1.0)
        // Mastery 1 -> 1.0, Mastery 5 -> 0.2
        $weaknessFactor = (6 - max(1, min(5, (int) $topic->mastery_level))) / 5.0;

        // Faktor urgensi tanggal ujian:
        // Jika ujian <= 3 hari lagi -> urgensi 1.0 (sangat mendesak)
        // Jika ujian 30 hari lagi -> urgensi 0.1
        if ($daysToExam <= 3) {
            $urgencyFactor = 1.0;
        } else {
            $urgencyFactor = max(0.1, min(1.0, 1.0 - ($daysToExam / 30.0)));
        }

        // Formula Gabungan: 40% Kelemahan + 35% Urgensi Ujian + 25% Bobot Kesulitan
        $priorityScore = (0.40 * $weaknessFactor) + (0.35 * $urgencyFactor) + (0.25 * $difficultyFactor);

        return round($priorityScore, 4);
    }

    /**
     * 2. Adaptasi SM-2: Menghitung jadwal review berikutnya setelah mahasiswa menyelesaikan sesi Feynman.
     * $quality: skor 1 s.d. 5 yang diberikan oleh Gemini.
     */
    public function calculateNextReview(Topic $topic, int $quality): array
    {
        $quality = max(1, min(5, $quality));
        $repetitions = (int) $topic->repetition_count;
        $ease = (float) $topic->ease_factor;
        $interval = (int) $topic->interval_days;

        // Algoritma SM-2 inti
        if ($quality >= 3) {
            $repetitions++;

            if ($repetitions === 1) {
                $interval = 1; // Ulangi besok
            } elseif ($repetitions === 2) {
                $interval = 3; // Ulangi 3 hari lagi (disesuaikan untuk mahasiswa)
            } else {
                $interval = (int) round($interval * $ease);
            }
        } else {
            // Jika skor di bawah 3, reset repetisi agar diulang segera
            $repetitions = 0;
            $interval = 1;
        }

        // Update Ease Factor (rumus SuperMemo SM-2)
        $ease += 0.1 - ((5 - $quality) * (0.08 + ((5 - $quality) * 0.02)));
        $ease = max(1.30, round($ease, 2));

        // KOMPRESI JADWAL MENJELANG UJIAN (Exam-Proximity Compression):
        // Interval tidak boleh melebihi sisa hari menuju ujian!
        $daysToExam = $this->getDaysToExam($topic);
        if ($daysToExam > 0) {
            // Batasi interval maksimal setengah dari sisa hari ujian
            $maxAllowedInterval = max(1, (int) floor($daysToExam / 2));
            $interval = min($interval, $maxAllowedInterval);

            // Jika ujian tinggal <= 3 hari, ulangi setiap hari (interval = 1)
            if ($daysToExam <= 3) {
                $interval = 1;
            }
        }

        // Update tingkat penguasaan (Mastery Level 1-5)
        $newMastery = (int) $topic->mastery_level;
        if ($quality >= 4 && $newMastery < 5) {
            $newMastery++;
        } elseif ($quality <= 2 && $newMastery > 1) {
            $newMastery--;
        }

        $nextReviewDue = Carbon::now('Asia/Jakarta')->addDays($interval)->startOfHour();

        return [
            'mastery_level' => $newMastery,
            'ease_factor' => $ease,
            'repetition_count' => $repetitions,
            'interval_days' => $interval,
            'next_review_due' => $nextReviewDue,
        ];
    }

    /**
     * 3. Membuat rencana slot belajar ke kalender mahasiswa.
     * Batasan ketat: Maksimal 2 sesi per hari agar mahasiswa tidak burnout.
     */
    public function generateSchedulePlan(User $user, Collection $topics, array $busySlots = [], int $horizonDays = 14): array
    {
        if ($topics->isEmpty()) {
            return [];
        }

        // Urutkan topik dari yang paling prioritas (skor tertinggi)
        $sortedTopics = $topics->sortByDesc(fn (Topic $t) => $this->calculatePriority($t))->values();

        $schedulePlan = [];
        $sessionsCountPerDay = []; // Tracker harian untuk mencegah > 2 sesi per hari

        $windowStart = $user->study_window_start ?? '08:00';
        $windowEnd = $user->study_window_end ?? '22:00';
        $maxDaily = $user->max_daily_sessions ?? self::MAX_SESSIONS_PER_DAY;

        $topicIndex = 0;
        $now = Carbon::now('Asia/Jakarta');

        for ($day = 0; $day < $horizonDays; $day++) {
            $currentDate = $now->copy()->addDays($day);
            $dateKey = $currentDate->toDateString();
            $sessionsCountPerDay[$dateKey] = 0;

            // Filter hanya topik yang tanggal ujiannya >= tanggal yang sedang dijadwalkan
            // (Jangan menjadwalkan materi jika ujiannya sudah lewat!)
            $eligibleTopics = $sortedTopics->filter(function (Topic $t) use ($currentDate) {
                if (! $t->course || ! $t->course->exam_date) {
                    return true;
                }
                $examDay = Carbon::parse($t->course->exam_date)->startOfDay();
                return $currentDate->copy()->startOfDay()->lte($examDay);
            })->values();

            if ($eligibleTopics->isEmpty()) {
                // Semua mata kuliah sudah melewati tanggal ujian
                break;
            }

            // Jam mulai belajar hari ini
            $cursorTime = $currentDate->copy()->setTimeFromTimeString($windowStart);
            $limitTime = $currentDate->copy()->setTimeFromTimeString($windowEnd);

            // Jika hari ini ($day === 0), jangan jadwalkan jam yang sudah lewat
            if ($day === 0 && $cursorTime->lt($now)) {
                $roundedNow = $now->copy()->addMinutes(30 - ($now->minute % 30))->second(0);
                $cursorTime = $roundedNow;
            }

            while ($cursorTime->copy()->addMinutes(self::SESSION_DURATION_MINUTES)->lte($limitTime)) {
                // Cek apakah kuota hari ini sudah mencapai batas (maks 2 sesi)
                if ($sessionsCountPerDay[$dateKey] >= $maxDaily) {
                    break;
                }

                $slotStart = $cursorTime->copy();
                $slotEnd = $cursorTime->copy()->addMinutes(self::SESSION_DURATION_MINUTES);

                // Cek bentrok dengan jadwal sibuk user (dari Google Calendar)
                if (! $this->isSlotBusy($slotStart, $slotEnd, $busySlots)) {
                    $selectedTopic = $eligibleTopics[$topicIndex % $eligibleTopics->count()];
                    $priority = $this->calculatePriority($selectedTopic);

                    $schedulePlan[] = [
                        'topic_id' => $selectedTopic->id,
                        'topic_title' => $selectedTopic->title,
                        'course_name' => $selectedTopic->course->name ?? 'Course',
                        'scheduled_start' => $slotStart->toDateTimeString(),
                        'scheduled_end' => $slotEnd->toDateTimeString(),
                        'duration_minutes' => self::SESSION_DURATION_MINUTES,
                        'priority_score' => $priority,
                        'reason' => sprintf(
                            'Bobot %d/5 · Penguasaan %d/5 · Ujian %d hari lagi',
                            $selectedTopic->difficulty_weight,
                            $selectedTopic->mastery_level,
                            $this->getDaysToExam($selectedTopic)
                        ),
                    ];

                    $sessionsCountPerDay[$dateKey]++;
                    $topicIndex++;

                    // Lompat ke slot berikutnya dengan jeda 60 menit
                    $cursorTime->addMinutes(self::SESSION_DURATION_MINUTES + 60);
                } else {
                    // Geser 30 menit jika bentrok
                    $cursorTime->addMinutes(30);
                }
            }
        }

        return $schedulePlan;
    }

    private function getDaysToExam(Topic $topic): int
    {
        if (! $topic->course || ! $topic->course->exam_date) {
            return 30;
        }

        $examDate = Carbon::parse($topic->course->exam_date)->startOfDay();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();

        return max(0, (int) $today->diffInDays($examDate, false));
    }

    private function isSlotBusy(Carbon $slotStart, Carbon $slotEnd, array $busySlots): bool
    {
        foreach ($busySlots as $busy) {
            $busyStart = Carbon::parse($busy['start']);
            $busyEnd = Carbon::parse($busy['end']);

            // Overlap jika slotStart < busyEnd DAN slotEnd > busyStart
            if ($slotStart->lt($busyEnd) && $slotEnd->gt($busyStart)) {
                return true;
            }
        }

        return false;
    }
}
