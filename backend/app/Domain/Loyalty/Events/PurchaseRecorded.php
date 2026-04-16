<?php

namespace App\Domain\Loyalty\Events;

use App\Domain\Loyalty\Models\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PurchaseRecorded Event
 *
 * Dispatched by ProcessPurchaseJob after a purchase is verified as completed
 * by Paystack. This event is the entry point into the loyalty pipeline:
 *
 *   PurchaseRecorded
 *       └─► HandleAchievementUnlock  (checks & unlocks achievements)
 *       └─► HandleBadgePromotion     (promotes badge if points threshold crossed)
 *       └─► HandleCashbackPayout     (initiates Paystack transfer)
 */
class PurchaseRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Purchase $purchase,
    ) {}
}
