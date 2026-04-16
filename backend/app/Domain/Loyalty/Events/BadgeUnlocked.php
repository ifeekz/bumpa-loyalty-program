<?php

namespace App\Domain\Loyalty\Events;

use App\Domain\Loyalty\Models\Badge;
use App\Domain\Loyalty\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BadgeUnlocked Event
 *
 * Fired by BadgeService when a user is promoted to a new badge tier.
 * Also broadcasts to the frontend for the animated badge promotion UI.
 */
class BadgeUnlocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly Badge  $badge,
        public readonly ?Badge $previousBadge,
        public readonly string $earnedAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("user.{$this->user->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'badge.unlocked';
    }

    public function broadcastWith(): array
    {
        return [
            'badge' => [
                'id'               => $this->badge->id,
                'name'             => $this->badge->name,
                'description'      => $this->badge->description,
                'icon'             => $this->badge->icon,
                'cashback_percent' => $this->badge->cashback_percent,
                'level'            => $this->badge->level,
            ],
            'previous_badge' => $this->previousBadge ? [
                'id'   => $this->previousBadge->id,
                'name' => $this->previousBadge->name,
            ] : null,
            'earned_at' => $this->earnedAt,
        ];
    }
}
