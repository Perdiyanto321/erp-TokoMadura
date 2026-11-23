<?php

namespace App\Http\Middleware;

use App\Services\LogAktivitasService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Batasi jika status akun nonaktif
        if ($user->status !== 'aktif') {
            Auth::logout();
            return redirect()->route('login')->withErrors(['username' => 'Username atau kata sandi salah.']);
        }

        $userRole = strtolower($user->role?->nama ?? '');
        $allowedRoles = array_map('strtolower', $roles);

        // Periksa apakah peran pengguna diizinkan
        if (!in_array($userRole, $allowedRoles, true)) {
            // Catat penolakan akses ke log aktivitas audit (FR-03, TS-11)
            LogAktivitasService::catat(
                'akses.ditolak',
                'Halaman',
                null,
                [
                    'url' => $request->path(),
                    'method' => $request->method(),
                    'role' => $user->role?->nama,
                    'diperlukan' => implode(', ', $roles),
                ],
                $user
            );

            $roleCapital = ucfirst($user->role?->nama ?? 'Pengguna');
            abort(403, "Akun {$roleCapital} tidak punya akses ke halaman ini. Percobaan ini sudah dicatat.");
        }

        return $next($request);
    }
}
