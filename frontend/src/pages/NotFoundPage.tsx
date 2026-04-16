import { Link } from 'react-router-dom'
import { useAuth } from '@/store/AuthContext'
import { Button } from '@/components/ui/button'

export default function NotFoundPage() {
  const { isAuthenticated, user } = useAuth()
  const home = !isAuthenticated
    ? '/login'
    : user?.role === 'admin' ? '/admin' : '/dashboard'

  return (
    <div className="min-h-screen flex flex-col items-center justify-center gap-4 text-center p-4">
      <p className="text-8xl font-bold text-muted-foreground/30">404</p>
      <h1 className="text-2xl font-bold">Page not found</h1>
      <p className="text-muted-foreground text-sm max-w-sm">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <Button asChild>
        <Link to={home}>Go home</Link>
      </Button>
    </div>
  )
}
