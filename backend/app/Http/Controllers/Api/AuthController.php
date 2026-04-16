<?php

namespace App\Http\Controllers\Api;

use App\Domain\Loyalty\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * AuthController
 *
 * JWT-based authentication for both customers and admins.
 *
 * Token lifecycle:
 *   - POST /auth/register  → issues a fresh JWT
 *   - POST /auth/login     → issues a fresh JWT (blacklists old token if present)
 *   - POST /auth/logout    → blacklists the current token (stateless invalidation)
 *   - POST /auth/refresh   → rotates the token (issues new, blacklists old)
 *   - GET  /auth/me        → decodes token, returns user from DB
 *
 * The JWT payload carries { sub, role, email, iat, exp } — any microservice
 * that shares the JWT_SECRET can verify and authorise requests without
 * a database call or round-trip to this service.
 */
class AuthController extends Controller
{

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string',
        ]);

        $user  = User::create([...$data, 'role' => 'customer']);
        $token = JWTAuth::fromUser($user);

        return $this->created('Registration successful', $this->formatUser($user))
            ->withHeaders($this->tokenHeaders($token));
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return $this->unauthorized('The provided credentials are incorrect.');
        }

        return $this->success('Login successful', $this->formatUser(auth('api')->user()))
            ->withHeaders($this->tokenHeaders($token));
    }


    public function logout(): JsonResponse
    {
        // Adds the current token's JTI to the blacklist.
        // The blacklist is stored in the cache driver (Redis in our stack).
        // Token remains cryptographically valid but will be rejected on next use.
        auth('api')->logout();

        return $this->noContent('Logged out successfully.');
    }


    /**
     * Rotate the token — blacklists the current one and issues a new one.
     * The frontend should call this before the token expires (JWT_REFRESH_TTL).
     * Implement a silent refresh in your React app using an axios interceptor.
     */
    public function refresh(): JsonResponse
    {
        $newToken = auth('api')->refresh();

        return $this->success('Token refreshed.')
            ->withHeaders($this->tokenHeaders($newToken));
    }


    public function me(): JsonResponse
    {
        $user = auth('api')->user()->load(['currentBadge.badge']);

        return $this->success('Authenticated user.', $this->formatUser($user));
    }

    // Helpers

    /**
     * Return the token in the Authorization header (Bearer scheme) AND in
     * the response body. The header approach is preferred for microservice
     * clients; the body value is convenient for frontend localStorage.
     */
    private function tokenHeaders(string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            // Expose TTL so the client knows when to refresh
            'X-Token-TTL'   => config('jwt.ttl'),
        ];
    }

    private function formatUser(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'role'           => $user->role,
            'loyalty_points' => (float) $user->loyalty_points,
            'total_spent'    => (float) $user->total_spent,
            'current_badge'  => $user->currentBadge?->badge ? [
                'name'             => $user->currentBadge->badge->name,
                'icon'             => $user->currentBadge->badge->icon,
                'cashback_percent' => $user->currentBadge->badge->cashback_percent,
            ] : null,
        ];
    }
}
