<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicScope
{
    /**
     * Handle an incoming request and enforce clinic tenancy scope.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $clinic = null;

        if ($user->isSuperAdmin()) {
            // Superadmin can switch clinic; retrieve from session or default to first
            $sessionClinicId = session('active_clinic_id');
            if ($sessionClinicId) {
                $clinic = Clinic::find($sessionClinicId);
            }

            if (!$clinic) {
                $clinic = Clinic::first();
                if ($clinic) {
                    session(['active_clinic_id' => $clinic->id]);
                }
            }
        } else {
            // Normal Admin / Staff / Doctor is strictly locked to their assigned clinic
            if (!$user->clinic_id || !$user->clinic) {
                abort(403, 'Akun Anda belum ditautkan ke klinik aktif manapun.');
            }

            if (!$user->clinic->is_active) {
                abort(403, 'Klinik Anda saat ini sedang dinonaktifkan.');
            }

            $clinic = $user->clinic;
        }

        if ($clinic) {
            // Bind clinic to container, request attribute, and share across views
            app()->instance('currentClinic', $clinic);
            $request->attributes->set('clinic', $clinic);
            view()->share('clinic', $clinic);
        }

        return $next($request);
    }
}

