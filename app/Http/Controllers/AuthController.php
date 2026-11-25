<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Scope minimal: info akun dasar + akses event Google Calendar
     */
    private const GOOGLE_SCOPES = [
        'openid',
        'profile',
        'email',
        'https://www.googleapis.com/auth/calendar.events',
    ];

    /**
     * Tampilkan halaman login / landing page
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Redirect mahasiswa ke halaman persetujuan Google OAuth 2.0
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(self::GOOGLE_SCOPES)
            ->with([
                'access_type' => 'offline', // Wajib untuk mendapatkan refresh_token
                'prompt' => 'consent',      // Selalu minta konfirmasi agar refresh_token terkirim
            ])
            ->redirect();
    }

    /**
     * Menangani callback respon dari Google setelah mahasiswa login
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            if (! $googleUser->getEmail()) {
                return redirect()->route('login')->withErrors([
                    'error' => 'Akun Google Anda tidak membagikan alamat email.',
                ]);
            }

            // Cari user berdasarkan google_id atau email
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            $tokenData = [
                'name' => $googleUser->getName() ?: 'Mahasiswa',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'google_access_token' => $googleUser->token,
                'token_expires_at' => now()->addSeconds((int) ($googleUser->expiresIn ?? 3600)),
            ];

            // Refresh token hanya dikirim Google saat persetujuan pertama kali
            if ($googleUser->refreshToken) {
                $tokenData['google_refresh_token'] = $googleUser->refreshToken;
            }

            if ($user) {
                $user->update($tokenData);
            } else {
                $user = User::create(array_merge($tokenData, [
                    'study_window_start' => '08:00',
                    'study_window_end' => '22:00',
                    'max_daily_sessions' => 2,
                ]));
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('success', 'Selamat datang di Fluidity!');
        } catch (Exception $e) {
            Log::error('Google Sign-In Error: ' . $e->getMessage());

            return redirect()->route('login')->withErrors([
                'error' => 'Gagal masuk dengan Google: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Logout pengguna
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda berhasil keluar.');
    }
}
