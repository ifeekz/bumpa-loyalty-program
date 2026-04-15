<?php

namespace App\Domain\Loyalty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'purchase_id',
        'reference',
        'amount',
        'status',
        'paystack_transfer_code',
        'paystack_recipient_code',
        'paystack_response',
        'failure_reason',
        'paid_at',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'paystack_response' => 'array',
        'paid_at'           => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
