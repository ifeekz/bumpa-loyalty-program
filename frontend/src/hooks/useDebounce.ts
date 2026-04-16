import { useState, useEffect } from 'react'

/**
 * useDebounce
 *
 * Delays updating the returned value until after the specified delay.
 * Used in the admin search input to avoid firing an API request on
 * every keystroke.
 */
export function useDebounce<T>(value: T, delay: number): T {
  const [debouncedValue, setDebouncedValue] = useState<T>(value)

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedValue(value), delay)
    return () => clearTimeout(timer)
  }, [value, delay])

  return debouncedValue
}
