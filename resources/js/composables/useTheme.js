import { ref, watch, onMounted } from 'vue'

/**
 * Theme Management Composable
 * 
 * Phase 5 - Task 6: Dark mode support with persistence
 * 
 * Features:
 * - Dark/Light mode toggle
 * - System preference detection
 * - LocalStorage persistence
 * - Smooth transitions
 */

const THEME_KEY = 'app-theme'
const THEME_OPTIONS = ['light', 'dark', 'auto']

// Shared state across all components
const currentTheme = ref('dark') // Default to dark
const systemPrefersDark = ref(false)

export function useTheme() {
  /**
   * Get effective theme (resolves 'auto' to actual theme)
   */
  const effectiveTheme = computed(() => {
    if (currentTheme.value === 'auto') {
      return systemPrefersDark.value ? 'dark' : 'light'
    }
    return currentTheme.value
  })

  const isDark = computed(() => effectiveTheme.value === 'dark')

  /**
   * Set theme
   */
  const setTheme = (theme) => {
    if (!THEME_OPTIONS.includes(theme)) {
      console.warn(`Invalid theme: ${theme}. Using 'dark'`)
      theme = 'dark'
    }

    currentTheme.value = theme
    applyTheme()
    saveTheme()
  }

  /**
   * Toggle between light and dark
   */
  const toggleTheme = () => {
    const newTheme = isDark.value ? 'light' : 'dark'
    setTheme(newTheme)
  }

  /**
   * Apply theme to DOM
   */
  const applyTheme = () => {
    const theme = effectiveTheme.value
    const html = document.documentElement

    if (theme === 'dark') {
      html.classList.add('dark')
      html.classList.remove('light')
    } else {
      html.classList.add('light')
      html.classList.remove('dark')
    }

    // Set data attribute for CSS
    html.setAttribute('data-theme', theme)
  }

  /**
   * Save theme to localStorage
   */
  const saveTheme = () => {
    try {
      localStorage.setItem(THEME_KEY, currentTheme.value)
    } catch (e) {
      console.error('Failed to save theme:', e)
    }
  }

  /**
   * Load theme from localStorage
   */
  const loadTheme = () => {
    try {
      const saved = localStorage.getItem(THEME_KEY)
      if (saved && THEME_OPTIONS.includes(saved)) {
        currentTheme.value = saved
      }
    } catch (e) {
      console.error('Failed to load theme:', e)
    }
  }

  /**
   * Detect system theme preference
   */
  const detectSystemTheme = () => {
    if (window.matchMedia) {
      const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)')
      systemPrefersDark.value = darkModeQuery.matches

      // Listen for system theme changes
      darkModeQuery.addEventListener('change', (e) => {
        systemPrefersDark.value = e.matches
        if (currentTheme.value === 'auto') {
          applyTheme()
        }
      })
    }
  }

  /**
   * Initialize theme
   */
  const initTheme = () => {
    detectSystemTheme()
    loadTheme()
    applyTheme()
  }

  // Watch for theme changes
  watch(effectiveTheme, () => {
    applyTheme()
  })

  // Initialize on mount
  onMounted(() => {
    initTheme()
  })

  return {
    currentTheme,
    effectiveTheme,
    isDark,
    setTheme,
    toggleTheme,
    initTheme,
  }
}

// Export for direct use
export const themeState = {
  current: currentTheme,
  isDark: computed(() => effectiveTheme.value === 'dark'),
}
