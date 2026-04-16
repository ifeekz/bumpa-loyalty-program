<?php

namespace App\Domain\Loyalty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'points_reward',
        'condition_type',
        'condition_value',
    ];

    protected $casts = [
        'points_reward'   => 'integer',
        'condition_value' => 'decimal:2',
    ];

    // Supported condition types — used by AchievementService
    public const CONDITION_PURCHASE_COUNT  = 'purchase_count';
    public const CONDITION_TOTAL_SPENT     = 'total_spent';
    public const CONDITION_SINGLE_PURCHASE = 'single_purchase';

    // Relationships

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at');
    }
}
