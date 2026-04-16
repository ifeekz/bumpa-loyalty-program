import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ExternalLink, Loader2, ShoppingBag } from 'lucide-react'
import { usePurchaseHistory, useInitiatePurchase } from '@/hooks/useApi'
import { useToast } from '@/components/ui/toaster'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input }  from '@/components/ui/input'
import { Label }  from '@/components/ui/label'
import { Skeleton, ErrorMessage } from '@/components/shared'
import { formatNaira, formatDate } from '@/utils/formatters'
import { cn } from '@/utils/cn'

const schema = z.object({
  amount: z
    .number({ invalid_type_error: 'Enter a valid amount' })
    .min(100, 'Minimum amount is ₦100'),
})

const statusStyles: Record<string, string> = {
  completed: 'bg-green-100 text-green-800 border-green-200',
  pending:   'bg-yellow-100 text-yellow-800 border-yellow-200',
  failed:    'bg-red-100 text-red-800 border-red-200',
}

export default function PurchasesPage() {
  const { data, isLoading, error } = usePurchaseHistory()
  const initiate  = useInitiatePurchase()
  const { toast } = useToast()

  const { register, handleSubmit, reset, formState: { errors } } = useForm<{ amount: number }>({
    resolver: zodResolver(schema),
  })

  const onSubmit = async ({ amount }: { amount: number }) => {
    try {
      const response = await initiate.mutateAsync(amount)
      if (response.data?.authorization_url) {
        window.open(response.data.authorization_url, '_blank', 'noopener,noreferrer')
        toast({ title: 'Payment initiated', description: 'Complete your payment in the new tab.' })
        reset()
      }
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Failed to initiate payment.'
      toast({ title: 'Error', description: message, variant: 'destructive' })
    }
  }

  if (isLoading) return <PurchasesSkeleton />
  if (error)     return <ErrorMessage message="Failed to load purchase history." />

  const purchases = data?.data ?? []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Purchases</h1>
        <p className="text-muted-foreground text-sm mt-1">Your purchase history and payments</p>
      </div>

      {/* Initiate payment */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Make a Purchase</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="flex gap-3 items-end flex-wrap">
            <div className="flex-1 min-w-[200px] space-y-2">
              <Label htmlFor="amount">Amount (₦)</Label>
              <Input
                id="amount"
                type="number"
                placeholder="e.g. 5000"
                {...register('amount', { valueAsNumber: true })}
              />
              {errors.amount && (
                <p className="text-xs text-destructive">{errors.amount.message}</p>
              )}
            </div>
            <Button type="submit" disabled={initiate.isPending}>
              {initiate.isPending ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <>
                  <ExternalLink className="h-4 w-4 mr-2" />
                  Pay with Paystack
                </>
              )}
            </Button>
          </form>
        </CardContent>
      </Card>

      {/* History */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">History</CardTitle>
        </CardHeader>
        <CardContent>
          {purchases.length === 0 ? (
            <div className="text-center py-10">
              <ShoppingBag className="h-10 w-10 text-muted-foreground mx-auto mb-3" />
              <p className="text-sm text-muted-foreground">No purchases yet</p>
            </div>
          ) : (
            <div className="space-y-1">
              {purchases.map((p) => (
                <div
                  key={p.reference}
                  className="flex items-center justify-between py-3 border-b last:border-0"
                >
                  <div>
                    <p className="text-sm font-medium">{formatNaira(p.amount)}</p>
                    <p className="text-xs text-muted-foreground font-mono">{p.reference}</p>
                    {p.completed_at && (
                      <p className="text-xs text-muted-foreground">{formatDate(p.completed_at)}</p>
                    )}
                  </div>
                  <div className="text-right space-y-1">
                    <span
                      className={cn(
                        'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold',
                        statusStyles[p.status]
                      )}
                    >
                      {p.status}
                    </span>
                    {p.cashback_amount > 0 && (
                      <p className="text-xs text-green-600 font-medium">
                        +{formatNaira(p.cashback_amount)} cashback
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

function PurchasesSkeleton() {
  return (
    <div className="space-y-6">
      <Skeleton className="h-8 w-32" />
      <Skeleton className="h-32 rounded-xl" />
      <Skeleton className="h-64 rounded-xl" />
    </div>
  )
}
