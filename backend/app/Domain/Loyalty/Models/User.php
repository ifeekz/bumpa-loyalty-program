<?php

namespace App\Domain\Loyalty\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/**
 * User Model
 *
 * Implements JWTSubject so the jwt-auth package knows:
 *   - getJWTIdentifier()   → value encoded as the 'sub' claim (user ID)
 *   - getJWTCustomClaims() → extra claims merged into every token payload
 *
 * Encoding 'role' as a custom claim means any downstream microservice can
 * authorise the request from the token payload alone - no DB lookup,
 * no shared personal_access_tokens table, no inter-service round-trip.
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'loyalty_points',
        'total_spent',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'loyalty_points'    => 'decimal:2',
        'total_spent'       => 'decimal:2',
    ];

    // Override: bypassing the namespace-convention factory resolver.
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    // JWT Contract

    /**
     * The value encoded as the JWT 'sub' claim.
     * Identifies which user the token belongs to across all services.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Custom claims merged into every JWT payload.
     * 'role' lets downstream services gate admin vs customer without a DB call.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role'  => $this->role,
            'email' => $this->email,
        ];
    }

    // Relationships

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->orderByPivot('unlocked_at', 'desc');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot(['is_current', 'earned_at'])
            ->orderByPivot('earned_at', 'desc');
    }

    public function currentBadge(): HasOne
    {
        return $this->hasOne(UserBadge::class)->where('is_current', true)->with('badge');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class)->latest();
    }

    public function cashbackTransactions(): HasMany
    {
        return $this->hasMany(CashbackTransaction::class)->latest();
    }

    // Helpers

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasUnlockedAchievement(int $achievementId): bool
    {
        return $this->achievements()->where('achievement_id', $achievementId)->exists();
    }
}
