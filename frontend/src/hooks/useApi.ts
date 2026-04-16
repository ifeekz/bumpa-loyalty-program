import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { loyaltyApi, purchasesApi, adminApi, type AdminUsersParams } from '@/api'
import { useAuth } from '@/store/AuthContext'

// Query keys - centralised to avoid typo bugs

export const queryKeys = {
  loyaltyProfile: (userId: number)  => ['loyalty', 'profile', userId] as const,
  purchases:                          () => ['purchases'] as const,
  adminUsers:     (params?: AdminUsersParams) => ['admin', 'users', params] as const,
  adminUser:      (userId: number)  => ['admin', 'users', userId] as const,
}

// Customer hooks

export function useLoyaltyProfile() {
  const { user } = useAuth()

  return useQuery({
    queryKey:  queryKeys.loyaltyProfile(user!.id),
    queryFn:   () => loyaltyApi.getProfile(user!.id),
    enabled:   !!user,
    // Refetch when the window regains focus - keeps points/badges fresh
    // after the user completes a payment in a new tab
    refetchOnWindowFocus: true,
    staleTime: 30_000, // 30 seconds
    select:    (data) => data.data,
  })
}

export function usePurchaseHistory() {
  return useQuery({
    queryKey: queryKeys.purchases(),
    queryFn:  purchasesApi.getHistory,
    staleTime: 60_000,
    select:    (data) => data.data,
  })
}

export function useInitiatePurchase() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (amount: number) => purchasesApi.initiate(amount),
    onSuccess: () => {
      // Invalidate purchases so the list refreshes after payment
      queryClient.invalidateQueries({ queryKey: queryKeys.purchases() })
    },
  })
}

// Admin hooks

export function useAdminUsers(params?: AdminUsersParams) {
  return useQuery({
    queryKey: queryKeys.adminUsers(params),
    queryFn:  () => adminApi.getUsers(params),
    staleTime: 30_000,
    select:    (data) => data.data,
  })
}

export function useAdminUser(userId: number) {
  return useQuery({
    queryKey: queryKeys.adminUser(userId),
    queryFn:  () => adminApi.getUser(userId),
    enabled:  !!userId,
    staleTime: 60_000,
    select:    (data) => data.data,
  })
}
