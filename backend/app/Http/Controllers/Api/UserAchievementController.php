<?php

namespace App\Http\Controllers\Api;

use App\Domain\Loyalty\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UserAchievementController
 *
 * GET /api/users/{user}/achievements
 *
 * Returns the full loyalty profile for a user:
 *   - Unlocked achievements (with timestamps)
 *   - Current badge + badge history
 *   - Points summary
 *   - Progress toward next badge tier
 *
 * Authorization: a customer can only view their own profile.
 * Admins can view any user's profile via the admin endpoint.
 */
class UserAchievementController extends Controller
{
    public function show(Request $request, User $user): JsonResponse
    {
        // Customers can only see their own data
        if ($request->user()->id !== $user->id && ! $request->user()->isAdmin()) {
            return $this->forbidden('You do not have permission to view this profile.');
        }

        $user->load([
            'achievements',
            'currentBadge.badge',
            'badges' => fn ($q) => $q->withPivot(['is_current', 'earned_at'])->with('badge'),
        ]);

        $currentBadge = $user->currentBadge?->badge;
        $nextBadge    = $this->getNextBadge($user->loyalty_points, $currentBadge?->level ?? 0);

        return $this->success('Loyalty profile retrieved.', [
            'user' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'loyalty_points' => (float) $user->loyalty_points,
                'total_spent'    => (float) $user->total_spent,
            ],
            'current_badge' => $currentBadge ? [
                'id'               => $currentBadge->id,
                'name'             => $currentBadge->name,
                'slug'             => $currentBadge->slug,
                'description'      => $currentBadge->description,
                'icon'             => $currentBadge->icon,
                'cashback_percent' => (float) $currentBadge->cashback_percent,
                'level'            => $currentBadge->level,
            ] : null,
            'next_badge' => $nextBadge ? [
                'name'             => $nextBadge->name,
                'min_points'       => $nextBadge->min_points,
                'points_needed'    => max(0, $nextBadge->min_points - $user->loyalty_points),
                'progress_percent' => $this->progressPercent(
                    $user->loyalty_points,
                    $currentBadge?->min_points ?? 0,
                    $nextBadge->min_points
                ),
            ] : null,
            'achievements' => $user->achievements->map(fn($a) => [
                'id'            => $a->id,
                'name'          => $a->name,
                'slug'          => $a->slug,
                'description'   => $a->description,
                'icon'          => $a->icon,
                'points_reward' => $a->points_reward,
                'unlocked_at'   => $a->pivot->unlocked_at,
            ]),
            'badge_history' => $user->badges->map(fn($b) => [
                'name'       => $b->badge->name ?? $b->name,
                'icon'       => $b->badge->icon ?? $b->icon,
                'is_current' => (bool) $b->pivot->is_current,
                'earned_at'  => $b->pivot->earned_at,
            ]),
            'stats' => [
                'total_achievements' => $user->achievements->count(),
                'purchase_count'     => $user->purchases()->where('status', 'completed')->count(),
            ],
        ]);
    }

    private function getNextBadge(float $points, int $currentLevel): ?\App\Domain\Loyalty\Models\Badge
    {
        return \App\Domain\Loyalty\Models\Badge::where('level', '>', $currentLevel)
            ->orderBy('level', 'asc')
            ->first();
    }

    private function progressPercent(float $current, float $floor, float $ceiling): float
    {
        if ($ceiling <= $floor) return 100.0;
        return min(100, round((($current - $floor) / ($ceiling - $floor)) * 100, 1));
    }
}
