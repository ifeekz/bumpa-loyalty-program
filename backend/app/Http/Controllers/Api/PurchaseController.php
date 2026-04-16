<?php

namespace App\Http\Controllers\Api;

use App\Domain\Loyalty\DTOs\PurchaseData;
use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Services\Payment\PaymentProviderInterface;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPurchaseJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PurchaseController
 *
 * Two endpoints:
 *  - POST /api/purchases          - Initialise a payment with Paystack, return checkout URL
 *  - POST /api/purchases/webhook  - Paystack webhook callback (verifies + dispatches queue job)
 *
 * Flow:
 *   Client → POST /api/purchases → Paystack initialize → return {authorization_url}
 *   User completes payment on Paystack hosted page
 *   Paystack → POST /api/purchases/webhook → verify → dispatch ProcessPurchaseJob
 *   ProcessPurchaseJob → PurchaseRecorded event → Achievements + Badge + Cashback
 */
class PurchaseController extends Controller
{
    public function __construct(
        private readonly PaymentProviderInterface $paymentProvider,
    ) {}

    /**
     * POST /api/purchases
     * Initialise a new purchase and return the Paystack checkout URL.
     */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:100', // Minimum ₦100
        ]);

        $user      = $request->user();
        $reference = 'LYL-' . strtoupper(Str::random(20));

        // Create a pending purchase record before calling Paystack
        // so we have a reference row even if the browser closes
        $purchase = Purchase::create([
            'user_id'   => $user->id,
            'reference' => $reference,
            'amount'    => $data['amount'],
            'status'    => 'pending',
        ]);

        $paymentData = $this->paymentProvider->initializePayment(
            email:     $user->email,
            amount:    $data['amount'],
            reference: $reference,
            metadata:  ['purchase_id' => $purchase->id, 'user_id' => $user->id],
        );

        return $this->success('Payment initialised.', [
            'reference'         => $reference,
            'authorization_url' => $paymentData['authorization_url'],
            'access_code'       => $paymentData['access_code'],
        ]);
    }

    /**
     * POST /api/purchases/webhook
     * Paystack webhook endpoint. Must be publicly accessible (no auth middleware).
     * Verifies the Paystack signature before processing.
     */
    public function webhook(Request $request): JsonResponse
    {
        // Verify Paystack signature to prevent spoofed webhooks
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();
        $expected  = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

        if (! hash_equals($expected, $signature ?? '')) {
            return $this->error('Invalid signature.', 400);
        }

        $event = $request->input('event');
        $data  = $request->input('data');

        // Only handle successful charge events
        if ($event !== 'charge.success') {
            return $this->success('Event ignored.');
        }

        $purchase = Purchase::where('reference', $data['reference'])->first();

        if (! $purchase || $purchase->processed_for_loyalty) {
            return $this->success('Already processed or not found.');
        }

        // Dispatch the loyalty pipeline job
        ProcessPurchaseJob::dispatch(
            PurchaseData::fromArray([
                'user_id'   => $purchase->user_id,
                'amount'    => $purchase->amount,
                'reference' => $purchase->reference,
                'email'     => $purchase->user->email,
            ])
        );

        return $this->success('Webhook received.');
    }

    /**
     * GET /api/purchases
     * Returns the authenticated user's purchase history.
     */
    public function index(Request $request): JsonResponse
    {
        $purchases = $request->user()
            ->purchases()
            ->with('cashbackTransaction')
            ->paginate(15);

        return $this->success('Purchase history retrieved.', $purchases);
    }
}
