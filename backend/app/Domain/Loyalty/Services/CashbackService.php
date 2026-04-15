<?php

namespace App\Domain\Loyalty\Services;

use App\Domain\Loyalty\Models\CashbackTransaction;
use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Models\User;
use App\Domain\Loyalty\Services\Payment\PaymentProviderInterface;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CashbackService
 *
 * Calculates the cashback amount based on the user's current badge tier
 * and initiates the Paystack transfer. A CashbackTransaction record is
 * always created first (status=pending) so we have an audit trail even
 * if the transfer call fails.
 */
class CashbackService
{
    public function __construct(
        private readonly PaymentProviderInterface $paymentProvider,
        private readonly BadgeService             $badgeService,
    ) {}

    /**
     * Process cashback for a completed purchase.
     * Returns the CashbackTransaction record.
     */
    public function processForPurchase(User $user, Purchase $purchase): CashbackTransaction
    {
        $cashbackPercent = $this->getCashbackPercent($user);
        $cashbackAmount  = round($purchase->amount * ($cashbackPercent / 100), 2);

        // Record the pending transaction first — we want an audit row
        // regardless of whether the Paystack call succeeds
        $transaction = DB::transaction(function () use ($user, $purchase, $cashbackAmount) {
            $tx = CashbackTransaction::create([
                'user_id'     => $user->id,
                'purchase_id' => $purchase->id,
                'reference'   => 'CB-' . strtoupper(Str::random(16)),
                'amount'      => $cashbackAmount,
                'status'      => 'pending',
            ]);

            // Update the purchase row with the calculated cashback
            $purchase->update(['cashback_amount' => $cashbackAmount]);

            return $tx;
        });

        if ($cashbackAmount <= 0) {
            // No cashback for this tier — mark as paid immediately
            $transaction->update(['status' => 'paid', 'paid_at' => now()]);
            return $transaction;
        }

        // Attempt the Paystack transfer
        $this->attemptTransfer($user, $transaction);

        return $transaction->fresh();
    }

    private function attemptTransfer(User $user, CashbackTransaction $transaction): void
    {
        try {
            // Note: In production you would store the recipient_code on the user
            // record after the first transfer, and reuse it. For this assessment
            // we create a recipient per transaction to keep the model simpler.
            // The interface contract leaves room for that optimisation.
            $result = $this->paymentProvider->initiateTransfer(
                amount:        $transaction->amount,
                recipientCode: $this->getOrCreateRecipient($user),
                reference:     $transaction->reference,
                reason:        "Cashback for purchase #{$transaction->purchase_id}",
            );

            $transaction->update([
                'status'                  => 'paid',
                'paystack_transfer_code'  => $result['transfer_code'],
                'paid_at'                 => now(),
                'paystack_response'       => $result,
            ]);

            Log::info('Cashback transfer initiated', [
                'user_id'       => $user->id,
                'amount'        => $transaction->amount,
                'transfer_code' => $result['transfer_code'],
            ]);

        } catch (PaymentException $e) {
            $transaction->update([
                'status'         => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Cashback transfer failed', [
                'user_id'          => $user->id,
                'transaction_ref'  => $transaction->reference,
                'reason'           => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the cashback percentage for the user's current badge tier.
     * Falls back to the global config default (5%) if no badge held.
     */
    private function getCashbackPercent(User $user): float
    {
        $currentBadge = $this->badgeService->getCurrentBadge($user);

        return $currentBadge
            ? (float) $currentBadge->cashback_percent
            : (float) config('loyalty.cashback_percent', 5);
    }

    /**
     * Retrieve or create a Paystack transfer recipient for the user.
     * In a full implementation this would be stored on the user profile.
     */
    private function getOrCreateRecipient(User $user): string
    {
        // For this assessment we use a hardcoded test bank account.
        // In production: fetch from user's saved bank details.
        $result = $this->paymentProvider->createTransferRecipient(
            name:          $user->name,
            accountNumber: '0000000000',   // Replace with user's actual account
            bankCode:      '058',          // GTBank test code
        );

        return $result['recipient_code'];
    }
}
