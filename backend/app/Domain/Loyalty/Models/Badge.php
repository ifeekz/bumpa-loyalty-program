<?php

namespace App\Domain\Loyalty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'min_points',
        'level',
        'cashback_percent',
    ];

    protected $casts = [
        'min_points'       => 'integer',
        'level'            => 'integer',
        'cashback_percent' => 'decimal:2',
    ];

    // Relationships

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot(['is_current', 'earned_at']);
    }

    // Scopes

    /**
     * Return badges ordered from lowest to highest tier.
     * Used by BadgeService to determine which badge a user qualifies for.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level', 'asc');
    }

    /**
     * Find the highest badge a user qualifies for based on points.
     */
    public static function forPoints(float $points): ?self
    {
        return static::where('min_points', '<=', $points)
            ->orderBy('level', 'desc')
            ->first();
    }
}
