// API Envelope

export interface ApiResponse<T = undefined> {
  success: boolean
  status_code: number
  message: string
  data?: T
  errors?: Record<string, string[]>
}

export interface PaginatedData<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// Auth

export type UserRole = 'customer' | 'admin'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  loyalty_points: number
  total_spent: number
  current_badge: Badge | null
}

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}

// Loyalty

export interface Badge {
  id: number
  name: string
  slug: string
  description: string
  icon: string
  cashback_percent: number
  level: number
}

export interface Achievement {
  id: number
  name: string
  slug: string
  description: string
  icon: string
  points_reward: number
  unlocked_at: string
}

export interface NextBadge {
  name: string
  min_points: number
  points_needed: number
  progress_percent: number
}

export interface LoyaltyProfile {
  user: {
    id: number
    name: string
    loyalty_points: number
    total_spent: number
  }
  current_badge: Badge | null
  next_badge: NextBadge | null
  achievements: Achievement[]
  badge_history: {
    name: string
    icon: string
    is_current: boolean
    earned_at: string
  }[]
  stats: {
    total_achievements: number
    purchase_count: number
  }
}

// Purchases

export interface Purchase {
  reference: string
  amount: number
  cashback_amount: number
  status: 'pending' | 'completed' | 'failed'
  completed_at: string | null
}

export interface InitiatePaymentResponse {
  reference: string
  authorization_url: string
  access_code: string
}

// Admin

export interface AdminUser {
  id: number
  name: string
  email: string
  loyalty_points: number
  total_spent: number
  total_achievements: number
  current_badge: Pick<Badge, 'name' | 'slug' | 'icon' | 'level'> | null
}

export interface AdminUserDetail {
  user: {
    id: number
    name: string
    email: string
    loyalty_points: number
    total_spent: number
    created_at: string
  }
  current_badge: Badge | null
  achievements: Pick<Achievement, 'id' | 'name' | 'icon' | 'unlocked_at'>[]
  recent_purchases: Purchase[]
  cashback_summary: {
    total_paid: number
    total_pending: number
  }
}

// Forms

export interface LoginForm {
  email: string
  password: string
}

export interface RegisterForm {
  name: string
  email: string
  password: string
  confirm_password: string
}
