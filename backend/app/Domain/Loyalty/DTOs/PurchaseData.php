<?php

namespace App\Domain\Loyalty\DTOs;

/**
 * PurchaseData DTO
 *
 * A simple value object that carries validated purchase data between
 * layers (controller → job → service). Using a DTO instead of raw arrays
 * gives us IDE autocomplete, type safety, and a clear contract.
 */
final class PurchaseData
{
    public function __construct(
        public readonly int    $userId,
        public readonly float  $amount,
        public readonly string $reference,
        public readonly string $email,     // User email — required by Paystack
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId:    $data['user_id'],
            amount:    (float) $data['amount'],
            reference: $data['reference'],
            email:     $data['email'],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id'   => $this->userId,
            'amount'    => $this->amount,
            'reference' => $this->reference,
            'email'     => $this->email,
        ];
    }
}
