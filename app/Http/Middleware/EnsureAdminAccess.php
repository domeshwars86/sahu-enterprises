<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->adminRole) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        }

        if ($user->must_change_password && ! $request->routeIs('admin.password.change*')) {
            return redirect()->route('admin.password.change');
        }

        if (in_array($user->adminRole->slug, ['super-admin', 'admin'], true)) {
            if (! $user->two_factor_enabled && ! $request->routeIs('admin.security.setup*')) {
                return redirect()->route('admin.security.setup');
            }

            $verifiedAt = (int) $request->session()->get('admin_mfa_verified_at', 0);
            if ($user->two_factor_enabled && ($verifiedAt === 0 || now()->timestamp - $verifiedAt > 8 * 60 * 60) && ! $request->routeIs('admin.security.challenge*', 'admin.security.verify')) {
                $request->session()->forget('admin_mfa_verified_at');

                return redirect()->route('admin.security.challenge');
            }
        }

        return $next($request);
    }
}
