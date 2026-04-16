import { lazy, Suspense } from 'react'
import { createBrowserRouter, RouterProvider, Navigate } from 'react-router-dom'
import { ProtectedRoute, AdminRoute, GuestRoute } from '@/components/auth/RouteGuards'
import { AppLayout }   from '@/components/layout/AppLayout'
import { AdminLayout } from '@/components/layout/AdminLayout'
import { PageLoader }  from '@/components/shared/PageLoader'
import { useAuth }     from '@/store/AuthContext'

const LoginPage           = lazy(() => import('@/pages/auth/LoginPage'))
const RegisterPage        = lazy(() => import('@/pages/auth/RegisterPage'))
const DashboardPage       = lazy(() => import('@/pages/customer/DashboardPage'))
const AchievementsPage    = lazy(() => import('@/pages/customer/AchievementsPage'))
const PurchasesPage       = lazy(() => import('@/pages/customer/PurchasesPage'))
const AdminUsersPage      = lazy(() => import('@/pages/admin/AdminUsersPage'))
const AdminUserDetailPage = lazy(() => import('@/pages/admin/AdminUserDetailPage'))
const NotFoundPage        = lazy(() => import('@/pages/NotFoundPage'))

const wrap = (C: React.ComponentType) => (
  <Suspense fallback={<PageLoader />}><C /></Suspense>
)

function RootRedirect() {
  const { isAuthenticated, user } = useAuth()
  if (!isAuthenticated) return <Navigate to="/login" replace />
  return <Navigate to={user?.role === 'admin' ? '/admin' : '/dashboard'} replace />
}

const router = createBrowserRouter([
  // Guest only - redirect if already logged in
  {
    element: <GuestRoute />,
    children: [
      { path: '/login',    element: wrap(LoginPage) },
      { path: '/register', element: wrap(RegisterPage) },
    ],
  },
  // Customer routes
  {
    element: <ProtectedRoute />,
    children: [{
      element: <AppLayout />,
      children: [
        { path: '/dashboard',    element: wrap(DashboardPage) },
        { path: '/achievements', element: wrap(AchievementsPage) },
        { path: '/purchases',    element: wrap(PurchasesPage) },
      ],
    }],
  },
  // Admin routes
  {
    element: <AdminRoute />,
    children: [{
      element: <AdminLayout />,
      children: [
        { path: '/admin',           element: wrap(AdminUsersPage) },
        { path: '/admin/users/:id', element: wrap(AdminUserDetailPage) },
      ],
    }],
  },
  { path: '/', element: <RootRedirect /> },
  { path: '*', element: wrap(NotFoundPage) },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
