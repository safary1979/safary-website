<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        // A verified code is trusted for one session lifetime from the moment it was entered,
        // so continuous activity cannot extend 2FA beyond that.
        $verifiedAt = (int) $request->session()->get('two_factor_verified_at', 0);
        if ($verifiedAt > 0 && now()->timestamp - $verifiedAt < (int) config('session.lifetime') * 60) {
            return $next($request);
        }
        $request->session()->forget('two_factor_verified_at');

        if ($request->routeIs('filament.admin.pages.two-factor-challenge')) {
            return $next($request);
        }

        if ($request->routeIs('default.livewire.update') && str_contains((string) $request->headers->get('referer'), '/admin/two-factor-challenge')) {
            return $next($request);
        }

        return redirect()->route('filament.admin.pages.two-factor-challenge');
    }
}
