/**
 * Pagination Composable
 * Provides pagination logic and state management
 */

import { ref, computed } from 'vue'

export function usePagination(options = {}) {
  const {
    initialPage = 1,
    initialPerPage = 20,
    onPageChange = null
  } = options
  
  // State
  const currentPage = ref(initialPage)
  const perPage = ref(initialPerPage)
  const total = ref(0)
  
  // Computed
  const totalPages = computed(() => {
    if (total.value === 0 || perPage.value === 0) return 1
    return Math.ceil(total.value / perPage.value)
  })
  
  const hasNextPage = computed(() => {
    return currentPage.value < totalPages.value
  })
  
  const hasPrevPage = computed(() => {
    return currentPage.value > 1
  })
  
  const startItem = computed(() => {
    if (total.value === 0) return 0
    return (currentPage.value - 1) * perPage.value + 1
  })
  
  const endItem = computed(() => {
    const end = currentPage.value * perPage.value
    return end > total.value ? total.value : end
  })
  
  const pages = computed(() => {
    const pageCount = totalPages.value
    const current = currentPage.value
    const delta = 2 // Number of pages to show on each side
    
    const range = []
    const rangeWithDots = []
    
    for (let i = 1; i <= pageCount; i++) {
      if (
        i === 1 ||
        i === pageCount ||
        (i >= current - delta && i <= current + delta)
      ) {
        range.push(i)
      }
    }
    
    let l
    for (const i of range) {
      if (l) {
        if (i - l === 2) {
          rangeWithDots.push(l + 1)
        } else if (i - l !== 1) {
          rangeWithDots.push('...')
        }
      }
      rangeWithDots.push(i)
      l = i
    }
    
    return rangeWithDots
  })
  
  // Methods
  function goToPage(page) {
    if (page < 1 || page > totalPages.value) return
    
    currentPage.value = page
    
    if (onPageChange) {
      onPageChange(page)
    }
  }
  
  function nextPage() {
    if (hasNextPage.value) {
      goToPage(currentPage.value + 1)
    }
  }
  
  function prevPage() {
    if (hasPrevPage.value) {
      goToPage(currentPage.value - 1)
    }
  }
  
  function firstPage() {
    goToPage(1)
  }
  
  function lastPage() {
    goToPage(totalPages.value)
  }
  
  function setPerPage(newPerPage) {
    perPage.value = newPerPage
    currentPage.value = 1 // Reset to first page
    
    if (onPageChange) {
      onPageChange(1)
    }
  }
  
  function setTotal(newTotal) {
    total.value = newTotal
  }
  
  function reset() {
    currentPage.value = initialPage
    perPage.value = initialPerPage
    total.value = 0
  }
  
  return {
    // State
    currentPage,
    perPage,
    total,
    
    // Computed
    totalPages,
    hasNextPage,
    hasPrevPage,
    startItem,
    endItem,
    pages,
    
    // Methods
    goToPage,
    nextPage,
    prevPage,
    firstPage,
    lastPage,
    setPerPage,
    setTotal,
    reset
  }
}

export default usePagination
