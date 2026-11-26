<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller halaman utama Dashboard mahasiswa.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan ringkasan mata kuliah, sesi belajar terdekat, dan status Google Calendar.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ambil seluruh mata kuliah beserta jumlah babnya
        $courses = $user->courses()->with('topics')->latest()->get();

        // Ambil maksimal 5 sesi belajar terdekat (termasuk yang baru dimulai dalam 2 jam terakhir)
        $upcomingSessions = $user->studySessions()
            ->with(['topic.course'])
            ->where('scheduled_start', '>=', now()->subHours(2))
            ->orderBy('scheduled_start')
            ->take(5)
            ->get();

        // Cek apakah token Google OAuth tersedia untuk sinkronisasi Google Calendar
        $calendarConnected = ! empty($user->google_access_token) || ! empty($user->google_refresh_token);

        // Sesi berikutnya yang belum selesai (untuk kartu "Sesi berikutnya")
        $nextSession = $upcomingSessions->firstWhere('status', '!=', 'completed');

        // Ringkasan minggu ini
        $weekSessions = $user->studySessions()
            ->whereBetween('scheduled_start', [now()->startOfWeek(), now()->endOfWeek()])
            ->get(['status', 'duration_minutes']);
        $completedWeek = $weekSessions->where('status', 'completed');

        $reviewDue = $courses->flatMap->topics
            ->filter(fn ($topic) => $topic->next_review_due && $topic->next_review_due->lte(now()))
            ->count();

        // Ujian yang belum lewat, terdekat lebih dulu
        $upcomingExams = $courses
            ->filter(fn ($course) => $course->exam_date->gte(today()))
            ->sortBy('exam_date')
            ->values();

        return view('dashboard', [
            'user' => $user,
            'courses' => $courses,
            'upcomingSessions' => $upcomingSessions,
            'calendarConnected' => $calendarConnected,
            'nextSession' => $nextSession,
            'weekCompleted' => $completedWeek->count(),
            'weekTotal' => $weekSessions->count(),
            'weekHours' => round($completedWeek->sum('duration_minutes') / 60, 1),
            'reviewDue' => $reviewDue,
            'upcomingExams' => $upcomingExams,
            'focusCourse' => $upcomingExams->first(),
        ]);
    }
}
