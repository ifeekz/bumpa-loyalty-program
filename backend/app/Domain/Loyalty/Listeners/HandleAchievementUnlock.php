<?php

namespace App\Domain\Loyalty\Listeners;

use App\Domain\Loyalty\Events\PurchaseRecorded;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Services\AchievementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * HandleAchievementUnlock
 *
 * Listens for PurchaseRecorded and delegates to AchievementService.
 * Runs on the 'achievements' queue (second priority after 'purchases').
 *
 * Implements ShouldQueue so it runs asynchronously — the HTTP response
 * returns to the user immediately without waiting for achievement checks.
 */
class HandleAchievementUnlock implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'achievements';
    public int    $tries = 3;

    public function __construct(
        private readonly AchievementService $achievementService,
    ) {}

    public function handle(PurchaseRecorded $event): void
    {
        $user     = User::find($event->purchase->user_id);
        $purchase = $event->purchase;

        if (! $user) {
            Log::warning('HandleAchievementUnlock: user not found', [
                'user_id' => $event->purchase->user_id,
            ]);
            return;
        }

        $unlocked = $this->achievementService->processForPurchase($user, $purchase);

        Log::info('HandleAchievementUnlock: completed', [
            'user_id'  => $user->id,
            'unlocked' => $unlocked->pluck('slug')->toArray(),
        ]);
    }
}
