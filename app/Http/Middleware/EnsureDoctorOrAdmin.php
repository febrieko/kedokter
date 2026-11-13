<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctorOrAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (!$user->isDoctor() && !$user->isAdmin())) {
            abort(403, 'Akses khusus untuk Dokter dan Administrator Klinik.');
        }

        return $next($request);
    }
}
