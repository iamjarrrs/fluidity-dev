@extends('layouts.app')

@section('title', 'Fluidity - Masuk dengan Akun Google')

@section('content')
<div class="min-h-[75vh] flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-2xl shadow-sm border border-slate-200">
        
        <!-- Header & Logo -->
        <div class="text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center text-white text-2xl font-black shadow-md shadow-blue-200">
                F
            </div>
            <h2 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">
                Selamat Datang di Fluidity
            </h2>
            <p class="mt-2 text-sm text-slate-600">
                Platform panduan belajar mandiri adaptif berbasis AI dan kalender pintar untuk mahasiswa.
            </p>
        </div>

        <!-- Google Sign-In Button -->
        <div class="mt-8 space-y-4">
            <a href="{{ route('auth.google') }}" 
               class="w-full flex items-center justify-center gap-3 px-5 py-3.5 border border-slate-300 rounded-xl shadow-sm bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <!-- Official Google 'G' SVG Logo -->
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                Masuk dengan Google
            </a>

            <div class="text-center text-xs text-slate-400">
                Terintegrasi aman dengan Google Calendar & Google OAuth 2.0
            </div>
        </div>

        <!-- 3 Core Pillars Highlight -->
        <div class="pt-6 border-t border-slate-100 space-y-3">
            <div class="flex items-start space-x-3 text-xs text-slate-600">
                <span class="text-blue-600 font-bold">✓</span>
                <span><strong>Otomatisasi Kalender:</strong> Jadwal belajar langsung sinkron dengan waktu luang Anda.</span>
            </div>
            <div class="flex items-start space-x-3 text-xs text-slate-600">
                <span class="text-blue-600 font-bold">✓</span>
                <span><strong>AI Active Recall & Feynman:</strong> Uji konsep dari modul dosen dengan Gemini AI.</span>
            </div>
            <div class="flex items-start space-x-3 text-xs text-slate-600">
                <span class="text-blue-600 font-bold">✓</span>
                <span><strong>Anti-Burnout:</strong> Dibatasi maksimal 2 sesi per hari dengan interval adaptif.</span>
            </div>
        </div>

    </div>
</div>
@endsection
