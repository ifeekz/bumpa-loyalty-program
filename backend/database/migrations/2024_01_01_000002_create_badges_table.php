<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Badges represent tiers a user progresses through (Bronze → Silver → Gold → Platinum).
        // They are seeded - not user-created — so the table is intentionally simple.
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                       // e.g. "Bronze", "Gold"
            $table->string('slug')->unique();                       // e.g. "bronze", "gold"
            $table->text('description')->nullable();
            $table->string('icon')->nullable();                     // Icon class or image path
            $table->unsignedInteger('min_points')->default(0);     // Points threshold to earn this badge
            $table->unsignedInteger('level')->default(1);          // Sort order: 1=Bronze, 4=Platinum
            $table->decimal('cashback_percent', 5, 2)->default(0); // Cashback rate for this tier
            $table->timestamps();

            $table->index('min_points');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
