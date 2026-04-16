<?php

namespace App\Providers;

use App\Domain\Loyalty\Events\AchievementUnlocked;
use App\Domain\Loyalty\Events\BadgeUnlocked;
use App\Domain\Loyalty\Events\PurchaseRecorded;
use App\Domain\Loyalty\Listeners\HandleAchievementUnlock;
use App\Domain\Loyalty\Listeners\HandleBadgePromotion;
use App\Domain\Loyalty\Listeners\HandleCashbackPayout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * EventServiceProvider
 *
 * Wires domain events to their listeners.
 *
 * PurchaseRecorded fires three listeners in this order:
 *   1. HandleAchievementUnlock  - checks and unlocks achievements, grants points
 *   2. HandleBadgePromotion     - promotes badge if points threshold crossed (delayed 2s)
 *   3. HandleCashbackPayout     - initiates Paystack cashback transfer
 *
 * AchievementUnlocked and BadgeUnlocked are ShouldBroadcast events handled
 * by Laravel's broadcasting layer (Redis / Pusher) - no explicit listeners needed here.
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PurchaseRecorded::class => [
            HandleAchievementUnlock::class,
            HandleBadgePromotion::class,
            HandleCashbackPayout::class,
        ],

        // These are broadcast-only events.
        // Add listeners here if you need server-side side effects on unlock.
        AchievementUnlocked::class => [],
        BadgeUnlocked::class       => [],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false; // Explicit registration preferred - avoids magic discovery surprises
    }
}
