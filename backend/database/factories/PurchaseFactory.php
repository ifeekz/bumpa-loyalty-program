<?php

namespace Database\Factories;

use App\Domain\Loyalty\Models\Purchase;
use App\Domain\Loyalty\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'user_id'               => User::factory(),
            'reference'             => 'LYL-' . strtoupper(Str::random(20)),
            'amount'                => fake()->randomFloat(2, 500, 50000),
            'cashback_amount'       => 0,
            'status'                => 'completed',
            'processed_for_loyalty' => true,
            'completed_at'          => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status'                => 'pending',
            'processed_for_loyalty' => false,
            'completed_at'          => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }

    public function unprocessed(): static
    {
        return $this->state(['processed_for_loyalty' => false]);
    }
}
