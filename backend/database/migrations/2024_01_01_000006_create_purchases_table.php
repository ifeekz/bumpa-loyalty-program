<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();                       // Paystack payment reference
            $table->decimal('amount', 12, 2);                           // Amount in Naira
            $table->decimal('cashback_amount', 10, 2)->default(0);      // Cashback calculated at time of purchase
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('paystack_transaction_id')->nullable();
            $table->json('paystack_response')->nullable();               // Full Paystack response for auditing
            $table->boolean('processed_for_loyalty')->default(false);   // Guard: prevents double-counting
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['reference']);
            $table->index(['processed_for_loyalty', 'status']);         // Used by queue worker query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
