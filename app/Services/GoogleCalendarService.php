<?php

namespace App\Services;

use App\Models\StudySession;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private const CALENDAR_API_BASE = 'https://www.googleapis.com/calendar/v3';

    public bool $scopeInsufficient = false;

    /**
     * Ambil access token yang valid, atau refresh otomatis jika sudah kedaluwarsa.
     */
    public function getValidAccessToken(User $user): ?string
    {
        if (empty($user->google_access_token)) {
            return null;
        }

        // Jika token masih berlaku lebih dari 2 menit, langsung pakai
        if ($user->token_expires_at && $user->token_expires_at->gt(now()->addMinutes(2))) {
            return $user->google_access_token;
        }

        // Jika kedaluwarsa, coba refresh memakai refresh_token
        if (empty($user->google_refresh_token)) {
            return $user->google_access_token;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $user->google_refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newToken = $data['access_token'] ?? null;

                if ($newToken) {
                    $user->update([
                        'google_access_token' => $newToken,
                        'token_expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 3600)),
                    ]);

                    return $newToken;
                }
            } else {
                Log::warning('Google Token Refresh Failed: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::warning('Google Token Refresh Exception: ' . $e->getMessage());
        }

        return $user->google_access_token;
    }

    /**
     * Ambil daftar slot sibuk (event yang sudah ada) dari Google Calendar utama mahasiswa.
     *
     * @return array<int, array{start: string, end: string, summary: string}>
     */
    public function getBusySlots(User $user, int $horizonDays = 14): array
    {
        $token = $this->getValidAccessToken($user);
        if (! $token) {
            return [];
        }

        $timeMin = Carbon::now('Asia/Jakarta')->startOfDay()->toRfc3339String();
        $timeMax = Carbon::now('Asia/Jakarta')->addDays($horizonDays)->endOfDay()->toRfc3339String();

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get(self::CALENDAR_API_BASE . '/calendars/primary/events', [
                    'timeMin' => $timeMin,
                    'timeMax' => $timeMax,
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                    'maxResults' => 250,
                ]);

            if (! $response->successful()) {
                Log::warning('Google Calendar Events Fetch Failed: ' . $response->body());
                return [];
            }

            $items = $response->json('items', []);
            $busySlots = [];

            foreach ($items as $event) {
                // Abaikan event yang berstatus 'cancelled' atau 'transparent' (Free)
                if (($event['status'] ?? '') === 'cancelled' || ($event['transparency'] ?? '') === 'transparent') {
                    continue;
                }

                // Abaikan event yang dibuat oleh Fluidity sendiri agar tidak bentrok saat re-schedule
                $isFluidity = ($event['extendedProperties']['private']['fluidity_managed'] ?? '') === 'true'
                    || str_starts_with($event['summary'] ?? '', '📚 [Fluidity]');
                if ($isFluidity) {
                    continue;
                }

                // Hanya ambil event dengan jam spesifik (dateTime), bukan all-day event (mis. hari libur)
                $start = $event['start']['dateTime'] ?? null;
                $end = $event['end']['dateTime'] ?? null;

                if ($start && $end) {
                    $busySlots[] = [
                        'start' => $start,
                        'end' => $end,
                        'summary' => $event['summary'] ?? 'Agenda Sibuk',
                    ];
                }
            }

            return $busySlots;
        } catch (Exception $e) {
            Log::warning('Google Calendar Fetch Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Buat event sesi belajar di Google Calendar utama mahasiswa.
     */
    public function createStudyEvent(User $user, StudySession $session): ?string
    {
        $token = $this->getValidAccessToken($user);
        if (! $token) {
            return null;
        }

        $session->loadMissing('topic.course');
        $courseName = $session->topic->course->name ?? 'Mata Kuliah';
        $topicTitle = $session->topic->title ?? 'Materi Belajar';
        $roomUrl = route('session.room', $session->id);

        $start = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session->scheduled_start)->format('Y-m-d H:i:s'), 'Asia/Jakarta')->toRfc3339String();
        $end = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session->scheduled_end)->format('Y-m-d H:i:s'), 'Asia/Jakarta')->toRfc3339String();

        $description = implode("\n", [
            "Sesi Belajar Adaptif — Fluidity",
            "Mata Kuliah: {$courseName}",
            "Topik: {$topicTitle}",
            "Durasi: {$session->duration_minutes} menit (50m Pomodoro + 10m Active Recall & Feynman)",
            "",
            "Mulai sesi belajar di sini:",
            $roomUrl,
        ]);

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post(self::CALENDAR_API_BASE . '/calendars/primary/events', [
                    'summary' => "📚 [Fluidity] {$courseName}: {$topicTitle}",
                    'description' => $description,
                    'start' => [
                        'dateTime' => $start,
                        'timeZone' => 'Asia/Jakarta',
                    ],
                    'end' => [
                        'dateTime' => $end,
                        'timeZone' => 'Asia/Jakarta',
                    ],
                    'colorId' => '9', // Biru Blueberry
                    'extendedProperties' => [
                        'private' => [
                            'fluidity_managed' => 'true',
                            'session_id' => (string) $session->id,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            if ($response->status() === 403 && str_contains($response->body(), 'ACCESS_TOKEN_SCOPE_INSUFFICIENT')) {
                $this->scopeInsufficient = true;
            }

            Log::warning('Google Calendar Create Event Failed: ' . $response->body());
        } catch (Exception $e) {
            Log::warning('Google Calendar Create Event Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Hapus event Fluidity dari Google Calendar (dipakai saat re-schedule).
     */
    public function deleteStudyEvent(User $user, string $eventId): bool
    {
        $token = $this->getValidAccessToken($user);
        if (! $token || empty($eventId)) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->delete(self::CALENDAR_API_BASE . "/calendars/primary/events/{$eventId}");

            return $response->successful() || $response->status() === 404 || $response->status() === 410;
        } catch (Exception $e) {
            Log::warning('Google Calendar Delete Event Exception: ' . $e->getMessage());
            return false;
        }
    }
}
