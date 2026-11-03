<?php

namespace App\Http\Middleware;

use App\Models\UserPref;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $prefs = UserPref::payloadFor($request->user());

        View::share('appearance', $prefs['theme']);
        View::share('font', $prefs['font']);
        View::share('noteColor', $prefs['noteColor']);

        return $next($request);
    }
}
