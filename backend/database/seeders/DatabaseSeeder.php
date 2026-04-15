<?php

namespace Database\Seeders;

use App\Domain\Loyalty\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Always seed reference data first - models depend on badges + achievements existing
        $this->call([
            BadgeSeeder::class,
            AchievementSeeder::class,
        ]);

        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@loyalty.test'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        // Sample customers (local / testing only)
        if (app()->environment(['local', 'testing'])) {
            User::factory(10)->create();
        }
    }
}
