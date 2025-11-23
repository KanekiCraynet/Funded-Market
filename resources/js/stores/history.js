/**
 * History Store
 * Manages analysis history, filters, and performance stats
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'

export const useHistoryStore = defineStore('history', () => {
  // State
  const analyses = ref([])
  const stats = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const lastUpdate = ref(null)
  
  // Filters
  const filters = ref({
    symbol: null,
    recommendation: null,
    date_from: null,
    date_to: null,
    search: ''
  })
  
  // Sort
  const sort = ref({
    by: 'created_at',
    order: 'desc'
  })
  
  // Pagination
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    total: 0
  })
  
  // Actions
  async function fetchHistory(params = {}) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get('/analysis/history', {
        params: {
          symbol: filters.value.symbol,
          recommendation: filters.value.recommendation,
          date_from: filters.value.date_from,
          date_to: filters.value.date_to,
          search: filters.value.search,
          sort_by: sort.value.by,
          order: sort.value.order,
          per_page: pagination.value.perPage,
          page: pagination.value.currentPage,
          ...params
        }
      })
      
      if (response.data.success) {
        analyses.value = response.data.data.data || response.data.data
        
        // Update pagination
        if (response.data.data.pagination) {
          pagination.value = {
            currentPage: response.data.data.pagination.current_page,
            lastPage: response.data.data.pagination.last_page,
            perPage: response.data.data.pagination.per_page,
            total: response.data.data.pagination.total
          }
        }
        
        lastUpdate.value = new Date()
      }
    } catch (err) {
      error.value = err.message || 'Failed to fetch history'
      console.error('Error fetching history:', err)
    } finally {
      loading.value = false
    }
  }
  
  async function fetchStats() {
    try {
      const response = await api.get('/analysis/stats')
      
      if (response.data.success) {
        stats.value = response.data.data
      }
    } catch (err) {
      console.error('Error fetching stats:', err)
    }
  }
  
  async function deleteAnalysis(id) {
    try {
      const response = await api.delete(`/analysis/${id}`)
      
      if (response.data.success) {
        // Remove from local list
        analyses.value = analyses.value.filter(a => a.id !== id)
        
        // Refresh stats
        fetchStats()
        
        return true
      }
    } catch (err) {
      console.error('Error deleting analysis:', err)
      throw err
    }
  }
  
  async function exportHistory(format = 'csv') {
    try {
      const response = await api.post('/analysis/export', {
        format,
        filters: filters.value
      }, {
        responseType: 'blob'
      })
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `analysis-history-${Date.now()}.${format}`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      
      return true
    } catch (err) {
      console.error('Error exporting history:', err)
      throw err
    }
  }
  
  function updateFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
    pagination.value.currentPage = 1
    fetchHistory()
  }
  
  function updateSort(newSort) {
    sort.value = { ...sort.value, ...newSort }
    fetchHistory()
  }
  
  function goToPage(page) {
    pagination.value.currentPage = page
    fetchHistory()
  }
  
  function clearFilters() {
    filters.value = {
      symbol: null,
      recommendation: null,
      date_from: null,
      date_to: null,
      search: ''
    }
    fetchHistory()
  }
  
  function clearError() {
    error.value = null
  }
  
  // Getters
  const filteredHistory = computed(() => {
    let result = [...analyses.value]
    
    // Client-side search if needed
    if (filters.value.search) {
      const searchLower = filters.value.search.toLowerCase()
      result = result.filter(a => 
        a.symbol?.toLowerCase().includes(searchLower) ||
        a.instrument_name?.toLowerCase().includes(searchLower)
      )
    }
    
    return result
  })
  
  const paginatedHistory = computed(() => {
    return filteredHistory.value
  })
  
  const hasHistory = computed(() => analyses.value.length > 0)
  
  const totalPages = computed(() => pagination.value.lastPage)
  
  const performanceMetrics = computed(() => {
    if (!stats.value) return null
    
    return {
      totalAnalyses: stats.value.total_analyses || 0,
      buyRecommendations: stats.value.buy_count || 0,
      sellRecommendations: stats.value.sell_count || 0,
      holdRecommendations: stats.value.hold_count || 0,
      averageConfidence: stats.value.avg_confidence || 0,
      mostAnalyzedSymbol: stats.value.most_analyzed_symbol || null
    }
  })
  
  const buyPercentage = computed(() => {
    if (!stats.value || !stats.value.total_analyses) return 0
    return (stats.value.buy_count / stats.value.total_analyses) * 100
  })
  
  const sellPercentage = computed(() => {
    if (!stats.value || !stats.value.total_analyses) return 0
    return (stats.value.sell_count / stats.value.total_analyses) * 100
  })
  
  const holdPercentage = computed(() => {
    if (!stats.value || !stats.value.total_analyses) return 0
    return (stats.value.hold_count / stats.value.total_analyses) * 100
  })
  
  return {
    // State
    analyses,
    stats,
    loading,
    error,
    lastUpdate,
    filters,
    sort,
    pagination,
    
    // Actions
    fetchHistory,
    fetchStats,
    deleteAnalysis,
    exportHistory,
    updateFilters,
    updateSort,
    goToPage,
    clearFilters,
    clearError,
    
    // Getters
    filteredHistory,
    paginatedHistory,
    hasHistory,
    totalPages,
    performanceMetrics,
    buyPercentage,
    sellPercentage,
    holdPercentage
  }
})
