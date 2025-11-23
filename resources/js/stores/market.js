import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'

export const useMarketStore = defineStore('market', () => {
  // State
  const overview = ref(null)
  const instruments = ref([])
  const selectedSymbol = ref('BTCUSDT')
  const marketData = ref({})
  const isLoading = ref(false)
  const error = ref(null)
  const pagination = ref({
    total: 0,
    currentPage: 1,
    lastPage: 1,
    perPage: 20
  })

  // Polling interval
  let pollingInterval = null

  // Getters
  const currentInstrument = computed(() => {
    return instruments.value.find(i => i.symbol === selectedSymbol.value)
  })

  const priceData = computed(() => {
    return marketData.value[selectedSymbol.value] || null
  })

  const trendingInstruments = computed(() => {
    return overview.value?.trending || []
  })

  const topGainers = computed(() => {
    return overview.value?.top_gainers || []
  })

  const topLosers = computed(() => {
    return overview.value?.top_losers || []
  })

  const hasInstruments = computed(() => {
    return instruments.value.length > 0
  })

  // Actions
  async function fetchMarketOverview() {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/market/overview')
      overview.value = response.data.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch market overview'
      console.error('Market overview error:', err)
    } finally {
      isLoading.value = false
    }
  }

  // Alias for compatibility
  const fetchOverview = fetchMarketOverview

  async function fetchInstruments(params = {}) {
    try {
      const response = await api.get('/market/instruments', { params })
      
      if (response.data.success) {
        instruments.value = response.data.data.data || response.data.data
        
        // Update pagination if present
        if (response.data.data.pagination) {
          pagination.value = {
            total: response.data.data.pagination.total,
            currentPage: response.data.data.pagination.current_page,
            lastPage: response.data.data.pagination.last_page,
            perPage: response.data.data.pagination.per_page
          }
        }
      }
    } catch (err) {
      console.error('Fetch instruments error:', err)
    }
  }

  async function fetchMarketData(symbol) {
    try {
      const response = await api.get(`/market/data/${symbol}`)
      marketData.value[symbol] = response.data.data
    } catch (err) {
      console.error(`Fetch market data error for ${symbol}:`, err)
    }
  }

  async function fetchHistoricalData(symbol, timeframe = '1h', limit = 100) {
    try {
      const response = await api.get(`/market/history/${symbol}`, {
        params: { timeframe, limit }
      })
      return response.data.data
    } catch (err) {
      console.error(`Fetch historical data error for ${symbol}:`, err)
      return []
    }
  }

  function setSelectedSymbol(symbol) {
    selectedSymbol.value = symbol
    fetchMarketData(symbol)
  }

  function startPolling(interval = 30000) {
    stopPolling()
    
    // Initial fetch
    fetchMarketOverview()
    if (selectedSymbol.value) {
      fetchMarketData(selectedSymbol.value)
    }

    // Setup polling
    pollingInterval = setInterval(() => {
      fetchMarketOverview()
      if (selectedSymbol.value) {
        fetchMarketData(selectedSymbol.value)
      }
    }, interval)
  }

  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
    }
  }

  return {
    // State
    overview,
    instruments,
    selectedSymbol,
    marketData,
    isLoading,
    error,
    pagination,

    // Getters
    currentInstrument,
    priceData,
    trendingInstruments,
    topGainers,
    topLosers,
    hasInstruments,

    // Actions
    fetchMarketOverview,
    fetchOverview,
    fetchInstruments,
    fetchMarketData,
    fetchHistoricalData,
    setSelectedSymbol,
    startPolling,
    stopPolling,
  }
})
