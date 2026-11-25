<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $courses = $user->courses()->with('topics')->latest()->get();
        $upcomingSessions = $user->studySessions()
            ->with(['topic.course'])
            ->where('scheduled_start', '>=', now()->subHours(2))
            ->orderBy('scheduled_start')
            ->take(5)
            ->get();

        $calendarConnected = ! empty($user->google_access_token) || ! empty($user->google_refresh_token);

        return view('dashboard', [
            'user' => $user,
            'courses' => $courses,
            'upcomingSessions' => $upcomingSessions,
            'calendarConnected' => $calendarConnected,
        ]);
    }
}
