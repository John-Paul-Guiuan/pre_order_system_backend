<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Always tell Laravel to respond as JSON
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
