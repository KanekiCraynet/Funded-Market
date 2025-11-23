import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAnalysisStore = defineStore('analysis', () => {
  // State
  const history = ref([])
  const currentAnalysis = ref(null)
  const isGenerating = ref(false)
  const isLoading = ref(false)
  const error = ref(null)
  const rateLimitRemaining = ref(0)
  const pagination = ref({
    currentPage: 1,
    totalPages: 1,
    perPage: 15,
    total: 0,
  })

  // Countdown timer
  let countdownInterval = null

  // Getters
  const hasHistory = computed(() => history.value.length > 0)
  
  const latestAnalysis = computed(() => {
    return history.value[0] || null
  })

  const isRateLimited = computed(() => rateLimitRemaining.value > 0)

  const analysisStats = computed(() => {
    const total = history.value.length
    const buySignals = history.value.filter(a => a.recommendation === 'BUY').length
    const sellSignals = history.value.filter(a => a.recommendation === 'SELL').length
    const holdSignals = history.value.filter(a => a.recommendation === 'HOLD').length
    
    return {
      total,
      buySignals,
      sellSignals,
      holdSignals,
      avgConfidence: history.value.reduce((sum, a) => sum + a.confidence, 0) / total || 0,
    }
  })

  // Actions
  async function generate(symbol) {
    isGenerating.value = true
    error.value = null

    try {
      const response = await axios.post('/api/v1/analysis/generate', {
        symbol: symbol.toUpperCase(),
      })

      const { data } = response.data

      currentAnalysis.value = data
      
      // Add to history at the beginning
      history.value.unshift(data)

      // Start rate limit countdown
      startRateLimitCountdown(data.rate_limit_reset || 60)

      return { success: true, data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to generate analysis'
      
      // Handle rate limit error
      if (err.response?.status === 429) {
        const retryAfter = err.response.data.retry_after || 60
        startRateLimitCountdown(retryAfter)
      }
      
      return { success: false, error: error.value }
    } finally {
      isGenerating.value = false
    }
  }

  async function fetchHistory(filters = {}) {
    isLoading.value = true
    error.value = null

    try {
      const response = await axios.get('/api/v1/analysis/history', {
        params: {
          page: filters.page || 1,
          per_page: filters.per_page || 15,
          symbol: filters.symbol,
          recommendation: filters.recommendation,
          date_from: filters.date_from,
          date_to: filters.date_to,
        }
      })

      const { data, pagination: paginationData } = response.data

      history.value = data
      pagination.value = paginationData

      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch history'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function fetchAnalysis(id) {
    isLoading.value = true
    error.value = null

    try {
      const response = await axios.get(`/api/v1/analysis/${id}`)
      currentAnalysis.value = response.data.data
      return { success: true, data: response.data.data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch analysis'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  async function deleteAnalysis(id) {
    try {
      await axios.delete(`/api/v1/analysis/${id}`)
      
      // Remove from history
      history.value = history.value.filter(a => a.id !== id)
      
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete analysis'
      return { success: false, error: error.value }
    }
  }

  async function fetchStats() {
    try {
      const response = await axios.get('/api/v1/analysis/stats')
      return response.data.data
    } catch (err) {
      console.error('Fetch stats error:', err)
      return null
    }
  }

  function startRateLimitCountdown(seconds) {
    rateLimitRemaining.value = seconds
    
    clearInterval(countdownInterval)
    
    countdownInterval = setInterval(() => {
      rateLimitRemaining.value--
      
      if (rateLimitRemaining.value <= 0) {
        clearInterval(countdownInterval)
      }
    }, 1000)
  }

  function clearRateLimit() {
    rateLimitRemaining.value = 0
    clearInterval(countdownInterval)
  }

  return {
    // State
    history,
    currentAnalysis,
    isGenerating,
    isLoading,
    error,
    rateLimitRemaining,
    pagination,

    // Getters
    hasHistory,
    latestAnalysis,
    isRateLimited,
    analysisStats,

    // Actions
    generate,
    fetchHistory,
    fetchAnalysis,
    deleteAnalysis,
    fetchStats,
    startRateLimitCountdown,
    clearRateLimit,
  }
})
