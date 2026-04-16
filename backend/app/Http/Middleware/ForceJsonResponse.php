<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces all API requests to expect a JSON response.
 *
 * Without this, Laravel checks $request->expectsJson() before deciding
 * whether to return JSON or HTML for exceptions. A client that sends
 * no Accept header fails that check and gets HTML — even on /api routes.
 *
 * This middleware sets Accept: application/json on every request before
 * it hits any handler, so the check always passes.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
