import { useState } from 'react'
import { Trophy, ShoppingBag, Star, TrendingUp, Zap } from 'lucide-react'
import { useLoyaltyProfile } from '@/hooks/useApi'
import { useAuth } from '@/store/AuthContext'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Button }   from '@/components/ui/button'
import { StatCard, Skeleton, ErrorMessage, BadgePill } from '@/components/shared'
import { formatNaira, formatPoints, formatRelativeTime } from '@/utils/formatters'

export default function DashboardPage() {
  const { user }                   = useAuth()
  const { data, isLoading, error } = useLoyaltyProfile()
  const [newAchievement, setNewAchievement] = useState<string | null>(null)

  const simulateUnlock = () => {
    setNewAchievement('First Purchase')
    setTimeout(() => setNewAchievement(null), 3000)
  }

  if (isLoading) return <DashboardSkeleton />
  if (error)     return <ErrorMessage message="Failed to load your loyalty profile." />

  const profile = data

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold">
          Welcome back, {user?.name?.split(' ')[0]} 👋
        </h1>
        <p className="text-muted-foreground text-sm mt-1">Here's your loyalty overview</p>
      </div>

      {/* Achievement unlock animation overlay */}
      {newAchievement && (
        <div className="fixed inset-0 z-50 flex items-center justify-center pointer-events-none">
          <div className="animate-achievement-pop bg-card border-2 border-primary rounded-2xl p-8 shadow-2xl text-center max-w-xs mx-4">
            <div className="text-5xl mb-3">🏆</div>
            <p className="font-bold text-lg">Achievement Unlocked!</p>
            <p className="text-primary font-medium mt-1">{newAchievement}</p>
            <p className="text-xs text-muted-foreground mt-2">+50 loyalty points</p>
          </div>
        </div>
      )}

      {/* Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Loyalty Points"
          value={formatPoints(profile?.user.loyalty_points ?? 0)}
          icon={<Star className="h-4 w-4" />}
        />
        <StatCard
          title="Total Spent"
          value={formatNaira(profile?.user.total_spent ?? 0)}
          icon={<ShoppingBag className="h-4 w-4" />}
        />
        <StatCard
          title="Achievements"
          value={profile?.stats.total_achievements ?? 0}
          icon={<Trophy className="h-4 w-4" />}
        />
        <StatCard
          title="Purchases"
          value={profile?.stats.purchase_count ?? 0}
          icon={<TrendingUp className="h-4 w-4" />}
        />
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        {/* Badge progress */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Badge Progress</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {profile?.current_badge ? (
              <div className="flex items-center gap-3">
                <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-2xl">
                  🏅
                </div>
                <div>
                  <BadgePill
                    name={profile.current_badge.name}
                    slug={profile.current_badge.slug}
                  />
                  <p className="text-xs text-muted-foreground mt-1">
                    {profile.current_badge.cashback_percent}% cashback on purchases
                  </p>
                </div>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">
                No badge yet — make your first purchase!
              </p>
            )}

            {profile?.next_badge && (
              <div className="space-y-2">
                <div className="flex justify-between text-xs text-muted-foreground">
                  <span>Progress to {profile.next_badge.name}</span>
                  <span>{profile.next_badge.progress_percent}%</span>
                </div>
                <Progress value={profile.next_badge.progress_percent} />
                <p className="text-xs text-muted-foreground">
                  {formatPoints(profile.next_badge.points_needed)} needed
                </p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Recent achievements */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Recent Achievements</CardTitle>
          </CardHeader>
          <CardContent>
            {profile?.achievements?.length ? (
              <ul className="space-y-3">
                {profile.achievements.slice(0, 4).map((a) => (
                  <li key={a.id} className="flex items-center gap-3">
                    <div className="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center text-sm flex-shrink-0">
                      🏆
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium truncate">{a.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {formatRelativeTime(a.unlocked_at)} · +{a.points_reward} pts
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-muted-foreground">
                No achievements yet. Start shopping to earn your first one!
              </p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Simulate unlock — assessment demo requirement */}
      <Card className="border-dashed">
        <CardContent className="pt-6">
          <div className="flex items-center gap-3">
            <div className="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center">
              <Zap className="h-4 w-4 text-primary" />
            </div>
            <div className="flex-1">
              <p className="text-sm font-medium">Simulate Achievement Unlock</p>
              <p className="text-xs text-muted-foreground">
                Demonstrates the animated notification shown on a real unlock event
              </p>
            </div>
            <Button size="sm" variant="outline" onClick={simulateUnlock}>
              Unlock
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-4 w-64" />
      </div>
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map(num => (
          <Skeleton key={num} className="h-28 rounded-xl" />
        ))}
      </div>
      <div className="grid lg:grid-cols-2 gap-6">
        <Skeleton className="h-48 rounded-xl" />
        <Skeleton className="h-48 rounded-xl" />
      </div>
    </div>
  )
}
