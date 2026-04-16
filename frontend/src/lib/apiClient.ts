import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { tokenStorage } from '@/lib/tokenStorage'
import type { ApiResponse } from '@/types'

// Client

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
  },
  timeout: 15000,
})

// Request interceptor - attach token

apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = tokenStorage.getToken()
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor - token rotation + error normalisation

let isRefreshing  = false
let refreshQueue: Array<(token: string) => void> = []

const processQueue = (token: string) => {
  refreshQueue.forEach((cb) => cb(token))
  refreshQueue = []
}

apiClient.interceptors.response.use(
  (response) => {
    // Extract and store the rotated token if the backend issued a new one.
    // This handles login, register, and refresh responses automatically.
    const authHeader = response.headers['authorization']
    if (authHeader?.startsWith('Bearer ')) {
      tokenStorage.setToken(authHeader.replace('Bearer ', ''))
    }
    return response
  },

  async (error: AxiosError<ApiResponse>) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

    // 401 handling: attempt silent token refresh
    // If the server says the token expired (not "invalid" or "not provided"),
    // we try one silent refresh before forcing the user to log in again.
    if (
      error.response?.status === 401 &&
      error.response.data?.message === 'Token has expired.' &&
      !originalRequest._retry
    ) {
      if (isRefreshing) {
        // Another request already triggered a refresh — queue this one
        return new Promise((resolve) => {
          refreshQueue.push((token) => {
            originalRequest.headers.Authorization = `Bearer ${token}`
            resolve(apiClient(originalRequest))
          })
        })
      }

      originalRequest._retry = true
      isRefreshing = true

      try {
        const { data, headers } = await apiClient.post<ApiResponse>('/auth/refresh')
        const newToken = headers['authorization']?.replace('Bearer ', '')

        if (newToken) {
          tokenStorage.setToken(newToken)
          processQueue(newToken)
          originalRequest.headers.Authorization = `Bearer ${newToken}`
          return apiClient(originalRequest)
        }
      } catch {
        // Refresh failed — session is unrecoverable, force logout
        tokenStorage.clear()
        window.location.href = '/login'
        return Promise.reject(error)
      } finally {
        isRefreshing = false
      }
    }

    // Hard 401: token invalid or missing — force logout
    if (error.response?.status === 401) {
      tokenStorage.clear()
      window.location.href = '/login'
    }

    return Promise.reject(error)
  }
)
