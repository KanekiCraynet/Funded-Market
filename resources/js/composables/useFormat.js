/**
 * Format Composable
 * Provides formatting utilities as a composable
 */

import { computed } from 'vue'
import * as formatters from '../utils/formatters'

export function useFormat() {
  return {
    // Direct access to all formatters
    ...formatters,
    
    // Computed helpers for common use cases
    formatPrice: formatters.formatPrice,
    formatPercent: formatters.formatPercent,
    formatNumber: formatters.formatNumber,
    formatDate: formatters.formatDate,
    formatDateTime: formatters.formatDateTime,
    formatRelativeTime: formatters.formatRelativeTime,
    formatChange: formatters.formatChange,
    formatConfidence: formatters.formatConfidence,
    formatRiskLevel: formatters.formatRiskLevel,
    formatRecommendation: formatters.formatRecommendation,
    formatSignal: formatters.formatSignal
  }
}

export default useFormat
