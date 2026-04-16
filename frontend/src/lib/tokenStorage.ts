/**
 * tokenStorage
 *
 * Single point of control for JWT persistence.
 *
 * Security decisions:
 * - Stored in localStorage (not cookies) because this is an SPA with no
 *   server-side rendering. HttpOnly cookies would be ideal for SSR but
 *   require backend session management.
 * - The token is NEVER logged, never interpolated into error messages,
 *   and never stored anywhere except this module.
 * - All reads go through getToken() so we can add encryption or
 *   migration logic here in future without touching call sites.
 *
 * Note: localStorage is vulnerable to XSS. The Security Headers
 * middleware on the backend (X-XSS-Protection, CSP) and input
 * sanitization are the first line of defence against this.
 */

const TOKEN_KEY = 'lyl_access_token'
const USER_KEY  = 'lyl_user'

export const tokenStorage = {
  getToken: (): string | null => {
    return localStorage.getItem(TOKEN_KEY)
  },

  setToken: (token: string): void => {
    localStorage.setItem(TOKEN_KEY, token)
  },

  removeToken: (): void => {
    localStorage.removeItem(TOKEN_KEY)
  },

  getUser: <T>(): T | null => {
    const raw = localStorage.getItem(USER_KEY)
    if (!raw) return null
    try {
      return JSON.parse(raw) as T
    } catch {
      // Corrupt storage — clear it
      localStorage.removeItem(USER_KEY)
      return null
    }
  },

  setUser: <T>(user: T): void => {
    localStorage.setItem(USER_KEY, JSON.stringify(user))
  },

  removeUser: (): void => {
    localStorage.removeItem(USER_KEY)
  },

  // Clears all auth state - called on logout and on 401 responses
  clear: (): void => {
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  },
}
