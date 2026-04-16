<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminMiddleware
 *
 * Reads the 'role' claim directly from the decoded JWT payload.
 * No database query — the claim was embedded at token-issue time.
 *
 * This is the key microservice advantage: any service sharing JWT_SECRET
 * can enforce role-based access from the token alone.
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        return $next($request);
    }
}
