/**
 * Format a number as Nigerian Naira
 * e.g. 15000 → "₦15,000.00"
 */
export function formatNaira(amount: number): string {
  return new Intl.NumberFormat('en-NG', {
    style:    'currency',
    currency: 'NGN',
  }).format(amount)
}

/**
 * Format loyalty points with comma separation
 * e.g. 1500 → "1,500 pts"
 */
export function formatPoints(points: number): string {
  return `${new Intl.NumberFormat('en-NG').format(points)} pts`
}

/**
 * Format ISO date string to readable local date
 * e.g. "2024-01-15T10:00:00Z" → "Jan 15, 2024"
 */
export function formatDate(dateString: string): string {
  return new Intl.DateTimeFormat('en-NG', {
    year:  'numeric',
    month: 'short',
    day:   'numeric',
  }).format(new Date(dateString))
}

/**
 * Format ISO date string to relative time
 * e.g. "2024-01-15T10:00:00Z" → "3 days ago"
 */
export function formatRelativeTime(dateString: string): string {
  const diff    = Date.now() - new Date(dateString).getTime()
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours   = Math.floor(minutes / 60)
  const days    = Math.floor(hours / 24)

  if (days > 30)    return formatDate(dateString)
  if (days > 0)     return `${days}d ago`
  if (hours > 0)    return `${hours}h ago`
  if (minutes > 0)  return `${minutes}m ago`
  return 'just now'
}

/**
 * Truncate a string to a max length with ellipsis
 */
export function truncate(str: string, maxLength: number): string {
  if (str.length <= maxLength) return str
  return `${str.slice(0, maxLength)}...`
}
