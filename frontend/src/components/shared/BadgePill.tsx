import { cn } from '@/utils/cn'

const badgeColors: Record<string, string> = {
  bronze:   'bg-amber-100 text-amber-800 border-amber-200',
  silver:   'bg-slate-100 text-slate-700 border-slate-200',
  gold:     'bg-yellow-100 text-yellow-800 border-yellow-200',
  platinum: 'bg-cyan-100 text-cyan-800 border-cyan-200',
}

export function BadgePill({ name, slug }: { name: string; slug: string }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold',
        badgeColors[slug] ?? 'bg-muted text-muted-foreground border-border'
      )}
    >
      {name}
    </span>
  )
}
