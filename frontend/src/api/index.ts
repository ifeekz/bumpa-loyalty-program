import { apiClient } from '@/lib/apiClient'
import type {
  ApiResponse,
  LoyaltyProfile,
  Purchase,
  PaginatedData,
  InitiatePaymentResponse,
  AdminUser,
  AdminUserDetail,
} from '@/types'

// Loyalty

export const loyaltyApi = {
  getProfile: async (userId: number) => {
    const response = await apiClient.get<ApiResponse<LoyaltyProfile>>(
      `/users/${userId}/achievements`
    )
    return response.data
  },
}

// Purchases

export const purchasesApi = {
  getHistory: async () => {
    const response = await apiClient.get<ApiResponse<PaginatedData<Purchase>>>('/purchases')
    return response.data
  },

  initiate: async (amount: number) => {
    const response = await apiClient.post<ApiResponse<InitiatePaymentResponse>>('/purchases', {
      amount,
    })
    return response.data
  },
}

// Admin

export interface AdminUsersParams {
  search?:   string
  badge?:    string
  per_page?: number
  page?:     number
}

export const adminApi = {
  getUsers: async (params?: AdminUsersParams) => {
    const response = await apiClient.get<ApiResponse<PaginatedData<AdminUser>>>(
      '/admin/users/achievements',
      { params }
    )
    return response.data
  },

  getUser: async (userId: number) => {
    const response = await apiClient.get<ApiResponse<AdminUserDetail>>(
      `/admin/users/${userId}`
    )
    return response.data
  },
}
