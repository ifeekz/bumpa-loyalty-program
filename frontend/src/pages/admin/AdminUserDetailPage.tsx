import { useParams, Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { useAdminUser } from '@/hooks/useApi'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Skeleton, ErrorMessage, BadgePill, StatCard } from '@/components/shared'
import { formatNaira, formatPoints, formatDate, formatRelativeTime } from '@/utils/formatters'
import { cn } from '@/utils/cn'

const statusStyles: Record<string, string> = {
  completed: 'text-green-700',
  pending:   'text-yellow-700',
  failed:    'text-red-700',
}

export default function AdminUserDetailPage() {
  const { id }                     = useParams<{ id: string }>()
  const { data, isLoading, error } = useAdminUser(Number(id))

  if (isLoading) return <DetailSkeleton />
  if (error)     return <ErrorMessage message="Failed to load user profile." />
  if (!data)     return <ErrorMessage message="User not found." />

  const { user, current_badge, achievements, recent_purchases, cashback_summary } = data

  return (
    <div className="space-y-6">
      <Button asChild variant="ghost" size="sm">
        <Link to="/admin">
          <ArrowLeft className="h-4 w-4 mr-1" />
          Back to customers
        </Link>
      </Button>

      {/* Profile header */}
      <div className="flex items-start gap-4">
        <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center font-bold text-lg flex-shrink-0">
          {user.name.charAt(0).toUpperCase()}
        </div>
        <div className="flex-1 min-w-0">
          <h1 className="text-xl font-bold">{user.name}</h1>
          <p className="text-muted-foreground text-sm">{user.email}</p>
          <p className="text-xs text-muted-foreground mt-0.5">
            Member since {formatDate(user.created_at)}
          </p>
        </div>
        {current_badge && (
          <BadgePill name={current_badge.name} slug={current_badge.slug} />
        )}
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard title="Loyalty Points"   value={formatPoints(user.loyalty_points)} />
        <StatCard title="Total Spent"      value={formatNaira(user.total_spent)} />
        <StatCard title="Cashback Paid"    value={formatNaira(cashback_summary.total_paid)} />
        <StatCard title="Cashback Pending" value={formatNaira(cashback_summary.total_pending)} />
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        {/* Achievements */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">
              Achievements ({achievements.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {achievements.length === 0 ? (
              <p className="text-sm text-muted-foreground">No achievements yet</p>
            ) : (
              <ul className="space-y-3">
                {achievements.map((a) => (
                  <li key={a.id} className="flex items-center gap-3">
                    <div className="h-7 w-7 rounded-full bg-amber-100 flex items-center justify-center text-sm flex-shrink-0">
                      🏆
                    </div>
                    <div>
                      <p className="text-sm font-medium">{a.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {formatRelativeTime(a.unlocked_at)}
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>

        {/* Recent purchases */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Recent Purchases</CardTitle>
          </CardHeader>
          <CardContent>
            {recent_purchases.length === 0 ? (
              <p className="text-sm text-muted-foreground">No purchases yet</p>
            ) : (
              <ul className="divide-y">
                {recent_purchases.map((p) => (
                  <li key={p.reference} className="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                    <div>
                      <p className="text-sm font-medium">{formatNaira(p.amount)}</p>
                      <p className="text-xs text-muted-foreground font-mono truncate max-w-[140px]">
                        {p.reference}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className={cn('text-xs font-medium capitalize', statusStyles[p.status])}>
                        {p.status}
                      </p>
                      {p.cashback_amount > 0 && (
                        <p className="text-xs text-green-600">
                          +{formatNaira(p.cashback_amount)}
                        </p>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

function DetailSkeleton() {
  return (
    <div className="space-y-6">
      <Skeleton className="h-9 w-36" />
      <div className="flex gap-4">
        <Skeleton className="h-12 w-12 rounded-full flex-shrink-0" />
        <div className="space-y-2 flex-1">
          <Skeleton className="h-6 w-40" />
          <Skeleton className="h-4 w-56" />
        </div>
      </div>
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((num) => (
          <Skeleton key={num} className="h-24 rounded-xl" />
        ))}
      </div>
      <div className="grid lg:grid-cols-2 gap-6">
        <Skeleton className="h-64 rounded-xl" />
        <Skeleton className="h-64 rounded-xl" />
      </div>
    </div>
  )
}
