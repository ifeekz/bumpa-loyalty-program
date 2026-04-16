<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a payment provider call fails.
 * Caught in CashbackService and ProcessPurchaseJob so failures
 * degrade gracefully without crashing the full loyalty pipeline.
 */
class PaymentException extends RuntimeException {}
