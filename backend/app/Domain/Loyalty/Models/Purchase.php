<?php

namespace App\Domain\Loyalty\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'amount',
        'cashback_amount',
        'status',
        'paystack_transaction_id',
        'paystack_response',
        'processed_for_loyalty',
        'completed_at',
    ];

    protected $casts = [
        'amount'                => 'decimal:2',
        'cashback_amount'       => 'decimal:2',
        'paystack_response'     => 'array',
        'processed_for_loyalty' => 'boolean',
        'completed_at'          => 'datetime',
    ];

    // Override: bypassing the namespace-convention factory resolver.
    protected static function newFactory(): PurchaseFactory
    {
        return PurchaseFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashbackTransaction(): HasOne
    {
        return $this->hasOne(CashbackTransaction::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
