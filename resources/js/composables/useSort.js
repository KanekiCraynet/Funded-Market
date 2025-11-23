/**
 * Sort Composable
 * Provides sorting functionality for lists and data
 */

import { ref, computed } from 'vue'

export function useSort(items, options = {}) {
  const {
    defaultSortBy = null,
    defaultSortOrder = 'asc',
    sortFn = null
  } = options
  
  // State
  const sortBy = ref(defaultSortBy)
  const sortOrder = ref(defaultSortOrder)
  
  // Computed sorted items
  const sortedItems = computed(() => {
    if (!items.value || items.value.length === 0) {
      return []
    }
    
    if (!sortBy.value) {
      return items.value
    }
    
    // Use custom sort function if provided
    if (sortFn) {
      return sortFn(items.value, sortBy.value, sortOrder.value)
    }
    
    // Default sorting logic
    const sorted = [...items.value].sort((a, b) => {
      const aValue = getNestedValue(a, sortBy.value)
      const bValue = getNestedValue(b, sortBy.value)
      
      // Handle null/undefined
      if (aValue == null && bValue == null) return 0
      if (aValue == null) return 1
      if (bValue == null) return -1
      
      // String comparison
      if (typeof aValue === 'string' && typeof bValue === 'string') {
        return aValue.localeCompare(bValue)
      }
      
      // Number/Date comparison
      if (aValue < bValue) return -1
      if (aValue > bValue) return 1
      return 0
    })
    
    // Reverse if descending
    return sortOrder.value === 'desc' ? sorted.reverse() : sorted
  })
  
  // Helper to get nested object value
  function getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current?.[key], obj)
  }
  
  // Methods
  function setSortBy(field) {
    if (sortBy.value === field) {
      // Toggle order if same field
      toggleSortOrder()
    } else {
      sortBy.value = field
      sortOrder.value = defaultSortOrder
    }
  }
  
  function setSortOrder(order) {
    sortOrder.value = order
  }
  
  function toggleSortOrder() {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  }
  
  function clearSort() {
    sortBy.value = defaultSortBy
    sortOrder.value = defaultSortOrder
  }
  
  return {
    sortBy,
    sortOrder,
    sortedItems,
    setSortBy,
    setSortOrder,
    toggleSortOrder,
    clearSort
  }
}

export default useSort
