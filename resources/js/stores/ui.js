/**
 * UI Store
 * Manages UI state, theme, sidebar, notifications, modals
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { STORAGE_KEYS, THEMES } from '../utils/constants'

export const useUIStore = defineStore('ui', () => {
  // State
  const theme = ref(THEMES.DARK)
  const sidebarCollapsed = ref(false)
  const notifications = ref([])
  const activeModal = ref(null)
  const loading = ref(false)
  const loadingMessage = ref('')
  
  // Load from localStorage
  const loadPreferences = () => {
    try {
      const storedTheme = localStorage.getItem(STORAGE_KEYS.THEME)
      if (storedTheme) {
        theme.value = storedTheme
      }
      
      const storedSidebar = localStorage.getItem(STORAGE_KEYS.SIDEBAR_COLLAPSED)
      if (storedSidebar) {
        sidebarCollapsed.value = JSON.parse(storedSidebar)
      }
    } catch (err) {
      console.error('Error loading UI preferences:', err)
    }
  }
  
  // Actions - Theme
  function setTheme(newTheme) {
    theme.value = newTheme
    localStorage.setItem(STORAGE_KEYS.THEME, newTheme)
    
    // Apply theme to document
    document.documentElement.classList.remove('light', 'dark')
    document.documentElement.classList.add(newTheme)
  }
  
  function toggleTheme() {
    const newTheme = theme.value === THEMES.DARK ? THEMES.LIGHT : THEMES.DARK
    setTheme(newTheme)
  }
  
  // Actions - Sidebar
  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem(STORAGE_KEYS.SIDEBAR_COLLAPSED, JSON.stringify(sidebarCollapsed.value))
  }
  
  function collapseSidebar() {
    sidebarCollapsed.value = true
    localStorage.setItem(STORAGE_KEYS.SIDEBAR_COLLAPSED, 'true')
  }
  
  function expandSidebar() {
    sidebarCollapsed.value = false
    localStorage.setItem(STORAGE_KEYS.SIDEBAR_COLLAPSED, 'false')
  }
  
  // Actions - Notifications
  function showNotification(notification) {
    const id = Date.now()
    const notif = {
      id,
      type: notification.type || 'info', // success, error, warning, info
      title: notification.title || '',
      message: notification.message || '',
      duration: notification.duration || 5000,
      action: notification.action || null
    }
    
    notifications.value.push(notif)
    
    // Auto remove after duration
    if (notif.duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, notif.duration)
    }
    
    return id
  }
  
  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }
  
  function clearNotifications() {
    notifications.value = []
  }
  
  // Shorthand notification methods
  function showSuccess(message, title = 'Success') {
    return showNotification({
      type: 'success',
      title,
      message
    })
  }
  
  function showError(message, title = 'Error') {
    return showNotification({
      type: 'error',
      title,
      message,
      duration: 7000
    })
  }
  
  function showWarning(message, title = 'Warning') {
    return showNotification({
      type: 'warning',
      title,
      message
    })
  }
  
  function showInfo(message, title = 'Info') {
    return showNotification({
      type: 'info',
      title,
      message
    })
  }
  
  // Actions - Modals
  function openModal(modalName, data = null) {
    activeModal.value = {
      name: modalName,
      data
    }
  }
  
  function closeModal() {
    activeModal.value = null
  }
  
  // Actions - Loading
  function startLoading(message = 'Loading...') {
    loading.value = true
    loadingMessage.value = message
  }
  
  function stopLoading() {
    loading.value = false
    loadingMessage.value = ''
  }
  
  // Getters
  const isDarkMode = computed(() => theme.value === THEMES.DARK)
  const isLightMode = computed(() => theme.value === THEMES.LIGHT)
  const hasNotifications = computed(() => notifications.value.length > 0)
  const notificationCount = computed(() => notifications.value.length)
  const hasActiveModal = computed(() => !!activeModal.value)
  const isLoading = computed(() => loading.value)
  
  // Initialize
  loadPreferences()
  
  return {
    // State
    theme,
    sidebarCollapsed,
    notifications,
    activeModal,
    loading,
    loadingMessage,
    
    // Actions - Theme
    setTheme,
    toggleTheme,
    
    // Actions - Sidebar
    toggleSidebar,
    collapseSidebar,
    expandSidebar,
    
    // Actions - Notifications
    showNotification,
    removeNotification,
    clearNotifications,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    
    // Actions - Modals
    openModal,
    closeModal,
    
    // Actions - Loading
    startLoading,
    stopLoading,
    
    // Getters
    isDarkMode,
    isLightMode,
    hasNotifications,
    notificationCount,
    hasActiveModal,
    isLoading
  }
})
