<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashback_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();                       // Internal reference
            $table->decimal('amount', 10, 2);                           // Cashback amount in Naira
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('paystack_transfer_code')->nullable();
            $table->string('paystack_recipient_code')->nullable();
            $table->json('paystack_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashback_transactions');
    }
};
