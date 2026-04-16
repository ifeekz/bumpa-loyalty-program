<?php

namespace Database\Seeders;

use App\Domain\Loyalty\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // Purchase count milestones
            [
                'name'            => 'First Purchase',
                'slug'            => 'first-purchase',
                'description'     => 'Completed your very first purchase.',
                'icon'            => 'achievement-first-purchase',
                'points_reward'   => 50,
                'condition_type'  => 'purchase_count',
                'condition_value' => 1,
            ],
            [
                'name'            => 'Regular Shopper',
                'slug'            => 'regular-shopper',
                'description'     => 'Completed 5 purchases.',
                'icon'            => 'achievement-regular',
                'points_reward'   => 100,
                'condition_type'  => 'purchase_count',
                'condition_value' => 5,
            ],
            [
                'name'            => 'Loyal Customer',
                'slug'            => 'loyal-customer',
                'description'     => 'Completed 10 purchases.',
                'icon'            => 'achievement-loyal',
                'points_reward'   => 200,
                'condition_type'  => 'purchase_count',
                'condition_value' => 10,
            ],
            [
                'name'            => 'Dedicated Shopper',
                'slug'            => 'dedicated-shopper',
                'description'     => 'Completed 25 purchases.',
                'icon'            => 'achievement-dedicated',
                'points_reward'   => 500,
                'condition_type'  => 'purchase_count',
                'condition_value' => 25,
            ],

            // Total spend milestones
            [
                'name'            => 'Spender',
                'slug'            => 'spender',
                'description'     => 'Spent a total of ₦10,000.',
                'icon'            => 'achievement-spender',
                'points_reward'   => 100,
                'condition_type'  => 'total_spent',
                'condition_value' => 10000,
            ],
            [
                'name'            => 'Big Spender',
                'slug'            => 'big-spender',
                'description'     => 'Spent a total of ₦50,000.',
                'icon'            => 'achievement-big-spender',
                'points_reward'   => 300,
                'condition_type'  => 'total_spent',
                'condition_value' => 50000,
            ],
            [
                'name'            => 'High Roller',
                'slug'            => 'high-roller',
                'description'     => 'Spent a total of ₦200,000.',
                'icon'            => 'achievement-high-roller',
                'points_reward'   => 1000,
                'condition_type'  => 'total_spent',
                'condition_value' => 200000,
            ],

            // Single purchase milestones
            [
                'name'            => 'Power Purchase',
                'slug'            => 'power-purchase',
                'description'     => 'Made a single purchase of ₦5,000 or more.',
                'icon'            => 'achievement-power',
                'points_reward'   => 75,
                'condition_type'  => 'single_purchase',
                'condition_value' => 5000,
            ],
            [
                'name'            => 'Whale',
                'slug'            => 'whale',
                'description'     => 'Made a single purchase of ₦20,000 or more.',
                'icon'            => 'achievement-whale',
                'points_reward'   => 250,
                'condition_type'  => 'single_purchase',
                'condition_value' => 20000,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['slug' => $achievement['slug']],
                $achievement
            );
        }
    }
}
