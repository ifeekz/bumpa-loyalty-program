<?php

namespace App\Domain\Loyalty\Events;

use App\Domain\Loyalty\Models\Achievement;
use App\Domain\Loyalty\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AchievementUnlocked Event
 *
 * Fired by AchievementService when a user earns a new achievement.
 * Implements ShouldBroadcast so the React dashboard receives a real-time
 * push notification via Laravel Echo + Redis (or Pusher).
 *
 * Broadcasting channel: private-user.{userId}
 */
class AchievementUnlocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User        $user,
        public readonly Achievement $achievement,
        public readonly string      $unlockedAt,
    ) {}

    /**
     * The channel this event broadcasts on.
     * Private channel ensures only the authenticated user receives it.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("user.{$this->user->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'achievement.unlocked';
    }

    /**
     * Data sent to the frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'achievement' => [
                'id'           => $this->achievement->id,
                'name'         => $this->achievement->name,
                'description'  => $this->achievement->description,
                'icon'         => $this->achievement->icon,
                'points_reward' => $this->achievement->points_reward,
            ],
            'unlocked_at' => $this->unlockedAt,
        ];
    }
}
