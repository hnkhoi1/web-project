<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the shared demo account from changing anything about itself.
 */
class BlockDemoAccount
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDemo()) {
            return back()->withErrors([
                'demo' => 'Tài khoản demo không thể thay đổi thông tin tài khoản.',
            ]);
        }

        return $next($request);
    }
}
