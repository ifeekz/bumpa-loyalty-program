import { useLoyaltyProfile } from '@/hooks/useApi'
import { Card, CardContent } from '@/components/ui/card'
import { Badge }  from '@/components/ui/badge'
import { Skeleton, ErrorMessage, BadgePill } from '@/components/shared'
import { formatPoints, formatRelativeTime } from '@/utils/formatters'
import { Lock } from 'lucide-react'

const ALL_ACHIEVEMENTS = [
  { slug: 'first-purchase',    name: 'First Purchase',    description: 'Completed your first purchase',    points: 50   },
  { slug: 'regular-shopper',   name: 'Regular Shopper',   description: 'Completed 5 purchases',            points: 100  },
  { slug: 'loyal-customer',    name: 'Loyal Customer',    description: 'Completed 10 purchases',           points: 200  },
  { slug: 'dedicated-shopper', name: 'Dedicated Shopper', description: 'Completed 25 purchases',           points: 500  },
  { slug: 'spender',           name: 'Spender',           description: 'Spent a total of ₦10,000',         points: 100  },
  { slug: 'big-spender',       name: 'Big Spender',       description: 'Spent a total of ₦50,000',         points: 300  },
  { slug: 'high-roller',       name: 'High Roller',       description: 'Spent a total of ₦200,000',        points: 1000 },
  { slug: 'power-purchase',    name: 'Power Purchase',    description: 'Single purchase ≥ ₦5,000',         points: 75   },
  { slug: 'whale',             name: 'Whale',             description: 'Single purchase ≥ ₦20,000',        points: 250  },
]

export default function AchievementsPage() {
  const { data, isLoading, error } = useLoyaltyProfile()

  if (isLoading) return <AchievementsSkeleton />
  if (error)     return <ErrorMessage message="Failed to load achievements." />

  const unlockedSlugs = new Set(data?.achievements?.map((a) => a.slug) ?? [])
  const unlockedMap   = new Map(data?.achievements?.map((a) => [a.slug, a]) ?? [])
  const unlocked      = ALL_ACHIEVEMENTS.filter((a) =>  unlockedSlugs.has(a.slug))
  const locked        = ALL_ACHIEVEMENTS.filter((a) => !unlockedSlugs.has(a.slug))

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Achievements</h1>
        <p className="text-muted-foreground text-sm mt-1">
          {unlocked.length} of {ALL_ACHIEVEMENTS.length} unlocked
        </p>
      </div>

      {/* Badge history */}
      {!!data?.badge_history?.length && (
        <div>
          <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
            Badge History
          </h2>
          <div className="flex flex-wrap gap-2">
            {data.badge_history.map((b, i) => (
              <div key={i} className="flex items-center gap-2">
                <BadgePill name={b.name} slug={b.name.toLowerCase()} />
                {b.is_current && (
                  <span className="text-xs text-muted-foreground">current</span>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Unlocked */}
      {unlocked.length > 0 && (
        <div>
          <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
            Unlocked
          </h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {unlocked.map((a) => {
              const detail = unlockedMap.get(a.slug)
              return (
                <Card key={a.slug} className="border-amber-200 bg-amber-50/50">
                  <CardContent className="pt-5 pb-4">
                    <div className="flex items-start gap-3">
                      <div className="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center text-xl flex-shrink-0">
                        🏆
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-sm font-semibold">{a.name}</p>
                          <Badge variant="secondary" className="text-xs">
                            +{formatPoints(a.points)}
                          </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-0.5">{a.description}</p>
                        {detail && (
                          <p className="text-xs text-amber-700 mt-1">
                            Unlocked {formatRelativeTime(detail.unlocked_at)}
                          </p>
                        )}
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )
            })}
          </div>
        </div>
      )}

      {/* Locked */}
      {locked.length > 0 && (
        <div>
          <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
            Locked
          </h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {locked.map((a) => (
              <Card key={a.slug} className="opacity-60">
                <CardContent className="pt-5 pb-4">
                  <div className="flex items-start gap-3">
                    <div className="h-10 w-10 rounded-full bg-muted flex items-center justify-center flex-shrink-0">
                      <Lock className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <p className="text-sm font-semibold">{a.name}</p>
                        <Badge variant="outline" className="text-xs">
                          +{formatPoints(a.points)}
                        </Badge>
                      </div>
                      <p className="text-xs text-muted-foreground mt-0.5">{a.description}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

function AchievementsSkeleton() {
  return (
    <div className="space-y-6">
      <Skeleton className="h-8 w-40" />
      <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {[...Array(6)].map(num => (
          <Skeleton key={num} className="h-24 rounded-xl" />
        ))}
      </div>
    </div>
  )
}
