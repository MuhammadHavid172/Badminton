<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Memeriksa apakah pengguna memiliki peran yang diizinkan untuk mengakses rute ini.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('masuk'));
        }

        $userRoleValue = is_object($user->role) ? $user->role->value : (string) $user->role;

        if (!in_array($userRoleValue, $roles, true)) {
            abort(403, 'Kamu tidak memiliki izin untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
