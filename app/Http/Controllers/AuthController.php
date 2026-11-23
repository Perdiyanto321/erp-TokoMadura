<?php

namespace App\Http\Controllers;

use App\Services\LogAktivitasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['username']) . '|' . $request->ip());

        // Batasi percobaan login maksimal 5 kali per menit (NFR-01, 7.6)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ])->onlyInput('username');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Akun nonaktif diperlakukan sama dengan gagal otentikasi (7.6)
            if ($user->status !== 'aktif') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                RateLimiter::hit($throttleKey, 60);

                LogAktivitasService::catat(
                    'login.gagal',
                    'User',
                    $user->id,
                    ['alasan' => 'Akun nonaktif', 'username' => $credentials['username']]
                );

                return back()->withErrors([
                    'username' => 'Username atau kata sandi salah.',
                ])->onlyInput('username');
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            LogAktivitasService::catat('login.berhasil', 'User', $user->id, ['username' => $user->username], $user);

            return $this->redirectBasedOnRole($user);
        }

        RateLimiter::hit($throttleKey, 60);

        LogAktivitasService::catat(
            'login.gagal',
            'User',
            null,
            ['alasan' => 'Kredensial salah', 'username' => $credentials['username']]
        );

        return back()->withErrors([
            'username' => 'Username atau kata sandi salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            LogAktivitasService::catat('logout', 'User', $user->id, [], $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectBasedOnRole($user): RedirectResponse
    {
        $role = strtolower($user->role?->nama ?? '');

        return match ($role) {
            'kasir' => redirect()->route('kasir.index'),
            'gudang' => redirect()->route('stok.index'),
            'keuangan', 'admin' => redirect()->route('dashboard'),
            default => redirect()->route('dashboard'),
        };
    }
}
