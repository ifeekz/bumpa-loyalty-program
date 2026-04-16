<?php

namespace App\Domain\Loyalty\Listeners;

use App\Domain\Loyalty\Events\PurchaseRecorded;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Services\CashbackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * HandleCashbackPayout
 *
 * Listens for PurchaseRecorded and initiates the Paystack cashback transfer.
 * Runs on the 'default' queue (lowest priority) — cashback payouts are
 * important but not as time-sensitive as achievement processing.
 */
class HandleCashbackPayout implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';
    public int    $tries = 3;
    public array  $backoff = [30, 120, 300];

    public function __construct(
        private readonly CashbackService $cashbackService,
    ) {}

    public function handle(PurchaseRecorded $event): void
    {
        $user     = User::find($event->purchase->user_id);
        $purchase = $event->purchase;

        if (! $user) {
            return;
        }

        $transaction = $this->cashbackService->processForPurchase($user, $purchase);

        Log::info('HandleCashbackPayout: completed', [
            'user_id'    => $user->id,
            'cashback'   => $transaction->amount,
            'tx_status'  => $transaction->status,
        ]);
    }
}
