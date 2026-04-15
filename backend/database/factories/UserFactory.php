<?php

namespace Database\Factories;

use App\Domain\Loyalty\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'           => fake()->name(),
            'email'          => fake()->unique()->safeEmail(),
            'password'       => 'password',   // cast hashes automatically
            'role'           => 'customer',
            'loyalty_points' => fake()->randomFloat(2, 0, 3000),
            'total_spent'    => fake()->randomFloat(2, 0, 150000),
            'remember_token' => Str::random(10),
        ];
    }

    /** Produce an admin user */
    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    /** Produce a user with no points (fresh customer) */
    public function fresh(): static
    {
        return $this->state(['loyalty_points' => 0, 'total_spent' => 0]);
    }

    /** Produce a user at a specific points level */
    public function withPoints(float $points): static
    {
        return $this->state(['loyalty_points' => $points]);
    }
}
