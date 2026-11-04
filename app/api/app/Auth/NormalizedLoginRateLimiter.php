<?php

namespace App\Auth;

use App\Providers\FortifyServiceProvider;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

/**
 * Fortify's login limiter keyed on the normalized identifier, so case,
 * surrounding spaces and Unicode composition cannot dodge the lockout.
 */
class NormalizedLoginRateLimiter extends LoginRateLimiter
{
    protected function throttleKey(Request $request)
    {
        return FortifyServiceProvider::normalizeIdentifier($request->input(Fortify::username())).'|'.$request->ip();
    }
}
