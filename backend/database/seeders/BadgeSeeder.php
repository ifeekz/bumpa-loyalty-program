<?php

namespace Database\Seeders;

use App\Domain\Loyalty\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'name'             => 'Bronze',
                'slug'             => 'bronze',
                'description'      => 'Welcome to the loyalty program! You\'ve earned your first badge.',
                'icon'             => 'badge-bronze',
                'min_points'       => 0,       // Everyone starts at Bronze
                'level'            => 1,
                'cashback_percent' => 2.00,
            ],
            [
                'name'             => 'Silver',
                'slug'             => 'silver',
                'description'      => 'You\'re building momentum. Keep shopping to unlock more rewards.',
                'icon'             => 'badge-silver',
                'min_points'       => 500,
                'level'            => 2,
                'cashback_percent' => 5.00,
            ],
            [
                'name'             => 'Gold',
                'slug'             => 'gold',
                'description'      => 'A valued customer. Enjoy enhanced cashback and exclusive perks.',
                'icon'             => 'badge-gold',
                'min_points'       => 2000,
                'level'            => 3,
                'cashback_percent' => 8.00,
            ],
            [
                'name'             => 'Platinum',
                'slug'             => 'platinum',
                'description'      => 'Our most valued customer. You enjoy our highest cashback rate.',
                'icon'             => 'badge-platinum',
                'min_points'       => 5000,
                'level'            => 4,
                'cashback_percent' => 12.00,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
