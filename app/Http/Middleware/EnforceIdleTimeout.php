<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleTimeout
{
    /**
     * Memeriksa batas waktu ketidakaktifan (idle timeout) khusus untuk sesi Admin dan Owner.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isAdmin() || $user->isOwner()) {
                $timeoutMinutes = config('gor.idle_timeout_minutes', 60);
                $lastActivity = session('admin_last_activity_time');

                if ($lastActivity && (now()->timestamp - $lastActivity) > ($timeoutMinutes * 60)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('masuk')
                        ->with('error', "Sesi kamu telah berakhir otomatis karena tidak ada aktivitas selama {$timeoutMinutes} menit.");
                }

                session(['admin_last_activity_time' => now()->timestamp]);
            }
        }

        return $next($request);
    }
}
