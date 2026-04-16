import { useState } from 'react'
import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '@/store/AuthContext'
import { cn }      from '@/utils/cn'
import { Users, BarChart3, LogOut, Menu, X } from 'lucide-react'
import { Button } from '@/components/ui/button'

const adminNav = [
  { to: '/admin', label: 'Customers', icon: Users, end: true },
]

export function AdminLayout() {
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
      <aside className="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:flex flex-col w-64 h-screen border-r bg-card px-4 py-6 gap-6">
        <div className="flex items-center gap-2 px-2">
          <div className="h-8 w-8 rounded bg-destructive/10 flex items-center justify-center">
            <BarChart3 className="h-4 w-4 text-destructive" />
          </div>
          <span className="font-semibold text-sm">Admin Panel</span>
        </div>
        <nav className="flex flex-col gap-1 flex-1">
          {adminNav.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
                  isActive
                    ? 'bg-primary text-primary-foreground font-medium'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )
              }
            >
              <item.icon className="h-4 w-4" />
              {item.label}
            </NavLink>
          ))}
        </nav>
        <div className="border-t pt-4 flex flex-col gap-2">
          <div className="px-2">
            <p className="text-sm font-medium truncate">{user?.name}</p>
            <p className="text-xs text-muted-foreground truncate">{user?.email}</p>
            <span className="text-xs bg-destructive/10 text-destructive rounded px-1.5 py-0.5 mt-1 inline-block">
              Admin
            </span>
          </div>
          <Button
            variant="ghost"
            size="sm"
            className="justify-start gap-2 text-muted-foreground hover:text-destructive"
            onClick={handleLogout}
          >
            <LogOut className="h-4 w-4" />
            Sign out
          </Button>
        </div>
      </aside>

      {/* Mobile header */}
      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div
            className="absolute inset-0 bg-black/50"
            onClick={() => setMobileOpen(false)}
          />
          <aside className="relative flex flex-col w-64 h-full border-r bg-card px-4 py-6 gap-6 z-50">
            <nav className="flex flex-col gap-1 flex-1">
              {adminNav.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.end}
                  onClick={() => setMobileOpen(false)}
                  className={({ isActive }) =>
                    cn(
                      'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
                      isActive
                        ? 'bg-primary text-primary-foreground font-medium'
                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                    )
                  }
                >
                  <item.icon className="h-4 w-4" />
                  {item.label}
                </NavLink>
              ))}
            </nav>
          </aside>
        </div>
      )}

      <div className="flex-1 flex flex-col min-w-0 lg:pl-64">
        <header className="lg:hidden flex items-center justify-between px-4 py-3 border-b bg-card">
          <span className="font-semibold text-sm">Admin Panel</span>
          <Button variant="ghost" size="icon" onClick={() => setMobileOpen(!mobileOpen)}>
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
