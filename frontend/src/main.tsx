import React from 'react'
import ReactDOM from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import { AuthProvider } from '@/store/AuthContext'
import { AppRouter }   from '@/router'
import { Toaster }     from '@/components/ui/toaster'
import './index.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Skip retrying 4xx errors - those are client errors, not transient failures
      retry: (failureCount, error: unknown) => {
        const status = (error as { response?: { status?: number } })?.response?.status
        if (status && status >= 400 && status < 500) return false
        return failureCount < 2
      },
      staleTime: 30_000,
    },
    mutations: {
      retry: false,
    },
  },
})

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      {/* Toaster wraps the whole app so useToast() works anywhere in the tree */}
      <Toaster>
        <AuthProvider>
          <AppRouter />
        </AuthProvider>
      </Toaster>
      {import.meta.env.DEV && <ReactQueryDevtools initialIsOpen={false} />}
    </QueryClientProvider>
  </React.StrictMode>
)
