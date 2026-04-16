import { apiClient } from '@/lib/apiClient'
import type { ApiResponse, User, LoginForm, RegisterForm } from '@/types'

export const authApi = {
  register: async (data: RegisterForm) => {
    const response = await apiClient.post<ApiResponse<User>>('/auth/register', data)
    return response.data
  },

  login: async (data: LoginForm) => {
    const response = await apiClient.post<ApiResponse<User>>('/auth/login', data)
    return response.data
  },

  logout: async () => {
    const response = await apiClient.post<ApiResponse>('/auth/logout')
    return response.data
  },

  refresh: async () => {
    const response = await apiClient.post<ApiResponse>('/auth/refresh')
    return response.data
  },

  me: async () => {
    const response = await apiClient.get<ApiResponse<User>>('/auth/me')
    return response.data
  },
}
