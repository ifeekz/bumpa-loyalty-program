import {
  createContext,
  useContext,
  useState,
  useCallback,
  type ReactNode,
} from 'react'
import { tokenStorage } from '@/lib/tokenStorage'
import { authApi } from '@/api/auth'
import type { User, LoginForm, RegisterForm } from '@/types'

// Context shape

interface AuthContextValue {
  user:            User | null
  isAuthenticated: boolean
  isLoading:       boolean
  login:           (data: LoginForm)    => Promise<void>
  register:        (data: RegisterForm) => Promise<void>
  logout:          () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

// Provider

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user,      setUser]      = useState<User | null>(() => tokenStorage.getUser<User>())
  const [isLoading, setIsLoading] = useState(false)

  const login = useCallback(async (data: LoginForm) => {
    setIsLoading(true)
    try {
      const response = await authApi.login(data)
      if (response.data) {
        setUser(response.data)
        tokenStorage.setUser(response.data)
        // Token is extracted from response headers inside the axios interceptor
      }
    } finally {
      setIsLoading(false)
    }
  }, [])

  const register = useCallback(async (data: RegisterForm) => {
    setIsLoading(true)
    try {
      const response = await authApi.register(data)
      if (response.data) {
        setUser(response.data)
        tokenStorage.setUser(response.data)
      }
    } finally {
      setIsLoading(false)
    }
  }, [])

  const logout = useCallback(async () => {
    try {
      await authApi.logout()
    } finally {
      // Always clear local state even if the API call fails
      setUser(null)
      tokenStorage.clear()
    }
  }, [])

  return (
    <AuthContext.Provider
      value={{
        user,
        isAuthenticated: !!user && !!tokenStorage.getToken(),
        isLoading,
        login,
        register,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

// Hook

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>')
  return ctx
}
