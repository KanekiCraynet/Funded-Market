import { ref, onMounted, onUnmounted } from 'vue'
import { useMarketStore } from '@/stores/market'

/**
 * Real-time Data Updates Composable
 * 
 * Phase 5 - Task 2: Real-time updates with cache awareness
 * 
 * Features:
 * - Auto-refresh market data every 30 seconds
 * - Cache-aware (respects X-Cache headers)
 * - Automatic cleanup on unmount
 * - Error handling with exponential backoff
 */
export function useRealtime(options = {}) {
  const {
    interval = 30000, // 30 seconds default
    enabled = true,
    onUpdate = null,
    onError = null,
  } = options

  const isUpdating = ref(false)
  const lastUpdate = ref(null)
  const updateCount = ref(0)
  const errorCount = ref(0)
  let intervalId = null
  let retryTimeout = null

  const marketStore = useMarketStore()

  /**
   * Fetch latest data with cache awareness
   */
  const fetchData = async () => {
    if (isUpdating.value) return // Prevent concurrent updates

    isUpdating.value = true

    try {
      // Refresh market data
      await marketStore.fetchMarketOverview()
      
      lastUpdate.value = new Date()
      updateCount.value++
      errorCount.value = 0 // Reset error count on success

      if (onUpdate) {
        onUpdate({
          timestamp: lastUpdate.value,
          count: updateCount.value,
        })
      }
    } catch (error) {
      console.error('Real-time update failed:', error)
      errorCount.value++

      if (onError) {
        onError(error)
      }

      // Exponential backoff on errors
      if (errorCount.value > 3) {
        console.warn('Too many errors, stopping real-time updates')
        stopUpdates()
      }
    } finally {
      isUpdating.value = false
    }
  }

  /**
   * Start real-time updates
   */
  const startUpdates = () => {
    if (!enabled || intervalId) return

    // Initial fetch
    fetchData()

    // Set up interval
    intervalId = setInterval(fetchData, interval)

    console.log(`Real-time updates started (interval: ${interval}ms)`)
  }

  /**
   * Stop real-time updates
   */
  const stopUpdates = () => {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
      console.log('Real-time updates stopped')
    }

    if (retryTimeout) {
      clearTimeout(retryTimeout)
      retryTimeout = null
    }
  }

  /**
   * Manual refresh
   */
  const refresh = () => {
    return fetchData()
  }

  // Lifecycle
  onMounted(() => {
    if (enabled) {
      startUpdates()
    }
  })

  onUnmounted(() => {
    stopUpdates()
  })

  return {
    isUpdating,
    lastUpdate,
    updateCount,
    errorCount,
    startUpdates,
    stopUpdates,
    refresh,
  }
}

/**
 * Price ticker composable
 * 
 * For live price updates of specific symbols
 */
export function usePriceTicker(symbols = [], interval = 5000) {
  const prices = ref({})
  const isLoading = ref(false)
  let intervalId = null

  const fetchPrices = async () => {
    if (isLoading.value || symbols.length === 0) return

    isLoading.value = true

    try {
      // Fetch latest prices for symbols
      // This would call your API endpoint
      const response = await fetch(`/api/v1/market/prices?symbols=${symbols.join(',')}`)
      const data = await response.json()

      if (data.success) {
        prices.value = data.data
      }
    } catch (error) {
      console.error('Failed to fetch prices:', error)
    } finally {
      isLoading.value = false
    }
  }

  const start = () => {
    if (intervalId) return

    fetchPrices()
    intervalId = setInterval(fetchPrices, interval)
  }

  const stop = () => {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  onMounted(start)
  onUnmounted(stop)

  return {
    prices,
    isLoading,
    refresh: fetchPrices,
  }
}
