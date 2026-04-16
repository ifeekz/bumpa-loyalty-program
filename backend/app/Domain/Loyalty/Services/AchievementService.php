<?php

namespace App\Domain\Loyalty\Services;

use App\Domain\Loyalty\Events\AchievementUnlocked;
use App\Domain\Loyalty\Models\Achievement;
use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AchievementService
 *
 * Responsible for evaluating which achievements a user has newly qualified
 * for after a purchase, recording them, awarding points, and firing the
 * AchievementUnlocked event.
 *
 * The condition engine is data-driven: each Achievement row specifies a
 * condition_type + condition_value. Adding a new achievement type requires
 * only a new seeded row and a matching case in evaluateCondition().
 */
class AchievementService
{
    /**
     * Process achievements for a user after a purchase.
     * Returns a collection of newly unlocked Achievement models.
     */
    public function processForPurchase(User $user, Purchase $purchase): Collection
    {
        // Load all achievements the user has NOT yet unlocked
        $lockedAchievements = Achievement::whereNotIn(
            'id',
            $user->achievements()->pluck('achievements.id')
        )->get();

        $unlocked = collect();

        foreach ($lockedAchievements as $achievement) {
            if ($this->evaluateCondition($achievement, $user, $purchase)) {
                $this->unlock($user, $achievement);
                $unlocked->push($achievement);
            }
        }

        return $unlocked;
    }

    /**
     * Evaluate whether a user now meets an achievement's condition.
     */
    private function evaluateCondition(Achievement $achievement, User $user, Purchase $purchase): bool
    {
        return match ($achievement->condition_type) {

            // Triggered when total completed purchases reaches the threshold
            Achievement::CONDITION_PURCHASE_COUNT => $this->checkPurchaseCount(
                $user,
                (int) $achievement->condition_value
            ),

            // Triggered when lifetime spend crosses the threshold (in Naira)
            Achievement::CONDITION_TOTAL_SPENT => $this->checkTotalSpent(
                $user,
                (float) $achievement->condition_value
            ),

            // Triggered when a single purchase is >= the threshold (big spender)
            Achievement::CONDITION_SINGLE_PURCHASE => $purchase->amount >= $achievement->condition_value,

            default => false,
        };
    }

    private function checkPurchaseCount(User $user, int $required): bool
    {
        $count = $user->purchases()
            ->where('status', 'completed')
            ->count();

        return $count >= $required;
    }

    private function checkTotalSpent(User $user, float $required): bool
    {
        // Use fresh value from DB to avoid stale in-memory totals
        return (float) $user->fresh()->total_spent >= $required;
    }

    /**
     * Record the unlock, award points, and fire the domain event.
     * Wrapped in a transaction so points and pivot row are always consistent.
     */
    private function unlock(User $user, Achievement $achievement): void
    {
        DB::transaction(function () use ($user, $achievement) {
            $now = Carbon::now();

            // Insert pivot row
            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => $now,
            ]);

            // Award points
            $user->increment('loyalty_points', $achievement->points_reward);

            Log::info('Achievement unlocked', [
                'user_id'        => $user->id,
                'achievement'    => $achievement->slug,
                'points_awarded' => $achievement->points_reward,
            ]);
        });

        // Refresh so the event carries up-to-date totals
        $user->refresh();

        event(new AchievementUnlocked(
            user:        $user,
            achievement: $achievement,
            unlockedAt:  now()->toIso8601String(),
        ));
    }
}
