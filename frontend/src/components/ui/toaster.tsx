import * as React from 'react'
import * as ToastPrimitive from '@radix-ui/react-toast'
import { X } from 'lucide-react'
import { cn } from '@/utils/cn'

// ─── Context ──────────────────────────────────────────────────────────────────

interface ToastOptions {
  title:        string
  description?: string
  variant?:     'default' | 'destructive'
}

interface ToastContextValue {
  toast: (opts: ToastOptions) => void
}

const ToastContext = React.createContext<ToastContextValue>({ toast: () => {} })

export function useToast() {
  return React.useContext(ToastContext)
}

// ─── Provider + renderer ──────────────────────────────────────────────────────

interface ToastItem extends ToastOptions {
  id: string
}

export function Toaster({ children }: { children?: React.ReactNode }) {
  const [toasts, setToasts] = React.useState<ToastItem[]>([])

  const toast = React.useCallback((opts: ToastOptions) => {
    const id = crypto.randomUUID()
    setToasts((prev) => [...prev, { ...opts, id }])
    setTimeout(() => setToasts((prev) => prev.filter((t) => t.id !== id)), 4500)
  }, [])

  return (
    <ToastContext.Provider value={{ toast }}>
      <ToastPrimitive.Provider swipeDirection="right">
        {children}
        {toasts.map((t) => (
          <ToastPrimitive.Root
            key={t.id}
            className={cn(
              'fixed bottom-4 right-4 z-50 flex items-start gap-3 rounded-lg border p-4 shadow-lg w-80 animate-slide-in-right',
              t.variant === 'destructive'
                ? 'bg-destructive text-destructive-foreground border-destructive/50'
                : 'bg-card text-card-foreground'
            )}
          >
            <div className="flex-1 min-w-0">
              <ToastPrimitive.Title className="text-sm font-semibold">
                {t.title}
              </ToastPrimitive.Title>
              {t.description && (
                <ToastPrimitive.Description className="text-xs opacity-80 mt-0.5">
                  {t.description}
                </ToastPrimitive.Description>
              )}
            </div>
            <ToastPrimitive.Close
              className="opacity-50 hover:opacity-100 transition-opacity flex-shrink-0 mt-0.5"
              aria-label="Close"
            >
              <X className="h-4 w-4" />
            </ToastPrimitive.Close>
          </ToastPrimitive.Root>
        ))}
        <ToastPrimitive.Viewport />
      </ToastPrimitive.Provider>
    </ToastContext.Provider>
  )
}
