import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Search, ChevronLeft, ChevronRight } from 'lucide-react'
import { useAdminUsers } from '@/hooks/useApi'
import { Card, CardContent } from '@/components/ui/card'
import { Input }  from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Skeleton, ErrorMessage, BadgePill } from '@/components/shared'
import { formatNaira, formatPoints } from '@/utils/formatters'
import { useDebounce } from '@/hooks/useDebounce'

const BADGE_FILTERS = [
  { label: 'All',      value: '' },
  { label: 'Bronze',   value: 'bronze' },
  { label: 'Silver',   value: 'silver' },
  { label: 'Gold',     value: 'gold' },
  { label: 'Platinum', value: 'platinum' },
]

export default function AdminUsersPage() {
  const [search,      setSearch]      = useState('')
  const [badgeFilter, setBadgeFilter] = useState('')
  const [page,        setPage]        = useState(1)

  const debouncedSearch = useDebounce(search, 400)

  const { data, isLoading, error } = useAdminUsers({
    search:   debouncedSearch || undefined,
    badge:    badgeFilter     || undefined,
    page,
    per_page: 20,
  })

  const users = data?.data ?? []
  const meta  = data?.meta

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Customers</h1>
        <p className="text-muted-foreground text-sm mt-1">
          {meta ? `${meta.total} total customers` : 'Loading...'}
        </p>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search by name or email..."
            className="pl-9"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
          />
        </div>
        <div className="flex gap-2 flex-wrap">
          {BADGE_FILTERS.map((f) => (
            <Button
              key={f.value}
              size="sm"
              variant={badgeFilter === f.value ? 'default' : 'outline'}
              onClick={() => { setBadgeFilter(f.value); setPage(1) }}
            >
              {f.label}
            </Button>
          ))}
        </div>
      </div>

      {error ? (
        <ErrorMessage message="Failed to load users." />
      ) : (
        <Card>
          <CardContent className="p-0">
            {isLoading ? (
              <div className="p-6 space-y-3">
                {[1, 2, 3, 4, 5].map((num) => (
                  <Skeleton key={num} className="h-12 rounded" />
                ))}
              </div>
            ) : users.length === 0 ? (
              <div className="text-center py-12 text-muted-foreground text-sm">
                No customers found
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-muted/50">
                      <th className="text-left px-6 py-3 font-medium text-muted-foreground">Customer</th>
                      <th className="text-left px-6 py-3 font-medium text-muted-foreground">Badge</th>
                      <th className="text-right px-6 py-3 font-medium text-muted-foreground">Points</th>
                      <th className="text-right px-6 py-3 font-medium text-muted-foreground">Spent</th>
                      <th className="text-right px-6 py-3 font-medium text-muted-foreground">Achievements</th>
                      <th className="px-6 py-3" />
                    </tr>
                  </thead>
                  <tbody>
                    {users.map((u) => (
                      <tr
                        key={u.id}
                        className="border-b last:border-0 hover:bg-muted/30 transition-colors"
                      >
                        <td className="px-6 py-4">
                          <p className="font-medium">{u.name}</p>
                          <p className="text-xs text-muted-foreground">{u.email}</p>
                        </td>
                        <td className="px-6 py-4">
                          {u.current_badge ? (
                            <BadgePill name={u.current_badge.name} slug={u.current_badge.slug} />
                          ) : (
                            <span className="text-muted-foreground text-xs">—</span>
                          )}
                        </td>
                        <td className="px-6 py-4 text-right font-mono text-xs">
                          {formatPoints(u.loyalty_points)}
                        </td>
                        <td className="px-6 py-4 text-right">{formatNaira(u.total_spent)}</td>
                        <td className="px-6 py-4 text-right">{u.total_achievements}</td>
                        <td className="px-6 py-4 text-right">
                          <Button asChild size="sm" variant="ghost">
                            <Link to={`/admin/users/${u.id}`}>View</Link>
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Page {meta.current_page} of {meta.last_page}
          </p>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="outline"
              disabled={page === 1}
              onClick={() => setPage((p) => p - 1)}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button
              size="sm"
              variant="outline"
              disabled={page === meta.last_page}
              onClick={() => setPage((p) => p + 1)}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
