<?php

namespace App\Domain\Loyalty\Services;

use App\Domain\Loyalty\Events\BadgeUnlocked;
use App\Domain\Loyalty\Models\Badge;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Models\UserBadge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BadgeService
 *
 * Evaluates whether a user's current loyalty_points qualify them for a
 * higher badge tier after a purchase. If so, it demotes the old "current"
 * badge and promotes the new one, then fires the BadgeUnlocked event.
 *
 * Design note: Badge progression is always upward — users are never demoted.
 * The service simply does nothing if the qualifying badge is the same tier
 * or lower than what the user already holds.
 */
class BadgeService
{
    /**
     * Check and promote badge for a user.
     * Returns the newly earned Badge, or null if no change.
     */
    public function processForUser(User $user): ?Badge
    {
        $user->refresh(); // Ensure loyalty_points reflects achievement point grants

        $qualifyingBadge = Badge::forPoints($user->loyalty_points);

        if (! $qualifyingBadge) {
            return null;
        }

        $currentBadge = $this->getCurrentBadge($user);

        // No promotion needed if user already holds this tier or higher
        if ($currentBadge && $currentBadge->level >= $qualifyingBadge->level) {
            return null;
        }

        $this->promote($user, $qualifyingBadge, $currentBadge);

        return $qualifyingBadge;
    }

    /**
     * Atomically swap the user's current badge.
     */
    private function promote(User $user, Badge $newBadge, ?Badge $previousBadge): void
    {
        DB::transaction(function () use ($user, $newBadge, $previousBadge) {
            $now = Carbon::now();

            // Demote all existing current badges for this user
            UserBadge::where('user_id', $user->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            // Insert the new current badge record
            UserBadge::create([
                'user_id'    => $user->id,
                'badge_id'   => $newBadge->id,
                'is_current' => true,
                'earned_at'  => $now,
            ]);

            Log::info('Badge promoted', [
                'user_id'       => $user->id,
                'new_badge'     => $newBadge->slug,
                'from_badge'    => $previousBadge?->slug ?? 'none',
                'points_at_time' => $user->loyalty_points,
            ]);
        });

        event(new BadgeUnlocked(
            user:          $user,
            badge:         $newBadge,
            previousBadge: $previousBadge,
            earnedAt:      now()->toIso8601String(),
        ));
    }

    public function getCurrentBadge(User $user): ?Badge
    {
        $userBadge = UserBadge::where('user_id', $user->id)
            ->where('is_current', true)
            ->with('badge')
            ->first();

        return $userBadge?->badge;
    }
}
