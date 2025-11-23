/**
 * Watchlist Store
 * Manages user's watchlist of instruments
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { STORAGE_KEYS } from '../utils/constants'

export const useWatchlistStore = defineStore('watchlist', () => {
  // State
  const instruments = ref([])
  const loading = ref(false)
  const error = ref(null)
  
  // Load from localStorage on init
  const loadFromStorage = () => {
    try {
      const stored = localStorage.getItem(STORAGE_KEYS.WATCHLIST)
      if (stored) {
        instruments.value = JSON.parse(stored)
      }
    } catch (err) {
      console.error('Error loading watchlist from storage:', err)
    }
  }
  
  // Save to localStorage
  const saveToStorage = () => {
    try {
      localStorage.setItem(STORAGE_KEYS.WATCHLIST, JSON.stringify(instruments.value))
    } catch (err) {
      console.error('Error saving watchlist to storage:', err)
    }
  }
  
  // Actions
  function addToWatchlist(instrument) {
    // Check if already exists
    const exists = instruments.value.find(i => i.symbol === instrument.symbol)
    if (exists) {
      return false
    }
    
    instruments.value.push({
      symbol: instrument.symbol,
      name: instrument.name,
      type: instrument.type,
      addedAt: new Date().toISOString()
    })
    
    saveToStorage()
    return true
  }
  
  function removeFromWatchlist(symbol) {
    const index = instruments.value.findIndex(i => i.symbol === symbol)
    if (index !== -1) {
      instruments.value.splice(index, 1)
      saveToStorage()
      return true
    }
    return false
  }
  
  function toggleWatchlist(instrument) {
    if (isInWatchlist(instrument.symbol)) {
      removeFromWatchlist(instrument.symbol)
      return false
    } else {
      addToWatchlist(instrument)
      return true
    }
  }
  
  function clearWatchlist() {
    instruments.value = []
    saveToStorage()
  }
  
  function clearError() {
    error.value = null
  }
  
  // Getters
  const isInWatchlist = computed(() => (symbol) => {
    return instruments.value.some(i => i.symbol === symbol)
  })
  
  const hasInstruments = computed(() => instruments.value.length > 0)
  
  const watchlistCount = computed(() => instruments.value.length)
  
  const sortedByAddedDate = computed(() => {
    return [...instruments.value].sort((a, b) => 
      new Date(b.addedAt) - new Date(a.addedAt)
    )
  })
  
  const sortedBySymbol = computed(() => {
    return [...instruments.value].sort((a, b) => 
      a.symbol.localeCompare(b.symbol)
    )
  })
  
  // Initialize
  loadFromStorage()
  
  return {
    // State
    instruments,
    loading,
    error,
    
    // Actions
    addToWatchlist,
    removeFromWatchlist,
    toggleWatchlist,
    clearWatchlist,
    clearError,
    
    // Getters
    isInWatchlist,
    hasInstruments,
    watchlistCount,
    sortedByAddedDate,
    sortedBySymbol
  }
})
