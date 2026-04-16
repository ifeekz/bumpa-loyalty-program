import { useState } from 'react'
import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '@/store/AuthContext'
import { cn }      from '@/utils/cn'
import { LayoutDashboard, Trophy, ShoppingBag, LogOut, Menu, X } from 'lucide-react'
import { Button } from '@/components/ui/button'

const customerNav = [
  { to: '/dashboard',    label: 'Dashboard',    icon: LayoutDashboard },
  { to: '/achievements', label: 'Achievements', icon: Trophy },
  { to: '/purchases',    label: 'Purchases',    icon: ShoppingBag },
]

export function AppLayout() {
  const { user, logout } = useAuth()
  const navigate         = useNavigate()
  const [mobileOpen, setMobileOpen] = useState(false)

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  return (
    <div className="min-h-screen bg-background flex">
      {/* Sidebar desktop */}
      <aside className="hidden lg:flex flex-col w-64 border-r bg-card px-4 py-6 gap-6">
        <SidebarBrand />
        <nav className="flex flex-col gap-1 flex-1">
          {customerNav.map((item) => (
            <SidebarLink key={item.to} {...item} />
          ))}
        </nav>
        <UserFooter user={user} onLogout={handleLogout} />
      </aside>

      {/* Mobile sidebar overlay */}
      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div
            className="absolute inset-0 bg-black/50"
            onClick={() => setMobileOpen(false)}
          />
          <aside className="relative flex flex-col w-64 h-full border-r bg-card px-4 py-6 gap-6 z-50">
            <SidebarBrand />
            <nav className="flex flex-col gap-1 flex-1">
              {customerNav.map((item) => (
                <SidebarLink
                  key={item.to}
                  {...item}
                  onClick={() => setMobileOpen(false)}
                />
              ))}
            </nav>
            <UserFooter user={user} onLogout={handleLogout} />
          </aside>
        </div>
      )}

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="lg:hidden flex items-center justify-between px-4 py-3 border-b bg-card">
          <SidebarBrand />
          <Button
            variant="ghost"
            size="icon"
            onClick={() => setMobileOpen(!mobileOpen)}
          >
            {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </Button>
        </header>
        <main className="flex-1 p-6 overflow-auto">
          <Outlet />
        </main>
      </div>
    </div>
  )
}

// Shared sidebar pieces

function SidebarBrand() {
  return (
    <div className="flex items-center gap-2 px-2">
      <div className="h-8 w-8 rounded-lg bg-primary/10 flex items-center justify-center">
        <ShoppingBag className="h-4 w-4 text-emerald-600" />
      </div>
      <span className="font-semibold text-sm">Bumpa Loyalty</span>
    </div>
  )
}

function SidebarLink({
  to,
  label,
  icon: Icon,
  onClick,
}: {
  to:       string
  label:    string
  icon:     React.ElementType
  onClick?: () => void
}) {
  return (
    <NavLink
      to={to}
      onClick={onClick}
      className={({ isActive }) =>
        cn(
          'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
          isActive
            ? 'bg-primary text-primary-foreground font-medium'
            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
        )
      }
    >
      <Icon className="h-4 w-4" />
      {label}
    </NavLink>
  )
}

function UserFooter({
  user,
  onLogout,
}: {
  user:     ReturnType<typeof useAuth>['user']
  onLogout: () => void
}) {
  return (
    <div className="border-t pt-4 flex flex-col gap-2">
      <div className="px-2">
        <p className="text-sm font-medium truncate">{user?.name}</p>
        <p className="text-xs text-muted-foreground truncate">{user?.email}</p>
      </div>
      <Button
        variant="ghost"
        size="sm"
        className="justify-start gap-2 text-muted-foreground hover:text-destructive"
        onClick={onLogout}
      >
        <LogOut className="h-4 w-4" />
        Sign out
      </Button>
    </div>
  )
}
