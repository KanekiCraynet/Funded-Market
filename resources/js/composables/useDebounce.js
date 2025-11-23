/**
 * Debounce Composable
 * Provides debouncing functionality for inputs and functions
 */

import { ref, watch, customRef } from 'vue'

/**
 * Debounced ref
 * @param {*} value - Initial value
 * @param {number} delay - Debounce delay in ms
 * @returns {Ref} Debounced ref
 */
export function useDebouncedRef(value, delay = 300) {
  const debounced = customRef((track, trigger) => {
    let timeout
    
    return {
      get() {
        track()
        return value
      },
      set(newValue) {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          value = newValue
          trigger()
        }, delay)
      }
    }
  })
  
  debounced.value = value
  
  return debounced
}

/**
 * Debounced function
 * @param {Function} fn - Function to debounce
 * @param {number} delay - Debounce delay in ms
 * @returns {Function} Debounced function
 */
export function useDebouncedFn(fn, delay = 300) {
  let timeout
  
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      fn(...args)
    }, delay)
  }
}

/**
 * Watch with debounce
 * @param {Ref} source - Source ref to watch
 * @param {Function} callback - Callback function
 * @param {number} delay - Debounce delay in ms
 * @param {object} options - Watch options
 */
export function useDebouncedWatch(source, callback, delay = 300, options = {}) {
  let timeout
  
  watch(
    source,
    (...args) => {
      clearTimeout(timeout)
      timeout = setTimeout(() => {
        callback(...args)
      }, delay)
    },
    options
  )
}

/**
 * Complete debounce composable with value and function
 * @param {*} initialValue - Initial value
 * @param {Function} callback - Callback when value changes
 * @param {number} delay - Debounce delay in ms
 * @returns {object} Debounce utilities
 */
export function useDebounce(initialValue, callback, delay = 300) {
  const value = ref(initialValue)
  const debouncedValue = ref(initialValue)
  
  let timeout
  
  watch(value, (newValue) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      debouncedValue.value = newValue
      if (callback) {
        callback(newValue)
      }
    }, delay)
  })
  
  function cancel() {
    clearTimeout(timeout)
  }
  
  function flush() {
    clearTimeout(timeout)
    debouncedValue.value = value.value
    if (callback) {
      callback(value.value)
    }
  }
  
  return {
    value,
    debouncedValue,
    cancel,
    flush
  }
}

export default {
  useDebouncedRef,
  useDebouncedFn,
  useDebouncedWatch,
  useDebounce
}
