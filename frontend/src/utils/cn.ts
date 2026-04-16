import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

/**
 * cn — combines clsx + tailwind-merge.
 * Resolves Tailwind class conflicts intelligently:
 *   cn('px-2 py-1', 'px-4') → 'py-1 px-4'  (px-2 is overridden)
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}
