<?php

namespace App\Http\Controllers;

use App\Models\StudySession;
use App\Models\Topic;
use App\Services\GoogleCalendarService;
use App\Services\SM2SchedulerEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Preview jadwal belajar yang dihasilkan algoritma SM-2
     * Membaca slot sibuk dari Google Calendar mahasiswa agar tidak bentrok
     */
    public function preview(Request $request, SM2SchedulerEngine $scheduler, GoogleCalendarService $calendarService): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ambil semua topik dari semua mata kuliah user yang ujiannya belum lewat
        $topics = Topic::whereHas('course', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->where('exam_date', '>=', now()->toDateString());
        })->with('course')->get();

        if ($topics->isEmpty()) {
            return redirect()->route('dashboard')
                ->with('info', 'Belum ada mata kuliah dengan tanggal ujian mendatang. Tambahkan mata kuliah terlebih dahulu.');
        }

        // Ambil slot sibuk dari Google Calendar jika terhubung
        $calendarConnected = ! empty($user->google_access_token) || ! empty($user->google_refresh_token);
        $busySlots = $calendarConnected ? $calendarService->getBusySlots($user, 14) : [];

        // Generate rencana jadwal dari SM2 engine dengan menghindari jadwal sibuk
        $schedulePlan = $scheduler->generateSchedulePlan($user, $topics, $busySlots, 14);

        return view('schedule.preview', [
            'user' => $user,
            'schedulePlan' => $schedulePlan,
            'topics' => $topics,
            'calendarConnected' => $calendarConnected,
            'busyCount' => count($busySlots),
        ]);
    }

    /**
     * Konfirmasi & simpan jadwal belajar ke database + Google Calendar
     */
    public function confirm(Request $request, GoogleCalendarService $calendarService): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'sessions' => ['required', 'array', 'min:1'],
            'sessions.*.topic_id' => ['required', 'integer', 'exists:topics,id'],
            'sessions.*.scheduled_start' => ['required', 'date'],
            'sessions.*.scheduled_end' => ['required', 'date'],
            'sessions.*.duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        // Verifikasi bahwa semua topik milik user ini
        $topicIds = collect($validated['sessions'])->pluck('topic_id')->unique();
        $ownedTopicCount = Topic::whereIn('id', $topicIds)
            ->whereHas('course', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        if ($ownedTopicCount !== $topicIds->count()) {
            return back()->withErrors(['sessions' => 'Beberapa topik tidak valid atau bukan milik Anda.']);
        }

        // Hapus sesi scheduled lama yang belum dimulai (beserta event-nya di Google Calendar)
        $oldSessions = StudySession::where('user_id', $user->id)
            ->where('status', 'scheduled')
            ->where('scheduled_start', '>=', now())
            ->get();

        foreach ($oldSessions as $oldSession) {
            if ($oldSession->google_calendar_event_id) {
                $calendarService->deleteStudyEvent($user, $oldSession->google_calendar_event_id);
            }
            $oldSession->delete();
        }

        $syncedCalendarCount = 0;

        foreach ($validated['sessions'] as $sessionData) {
            $session = StudySession::create([
                'user_id' => $user->id,
                'topic_id' => $sessionData['topic_id'],
                'scheduled_start' => $sessionData['scheduled_start'],
                'scheduled_end' => $sessionData['scheduled_end'],
                'duration_minutes' => $sessionData['duration_minutes'],
                'status' => 'scheduled',
            ]);

            $eventId = $calendarService->createStudyEvent($user, $session);
            if ($eventId) {
                $session->update(['google_calendar_event_id' => $eventId]);
                $syncedCalendarCount++;
            }
        }

        $count = count($validated['sessions']);
        if ($calendarService->scopeInsufficient) {
            return redirect()->route('dashboard')->with('info', "{$count} sesi belajar tersimpan di Fluidity, namun gagal masuk ke Google Calendar karena izin akses kalender belum dicentang saat login. Silakan klik Keluar, lalu Masuk dengan Google lagi dan pastikan mencentang izin Google Calendar.");
        }

        $message = $syncedCalendarCount > 0
            ? "{$count} sesi belajar berhasil dijadwalkan & {$syncedCalendarCount} event disinkronkan ke Google Calendar Anda!"
            : "{$count} sesi belajar berhasil dijadwalkan! Lihat jadwal terdekat di bawah.";

        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Simpan preferensi jam luang belajar mahasiswa (study_window_start, study_window_end, max_daily_sessions)
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'study_window_start' => ['required', 'date_format:H:i'],
            'study_window_end' => ['required', 'date_format:H:i', 'after:study_window_start'],
            'max_daily_sessions' => ['required', 'integer', 'min:1', 'max:4'],
        ], [
            'study_window_end.after' => 'Jam selesai belajar harus lebih malam daripada jam mulai belajar.',
        ]);

        $user->update([
            'study_window_start' => $validated['study_window_start'],
            'study_window_end' => $validated['study_window_end'],
            'max_daily_sessions' => (int) $validated['max_daily_sessions'],
        ]);

        return back()->with('success', "Preferensi jam luang belajar diperbarui ({$validated['study_window_start']} - {$validated['study_window_end']} WIB, maks {$validated['max_daily_sessions']} sesi/hari). Silakan klik Generate Jadwal untuk menyesuaikan jadwal!");
    }
}
