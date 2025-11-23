/**
 * Filter Composable
 * Provides filtering functionality for lists and data
 */

import { ref, computed } from 'vue'

export function useFilter(items, options = {}) {
  const {
    defaultFilters = {},
    filterFn = null
  } = options
  
  // State
  const filters = ref({ ...defaultFilters })
  
  // Computed filtered items
  const filteredItems = computed(() => {
    if (!items.value || items.value.length === 0) {
      return []
    }
    
    // Use custom filter function if provided
    if (filterFn) {
      return filterFn(items.value, filters.value)
    }
    
    // Default filtering logic
    return items.value.filter(item => {
      for (const [key, value] of Object.entries(filters.value)) {
        // Skip null/undefined/empty filters
        if (value === null || value === undefined || value === '') {
          continue
        }
        
        // Array filters (multiple values)
        if (Array.isArray(value)) {
          if (value.length === 0) continue
          if (!value.includes(item[key])) {
            return false
          }
        }
        // String filters (case-insensitive contains)
        else if (typeof value === 'string') {
          const itemValue = String(item[key] || '').toLowerCase()
          const filterValue = value.toLowerCase()
          if (!itemValue.includes(filterValue)) {
            return false
          }
        }
        // Number/Boolean filters (exact match)
        else {
          if (item[key] !== value) {
            return false
          }
        }
      }
      
      return true
    })
  })
  
  // Methods
  function setFilter(key, value) {
    filters.value[key] = value
  }
  
  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }
  
  function clearFilter(key) {
    filters.value[key] = defaultFilters[key] || null
  }
  
  function clearAllFilters() {
    filters.value = { ...defaultFilters }
  }
  
  function hasActiveFilters() {
    return Object.values(filters.value).some(v => 
      v !== null && v !== undefined && v !== '' && (!Array.isArray(v) || v.length > 0)
    )
  }
  
  function getActiveFilters() {
    const active = {}
    for (const [key, value] of Object.entries(filters.value)) {
      if (value !== null && value !== undefined && value !== '' && (!Array.isArray(value) || value.length > 0)) {
        active[key] = value
      }
    }
    return active
  }
  
  return {
    filters,
    filteredItems,
    setFilter,
    setFilters,
    clearFilter,
    clearAllFilters,
    hasActiveFilters,
    getActiveFilters
  }
}

export default useFilter
