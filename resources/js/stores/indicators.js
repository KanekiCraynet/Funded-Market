/**
 * Indicators Store
 * Manages technical indicators data
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'

export const useIndicatorsStore = defineStore('indicators', () => {
  // State
  const allIndicators = ref(null)
  const selectedSymbol = ref(null)
  const selectedTimeframe = ref('1d')
  const selectedCategory = ref('trend')
  const loading = ref(false)
  const error = ref(null)
  const lastUpdate = ref(null)
  
  // Actions
  async function fetchIndicators(symbol, period = 200) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(`/api/v1/quant/indicators/${symbol}`, {
        params: { period }
      })
      
      if (response.data.success) {
        allIndicators.value = response.data.data
        selectedSymbol.value = symbol
        lastUpdate.value = new Date()
        return response.data.data
      }
    } catch (err) {
      error.value = err.message || 'Failed to fetch indicators'
      console.error('Error fetching indicators:', err)
      throw err
    } finally {
      loading.value = false
    }
  }
  
  function selectCategory(category) {
    selectedCategory.value = category
  }
  
  function selectTimeframe(timeframe) {
    selectedTimeframe.value = timeframe
    if (selectedSymbol.value) {
      fetchIndicators(selectedSymbol.value)
    }
  }
  
  function clearIndicators() {
    allIndicators.value = null
    selectedSymbol.value = null
    error.value = null
  }
  
  function clearError() {
    error.value = null
  }
  
  // Getters
  const hasIndicators = computed(() => !!allIndicators.value)
  
  const trendIndicators = computed(() => {
    return allIndicators.value?.trend || null
  })
  
  const momentumIndicators = computed(() => {
    return allIndicators.value?.momentum || null
  })
  
  const volatilityIndicators = computed(() => {
    return allIndicators.value?.volatility || null
  })
  
  const volumeIndicators = computed(() => {
    return allIndicators.value?.volume || null
  })
  
  const compositeScore = computed(() => {
    return allIndicators.value?.composite || null
  })
  
  const currentCategoryData = computed(() => {
    if (!allIndicators.value) return null
    
    switch (selectedCategory.value) {
      case 'trend':
        return trendIndicators.value
      case 'momentum':
        return momentumIndicators.value
      case 'volatility':
        return volatilityIndicators.value
      case 'volume':
        return volumeIndicators.value
      case 'composite':
        return compositeScore.value
      default:
        return null
    }
  })
  
  // Helper function to get indicator signal
  const getIndicatorSignal = computed(() => (indicatorName, value) => {
    // RSI
    if (indicatorName === 'rsi') {
      if (value > 70) return { signal: 'OVERBOUGHT', color: 'red' }
      if (value < 30) return { signal: 'OVERSOLD', color: 'green' }
      return { signal: 'NEUTRAL', color: 'yellow' }
    }
    
    // MACD
    if (indicatorName === 'macd') {
      if (value > 0) return { signal: 'BULLISH', color: 'green' }
      if (value < 0) return { signal: 'BEARISH', color: 'red' }
      return { signal: 'NEUTRAL', color: 'yellow' }
    }
    
    // ADX
    if (indicatorName === 'adx') {
      if (value > 25) return { signal: 'STRONG TREND', color: 'green' }
      if (value < 20) return { signal: 'WEAK TREND', color: 'red' }
      return { signal: 'MODERATE', color: 'yellow' }
    }
    
    // Stochastic
    if (indicatorName === 'stochastic') {
      if (value > 80) return { signal: 'OVERBOUGHT', color: 'red' }
      if (value < 20) return { signal: 'OVERSOLD', color: 'green' }
      return { signal: 'NEUTRAL', color: 'yellow' }
    }
    
    // Williams %R
    if (indicatorName === 'williams_r') {
      if (value > -20) return { signal: 'OVERBOUGHT', color: 'red' }
      if (value < -80) return { signal: 'OVERSOLD', color: 'green' }
      return { signal: 'NEUTRAL', color: 'yellow' }
    }
    
    // Default
    return { signal: 'NEUTRAL', color: 'gray' }
  })
  
  const indicatorsList = computed(() => {
    if (!allIndicators.value) return []
    
    const list = []
    
    // Trend indicators
    if (trendIndicators.value) {
      Object.entries(trendIndicators.value).forEach(([key, value]) => {
        if (typeof value === 'number') {
          list.push({
            name: key,
            category: 'trend',
            value,
            signal: getIndicatorSignal.value(key, value)
          })
        }
      })
    }
    
    // Momentum indicators
    if (momentumIndicators.value) {
      Object.entries(momentumIndicators.value).forEach(([key, value]) => {
        if (typeof value === 'number') {
          list.push({
            name: key,
            category: 'momentum',
            value,
            signal: getIndicatorSignal.value(key, value)
          })
        }
      })
    }
    
    // Volatility indicators
    if (volatilityIndicators.value) {
      Object.entries(volatilityIndicators.value).forEach(([key, value]) => {
        if (typeof value === 'number') {
          list.push({
            name: key,
            category: 'volatility',
            value,
            signal: getIndicatorSignal.value(key, value)
          })
        }
      })
    }
    
    // Volume indicators
    if (volumeIndicators.value) {
      Object.entries(volumeIndicators.value).forEach(([key, value]) => {
        if (typeof value === 'number') {
          list.push({
            name: key,
            category: 'volume',
            value,
            signal: getIndicatorSignal.value(key, value)
          })
        }
      })
    }
    
    return list
  })
  
  return {
    // State
    allIndicators,
    selectedSymbol,
    selectedTimeframe,
    selectedCategory,
    loading,
    error,
    lastUpdate,
    
    // Actions
    fetchIndicators,
    selectCategory,
    selectTimeframe,
    clearIndicators,
    clearError,
    
    // Getters
    hasIndicators,
    trendIndicators,
    momentumIndicators,
    volatilityIndicators,
    volumeIndicators,
    compositeScore,
    currentCategoryData,
    getIndicatorSignal,
    indicatorsList
  }
})
