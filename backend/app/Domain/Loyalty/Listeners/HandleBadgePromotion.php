<?php

namespace App\Domain\Loyalty\Listeners;

use App\Domain\Loyalty\Events\PurchaseRecorded;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * HandleBadgePromotion
 *
 * Listens for PurchaseRecorded and checks whether the user's accumulated
 * loyalty_points (updated by HandleAchievementUnlock) now qualify them
 * for a higher badge tier.
 *
 * NOTE: This listener intentionally runs on the same 'achievements' queue
 * as HandleAchievementUnlock. Laravel dispatches listeners in registration
 * order, so achievements are always processed first, ensuring badge promotion
 * reflects the latest point total.
 *
 * If you switch to a parallel queue setup, add a small delay here:
 *   public int $delay = 5;
 */
class HandleBadgePromotion implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'achievements';
    public int    $tries = 3;
    public int    $delay = 2; // 2-second delay ensures achievement points are committed first

    public function __construct(
        private readonly BadgeService $badgeService,
    ) {}

    public function handle(PurchaseRecorded $event): void
    {
        $user = User::find($event->purchase->user_id);

        if (! $user) {
            return;
        }

        $newBadge = $this->badgeService->processForUser($user);

        if ($newBadge) {
            Log::info('HandleBadgePromotion: badge promoted', [
                'user_id' => $user->id,
                'badge'   => $newBadge->slug,
            ]);
        }
    }
}
