/**
 * Formatting Utilities
 * 
 * Centralized formatting functions for consistent display across the app
 */

/**
 * Format a number as currency
 */
export function formatCurrency(value, currency = 'USD', options = {}) {
  const defaultOptions = {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 8,
    ...options
  }
  
  return new Intl.NumberFormat('en-US', defaultOptions).format(value)
}

/**
 * Format a large number with K/M/B suffix
 */
export function formatCompactNumber(value) {
  if (value >= 1_000_000_000) {
    return `${(value / 1_000_000_000).toFixed(2)}B`
  }
  if (value >= 1_000_000) {
    return `${(value / 1_000_000).toFixed(2)}M`
  }
  if (value >= 1_000) {
    return `${(value / 1_000).toFixed(2)}K`
  }
  return value.toFixed(0)
}

/**
 * Format a percentage
 */
export function formatPercentage(value, decimals = 2) {
  const sign = value >= 0 ? '+' : ''
  return `${sign}${value.toFixed(decimals)}%`
}

/**
 * Format a decimal to percentage
 */
export function formatDecimalAsPercentage(value, decimals = 0) {
  return `${(value * 100).toFixed(decimals)}%`
}

/**
 * Format a date
 */
export function formatDate(date, format = 'short') {
  const d = new Date(date)
  
  const formats = {
    short: {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    },
    long: {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    },
    time: {
      hour: '2-digit',
      minute: '2-digit'
    },
    datetime: {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  }
  
  return d.toLocaleString('en-US', formats[format] || formats.short)
}

/**
 * Format a relative time (e.g., "2 hours ago")
 */
export function formatRelativeTime(date) {
  const now = new Date()
  const d = new Date(date)
  const seconds = Math.floor((now - d) / 1000)
  
  const intervals = {
    year: 31536000,
    month: 2592000,
    week: 604800,
    day: 86400,
    hour: 3600,
    minute: 60,
    second: 1
  }
  
  for (const [unit, secondsInUnit] of Object.entries(intervals)) {
    const interval = Math.floor(seconds / secondsInUnit)
    if (interval >= 1) {
      return `${interval} ${unit}${interval !== 1 ? 's' : ''} ago`
    }
  }
  
  return 'just now'
}

/**
 * Format a score to fixed decimals
 */
export function formatScore(value, decimals = 3) {
  return Number(value).toFixed(decimals)
}

/**
 * Format a price with appropriate decimals
 */
export function formatPrice(value, minDecimals = 2, maxDecimals = 8) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: minDecimals,
    maximumFractionDigits: maxDecimals
  }).format(value)
}

/**
 * Format volume with compact notation
 */
export function formatVolume(value) {
  return formatCurrency(formatCompactNumber(value))
}

/**
 * Truncate text with ellipsis
 */
export function truncateText(text, maxLength = 100) {
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

/**
 * Format a time horizon
 */
export function formatTimeHorizon(horizon) {
  const map = {
    short_term: 'Short Term',
    medium_term: 'Medium Term',
    long_term: 'Long Term'
  }
  return map[horizon] || horizon
}

/**
 * Format risk level
 */
export function formatRiskLevel(level) {
  return level?.toUpperCase() || 'UNKNOWN'
}

/**
 * Format recommendation
 */
export function formatRecommendation(recommendation) {
  return recommendation?.toUpperCase() || 'UNKNOWN'
}

/**
 * Capitalize first letter
 */
export function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase()
}

/**
 * Format sentiment label
 */
export function formatSentiment(polarity) {
  if (polarity > 0.3) return 'Positive'
  if (polarity < -0.3) return 'Negative'
  return 'Neutral'
}

/**
 * Format percent (alias for formatPercentage)
 */
export function formatPercent(value, decimals = 2) {
  return formatPercentage(value, decimals)
}

/**
 * Format number with locale formatting
 */
export function formatNumber(value, decimals = 2) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  }).format(value)
}

/**
 * Format date and time together
 */
export function formatDateTime(date) {
  return formatDate(date, 'datetime')
}

/**
 * Format price change with sign and color indication
 */
export function formatChange(value, decimals = 2) {
  const sign = value >= 0 ? '+' : ''
  return `${sign}${value.toFixed(decimals)}%`
}

/**
 * Format confidence score as percentage
 */
export function formatConfidence(value) {
  if (typeof value === 'number') {
    return `${Math.round(value * 100)}%`
  }
  return '0%'
}

/**
 * Format signal strength indicator
 */
export function formatSignal(signal) {
  if (typeof signal === 'number') {
    if (signal > 0.5) return 'Strong Buy'
    if (signal > 0) return 'Buy'
    if (signal < -0.5) return 'Strong Sell'
    if (signal < 0) return 'Sell'
    return 'Neutral'
  }
  return String(signal).toUpperCase()
}
