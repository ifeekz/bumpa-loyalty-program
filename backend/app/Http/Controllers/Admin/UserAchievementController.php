<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Loyalty\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin\UserAchievementController
 *
 * GET  /api/admin/users/achievements   — paginated list of ALL users with loyalty summaries
 * GET  /api/admin/users/{user}         — detailed profile for a specific user (admin view)
 *
 * Protected by 'auth:api' + 'admin' middleware (see routes/api.php).
 */
class UserAchievementController extends Controller
{
    /**
     * GET /api/admin/users/achievements
     *
     * Returns a paginated list of all customers with their:
     *   - total achievement count
     *   - current badge
     *   - loyalty points + total spend
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'badge'    => 'nullable|string',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $users = User::query()
            ->where('role', 'customer')
            ->withCount('achievements')
            ->with(['currentBadge.badge'])
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
            )
            ->when($request->badge, fn ($q, $badge) =>
                $q->whereHas('currentBadge.badge', fn ($bq) => $bq->where('slug', $badge))
            )
            ->orderByDesc('loyalty_points')
            ->paginate($request->per_page ?? 20);

        return $this->success('Users retrieved.', [
            'data' => collect($users->items())->map(fn (User $u) => [
                'id'                 => $u->id,
                'name'               => $u->name,
                'email'              => $u->email,
                'loyalty_points'     => (float) $u->loyalty_points,
                'total_spent'        => (float) $u->total_spent,
                'total_achievements' => $u->achievements_count,
                'current_badge'      => $u->currentBadge?->badge ? [
                    'name'  => $u->currentBadge->badge->name,
                    'slug'  => $u->currentBadge->badge->slug,
                    'icon'  => $u->currentBadge->badge->icon,
                    'level' => $u->currentBadge->badge->level,
                ] : null,
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/users/{user}
     *
     * Full detail view of a single user accessible by admins only.
     */
    public function show(User $user): JsonResponse
    {
        $user->load([
            'achievements',
            'currentBadge.badge',
            'purchases'            => fn ($q) => $q->latest()->limit(10),
            'cashbackTransactions' => fn ($q) => $q->latest()->limit(10),
        ]);

        return $this->success('User profile retrieved.', [
            'user' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'loyalty_points' => (float) $user->loyalty_points,
                'total_spent'    => (float) $user->total_spent,
                'created_at'     => $user->created_at,
            ],
            'current_badge' => $user->currentBadge?->badge,
            'achievements'  => $user->achievements->map(fn ($a) => [
                'id'          => $a->id,
                'name'        => $a->name,
                'icon'        => $a->icon,
                'unlocked_at' => $a->pivot->unlocked_at,
            ]),
            'recent_purchases' => $user->purchases->map(fn ($p) => [
                'reference'       => $p->reference,
                'amount'          => (float) $p->amount,
                'cashback_amount' => (float) $p->cashback_amount,
                'status'          => $p->status,
                'completed_at'    => $p->completed_at,
            ]),
            'cashback_summary' => [
                'total_paid'    => (float) $user->cashbackTransactions->where('status', 'paid')->sum('amount'),
                'total_pending' => (float) $user->cashbackTransactions->where('status', 'pending')->sum('amount'),
            ],
        ]);
    }
}
