<?php

namespace App\Jobs;

use App\Domain\Loyalty\DTOs\PurchaseData;
use App\Domain\Loyalty\Events\PurchaseRecorded;
use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Services\Payment\PaymentProviderInterface;
use App\Exceptions\PaymentException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPurchaseJob
 *
 * This is the entry point into the event-driven loyalty pipeline.
 * It runs on the 'purchases' queue (highest priority) and:
 *
 *   1. Verifies the transaction with Paystack
 *   2. Marks the purchase as completed
 *   3. Updates the user's total_spent
 *   4. Fires PurchaseRecorded — which triggers achievement + badge + cashback listeners
 *
 * The processed_for_loyalty flag acts as an idempotency guard.
 * If the job is retried (e.g. after a crash), the guard prevents
 * the user from earning double points.
 *
 * Queue: purchases (high priority)
 * Retries: 3  (set in docker-compose queue_worker command)
 * Backoff: exponential — 60s, 300s, 600s
 */
class ProcessPurchaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [60, 300, 600];

    public function __construct(
        private readonly PurchaseData $purchaseData,
    ) {
        $this->onQueue('purchases');
    }

    public function handle(PaymentProviderInterface $paymentProvider): void
    {
        $purchase = Purchase::where('reference', $this->purchaseData->reference)->first();

        if (! $purchase) {
            Log::warning('ProcessPurchaseJob: purchase not found', [
                'reference' => $this->purchaseData->reference,
            ]);
            return;
        }

        // Idempotency guard — safe to retry without side effects
        if ($purchase->processed_for_loyalty) {
            Log::info('ProcessPurchaseJob: already processed, skipping', [
                'purchase_id' => $purchase->id,
            ]);
            return;
        }

        // Verify with Paystack before touching any loyalty data
        try {
            $verification = $paymentProvider->verifyTransaction($this->purchaseData->reference);
        } catch (PaymentException $e) {
            Log::error('ProcessPurchaseJob: verification failed', [
                'reference' => $this->purchaseData->reference,
                'error'     => $e->getMessage(),
            ]);
            $this->fail($e);
            return;
        }

        if ($verification['status'] !== 'success') {
            // Mark failed — do not retry, the payment itself failed
            $purchase->update(['status' => 'failed']);
            Log::warning('ProcessPurchaseJob: payment not successful', [
                'reference' => $this->purchaseData->reference,
                'status'    => $verification['status'],
            ]);
            return;
        }

        // Atomic update: mark completed + set guard flag
        DB::transaction(function () use ($purchase, $verification) {
            $user = User::lockForUpdate()->find($purchase->user_id);

            $purchase->update([
                'status'                  => 'completed',
                'processed_for_loyalty'   => true,
                'paystack_transaction_id' => $verification['raw']['id'] ?? null,
                'paystack_response'       => $verification['raw'],
                'completed_at'            => now(),
            ]);

            // Accumulate lifetime spend on the user row
            $user->increment('total_spent', $purchase->amount);
        });

        Log::info('ProcessPurchaseJob: purchase verified and completed', [
            'purchase_id' => $purchase->id,
            'user_id'     => $purchase->user_id,
            'amount'      => $purchase->amount,
        ]);

        // Fire the domain event — listeners handle achievements, badges, cashback
        PurchaseRecorded::dispatch($purchase->fresh());
    }

    /**
     * Handle a job that has failed all retries.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPurchaseJob permanently failed', [
            'reference' => $this->purchaseData->reference,
            'error'     => $exception->getMessage(),
        ]);
    }
}
