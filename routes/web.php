<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudySessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Peta URL Utama Aplikasi Fluidity
|--------------------------------------------------------------------------
| - Public routes: Halaman login & callback Google OAuth 2.0
| - Auth routes: Dashboard, CRUD Mata Kuliah, Penjadwalan, dan Study Room
*/

// Halaman Login & Google OAuth
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.show');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Protected Routes (Hanya untuk mahasiswa yang sudah login)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Manajemen Mata Kuliah, Bab, dan Modul Materi
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');

    // Penjadwalan Adaptif (SM-2 Scheduler)
    Route::get('/schedule/preview', [ScheduleController::class, 'preview'])->name('schedule.preview');
    Route::post('/schedule/confirm', [ScheduleController::class, 'confirm'])->name('schedule.confirm');
    Route::post('/preferences/study-window', [ScheduleController::class, 'updatePreferences'])->name('preferences.update');

    // Ruang Belajar (Pomodoro + Active Recall + Feynman)
    Route::get('/sessions/{session}/room', [StudySessionController::class, 'room'])->name('session.room');
    Route::post('/sessions/{session}/refresh-questions', [StudySessionController::class, 'refreshQuestions'])->name('session.refresh_questions');
    Route::post('/sessions/{session}/feynman', [StudySessionController::class, 'submitFeynman'])->name('session.submit');
    Route::get('/sessions/{session}/result', [StudySessionController::class, 'result'])->name('session.result');
});
