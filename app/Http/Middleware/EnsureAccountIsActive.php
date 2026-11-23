<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Memeriksa apakah akun pengguna yang sedang login aktif.
     * Jika akun dinonaktifkan, sesi langsung dikeluarkan otomatis.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('masuk')->withErrors([
                'login' => 'Akun kamu dinonaktifkan. Hubungi admin untuk informasi lebih lanjut.',
            ]);
        }

        return $next($request);
    }
}
