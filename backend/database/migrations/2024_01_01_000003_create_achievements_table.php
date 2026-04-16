<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Achievements are discrete milestones a user can unlock (e.g. "First Purchase",
        // "Spent ₦50,000"). They are seeded. The 'condition_type' + 'condition_value' pair
        // drives the AchievementService logic without hardcoding rules in PHP.
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('points_reward')->default(0);   // Points granted on unlock

            // Condition engine — keeps business rules data-driven
            $table->string('condition_type');                        // e.g. "purchase_count", "total_spent", "single_purchase"
            $table->decimal('condition_value', 12, 2)->default(0);  // Threshold value for the condition

            $table->timestamps();

            $table->index('condition_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
