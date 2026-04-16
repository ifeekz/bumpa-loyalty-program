import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '@/store/AuthContext'

/**
 * ProtectedRoute
 *
 * Blocks unauthenticated users from accessing any route inside it.
 * Saves the attempted URL so we can redirect back after login.
 */
export function ProtectedRoute() {
  const { isAuthenticated } = useAuth()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return <Outlet />
}

/**
 * AdminRoute
 *
 * Blocks non-admin users from accessing admin routes.
 * A customer who somehow navigates to /admin is redirected to their
 * dashboard — not to login (they ARE authenticated, just not authorised).
 *
 * The role check here is a UI-level guard only. The real enforcement
 * is the AdminMiddleware on the backend — the API will return 403 even
 * if someone bypasses this component.
 */
export function AdminRoute() {
  const { user, isAuthenticated } = useAuth()

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  if (user?.role !== 'admin') {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}

/**
 * GuestRoute
 *
 * Redirects already-authenticated users away from auth pages
 * (login, register) to their appropriate dashboard.
 */
export function GuestRoute() {
  const { isAuthenticated, user } = useAuth()

  if (isAuthenticated) {
    return <Navigate to={user?.role === 'admin' ? '/admin' : '/dashboard'} replace />
  }

  return <Outlet />
}
